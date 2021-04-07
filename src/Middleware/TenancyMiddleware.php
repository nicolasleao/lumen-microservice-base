<?php

namespace LumenMicroservice\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use LumenMicroservice\Models\ApiKey;
use LumenMicroservice\Classes\CacheManager;
use LumenMicroservice\Traits\ConnectsToDatabase;

class TenancyMiddleware
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
        $apiKey = $request->header('x-api-key');
        
        $currentTenant = CacheManager::getCurrentTenant($apikey);
        if(!$currentTenant) {
            return response()->json([
                'error' => 'The API key is invalid'
            ], 301);
        }
        else {
            if($currentTenant['database_host']) {
                $this->useConnection($currentTenant);
            }
            else {
                $this->useSchema($currentTenant['database_schema']);
            }
        }

        $response = $next($request);
        $response->header('X-Current-Tenant', json_encode($currentTenant));
        return $response;
    }
}
