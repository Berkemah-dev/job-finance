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
        $role = (string) ($user->role?->name ?? '');
        return $this->render($role, $service, $user);
    }

    public function role(string $role, DashboardService $service, Request $request): View
    {
        $currentRole = (string) ($request->user()->role?->name ?? '');
        abort_unless($currentRole === 'super-admin' || $currentRole === $role, 403);

        return $this->render($role, $service, $request->user());
    }

    private function render(string $role, DashboardService $service, $user): View
    {
        $view = match ($role) {
            'finance' => 'dashboard.finance',
            'finance-manager' => 'dashboard.finance-manager',
            'sales', 'sales-manager' => 'dashboard.sales',
            'operational' => 'dashboard.operational',
            'customer-service' => 'dashboard.customer-service',
            default => 'dashboard',
        };

        return view($view, $service->summary($user));
    }
}
