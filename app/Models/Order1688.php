<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order1688 extends Model
{
    protected $fillable = ['order_id', 'product_price', 'shipping_fee', 'total'];
    
    protected $table = 'order_1688';
}
