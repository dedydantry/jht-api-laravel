<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'cart_id',
        'order_number',
        'product_name',
        'product_cover',
        'product_price',
        'handling_fee',
        'warehouse_delivery_fee',
        'last_status',
        'invoice_id',
        'paid_at',
        'shipping_method'
    ];

    public function scopeOwner($query)
    {
       return $query->where('user_id', auth()->user()->id);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeHasPaid($query)
    {
        return $query->whereNotNull('paid_at');
    }

    public function order1688()
    {
        return $this->hasOne(Order1688::class);
    }

    public function orderId1688()
    {
        return $this->hasOne(OrderId1688::class);
    }

    public function orderId1688s()
    {
        return $this->hasMany(OrderId1688::class);
    }
}
