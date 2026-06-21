<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Services\RbacBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class RbacUserController extends Controller
{
    public function __construct(private RbacBuilderService $builder) {}

    public function index(Request $request)
    {
        Gate::authorize('user.manage');
        $query = User::query();
        if (! $request->user()->isSuperAdmin()) {
            $query->where('tenant_id', $request->user()->tenant_id);
        }
        $users = $query->with('roles')->paginate(20);
        
        // Scope roles by tenant
        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $originalTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($request->user()->tenant_id ?? 0);
        
        $roles = Role::orderBy('name')->get();
        
        $registrar->setPermissionsTeamId($originalTeamId);

        return view('rbac.users', compact('users', 'roles'));
    }

    public function assignRole(Request $request, User $user)
    {
        Gate::authorize('user.manage');
        $request->validate(['roles' => 'array', 'roles.*' => 'exists:roles,id']);
        
        // Verify tenant matches if not superadmin
        if (! auth()->user()->isSuperAdmin() && $user->tenant_id !== auth()->user()->tenant_id) {
            abort(403, 'Aksi tidak diizinkan untuk tenant ini.');
        }

        $this->builder->assignUserRole($user->id, $request->roles ?? []);
        return back()->with('success', "Role untuk {$user->nama} diperbarui.");
    }
}
