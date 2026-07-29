<?php

namespace App\Support;

use App\Helpers\QuotationPricing;
use App\Models\CategoryItem;
use App\Models\Item;
use App\Models\Service;
use App\Models\ServiceGroup;
use Illuminate\Support\Carbon;

/**
 * Maps internal Service/quotation state into the customer-facing labels used by
 * the customer portal (Indonesian), matching the prototype design.
 */
class ServicePresenter
{
    /**
     * Customer-facing quotation status.
     * Portal flow: Menunggu (belum ada PO) → PO Diupload (customer setuju via upload PO).
     * Tidak ada langkah approve/reject terpisah di portal.
     */
    public static function quotationStatus(Service $s): array
    {
        $status = (string) $s->quotation_status;

        if (in_array($status, ['Rejected', 'Cancelled'], true)) {
            return [
                'code' => 'ditolak',
                'label' => 'Ditolak',
                'color' => 'danger',
                'tab' => 'rejected',
            ];
        }

        if (! empty($s->po_number)) {
            return [
                'code' => 'po_diupload',
                'label' => 'PO Diupload',
                'color' => 'info',
                'tab' => 'approved',
            ];
        }

        return [
            'code' => 'menunggu',
            'label' => 'Menunggu',
            'color' => 'warning',
            'tab' => 'pending',
        ];
    }

    /**
     * Service repair progress as a 4-step flow (matches prototype):
     * 1 Kendaraan Diterima -> 2 Sedang Dikerjakan -> 3 Sudah Diperbaiki -> 4 Serah Terima.
     */
    public static function serviceProgress(Service $s): array
    {
        $status = (string) $s->status;
        $hasHandover = ! empty($s->handover_date) || ! empty($s->invoice_handover_date);
        $hasAfterPhotos = $s->relationLoaded('afterPhotos')
            ? $s->afterPhotos->isNotEmpty()
            : $s->afterPhotos()->exists();

        $step = 1;
        $label = 'Kendaraan Diterima';
        $badge = ['label' => 'Baru Masuk', 'color' => 'gray'];

        if ($hasHandover) {
            $step = 4;
            $label = 'Serah Terima';
            $badge = ['label' => 'Selesai', 'color' => 'success'];
        } elseif ($hasAfterPhotos || $status === 'Completed') {
            $step = 3;
            $label = 'Sudah Diperbaiki';
            $badge = ['label' => 'Siap Diambil', 'color' => 'info'];
        } elseif (in_array($status, ['In Progress', 'Pending Parts', 'On Hold'], true) || (int) $s->stage === 2) {
            $step = 2;
            $label = 'Sedang Dikerjakan';
            $badge = ['label' => 'Dikerjakan', 'color' => 'warning'];
        }

        return [
            'step' => $step,
            'step_label' => $label,
            'total_steps' => 4,
            'steps' => [
                ['no' => 1, 'label' => 'Kendaraan Diterima', 'done' => $step >= 1, 'active' => $step === 1],
                ['no' => 2, 'label' => 'Sedang Dikerjakan', 'done' => $step >= 2, 'active' => $step === 2],
                ['no' => 3, 'label' => 'Sudah Diperbaiki', 'done' => $step >= 3, 'active' => $step === 3],
                ['no' => 4, 'label' => 'Serah Terima', 'done' => $step >= 4, 'active' => $step === 4],
            ],
            'badge' => $badge,
        ];
    }

    public static function vehicleLabel(Service $s): array
    {
        $v = $s->vehicle;

        return [
            'name' => $v ? trim(($v->brand ?? '') . ' ' . ($v->model ?? '')) : '-',
            'license_plate' => $v->license_plate ?? '-',
            'brand' => $v->brand ?? null,
            'model' => $v->model ?? null,
            'type' => $v->type ?? null,
            'color' => $v->color ?? null,
            'chassis_number' => $v->chassis_number ?? null,
            'engine_type' => $v->engine_type ?? null,
            'karoseri' => $v->karoseri ?? null,
        ];
    }

    /**
     * Flatten items_offer groups into portal line_items.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function quotationLineItems(Service $s): array
    {
        $groups = $s->items_offer ?: [];
        if (! is_array($groups) || $groups === []) {
            return [];
        }

        $groupIds = collect($groups)->pluck('service_group_id')->filter()->unique()->values();
        $groupNames = ServiceGroup::whereIn('id', $groupIds)->pluck('name', 'id');

        $categoryIds = collect($groups)
            ->flatMap(fn ($g) => collect($g['items'] ?? [])->pluck('category_item_id'))
            ->filter()
            ->unique()
            ->values();
        $categoryNames = CategoryItem::whereIn('id', $categoryIds)->pluck('name', 'id');

        $itemIds = collect($groups)
            ->flatMap(fn ($g) => collect($g['items'] ?? [])->pluck('item_id'))
            ->filter()
            ->unique()
            ->values();
        $itemNames = Item::whereIn('id', $itemIds)->pluck('name', 'id');

        $lineItems = [];
        $no = 1;

        foreach ($groups as $group) {
            $groupName = $groupNames[$group['service_group_id'] ?? null]
                ?? ($group['name'] ?? 'Item');

            foreach (($group['items'] ?? []) as $item) {
                $line = QuotationPricing::calcLine($item);
                $discPct = (float) ($item['discount_percent'] ?? 0);
                $description = $categoryNames[$item['category_item_id'] ?? null]
                    ?? $itemNames[$item['item_id'] ?? null]
                    ?? ($item['name'] ?? $item['description'] ?? '-');

                $lineItems[] = [
                    'no' => $no++,
                    'group' => (string) $groupName,
                    'description' => (string) $description,
                    'detail' => $item['remarks'] ?? $item['note'] ?? $item['description'] ?? null,
                    'qty' => (float) ($item['quantity'] ?? $group['qty'] ?? 1),
                    'unit_price' => (float) ($item['sales_price'] ?? 0),
                    'discount' => $discPct > 0
                        ? rtrim(rtrim(number_format($discPct, 2, ',', '.'), '0'), ',') . '%'
                        : null,
                    'discount_percent' => $discPct,
                    'subtotal' => $line['subtotal'],
                ];
            }
        }

        return $lineItems;
    }

    public static function quotationTotals(Service $s): array
    {
        $groups = is_array($s->items_offer) ? $s->items_offer : [];
        $calc = QuotationPricing::calcFromGroups($groups, $s->ppn_type, $s->ppn_percent);
        $amount = (float) ($s->amount_offer_revision ?: $s->amount_offer ?: $calc['total']);

        return [
            'gross' => $calc['gross'],
            'subtotal' => $calc['subtotal'],
            'discount' => $calc['discount'],
            'tax' => $calc['ppn'],
            'ppn_percent' => $calc['ppn_percent'],
            'ppn_type' => $calc['ppn_type'],
            'grand_total' => $amount > 0 ? $amount : $calc['total'],
        ];
    }

    public static function quotationTimeline(Service $s): array
    {
        $status = self::quotationStatus($s);
        $created = self::formatDateTime($s->created_at_offer ?: $s->created_at);
        $approved = in_array($status['code'], ['disetujui', 'po_diupload'], true);
        $hasPo = ! empty($s->po_number);
        $inWorkshop = (int) $s->stage === 2;
        $hasAfter = $s->relationLoaded('afterPhotos')
            ? $s->afterPhotos->isNotEmpty()
            : $s->afterPhotos()->exists();
        $hasHandover = ! empty($s->handover_date) || ! empty($s->invoice_handover_date);

        $steps = [
            [
                'title' => 'Penawaran dibuat',
                'time' => $created,
                'note' => $s->offer_number ? 'No. ' . $s->offer_number : null,
                'state' => 'done',
            ],
            [
                'title' => 'Menunggu persetujuan customer',
                'time' => $status['code'] === 'menunggu' ? 'Menunggu' : $created,
                'note' => $status['code'] === 'menunggu' ? 'Silakan review & setujui penawaran' : null,
                'state' => $status['code'] === 'menunggu' ? 'active' : ($status['code'] === 'ditolak' ? 'done' : 'done'),
            ],
        ];

        if ($status['code'] === 'ditolak') {
            $steps[] = [
                'title' => 'Penawaran ditolak',
                'time' => '-',
                'note' => null,
                'state' => 'done',
            ];

            return $steps;
        }

        $steps[] = [
            'title' => 'Penawaran disetujui',
            'time' => $approved ? ($created ?: '-') : '-',
            'note' => null,
            'state' => $approved ? 'done' : 'pending',
        ];

        $steps[] = [
            'title' => 'PO diupload',
            'time' => $hasPo ? '-' : '-',
            'note' => $hasPo ? 'PO: ' . $s->po_number : 'Menunggu upload PO',
            'state' => $hasPo ? 'done' : ($approved ? 'active' : 'pending'),
        ];

        $steps[] = [
            'title' => 'Pengerjaan bengkel',
            'time' => $inWorkshop ? self::formatDateTime($s->service_start_date) : '-',
            'note' => $s->sr_number ? 'SR: ' . $s->sr_number : null,
            'state' => $hasAfter || $hasHandover ? 'done' : ($inWorkshop ? 'active' : 'pending'),
        ];

        $steps[] = [
            'title' => 'Selesai / serah terima',
            'time' => $hasHandover ? self::formatDateTime($s->handover_date ?: $s->invoice_handover_date) : '-',
            'note' => null,
            'state' => $hasHandover ? 'done' : ($hasAfter ? 'active' : 'pending'),
        ];

        return $steps;
    }

    private static function formatDateTime(mixed $value): string
    {
        if (empty($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->translatedFormat('d M Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
