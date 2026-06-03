<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coop extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'coops';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'farm_id',
        'name',
        'coop_type',
        'note',
        'is_active',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
        'sync_metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
        'server_id' => 'integer',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    public function coopFloors()
    {
        return $this->hasMany(CoopFloor::class, 'coop_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'coop_id');
    }
}
