<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Models\{Field, FieldRoleOverride, Menu, MenuRoleOverride};
use App\Support\{FieldAcl, MenuRenderer};
use Spatie\Permission\Models\{Permission, Role};
use Spatie\Permission\PermissionRegistrar;

class RbacBuilderService
{
    public function __construct(private AuditLogger $audit) {}

    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->blockIfImpersonating();
        
        $role = Role::findOrFail($roleId);
        $permNames = Permission::whereIn('id', $permissionIds)->pluck('name');
        
        // Wrap Spatie role permissions sync in team context
        $registrar = app(PermissionRegistrar::class);
        $originalTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($role->tenant_id ?? 0);
        
        $old = $role->permissions->pluck('name')->all();
        $role->syncPermissions($permNames);
        
        $registrar->setPermissionsTeamId($originalTeamId);
        
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        FieldAcl::clearCache();
        MenuRenderer::clearCache();
        
        $this->audit->log('rbac.role_permission_changed', auth()->user(), [
            'role_id' => $roleId, 'new' => $permNames->all(),
        ], request(), ['old' => $old]);
    }

    public function setMenuOverride(int $menuId, int $roleId, ?int $tenantId, string $visible): void
    {
        $this->blockIfImpersonating();
        MenuRoleOverride::updateOrCreate(
            ['menu_id' => $menuId, 'role_id' => $roleId, 'tenant_id' => $tenantId],
            ['visible' => $visible],
        );
        MenuRenderer::clearCache();
        $this->audit->log('rbac.menu_override_changed', auth()->user(), [
            'menu_id' => $menuId, 'role_id' => $roleId, 'visible' => $visible,
        ], request());
    }

    public function setFieldOverride(int $fieldId, int $roleId, ?int $tenantId, string $visibility): void
    {
        $this->blockIfImpersonating();
        FieldRoleOverride::updateOrCreate(
            ['field_id' => $fieldId, 'role_id' => $roleId, 'tenant_id' => $tenantId],
            ['visibility' => $visibility],
        );
        FieldAcl::clearCache();
        $this->audit->log('rbac.field_override_changed', auth()->user(), [
            'field_id' => $fieldId, 'role_id' => $roleId, 'visibility' => $visibility,
        ], request());
    }

    public function assignUserRole(int $userId, array $roleIds): void
    {
        $this->blockIfImpersonating();
        $user = \App\Models\User::findOrFail($userId);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        $roles = Role::whereIn('id', $roleIds)->get();
        $user->syncRoles($roles);
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $this->audit->log('rbac.user_role_changed', auth()->user(), [
            'user_id' => $userId, 'roles' => $roles->pluck('name')->all(),
        ], request());
    }

    private function blockIfImpersonating(): void
    {
        if (session()->has('impersonated_by')) {
            abort(403, 'Perubahan RBAC diblokir selama impersonation.');
        }
    }
}
