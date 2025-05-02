<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\CustomerToken;
use App\Services\GoogleAccessTokenService;
use GuzzleHttp\Client;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{

    protected $googleAccessTokenService;

    public function __construct(GoogleAccessTokenService $googleAccessTokenService)
    {
        $this->googleAccessTokenService = $googleAccessTokenService;
    }

    public function index()
    {
        // Get all products with their categories
        $products = Product::with('category')->get();

        // Check if there is a search query
        $searchQuery = request()->q;

        // Sequential search
        if ($searchQuery) {
            $filteredProducts = $products->filter(function($product) use ($searchQuery) {
                // Search in product title and category name
                $titleMatch = stripos($product->title, $searchQuery) !== false;
                $categoryMatch = stripos($product->category->name, $searchQuery) !== false;
                return $titleMatch || $categoryMatch;
            });
        } else {
            $filteredProducts = $products;
        }

        // Convert filtered collection to a query builder
        $query = Product::whereIn('id', $filteredProducts->pluck('id')->toArray());

        // Paginate the filtered results
        $paginatedProducts = $query->paginate(5);

        // Modify the paginated products to include category information
        $paginatedProducts->getCollection()->transform(function ($product ) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'stock' => $product->stock,
                'category' => [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                ],
                // jika ingin menambah data
            ];
        });

        return new ProductResource(true, 'List Data Products', $paginatedProducts);
    }

    public function store(Request $request)
    {
        Log::info('Store method called');

        $validator = $this->validateProduct($request);

        if ($validator->fails()) {
            Log::error('Validation failed: ', $validator->errors()->toArray());
            return response()->json($validator->errors(), 422);
        }

        // Upload image
        $imageName = $this->handleImageUpload($request->file('image'));
        Log::info('Image uploaded: ' . $imageName);

        // Create product
        $product = Product::create($this->getProductData($request, $imageName));
        Log::info('Product created: ', $product->toArray());

        if ($product) {
            $this->sendPushNotification($product);
            return new ProductResource(true, 'Data Product Berhasil Disimpan!', $product);
        }

        return new ProductResource(false, 'Data Product Gagal Disimpan!', null);
    }

    public function show($id)
    {
        $product = Product::find($id);

        if ($product) {
            return new ProductResource(true, 'Detail Data Product!', $product);
        }

        return new ProductResource(false, 'Detail Data Product Tidak Ditemukan!', null);
    }

    public function update(Request $request, Product $product)
    {
        Log::info('Update method called');
        Log::info('Request data: ', $request->all());

        $validator = $this->validateProduct($request, $product->id);

        if ($validator->fails()) {
            Log::error('Validation failed: ', $validator->errors()->toArray());
            return response()->json($validator->errors(), 422);
        }

        // Check if there is a new image file
        if ($request->hasFile('image')) {
            // Remove old image and upload new image
            Storage::disk('local')->delete('public/products/' . basename($product->image));
            $imageName = $this->handleImageUpload($request->file('image'));
        } else {
            // If no new image, keep the old one
            $imageName = $product->image;
        }

        $product->update($this->getProductData($request, $imageName));

        return new ProductResource(true, 'Data Product Berhasil Diupdate!', $product);
    }




    public function destroy(Product $product)
    {
        // Remove image
        Storage::disk('local')->delete('public/products/' . basename($product->image));

        if ($product->delete()) {
            return new ProductResource(true, 'Data Product Berhasil Dihapus!', null);
        }

        return new ProductResource(false, 'Data Product Gagal Dihapus!', null);
    }

    protected function validateProduct(Request $request, $productId = null)
    {
        $rules = [
            'image' => 'required|image|mimes:jpeg,jpg,png|max:2000',
            'title' => 'required|unique:products,title,' . $productId,
            'category_id' => 'required',
            'description' => 'required',
            'weight' => 'required',
            'price' => 'required',
            'stock' => 'required',
            'discount' => 'required'
        ];

        return Validator::make($request->all(), $rules);
    }

    protected function handleImageUpload($image)
    {
        $image->storeAs('public/products', $image->hashName());
        return $image->hashName();
    }

    protected function getProductData(Request $request, $imageName)
    {
        return [
            'image' => $imageName,
            'title' => $request->title,
            'slug' => Str::slug($request->title, '-'),
            'category_id' => $request->category_id,
            'user_id' => auth()->guard('api_admin')->user()->id,
            'description' => $request->description,
            'weight' => $request->weight,
            'price' => $request->price,
            'stock' => $request->stock,
            'discount' => $request->discount
        ];
    }

    protected function sendPushNotification($product)
    {
        Log::info('sendPushNotification method called');

        $accessToken = $this->googleAccessTokenService->getAccessToken();
        Log::info('Access Token: ' . $accessToken);

        $tokens = CustomerToken::with('customer')->get()->pluck('token')->toArray();
        $firebaseToken = array_filter($tokens);
        Log::info('FCM Tokens: ', $firebaseToken);

        // Ensure there are tokens to send to
        if (empty($firebaseToken)) {
            Log::error('No FCM tokens available');
            return;
        }

        foreach ($firebaseToken as $token) {
            $data = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "body" => $product->description,
                        "title" => "New Product: " . $product->title,
                    ]
                ]
            ];

            Log::info('Notification data: ', $data);

            try {
                $client = new Client();
                $response = $client->post('https://fcm.googleapis.com/v1/projects/push-notification-57bbc/messages:send', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data,
                ]);

                if ($response->getStatusCode() != 200) {
                    Log::error('Error sending push notification: ' . $response->getBody());
                } else {
                    Log::info('Push notification sent successfully');
                }
            } catch (\Exception $e) {
                Log::error('Exception sending push notification: ' . $e->getMessage());
            }
        }
    }
}
