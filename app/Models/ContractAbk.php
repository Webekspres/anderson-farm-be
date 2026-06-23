<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractAbk extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'contract_abks';

    // Memberitahu Laravel untuk menggunakan field custom ini sebagai timestamps bawaan
    const CREATED_AT = 'created_at_client';

    const UPDATED_AT = 'updated_at_client';

    protected $fillable = [
        'server_id',
        'version',
        'period_id',
        'title',
        'file_path_local',
        'file_url',
        'uploaded_by',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
    ];

    protected $casts = [
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'server_id' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(ProductionPeriod::class, 'period_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(ContractAcceptance::class, 'contract_id');
    }
}
