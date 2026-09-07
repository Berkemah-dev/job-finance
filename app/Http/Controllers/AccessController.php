<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function users(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('access.users', ['users' => User::with('role')->orderBy('name')->paginate(15), 'roles' => Role::with('permissions')->get()]);
    }

    public function activity(): View
    {
        return view('access.activity', ['logs' => ActivityLog::with('user')->latest('id')->paginate(15)]);
    }
}
