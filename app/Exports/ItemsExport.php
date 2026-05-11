<?php

namespace App\Exports;

use App\Models\Service;
use App\Models\CategoryItem;
use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ItemsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Detail Item Pekerjaan';
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
            ->when($this->filters['location_id'] ?? null, fn ($q, $v) => $q->where('location_id', $v))
            ->when($this->filters['customer_id'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['start_date'] ?? null, fn ($q, $v) => $q->whereDate('service_start_date', '>=', $v))
            ->when($this->filters['end_date'] ?? null, fn ($q, $v) => $q->whereDate('service_start_date', '<=', $v))
            ->get();

        $allItems = collect();

        foreach ($services as $service) {
            $items = is_array($service->items) ? $service->items : json_decode($service->items, true);
            if (!is_array($items)) {
                continue;
            }

            $categoryIds = collect($items)->pluck('category_item_id')->filter();
            $itemIds     = collect($items)->pluck('item_id')->filter();

            $categories = CategoryItem::whereIn('id', $categoryIds)->pluck('name', 'id');
            $itemsData  = Item::whereIn('id', $itemIds)->pluck('name', 'id');

            foreach ($items as $item) {
                $price      = (float) ($item['sales_price'] ?? 0);
                $qty        = (float) ($item['quantity'] ?? 0);
                $discPct    = (float) ($item['discount_percent'] ?? 0);
                $gross      = $price * $qty;
                $discAmount = $gross * ($discPct / 100);
                $subtotal   = $gross - $discAmount;

                $allItems->push([
                    'No. Penawaran'   => $service->offer_number ?? '-',
                    'Customer'        => $service->customer->name ?? '-',
                    'No. Polisi'      => $service->vehicle->license_plate ?? '-',
                    'Kategori'        => $categories[$item['category_item_id']] ?? '-',
                    'Item Pekerjaan'  => $itemsData[$item['item_id']] ?? '-',
                    'Qty'             => $qty,
                    'Harga Satuan'    => $this->rupiah($price),
                    'Gross'           => $this->rupiah($gross),
                    'Diskon (%)'      => $discPct > 0 ? $discPct . '%' : '-',
                    'Potongan Diskon' => $discAmount > 0 ? $this->rupiah($discAmount) : '-',
                    'Subtotal'        => $this->rupiah($subtotal),
                ]);
            }
        }

        return $allItems;
    }

    public function headings(): array
    {
        return [
            'No. Penawaran',
            'Customer',
            'No. Polisi',
            'Kategori',
            'Item Pekerjaan',
            'Qty',
            'Harga Satuan',
            'Gross',
            'Diskon (%)',
            'Potongan Diskon',
            'Subtotal',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = 'K'; // 11 kolom

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(36);

        // Kolom angka rata kanan
        foreach (['F', 'G', 'H', 'J', 'K'] as $col) {
            $sheet->getStyle("{$col}2:{$col}9999")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getStyle("I2:I9999")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    private function rupiah($amount): string
    {
        if ($amount === null || (float) $amount === 0.0) {
            return '-';
        }
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }
}
