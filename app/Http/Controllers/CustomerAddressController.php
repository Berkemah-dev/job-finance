<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerAddressController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('customers.view');

        $search = mb_substr($request->string('search')->toString(), 0, 80);
        $customerId = $request->integer('customer_id') ?: null;

        $addresses = CustomerAddress::with('customer')
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('location_name', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
                ->orWhere('pic_name', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
            ))
            ->orderBy('customer_id')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::orderBy('name')->get(['id', 'name', 'code']);

        return view('customer-addresses.index', compact('addresses', 'customers', 'search', 'customerId'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('customers.manage');

        $validated = $request->validate([
            'customer_id'   => 'required|exists:customers,id',
            'location_name' => 'required|string|max:160',
            'address'       => 'required|string|max:5000',
            'pic_name'      => 'nullable|string|max:160',
            'pic_phone'     => 'nullable|string|max:50',
            'is_default'    => 'nullable|boolean',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = true;

        if ($validated['is_default']) {
            CustomerAddress::where('customer_id', $validated['customer_id'])->update(['is_default' => false]);
        }

        $addr = CustomerAddress::create($validated);

        $backUrl = $request->input('redirect_to');
        if ($backUrl) {
            return redirect($backUrl)->with('success', 'Alamat lokasi ' . $addr->location_name . ' berhasil ditambahkan.');
        }

        return redirect()->route('customer-addresses.index', ['customer_id' => $validated['customer_id']])
            ->with('success', 'Alamat lokasi ' . $addr->location_name . ' berhasil ditambahkan.');
    }

    public function update(Request $request, CustomerAddress $address): RedirectResponse
    {
        Gate::authorize('customers.manage');

        $validated = $request->validate([
            'location_name' => 'required|string|max:160',
            'address'       => 'required|string|max:5000',
            'pic_name'      => 'nullable|string|max:160',
            'pic_phone'     => 'nullable|string|max:50',
            'is_default'    => 'nullable|boolean',
            'is_active'     => 'nullable|boolean',
        ]);

        $validated['is_default'] = $request->boolean('is_default');
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($validated['is_default']) {
            CustomerAddress::where('customer_id', $address->customer_id)->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        $backUrl = $request->input('redirect_to');
        if ($backUrl) {
            return redirect($backUrl)->with('success', 'Alamat lokasi ' . $address->location_name . ' berhasil diperbarui.');
        }

        return redirect()->back()->with('success', 'Alamat lokasi ' . $address->location_name . ' berhasil diperbarui.');
    }

    public function destroy(CustomerAddress $address): RedirectResponse
    {
        Gate::authorize('customers.manage');

        $name = $address->location_name;
        $address->delete();

        return redirect()->back()->with('success', 'Alamat lokasi ' . $name . ' berhasil dihapus.');
    }

    public function apiList(Customer $customer): JsonResponse
    {
        $addresses = $customer->addresses()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('location_name')
            ->get(['id', 'location_name', 'address', 'pic_name', 'pic_phone', 'is_default']);

        return response()->json($addresses);
    }
}
