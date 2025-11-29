<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Service;
use App\Models\Customer;
use App\Models\CategoryService; 
use App\Models\CategoryItem; 
class DashboardSeino extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.dashboard-seno';

    public $startDate;
    public $endDate;
    public $status;
    public $categoryServiceId;
    public $categoryDamageId;

    public function mount()
    {
        // dd($_SERVER);
        // Set default date range (e.g., last 30 days)
        $this->startDate = request()->query('startDate', now()->subDays(30)->format('Y-m-d'));
        $this->endDate = request()->query('endDate', now()->addDays(30)->format('Y-m-d'));
        $this->status = request()->query('status', null);
        $this->categoryServiceId = request()->query('categoryServiceId', null); // Capture category_service_id
        $this->categoryDamageId = request()->query('categoryDamageId'); 
        $this->CustomerId = request()->query('customerId', 1); 

    
    }

    public function getStats()
    {
        // ❌ Lama:
        // $query = Service::query();
    
        // ⭐ FIX: Base query dengan select minimum kolom → lebih ringan
        $query = Service::select('id', 'amount_offer_revision', 'items');
    
    
        // ⭐ FIX: Filter Date Range
        if ($this->startDate) {
            $query->whereDate('service_start_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('service_start_date', '<=', $this->endDate);
        }
    
        // ⭐ FIX: Filter Customer
        if ($this->CustomerId) {
            $query->where('customer_id', $this->CustomerId);
        }
    
        // ⭐ FIX: Filter Status
        if ($this->status) {
            $query->where('status', $this->status);
        }
    
        // ⭐ FIX: Filter Category Service
        if ($this->categoryServiceId) {
            $query->where('category_service_id', $this->categoryServiceId);
        }
    
        // ⭐ NEW: Filter Category Damage (JSON)
        if ($this->categoryDamageId) {
            $query->whereJsonContains('items', [
                'category_item_id' => (string) $this->categoryDamageId
            ]);
        }
    
    
        // ⭐ FIX: Eksekusi query sekali saja (lebih efisien)
        $services = $query->get();
    
        // ⭐ FIX: Hitung revenue & total service tanpa query ulang
        $revenue = $services->sum('amount_offer_revision');
        $totalServices = $services->count();
    
    
        // ⭐ FIX: Hitung newCustomers aman meski tanggal kosong
        if ($this->startDate && $this->endDate) {
            $newCustomers = Customer::whereBetween('created_at', [
                $this->startDate,
                $this->endDate
            ])->count();
        } else {
            $newCustomers = 0; // atau boleh null
        }
    
        // Return hasil
        return [
            'revenue'        => $revenue,
            'totalServices'  => $totalServices,
            'newCustomers'   => $newCustomers, // ⭐ optional returning
        ];
    }
    
    public function getLocationRevenueData(): array
    {
        $data = \App\Models\Service::join('locations', 'services.location_id', '=', 'locations.id')
        ->selectRaw('locations.name as location, SUM(services.amount_offer_revision) as total_revenue')
        ->groupBy('locations.name')
        ->pluck('total_revenue', 'location')
        ->toArray();
    
        return [
            'labels' => array_keys($data),
            'data' => array_values($data),
        ];
    }
    public function getCustomerRevenueData()
    {
    //    dd($this->startDate);
        $data = Service::selectRaw('customers.name as customer, SUM(amount_offer_revision) as total_revenue')
            ->join('customers', 'services.customer_id', '=', 'customers.id')
            ->groupBy('customers.name')
            ->get();

        return [
            'labels' => $data->pluck('customer'),
            'data' => $data->pluck('total_revenue'),
        ];
    }
    public function getServiceQuantityData()
    {
        $data = Service::selectRaw('locations.name as location, COUNT(services.id) as total_services')
            ->join('locations', 'services.location_id', '=', 'locations.id')
            ->groupBy('locations.name')
            ->get();

        return [
            'labels' => $data->pluck('location'),
            'data' => $data->pluck('total_services'),
        ];
    }
    public function getServicePercentageData()
    {
        $data = Service::selectRaw('locations.name as location, COUNT(services.id) as total_services')
            ->join('locations', 'services.location_id', '=', 'locations.id')
            ->groupBy('locations.name')
            ->get();

        $totalServices = $data->sum('total_services');

        return [
            'labels' => $data->pluck('location'),
            'data' => $data->map(fn ($item) => round(($item->total_services / $totalServices) * 100, 2)),
        ];
    }


    public function getFilteredStats()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');
        $status = request()->query('status');

        // Query data based on filters
        $query = Order::query();

        if ($startDate) {
            $query->whereDate('service_start_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('service_start_date', '<=', $endDate);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($this->categoryServiceId) {
            $query->where('category_service_id', $this->categoryServiceId);
        }

        return [
            'revenue' => $query->sum('total_price'),
            'newCustomers' => $query->distinct('customer_id')->count(),
            'totalServices' => $query->count(),
        ];
    }
    public function getServiceChartData()
    {
        $startYear = 2019;
        $endYear   = now()->year;
        $labels = range($startYear, $endYear);
    
        // ❌ REMOVE:
        // $startDate = request()->query('startDate');
        // $endDate   = request()->query('endDate');
        // $status    = request()->query('status');
        // $customerId = request()->query('customerId');
    
        // ⭐ FIX: Semua filter pakai property (lebih konsisten)
        $startDate  = $this->startDate;
        $endDate    = $this->endDate;
        $status     = $this->status;
        $customerId = $this->CustomerId;
        $damageId   = $this->categoryDamageId;   // ⭐ NEW
        $categoryId = $this->categoryServiceId;  // ⭐ NEW
    
    
        // ⭐ NEW: Query tunggal untuk semua tahun (lebih optimal)
        $query = Service::select(
            'amount_offer_revision',
            'items',
            \DB::raw('YEAR(service_start_date) as year')
        )->whereNotNull('service_start_date');
    
    
        // ⭐ FIX: Filter Customer
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }
    
        // ⭐ FIX: Filter date range
        if ($startDate) {
            $query->whereDate('service_start_date', '>=', $startDate);
        }
    
        if ($endDate) {
            $query->whereDate('service_start_date', '<=', $endDate);
        }
    
        // ⭐ FIX: Filter status
        if ($status) {
            $query->where('status', $status);
        }
    
        // ⭐ FIX: Filter Category Service
        if ($categoryId) {
            $query->where('category_service_id', $categoryId);
        }
    
        // ⭐ NEW: Filter Category Damage (JSON)
        if ($damageId) {
            $query->whereJsonContains('items', [
                'category_item_id' => (string) $damageId
            ]);
        }
    
    
        // ⭐ NEW: Eksekusi query sekali saja
        $services = $query->get();
    
        // ⭐ NEW: Persiapkan array default
        $quantityData = array_fill_keys($labels, 0);
        $revenueData  = array_fill_keys($labels, 0);
    
    
        // ⭐ NEW: Loop results, group by year (super cepat)
        foreach ($services as $service) {
            $year = (int) $service->year;
    
            // Skip jika tahun tidak ada dalam range
            if (!in_array($year, $labels)) {
                continue;
            }
    
            // Hitung quantity = jumlah item per service
            $items = is_string($service->items)
                ? json_decode($service->items, true)
                : $service->items;
    
            $qty = is_array($items) ? count($items) : 0;
    
            // Sum hasil
            $quantityData[$year] += $qty;
            $revenueData[$year]  += (float) $service->amount_offer_revision;
        }
    
    
        // ⭐ FIX: output tetap sama formatnya
        return [
            'labels'   => $labels,
            'quantity' => array_values($quantityData),
            'revenue'  => array_values($revenueData),
        ];
    }
    


    public function getCategoryItems()
    {
        return CategoryItem::pluck('name', 'id'); // Get categories as [id => name]
    }
    public function getCategoryServices()
    {
        return CategoryService::pluck('name', 'id'); // Get categories as [id => name]
    }
    public function getListCustomer()
    {
        return Customer::pluck('name', 'id'); // Get categories as [id => name]
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view dashboard');
    }
    public function getCategoryItemQuantityData()
    {
        // Build query untuk ambil services yang terfilter
        $query = Service::query();
    
        // Filter Customer
        if ($this->CustomerId) {
            $query->where('customer_id', $this->CustomerId);
        }
    
        // Filter Date Range
        if ($this->startDate) {
            $query->whereDate('service_start_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('service_start_date', '<=', $this->endDate);
        }
    
        // Filter Status
        if ($this->status) {
            $query->where('status', $this->status);
        }
    
        // Filter Category Damage
        // JSON array harus dicari dengan cara mengandung value spesifik
        if ($this->categoryDamageId) {
            $query->whereJsonContains('items', [
                'category_item_id' => (string) $this->categoryDamageId
            ]);
        }
    
        // Eksekusi, ambil kolom items
        $services = $query->pluck('items');
    
        $totals = [];
    
        foreach ($services as $itemsJson) {
            if (empty($itemsJson)) {
                continue;
            }
    
            // decode JSON ke array
            $items = is_string($itemsJson) ? json_decode($itemsJson, true) : $itemsJson;
    
            if (!is_array($items)) {
                continue;
            }
    
            foreach ($items as $item) {
    
                if (!isset($item['category_item_id']) || !isset($item['quantity'])) {
                    continue;
                }
    
                $categoryId = (int) $item['category_item_id'];
                $qty = (int) $item['quantity'];
    
                if ($this->categoryDamageId && $categoryId != $this->categoryDamageId) {
                    continue; // Jika filter dipilih → hanya hitung kategori itu saja
                }
    
                if (!isset($totals[$categoryId])) {
                    $totals[$categoryId] = 0;
                }
    
                $totals[$categoryId] += $qty;
            }
        }
    
        // Ambil nama kategori
        $categoryNames = \DB::table('category_items')
            ->whereIn('id', array_keys($totals))
            ->pluck('name', 'id');
    
        $labels = [];
        $data   = [];
    
        foreach ($totals as $categoryId => $qty) {
            $labels[] = $categoryNames[$categoryId] ?? "Category {$categoryId}";
            $data[]   = $qty;
        }
    
        return [
            'labels' => $labels,
            'data'   => $data,
        ];
    }
    
    
    public function getCategoryItemQuantityPerYear()
    {
        // Build base query
        $query = Service::select(
            'items',
            \DB::raw('YEAR(service_start_date) as year')
        )->whereNotNull('service_start_date');
    
        // Filter Customer
        if ($this->CustomerId) {
            $query->where('customer_id', $this->CustomerId);
        }
    
        // Filter Date Range
        if ($this->startDate) {
            $query->whereDate('service_start_date', '>=', $this->startDate);
        }
    
        if ($this->endDate) {
            $query->whereDate('service_start_date', '<=', $this->endDate);
        }
    
        // Filter Status
        if ($this->status) {
            $query->where('status', $this->status);
        }
    
        // Filter Category Damage (JSON)
        if ($this->categoryDamageId) {
            $query->whereJsonContains('items', [
                'category_item_id' => (string) $this->categoryDamageId
            ]);
        }
    
        // Ambil services terfilter
        $services = $query->get();
    
        $totals = []; // [category_id][year] => qty total
    
        foreach ($services as $service) {
    
            $itemsData = $service->items;
    
            // Decode JSON
            if (is_array($itemsData)) {
                $items = $itemsData;
            } elseif (is_string($itemsData) && !empty($itemsData)) {
                $decoded = json_decode($itemsData, true);
                $items = is_array($decoded) ? $decoded : [];
            } else {
                continue;
            }
    
            foreach ($items as $item) {
    
                // Validasi
                if (!isset($item['category_item_id']) || !isset($item['quantity'])) {
                    continue;
                }
    
                $categoryId = (int) $item['category_item_id'];
                $qty = (int) $item['quantity'];
    
                // Jika user pilih kategori → hanya tampilkan kategori itu
                if ($this->categoryDamageId && $categoryId != $this->categoryDamageId) {
                    continue;
                }
    
                if ($qty <= 0) {
                    continue;
                }
    
                $year = $service->year ?? 'Unknown';
    
                // Inisialisasi nested array
                if (!isset($totals[$categoryId])) {
                    $totals[$categoryId] = [];
                }
                if (!isset($totals[$categoryId][$year])) {
                    $totals[$categoryId][$year] = 0;
                }
    
                // Akumulasi qty
                $totals[$categoryId][$year] += $qty;
            }
        }
    
        // Ambil nama kategori
        $categoryNames = \DB::table('category_items')
            ->whereIn('id', array_keys($totals))
            ->pluck('name', 'id');
    
        // Siapkan label & daftar tahun
        $labels = [];
        $years = [];
    
        foreach ($totals as $categoryId => $yearData) {
            $labels[] = $categoryNames[$categoryId] ?? "Category {$categoryId}";
            foreach (array_keys($yearData) as $year) {
                $years[$year] = true;
            }
        }
    
        $years = array_keys($years);
        sort($years);
    
        // Build dataset per tahun
        $datasets = [];
        foreach ($years as $year) {
            $data = [];
            foreach ($totals as $categoryId => $yearData) {
                $data[] = $yearData[$year] ?? 0;
            }
    
            $datasets[] = [
                'label' => $year,
                'data' => $data,
            ];
        }
    
        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }
    



}
