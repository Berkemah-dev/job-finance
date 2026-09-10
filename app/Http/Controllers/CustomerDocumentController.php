<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDocument;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CustomerDocumentController extends Controller
{
    public function download(Customer $customer, CustomerDocument $document)
    {
        Gate::forUser(auth()->user())->authorize('customers.view');
        abort_unless($document->customer_id === $customer->id, 404);
        if (! \Storage::disk($document->disk)->exists($document->path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return \Storage::disk($document->disk)->download($document->path, $document->original_name ?? Str::afterLast($document->path, '/'));
    }

    public function destroy(Customer $customer, CustomerDocument $document)
    {
        Gate::forUser(auth()->user())->authorize('customers.manage');
        abort_unless($document->customer_id === $customer->id, 404);
        if (\Storage::disk($document->disk)->exists($document->path)) {
            \Storage::disk($document->disk)->delete($document->path);
        }
        abort_unless($customer->npwp_file !== $document->path && $customer->nib_file !== $document->path, 422, 'Dokumen aktif customer tidak bisa dihapus dari sini. Ganti lewat form edit.');
        $document->delete();

        return redirect()->route('customers.show', $customer)->with('success', 'Dokumen dihapus.');
    }
}
