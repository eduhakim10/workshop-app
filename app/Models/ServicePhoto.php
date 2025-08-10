<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServicePhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'type',
        'image_path',
        'spk_number',
        'damage',
        'uploaded_by'
        
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

}
