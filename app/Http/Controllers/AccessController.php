<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\MasterDataService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function users(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'role_id' => $request->integer('role_id') ?: null,
            'status' => (string) $request->input('status', 'all'),
        ];

        $query = User::with('role')
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role_id'], fn ($query) => $query->where('role_id', $filters['role_id']))
            ->when($filters['status'] === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($query) => $query->where('is_active', false));

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'roles' => Role::count(),
        ];

        return view('access.users', [
            'users' => $query->orderBy('name')->paginate(10)->withQueryString(),
            'roles' => Role::with('permissions')->orderBy('label')->get(),
            'filters' => $filters,
            'stats' => $stats,
        ]);
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

    public function toggleUser(Request $request, User $user, MasterDataService $master)
    {
        Gate::authorize('users.manage');

        if ($request->user()->is($user) && $user->is_active) {
            return back()->with('error', 'Akun sendiri tidak bisa dinonaktifkan.');
        }

        $user->forceFill([
            'is_active' => ! $user->is_active,
            'lock_version' => $user->lock_version + 1,
        ])->save();

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $master->log($request->user(), 'user.status', "Akun {$user->email} {$status}");

        return back()->with('success', "Akun {$user->name} berhasil {$status}.");
    }

    public function generatePassword(Request $request, User $user, MasterDataService $master)
    {
        Gate::authorize('users.manage');

        $password = Str::password(12, letters: true, numbers: true, symbols: false, spaces: false);

        $user->forceFill([
            'password' => Hash::make($password),
            'lock_version' => $user->lock_version + 1,
        ])->save();

        $master->log($request->user(), 'user.password.generated', 'Generate password baru untuk '.$user->email);

        return back()->with('success', 'Password baru berhasil dibuat.')->with('generated_password', [
            'name' => $user->name,
            'email' => $user->email,
            'password' => $password,
        ]);
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
            ->latest('id')->paginate(10)->withQueryString();

        $roles = Role::orderBy('label')->get(['id', 'label']);
        $users = User::orderBy('name')->get(['id', 'name']);
        $modules = ActivityLog::query()->whereNotNull('module')->select('module')->distinct()->orderBy('module')->pluck('module');

        return view('access.activity', compact('logs', 'filters', 'roles', 'users', 'modules'));
    }
}
