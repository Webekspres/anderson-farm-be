<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceReference extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'price_references';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'created_at_client';
    const UPDATED_AT = 'updated_at_client';

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'name',
        'highlight_price',
        'link_url',
        'image_url',
        'image_path_local',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
    ];

    protected $casts = [
        'created_at_client' => 'datetime',
        'updated_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_server' => 'datetime',
    ];
}
