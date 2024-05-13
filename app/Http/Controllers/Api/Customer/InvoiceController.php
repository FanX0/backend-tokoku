<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::latest()->when(request()->q, function($invoices) {
            $invoices = $invoices->where('invoice', 'like', '%'. request()->q . '%');
        })->where('customer_id', auth()->guard('api_customer')->user()->id)->paginate(5);

        //return with Api Resource
        return new InvoiceResource(true, 'List Data Invoices : '.auth()->guard('api_customer')->user()->name.'', $invoices);
    }

    public function show($snap_token)
    {
        $invoice = Invoice::with('orders.product', 'customer', 'city', 'province')->where('customer_id', auth()->guard('api_customer')->user()->id)->where('snap_token', $snap_token)->first();

        if($invoice) {
            //return success with Api Resource
            return new InvoiceResource(true, 'Detail Data Invoice : '.$invoice->snap_token.'', $invoice);
        }

        //return failed with Api Resource
        return new InvoiceResource(false, 'Detail Data Invoice Tidak DItemukan!', null);
    }
}
