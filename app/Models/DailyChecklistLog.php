<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyChecklistLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'boolean_value' => 'boolean',
        'created_at_client' => 'datetime',
        'updated_at_client' => 'datetime',
        'server_id' => 'integer',
    ];

    public function header()
    {
        return $this->belongsTo(DailyActivityHeader::class, 'header_id');
    }

    public function period()
    {
        return $this->belongsTo(ProductionPeriod::class, 'period_id');
    }

    public function task()
    {
        return $this->belongsTo(ChecklistTask::class, 'task_id');
    }
}
