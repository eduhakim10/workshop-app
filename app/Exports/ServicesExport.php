<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Vehicle;

class ServicesExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Service::with(['customer', 'vehicle', 'assignTo'])
        ->when($this->filters['location_id'] ?? null, fn ($query, $locationId) => $query->where('location_id', $locationId))
        ->when($this->filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('customer_id', $customerId))
        ->when($this->filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
        ->when($this->filters['start_date'] ?? null, fn ($query, $startDate) => $query->whereDate('service_start_date', '>=', $startDate))
        ->when($this->filters['end_date'] ?? null, fn ($query, $endDate) => $query->whereDate('service_start_date', '<=', $endDate))
        ->get()
        ->map(function ($service) {
            // Decode JSON items field
          // Ensure items is an array
          $items = is_array($service->items) ? $service->items : json_decode($service->items, true);

          // Handle case when decoding fails
          if (!is_array($items)) {
              $items = [];
          }
      
          // Get unique category_item_ids and item_ids
          $categoryIds = collect($items)->pluck('category_item_id')->unique()->filter();
          $itemIds = collect($items)->pluck('item_id')->unique()->filter();
      
          // Fetch all category names at once
          $categories = \App\Models\CategoryItem::whereIn('id', $categoryIds)
              ->pluck('name', 'id'); // [id => name]
      
          // Fetch all item names at once
          $itemsData = \App\Models\Item::whereIn('id', $itemIds)
              ->pluck('name', 'id'); // [id => name]
      
          

          // Format items with category & item names
        $formattedItems = collect($items)->map(function ($item) use ($categories, $itemsData) {
            $categoryName = $categories[$item['category_item_id']] ?? 'Unknown Category';
            $itemName = $itemsData[$item['item_id']] ?? 'Unknown Item';
            return "{$categoryName}: {$itemName} (Qty: {$item['quantity']} x Price: {$item['sales_price']})";
        })->implode("; ");

            return [
                $service->location->name ?? '-',
                $service->customer->name ?? '-',
                $service->vehicle->license_plate ?? '-',
                $service->vehicle->karoseri ?? '-',
                $service->offer_number ?? '-',
                $service->amount_offer ?? '-',
                $service->amount_offer_revision ?? '-',
                $service->handover_offer_date ?? '-',
                $service->work_order_number ?? '-',
                $service->work_order_date ?? '-',
                $service->invoice_number ?? '-',
                $service->invoice_handover_date ?? '-',
                $service->assignTo->name ?? '-',
                $service->service_start_date ?? '-',
                $service->service_due_date ?? '-',
                $service->service_start_time ?? '-',
                $service->service_due_time ?? '-',
                $service->status ?? '-',
                $service->notes ?? '-',
                $formattedItems, // Add formatted items here
            ];
        });
}

public function headings(): array
{
    return [
        'Location',
        'Customer Name',
        'No Pol',
        'Karoseri',
        'Offer Number',
        'Amount Offer',
        'Amount Offer Revision',
        'Handovers Date',
        'Work Order Number',
        'Work Order Date',
        'Invoice Number',
        'Invoice Handover Date',
        'Assign To',
        'Service Start Date',
        'Service Due Date',
        'Service Start Time',
        'Service Due Time',
        'Status',
        'Notes',
        'Items', // Add Items column
    ];
}
}
