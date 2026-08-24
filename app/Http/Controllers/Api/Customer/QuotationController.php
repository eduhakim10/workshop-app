<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Support\ServicePresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class QuotationController extends Controller
{
    /**
     * List quotations for the authenticated customer.
     * Only rows with filled sr_number (keeps payload small for the portal).
     */
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->user()->customer_id;

        $items = Service::where('customer_id', $customerId)
            ->whereIn('stage', [1, 2])
            ->whereNotNull('sr_number')
            ->where('sr_number', '!=', '')
            ->with([
                'vehicle:id,brand,model,license_plate',
                'categoryService:id,name',
                'portalServiceStatus',
            ])
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
                'portalServiceStatus',
            ])
            ->findOrFail($id);

        $status = ServicePresenter::portalStatusBadge($s);
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
                'can_upload_po' => ServicePresenter::canUploadPo($s),
                'is_rejected' => ServicePresenter::isQuotationRejected($s),
                'has_po_file' => ServicePresenter::hasCustomerPoUpload($s),
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
                'po_date' => optional($s->po_date)->toDateString(),
                'po_file' => $s->po_file,
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
                'items' => $s->items_offer ?: $s->items,
            ],
        ]);
    }

    /**
     * Print quotation using the same workshop staff print blade (detail layout + signature).
     */
    public function print(Request $request, int $id): View|Response
    {
        $service = Service::where('customer_id', $request->user()->customer_id)
            ->whereIn('stage', [1, 2])
            ->with(['customer', 'vehicle', 'serviceRequest', 'preparedBy'])
            ->findOrFail($id);

        if (! $service->items_offer || (is_countable($service->items_offer) && count($service->items_offer) === 0)) {
            return response(
                '<!DOCTYPE html><html><body><p>Silakan isi item penawaran terlebih dahulu.</p></body></html>',
                422,
                ['Content-Type' => 'text/html; charset=UTF-8']
            );
        }

        return view('prints.quotation-detail', compact('service'));
    }

    public function uploadPo(Request $request, int $id): JsonResponse
    {
        $service = $this->findOwnedQuotation($request, $id);

        $data = $request->validate([
            'po_number' => ['required', 'string', 'max:100'],
            'po_date' => ['required', 'date'],
            'po_file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if (in_array($service->quotation_status, ['Rejected', 'Cancelled'], true)) {
            return response()->json(['message' => 'Penawaran yang ditolak tidak dapat diupload PO.'], 422);
        }

        if ($service->po_file && Storage::disk('public')->exists($service->po_file)) {
            Storage::disk('public')->delete($service->po_file);
        }

        $path = $request->file('po_file')->store('customer_po/' . $service->customer_id, 'public');

        // Upload PO = customer menyetujui penawaran
        $service->po_number = $data['po_number'];
        $service->po_date = $data['po_date'];
        $service->po_file = $path;
        $service->quotation_status = 'Accepted';
        $service->save();

        return response()->json([
            'message' => 'PO berhasil diupload. Penawaran disetujui.',
            'data' => $this->row($service->fresh(['vehicle', 'categoryService', 'portalServiceStatus'])),
        ]);
    }

    private function findOwnedQuotation(Request $request, int $id): Service
    {
        return Service::where('customer_id', $request->user()->customer_id)
            ->whereIn('stage', [1, 2])
            ->findOrFail($id);
    }

    private function row(Service $s): array
    {
        $status = ServicePresenter::portalStatusBadge($s);
        $vehicle = ServicePresenter::vehicleLabel($s);
        $date = $s->created_at_offer ? Carbon::parse($s->created_at_offer) : $s->created_at;
        $offer = (string) ($s->offer_number ?? '');
        $plate = (string) ($vehicle['license_plate'] ?? '');
        $name = (string) ($vehicle['name'] ?? '');

        return [
            'id' => $s->id,
            'offer_number' => $s->offer_number,
            'sr_number' => $s->sr_number,
            'vehicle' => $vehicle['name'],
            'license_plate' => $vehicle['license_plate'],
            'category' => optional($s->categoryService)->name
                ?: $s->damage_classification
                ?: '-',
            'date' => optional($date)->toDateString(),
            'amount' => (float) ($s->amount_offer_revision ?: $s->amount_offer),
            'status' => $status,
            'can_upload_po' => ServicePresenter::canUploadPo($s),
            'is_rejected' => ServicePresenter::isQuotationRejected($s),
            'has_po_file' => ServicePresenter::hasCustomerPoUpload($s),
            'stage' => (int) $s->stage,
            'po_number' => $s->po_number,
            'po_date' => $s->po_date ? Carbon::parse($s->po_date)->toDateString() : null,
            'search' => strtolower(trim($offer . ' ' . $name . ' ' . $plate . ' ' . ($s->sr_number ?? ''))),
        ];
    }
}
