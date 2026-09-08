<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function users(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('access.users', ['users' => User::with('role')->orderBy('name')->paginate(15), 'roles' => Role::with('permissions')->get()]);
    }

    public function createUser(): View
    {
        Gate::authorize('users.manage');

        return view('access.user-form', ['user' => new User, 'roles' => Role::orderBy('label')->get()]);
    }

    public function storeUser(UserRequest $request, UserService $service)
    {
        $user = $service->save(null, $request->validated(), $request->user());

        return redirect()->route('users.edit', $user)->with('success', 'Pengguna berhasil dibuat.');
    }

    public function editUser(User $user): View
    {
        Gate::authorize('users.manage');

        return view('access.user-form', ['user' => $user, 'roles' => Role::orderBy('label')->get()]);
    }

    public function updateUser(UserRequest $request, User $user, UserService $service)
    {
        $service->save($user, $request->validated(), $request->user());

        return redirect()->route('users.edit', $user)->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function activity(): View
    {
        return view('access.activity', ['logs' => ActivityLog::with('user')->latest('id')->paginate(15)]);
    }
}
