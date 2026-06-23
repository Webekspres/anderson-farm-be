<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PeriodDocument extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'period_documents';

    // Memberitahu Laravel untuk menggunakan field custom ini sebagai timestamps
    const CREATED_AT = 'created_at_client';

    const UPDATED_AT = 'updated_at_client';

    protected $fillable = [
        'server_id',
        'version',
        'period_id',
        'title',
        'document_type',
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
        'file_metadata' => 'array',
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
}
