<?php

namespace App\Http\Controllers;

use App\Services\PaymentListService;
use Illuminate\Http\Request;

class PaymentListController extends Controller
{
    public function index(Request $request, PaymentListService $service)
    {
        $filters = $request->validate([
            'category' => ['nullable', 'in:'.implode(',', array_keys(PaymentListService::CATEGORIES))],
            'status' => ['nullable', 'in:paid,unpaid,partial'],
            'number' => ['nullable', 'string', 'max:100'],
            'job_number' => ['nullable', 'string', 'max:100'],
        ]);

        return view('payments.list', ['costs' => $service->query($filters)->paginate(20)->withQueryString(), 'categories' => PaymentListService::CATEGORIES]);
    }
}
