<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicesRequestDamage extends Model
{
     protected $fillable = [
        'service_request_id',
        'damage_id',
        'damage_name'
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServicesRequest::class);
    }
}
