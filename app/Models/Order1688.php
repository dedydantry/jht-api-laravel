<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order1688 extends Model
{
    protected $table = 'order_1688';

    protected $fillable = [
        'order_id',
        'product_price',
        'shipping_fee',
        'total',
        'logistic_bill_no',
        'logistic_company_name',
        'courier',
        'receipt_no',
    ];
}
