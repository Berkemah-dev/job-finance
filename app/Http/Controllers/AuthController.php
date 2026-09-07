<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthenticationService $service): RedirectResponse
    {
        $service->login($request);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuthenticationService $service): RedirectResponse
    {
        $service->logout($request);

        return redirect()->route('login');
    }
}
