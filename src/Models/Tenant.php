<?php

namespace LumenMicroservice\Models;

use Illuminate\Support\Facades\DB;
use LumenMicroservice\Traits\ConnectsToDatabase;

class Tenant extends BaseModel
{
    use ConnectsToDatabase;
    
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'landlord';
    protected $table = 'tenants';

    protected $fillable = [
        'user_id',
        'name',
        'database_schema',
        'database_host',
        'database_port',
        'database_user',
        'database_pass',
        'database_db',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            /**
             * Generate random key and a schema name if it wasn't provided
             * Output example: tenant1610669219028c790a9e7af279a88d0de30a
             */

            if(!$model->database_schema) {
                $generatedSchemaName = 'tenant' . time() . substr(md5(rand()), 0, 25);
                $model->database_schema = $generatedSchemaName;
            }
        });
    }
}