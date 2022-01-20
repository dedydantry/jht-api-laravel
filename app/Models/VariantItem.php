<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantItem extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'product_variant_items';

    protected $fillable = [
        'product_variant_id',
        'stock',
        'price',
        'retail_price',
        'name',
        'name_en',
        'sku_id',
        'spec_id',
        'key_variant'
    ];
}
