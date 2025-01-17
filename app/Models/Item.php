<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'item_code',
        'quantity',
        'unit',
        'purchase_price',
        'sales_price',
        'manufacturer_by',
        'warranty_information',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'sales_price' => 'decimal:2',
    ];

    /**
     * Additional business logic or relationships can be added here.
     */

    // Example relationship: If items are associated with a category or inventory
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
