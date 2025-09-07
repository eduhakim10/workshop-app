<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ServicePhoto;
use App\Models\ServiceRequestPhoto;
use Illuminate\Support\Facades\Storage;

class ServicePhotoController extends Controller
{

    public function index(Service $service)
    {
        $photos = ServicePhoto::where('service_id', $service->id)
            ->with('uploader:id,name') // kalau kamu ingin info siapa yang upload
            ->get();

        return response()->json([
            'data' => $photos,
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'nullable|integer', 
            'kerusakan_after' => 'nullable|array',      // array of strings
            'kerusakan_after.*' => 'string|max:255',
            'photos.*' => 'nullable|image|max:2048',   // multiple images
        ]);
        // $photos = [];
        // $path = $request->file('image')->store('service_photos', 'public');
            $service = Service::findOrFail($validated['service_id']);
// Simpan kerusakan_after (json array)
        if ($request->has('kerusakan_after')) {
            $service->kerusakan_after = $validated['kerusakan_after'];
            $service->save();
        }
         // Upload photos after
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('service_photos', 'public');

                ServiceRequestPhoto::create([
                 //   'service_request_id' => $service->service_request_id, //ini nanti di uncoment pas udah testing
                    'service_request_id' => 1, // ini di comment pas testing ya
                    'file_path' => $path,
                    'type' => 'after',
                ]);
            }
        }
 
    
       return response()->json([
            'message' => 'Service updated with after repair data',
            'service' => $service->load('photosAfter'),
        ]);
    }
}
