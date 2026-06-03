<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'transactions';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'created_at_client';

    const UPDATED_AT = 'updated_at_client';

    protected $fillable = [
        'id', 'server_id', 'version', 'period_id', 'coop_id', 'user_id', 'category_id', 'harvest_id', 'salary_id',
        'date', 'amount', 'description', 'reference_no', 'receipt_url', 'receipt_path_local', 'expense_scope',
        'business_status', 'approved_by', 'rejection_reason', 'linked_transaction_id',
        'sync_status', 'created_at_client', 'created_at_server', 'updated_at_client', 'updated_at_server', 'deleted_at', 'sync_metadata',
    ];

    protected $casts = [
        'date' => 'datetime',
        'amount' => 'double',
        'created_at_client' => 'datetime',
        'updated_at_client' => 'datetime',
        'created_at_server' => 'datetime',
        'updated_at_server' => 'datetime',
        'server_id' => 'integer',
    ];

    public function period()
    {
        return $this->belongsTo(ProductionPeriod::class);
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class, 'coop_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function harvest()
    {
        return $this->belongsTo(HarvestEntry::class, 'harvest_id');
    }

    public function salary()
    {
        return $this->belongsTo(EmployeeSalary::class, 'salary_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function linked_transaction()
    {
        return $this->belongsTo(Transaction::class, 'linked_transaction_id');
    }

    public function corrections()
    {
        return $this->hasMany(Transaction::class, 'linked_transaction_id');
    }
}
