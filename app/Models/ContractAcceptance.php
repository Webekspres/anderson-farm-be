<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAcceptance extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'contract_acceptances';

    const CREATED_AT = 'created_at_client';
    const UPDATED_AT = 'updated_at_client';

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'contract_id',
        'user_id',
        'accepted_at',
        'device_id',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server'
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(ContractAbk::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
