<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PeriodInvestor extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'period_investors';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'period_id',
        'user_id',
        'profit_share_percentage',
        'initial_investment',
        'final_dividend_amount',
        'is_paid',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
        'sync_metadata',
    ];

    protected $casts = [
        'profit_share_percentage' => 'float',
        'initial_investment' => 'float',
        'final_dividend_amount' => 'float',
        'is_paid' => 'boolean',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
