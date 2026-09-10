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

    public function generate(Invoice $invoice, User $actor): string
    {
        if (Money::decimal($invoice->tax)->isZero()) {
            throw ValidationException::withMessages(['invoice' => 'Invoice tidak memiliki PPN sehingga tidak dapat diekspor ke Coretax.']);
        }
        $subtotal = Money::decimal($invoice->subtotal);
        if ($subtotal->isZero()) {
            throw ValidationException::withMessages(['invoice' => 'DPP invoice nol sehingga tidak dapat diekspor ke Coretax.']);
        }
        $rate = Money::decimal($invoice->tax)->multipliedBy('100')->dividedBy($subtotal, 2, RoundingMode::HalfUp);
        $seller = config('accounting.coretax');
        $buyer = $invoice->customer_snapshot;

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $root = $this->element($doc, 'CoretaxImport', null);
        $root->setAttribute('version', '1.0');
        $doc->appendChild($root);

        $info = $this->element($doc, 'DocumentInfo', $root);
        $this->leaf($doc, $info, 'DocumentType', 'FakturKeluaran');
        $this->leaf($doc, $info, 'GeneratedBy', 'JobFinance');
        $this->leaf($doc, $info, 'GeneratedDate', now()->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP'));

        $sellerNode = $this->element($doc, 'Seller', $root);
        $this->leaf($doc, $sellerNode, 'NPWP', (string) $seller['seller_npwp']);
        $this->leaf($doc, $sellerNode, 'Name', (string) $seller['seller_name']);
        $this->leaf($doc, $sellerNode, 'Address', (string) $seller['seller_address']);
        $this->leaf($doc, $sellerNode, 'City', (string) $seller['seller_city']);
        $this->leaf($doc, $sellerNode, 'PostalCode', (string) $seller['seller_postal_code']);

        $buyerNode = $this->element($doc, 'Buyer', $root);
        $this->leaf($doc, $buyerNode, 'NPWP', (string) ($buyer['tax_number'] ?? ''));
        $this->leaf($doc, $buyerNode, 'Name', (string) ($buyer['name'] ?? ''));
        $this->leaf($doc, $buyerNode, 'Address', (string) ($buyer['address'] ?? ''));

        $invoiceNode = $this->element($doc, 'Invoice', $root);
        $this->leaf($doc, $invoiceNode, 'NoFaktur', $invoice->number);
        $this->leaf($doc, $invoiceNode, 'TanggalFaktur', $invoice->invoice_date->format('Y-m-d'));
        $this->leaf($doc, $invoiceNode, 'TanggalJatuhTempo', $invoice->due_date->format('Y-m-d'));
        $this->leaf($doc, $invoiceNode, 'JenisTransaksi', '01');
        $this->leaf($doc, $invoiceNode, 'StatusFaktur', 'Normal');

        $items = $this->element($doc, 'Items', $root);
        foreach ($invoice->items as $item) {
            $node = $this->element($doc, 'Item', $items);
            $this->leaf($doc, $node, 'Description', $item->description);
            $this->leaf($doc, $node, 'Quantity', Money::format($item->quantity));
            $this->leaf($doc, $node, 'Uom', $item->unit);
            $this->leaf($doc, $node, 'UnitPrice', Money::format($item->unit_price));
            $this->leaf($doc, $node, 'GrossAmount', Money::format($item->amount));
        }

        $summary = $this->element($doc, 'Summary', $root);
        $this->leaf($doc, $summary, 'SubTotal', Money::format($invoice->subtotal));
        $this->leaf($doc, $summary, 'TotalDiscount', '0.00');
        $this->leaf($doc, $summary, 'Dpp', Money::format($invoice->subtotal));
        $this->leaf($doc, $summary, 'Ppn', Money::format($invoice->tax));
        $this->leaf($doc, $summary, 'PpnRate', $rate->toScale(2, RoundingMode::HalfUp));
        $this->leaf($doc, $summary, 'Currency', $invoice->currency);
        $this->leaf($doc, $summary, 'ExchangeRate', $invoice->exchange_rate);
        $this->leaf($doc, $summary, 'GrandTotal', Money::format($invoice->total));

        $this->master->log($actor, 'invoice.coretax.exported', 'Ekspor XML Coretax '.$invoice->number, ['module' => 'invoice', 'record_id' => $invoice->id, 'after' => ['dpp' => Money::format($invoice->subtotal), 'ppn' => Money::format($invoice->tax), 'currency' => $invoice->currency]]);

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
        $parent->appendChild($doc->createElement($name, $value));
    }
}
