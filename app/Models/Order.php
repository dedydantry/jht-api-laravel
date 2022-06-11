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
        'category_id_blueray',
        'handling_fee',
        'warehouse_delivery_fee',
        'transfer_fee',
        'last_status',
        'invoice_id',
        'paid_at',
        'shipping_method',
        'total_weight',
        'total_cbm',
        'freight_package'
    ];

    protected $casts = [
        'product_price' => 'integer',
        'warehouse_delivery_fee' => 'integer',
        'handling_fee' => 'integer',
        'transfer_fee' => 'integer'
    ];

    public function scopeOwner($query)
    {
        return $query->where('user_id', auth()->user()->id);
    }

    public function categoryBlueray()
    {
        return $this->belongsTo(CategoryBlueray::class, 'category_id_blueray', 'id');
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function rate()
    {
        return $this->hasOne(OrderRate::class);
    }

    public function buying()
    {
        return $this->hasOne(OrderRate::class)->where('type', 'buy');
    }

    public function selling()
    {
        return $this->hasOne(OrderRate::class)->where('type', 'sell');
    }

    public function address()
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function status()
    {
        return $this->hasOne(OrderStatus::class);
    }

    public function statuses()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function bill()
    {
        return $this->hasOne(Bill::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
