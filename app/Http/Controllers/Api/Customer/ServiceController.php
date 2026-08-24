<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Support\ServicePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Before/after photos JSON (legacy / optional).
     */
    public function photos(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:before,after'],
        ]);

        $service = $this->findOwnedService($request, $id);
        $type = $request->query('type');

        return response()->json([
            'data' => ServicePresenter::servicePhotos($service, $type),
            'type' => $type,
            'service_id' => $service->id,
        ]);
    }

    /**
     * Same HTML layout as workshop staff Service Request before/after pages.
     */
    public function photosPage(Request $request, int $id): View
    {
        $request->validate([
            'type' => ['required', 'in:before,after'],
        ]);

        $service = $this->findOwnedService($request, $id);

        if (! $service->service_request_id) {
            abort(404, 'Service request tidak ditemukan.');
        }

        $type = $request->query('type');
        $damageType = $type;

        $serviceRequest = ServiceRequest::with([
            'customer',
            'vehicle',
            'creator',
            'damages' => fn ($q) => $q->where('services_request_damages.type', $damageType),
            'photos' => fn ($q) => $q->where('type', $type),
        ])->findOrFail($service->service_request_id);

        abort_unless(
            (int) $serviceRequest->customer_id === (int) $request->user()->customer_id,
            403
        );

        // Blade expects createdBy; model relation is creator.
        $serviceRequest->setRelation('createdBy', $serviceRequest->creator);

        return $type === 'before'
            ? view('service-requests.show', compact('serviceRequest'))
            : view('service-requests.show_after', compact('serviceRequest'));
    }

    private function findOwnedService(Request $request, int $id): Service
    {
        return Service::where('customer_id', $request->user()->customer_id)
            ->where('stage', 2)
            ->findOrFail($id);
    }
}
