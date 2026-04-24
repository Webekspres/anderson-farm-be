<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormConfig extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'form_configs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'category',
        'key_name',
        'config_value',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
    ];

    protected $casts = [
        'config_value' => 'array',
        'is_active' => 'boolean',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function periodAssignments()
    {
        return $this->hasMany(PeriodFormAssignment::class, 'form_config_id');
    }

    public function coopAssignments()
    {
        return $this->hasMany(CoopFormAssignment::class, 'form_config_id');
    }

    public function dailyLogs()
    {
        return $this->hasMany(DailyDynamicLog::class, 'form_config_id');
    }
}
