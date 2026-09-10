<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
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

    public function activity(Request $request): View
    {
        $filters = [
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'user_id' => $request->integer('user_id') ?: null,
            'role_id' => $request->integer('role_id') ?: null,
            'module' => (string) $request->input('module', ''),
            'action' => (string) $request->input('action', ''),
        ];

        $logs = ActivityLog::with(['user', 'role'])
            ->when($filters['date_from'], fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when($filters['user_id'], fn ($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['role_id'], fn ($q) => $q->where('role_id', $filters['role_id']))
            ->when($filters['module'] !== '', fn ($q) => $q->where('module', $filters['module']))
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->latest('id')->paginate(15)->withQueryString();

        $roles = Role::orderBy('label')->get(['id', 'label']);
        $users = User::orderBy('name')->get(['id', 'name']);
        $modules = ActivityLog::query()->whereNotNull('module')->select('module')->distinct()->orderBy('module')->pluck('module');

        return view('access.activity', compact('logs', 'filters', 'roles', 'users', 'modules'));
    }
}
