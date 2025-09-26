<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\Damage;
use App\Models\ServiceRequestPhoto;
use Illuminate\Support\Facades\Storage;

class ServiceRequestController extends Controller 
{
    /**
     * Display a listing of the resource.
     */
  public function index()
    {
        return response()->json(
            ServiceRequest::with('photos', 'customer')->latest()->get()
        );
    }

    public function damages()
    {
        return response()->json(
            Damage::latest()->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        // print_r($request->input());
        // die;
        $validated = $request->validate([
            // 'sr_number'    => 'required|string|unique:service_requests',
            'customer_id'  => 'required|exists:customers,id',
            'kerusakan'    => 'nullable|array',
            'kerusakan.*'  => 'exists:damages,id', // pastikan damage_id valid

            'notes'    =>       'nullable|string',
            'photos.*'     => 'nullable|image|max:2048', // multiple images
        ]);

       $serviceRequest = ServiceRequest::create(array_merge($validated, [
            'kerusakan'   => isset($validated['kerusakan'])
                    ? implode(', ', $validated['kerusakan'])
                    : null,
            'created_by' => auth()->id(), // isi otomatis user login
        ]));
         
        if ($request->has('kerusakan')) {
            foreach ($request->kerusakan as $damageId) {
                $serviceRequest->damages()->attach($damageId);
        }
    }
        // handle photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('service_requests', 'public');
           
                ServiceRequestPhoto::create([
                    'service_request_id' => $serviceRequest->id,
                    'file_path' => $path,
                    'type' => 'before', // default before
                ]);
            }
        }

        return response()->json($serviceRequest->load('photos'));
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        return response()->json($serviceRequest->load('photos', 'customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $validated = $request->validate([
            'sr_number'    => 'sometimes|string|unique:service_requests,sr_number,' . $serviceRequest->id,
            'customer_id'  => 'sometimes|exists:customers,id',
            'kerusakan'    => 'nullable|string',
        ]);

        $serviceRequest->update($validated);

        return response()->json($serviceRequest->load('photos', 'customer'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $serviceRequest = ServiceRequest::with('photos')->findOrFail($id);
    
            // cek apakah sr_number ada di services (offer_number)
            $exists = DB::table('services')
                    ->whereNotNull('status')
                    ->where('status', '<>', 'Draft')
                    ->exists();
    
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service request cannot be deleted because it is already linked with an offer number.',
                ], 400);
            }
    
            // hapus relasi damages (pivot)
            $serviceRequest->damages()->detach();
    
            // hapus foto di storage + DB
            foreach ($serviceRequest->photos as $photo) {
                if (\Storage::disk('public')->exists($photo->file_path)) {
                    \Storage::disk('public')->delete($photo->file_path);
                }
                $photo->delete();
            }
    
            // hapus service request
            $serviceRequest->delete();
    
            return response()->json([
                'success' => true,
                'message' => 'Service request deleted successfully',
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    
}
