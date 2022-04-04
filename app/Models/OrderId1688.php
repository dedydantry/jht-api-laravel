<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderId1688 extends Model
{
    use HasFactory;

    protected $table = 'order_id_1688';

    protected $fillable =[
        'order_id',
        'bulk_payment_at',
        'order_number',
        'payment_1688_id'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
