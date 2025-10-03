<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    // Ambil semua SPK / services
    public function index(Request $request)
    {
        // Ambil user yang login
        $user = $request->user();

        // atau yang terkait sama employee_id
            $services = Service::with(['vehicle', 'customer'])
            ->whereIn('status', ['Scheduled', 'In Progress'])
            ->whereNotNull('work_order_number')
            ->orderBy('created_at', 'desc')
            ->get();
    

        return response()->json([
            'data' => $services
        ]);
    }

    // Ambil detail SPK tertentu
    // public function show(Service $service)
    // {
    //     return response()->json([
    //         'data' => $service->load(['vehicle', 'customer']),
    //     ]);
    // }
    public function show($id)
    {
        $service = Service::with(['customer', 'vehicle', 'afterPhotos', 'damages'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }
}
