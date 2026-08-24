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
        'attn_quotation',
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
        'portal_service_status_id',
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
        'po_date',
        'po_file',
        'created_at_offer',
        'updated_at_offer',
        'sr_number',
        'service_request_id',
        'service_check_date',
        // Pricing & klasifikasi (added 2026-05)
        'ppn_type',
        'ppn_percent',
        'subtotal_amount',
        'discount_total',
        'total_price',
        'damage_classification',
    ];
    protected $casts = [
        'items' => 'array',
        'items_offer' => 'array',
        'customer_id' => 'integer',
        'kerusakan_after' => 'array',
        'ppn_percent' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total_price' => 'decimal:2',
        'amount_offer' => 'decimal:2',
        'amount_offer_revision' => 'decimal:2',
        'po_date' => 'date',
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

    public function portalServiceStatus()
    {
        return $this->belongsTo(PortalServiceStatus::class);
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
    public function beforePhotos()
    {
        return $this->hasMany(\App\Models\ServiceRequestPhoto::class, 'service_request_id', 'service_request_id')
            ->where('type', 'before');
    }

    public function afterPhotos()
    {
        return $this->hasMany(\App\Models\ServiceRequestPhoto::class, 'service_request_id', 'service_request_id')
            ->where('type', 'after');
    }

        public function photos()
    {
        return $this->hasMany(ServiceRequestPhoto::class, 'service_request_id', 'service_request_id');
    }
    public function damages()
    {
        return $this->hasMany(ServicesRequestDamage::class, 'service_request_id', 'service_request_id');
    }
    public function beforedamages()
    {
        return $this->hasMany(ServicesRequestDamage::class, 'service_request_id', 'service_request_id') ->where('type', 'before')->with('damage');;
    }
    public function afterdamages()
    {
        return $this->hasMany(ServicesRequestDamage::class, 'service_request_id', 'service_request_id') ->where('type', 'after')->with('damage');;;
    }
    
    
    
    protected static function booted()
    {
        static::creating(function ($service) {
            if ($service->service_request_id) {
                $sr = \App\Models\ServiceRequest::find($service->service_request_id);
                $service->sr_number = $sr?->sr_number;
            }

            if (empty($service->portal_service_status_id) && $service->service_request_id) {
                $service->portal_service_status_id = PortalServiceStatus::idByCode('kendaraan_diterima');
            }
        });

        static::updating(function ($service) {
            if ($service->service_request_id) {
                $sr = \App\Models\ServiceRequest::find($service->service_request_id);
                $service->sr_number = $sr?->sr_number;
            }

            if (
                empty($service->portal_service_status_id)
                && $service->isDirty('service_request_id')
                && $service->service_request_id
            ) {
                $service->portal_service_status_id = PortalServiceStatus::idByCode('kendaraan_diterima');
            }
        });
    }

    /**
     * Filter by the period used on the customer portal dashboard.
     * Uses service start date, then quotation date, then created_at.
     */
    public function scopeInPortalPeriod($query, string $from, string $to)
    {
        return $query->whereRaw(
            'DATE(COALESCE(services.service_start_date, services.created_at_offer, services.created_at)) BETWEEN ? AND ?',
            [$from, $to]
        );
    }
}
