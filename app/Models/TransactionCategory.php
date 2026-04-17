<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

// If Transaction model exists, import it. Otherwise, comment out the relation.
// use App\Models\Transaction;

class TransactionCategory extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transaction_categories';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'server_id',
        'version',
        'name',
        'type',
        'is_active',
        'sync_status',
        'created_at_client',
        'created_at_server',
        'updated_at_client',
        'updated_at_server',
        'deleted_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_client' => 'datetime',
        'updated_at_server' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Uncomment if Transaction model exists
    // public function transactions()
    // {
    //     return $this->hasMany(Transaction::class, 'category_id');
    // }
}
