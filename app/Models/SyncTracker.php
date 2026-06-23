<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncTracker extends Model
{
    use HasFactory;

    protected $primaryKey = 'table_name';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'last_server_id' => 'integer',
        'last_sync_at' => 'datetime',
    ];
}
