<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyDynamicLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
    ];

    public function header()
    {
        return $this->belongsTo(DailyActivityHeader::class, 'header_id');
    }

    public function formConfig()
    {
        return $this->belongsTo(FormConfig::class, 'form_config_id');
    }
}
