<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Vehicle;

class CustomerController extends Controller
{
    public function index()
    {
        // Ambil semua customer
        $customers = Customer::select('id', 'name', 'email', 'phone', 'address')->get();

        return response()->json($customers);
    }

    public function vehicles($id)
    {
        $vehicles = Vehicle::where('customer_id', $id)
                    ->select('id', 'license_plate')
                    ->get();

        return response()->json($vehicles);
}

    
}
