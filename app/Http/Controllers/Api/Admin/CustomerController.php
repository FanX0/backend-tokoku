<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    public function index()
    {
        // Get all customers
        $customers = Customer::all();

        // Check if there is a search query
        $searchQuery = request()->q;

        // Sequential search
        if ($searchQuery) {
            $filteredCustomers = $customers->filter(function($customer) use ($searchQuery) {
                return stripos($customer->name, $searchQuery) !== false;
            });
        } else {
            $filteredCustomers = $customers;
        }

        // Convert filtered collection to a query builder
        $query = Customer::whereIn('id', $filteredCustomers->pluck('id')->toArray());

        // Paginate the filtered results
        $paginatedCustomers = $query->paginate(5);

        // Return with API Resource
        return new CustomerResource(true, 'List Data Customer', $paginatedCustomers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:customers',
            'password' => 'required|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Create customer
        $customer = Customer::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($customer) {
            // Return with API Resource
            return new CustomerResource(true, 'Register Customer Berhasil', $customer);
        }

        // Return failed with API Resource
        return new CustomerResource(false, 'Register Customer Gagal!', null);
    }

    public function show($id)
    {
        $customer = Customer::whereId($id)->first();

        if ($customer) {
            // Return success with API Resource
            return new CustomerResource(true, 'Detail Data Customer!', $customer);
        }

        // Return failed with API Resource
        return new CustomerResource(false, 'Detail Data Customer Tidak Ditemukan!', null);
    }

    public function update(Request $request, Customer $customer)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'string|max:255',
            'email'    => 'unique:customers,email,'.$customer->id,
            'password' => 'sometimes|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Update customer
        $customer->update([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->filled('password') ? Hash::make($request->password) : $customer->password,
        ]);

        if ($customer) {
            // Return success with API Resource
            return new CustomerResource(true, 'Data Customer Berhasil Diupdate!', $customer);
        }

        // Return failed with API Resource
        return new CustomerResource(false, 'Data Customer Gagal Diupdate!', null);
    }

    public function destroy(Customer $customer)
    {
        if ($customer->delete()) {
            // Return success with API Resource
            return new CustomerResource(true, 'Data Customer Berhasil Dihapus!', null);
        }

        // Return failed with API Resource
        return new CustomerResource(false, 'Data Customer Gagal Dihapus!', null);
    }
}
