<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\RateService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        RateService::rate('CNY');
        return ProductResource::collection(
            Product::with([
                'category',
                'seller'
            ])
            ->orderBy('created_at', 'DESC')
            ->paginate(10)
        );
    }
}
