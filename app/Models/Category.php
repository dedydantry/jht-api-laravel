<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'uuid',
        'name',
        'name_en',
        'image',
        'icon',
        'category_id_1688'
    ];

}
