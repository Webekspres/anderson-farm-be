<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoopFloor extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'coop_floors';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'server_id',
        'coop_id',
        'name',
        'capacity',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
        'sync_metadata',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'server_id' => 'integer',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function coop()
    {
        return $this->belongsTo(Coop::class, 'coop_id');
    }

    public function productionPeriods()
    {
        return $this->hasMany(ProductionPeriod::class, 'floor_id');
    }

    public function coopEquipments()
    {
        return $this->hasMany(CoopEquipment::class, 'floor_id');
    }
}
