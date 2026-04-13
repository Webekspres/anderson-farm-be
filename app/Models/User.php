<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'username',
        'password_hash',
        'name',
        'email',
        'phone_number',
        'role',
        'device_id',
        'device_bound_at',
        'is_active',
        'version',
        'server_id',
        'last_validated_at',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at',
        'sync_metadata',
        'remember_token',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    // OVERRIDE: Beri tahu fitur Auth Laravel untuk mengecek kolom 'password_hash'
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'device_bound_at' => 'datetime',
            'last_validated_at' => 'datetime',
            'created_at_client' => 'datetime',
            'created_at_server' => 'datetime',
            'updated_at_client' => 'datetime',
            'updated_at_server' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
