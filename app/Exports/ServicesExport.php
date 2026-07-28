<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Models\Service;
use App\Helpers\QuotationPricing;

class ServicesExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Data Servis';
    }

    public function collection()
    {
        return Service::with(['customer', 'vehicle', 'assignTo', 'location'])
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
            ->get()
            ->map(function ($service) {
                $items = is_array($service->items) ? $service->items : json_decode($service->items, true);
                if (!is_array($items)) {
                    $items = [];
                }

                $categoryIds = collect($items)->pluck('category_item_id')->unique()->filter();
                $itemIds     = collect($items)->pluck('item_id')->unique()->filter();

                $categories = \App\Models\CategoryItem::whereIn('id', $categoryIds)->pluck('name', 'id');
                $itemsData  = \App\Models\Item::whereIn('id', $itemIds)->pluck('name', 'id');

                $formattedItems = collect($items)->map(function ($item) use ($categories, $itemsData) {
                    $cat  = $categories[$item['category_item_id']] ?? '?';
                    $name = $itemsData[$item['item_id']] ?? '?';
                    $disc = isset($item['discount_percent']) && (float) $item['discount_percent'] > 0
                        ? ' | Diskon: ' . $item['discount_percent'] . '%'
                        : '';
                    return "• {$cat}: {$name} | Qty: {$item['quantity']} | Harga: "
                        . $this->rupiah($item['sales_price'] ?? 0)
                        . $disc;
                })->implode("\n");

                $ppnType  = $service->ppn_type ?? QuotationPricing::PPN_NONE;
                $ppnLabel = QuotationPricing::ppnTypeLabel($ppnType);
                $ppnPct   = $ppnType !== QuotationPricing::PPN_NONE
                    ? ($service->ppn_percent . '%')
                    : '-';

                return [
                    $service->location->name ?? '-',
                    $service->customer->name ?? '-',
                    $service->vehicle->license_plate ?? '-',
                    $service->vehicle->karoseri ?? '-',
                    $this->damageLabel($service->damage_classification),
                    $service->offer_number ?? '-',
                    $this->quotationStatusLabel($service->quotation_status),
                    $this->rupiah($service->subtotal_amount),
                    $this->rupiah($service->discount_total),
                    $ppnLabel,
                    $ppnPct,
                    $this->rupiah($service->total_price),
                    $this->rupiah($service->amount_offer),
                    $this->rupiah($service->amount_offer_revision),
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
                    $formattedItems,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Lokasi',
            'Nama Customer',
            'No. Polisi',
            'Karoseri',
            'Klasifikasi Kerusakan',
            'No. Penawaran',
            'Status Quotation',
            'Subtotal / DPP',
            'Total Diskon',
            'Jenis PPN',
            '% PPN',
            'Total Harga',
            'Nilai Penawaran (Lama)',
            'Revisi Nilai Penawaran',
            'Tgl. Penawaran',
            'No. Work Order',
            'Tgl. Work Order',
            'No. Invoice',
            'Tgl. Invoice',
            'Teknisi / PIC',
            'Tgl. Mulai Servis',
            'Tgl. Selesai Servis',
            'Jam Mulai',
            'Jam Selesai',
            'Status Servis',
            'Catatan',
            'Item Pekerjaan',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = 'AA'; // 27 kolom

        // Header: background biru gelap, teks putih, tebal, center
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

        // Wrap text & vertical align untuk semua sel data
        $sheet->getStyle("A2:{$lastCol}9999")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);

        // Kolom keuangan (H–L = subtotal, diskon, jenis PPN, %, total): rata kanan
        foreach (['H', 'I', 'K', 'L', 'M', 'N'] as $col) {
            $sheet->getStyle("{$col}2:{$col}9999")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        return [];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function rupiah($amount): string
    {
        if ($amount === null || $amount === '' || (float) $amount === 0.0) {
            return '-';
        }
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }

    private function damageLabel(?string $value): string
    {
        return match ($value) {
            'Ringan' => 'Ringan',
            'Sedang' => 'Sedang',
            'Berat'  => 'Berat',
            default  => '-',
        };
    }

    private function quotationStatusLabel(?string $status): string
    {
        return match ($status) {
            'Draft'     => 'Draft',
            'Sent'      => 'Terkirim',
            'Revised'   => 'Direvisi',
            'Accepted'  => 'Disetujui',
            'Rejected'  => 'Ditolak',
            'Cancelled' => 'Dibatalkan',
            default     => ($status ?? '-'),
        };
    }
}
