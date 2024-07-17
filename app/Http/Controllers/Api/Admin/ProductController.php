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
        // Get products with optional search query
        $products = Product::with('category')
            ->when(request()->q, function($query) {
                $query->where('title', 'like', '%' . request()->q . '%');
            })
            ->latest()
            ->paginate(5);

        return new ProductResource(true, 'List Data Products', $products);
    }

    public function store(Request $request)
    {
        $validator = $this->validateProduct($request);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Upload image
        $imageName = $this->handleImageUpload($request->file('image'));

        // Create product
        $product = Product::create($this->getProductData($request, $imageName));

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
        $validator = $this->validateProduct($request, $product->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->file('image')) {
            // Remove old image and upload new image
            Storage::disk('local')->delete('public/products/' . basename($product->image));
            $imageName = $this->handleImageUpload($request->file('image'));
            $product->image = $imageName;
        }

        $product->update($this->getProductData($request, $product->image));

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
            'image' => 'sometimes|required|image|mimes:jpeg,jpg,png|max:2000',
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
    $accessToken = $this->googleAccessTokenService->getAccessToken();
    Log::info('Access Token: ' . $accessToken);
    $tokens = CustomerToken::with('customer')->get()->pluck('token')->toArray();
    $firebaseToken = array_filter($tokens);

    Log::info('FCM Tokens: ', $firebaseToken);

    // Creating the message payload
    $data = [
        "message" => [
            "notification" => [
                "body" => $product->description,
                "title" => "New Product: " . $product->title,
            ]
        ]
    ];

    // If only one token, add it to the message payload
    if (count($firebaseToken) == 1) {
        $data["message"]["token"] = $firebaseToken[0];
    } else {
        // For multiple tokens, use 'tokens'
        $data["message"]["token"] = $firebaseToken[0]; // Adjusted for single token usage, change logic for batch messaging if needed.
    }

    Log::info('LOG SAYA: ', $data);

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
        }
    } catch (\Exception $e) {
        Log::error('Exception sending push notification: ' . $e->getMessage());
    }
}

}
