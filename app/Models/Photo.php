<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'path', // contoh, sesuaikan sama field yang lo bikin
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
