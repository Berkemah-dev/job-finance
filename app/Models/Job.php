<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Job extends Model
{
    use HasFactory;

    public function costs(): HasMany
    {
        return $this->hasMany(JobCost::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(JobStatusHistory::class);
    }

    /**
     * Alias untuk statusHistory() — riwayat status pengiriman.
     * Status shipment ditulis ke job_status_history (satu tabel gabungan).
     * Tabel job_shipment_statuses sudah dihapus karena dead code.
     */
    public function shipmentStatusHistory(): HasMany
    {
        return $this->hasMany(JobStatusHistory::class);
    }

    public function doConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'do_confirmed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(JobDocument::class);
    }

    public function closingSnapshot(): HasOne
    {
        return $this->hasOne(JobClosingSnapshot::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function bookingConfirmations(): HasMany
    {
        return $this->hasMany(BookingConfirmation::class);
    }

    public function bookingConfirmation(): HasOne
    {
        return $this->hasOne(BookingConfirmation::class)->latestOfMany();
    }

    public function shippingInstructions(): HasMany
    {
        return $this->hasMany(ShippingInstruction::class);
    }

    public function shippingInstruction(): HasOne
    {
        return $this->hasOne(ShippingInstruction::class)->latestOfMany();
    }

    public function awbs(): HasMany
    {
        return $this->hasMany(Awb::class);
    }

    public function billsOfLading(): HasMany
    {
        return $this->hasMany(BillOfLading::class);
    }

    public function dnps(): HasMany
    {
        return $this->hasMany(Dnp::class);
    }

    public function dnp(): HasOne
    {
        return $this->hasOne(Dnp::class)->latestOfMany();
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quotation_snapshot' => 'array', 'job_date' => 'date', 'expected_completion_date' => 'date', 'etd' => 'date', 'eta' => 'date', 'nopen_date' => 'date', 'peb_date' => 'date', 'commercial_invoice_date' => 'date', 'packing_list_date' => 'date', 'gross_weight' => 'decimal:2', 'volume' => 'decimal:2', 'opened_at' => 'datetime', 'cancelled_at' => 'datetime', 'closed_at' => 'datetime', 'shipment_status_at' => 'datetime', 'do_confirmed_at' => 'datetime'];
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function cs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cs_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function vendorTrucking(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_trucking_id');
    }

    public function vendorTruck(): BelongsTo
    {
        return $this->belongsTo(VendorTruck::class, 'vendor_truck_id');
    }

    public function deliveryAddressLocation(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function etaApproaching(): bool
    {
        if (! $this->eta) {
            return false;
        }

        return $this->eta->gte(today()) && $this->eta->lte(today()->addDays(3));
    }

    public function hasBlDocument(): bool
    {
        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'HBL') || str_contains($code, 'MBL') || str_contains($name, 'BL') || str_contains($name, 'BILL OF LADING');
        });
    }

    public function hasAwbDocument(): bool
    {
        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'HAWB') || str_contains($code, 'MAWB') || str_contains($name, 'AWB') || str_contains($name, 'AIR WAYBILL');
        });
    }

    public function hasInvoiceDocument(): bool
    {
        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'INV') || str_contains($name, 'INVOICE');
        });
    }

    public function hasPackingListDocument(): bool
    {
        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'PL') || str_contains($name, 'PACKINGLIST') || str_contains($name, 'PACKING LIST');
        });
    }

    public function hasImportPibReadyDocuments(): bool
    {
        $service = (string) ($this->service_type ?? '');
        $hasTransportDocument = $service === 'imp_air' ? $this->hasAwbDocument() : $this->hasBlDocument();

        return $hasTransportDocument && $this->hasInvoiceDocument() && $this->hasPackingListDocument();
    }
    public function hasNpeDocument(): bool
    {
        if ($this->shipment_status === 'npe' || ! empty($this->npe_number)) {
            return true;
        }

        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'NPE') || str_contains($name, 'NPE') || str_contains($code, 'PEBNPE');
        });
    }

    public function hasSuratJalanDocument(): bool
    {
        return $this->getSuratJalanDocument() !== null;
    }

    public function getSuratJalanDocument(): ?JobDocument
    {
        return $this->documents->first(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));
            $notes = strtoupper((string) ($doc->notes ?? ''));
            $orig = strtoupper((string) ($doc->original_name ?? ''));

            return str_contains($code, 'SJ') ||
                   str_contains($code, 'SURAT_JALAN') ||
                   str_contains($name, 'SURAT JALAN') ||
                   str_contains($name, 'SURAT_JALAN') ||
                   str_contains($notes, 'SURAT JALAN') ||
                   str_contains($orig, 'SURAT_JALAN') ||
                   str_contains($orig, 'SURAT JALAN');
        });
    }

    public function hasDoChecklist(): bool
    {
        if ($this->do_confirmed_at !== null) {
            return true;
        }

        if ($this->hasSuratJalanDocument()) {
            return true;
        }

        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));
            $notes = strtoupper((string) ($doc->notes ?? ''));

            return str_contains($code, 'DO') || str_contains($name, 'DO') || str_contains($name, 'DELIVERY ORDER') || str_contains($notes, 'DO');
        });
    }

    /**
     * Apakah PIB sudah diajukan (PIB submitted).
     * Ditandai dengan status shipment 'pib_submitted' ATAU ada dokumen PIB terupload.
     */
    public function hasPibDocument(): bool
    {
        if (in_array($this->shipment_status, ['pib_submitted', 'billing', 'spjm', 'behandle', 'sppb'], true)) {
            return true;
        }

        if ($this->hasImportPibReadyDocuments()) {
            return true;
        }

        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'PIB') || str_contains($name, 'PIB') ||
                   str_contains($name, 'PEMBERITAHUAN IMPOR');
        });
    }

    /**
     * Apakah tagihan Bea Cukai (Billing) sudah dilunasi / diproses.
     * Ditandai dengan status 'billing' ke atas ATAU dokumen BILLING terupload.
     */
    public function hasBillingDocument(): bool
    {
        if (in_array($this->shipment_status, ['billing', 'spjm', 'behandle', 'sppb'], true)) {
            return true;
        }

        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'BILLING') || str_contains($code, 'BILLING-BC') ||
                   str_contains($name, 'BILLING') || str_contains($name, 'TAGIHAN BC') ||
                   str_contains($name, 'BILLING BEA CUKAI');
        });
    }

    public function hasSpjmDocument(): bool
    {
        if (in_array($this->shipment_status, ['spjm', 'behandle'], true)) {
            return true;
        }

        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'SPJM') || str_contains($name, 'SPJM');
        });
    }

    /**
     * Apakah sedang dalam tahap pemeriksaan fisik dari jalur SPJM.
     */
    public function hasBehandleStatus(): bool
    {
        if ($this->shipment_status === 'behandle') {
            return true;
        }

        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'BEHANDLE') || str_contains($name, 'BEHANDLE') ||
                   str_contains($code, 'SLIM') || str_contains($name, 'SLIM');
        });
    }

    public function hasSppbDocument(): bool
    {
        if ($this->shipment_status === 'sppb') {
            return true;
        }

        return $this->documents->contains(function (JobDocument $doc) {
            $code = strtoupper((string) ($doc->documentType?->code ?? ''));
            $name = strtoupper((string) ($doc->documentType?->name ?? ''));

            return str_contains($code, 'SPPB') || str_contains($name, 'SPPB');
        });
    }

    public function getShipmentChecklistSummaryAttribute(): array
    {
        $service = (string) ($this->service_type ?? '');
        $isExportSea = in_array($service, ['exp_sea', 'sea'], true);
        $isExportAir = in_array($service, ['exp_air', 'air'], true);
        $isImportSea = $service === 'imp_sea';
        $isImportAir = $service === 'imp_air';

        if ($isExportSea) {
            $hasBl = $this->hasBlDocument();
            $hasNpe = $this->hasNpeDocument();

            return [
                'type' => 'export_sea',
                'title' => 'Export Sea Checklist',
                'is_all_completed' => $hasBl && $hasNpe,
                'status_summary' => ($hasBl && $hasNpe) ? 'BL Checklist & Customs Checklist Selesai' : ($hasBl ? 'BL Checklist Selesai' : ($hasNpe ? 'Customs Checklist Selesai' : 'Menunggu Dokumen')),
                'items' => [
                    [
                        'key' => 'bl',
                        'label' => 'BL Checklist',
                        'sublabel' => 'House BL / Master BL',
                        'completed' => $hasBl,
                        'badge_text' => $hasBl ? 'BL Checklist ✓' : 'Belum Upload BL',
                        'badge_color' => $hasBl ? '#166534' : '#b91c1c',
                        'badge_bg' => $hasBl ? '#dcfce7' : '#fee2e2',
                    ],
                    [
                        'key' => 'customs',
                        'label' => 'Customs Checklist',
                        'sublabel' => 'Nota Pelayanan Ekspor (NPE)',
                        'completed' => $hasNpe,
                        'badge_text' => $hasNpe ? 'Customs Checklist (NPE) ✓' : 'Menunggu NPE',
                        'badge_color' => $hasNpe ? '#3730a3' : '#b91c1c',
                        'badge_bg' => $hasNpe ? '#e0e7ff' : '#fee2e2',
                    ],
                ],
            ];
        }

        if ($isExportAir) {
            $hasAwb = $this->hasAwbDocument();
            $hasNpe = $this->hasNpeDocument();

            return [
                'type' => 'export_air',
                'title' => 'Export Air Checklist',
                'is_all_completed' => $hasAwb && $hasNpe,
                'status_summary' => ($hasAwb && $hasNpe) ? 'AWB Checklist & Customs Checklist Selesai' : ($hasAwb ? 'AWB Checklist Selesai' : ($hasNpe ? 'Customs Checklist Selesai' : 'Menunggu Dokumen')),
                'items' => [
                    [
                        'key' => 'awb',
                        'label' => 'AWB Checklist',
                        'sublabel' => 'House AWB / Master AWB',
                        'completed' => $hasAwb,
                        'badge_text' => $hasAwb ? 'AWB Checklist ✓' : 'Belum Upload AWB',
                        'badge_color' => $hasAwb ? '#166534' : '#b91c1c',
                        'badge_bg' => $hasAwb ? '#dcfce7' : '#fee2e2',
                    ],
                    [
                        'key' => 'customs',
                        'label' => 'Customs Checklist',
                        'sublabel' => 'Nota Pelayanan Ekspor (NPE)',
                        'completed' => $hasNpe,
                        'badge_text' => $hasNpe ? 'Customs Checklist (NPE) ✓' : 'Menunggu NPE',
                        'badge_color' => $hasNpe ? '#3730a3' : '#b91c1c',
                        'badge_bg' => $hasNpe ? '#e0e7ff' : '#fee2e2',
                    ],
                ],
            ];
        }

        if ($isImportSea) {
            $hasPib      = $this->hasPibDocument();
            $hasSpjm     = $this->hasSpjmDocument();
            $hasBehandle = $this->hasBehandleStatus();
            $hasSppb     = $this->hasSppbDocument();

            if ($hasSppb && $hasSpjm) {
                $statusSummary = 'PIB diajukan → SPJM → Behandle → SPPB final';
            } elseif ($hasSppb) {
                $statusSummary = 'PIB diajukan → SPPB final';
            } elseif ($hasBehandle) {
                $statusSummary = 'SPJM dalam pemeriksaan fisik — menunggu SPPB';
            } elseif ($hasSpjm) {
                $statusSummary = 'SPJM aktif — menunggu SLIM / pemeriksaan fisik';
            } elseif ($hasPib) {
                $statusSummary = 'PIB diajukan — menunggu SPPB atau SPJM';
            } else {
                $statusSummary = 'Menunggu BL, Invoice, dan Packing List';
            }

            return [
                'type'            => 'import_sea',
                'title'           => 'Import Sea Checklist',
                'is_all_completed' => $hasPib && $hasSppb,
                'status_summary'  => $statusSummary,
                'items'           => [
                    [
                        'key'          => 'pib',
                        'label'        => 'PIB Diajukan',
                        'sublabel'     => 'Otomatis hijau setelah BL, Invoice, Packing List lengkap',
                        'completed'    => $hasPib,
                        'badge_text'   => $hasPib ? 'PIB Diajukan ✓' : 'Menunggu BL/Invoice/PL',
                        'badge_color'  => $hasPib ? '#166534' : '#b91c1c',
                        'badge_bg'     => $hasPib ? '#dcfce7' : '#fee2e2',
                    ],
                    [
                        'key'          => 'spjm',
                        'label'        => 'SPJM',
                        'sublabel'     => 'Jika jalur merah, Operation upload SPJM',
                        'completed'    => $hasSpjm || ($hasSppb && ! $hasSpjm),
                        'active'       => $hasSpjm && ! $hasSppb,
                        'badge_text'   => $hasSpjm ? 'SPJM Diterima ✓' : ($hasSppb ? 'Tidak ada SPJM' : 'Menunggu Penjaluran'),
                        'badge_color'  => $hasSpjm ? '#166534' : ($hasSppb ? '#166534' : '#b91c1c'),
                        'badge_bg'     => $hasSpjm ? '#dcfce7' : ($hasSppb ? '#dcfce7' : '#fee2e2'),
                    ],
                    [
                        'key'          => 'behandle',
                        'label'        => 'Behandle / Pemeriksaan Fisik',
                        'sublabel'     => 'Aktif setelah Operation upload SLIM',
                        'completed'    => $hasBehandle || ($hasSppb && ! $hasSpjm),
                        'active'       => $hasBehandle && ! $hasSppb,
                        'badge_text'   => $hasBehandle ? 'Behandle Selesai ✓' : ($hasSppb && ! $hasSpjm ? 'Dilewati' : 'Menunggu SLIM'),
                        'badge_color'  => $hasBehandle ? '#166534' : ($hasSppb && ! $hasSpjm ? '#166534' : '#b91c1c'),
                        'badge_bg'     => $hasBehandle ? '#dcfce7' : ($hasSppb && ! $hasSpjm ? '#dcfce7' : '#fee2e2'),
                    ],
                    [
                        'key'          => 'sppb',
                        'label'        => 'SPPB Final',
                        'sublabel'     => 'Ujung alur customs',
                        'completed'    => $hasSppb,
                        'badge_text'   => $hasSppb ? 'SPPB TERBIT ✓' : 'Menunggu SPPB',
                        'badge_color'  => $hasSppb ? '#166534' : '#b91c1c',
                        'badge_bg'     => $hasSppb ? '#dcfce7' : '#fee2e2',
                    ],
                ],
            ];
        }

        if ($isImportAir) {
            $hasPib      = $this->hasPibDocument();
            $hasSpjm     = $this->hasSpjmDocument();
            $hasBehandle = $this->hasBehandleStatus();
            $hasSppb     = $this->hasSppbDocument();

            if ($hasSppb && $hasSpjm) {
                $statusSummary = 'PIB diajukan → SPJM → Behandle → SPPB final';
            } elseif ($hasSppb) {
                $statusSummary = 'PIB diajukan → SPPB final';
            } elseif ($hasBehandle) {
                $statusSummary = 'SPJM dalam pemeriksaan fisik — menunggu SPPB';
            } elseif ($hasSpjm) {
                $statusSummary = 'SPJM aktif — menunggu SLIM / pemeriksaan fisik';
            } elseif ($hasPib) {
                $statusSummary = 'PIB diajukan — menunggu SPPB atau SPJM';
            } else {
                $statusSummary = 'Menunggu AWB, Invoice, dan Packing List';
            }

            return [
                'type'            => 'import_air',
                'title'           => 'Import Air Checklist',
                'is_all_completed' => $hasPib && $hasSppb,
                'status_summary'  => $statusSummary,
                'items'           => [
                    [
                        'key'          => 'pib',
                        'label'        => 'PIB Diajukan',
                        'sublabel'     => 'Otomatis hijau setelah AWB, Invoice, Packing List lengkap',
                        'completed'    => $hasPib,
                        'badge_text'   => $hasPib ? 'PIB Diajukan ✓' : 'Menunggu AWB/Invoice/PL',
                        'badge_color'  => $hasPib ? '#166534' : '#b91c1c',
                        'badge_bg'     => $hasPib ? '#dcfce7' : '#fee2e2',
                    ],
                    [
                        'key'          => 'spjm',
                        'label'        => 'SPJM',
                        'sublabel'     => 'Jika jalur merah, Operation upload SPJM',
                        'completed'    => $hasSpjm || ($hasSppb && ! $hasSpjm),
                        'active'       => $hasSpjm && ! $hasSppb,
                        'badge_text'   => $hasSpjm ? 'SPJM Diterima ✓' : ($hasSppb ? 'Tidak ada SPJM' : 'Menunggu Penjaluran'),
                        'badge_color'  => $hasSpjm ? '#166534' : ($hasSppb ? '#166534' : '#b91c1c'),
                        'badge_bg'     => $hasSpjm ? '#dcfce7' : ($hasSppb ? '#dcfce7' : '#fee2e2'),
                    ],
                    [
                        'key'          => 'behandle',
                        'label'        => 'Behandle / Pemeriksaan Fisik',
                        'sublabel'     => 'Aktif setelah Operation upload SLIM',
                        'completed'    => $hasBehandle || ($hasSppb && ! $hasSpjm),
                        'active'       => $hasBehandle && ! $hasSppb,
                        'badge_text'   => $hasBehandle ? 'Behandle Selesai ✓' : ($hasSppb && ! $hasSpjm ? 'Dilewati' : 'Menunggu SLIM'),
                        'badge_color'  => $hasBehandle ? '#166534' : ($hasSppb && ! $hasSpjm ? '#166534' : '#b91c1c'),
                        'badge_bg'     => $hasBehandle ? '#dcfce7' : ($hasSppb && ! $hasSpjm ? '#dcfce7' : '#fee2e2'),
                    ],
                    [
                        'key'          => 'sppb',
                        'label'        => 'SPPB Final',
                        'sublabel'     => 'Ujung alur customs',
                        'completed'    => $hasSppb,
                        'badge_text'   => $hasSppb ? 'SPPB TERBIT ✓' : 'Menunggu SPPB',
                        'badge_color'  => $hasSppb ? '#166534' : '#b91c1c',
                        'badge_bg'     => $hasSppb ? '#dcfce7' : '#fee2e2',
                    ],
                ],
            ];
        }
        return [
            'type' => 'domestic',
            'title' => 'Domestic Flow',
            'is_all_completed' => $this->status === 'closed',
            'status_summary' => 'Domestic Shipment',
            'items' => [],
        ];
    }
}
