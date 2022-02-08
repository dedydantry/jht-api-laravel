<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category1688 extends Model
{
    use HasFactory;

    protected $table = 'category_1688';

    protected $fillable = [
        'category_id_1688',
        'category_id',
        'name',
        'name_en'
    ];
}
