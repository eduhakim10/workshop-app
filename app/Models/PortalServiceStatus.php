<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalServiceStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'badge_color',
        'clickable_action',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public static function idByCode(string $code): ?int
    {
        return static::query()->where('code', $code)->value('id');
    }
}
