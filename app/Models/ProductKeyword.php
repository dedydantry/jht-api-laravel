<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
class ProductKeyword extends Model
{
    use HasFactory, Searchable;

    public $timestamps = false;

    protected $fillable = [
        'product_id', 'keyword'
    ];

    public function searchableAs()
    {
        return 'ProductKeywords';
    }

    public function toSearchableArray()
    {
        return [
            'id'   => $this->getKey(),
            'keyword'=> $this->keyword
        ];
    }
}
