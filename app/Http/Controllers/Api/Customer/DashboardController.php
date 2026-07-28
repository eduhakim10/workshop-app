<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\ServicePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard stats + active service progress, scoped to the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->user()->customer_id;

        $base = fn () => Service::where('customer_id', $customerId);

        // Sedang diperbaiki = job workshop (stage 2) yang foto after-nya belum diupload + punya SR
        $sedangDiperbaiki = (clone $base())
            ->where('stage', 2)
            ->whereNotNull('sr_number')
            ->where('sr_number', '!=', '')
            ->whereDoesntHave('afterPhotos')
            ->count();

        $sedangMenunggu = (clone $base())
            ->where('stage', 1)
            ->whereIn('quotation_status', ['Draft', 'Sent', 'Revised'])
            ->count();

        $penawaranBerlangsung = (clone $base())
            ->where('stage', 1)
            ->whereNotIn('quotation_status', ['Rejected', 'Cancelled'])
            ->count();

        // Sudah diperbaiki = stage 2 dan foto after sudah ada
        $sudahDiperbaiki = (clone $base())
            ->where('stage', 2)
            ->whereHas('afterPhotos')
            ->count();

        $totalPo = (clone $base())
            ->whereNotNull('po_number')
            ->where('po_number', '!=', '')
            ->count();

        $progress = (clone $base())
            ->where('stage', 2)
            ->with('vehicle')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(function (Service $s) {
                $vehicle = ServicePresenter::vehicleLabel($s);
                $progress = ServicePresenter::serviceProgress($s);

                return [
                    'id' => $s->id,
                    'ref' => $s->spk_number ?: $s->work_order_number ?: $s->offer_number,
                    'vehicle' => $vehicle['name'],
                    'license_plate' => $vehicle['license_plate'],
                    'category' => optional($s->categoryService)->name,
                    'progress' => $progress,
                ];
            });

        return response()->json([
            'stats' => [
                'sedang_diperbaiki' => $sedangDiperbaiki,
                'sedang_menunggu' => $sedangMenunggu,
                'penawaran_berlangsung' => $penawaranBerlangsung,
                'sudah_diperbaiki' => $sudahDiperbaiki,
                'total_po' => $totalPo,
            ],
            'services' => $progress,
        ]);
    }
}
