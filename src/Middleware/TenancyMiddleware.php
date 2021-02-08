<?php

namespace LumenMicroservice\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant;

class TenancyMiddleware
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
        // Try to get cached schema for current hostname
        $domain = $request->getHost();
        $cachedSchema = Cache::get($domain);

        /**
         * If there is a cache entry matching current hostname, 
         * set the current connection schema to that entry.
         */
        if($cachedSchema) {
            config(['database.connections.tenant.schema' => $cachedSchema]);
            DB::statement('SET search_path TO ' . $cachedSchema);
        }
        /**
         * Otherwise, query the database to find the matching database_schema
         * for current domain, and create a new cache entry with the domain as the key
         * and database_schema as the value.
         */
        else {
            $tenant = Tenant::where('domain', $domain)->firstOrFail();
            config(['database.connections.tenant.schema' => $tenant->database_schema]);
            DB::statement('SET search_path TO ' . $tenant->database_schema);
            Cache::put($domain, $tenant->database_schema, $seconds=(24 * 60 * 60));
        }

        return $next($request);
    }
}
