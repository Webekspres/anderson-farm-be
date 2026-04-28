<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSalary extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeSalaryFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'employee_salaries';

    // Gunakan kolom timestamp custom untuk offline-first.
    const CREATED_AT = 'created_at_client';
    const UPDATED_AT = 'updated_at_client';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'period_id',
        'employee_id',
        'salary_amount',
        'payment_status',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
        'sync_metadata',
    ];

    protected $casts = [
        'salary_amount' => 'float',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function period()
    {
        return $this->belongsTo(ProductionPeriod::class, 'period_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
