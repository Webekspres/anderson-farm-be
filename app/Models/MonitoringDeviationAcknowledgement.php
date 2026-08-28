<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringDeviationAcknowledgement extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'period_id',
        'user_id',
        'metric',
        'deviation_date',
        'acknowledged_at',
    ];

    protected $casts = [
        'deviation_date' => 'date',
        'acknowledged_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(ProductionPeriod::class, 'period_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
