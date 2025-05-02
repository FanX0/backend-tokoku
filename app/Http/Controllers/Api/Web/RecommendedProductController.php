<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecommendedProductResource;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RecommendedProduct;
use App\Models\SkinDetail;
use Illuminate\Http\Request;

class RecommendedProductController extends Controller
{
     //laravel 11 middleware
     public static function middleware(): array
     {
         return [
             'auth:api_customer',
         ];
     }

     public function index()
    {
        $recomended = RecommendedProduct::with('product.category')
                ->where('customer_id', auth()->guard('api_customer')->user()->id)
                ->latest()
                ->get();

        //return with Api Resource
        return new RecommendedProductResource(true, 'List Data Rekomendasi Produk : '.auth()->guard('api_customer')->user()->name.'', $recomended);
    }

    }


