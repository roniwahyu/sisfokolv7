<?php

namespace App\Http\Middleware;

use App\Support\PluginRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePluginEnabled
{
    public function __construct(private PluginRegistry $registry) {}

    public function handle(Request $request, Closure $next, string $pluginKode): Response
    {
        $user = $request->user();

        // SuperAdmin bypass
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        $tenantId = $user?->tenant_id;
        if (! $this->registry->isActiveForTenant($pluginKode, $tenantId)) {
            abort(403, "Plugin '{$pluginKode}' tidak aktif untuk tenant Anda.");
        }

        return $next($request);
    }
}
