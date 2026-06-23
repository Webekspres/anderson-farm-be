<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoopUserAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\CoopUserAssignmentFactory> */
    use HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids, SoftDeletes;

    protected $table = 'coop_user_assignments';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'user_id',
        'coop_id',
        'assigned_at',
        'role_in_coop',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
        'version' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class, 'coop_id');
    }
}
