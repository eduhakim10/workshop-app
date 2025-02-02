<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $table = 'rooms';

    protected $fillable = [
        'name',
        'description',
    ];

    // Relasi ke Meeting
    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }
}