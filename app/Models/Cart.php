<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory;

    use SoftDeletes;

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeOwner($query)
    {
       return $query->where('user_id', auth()->user()->id);
    }

    public function scopeCheckout($query)
    {
       return $query->whereNull('checkout_at');
    }
}
