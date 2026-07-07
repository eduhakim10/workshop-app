<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\ServicePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    /**
     * List quotations (Service stage 1 & 2) for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->user()->customer_id;

        $items = Service::where('customer_id', $customerId)
            ->whereIn('stage', [1, 2])
            ->with('vehicle')
            ->orderByDesc('created_at_offer')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Service $s) => $this->row($s));

        return response()->json(['data' => $items]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customerId = $request->user()->customer_id;

        $s = Service::where('customer_id', $customerId)
            ->whereIn('stage', [1, 2])
            ->with(['vehicle', 'customer'])
            ->findOrFail($id);

        $status = ServicePresenter::quotationStatus($s);
        $vehicle = ServicePresenter::vehicleLabel($s);

        return response()->json([
            'data' => [
                'id' => $s->id,
                'offer_number' => $s->offer_number,
                'attn' => $s->attn_quotation,
                'vehicle' => $vehicle,
                'amount_offer' => $s->amount_offer,
                'amount_offer_revision' => $s->amount_offer_revision,
                'quotation_status' => $status,
                'po_number' => $s->po_number,
                'spk_number' => $s->spk_number,
                'stage' => (int) $s->stage,
                'payment_terms' => $s->payment_terms,
                'delivery_terms' => $s->delivery_terms,
                'validity_terms' => $s->validity_terms,
                'notes' => $s->notes,
                'items' => $s->items_offer ?: $s->items,
                'created_at' => optional($s->created_at_offer ? \Illuminate\Support\Carbon::parse($s->created_at_offer) : $s->created_at)->toDateString(),
            ],
        ]);
    }

    private function row(Service $s): array
    {
        $status = ServicePresenter::quotationStatus($s);
        $vehicle = ServicePresenter::vehicleLabel($s);
        $date = $s->created_at_offer ? \Illuminate\Support\Carbon::parse($s->created_at_offer) : $s->created_at;

        return [
            'id' => $s->id,
            'offer_number' => $s->offer_number,
            'vehicle' => $vehicle['name'],
            'license_plate' => $vehicle['license_plate'],
            'date' => optional($date)->toDateString(),
            'amount' => (float) ($s->amount_offer_revision ?: $s->amount_offer),
            'status' => $status,
            'stage' => (int) $s->stage,
        ];
    }
}
