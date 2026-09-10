<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Services\MasterDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerContactController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr($request->string('search')->toString(), 0, 100);
        $type = (string) $request->input('type', '');
        $status = (string) $request->input('status', 'active');
        $customerId = (int) $request->input('customer_id', 0);

        $contacts = CustomerContact::query()
            ->with('customer')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('company', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->when(in_array($type, ['shipper', 'consignee'], true), fn ($q) => $q->where('type', $type))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false), fn ($q) => $q->where('is_active', true))
            ->when($customerId > 0, fn ($q) => $q->where('customer_id', $customerId))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('customer-contacts.index', compact('contacts', 'search', 'type', 'status'));
    }

    public function create()
    {
        return view('customer-contacts.form', ['contact' => new CustomerContact, 'customers' => Customer::orderBy('name')->get()]);
    }

    public function store(Request $request, MasterDataService $service)
    {
        $validated = $this->validated($request);
        DB::transaction(function () use ($validated, $request, $service) {
            $contact = CustomerContact::create($validated);
            $service->log($request->user(), 'customer.contact_created', 'Menambahkan '.$contact->type.' '.$contact->name, ['module' => 'customer', 'record_id' => $contact->customer_id]);
        }, 3);

        return redirect()->route('customer-contacts.index')->with('success', 'Kontak berhasil ditambahkan.');
    }

    public function show(CustomerContact $customerContact)
    {
        $customerContact->load('customer');

        return view('customer-contacts.show', ['contact' => $customerContact]);
    }

    public function edit(CustomerContact $customerContact)
    {
        return view('customer-contacts.form', ['contact' => $customerContact, 'customers' => Customer::orderBy('name')->get()]);
    }

    public function update(Request $request, CustomerContact $customerContact, MasterDataService $service)
    {
        $validated = $this->validated($request);
        if (! $this->versionValid($request, $customerContact)) {
            return back()->withInput()->withErrors(['lock_version' => 'Data telah diubah pihak lain. Muat ulang form.']);
        }
        DB::transaction(function () use ($validated, $request, $customerContact, $service) {
            $customerContact->update($validated);
            $customerContact->lock_version++;
            $customerContact->save();
            $service->log($request->user(), 'customer.contact_updated', 'Memperbarui '.$customerContact->type.' '.$customerContact->name, ['module' => 'customer', 'record_id' => $customerContact->customer_id]);
        }, 3);

        return redirect()->route('customer-contacts.show', $customerContact)->with('success', 'Kontak berhasil diperbarui.');
    }

    public function destroy(Request $request, CustomerContact $customerContact, MasterDataService $service)
    {
        if (! $this->versionValid($request, $customerContact)) {
            return back()->withErrors(['lock_version' => 'Data telah diubah pihak lain. Muat ulang halaman.']);
        }
        DB::transaction(function () use ($request, $customerContact, $service) {
            $service->log($request->user(), 'customer.contact_deleted', 'Menghapus '.$customerContact->type.' '.$customerContact->name, ['module' => 'customer', 'record_id' => $customerContact->customer_id]);
            $customerContact->delete();
        }, 3);

        return redirect()->route('customer-contacts.index')->with('success', 'Kontak dihapus.');
    }

    public function forCustomer(Customer $customer): JsonResponse
    {
        return response()->json($customer->contacts()->orderBy('type')->orderBy('name')->get(['id', 'type', 'name', 'company', 'email', 'phone', 'address', 'country', 'notes']));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'type' => ['required', 'in:shipper,consignee'],
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:2000'],
            'country' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function versionValid(Request $request, CustomerContact $contact): bool
    {
        return (int) $request->input('lock_version') === (int) $contact->lock_version;
    }
}
