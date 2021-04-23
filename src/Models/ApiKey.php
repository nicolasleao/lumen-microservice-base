<?php

namespace LumenMicroservice\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use LumenMicroservice\Traits\ConnectsToDatabase;

class ApiKey extends BaseModel
{
    use ConnectsToDatabase;
    
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'landlord';
    protected $table = 'api_keys';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'user_id',
        'role',
        'is_default',
    ];

    /**
     * Get the tenant that owns this key
     */
    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}