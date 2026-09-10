<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $service, Request $request): View
    {
        return view('dashboard', $service->summary($request->user()));
    }
}
