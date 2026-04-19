<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentType extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'equipment_types';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'name',
        'description',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
    ];

    public function formConfigs()
    {
        return $this->belongsToMany(
            FormConfig::class,
            'equipment_type_form_configs',
            'equipment_type_id',
            'form_config_id'
        )->withPivot(['display_order', 'sync_status', 'created_at_client', 'created_at_server', 'updated_at_client', 'updated_at_server', 'version', 'id', 'deleted_at']);
    }

    protected $casts = [
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    public function coopEquipments()
    {
        return $this->hasMany(CoopEquipment::class, 'equipment_type_id');
    }

    public function equipmentTypeFormConfigs()
    {
        return $this->hasMany(EquipmentTypeFormConfig::class, 'equipment_type_id');
    }
}
