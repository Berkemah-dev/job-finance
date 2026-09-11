<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $service, Request $request): View
    {
        $user = $request->user();
        return view('dashboard', $service->summary($user));
    }

    public function role(string $role, DashboardService $service, Request $request): View
    {
        $currentRole = (string) ($request->user()->role?->name ?? '');
        abort_unless($currentRole === 'super-admin' || $currentRole === $role, 403);

        return view('dashboard', $service->summary($request->user()));
    }
}
