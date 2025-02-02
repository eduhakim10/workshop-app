<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    // Tentukan nama tabel jika berbeda dari konvensi
    protected $table = 'meetings';

    // Tentukan kolom yang dapat diisi
    protected $fillable = [
        'title',
        'description',
        'meeting_time',
    ];

    // Jika Anda ingin menambahkan relasi atau metode lain, Anda bisa melakukannya di sini
}