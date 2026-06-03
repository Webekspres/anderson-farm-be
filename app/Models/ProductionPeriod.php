<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionPeriod extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public function investors()
    {
        return $this->hasMany(PeriodInvestor::class, 'period_id');
    }

    public function period_investors()
    {
        return $this->hasMany(PeriodInvestor::class, 'period_id');
    }

    public function salaries()
    {
        return $this->hasMany(EmployeeSalary::class, 'period_id');
    }

    public function contracts()
    {
        return $this->hasMany(ContractAbk::class, 'period_id');
    }

    public function documents()
    {
        return $this->hasMany(PeriodDocument::class, 'period_id');
    }

    protected $table = 'production_periods';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'floor_id',
        'pic_id',
        'period_code',
        'start_date',
        'end_date',
        'initial_stock',
        'closing_reason',
        'status',
        'closed_at',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
        'server_id' => 'integer',
    ];

    public function floor()
    {
        return $this->belongsTo(CoopFloor::class, 'floor_id');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function dailyActivityHeaders()
    {
        return $this->hasMany(DailyActivityHeader::class, 'period_id');
    }

    public function dailyChecklistLogs()
    {
        return $this->hasMany(DailyChecklistLog::class, 'period_id');
    }

    public function rhpp()
    {
        return $this->hasOne(Rhpp::class, 'period_id');
    }
}
