<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoopEquipment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'coop_equipments';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'coop_id',
        'equipment_type_id',
        'unit_code',
        'installed_at',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
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

    public function equipmentType()
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    public function coopFormAssignments()
    {
        return $this->hasMany(CoopFormAssignment::class, 'coop_equipment_id');
    }

    protected static function booted()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'coopFormAssignments')) {
                $model->coopFormAssignments()->delete();
            }
        });
    }
}
