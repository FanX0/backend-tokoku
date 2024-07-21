<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoiceController extends Controller
{
    public function index()
{
    // Get all invoices with their customers
    $invoices = Invoice::with('customer')->get();

    // Check if there is a search query
    $searchQuery = request()->q;

    // Sequential search
    if ($searchQuery) {
        $filteredInvoices = $invoices->filter(function($invoice) use ($searchQuery) {
            return stripos($invoice->invoice, $searchQuery) !== false;
        });
    } else {
        $filteredInvoices = $invoices;
    }

    // Convert filtered collection to a query builder
    $query = Invoice::whereIn('id', $filteredInvoices->pluck('id')->toArray());

    // Paginate the filtered results
    $paginatedInvoices = $query->paginate(5);

    // Return with API Resource
    return new InvoiceResource(true, 'List Data Invoices', $paginatedInvoices);
}

    public function show($id)
    {
        $invoice = Invoice::with('orders.product', 'customer', 'city', 'province')->whereId($id)->first();

        if($invoice) {
            //return success with Api Resource
            return new InvoiceResource(true, 'Detail Data Invoice!', $invoice);
        }

        //return failed with Api Resource
        return new InvoiceResource(false, 'Detail Data Invoice Tidak Ditemukan!', null);
    }
}
