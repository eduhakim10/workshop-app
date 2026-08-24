<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\SettingService;
use App\Support\ServicePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard stats + widgets, scoped to the authenticated customer.
     *
     * Query:
     * - from / to (Y-m-d) optional. If omitted, workshop-app portal setting is used.
     */
    public function index(Request $request, SettingService $settings): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $period = $settings->resolveDashboardPeriod(
            $request->query('from'),
            $request->query('to'),
        );

        $customerId = $request->user()->customer_id;
        $from = $period['from'];
        $to = $period['to'];

        $base = fn () => Service::where('customer_id', $customerId)
            ->inPortalPeriod($from, $to);

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

        // Penawaran berlangsung = quotation (stage 1), SR sudah ada, customer belum upload PO
        $penawaranBerlangsung = (clone $base())
            ->where('stage', 1)
            ->whereNotNull('sr_number')
            ->where('sr_number', '!=', '')
            ->where(function ($q) {
                $q->whereNull('po_file')->orWhere('po_file', '');
            })
            ->count();

        // Sudah diperbaiki = sama seperti sedang diperbaiki, tapi foto after sudah diupload
        $sudahDiperbaiki = (clone $base())
            ->where('stage', 2)
            ->whereNotNull('sr_number')
            ->where('sr_number', '!=', '')
            ->whereHas('afterPhotos')
            ->count();

        $totalPo = (clone $base())
            ->whereNotNull('po_file')
            ->where('po_file', '!=', '')
            ->count();

        $progress = (clone $base())
            ->where('stage', 2)
            ->with(['vehicle', 'beforePhotos', 'afterPhotos', 'portalServiceStatus'])
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

        $quotations = (clone $base())
            ->whereIn('stage', [1, 2])
            ->whereNotNull('sr_number')
            ->where('sr_number', '!=', '')
            ->with(['vehicle:id,brand,model,license_plate', 'portalServiceStatus'])
            ->orderByDesc('created_at_offer')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(fn (Service $s) => ServicePresenter::quotationDashboardRow($s));

        return response()->json([
            'stats' => [
                'sedang_diperbaiki' => $sedangDiperbaiki,
                'sedang_menunggu' => $sedangMenunggu,
                'penawaran_berlangsung' => $penawaranBerlangsung,
                'sudah_diperbaiki' => $sudahDiperbaiki,
                'total_po' => $totalPo,
            ],
            'services' => $progress,
            'quotations' => $quotations,
            'period' => $period,
        ]);
    }
}
