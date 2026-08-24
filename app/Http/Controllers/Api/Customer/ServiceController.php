<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\ServicePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Before/after photos for a service (scoped to authenticated customer).
     */
    public function photos(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:before,after'],
        ]);

        $service = Service::where('customer_id', $request->user()->customer_id)
            ->where('stage', 2)
            ->findOrFail($id);

        $type = $request->query('type');

        return response()->json([
            'data' => ServicePresenter::servicePhotos($service, $type),
            'type' => $type,
            'service_id' => $service->id,
        ]);
    }
}
