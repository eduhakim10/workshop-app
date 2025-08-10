<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServicePhoto;
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
            'service_id' => 'required|exists:services,id',
            'type' => 'required|in:before,after',
            'image' => 'required|image|max:2048',
            'spk_number' => 'nullable|string',
            'damage' => 'nullable|string',
        ]);

        $path = $request->file('image')->store('service_photos', 'public');

        $photo = ServicePhoto::create([
            'service_id' => $validated['service_id'],
            'type' => $validated['type'],
            'image_path' => $path,
            'spk_number' => $validated['spk_number'] ?? null,
            'uploaded_by' => auth()->id(),
            'damage' => $validated['damage'] ?? null,
        ]);

        return response()->json([
            'message' => 'Photo uploaded successfully test',
            'data' => $photo
        ]);
    }
}
