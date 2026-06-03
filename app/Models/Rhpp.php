<?php

namespace App\Models;

use Database\Factories\RhppFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rhpp extends Model
{
    /** @use HasFactory<RhppFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'rhpps';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'created_at_client';

    const UPDATED_AT = 'updated_at_client';

    protected $fillable = [
        'id', 'server_id', 'version', 'period_id', 'total_income', 'total_expense', 'net_profit', 'publish_status',
        'sync_status', 'created_at_client', 'created_at_server', 'updated_at_client', 'updated_at_server', 'deleted_at',
    ];

    protected $casts = [
        'total_income' => 'double',
        'total_expense' => 'double',
        'net_profit' => 'double',
        'created_at_client' => 'datetime',
        'updated_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_server' => 'datetime',
        'server_id' => 'integer',
    ];

    public function period()
    {
        return $this->belongsTo(ProductionPeriod::class);
    }

    public function documents()
    {
        return $this->hasMany(RhppDocument::class, 'Rhpp_id');
    }
}
