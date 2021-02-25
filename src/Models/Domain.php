<?php

namespace LumenMicroservice\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use SoftDeletes;
    
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'landlord';
    protected $table = 'tenants';

    protected $fillable = [
        'store_id',
        'domain',
        'database_schema',
    ];

    public function use() {
        config(['database.connections.tenant.schema' => $this->database_schema]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::statement('CREATE SCHEMA IF NOT EXISTS ' . $this->database_schema . ';');
        DB::statement('SET search_path TO ' . $this->database_schema);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            /**
             * Generate a schema name if it doesn't exist
             * Output example: 16106692190b125a028c790a9e7af279a88d0de30a
             */
            if(!$model->database_schema) {
                $generatedSchemaName = 'tenant' . time() . md5(rand());
                $model->database_schema = $generatedSchemaName;
            }
        });
    }
}