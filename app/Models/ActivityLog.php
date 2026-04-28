<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityLogFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'activity_logs';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'created_at_client';
    const UPDATED_AT = 'updated_at_client';

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'device_id',
        'status',
        'metadata',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
        'sync_metadata',
    ];

    protected $casts = [
        'created_at_client' => 'datetime',
        'updated_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_server' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
