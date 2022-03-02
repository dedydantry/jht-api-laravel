<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'product_variant_item_id',
        'quantity',
        'variant_type',
        'variant_name',
        'variant_item_name',
        'spec_id',
        'original_price',
        'final_price',
        'discount'
    ];
}
