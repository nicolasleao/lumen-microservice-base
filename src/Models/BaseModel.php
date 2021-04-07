<?php

namespace LumenMicroservice\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LumenMicroservice\Classes\UUID;

class BaseModel extends Model
{
    use SoftDeletes;    
    public $timestamps = true;

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
    }
}