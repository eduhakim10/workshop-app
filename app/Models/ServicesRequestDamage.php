<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicesRequestDamage extends Model
{

    protected $table = 'services_request_damages'; // pastikan sesuai nama tabel di DB

    protected $fillable = [
        'service_request_id',
        'damage_id',
        'damage_name',
        'type',
    ];
    

    public function serviceRequest()
    {
        return $this->belongsTo(ServicesRequest::class);
    }
    public function damage()
    {
        return $this->belongsTo(Damage::class, 'damage_id');
    }
}
