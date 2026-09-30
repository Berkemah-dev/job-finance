<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Job;
use App\Models\JobDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
            'customs_upload' => ['nullable', 'boolean'],
            'customs_document_kind' => ['nullable', Rule::in(['pib', 'billing', 'spjm', 'sppb', 'behandle', 'npe'])],
            'booking_reference' => ['nullable', 'string', 'max:60'],
            'customs_submission_number' => ['nullable', 'string', 'max:100'],
            'nopen' => ['nullable', 'string', 'max:60'],
            'nopen_date' => ['nullable', 'date'],
            'npe_number' => ['nullable', 'string', 'max:60'],
            'peb_number' => ['nullable', 'string', 'max:60'],
            'peb_date' => ['nullable', 'date'],
        ]);

        Gate::authorize('update', $job);
        $docType = DocumentType::findOrFail($request->document_type_id);
        $docText = strtoupper($docType->code.' '.$docType->name);
        $isCustomsDocument = $this->detectCustomsKind($docText) !== '';
        $isCustomsUpload = $request->boolean('customs_upload') || $request->filled('customs_document_kind');
        if ($isCustomsDocument && ! $isCustomsUpload) {
            return back()->withErrors(['document_type_id' => 'Dokumen kepabeanan hanya dapat diunggah melalui tab PIB/PEB.'])->withInput();
        }
        if (! $isCustomsDocument && $request->boolean('customs_upload') && ! $request->filled('customs_document_kind')) {
            return back()->withErrors(['document_type_id' => 'Pilih dokumen kepabeanan pada tab PIB/PEB.'])->withInput();
        }
        $kind = (string) $request->input('customs_document_kind', '');
        $kind = $kind ?: $this->detectCustomsKind($docText);

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

        $job->load(['documents.documentType']);
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
            $isBehandle = $kind === 'behandle' || str_contains($docCode, 'BEHANDLE') || str_contains($docName, 'BEHANDLE') || str_contains($docName, 'SLIM');
            $isSppb = $kind === 'sppb' || str_contains($docCode, 'SPPB') || str_contains($docName, 'SPPB');

            if ($isSppb) {
                $notification = 'Dokumen SPPB berhasil diupload. 🟢 SPPB Terbit — Proses Kepabeanan Selesai (Jalur Hijau).';
            } elseif ($isBehandle) {
                $notification = 'Dokumen Pemeriksaan Fisik/SLIM berhasil diupload. Notifikasi: Pemeriksaan Fisik aktif.';
            } elseif ($isSpjm) {
                $notification = 'Dokumen SPJM berhasil diupload. 🔴 Jalur Merah — Barang perlu pemeriksaan fisik (Behandle).';
            } elseif ($job->hasImportPibReadyDocuments() && $job->shipment_status === 'pib_submitted') {
                $notification = 'Dokumen berhasil diupload. Notifikasi: Dokumen lengkap (BL + Invoice + PL) — PIB Diajukan.';
            } elseif ($isDo) {
                $notification = 'Dokumen DO berhasil diupload. Notifikasi: DO Checklist terverifikasi.';
            }
        } elseif ($isImportAir) {
            $isSpjm = $kind === 'spjm' || str_contains($docCode, 'SPJM') || str_contains($docName, 'SPJM');
            $isBehandle = $kind === 'behandle' || str_contains($docCode, 'BEHANDLE') || str_contains($docName, 'BEHANDLE') || str_contains($docName, 'SLIM');
            $isSppb = $kind === 'sppb' || str_contains($docCode, 'SPPB') || str_contains($docName, 'SPPB');

            if ($isSppb) {
                $notification = 'Dokumen SPPB berhasil diupload. 🟢 SPPB Terbit — Proses Kepabeanan Selesai (Jalur Hijau).';
            } elseif ($isBehandle) {
                $notification = 'Dokumen Pemeriksaan Fisik/SLIM berhasil diupload. Notifikasi: Pemeriksaan Fisik aktif.';
            } elseif ($isSpjm) {
                $notification = 'Dokumen SPJM berhasil diupload. 🔴 Jalur Merah — Barang perlu pemeriksaan fisik (Behandle).';
            } elseif ($job->hasImportPibReadyDocuments() && $job->shipment_status === 'pib_submitted') {
                $notification = 'Dokumen berhasil diupload. Notifikasi: Dokumen lengkap (AWB + Invoice + PL) — PIB Diajukan.';
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

        foreach (['booking_reference', 'nopen', 'nopen_date', 'npe_number', 'peb_number', 'peb_date'] as $field) {
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
        $isImport = in_array($job->service_type, ['imp_sea', 'imp_air'], true);
        $isExport = in_array($job->service_type, ['exp_sea', 'exp_air'], true);

        if (! $kind && $isImport && $job->hasImportPibReadyDocuments() && ! in_array($job->shipment_status, ['billing', 'spjm', 'behandle', 'sppb'], true)) {
            $kind = 'pib';
        }

        if ($kind) {
            $importKindMap = [
                'pib'      => 'pib_submitted',
                'billing'  => 'billing',
                'spjm'     => 'spjm',
                'behandle' => 'behandle',
                'sppb'     => 'sppb',
            ];

            if ($isImport && isset($importKindMap[$kind])) {
                $currentRank = array_search($job->shipment_status, array_values($importKindMap), true);
                $nextRank = array_search($importKindMap[$kind], array_values($importKindMap), true);

                if ($currentRank === false || $nextRank === false || $nextRank >= $currentRank) {
                    $updates['shipment_status'] = $importKindMap[$kind];
                    $updates['shipment_status_by'] = $request->user()?->id;
                    $updates['shipment_status_at'] = now();
                }
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
            str_contains($text, 'BEHANDLE') || str_contains($text, 'SLIM') || str_contains($text, 'PEMERIKSAAN FISIK') => 'behandle',
            str_contains($text, 'SPJM') => 'spjm',
            str_contains($text, 'BILLING') => 'billing',
            str_contains($text, 'PIB') => 'pib',
            str_contains($text, 'NPE') => 'npe',
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

    public function preview(Job $job, JobDocument $document)
    {
        abort_unless($document->job_id === $job->id, 404);
        abort_unless(Storage::disk('private')->exists($document->file_path), 404);

        return response(Storage::disk('private')->get($document->file_path), 200, [
            'Content-Type' => $document->mime_type ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
        ]);
    }

    public function destroy(Job $job, JobDocument $document)
    {
        abort_unless($document->job_id === $job->id, 404);

        Storage::disk('private')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public function mergePdf(Request $request, Job $job)
    {
        Gate::authorize('view', $job);

        // Support both GET (direct link / browser refresh) and POST (checkbox selection form)
        $docIds = $request->input('document_ids');
        if (empty($docIds)) {
            // Default to all documents if refreshed or accessed directly
            $docIds = $job->documents()->pluck('id')->toArray();
        }

        if (empty($docIds)) {
            return redirect()->to(route('jobs.show', $job).'#tab-documents')->with('error', 'Job ini belum memiliki dokumen lampiran untuk digabungkan.');
        }

        $documents = $job->documents()->whereIn('id', (array) $docIds)->get();

        if ($documents->isEmpty()) {
            return redirect()->to(route('jobs.show', $job).'#tab-documents')->with('error', 'Pilih minimal satu dokumen lampiran untuk digabungkan.');
        }

        $filePaths = [];
        foreach ($documents as $doc) {
            $filePath = Storage::disk('private')->path($doc->file_path);
            if (file_exists($filePath)) {
                $filePaths[] = $filePath;
            }
        }

        if (empty($filePaths)) {
            return redirect()->to(route('jobs.show', $job).'#tab-documents')->with('error', 'Dokumen yang dipilih tidak memiliki berkas PDF yang valid untuk digabungkan.');
        }

        $mergedContent = null;
        $tempMergedPath = tempnam(sys_get_temp_dir(), 'merged_pdf_') . '.pdf';

        // 1. Prioritize Node.js pdf-lib (standard JavaScript package in package.json)
        $nodeScript = base_path('scripts/merge-pdf.js');
        if (file_exists($nodeScript)) {
            try {
                $nodeBinary = $this->resolveNodeBinary();
                $process = new \Symfony\Component\Process\Process(
                    array_merge([$nodeBinary, $nodeScript, $tempMergedPath], $filePaths),
                    base_path(),
                    [
                        'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
                        'windir'     => getenv('windir') ?: 'C:\\Windows',
                        'TEMP'       => sys_get_temp_dir(),
                        'TMP'        => sys_get_temp_dir(),
                        'PATH'       => getenv('PATH') ?: 'C:\\Program Files\\nodejs;C:\\Windows\\system32',
                    ]
                );
                $process->setTimeout(60);
                $process->run();

                if ($process->isSuccessful() && file_exists($tempMergedPath) && filesize($tempMergedPath) > 0) {
                    $mergedContent = file_get_contents($tempMergedPath);
                    @unlink($tempMergedPath);
                } else {
                    \Illuminate\Support\Facades\Log::warning('Node merge failed: exit=' . $process->getExitCode() . ' | err=' . $process->getErrorOutput());
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Node pdf-lib merge error: ' . $e->getMessage());
            }
        }

        // 2. Fallback to FPDI for environments without Node
        if (! $mergedContent && class_exists(\setasign\Fpdi\Fpdi::class)) {
            try {
                $pdf = new \setasign\Fpdi\Fpdi();
                $addedPages = 0;

                foreach ($filePaths as $path) {
                    try {
                        $pageCount = $pdf->setSourceFile($path);
                        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                            $templateId = $pdf->importPage($pageNo);
                            $size = $pdf->getTemplateSize($templateId);
                            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $pdf->useTemplate($templateId);
                            $addedPages++;
                        }
                    } catch (\Throwable $subE) {
                        \Illuminate\Support\Facades\Log::warning('FPDI sub-merge warning: ' . $subE->getMessage());
                    }
                }

                if ($addedPages > 0) {
                    $mergedContent = $pdf->Output('S');
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('FPDI merge fallback failed: ' . $e->getMessage());
            }
        }

        if (! $mergedContent) {
            return redirect()->to(route('jobs.show', $job).'#tab-documents')->with('error', 'Dokumen yang dipilih tidak memiliki berkas PDF yang valid untuk digabungkan.');
        }

        $filename = 'DOKUMEN_GABUNGAN_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $job->number) . '.pdf';
        $disposition = $request->input('mode') === 'download' ? 'attachment' : 'inline';

        return response($mergedContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }

    private function resolveNodeBinary(): string
    {
        $candidates = [
            'C:\\Program Files\\nodejs\\node.exe',
            'C:\\Program Files (x86)\\nodejs\\node.exe',
            'C:\\laragon\\bin\\nodejs\\node-v22\\node.exe',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return 'node';
    }
}
