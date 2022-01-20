<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceRange extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'product_price_ranges';

    protected $fillable = ['product_id', 'start_quantity', 'price'];
}
