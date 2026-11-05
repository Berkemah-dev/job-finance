<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use App\Support\Money;
use Brick\Math\RoundingMode;
use DOMDocument;
use DOMElement;
use Illuminate\Validation\ValidationException;

class CoretaxService
{
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

        $buyer = $invoice->customer_snapshot ?? [];
        $rawBuyerNpwp = preg_replace('/\D/', '', (string) ($buyer['tax_number'] ?? ''));
        $buyerTin = $rawBuyerNpwp !== '' ? str_pad($rawBuyerNpwp, 16, '0', STR_PAD_LEFT) : '';
        $buyerIdtku = $buyerTin !== '' ? $buyerTin.'000000' : '';

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElement('TaxInvoiceBulk');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:noNamespaceSchemaLocation', 'TaxInvoice.xsd');
        $doc->appendChild($root);

        $this->leaf($doc, $root, 'TIN', $sellerTin);

        $listOfTaxInvoice = $this->element($doc, 'ListOfTaxInvoice', $root);
        $taxInvoice = $this->element($doc, 'TaxInvoice', $listOfTaxInvoice);

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
        $this->leaf($doc, $taxInvoice, 'BuyerCountry', 'IND');
        $this->leaf($doc, $taxInvoice, 'BuyerDocumentNumber', '');
        $this->leaf($doc, $taxInvoice, 'BuyerName', (string) ($buyer['name'] ?? ''));
        $this->leaf($doc, $taxInvoice, 'BuyerAdress', (string) ($buyer['address'] ?? ''));
        $this->leaf($doc, $taxInvoice, 'BuyerEmail', (string) ($buyer['email'] ?? ''));
        $this->leaf($doc, $taxInvoice, 'BuyerIDTKU', $buyerIdtku);

        $listOfGoodService = $this->element($doc, 'ListOfGoodService', $taxInvoice);

        $totalTaxInt = (int) round((float) (string) $tax);
        $allocatedTax = 0;
        $totalItems = $selected->count();

        foreach ($selected as $index => $item) {
            $itemAmount = (float) $item->amount;
            if ($index === $totalItems - 1) {
                $itemVat = $totalTaxInt - $allocatedTax;
            } else {
                $itemVat = $subtotal->isPositive()
                    ? (int) round($itemAmount * $totalTaxInt / (float) (string) $subtotal)
                    : 0;
                $allocatedTax += $itemVat;
            }

            $goodService = $this->element($doc, 'GoodService', $listOfGoodService);
            $this->leaf($doc, $goodService, 'Opt', 'B');
            $this->leaf($doc, $goodService, 'Code', '060000');
            $this->leaf($doc, $goodService, 'Name', $item->description);
            $this->leaf($doc, $goodService, 'Unit', 'UM.0033');
            $this->leaf($doc, $goodService, 'Price', (string) (int) round($itemAmount));
            $this->leaf($doc, $goodService, 'Qty', (string) max(1, (int) round((float) $item->quantity)));
            $this->leaf($doc, $goodService, 'TotalDiscount', '0');
            $this->leaf($doc, $goodService, 'TaxBase', '0');
            $this->leaf($doc, $goodService, 'OtherTaxBase', '0');
            $this->leaf($doc, $goodService, 'VATRate', (string) (int) round((float) ($seller['vat_rate'] ?? 11)));
            $this->leaf($doc, $goodService, 'VAT', (string) max(0, $itemVat));
            $this->leaf($doc, $goodService, 'STLGRate', '0');
            $this->leaf($doc, $goodService, 'STLG', '0');
        }

        if ($log) {
            $this->master->log($actor, 'invoice.coretax.exported', 'Ekspor XML Coretax '.$invoice->number, ['module' => 'invoice', 'record_id' => $invoice->id, 'after' => ['dpp' => Money::format((string) $subtotal), 'ppn' => Money::format((string) $tax), 'currency' => $invoice->currency]]);
        }

        return $doc->saveXML();
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
