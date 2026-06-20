<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->tenant_id !== null) {
                // Load tenant settings
                $settings = [];
                if ($user->tenant && $user->tenant->settings) {
                    foreach ($user->tenant->settings as $s) {
                        $settings[$s->key] = $s->value;
                    }
                }
                $this->context->set(
                    tenantId: $user->tenant_id,
                    branchId: $user->branch_id,
                    settings: $settings,
                );
            }
            // SuperAdmin (tenant_id null) → context stays uninitialized
        }

        return $next($request);
    }
}
