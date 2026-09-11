<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()->load('role.permissions')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'nik' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:40'], 'address' => ['nullable', 'string', 'max:2000'],
            'birth_date' => ['nullable', 'date'], 'gender' => ['nullable', 'in:male,female'],
            'position' => ['nullable', 'string', 'max:120'], 'department' => ['nullable', 'string', 'max:120'],
            'emergency_contact' => ['nullable', 'string', 'max:160'], 'emergency_phone' => ['nullable', 'string', 'max:40'],
        ]);
        $request->user()->update($data);
        return back()->with('success', 'Data profil berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', 'min:10', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/']]);
        $request->user()->update(['password' => Hash::make($data['password'])]);
        return back()->with('success', 'Password berhasil diubah.');
    }
}
