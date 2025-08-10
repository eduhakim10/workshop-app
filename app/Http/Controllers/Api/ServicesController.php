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

        // Misalnya: hanya tampilkan SPK yang status-nya 'Scheduled' dan 'In Progress'
        // atau yang terkait sama employee_id
        $services = Service::with(['vehicle', 'customer'])
            ->whereIn('status', ['Scheduled', 'In Progress'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $services
        ]);
    }

    // Ambil detail SPK tertentu
    public function show(Service $service)
    {
        return response()->json([
            'data' => $service->load(['vehicle', 'customer']),
        ]);
    }
}
