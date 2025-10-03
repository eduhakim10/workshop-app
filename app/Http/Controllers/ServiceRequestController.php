<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    /**
     * Tampilkan detail Service Request beserta relasi-relasinya.
     */
    public function show($id)
    {
        $serviceRequest = ServiceRequest::with([
            'customer',
            'vehicle',
            'damages',      // relasi ke services_request_damages
            'photos',       // relasi ke service_request_photos
        ])->findOrFail($id);
        // echo '<pre>';
        // print_r(compact('serviceRequest'));
        // die;
        return view('service-requests.show', compact('serviceRequest'));
    }
}
