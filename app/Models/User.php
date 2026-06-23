<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use DevKandil\NotiFire\Traits\HasFcm;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasFcm, HasUuids, Notifiable, SoftDeletes;

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
        'fcm_token',
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
            'server_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            // Jika aplikasi berjalan di SQLite dan server_id kosong
            if (empty($user->server_id)) {
                // Beri angka acak agar SQLite tidak marah karena NULL
                $user->server_id = random_int(1, 9999999);
            }
        });
    }

    public function dailyActivityHeaders()
    {
        return $this->hasMany(DailyActivityHeader::class, 'user_id');
    }

    public function approvedActivityHeaders()
    {
        return $this->hasMany(DailyActivityHeader::class, 'approved_by');
    }
}
