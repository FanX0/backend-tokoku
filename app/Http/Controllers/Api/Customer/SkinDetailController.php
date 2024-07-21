<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\SkinDetailResource;
use App\Models\Product;
use App\Models\RecommendedProduct;
use App\Models\SkinDetail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class SkinDetailController extends Controller
{
    public function index()
    {
        $skindetails = SkinDetail::latest()->when(request()->q, function($skindetails) {
            $skindetails = $skindetails->where('invoice', 'like', '%'. request()->q . '%');
        })->where('customer_id', auth()->guard('api_customer')->user()->id)->paginate(5);

        //return with Api Resource
        return new SkinDetailResource(true, 'List Data Invoices : '.auth()->guard('api_customer')->user()->name.'', $skindetails);
    }

    public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'jenis_kulit' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    $customer = auth()->guard('api_customer')->user();

    //create skin detail
    $skindetail = SkinDetail::create([
        'customer_id' => $customer->id,
        'jenis_kulit' => $request->jenis_kulit,
    ]);

    // Tentukan produk yang direkomendasikan berdasarkan jenis kulit
    $recommendedProducts = [];

    switch ($request->jenis_kulit) {
        case 'berminyak':
            $recommendedProducts = [Product::find(46), Product::find(29), Product::find(31)];
            break;
        case 'kering':
            $recommendedProducts = [Product::find(46), Product::find(31)];
            break;
        case 'berflek':
            $recommendedProducts = [Product::find(30), Product::find(45), Product::find(34), Product::find(31)];
            break;
        case 'normal':
            $recommendedProducts =[Product::find(46), Product::find(31), Product::find(44)];
            break;
        case 'bumil':
            $recommendedProducts = [Product::find(28)];
            break;
        case 'berjerawat':
            $recommendedProducts = [Product::find(35)];
            break;
    }

    // Simpan data produk yang direkomendasikan
    foreach ($recommendedProducts as $recommendedProduct) {
        if ($recommendedProduct && $skindetail) {
            RecommendedProduct::create([
                'customer_id' => $customer->id,
                'skindetail_id' => $skindetail->id,
                'product_id' => $recommendedProduct->id,
            ]);
        }
    }

    if ($skindetail) {
        //return with Api Resource
        return new SkinDetailResource(true, 'Register Skin Detail Berhasil', [
            'skindetail' => $skindetail,
            'recommended_products' => $recommendedProducts
        ]);
    }

    //return failed with Api Resource
    return new SkinDetailResource(false, 'Register Skin Detail Gagal!', null);
}



    public function show($id)
    {
        $skindetail = SkinDetail::whereId($id)->first();

        if($skindetail) {
            //return success with Api Resource
            return new SkinDetailResource(true, 'Detail Data SkinDetail!', $skindetail);
        }

        //return failed with Api Resource
        return new SkinDetailResource(false, 'Detail Data SkinDetail Tidak Ditemukan!', null);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'jenis_kulit' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $customer = auth()->guard('api_customer')->user();

        //cari skin detail berdasarkan id dan customer id
        $skindetail = SkinDetail::where('id', $id)->where('customer_id', $customer->id)->first();

        if (!$skindetail) {
            return response()->json(['error' => 'Skin detail not found'], 404);
        }

        //update skin detail
        $skindetail->jenis_kulit = $request->jenis_kulit;
        $skindetail->save();

        // Hapus rekomendasi produk lama
        RecommendedProduct::where('skindetail_id', $skindetail->id)->delete();

        // Tentukan produk yang direkomendasikan berdasarkan jenis kulit baru
        $recommendedProducts = [];

        switch ($request->jenis_kulit) {
            case 'berminyak':
                $recommendedProducts = [Product::find(46), Product::find(29), Product::find(31)];
                break;
            case 'kering':
                $recommendedProducts = [Product::find(46), Product::find(31)];
                break;
            case 'berflek':
                $recommendedProducts = [Product::find(30), Product::find(45), Product::find(34), Product::find(31)];
                break;
            case 'normal':
                $recommendedProducts =[Product::find(46), Product::find(31), Product::find(44)];
                break;
            case 'bumil':
                $recommendedProducts = [Product::find(28)];
                break;
            case 'berjerawat':
                $recommendedProducts = [Product::find(35)];
                break;
        }

        // Simpan data produk yang direkomendasikan baru
        foreach ($recommendedProducts as $recommendedProduct) {
            if ($recommendedProduct && $skindetail) {
                RecommendedProduct::create([
                    'customer_id' => $customer->id,
                    'skindetail_id' => $skindetail->id,
                    'product_id' => $recommendedProduct->id,
                ]);
            }
        }

        if ($skindetail) {
            //return with Api Resource
            return new SkinDetailResource(true, 'Update Skin Detail Berhasil', [
                'skindetail' => $skindetail,
                'recommended_products' => $recommendedProducts
            ]);
        }

        //return failed with Api Resource
        return new SkinDetailResource(false, 'Update Skin Detail Gagal!', null);
    }

}
