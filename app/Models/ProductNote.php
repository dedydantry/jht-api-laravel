<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductNote extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'product_notes';

    protected $fillable = [
        'note',
    ];
}
