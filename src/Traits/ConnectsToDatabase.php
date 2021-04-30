<?php

namespace LumenMicroservice\Traits;

use Illuminate\Support\Facades\DB;

trait ConnectsToDatabase
{
    /**
     * Setup database schema in the laravel config
     *
     * @param  string $database_schema
     */
    public function useSchema($database_schema = null) {
        config(['database.connections.tenant.schema' => $database_schema]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::statement('CREATE SCHEMA IF NOT EXISTS ' . $database_schema . ';');
        DB::statement('SET search_path TO ' . $database_schema . ';');
    }

    /**
     * Setup the whole database connection in the laravel config
     *
     * @param  string $database_schema
     */
    public function useConnection($currentTenant = null) {
        config(['database.connections.tenant.host' => $currentTenant['database_host']]);
        config(['database.connections.tenant.port' => $currentTenant['database_port']]);
        config(['database.connections.tenant.database' => $currentTenant['database_db']]);
        config(['database.connections.tenant.username' => $currentTenant['database_user']]);
        config(['database.connections.tenant.password' => $currentTenant['database_pass']]);
        $this->use_schema($database_schema);
    }
}
