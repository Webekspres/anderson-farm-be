<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentTypeFormConfig extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'equipment_type_form_configs';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'equipment_type_id',
        'form_config_id',
        'display_order',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
    ];

    protected $casts = [
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function equipmentType()
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    public function formConfig()
    {
        return $this->belongsTo(FormConfig::class, 'form_config_id');
    }
}
