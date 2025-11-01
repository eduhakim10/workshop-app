<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceRequestPhoto;
use App\Models\ServicesRequestDamage;
use Illuminate\Support\Facades\Log;


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
    public function updateAfter(Request $request, $id)
    {
        Log::info('[updateAfter] Incoming Request:', [
            'id' => $id,
            'payload' => $request->all(),
        ]);
        // Validasi input
        $request->validate([
            'notes_after' => 'nullable|string',
            'after_photos.*' => 'nullable|image|max:5120', // max 5MB per file
            'after_damages' => 'nullable|array',
            'after_damages.*.damage_id' => 'required|integer|exists:damages,id',
        ]);

        DB::beginTransaction();
        try {
            $service = Service::findOrFail($id);

            // 1️⃣ Update notes_after
            $service->service_check_date = $request->service_check_date;
            $service->notes_after = $request->notes_after;
            $service->save();

            // 2️⃣ Upload foto after & insert ke service_request_photos
            if ($request->hasFile('after_photos')) {
                foreach ($request->file('after_photos') as $file) {
                    $path = $file->store('after_photos', 'public');

                    ServiceRequestPhoto::create([
                        'service_request_id' => $service->service_request_id,
                        'file_path' => $path,
                        'type' => 'after',
                    ]);
                }
            }

            // 3️⃣ Insert kerusakan after ke services_request_damages
            if ($request->filled('after_damages')) {
                foreach ($request->after_damages as $damage) {
                    ServicesRequestDamage::create([
                        'service_request_id' => $service->service_request_id,
                        'damage_id' => $damage['damage_id'],
                        'damage_name' => $damage['damage_name'] ?? null,
                        'type' => 'after',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data After berhasil diupdate',
                'after_photos' => ServiceRequestPhoto::where('service_request_id', $service->service_request_id)
                    ->where('type', 'after')
                    ->get(),
                'after_damages' => ServicesRequestDamage::where('service_request_id', $service->service_request_id)
                    ->where('type', 'after')
                    ->get(),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal update data After',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show($id)
    {
        $service = Service::with([
            'customer',
            'vehicle',
            'afterPhotos',
            'beforePhotos',
            'beforedamages.damage',
            'afterdamages.damage',
            'assignTo',
            'serviceRequest'
        ])->findOrFail($id);
        

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }
}
