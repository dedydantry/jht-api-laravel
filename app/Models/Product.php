<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'category_id',
        'subcategory_id',
        'product_id_1688',
        'uuid',
        'name',
        'name_en',
        'price',
        'price_type',
        'moq',
        'cover',
        'weight',
        'height',
        'length',
        'is_lartas',
        'variant_type',
        'last_updated'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function note()
    {
        return $this->hasOne(ProductNote::class);
    }

    public function ranges()
    {
        return $this->hasMany(PriceRange::class);
    }
}
