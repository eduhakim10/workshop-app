<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\Damage;
use App\Models\ServiceRequestPhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



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
        $request->merge([
            'kerusakan' => array_filter($request->kerusakan ?? [], fn($v) => !empty($v))
        ]);
        
        $validated = $request->validate([
            // 'sr_number'    => 'required|string|unique:service_requests',
            'customer_id'  => 'required|exists:customers,id',
            'kerusakan'    => 'nullable|array',
            'kerusakan.*'  => 'exists:damages,id', // pastikan damage_id valid
            'vehicle_id' => 'required|exists:vehicles,id',
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
    public function show($id)
    {
        $serviceRequest = ServiceRequest::with(['customer', 'vehicle', 'damages','photos'])
            ->find($id);

        if (!$serviceRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Service Request not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $serviceRequest
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // print_r($request->customer_id);
        // die;
        // echo $id;die;
        // // dd($request->all());
        \Log::info('Update ServiceRequest payload', $request->all());
        \Log::info('Update ServiceRequest payload: ' . json_encode($request->all()));
        // die;
        $request->merge([
            'kerusakan' => array_filter($request->kerusakan ?? [], fn($v) => !empty($v))
        ]);
        $serviceRequest = ServiceRequest::findOrFail($id);
    
        // 🔹 Validasi (optional, bisa lu sesuaikan)
        $validated = $request->validate([
            'jenis' => 'nullable|string',
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'kerusakan' => 'array',
            'kerusakan.*' => 'exists:damages,id',
            'notes' => 'nullable|string',
            'photos.*' => 'image|max:2048', // 2MB max
        ]);
    
        // 🔹 Update field basic
        $serviceRequest->customer_id = $request->customer_id;
        $serviceRequest->vehicle_id = $request->vehicle_id;
        $serviceRequest->notes = $request->notes ?? null;
    
        // 🔹 Mapping jenis ke status / flag lain (optional)
        // if ($request->filled('jenis')) {
        //     $serviceRequest->jenis = $request->jenis;
        // }
    
        $serviceRequest->save();
    
        // 🔹 Update kerusakan (pivot table)
        if ($request->has('kerusakan')) {
            $serviceRequest->damages()->sync($request->kerusakan);
        }
    
        // 🔹 Upload & simpan foto baru
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('service_requests', 'public');
    
                $serviceRequest->photos()->create([
                    'type' => 'before', // atau from request
                    'file_path' => $path,
                ]);
            }
        }

        if ($request->has('deleted_photos')) {
            foreach ($request->deleted_photos as $photoId) {
                $photo = ServiceRequestPhoto::find($photoId);
                if ($photo) {
                    Storage::disk('public')->delete($photo->file_path);
                    $photo->delete();
                }
            }
        }
        
    
        return response()->json([
            'success' => true,
            'message' => 'Service Request updated successfully',
            'data' => $serviceRequest->load(['customer', 'vehicle', 'damages', 'photos']),
        ]);
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
                    ->where('service_request_id', $id) // cek service terkait request yang mau dihapus
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
