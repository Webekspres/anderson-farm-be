<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EducationArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_articles';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'created_at_client';

    const UPDATED_AT = 'updated_at_client';

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'name',
        'excerpt',
        'content_html',
        'category',
        'author_name',
        'link_url',
        'image_url',
        'image_path_local',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
    ];

    protected $casts = [
        'created_at_client' => 'datetime',
        'updated_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_server' => 'datetime',
        'server_id' => 'integer',
    ];
}
