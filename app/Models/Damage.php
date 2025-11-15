<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Damage extends Model
{
    protected $fillable = ['name', 'description'];

    public function serviceRequests()
    {
        return $this->belongsToMany(ServiceRequest::class, 'services_request_damages')
                    ->withTimestamps();
    }
}
