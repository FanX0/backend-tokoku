<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerTokenResource;
use App\Models\CustomerToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required'
        ]);

        $customer = Auth::guard('api_customer')->user();

        // Delete any existing tokens for the customer
        CustomerToken::where('customer_id', $customer->id)->delete();

        // Store the new token
        CustomerToken::create([
            'customer_id' => auth()->guard('api_customer')->user()->id,
            'token' => $request->token,
        ]);

         //return with Api Resource
         return new CustomerTokenResource(true, 'List Data Customer', $customer);
        }
}
