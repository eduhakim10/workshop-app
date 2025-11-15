<?php

namespace App\Exports;

use App\Models\Service;
use App\Models\CategoryItem;
use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ItemsExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        \Log::info('Filters: ', $this->filters);

        $services = Service::with(['customer', 'vehicle'])
                    ->when($this->filters['seino_no'] ?? null, function ($query, $seinoNo) {
                        if ($seinoNo === 'Seino') {
                            $query->where('customer_id', 1);
                        } elseif ($seinoNo === 'Non Seino') {
                            $query->where('customer_id', '!=', 1);
                        }
                    })
            ->when($this->filters['location_id'] ?? null, fn ($query, $locationId) => $query->where('location_id', $locationId))
            ->when($this->filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->when($this->filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($this->filters['start_date'] ?? null, fn ($query, $startDate) => $query->whereDate('service_start_date', '>=', $startDate))
            ->when($this->filters['end_date'] ?? null, fn ($query, $endDate) => $query->whereDate('service_start_date', '<=', $endDate))
            ->get();

        $allItems = collect();

        foreach ($services as $service) {
            // Decode JSON items field
            $items = is_array($service->items) ? $service->items : json_decode($service->items, true);
            if (!is_array($items)) {
                continue;
            }

            $categoryIds = collect($items)->pluck('category_item_id')->filter();
            $itemIds = collect($items)->pluck('item_id')->filter();

            $categories = CategoryItem::whereIn('id', $categoryIds)->pluck('name', 'id');
            $itemsData = Item::whereIn('id', $itemIds)->pluck('name', 'id');

            foreach ($items as $item) {
                $allItems->push([
                    'Service ID' => $service->offer_number,
                    'Customer' => $service->customer->name ?? '-',
                    'Vehicle' => $service->vehicle->license_plate ?? '-',
                    'Category' => $categories[$item['category_item_id']] ?? 'Unknown Category',
                    'Item' => $itemsData[$item['item_id']] ?? 'Unknown Item',
                    'Quantity' => $item['quantity'] ?? 0,
                    'Sales Price' => $item['sales_price'] ?? 0,
                ]);
            }
        }

        return $allItems;
    }

    public function headings(): array
    {
        return [
            'Service ID',
            'Customer',
            'Vehicle',
            'Category',
            'Item',
            'Quantity',
            'Sales Price',
        ];
    }
}
