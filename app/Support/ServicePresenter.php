<?php

namespace App\Support;

use App\Helpers\QuotationPricing;
use App\Models\CategoryItem;
use App\Models\Item;
use App\Models\PortalServiceStatus;
use App\Models\Service;
use App\Models\ServiceGroup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Maps internal Service/quotation state into the customer-facing labels used by
 * the customer portal (Indonesian), matching the prototype design.
 */
class ServicePresenter
{
    /**
     * Customer-facing quotation status.
     * Portal flow: Menunggu → PO Diupload (hanya jika customer upload file PO via portal).
     * Nomor PO yang diisi staff saja TIDAK mengaktifkan status PO Diupload.
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

        if (self::hasCustomerPoUpload($s)) {
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

    /** True only when customer uploaded a PO file through the portal. */
    public static function hasCustomerPoUpload(Service $s): bool
    {
        return filled($s->po_file);
    }

    public static function isQuotationRejected(Service $s): bool
    {
        return in_array((string) $s->quotation_status, ['Rejected', 'Cancelled'], true);
    }

    public static function canUploadPo(Service $s): bool
    {
        return ! self::isQuotationRejected($s) && ! self::hasCustomerPoUpload($s);
    }

    /**
     * Badge for quotation/service rows — sourced from portal_service_statuses master.
     *
     * @return array{code: string, label: string, color: string, sort_order: int}
     */
    public static function portalStatusBadge(Service $s): array
    {
        $masters = PortalServiceStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $current = $s->relationLoaded('portalServiceStatus')
            ? $s->portalServiceStatus
            : $s->portalServiceStatus()->first();

        if (! $current) {
            $current = self::inferPortalStatus($s, $masters);
        }

        return [
            'code' => $current?->code ?? 'kendaraan_diterima',
            'label' => $current?->name ?? 'Kendaraan Diterima',
            'color' => $current?->badge_color ?? 'gray',
            'sort_order' => (int) ($current?->sort_order ?? 1),
        ];
    }

    /**
     * Customer portal service progress from master portal_service_statuses.
     * Admin sets portal_service_status_id on the Services form (Dikerjakan / Finishgood).
     * Antrian is auto-set on Move to Service; Kendaraan Diterima may be auto-set from SR/photos.
     */
    public static function serviceProgress(Service $s): array
    {
        $masters = PortalServiceStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $current = $s->relationLoaded('portalServiceStatus')
            ? $s->portalServiceStatus
            : $s->portalServiceStatus()->first();

        // Fallback for legacy rows without portal_service_status_id
        if (! $current) {
            $current = self::inferPortalStatus($s, $masters);
        }

        $step = (int) ($current?->sort_order ?? 1);
        $label = $current?->name ?? 'Kendaraan Diterima';
        $badge = [
            'label' => $label,
            'color' => $current?->badge_color ?? 'gray',
        ];

        $beforeCount = self::photoCount($s, 'before');
        $afterCount = self::photoCount($s, 'after');
        $maxStep = (int) ($masters->max('sort_order') ?: 1);

        $steps = $masters->map(function (PortalServiceStatus $master) use ($step, $beforeCount, $afterCount, $maxStep) {
            $order = (int) $master->sort_order;
            $action = $master->clickable_action;
            $photoCount = match ($action) {
                'before_photos' => $beforeCount,
                'after_photos' => $afterCount,
                default => 0,
            };
            $clickable = filled($action) && $photoCount > 0;
            $isFinal = $order === $maxStep;

            return [
                'no' => $order,
                'label' => $master->name,
                'done' => $step > $order || ($isFinal && $step >= $order),
                'active' => $step === $order && ! $isFinal,
                'clickable' => $clickable,
                'action' => $clickable ? $action : null,
                'photo_count' => $photoCount,
            ];
        })->values()->all();

        return [
            'step' => $step,
            'step_label' => $label,
            'total_steps' => max(count($steps), 1),
            'steps' => $steps,
            'badge' => $badge,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PortalServiceStatus>  $masters
     */
    private static function inferPortalStatus(Service $s, $masters): ?PortalServiceStatus
    {
        $byCode = $masters->keyBy('code');

        if (self::hasPhotos($s, 'after')) {
            return $byCode->get('finishgood');
        }

        if ((int) $s->stage === 2) {
            return $byCode->get('antrian');
        }

        if (self::hasPhotos($s, 'before') || ! empty($s->service_request_id)) {
            return $byCode->get('kendaraan_diterima');
        }

        return $byCode->get('kendaraan_diterima') ?? $masters->first();
    }

    /**
     * @return array<int, array{id:int,url:string}>
     */
    public static function servicePhotos(Service $s, string $type): array
    {
        if (! in_array($type, ['before', 'after'], true)) {
            return [];
        }

        $relation = $type === 'before' ? 'beforePhotos' : 'afterPhotos';

        if ($s->relationLoaded($relation)) {
            $photos = $s->{$relation};
        } else {
            $photos = $s->{$relation}()->get();
        }

        return $photos->map(fn ($photo) => [
            'id' => $photo->id,
            'url' => self::photoUrl($photo->file_path),
        ])->values()->all();
    }

    private static function hasPhotos(Service $s, string $type): bool
    {
        return self::photoCount($s, $type) > 0;
    }

    private static function photoCount(Service $s, string $type): int
    {
        $relation = $type === 'before' ? 'beforePhotos' : 'afterPhotos';

        if ($s->relationLoaded($relation)) {
            return $s->{$relation}->count();
        }

        return $s->{$relation}()->count();
    }

    private static function photoUrl(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        return url(Storage::url($normalized));
    }

    /**
     * Compact quotation row for the portal dashboard widget.
     *
     * @return array<string, mixed>
     */
    public static function quotationDashboardRow(Service $s): array
    {
        $status = self::portalStatusBadge($s);
        $vehicle = self::vehicleLabel($s);
        $date = $s->created_at_offer ? Carbon::parse($s->created_at_offer) : $s->created_at;

        return [
            'id' => $s->id,
            'offer_number' => $s->offer_number,
            'vehicle' => $vehicle['name'],
            'license_plate' => $vehicle['license_plate'],
            'date' => optional($date)->toDateString(),
            'amount' => (float) ($s->amount_offer_revision ?: $s->amount_offer),
            'status' => $status,
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

    /**
     * Riwayat status di detail penawaran (tanpa alur PO):
     * 1 Kendaraan Diterima (waktu upload foto before)
     * 2 Penawaran dibuat
     * 3 Antrian → 4 Dikerjakan → 5 Finishgood
     *
     * @return array<int, array{title: string, time: string, note: ?string, state: string}>
     */
    public static function quotationTimeline(Service $s): array
    {
        $masters = PortalServiceStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('code');

        $current = $s->relationLoaded('portalServiceStatus')
            ? $s->portalServiceStatus
            : $s->portalServiceStatus()->first();

        if (! $current) {
            $current = self::inferPortalStatus($s, $masters->values());
        }

        $currentCode = $current?->code;
        $currentOrder = (int) ($current?->sort_order ?? 0);

        $beforeUploadedAt = self::beforePhotoUploadedAt($s);
        $hasBefore = $beforeUploadedAt !== null
            || self::hasPhotos($s, 'before')
            || ! empty($s->service_request_id);

        $offerCreated = self::formatDateTime($s->created_at_offer ?: $s->created_at);
        $inQueueOrLater = $currentOrder >= (int) ($masters->get('antrian')?->sort_order ?? 2)
            || (int) $s->stage === 2;

        // Explicit flow requested by product (not raw master sort order).
        $flow = [
            [
                'code' => 'kendaraan_diterima',
                'title' => $masters->get('kendaraan_diterima')?->name ?? 'Kendaraan Diterima',
                'time' => $beforeUploadedAt ? self::formatDateTime($beforeUploadedAt) : '-',
                'note' => $hasBefore ? 'Foto before diupload teknisi' : null,
                'reached' => $hasBefore || $currentOrder >= 1,
            ],
            [
                'code' => 'penawaran_dibuat',
                'title' => 'Penawaran dibuat',
                'time' => $offerCreated,
                'note' => $s->offer_number ? 'No. ' . $s->offer_number : null,
                'reached' => filled($s->offer_number) || filled($s->created_at_offer) || filled($s->created_at),
            ],
            [
                'code' => 'antrian',
                'title' => $masters->get('antrian')?->name ?? 'Antrian',
                'time' => $inQueueOrLater ? self::formatDateTime($s->service_start_date) : '-',
                'note' => $inQueueOrLater && $s->sr_number ? 'SR: ' . $s->sr_number : null,
                'reached' => $inQueueOrLater,
            ],
            [
                'code' => 'dikerjakan',
                'title' => $masters->get('dikerjakan')?->name ?? 'Dikerjakan',
                'time' => $currentOrder >= (int) ($masters->get('dikerjakan')?->sort_order ?? 3)
                    ? self::formatDateTime($s->updated_at)
                    : '-',
                'note' => null,
                'reached' => $currentOrder >= (int) ($masters->get('dikerjakan')?->sort_order ?? 3),
            ],
            [
                'code' => 'finishgood',
                'title' => $masters->get('finishgood')?->name ?? 'Finishgood',
                'time' => $currentOrder >= (int) ($masters->get('finishgood')?->sort_order ?? 4)
                    ? self::formatDateTime($s->handover_date ?: $s->invoice_handover_date ?: $s->updated_at)
                    : '-',
                'note' => null,
                'reached' => $currentOrder >= (int) ($masters->get('finishgood')?->sort_order ?? 4)
                    || $currentCode === 'finishgood',
            ],
        ];

        if (self::isQuotationRejected($s)) {
            return [
                [
                    'title' => $flow[0]['title'],
                    'time' => $flow[0]['time'],
                    'note' => $flow[0]['note'],
                    'state' => $flow[0]['reached'] ? 'done' : 'pending',
                ],
                [
                    'title' => 'Penawaran dibuat',
                    'time' => $offerCreated,
                    'note' => $s->offer_number ? 'No. ' . $s->offer_number : null,
                    'state' => 'done',
                ],
                [
                    'title' => 'Penawaran ditolak',
                    'time' => '-',
                    'note' => null,
                    'state' => 'done',
                ],
            ];
        }

        $activeIndex = null;
        foreach ($flow as $i => $step) {
            if ($step['reached']) {
                $activeIndex = $i;
            }
        }

        $steps = [];
        foreach ($flow as $i => $step) {
            if (! $step['reached']) {
                $state = 'pending';
            } elseif ($step['code'] === 'finishgood') {
                $state = 'done';
            } elseif ($i === $activeIndex) {
                $state = 'active';
            } else {
                $state = 'done';
            }

            $steps[] = [
                'title' => $step['title'],
                'time' => $step['time'] ?: '-',
                'note' => $step['note'],
                'state' => $state,
            ];
        }

        return $steps;
    }

    private static function beforePhotoUploadedAt(Service $s): mixed
    {
        if ($s->relationLoaded('beforePhotos')) {
            return $s->beforePhotos->min('created_at');
        }

        if (empty($s->service_request_id)) {
            return null;
        }

        return $s->beforePhotos()->min('created_at');
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
