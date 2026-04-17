<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OvkItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'ovk_items';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'name',
        'type',
        'unit',
        'description',
        'is_active',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function usages()
    {
        return $this->hasMany(OvkUsage::class, 'ovk_item_id');
    }
}
