<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceRequestPhoto extends Model
{
    use HasFactory;

    protected $table = 'service_request_photos';

    protected $fillable = [
        'service_request_id',
        'file_path',
        'type', // kalau mau ada before/after, bisa dipakai
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
