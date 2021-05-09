<?php

namespace LumenMicroservice\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use LumenMicroservice\Models\Domain;
use LumenMicroservice\Traits\ConnectsToDatabase;

class ServiceTenancyMiddleware
{
    use ConnectsToDatabase;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Get correct database connection information from the request header
        $currentTenant = json_decode($request->header('X-Current-Tenant'), true);

        if(!$currentTenant) {
            return response()->json([
                'error' => 'Invalid or null X-Current-Tenant request header'
            ], 301);
        }
        else {
            if(array_key_exists('database_host', $currentTenant)) {
                $this->useConnection($currentTenant);
            }
            else {
                $this->useSchema($currentTenant['database_schema']);
            }
        }

        return $next($request);
    }
}
