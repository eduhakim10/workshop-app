<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'brand',
        'model',
        'license_plate',
        'color',
        'engine_type',
        'chassis_number',
        'next_service_due_date',
        'last_service_date',
        'notes',
    ];

    // Relationship with Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
