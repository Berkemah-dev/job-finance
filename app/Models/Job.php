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

    public function shippingInstructions(): HasMany
    {
        return $this->hasMany(ShippingInstruction::class);
    }

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quotation_snapshot' => 'array', 'job_date' => 'date', 'expected_completion_date' => 'date', 'etd' => 'date', 'eta' => 'date', 'peb_date' => 'date', 'gross_weight' => 'decimal:2', 'volume' => 'decimal:2', 'opened_at' => 'datetime', 'cancelled_at' => 'datetime', 'closed_at' => 'datetime', 'shipment_status_at' => 'datetime', 'do_confirmed_at' => 'datetime'];
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function cs(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cs_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
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

    public function hasDoChecklist(): bool
    {
        if ($this->do_confirmed_at !== null) {
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
                        'badge_color' => $hasBl ? '#166534' : '#64748b',
                        'badge_bg' => $hasBl ? '#dcfce7' : '#f1f5f9',
                    ],
                    [
                        'key' => 'customs',
                        'label' => 'Customs Checklist',
                        'sublabel' => 'Nota Pelayanan Ekspor (NPE)',
                        'completed' => $hasNpe,
                        'badge_text' => $hasNpe ? 'Customs Checklist (NPE) ✓' : 'Menunggu NPE',
                        'badge_color' => $hasNpe ? '#3730a3' : '#64748b',
                        'badge_bg' => $hasNpe ? '#e0e7ff' : '#f1f5f9',
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
                        'badge_color' => $hasAwb ? '#166534' : '#64748b',
                        'badge_bg' => $hasAwb ? '#dcfce7' : '#f1f5f9',
                    ],
                    [
                        'key' => 'customs',
                        'label' => 'Customs Checklist',
                        'sublabel' => 'Nota Pelayanan Ekspor (NPE)',
                        'completed' => $hasNpe,
                        'badge_text' => $hasNpe ? 'Customs Checklist (NPE) ✓' : 'Menunggu NPE',
                        'badge_color' => $hasNpe ? '#3730a3' : '#64748b',
                        'badge_bg' => $hasNpe ? '#e0e7ff' : '#f1f5f9',
                    ],
                ],
            ];
        }

        if ($isImportSea) {
            $hasDo       = $this->hasDoChecklist();
            $hasSpjm     = $this->hasSpjmDocument();
            $hasSppb     = $this->hasSppbDocument();

            if ($hasSppb) {
                $statusSummary = 'DO Checklist + SPJM Inspection + SPPB Final Selesai';
            } elseif ($hasSpjm) {
                $statusSummary = 'SPJM dalam inspection — menunggu SPPB';
            } elseif ($hasDo) {
                $statusSummary = 'DO Checklist Selesai — menunggu SPJM/SPPB';
            } else {
                $statusSummary = 'Menunggu Dokumen Import Sea';
            }

            return [
                'type'            => 'import_sea',
                'title'           => 'Import Sea Checklist',
                'is_all_completed' => $hasDo && $hasSppb,
                'status_summary'  => $statusSummary,
                'items'           => [
                    [
                        'key'          => 'do',
                        'label'        => 'DO Checklist',
                        'sublabel'     => 'Delivery Order untuk proses pengeluaran barang',
                        'completed'    => $hasDo,
                        'badge_text'   => $hasDo ? 'DO Checklist ✓' : 'Menunggu DO',
                        'badge_color'  => $hasDo ? '#166534' : '#64748b',
                        'badge_bg'     => $hasDo ? '#dcfce7' : '#f1f5f9',
                    ],
                    [
                        'key'          => 'spjm',
                        'label'        => 'SPJM Inspection',
                        'sublabel'     => 'SPJM berarti barang dalam proses inspection',
                        'completed'    => $hasSpjm || $hasSppb,
                        'active'       => $hasSpjm && ! $hasSppb,
                        'badge_text'   => $hasSppb ? 'Inspection Selesai ✓' : ($hasSpjm ? 'SPJM dalam inspection' : 'Menunggu SPJM'),
                        'badge_color'  => $hasSppb ? '#166534' : ($hasSpjm ? '#991b1b' : '#64748b'),
                        'badge_bg'     => $hasSppb ? '#dcfce7' : ($hasSpjm ? '#fee2e2' : '#f1f5f9'),
                    ],
                    [
                        'key'          => 'sppb',
                        'label'        => 'SPPB (Final)',
                        'sublabel'     => 'Surat Persetujuan Pengeluaran Barang — final',
                        'completed'    => $hasSppb,
                        'badge_text'   => $hasSppb ? 'SPPB Terbit — Selesai ✓' : 'Menunggu SPPB',
                        'badge_color'  => $hasSppb ? '#166534' : '#64748b',
                        'badge_bg'     => $hasSppb ? '#dcfce7' : '#f1f5f9',
                    ],
                ],
            ];
        }

        if ($isImportAir) {
            $hasSpjm     = $this->hasSpjmDocument();
            $hasSppb     = $this->hasSppbDocument();

            if ($hasSppb) {
                $statusSummary = 'SPJM Inspection + SPPB Final Selesai';
            } elseif ($hasSpjm) {
                $statusSummary = 'SPJM dalam inspection — menunggu SPPB';
            } else {
                $statusSummary = 'Menunggu Dokumen Import Air';
            }

            return [
                'type'            => 'import_air',
                'title'           => 'Import Air Checklist',
                'is_all_completed' => $hasSppb,
                'status_summary'  => $statusSummary,
                'items'           => [
                    [
                        'key'          => 'spjm',
                        'label'        => 'SPJM Inspection',
                        'sublabel'     => 'SPJM berarti barang dalam proses inspection',
                        'completed'    => $hasSpjm || $hasSppb,
                        'active'       => $hasSpjm && ! $hasSppb,
                        'badge_text'   => $hasSppb ? 'Inspection Selesai ✓' : ($hasSpjm ? 'SPJM dalam inspection' : 'Menunggu SPJM'),
                        'badge_color'  => $hasSppb ? '#166534' : ($hasSpjm ? '#991b1b' : '#64748b'),
                        'badge_bg'     => $hasSppb ? '#dcfce7' : ($hasSpjm ? '#fee2e2' : '#f1f5f9'),
                    ],
                    [
                        'key'          => 'sppb',
                        'label'        => 'SPPB (Final)',
                        'sublabel'     => 'Surat Persetujuan Pengeluaran Barang — final',
                        'completed'    => $hasSppb,
                        'badge_text'   => $hasSppb ? 'SPPB Terbit — Selesai ✓' : 'Menunggu SPPB',
                        'badge_color'  => $hasSppb ? '#166534' : '#64748b',
                        'badge_bg'     => $hasSppb ? '#dcfce7' : '#f1f5f9',
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
