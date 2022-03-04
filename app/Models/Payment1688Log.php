<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment1688Log extends Model
{
    protected $table = 'payment_1688_logs';

    protected $fillable = [
        'admin_id',
        'action',
        'note'
    ];
}
