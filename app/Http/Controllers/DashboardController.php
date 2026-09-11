<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $service, Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        $role = (string) ($user->role?->name ?? 'super-admin');

        if ($role === 'super-admin') {
            return redirect()->route('dashboard.finance');
        }

        $viewName = match ($role) {
            'finance' => 'dashboard.finance',
            'finance-manager' => 'dashboard.finance-manager',
            'sales-manager' => 'dashboard.sales-manager',
            'sales' => 'dashboard.sales',
            'operational' => 'dashboard.operational',
            'customer-service' => 'dashboard.customer-service',
            default => 'dashboard.finance',
        };

        return view($viewName, $service->summary($user, $role));
    }

    public function role(string $role, DashboardService $service, Request $request): View
    {
        $user = $request->user();
        $currentRole = (string) ($user->role?->name ?? '');
        abort_unless($currentRole === 'super-admin' || $currentRole === $role, 403);

        $viewName = match ($role) {
            'finance' => 'dashboard.finance',
            'finance-manager' => 'dashboard.finance-manager',
            'sales-manager' => 'dashboard.sales-manager',
            'sales' => 'dashboard.sales',
            'operational' => 'dashboard.operational',
            'customer-service' => 'dashboard.customer-service',
            default => 'dashboard',
        };

        return view($viewName, $service->summary($user, $role));
    }
}
