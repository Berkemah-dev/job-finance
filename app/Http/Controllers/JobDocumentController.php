<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Job;
use App\Models\JobDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class JobDocumentController extends Controller
{
    public function store(Request $request, Job $job)
    {
        $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
            'file'             => 'required|file|max:3072|mimes:pdf',
            'notes'            => 'nullable|string|max:500',
            'customs_document_kind' => ['nullable', Rule::in(['pib', 'billing', 'spjm', 'sppb', 'behandle', 'npe'])],
            'booking_reference' => ['nullable', 'string', 'max:60'],
            'customs_submission_number' => ['nullable', 'string', 'max:100'],
            'nopen' => ['nullable', 'string', 'max:60'],
            'npe_number' => ['nullable', 'string', 'max:60'],
            'peb_number' => ['nullable', 'string', 'max:60'],
            'peb_date' => ['nullable', 'date'],
        ]);

        $docType = DocumentType::findOrFail($request->document_type_id);
        $docText = strtoupper($docType->code.' '.$docType->name);
        $kind = (string) $request->input('customs_document_kind', '');
        $kind = $kind ?: $this->detectCustomsKind($docText);
        if (in_array($kind, ['spjm', 'behandle', 'sppb'], true) && ! $request->user()->hasRole(['operational', 'super-admin', 'admin'])) {
            abort(403, 'Dokumen SPJM, Pemeriksaan Fisik/SLIM, dan SPPB hanya dapat diupload oleh Operational.');
        }

        $file      = $request->file('file');
        $path      = $file->store('job-documents/'.$job->id, 'private');

        $job->documents()->create([
            'document_type_id' => $request->document_type_id,
            'original_name'    => $file->getClientOriginalName(),
            'file_path'        => $path,
            'mime_type'        => $file->getMimeType(),
            'file_size'        => $file->getSize(),
            'notes'            => $request->notes,
            'uploaded_by'      => $request->user()->id,
        ]);

        $this->syncCustomsDataFromUpload($request, $job);

        $job->load(['documents.documentType']);
        $docCode = strtoupper((string) ($docType?->code ?? ''));
        $docName = strtoupper((string) ($docType?->name ?? ''));

        $service = (string) ($job->service_type ?? '');
        $isExportSea = in_array($service, ['exp_sea', 'sea'], true);
        $isExportAir = in_array($service, ['exp_air', 'air'], true);
        $isImportSea = $service === 'imp_sea';
        $isImportAir = $service === 'imp_air';

        $notification = 'Dokumen berhasil diupload.';

        if ($isExportSea) {
            $isBl = str_contains($docCode, 'HBL') || str_contains($docCode, 'MBL') || str_contains($docName, 'BL') || str_contains($docName, 'BILL OF LADING');
            $isNpe = $kind === 'npe' || str_contains($docCode, 'NPE') || str_contains($docName, 'NPE') || $request->filled('npe_number');

            if ($job->hasBlDocument() && $job->hasNpeDocument()) {
                $notification = 'Dokumen berhasil diupload. Notifikasi: BL Checklist & Customs Checklist Lengkap!';
            } elseif ($isBl) {
                $notification = 'Dokumen BL berhasil diupload. Notifikasi: BL Checklist terverifikasi.';
            } elseif ($isNpe) {
                $notification = 'Dokumen NPE berhasil diupload. Notifikasi: Customs Checklist terverifikasi (NPE Terbit).';
            }
        } elseif ($isExportAir) {
            $isAwb = str_contains($docCode, 'HAWB') || str_contains($docCode, 'MAWB') || str_contains($docName, 'AWB') || str_contains($docName, 'AIR WAYBILL');
            $isNpe = $kind === 'npe' || str_contains($docCode, 'NPE') || str_contains($docName, 'NPE') || str_contains($docCode, 'PEBNPE') || $request->filled('npe_number');

            if ($job->hasAwbDocument() && $job->hasNpeDocument()) {
                $notification = 'Dokumen berhasil diupload. Notifikasi: AWB Checklist & Customs Checklist Lengkap!';
            } elseif ($isAwb) {
                $notification = 'Dokumen AWB berhasil diupload. Notifikasi: AWB Checklist terverifikasi.';
            } elseif ($isNpe) {
                $notification = 'Dokumen NPE berhasil diupload. Notifikasi: Customs Checklist terverifikasi (NPE Terbit).';
            }
        } elseif ($isImportSea) {
            $isDo = str_contains($docCode, 'DO') || str_contains($docName, 'DO') || str_contains($docName, 'DELIVERY ORDER');
            $isSpjm = $kind === 'spjm' || str_contains($docCode, 'SPJM') || str_contains($docName, 'SPJM');
            $isSppb = $kind === 'sppb' || str_contains($docCode, 'SPPB') || str_contains($docName, 'SPPB');

            if ($job->hasDoChecklist() && $job->hasSpjmDocument() && $job->hasSppbDocument()) {
                $notification = 'Dokumen berhasil diupload. Notifikasi: DO Checklist + SPJM dalam inspection + SPPB final.';
            } elseif ($job->hasDoChecklist() && $job->hasSpjmDocument()) {
                $notification = 'Dokumen berhasil diupload. Notifikasi: DO Checklist + SPJM dalam inspection.';
            } elseif ($isSppb) {
                $notification = 'Dokumen SPPB berhasil diupload. Notifikasi: SPPB final.';
            } elseif ($isSpjm) {
                $notification = 'Dokumen SPJM berhasil diupload. Notifikasi: SPJM dalam inspection.';
            } elseif ($isDo) {
                $notification = 'Dokumen DO berhasil diupload. Notifikasi: DO Checklist terverifikasi.';
            }
        } elseif ($isImportAir) {
            $isSpjm = $kind === 'spjm' || str_contains($docCode, 'SPJM') || str_contains($docName, 'SPJM');
            $isSppb = $kind === 'sppb' || str_contains($docCode, 'SPPB') || str_contains($docName, 'SPPB');

            if ($job->hasSpjmDocument() && $job->hasSppbDocument()) {
                $notification = 'Dokumen berhasil diupload. Notifikasi: SPJM dalam inspection + SPPB final.';
            } elseif ($isSppb) {
                $notification = 'Dokumen SPPB berhasil diupload. Notifikasi: SPPB final.';
            } elseif ($isSpjm) {
                $notification = 'Dokumen SPJM berhasil diupload. Notifikasi: SPJM dalam inspection.';
            }
        }

        return back()->with('success', $notification);
    }

    private function syncCustomsDataFromUpload(Request $request, Job $job): void
    {
        $updates = [];

        if ($request->filled('customs_submission_number')) {
            $aju = $this->lastSixDigits($request->string('customs_submission_number')->toString());
            if ($aju !== null && Schema::hasColumn('jobs', 'booking_reference')) {
                $updates['booking_reference'] = $aju;
            }
        }

        foreach (['booking_reference', 'nopen', 'npe_number', 'peb_number', 'peb_date'] as $field) {
            if ($request->filled($field)) {
                if (! Schema::hasColumn('jobs', $field)) {
                    continue;
                }

                $updates[$field] = $request->string($field)->trim()->toString();
            }
        }

        $kind = (string) $request->input('customs_document_kind', '');
        if (! $kind) {
            $doc = $job->documents()->with('documentType')->latest('id')->first();
            $kind = $doc ? $this->detectCustomsKind(strtoupper($doc->documentType?->code.' '.$doc->documentType?->name)) : '';
        }
        if ($kind) {
            $isImport = in_array($job->service_type, ['imp_sea', 'imp_air'], true);
            $isExport = in_array($job->service_type, ['exp_sea', 'exp_air'], true);

            // Peta kind -> shipment_status sesuai alur Import: PIB -> Billing -> Penjaluran -> Pemeriksaan Fisik -> SPPB
            $importKindMap = [
                'pib'      => 'pib_submitted',
                'billing'  => 'billing',
                'spjm'     => 'spjm',
                'behandle' => 'behandle',
                'sppb'     => 'sppb',
            ];

            if ($isImport && isset($importKindMap[$kind])) {
                $updates['shipment_status'] = $importKindMap[$kind];
                $updates['shipment_status_by'] = $request->user()?->id;
                $updates['shipment_status_at'] = now();
            } elseif ($isExport && $kind === 'npe') {
                $updates['shipment_status'] = 'npe';
                $updates['shipment_status_by'] = $request->user()?->id;
                $updates['shipment_status_at'] = now();
            }
        }

        if ($updates === []) {
            return;
        }

        $from = $job->shipment_status;
        $job->fill($updates);
        $job->save();

        if (array_key_exists('shipment_status', $updates) && $from !== $job->shipment_status) {
            $job->shipmentStatusHistory()->create([
                'from_status' => $from,
                'to_status' => $job->shipment_status,
                'note' => 'Otomatis dari upload dokumen kepabeanan.',
                'user_id' => $request->user()?->id,
                'created_at' => now(),
            ]);
        }
    }

    private function detectCustomsKind(string $text): string
    {
        return match (true) {
            str_contains($text, 'SPPB') => 'sppb',
            str_contains($text, 'BEHANDLE') || str_contains($text, 'SLIM') => 'behandle',
            str_contains($text, 'SPJM') => 'spjm',
            str_contains($text, 'BILLING') => 'billing',
            str_contains($text, 'PIB') => 'pib',
            default => '',
        };
    }

    private function lastSixDigits(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (! is_string($digits) || $digits === '') {
            return null;
        }

        return substr($digits, -6);
    }

    public function download(Job $job, JobDocument $document)
    {
        abort_unless($document->job_id === $job->id, 404);

        return Storage::disk('private')->download($document->file_path, $document->original_name);
    }

    public function destroy(Job $job, JobDocument $document)
    {
        abort_unless($document->job_id === $job->id, 404);

        Storage::disk('private')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
