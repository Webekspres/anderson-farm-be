<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyActivityHeader extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'date' => 'datetime',
        'feed_consumption_kg' => 'float',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(ProductionPeriod::class, 'period_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function dynamicLogs()
    {
        return $this->hasMany(DailyDynamicLog::class, 'header_id');
    }

    public function harvests()
    {
        return $this->hasMany(HarvestEntry::class, 'header_id');
    }

    public function ovkUsages()
    {
        return $this->hasMany(OvkUsage::class, 'header_id');
    }

    public function photos()
    {
        return $this->hasMany(PhotoEvidence::class, 'header_id');
    }

    public function dailyChecklistLogs()
    {
        return $this->hasMany(DailyChecklistLog::class, 'header_id');
    }
}
