<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceRequest extends Model
{
    use HasFactory;

    // Karena nama tabelnya bukan default
    protected $table = 'services_requests';

    protected $fillable = [
        // 'sr_number',
        'customer_id',
        'kerusakan',
        'created_by',
        'vehicle_id',

        // tambahin field lain sesuai migration
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Ambil nomor terakhir
            $last = self::orderBy('id', 'desc')->first();
            $nextNumber = $last ? $last->id + 1 : 1;

            // Format: SR-YYYY-XXXX
            $model->sr_number = 'SR-' . now()->format('Y') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }
    // Relasi ke Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relasi ke Service
    public function services()
    {
        return $this->hasMany(Service::class);
    }
     public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
     public function photos()
    {
        return $this->hasMany(ServiceRequestPhoto::class, 'service_request_id');
    }
        public function damages()
    {
        return $this->belongsToMany(Damage::class, 'services_request_damages')
                    ->withTimestamps();
    }
    // Relasi ke Vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
