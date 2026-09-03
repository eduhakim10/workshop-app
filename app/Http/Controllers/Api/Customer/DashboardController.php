<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\SettingService;
use App\Support\PortalDashboardBuckets;
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

        $stats = PortalDashboardBuckets::counts($customerId, $from, $to);

        $base = fn () => Service::where('customer_id', $customerId)
            ->inPortalPeriod($from, $to);

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
            'stats' => $stats,
            'services' => $progress,
            'quotations' => $quotations,
            'period' => $period,
        ]);
    }
}
