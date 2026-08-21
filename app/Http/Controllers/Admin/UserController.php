<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Read-only for now. Spec §14 lists user management under the admin
     * dashboard, but actually changing someone's role is exactly the
     * kind of sensitive operation §89 requires ConfirmPasswordModal for
     * — building that properly (role change + audit log entry, spec §90)
     * deserves its own pass rather than a bolt-on here. This gives
     * admins visibility now without the half-built danger of an
     * unaudited role-change endpoint.
     */
    public function index(Request $request): Response
    {
        $query = User::query()->with('roles');

        if ($role = $request->string('role')->trim()->value()) {
            $query->role($role);
        }

        $users = $query->latest()->paginate(25)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'roles' => $u->roles->pluck('name'),
                'createdAt' => $u->created_at->toIso8601String(),
            ]),
            'filters' => $request->only('role'),
        ]);
    }
}
