<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Customer;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\MasterDataService;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $status = $request->boolean('archived') ? 'inactive' : (string) $request->input('status', 'active');
        $onlyTrashed = $status === 'inactive';
        $customers = Customer::query()->when($onlyTrashed, fn ($q) => $q->onlyTrashed())
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')->orWhere('tax_number', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')))
            ->orderBy('name')->paginate(10)->withQueryString();

        return view('customers.index', compact('customers', 'search', 'status'));
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(CustomerRequest $request, MasterDataService $service, DocumentNumberService $numbers, Filesystem $filesystem)
    {
        $customer = DB::transaction(function () use ($request, $service, $numbers) {
            $validated = $request->validated();
            $codeFormat = config('operations.customer_code');
            $validated['code'] = $numbers->nextYear('customer', $codeFormat['prefix'] ?? null, $codeFormat['delimiter'] ?? '-', (int) ($codeFormat['pad'] ?? 5));
            $customer = $service->save(new Customer, Arr::except($validated, ['contacts']), $request->user());
            $this->syncContacts($customer, $validated['contacts'] ?? [], $request->user(), $service);

            return $customer;
        }, 3);
        $this->handleUploads($customer, $request, $filesystem, $request->user());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer '.$customer->code.' berhasil ditambahkan.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['contacts', 'documents.uploader']);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $customer->load(['contacts', 'documents']);

        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer, MasterDataService $service, Filesystem $filesystem)
    {
        $validated = $request->validated();
        $service->save($customer, Arr::except($validated, ['contacts']), $request->user());
        $this->syncContacts($customer, $validated['contacts'] ?? [], $request->user(), $service);
        $this->handleUploads($customer, $request, $filesystem, $request->user());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(VersionRequest $request, Customer $customer, MasterDataService $service)
    {
        $service->archive($customer, $request->validated(), $request->user());

        return redirect()->route('customers.index')->with('success', 'Customer diarsipkan. Histori tetap tersimpan.');
    }

    public function restore(VersionRequest $request, int $id, MasterDataService $service)
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        $service->restore($customer, $request->validated(), $request->user());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer diaktifkan kembali.');
    }

    private function syncContacts(Customer $customer, array $contacts, $user, MasterDataService $service): void
    {
        if (! $customer->wasRecentlyCreated && $customer->contacts()->exists()) {
            $before = $customer->contacts()->get(['type', 'name', 'company', 'email', 'phone', 'country', 'is_active'])->map(fn ($c) => $c->toArray())->all();
        }
        $customer->contacts()->delete();
        $rows = collect($contacts)->filter(fn ($c) => trim((string) ($c['name'] ?? '')) !== '' && in_array($c['type'] ?? '', ['shipper', 'consignee'], true))
            ->values()->map(fn ($c) => [
                'type' => $c['type'], 'name' => trim($c['name']), 'company' => $c['company'] ?? null,
                'email' => $c['email'] ?? null, 'phone' => $c['phone'] ?? null, 'address' => $c['address'] ?? null,
                'country' => $c['country'] ?? null, 'notes' => $c['notes'] ?? null,
                'is_active' => filter_var($c['is_active'] ?? true, FILTER_VALIDATE_BOOL),
            ])->all();
        if ($rows) {
            $customer->contacts()->createMany($rows);
        }
        $service->log($user, 'customer.contacts_updated', 'Memperbarui shipper/consignee '.$customer->code, ['module' => 'customer', 'record_id' => $customer->id, 'before' => $before ?? null]);
    }

    private function handleUploads(Customer $customer, Request $request, Filesystem $filesystem, ?User $user): void
    {
        foreach (['npwp_file' => 'npwp', 'nib_file' => 'nib'] as $input => $type) {
            if (! $request->hasFile($input)) {
                continue;
            }
            $file = $request->file($input);
            $old = $customer->{$input};
            $path = $file->storeAs('customers/'.$customer->id, $type.'-'.Str::random(16).'.'.$file->extension(), 'local');
            $customer->forceFill([$input => $path])->save();
            $customer->documents()->create([
                'type' => $type, 'filename' => $file->getClientOriginalName(), 'original_name' => $file->getClientOriginalName(),
                'path' => $path, 'disk' => 'local', 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'uploaded_by' => $user?->id,
            ]);
            if ($old && $filesystem->disk('local')->exists($old)) {
                $filesystem->disk('local')->delete($old);
            }
        }
    }
}
