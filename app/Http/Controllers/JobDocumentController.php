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
            'file'             => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
            'notes'            => 'nullable|string|max:500',
            'customs_document_kind' => ['nullable', Rule::in(['spjm', 'sppb', 'npe'])],
            'booking_reference' => ['nullable', 'string', 'max:60'],
            'nopen' => ['nullable', 'string', 'max:60'],
            'npe_number' => ['nullable', 'string', 'max:60'],
        ]);

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

        return back()->with('success', 'Dokumen berhasil diupload.');
    }

    private function syncCustomsDataFromUpload(Request $request, Job $job): void
    {
        $updates = [];

        foreach (['booking_reference', 'nopen', 'npe_number'] as $field) {
            if ($request->filled($field)) {
                if (! Schema::hasColumn('jobs', $field)) {
                    continue;
                }

                $updates[$field] = $request->string($field)->trim()->toString();
            }
        }

        if ($request->filled('customs_document_kind')) {
            $kind = $request->string('customs_document_kind')->toString();
            $isImport = in_array($job->service_type, ['imp_sea', 'imp_air'], true);
            $isExport = in_array($job->service_type, ['exp_sea', 'exp_air'], true);

            if (($isImport && in_array($kind, ['spjm', 'sppb'], true)) || ($isExport && $kind === 'npe')) {
                $updates['shipment_status'] = $kind;
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
