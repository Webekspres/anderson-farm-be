<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class PeriodFormAssignment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'period_form_assignments';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'period_id',
        'form_config_id',
        'display_order',
        'is_active',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function formConfig()
    {
        return $this->belongsTo(FormConfig::class, 'form_config_id');
    }
}
