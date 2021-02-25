<?php

namespace LumenMicroservice\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use LumenMicroservice\Models\Domain;

class ServiceTenancyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Get correct database schema from request header
        $schema_name = $request->header('X-Current-Tenant');

        config(['database.connections.tenant.schema' => $schema_name]);
        DB::statement('SET search_path TO ' . $schema_name);

        return $next($request);
    }
}
