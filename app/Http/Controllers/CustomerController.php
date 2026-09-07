<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Requests\VersionRequest;
use App\Models\Customer;
use App\Services\MasterDataService;
use Illuminate\Http\Request;

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

    public function store(CustomerRequest $request, MasterDataService $service)
    {
        $service->save(new Customer, $request->validated(), $request->user());

        return redirect()->route('customers.index')->with('success', 'Customer berhasil ditambahkan.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer, MasterDataService $service)
    {
        $service->save($customer, $request->validated(), $request->user());

        return redirect()->route('customers.index')->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(VersionRequest $request, Customer $customer, MasterDataService $service)
    {
        $service->archive($customer, $request->validated(), $request->user());

        return redirect()->route('customers.index')->with('success','Customer diarsipkan. Histori tetap tersimpan.');
    }
}
