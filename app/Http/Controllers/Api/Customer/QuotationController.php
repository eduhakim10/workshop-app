<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\ServicePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
            ->with([
                'vehicle',
                'customer',
                'categoryService',
                'serviceRequest',
                'preparedBy',
                'afterPhotos',
            ])
            ->findOrFail($id);

        $status = ServicePresenter::quotationStatus($s);
        $vehicle = ServicePresenter::vehicleLabel($s);
        $createdAt = $s->created_at_offer
            ? Carbon::parse($s->created_at_offer)
            : $s->created_at;
        $totals = ServicePresenter::quotationTotals($s);
        $lineItems = ServicePresenter::quotationLineItems($s);
        $sr = $s->serviceRequest;

        return response()->json([
            'data' => [
                'id' => $s->id,
                'offer_number' => $s->offer_number,
                'sr_number' => $s->sr_number ?: optional($sr)->sr_number,
                'attn' => $s->attn_quotation,
                'status' => $status,
                // backward-compatible alias
                'quotation_status' => $status,
                'vehicle' => $vehicle,
                'category' => optional($s->categoryService)->name
                    ?: $s->damage_classification
                    ?: '-',
                'created_at' => optional($createdAt)->toDateString(),
                'created_at_formatted' => $createdAt
                    ? $createdAt->translatedFormat('d M Y')
                    : '-',
                'amount' => $totals['grand_total'],
                'amount_offer' => (float) $s->amount_offer,
                'amount_offer_revision' => (float) $s->amount_offer_revision,
                'spk_number' => $s->spk_number,
                'po_number' => $s->po_number,
                'work_order_number' => $s->work_order_number,
                'stage' => (int) $s->stage,
                'notes' => $s->notes,
                'payment_terms' => $s->payment_terms,
                'delivery_terms' => $s->delivery_terms,
                'validity_terms' => $s->validity_terms,
                'damage_classification' => $s->damage_classification,
                'prepared_by' => optional($s->preparedBy)->name,
                'service' => [
                    'status' => $s->status,
                    'start_date' => $s->service_start_date,
                    'due_date' => $s->service_due_date,
                    'complaint' => optional($sr)->kerusakan ?: optional($sr)->notes,
                    'check_date' => $s->service_check_date,
                ],
                'customer' => [
                    'name' => optional($s->customer)->name,
                    'contact' => $s->attn_quotation ?: optional($s->customer)->name,
                    'phone' => optional($s->customer)->phone,
                    'email' => optional($s->customer)->email,
                    'address' => optional($s->customer)->address,
                ],
                'line_items' => $lineItems,
                'totals' => $totals,
                'timeline' => ServicePresenter::quotationTimeline($s),
                // raw fallback for older clients
                'items' => $s->items_offer ?: $s->items,
            ],
        ]);
    }

    private function row(Service $s): array
    {
        $status = ServicePresenter::quotationStatus($s);
        $vehicle = ServicePresenter::vehicleLabel($s);
        $date = $s->created_at_offer ? Carbon::parse($s->created_at_offer) : $s->created_at;

        return [
            'id' => $s->id,
            'offer_number' => $s->offer_number,
            'sr_number' => $s->sr_number,
            'vehicle' => $vehicle['name'],
            'license_plate' => $vehicle['license_plate'],
            'date' => optional($date)->toDateString(),
            'amount' => (float) ($s->amount_offer_revision ?: $s->amount_offer),
            'status' => $status,
            'stage' => (int) $s->stage,
            'po_number' => $s->po_number,
        ];
    }
}
