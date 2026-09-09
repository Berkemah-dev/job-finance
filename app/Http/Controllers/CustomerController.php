<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Customer;
use App\Services\MasterDataService;
use Illuminate\Contracts\Filesystem\Factory as Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $customers = Customer::query()->when($request->input('archived') === '1', fn ($q) => $q->onlyTrashed())
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(CustomerRequest $request, MasterDataService $service, Filesystem $filesystem)
    {
        $validated = $request->validated();
        $contacts = $validated['contacts'] ?? [];
        $customer = $service->save(new Customer, Arr::except($validated, ['contacts']), $request->user());
        $this->syncContacts($customer, $contacts, $request->user(), $service);
        $this->handleUploads($customer, $request, $filesystem);

        return redirect()->route('customers.index')->with('success', 'Customer berhasil ditambahkan.');
    }

    public function edit(Customer $customer)
    {
        $customer->load(['contacts', 'documents']);

        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer, MasterDataService $service, Filesystem $filesystem)
    {
        $validated = $request->validated();
        $contacts = $validated['contacts'] ?? [];
        $service->save($customer, Arr::except($validated, ['contacts']), $request->user());
        $this->syncContacts($customer, $contacts, $request->user(), $service);
        $this->handleUploads($customer, $request, $filesystem);

        return redirect()->route('customers.index')->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(VersionRequest $request, Customer $customer, MasterDataService $service)
    {
        $service->archive($customer, $request->validated(), $request->user());

        return redirect()->route('customers.index')->with('success', 'Customer diarsipkan. Histori tetap tersimpan.');
    }

    private function syncContacts(Customer $customer, array $contacts, $user, MasterDataService $service): void
    {
        if (! $customer->wasRecentlyCreated && $customer->contacts()->exists()) {
            $before = $customer->contacts()->get(['type', 'name', 'company', 'email', 'phone'])->map(fn ($c) => $c->toArray())->all();
        }
        $customer->contacts()->delete();
        $rows = collect($contacts)->filter(fn ($c) => trim((string) ($c['name'] ?? '')) !== '' && in_array($c['type'] ?? '', ['shipper', 'consignee'], true))
            ->values()->map(fn ($c) => [
                'type' => $c['type'], 'name' => trim($c['name']), 'company' => $c['company'] ?? null,
                'email' => $c['email'] ?? null, 'phone' => $c['phone'] ?? null, 'address' => $c['address'] ?? null,
            ])->all();
        if ($rows) {
            $customer->contacts()->createMany($rows);
        }
        $service->log($user, 'customer.contacts_updated', 'Memperbarui shipper/consignee '.$customer->code, ['module' => 'customer', 'record_id' => $customer->id, 'before' => $before ?? null]);
    }

    private function handleUploads(Customer $customer, Request $request, Filesystem $filesystem): void
    {
        foreach (['npwp_file' => 'npwp', 'nib_file' => 'nib'] as $input => $type) {
            if (! $request->hasFile($input)) {
                continue;
            }
            $file = $request->file($input);
            $old = $customer->{$input};
            $path = $file->storeAs('customers/'.$customer->id, $type.'-'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.now()->format('YmdHis').'.'.$file->extension(), 'local');
            $customer->forceFill([$input => $path])->save();
            if ($old && $filesystem->disk('local')->exists($old)) {
                $filesystem->disk('local')->delete($old);
            }
        }
    }
}
