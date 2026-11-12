<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use App\Support\Money;
use Brick\Math\RoundingMode;
use DOMDocument;
use DOMElement;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CoretaxService
{
    public const ITEM_OPT = 'B';
    public const ITEM_CODE = '060000';
    public const ITEM_NAME = 'Jasa Angkutan Barang';
    public const ITEM_UNIT = 'UM.0033';

    public function __construct(private MasterDataService $master) {}

    public function generate(Invoice $invoice, User $actor, bool $log = true, ?iterable $selectedItems = null): string
    {
        $invoice->loadMissing('items');
        $sourceItems = $selectedItems === null ? $invoice->items : collect($selectedItems);
        // Biaya temporary/reimbursement bukan objek PPN sehingga tidak dimasukkan ke XML PPN Keluaran
        $selected = $sourceItems->filter(fn ($item) => $item->type !== 'temporary')->values();
        if ($selected->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Pilih minimal satu charge (non-reimbursement) untuk XML Coretax.']);
        }
        if (Money::decimal($invoice->tax)->isZero()) {
            throw ValidationException::withMessages(['invoice' => 'Invoice tidak memiliki PPN sehingga tidak dapat diekspor ke Coretax.']);
        }
        $subtotal = Money::decimal($selected->sum('amount'));
        if ($subtotal->isZero()) {
            throw ValidationException::withMessages(['invoice' => 'DPP invoice nol sehingga tidak dapat diekspor ke Coretax.']);
        }
        $provisionTotal = Money::decimal($invoice->items->where('type', '!=', 'temporary')->sum('amount'));
        $tax = ($selectedItems === null || $selected->count() === $invoice->items->where('type', '!=', 'temporary')->count())
            ? Money::decimal($invoice->tax)
            : ($provisionTotal->isPositive()
                ? $subtotal->multipliedBy(Money::decimal($invoice->tax))->dividedBy($provisionTotal, 2, RoundingMode::HalfUp)
                : Money::decimal($invoice->tax));

        $seller = config('accounting.coretax');
        $rawSellerNpwp = preg_replace('/\D/', '', (string) ($seller['seller_npwp'] ?? '0270189186032000'));
        $sellerTin = str_pad($rawSellerNpwp, 16, '0', STR_PAD_LEFT);
        $sellerIdtku = $sellerTin.'000000';

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElement('TaxInvoiceBulk');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:noNamespaceSchemaLocation', 'TaxInvoice.xsd');
        $doc->appendChild($root);

        $this->leaf($doc, $root, 'TIN', $sellerTin);

        $listOfTaxInvoice = $this->element($doc, 'ListOfTaxInvoice', $root);
        $this->appendTaxInvoiceNode($doc, $listOfTaxInvoice, $invoice, $selected, $subtotal, $tax, $sellerIdtku, $seller);

        if ($log) {
            $this->master->log($actor, 'invoice.coretax.exported', 'Ekspor XML Coretax '.$invoice->number, ['module' => 'invoice', 'record_id' => $invoice->id, 'after' => ['dpp' => Money::format((string) $subtotal), 'ppn' => Money::format((string) $tax), 'currency' => $invoice->currency]]);
        }

        return $doc->saveXML();
    }

    public function generateBulk(iterable $invoices, User $actor, bool $log = true): string
    {
        $invoices = collect($invoices)->values();
        if ($invoices->isEmpty()) {
            throw ValidationException::withMessages(['invoice_ids' => 'Pilih minimal satu invoice untuk diekspor ke Coretax.']);
        }

        $seller = config('accounting.coretax');
        $rawSellerNpwp = preg_replace('/\D/', '', (string) ($seller['seller_npwp'] ?? '0270189186032000'));
        $sellerTin = str_pad($rawSellerNpwp, 16, '0', STR_PAD_LEFT);
        $sellerIdtku = $sellerTin.'000000';

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElement('TaxInvoiceBulk');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:noNamespaceSchemaLocation', 'TaxInvoice.xsd');
        $doc->appendChild($root);

        $this->leaf($doc, $root, 'TIN', $sellerTin);
        $listOfTaxInvoice = $this->element($doc, 'ListOfTaxInvoice', $root);

        $exportedInvoices = [];
        $totalDpp = Money::decimal('0');
        $totalTax = Money::decimal('0');

        foreach ($invoices as $invoice) {
            $invoice->loadMissing('items');
            $selected = $invoice->items->filter(fn ($item) => $item->type !== 'temporary')->values();
            if ($selected->isEmpty() || Money::decimal($invoice->tax)->isZero()) {
                continue;
            }
            $subtotal = Money::decimal($selected->sum('amount'));
            if ($subtotal->isZero()) {
                continue;
            }
            $tax = Money::decimal($invoice->tax);

            $this->appendTaxInvoiceNode($doc, $listOfTaxInvoice, $invoice, $selected, $subtotal, $tax, $sellerIdtku, $seller);

            $totalDpp = $totalDpp->plus($subtotal);
            $totalTax = $totalTax->plus($tax);
            $exportedInvoices[] = $invoice;
        }

        if (empty($exportedInvoices)) {
            throw ValidationException::withMessages(['invoice_ids' => 'Tidak ada invoice terpilih yang memiliki PPN dan biaya non-reimbursement valid.']);
        }

        if ($log) {
            $this->master->log($actor, 'invoice.coretax.bulk_exported', 'Ekspor Bulk XML Coretax ('.count($exportedInvoices).' faktur)', [
                'module' => 'invoice',
                'record_ids' => collect($exportedInvoices)->pluck('id')->all(),
                'after' => [
                    'count' => count($exportedInvoices),
                    'invoices' => collect($exportedInvoices)->pluck('number')->all(),
                    'total_dpp' => Money::format((string) $totalDpp),
                    'total_ppn' => Money::format((string) $totalTax),
                ],
            ]);
        }

        return $doc->saveXML();
    }

    public function exportExcel(iterable $invoices, User $actor, bool $log = true): Spreadsheet
    {
        $invoices = collect($invoices)->values();
        if ($invoices->isEmpty()) {
            throw ValidationException::withMessages(['invoice_ids' => 'Pilih minimal satu invoice untuk diekspor ke Excel.']);
        }

        $seller = config('accounting.coretax');
        $rawSellerNpwp = preg_replace('/\D/', '', (string) ($seller['seller_npwp'] ?? '0270189186032000'));
        $sellerTin = str_pad($rawSellerNpwp, 16, '0', STR_PAD_LEFT);
        $sellerIdtku = $sellerTin.'000000';
        $vatRate = (int) round((float) ($seller['vat_rate'] ?? 12));

        $spreadsheet = new Spreadsheet();

        // 1. Sheet Faktur
        $fakturSheet = $spreadsheet->getActiveSheet();
        $fakturSheet->setTitle('Faktur');

        // Metadata Penjual di baris 1
        $fakturSheet->setCellValue('A1', 'NPWP Penjual');
        $fakturSheet->setCellValueExplicit('C1', $sellerTin, DataType::TYPE_STRING);

        // Header kolom Faktur di baris 3 sesuai template resmi DJP
        $fakturHeaders = [
            'Baris',
            'Tanggal Faktur',
            'Jenis Faktur',
            'Kode Transaksi',
            'Keterangan Tambahan',
            'Dokumen Pendukung',
            'Period Dok Pendukung',
            'Referensi',
            'Cap Fasilitas',
            'NITKU Penjual',
            'NPWP Pembeli',
            'Jenis ID Pembeli',
            'Negara Pembeli',
            'Nomor Dokumen Pembeli',
            'Nama Pembeli',
            'Alamat Pembeli',
            'Email Pembeli',
            'NITKU Pembeli',
        ];
        $fakturSheet->fromArray([$fakturHeaders], null, 'A3');

        // 2. Sheet DetailFaktur
        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('DetailFaktur');

        $detailHeaders = [
            'Baris',
            'Barang/Jasa',
            'Kode Barang / Jasa',
            'Nama Barang / Jasa',
            'Satuan Ukur',
            'Harga Satuan',
            'Jumlah (Qty)',
            'Diskon',
            'DPP',
            'DPP Nilai Lain',
            'Tarif PPN (%)',
            'PPN',
            'Tarif PPnBM',
            'PPnBM',
        ];
        $detailSheet->fromArray([$detailHeaders], null, 'A1');

        $fakturRow = 4;
        $detailRow = 2;
        $barisIndex = 1;

        $exportedInvoices = [];
        $totalDpp = Money::decimal('0');
        $totalTax = Money::decimal('0');

        foreach ($invoices as $invoice) {
            $invoice->loadMissing('items');
            $selected = $invoice->items->filter(fn ($item) => $item->type !== 'temporary')->values();
            if ($selected->isEmpty() || Money::decimal($invoice->tax)->isZero()) {
                continue;
            }
            $subtotal = Money::decimal($selected->sum('amount'));
            if ($subtotal->isZero()) {
                continue;
            }
            $tax = Money::decimal($invoice->tax);

            $buyer = $invoice->customer_snapshot ?? [];
            $rawBuyerNpwp = preg_replace('/\D/', '', (string) ($buyer['tax_number'] ?? ''));
            $buyerTin = $rawBuyerNpwp !== '' ? str_pad($rawBuyerNpwp, 16, '0', STR_PAD_LEFT) : '';
            $buyerIdtku = $buyerTin !== '' ? $buyerTin.'000000' : '';
            $invoiceDate = $invoice->invoice_date ? $invoice->invoice_date->format('d/m/Y') : now()->format('d/m/Y');

            // Populate Faktur Row (mulai baris 4)
            $fakturSheet->setCellValue("A{$fakturRow}", $barisIndex);
            $fakturSheet->setCellValue("B{$fakturRow}", $invoiceDate);
            $fakturSheet->setCellValue("C{$fakturRow}", 'Normal');
            $fakturSheet->setCellValueExplicit("D{$fakturRow}", '05', DataType::TYPE_STRING);
            $fakturSheet->setCellValue("E{$fakturRow}", '');
            $fakturSheet->setCellValue("F{$fakturRow}", '');
            $fakturSheet->setCellValue("G{$fakturRow}", '');
            $fakturSheet->setCellValueExplicit("H{$fakturRow}", $invoice->number, DataType::TYPE_STRING);
            $fakturSheet->setCellValue("I{$fakturRow}", '');
            $fakturSheet->setCellValueExplicit("J{$fakturRow}", $sellerIdtku, DataType::TYPE_STRING);
            $fakturSheet->setCellValueExplicit("K{$fakturRow}", $buyerTin, DataType::TYPE_STRING);
            $fakturSheet->setCellValue("L{$fakturRow}", 'TIN');
            $fakturSheet->setCellValue("M{$fakturRow}", 'IDN');
            $fakturSheet->setCellValue("N{$fakturRow}", '-');
            $fakturSheet->setCellValue("O{$fakturRow}", (string) ($buyer['name'] ?? ''));
            $fakturSheet->setCellValue("P{$fakturRow}", (string) ($buyer['address'] ?? ''));
            $fakturSheet->setCellValue("Q{$fakturRow}", (string) ($buyer['email'] ?? ''));
            $fakturSheet->setCellValueExplicit("R{$fakturRow}", $buyerIdtku, DataType::TYPE_STRING);

            // Populate DetailFaktur: Tepat 1 baris terkonsolidasi dengan nama jasa terkunci "Jasa Angkutan Barang"
            $detailSheet->setCellValue("A{$detailRow}", $barisIndex);
            $detailSheet->setCellValueExplicit("B{$detailRow}", self::ITEM_OPT, DataType::TYPE_STRING);
            $detailSheet->setCellValueExplicit("C{$detailRow}", self::ITEM_CODE, DataType::TYPE_STRING);
            $detailSheet->setCellValue("D{$detailRow}", self::ITEM_NAME);
            $detailSheet->setCellValueExplicit("E{$detailRow}", self::ITEM_UNIT, DataType::TYPE_STRING);
            $detailSheet->setCellValue("F{$detailRow}", (float) (string) $subtotal);
            $detailSheet->setCellValue("G{$detailRow}", 1);
            $detailSheet->setCellValue("H{$detailRow}", 0);
            $detailSheet->setCellValue("I{$detailRow}", (float) (string) $subtotal);
            $detailSheet->setCellValue("J{$detailRow}", null);
            $detailSheet->setCellValue("K{$detailRow}", $vatRate);
            $detailSheet->setCellValue("L{$detailRow}", (float) (string) $tax);
            $detailSheet->setCellValue("M{$detailRow}", 0);
            $detailSheet->setCellValue("N{$detailRow}", "=M{$detailRow}*J{$detailRow}/100");

            $totalDpp = $totalDpp->plus($subtotal);
            $totalTax = $totalTax->plus($tax);
            $exportedInvoices[] = $invoice;
            $barisIndex++;
            $fakturRow++;
            $detailRow++;
        }

        if (empty($exportedInvoices)) {
            throw ValidationException::withMessages(['invoice_ids' => 'Tidak ada invoice terpilih yang memiliki PPN dan biaya non-reimbursement valid.']);
        }

        // Baris penutup 'END'
        $fakturSheet->setCellValue("A{$fakturRow}", 'END');
        $detailSheet->setCellValue("A{$detailRow}", 'END');

        // Apply styles ke kedua sheet
        $this->styleFakturSheet($fakturSheet, $fakturRow);
        $this->styleDetailSheet($detailSheet, $detailRow);

        $spreadsheet->setActiveSheetIndex(0);

        if ($log) {
            $this->master->log($actor, 'invoice.coretax.excel_exported', 'Ekspor Excel Coretax ('.count($exportedInvoices).' faktur)', [
                'module' => 'invoice',
                'record_ids' => collect($exportedInvoices)->pluck('id')->all(),
                'after' => [
                    'count' => count($exportedInvoices),
                    'invoices' => collect($exportedInvoices)->pluck('number')->all(),
                    'total_dpp' => Money::format((string) $totalDpp),
                    'total_ppn' => Money::format((string) $totalTax),
                ],
            ]);
        }

        return $spreadsheet;
    }

    private function appendTaxInvoiceNode(
        DOMDocument $doc,
        DOMElement $listOfTaxInvoice,
        Invoice $invoice,
        $selectedItems,
        $subtotal,
        $tax,
        string $sellerIdtku,
        array $seller
    ): void {
        $taxInvoice = $this->element($doc, 'TaxInvoice', $listOfTaxInvoice);

        $buyer = $invoice->customer_snapshot ?? [];
        $rawBuyerNpwp = preg_replace('/\D/', '', (string) ($buyer['tax_number'] ?? ''));
        $buyerTin = $rawBuyerNpwp !== '' ? str_pad($rawBuyerNpwp, 16, '0', STR_PAD_LEFT) : '';
        $buyerIdtku = $buyerTin !== '' ? $buyerTin.'000000' : '';

        $invoiceDate = $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : now()->format('Y-m-d');
        $this->leaf($doc, $taxInvoice, 'TaxInvoiceDate', $invoiceDate);
        $this->leaf($doc, $taxInvoice, 'TaxInvoiceOpt', 'Normal');
        $this->leaf($doc, $taxInvoice, 'TrxCode', '05');
        $this->leaf($doc, $taxInvoice, 'AddInfo', '');
        $this->leaf($doc, $taxInvoice, 'CustomDoc', '');
        $this->leaf($doc, $taxInvoice, 'RefDesc', $invoice->number);
        $this->leaf($doc, $taxInvoice, 'FacilityStamp', '');
        $this->leaf($doc, $taxInvoice, 'SellerIDTKU', $sellerIdtku);
        $this->leaf($doc, $taxInvoice, 'BuyerTin', $buyerTin);
        $this->leaf($doc, $taxInvoice, 'BuyerDocument', 'TIN');
        $this->leaf($doc, $taxInvoice, 'BuyerCountry', 'IDN');
        $this->leaf($doc, $taxInvoice, 'BuyerDocumentNumber', '-');
        $this->leaf($doc, $taxInvoice, 'BuyerName', (string) ($buyer['name'] ?? ''));
        $this->leaf($doc, $taxInvoice, 'BuyerAddress', (string) ($buyer['address'] ?? ''));
        $this->leaf($doc, $taxInvoice, 'BuyerEmail', (string) ($buyer['email'] ?? ''));
        $this->leaf($doc, $taxInvoice, 'BuyerIDTKU', $buyerIdtku);

        $listOfGoodService = $this->element($doc, 'ListOfGoodService', $taxInvoice);

        $vatRate = (int) round((float) ($seller['vat_rate'] ?? 12));
        $itemAmount = (float) (string) $subtotal;
        $totalTaxInt = (int) round((float) (string) $tax);

        $goodService = $this->element($doc, 'GoodService', $listOfGoodService);
        $this->leaf($doc, $goodService, 'Opt', self::ITEM_OPT);
        $this->leaf($doc, $goodService, 'Code', self::ITEM_CODE);
        $this->leaf($doc, $goodService, 'Name', self::ITEM_NAME);
        $this->leaf($doc, $goodService, 'Unit', self::ITEM_UNIT);
        $this->leaf($doc, $goodService, 'Price', (string) (int) round($itemAmount));
        $this->leaf($doc, $goodService, 'Qty', '1');
        $this->leaf($doc, $goodService, 'TotalDiscount', '0');
        $this->leaf($doc, $goodService, 'TaxBase', (string) (int) round($itemAmount));
        $this->leaf($doc, $goodService, 'OtherTaxBase', '0');
        $this->leaf($doc, $goodService, 'VATRate', (string) $vatRate);
        $this->leaf($doc, $goodService, 'VAT', (string) max(0, $totalTaxInt));
        $this->leaf($doc, $goodService, 'STLGRate', '0');
        $this->leaf($doc, $goodService, 'STLG', '0');
    }

    private function styleFakturSheet(Worksheet $sheet, int $endRow): void
    {
        $sheet->freezePane('A4');
        if ($endRow > 4) {
            $sheet->setAutoFilter('A3:R'.($endRow - 1));
        }

        $sheet->getStyle('A1')->getFont()->setBold(true);

        $headerStyle = $sheet->getStyle('A3:R3');
        $headerStyle->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0F1F3D');
        $headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        foreach ($sheet->getColumnIterator('A', 'R') as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    private function styleDetailSheet(Worksheet $sheet, int $endRow): void
    {
        $sheet->freezePane('A2');
        if ($endRow > 2) {
            $sheet->setAutoFilter('A1:N'.($endRow - 1));
        }

        $headerStyle = $sheet->getStyle('A1:N1');
        $headerStyle->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0F1F3D');
        $headerStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        if ($endRow > 2) {
            $dataLastRow = $endRow - 1;
            foreach (['F', 'I', 'L'] as $col) {
                $sheet->getStyle("{$col}2:{$col}{$dataLastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }
            $sheet->getStyle("N2:N{$dataLastRow}")
                ->getNumberFormat()
                ->setFormatCode('0.00');
        }

        foreach ($sheet->getColumnIterator('A', 'N') as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    private function element(DOMDocument $doc, string $name, ?DOMElement $parent): DOMElement
    {
        $node = $doc->createElement($name);

        if ($parent !== null) {
            $parent->appendChild($node);
        }

        return $node;
    }

    private function leaf(DOMDocument $doc, DOMElement $parent, string $name, string $value): void
    {
        $node = $doc->createElement($name);
        if ($value !== '') {
            $node->textContent = $value;
        }
        $parent->appendChild($node);
    }
}
