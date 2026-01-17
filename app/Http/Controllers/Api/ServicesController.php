<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ServiceRequestPhoto;
use App\Models\ServicesRequestDamage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


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
            'files' => $request->hasFile('after_photos') ? 'Yes' : 'No',
            'deleted_ids' => $request->deleted_photo_ids ?? 'None',
        ]);
        // Validasi input
        $request->validate([
            'notes_after' => 'nullable|string',
            'after_photos.*' => 'nullable|image|max:5120', // max 5MB per file
            'after_damages' => 'nullable|array',
            'after_damages.*.damage_id' => 'required|integer|exists:damages,id',
            'deleted_photo_ids' => 'nullable|array',
            'deleted_photo_ids.*' => 'integer',
            'deleted_after_damage_ids' => 'nullable|array',
            'deleted_after_damage_ids.*' => 'integer',
        ]);

        DB::beginTransaction();
        try {
            $service = Service::findOrFail($id);

            // 1️⃣ Update notes_after
            $service->service_check_date = $request->service_check_date;
            $service->notes_after = $request->notes_after;
            $service->save();

            // 2️⃣ Hapus foto yang di-request (deleted_photo_ids)
            if ($request->filled('deleted_photo_ids')) {
                Log::info('[updateAfter] Deleting photos:', $request->deleted_photo_ids);
                
                foreach ($request->deleted_photo_ids as $photoId) {
                    $photo = ServiceRequestPhoto::find($photoId);
                    if ($photo && $photo->service_request_id == $service->service_request_id) {
                        // Hapus file dari storage
                        if (Storage::disk('public')->exists($photo->file_path)) {
                            Storage::disk('public')->delete($photo->file_path);
                        }
                        // Hapus record dari database
                        $photo->delete();
                        Log::info('[updateAfter] Deleted photo ID:', ['id' => $photoId]);
                    }
                }
            }

            // 2.5️⃣ Hapus afterdamages yang di-request (deleted_after_damage_ids)
            if ($request->filled('deleted_after_damage_ids')) {
                Log::info('[updateAfter] Deleting after damages:', $request->deleted_after_damage_ids);
                
                foreach ($request->deleted_after_damage_ids as $damageId) {
                    $damage = ServicesRequestDamage::find($damageId);
                    if ($damage && $damage->service_request_id == $service->service_request_id && $damage->type === 'after') {
                        $damage->delete();
                        Log::info('[updateAfter] Deleted after damage ID:', ['id' => $damageId]);
                    }
                }
            }

            // 3️⃣ Upload foto after baru & insert ke service_request_photos
            if ($request->hasFile('after_photos')) {
                Log::info('[updateAfter] Uploading new photos');
                foreach ($request->file('after_photos') as $file) {
                    $path = $file->store('after_photos', 'public');

                    ServiceRequestPhoto::create([
                        'service_request_id' => $service->service_request_id,
                        'file_path' => $path,
                        'type' => 'after',
                    ]);
                }
            }

            // 4️⃣ Insert kerusakan after ke services_request_damages
            if ($request->filled('after_damages')) {
                Log::info('[updateAfter] Saving damages (deduplicating)...', $request->after_damages);

                // Deduplicate by damage_id to avoid unique constraint violation
                $damageItems = collect($request->after_damages)
                    ->filter(function ($d) {
                        return isset($d['damage_id']) && is_numeric($d['damage_id']);
                    })
                    ->unique('damage_id')
                    ->values();

                foreach ($damageItems as $damage) {
                    $damageId = (int) $damage['damage_id'];
                    $damageName = $damage['damage_name'] ?? null;

                    // Use firstOrCreate to safely avoid duplicate inserts
                    $existing = ServicesRequestDamage::firstOrCreate(
                        [
                            'service_request_id' => $service->service_request_id,
                            'damage_id' => $damageId,
                        ],
                        [
                            'damage_name' => $damageName,
                            'type' => 'after',
                        ]
                    );

                    // If record already existed but damage_name changed, update it
                    if ($existing && $existing->wasRecentlyCreated === false && $damageName && $existing->damage_name !== $damageName) {
                        $existing->update(['damage_name' => $damageName]);
                    }
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
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[updateAfter] Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
