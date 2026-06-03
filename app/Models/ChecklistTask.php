<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistTask extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'checklist_tasks';

    // Disable Laravel's default timestamps karena kita menggunakan penamaan custom
    public $timestamps = false;

    protected $fillable = [
        'server_id',
        'version',
        'period_id',
        'task_name',
        'task_type',
        'description',
        'is_active',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'server_id' => 'integer',
    ];

    /**
     * Relasi ke tabel production_periods
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(ProductionPeriod::class, 'period_id');
    }

    /**
     * Relasi ke tabel daily_checklist_logs
     */
    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyChecklistLog::class, 'task_id');
    }
}
