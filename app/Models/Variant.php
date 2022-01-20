<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variant extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'name',
        'name_en',
        'cover'
    ];

    public function items()
    {
        return $this->hasMany(VariantItem::class, 'product_variant_id');
    }
}
