<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CoopFloor extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'coop_floors';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'coop_id',
        'name',
        'capacity',
        'coop_type',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function coop()
    {
        return $this->belongsTo(Coop::class, 'coop_id');
    }

    public function productionPeriods()
    {
        return $this->hasMany(ProductionPeriod::class, 'floor_id');
    }

    public function coopEquipments()
    {
        return $this->hasMany(CoopEquipment::class, 'floor_id');
    }
}
