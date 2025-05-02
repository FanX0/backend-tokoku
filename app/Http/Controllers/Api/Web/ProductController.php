<?php

namespace App\Http\Controllers\Api\Web;

use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductController extends Controller
{
    public function index()
{
    // Get all products with their categories, average ratings, and review counts
    $products = Product::with('category')
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->get();

    // Check if there is a search query
    $searchQuery = request()->q;

    // Sequential search
    if ($searchQuery) {
        $filteredProducts = $products->filter(function($product) use ($searchQuery) {
            return stripos($product->title, $searchQuery) !== false;
        });
    } else {
        $filteredProducts = $products;
    }

    // Paginate the filtered results manually
    $perPage = 8;
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $currentItems = $filteredProducts->slice(($currentPage - 1) * $perPage, $perPage)->values();
    $paginatedProducts = new LengthAwarePaginator($currentItems, $filteredProducts->count(), $perPage, $currentPage, [
        'path' => LengthAwarePaginator::resolveCurrentPath(),
    ]);

    // Return with API Resource
    return new ProductResource(true, 'List Data Products', $paginatedProducts);
}

    public function show($slug)
    {
        $product = Product::with('category', 'reviews.customer')
        //count and average
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->where('slug', $slug)->first();

        if($product) {
            //return success with Api Resource
            return new ProductResource(true, 'Detail Data Product!', $product);
        }

        //return failed with Api Resource
        return new ProductResource(false, 'Detail Data Product Tidak Ditemukan!', null);
    }
}
