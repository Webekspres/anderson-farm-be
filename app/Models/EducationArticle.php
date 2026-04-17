<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EducationArticle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_articles';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'name',
        'excerpt',
        'link_url',
        'image_url',
        'image_path_local',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
