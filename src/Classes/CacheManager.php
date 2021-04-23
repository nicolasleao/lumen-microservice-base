<?php

namespace LumenMicroservice\Classes;

use Illuminate\Support\Facades\Cache;
use LumenMicroservice\Models\ApiKey;

class CacheManager {
	public static function getCurrentTenant($apiKey) {
        $cachedHost = Cache::get($apiKey . "_host");
        $cachedSchema = Cache::get($apiKey . "_schema");

        /**
         * Check if there is a cache entry matching current db hostname,
         */
        if($cachedHost) {
            /**
             * If so, we assume that the whole connection is cached and get the
             * extra connection info from the cache
             */
            return [
                'database_schema' => $cachedSchema,
                'database_host' => $cachedHost,
                'database_port' => Cache::get($apiKey . "_port"),
                'database_user' => Cache::get($apiKey . "_user"),
                'database_pass' => Cache::get($apiKey . "_pass"),
                'database_db' => Cache::get($apiKey . "_db"),
            ];
        }
        else {
            if($cachedSchema) {
                return [
	                'database_schema' => $cachedSchema,
	                'database_host' => null,
	                'database_port' => null,
	                'database_user' => null,
	                'database_pass' => null,
	                'database_db' => null,
	            ];
            }
            /**
             * Otherwise, query the database to find the matching api key
             * and cache the query results
             */
            else {
            	try {
	                $key = ApiKey::where('key', $apiKey)->with('tenant')->firstOrFail();
                    $tenant = $key->tenant()->first();
	                if($tenant->database_host) {
	                	$this->setCacheConnection($apiKey, $tenant);
	                }
	                else {
		                $this->setCacheSchema($apiKey, $tenant->database_schema);
	                }
            	}
            	catch (ModelNotFoundException $e) {
            		return null;
            	}

            	return [
            		'database_schema' => $tenant->database_schema,
	                'database_host' => $tenant->database_host,
	                'database_port' => $tenant->database_port,
	                'database_user' => $tenant->database_user,
	                'database_pass' => $tenant->database_pass,
	                'database_db' => $tenant->database_db,
            	];
            }
        }
	}

	private function setCacheSchema($apiKey, $database_schema) {
        Cache::put($apiKey . '_schema', $database_schema, $seconds=(61 * 5));
    }

    private function setCacheConnection($apiKey, $currentTenant) {
        Cache::put($apiKey . '_host', $currentTenant->database_host, $seconds=(60 * 5));
        Cache::put($apiKey . '_port', $currentTenant->database_port, $seconds=(61 * 5));
        Cache::put($apiKey . '_db', $currentTenant->database_db, $seconds=(61 * 5));
        Cache::put($apiKey . '_user', $currentTenant->database_user, $seconds=(61 * 5));
        Cache::put($apiKey . '_pass', $currentTenant->database_pass, $seconds=(61 * 5));
        $this->cache_schema($apiKey, $currentTenant->database_schema);
    }
}