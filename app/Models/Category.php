<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'uuid',
        'name',
        'name_en',
        'slug',
        'image',
        'icon',
    ];

    protected $casts = ['parent_id' => 'int'];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    public function scopeParent($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function hasProducts()
    {
        return $this->hasMany(Product::class, 'subcategory_id', 'id');
    }
}
