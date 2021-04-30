<?php

namespace LumenMicroservice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LumenMicroservice\Classes\UUID;

class BaseModel extends Model
{
    use SoftDeletes;    
    public $timestamps = true;

    /*
     * Ensure laravel doesn't cast UUID key to integer
     * by explicitely defining the $keyType property as string
     */
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            /**
             * Generate random UUID for sharding
             */
            $model->id = UUID::v4();
        });

        static::deleting(function($model) {
            // CASCADE SOFT DELETE
            if (isset($model->cascadeDelete)) {
                collect($model->cascadeDelete)->each(function ($class, $method) use ($model) {
                    if (method_exists($model, $method)) {
                        collect($model->$method($class)->get())->each(function ($model) {
                            $model->delete();
                        });
                        $model->$method($class)->delete();
                    }
                });
            }
            // CASCADE PERMANENTLY DELETE
            if (isset($model->cascadeForceDelete)) {
                collect($model->cascadeForceDelete)->each(function ($class, $method) use ($model) {
                    if (method_exists($model, $method)) {
                        collect($model->$method($class)->get())->each(function ($model) {
                            $model->forceDelete();
                        });
                        $model->$method($class)->forceDelete();
                    }
                });
            }
        });
    }
}