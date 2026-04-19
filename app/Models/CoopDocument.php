<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoopDocument extends Model
{
    /** @use HasFactory<\Database\Factories\CoopDocumentFactory> */
    use HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'coop_documents';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'coop_id',
        'name',
        'file_path_local',
        'file_url',
        'file_type',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
    ];

    protected $casts = [
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
        'version' => 'integer',
    ];

    public function coop()
    {
        return $this->belongsTo(Coop::class, 'coop_id');
    }
}
