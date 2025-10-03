<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'customer_id',
        'vehicle_id',
        'category_service_id',
        'offer_number',
        'amount_offer',
        'amount_offer_revision',
        'handover_date',
        'work_order_number',
        'work_order_date',
        'invoice_number',
        'invoice_handover_date',
        'assign_to',
        'service_start_date',
        'service_due_date',
        'service_start_time',
        'service_due_time',
        'status',
        'notes',
        'items', 
        'items_offer', 
        'stage',
        'payment_terms',
        'validity_terms',
        'delivery_terms',
        'prepared_by',
        'quotation_status',
         'spk_number',
         'po_number',
         'created_at_offer',
         'updated_at_offer',
          'sr_number',
        'service_request_id',
    ];
    protected $casts = [
        'items' => 'array',
        'items_offer' => 'array',
        'customer_id' => 'integer',
        'kerusakan_after' => 'array',

    ];
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }    

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'assign_to');
    }
   public function preparedBy()
    {
        return $this->belongsTo(Employee::class, 'prepared_by');
    }

    public function items()
    {
        return $this->hasMany(ServiceItem::class);
    }
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
    public function categoryService()
    {
        return $this->belongsTo(CategoryService::class);
    }
    public function assignTo()
    {
        return $this->belongsTo(Employee::class, 'assign_to', 'id');
    }
    // public function photos()
    // {
    //     return $this->hasMany(ServicePhoto::class);
    // }
    public function serviceRequest()
    {
        // pastiin foreign key bener
        return $this->belongsTo(ServiceRequest::class, 'service_request_id', 'id');
    }

    public function photosAfter()
    {
        return $this->hasMany(ServiceRequestPhoto::class, 'service_request_id', 'service_request_id')
                    ->where('type', 'after');
    }
        public function photos()
    {
        return $this->hasMany(ServiceRequestPhoto::class, 'service_request_id', 'service_request_id');
    }
    public function damages()
    {
        return $this->hasMany(ServiceRequestDamage::class, 'service_request_id', 'service_request_id');
    }
    protected static function booted()
    {
        static::creating(function ($service) {
            if ($service->service_request_id) {
                $sr = \App\Models\ServiceRequest::find($service->service_request_id);
                $service->sr_number = $sr?->sr_number;
            }
        });

        static::updating(function ($service) {
            if ($service->service_request_id) {
                $sr = \App\Models\ServiceRequest::find($service->service_request_id);
                $service->sr_number = $sr?->sr_number;
            }
        });
    }




}
