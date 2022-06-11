<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment1688 extends Model
{
    protected $table = 'payment_1688';

    protected $fillable = ['link'];

    public function orders()
    {
        return $this->hasMany(Order::class, 'payment_1688_id');
    }
}
