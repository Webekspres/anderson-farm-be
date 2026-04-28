<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RhppDocument extends Model
{
    /** @use HasFactory<\Database\Factories\RhppDocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'rhpp_documents';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'created_at_client';
    const UPDATED_AT = 'updated_at_client';

    protected $fillable = [
        'id', 'server_id', 'version', 'Rhpp_id', 'name', 'file_path_local', 'file_url', 'file_type',
        'sync_status', 'created_at_client', 'created_at_server', 'updated_at_client', 'updated_at_server', 'deleted_at'
    ];

    protected $casts = [
        'created_at_client' => 'datetime',
        'updated_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_server' => 'datetime',
    ];

    public function rhpp() { return $this->belongsTo(Rhpp::class, 'Rhpp_id'); }
}
