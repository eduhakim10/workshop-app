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



    public function mount()
    {
        // dd($_SERVER);
        // Set default date range (e.g., last 30 days)
        $this->startDate = request()->query('startDate', now()->subDays(30)->format('Y-m-d'));
        $this->endDate = request()->query('endDate', now()->addDays(30)->format('Y-m-d'));
        $this->status = request()->query('status', null);
        $this->categoryServiceId = request()->query('categoryServiceId', null); // Capture category_service_id
        $this->CustomerId = request()->query('customerId', 1); 

    
    }

    public function getStats()
    {
        
        $query = Service::query();
     //   dd($this->CustomerId);
     //  dd($this->endDate);
        // Apply filters
        if ($this->startDate) {
            $query->whereDate('service_start_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('service_start_date', '<=', $this->endDate);
        }
        if ($this->CustomerId) {
            $query->where('customer_id', '=', $this->CustomerId);
        }
        $query->where('stage', '=', 2);
        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->categoryServiceId) {
            $query->where('category_service_id', $this->categoryServiceId);
        }
          if ($this->categoryServiceId) {
            $query->where('category_service_id', $this->categoryServiceId);
        }

        $revenue = $query->sum('amount_offer_revision');
        $newCustomers = Customer::whereBetween('created_at', [$this->startDate, $this->endDate])->count();
        $totalServices = $query->count();

        // Return data for the dashboard
        return [
            'revenue' => $revenue,
         
            'totalServices' => $totalServices,
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
        $startDate = request()->query('startDate');
        $endDate = request()->query('endDate');
        $status = request()->query('status');
        $customerId = request()->query('customerId');
        $labels = range($startYear, $endYear);

        $quantityData = [];
        $revenueData  = [];

        foreach ($labels as $year) {
            $query = Service::whereYear('service_start_date', $year);
            if ($customerId) {
                    $query->where('customer_id', $customerId);
            }
            // if ($startDate) {
            //     $query->where('service_start_date', '>=', $startDate);
            // }

            // if ($endDate) {
            //     $query->where('service_start_date', '<=', $endDate);
            // }

            // if ($status) {
            //     $query->where('status', $status);
            // }

            // if ($this->categoryServiceId) {
            //     $query->where('category_service_id', $this->categoryServiceId);
            // }
            $quantityData[] = $query->count();
            $revenueData[]  = $query->sum('amount_offer_revision');
        }

        return [
            'labels'   => $labels,
            'quantity' => $quantityData,
            'revenue'  => $revenueData,
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
        $services = Service::pluck('items'); // ambil semua kolom items
    
        $totals = [];
    
        foreach ($services as $itemsJson) {
            if (empty($itemsJson)) {
                continue;
            }
    
            // kalau items sudah array, jangan decode lagi
            $items = is_string($itemsJson) ? json_decode($itemsJson, true) : $itemsJson;
    
            if (!is_array($items)) {
                continue;
            }
    
            foreach ($items as $item) {
                if (!isset($item['category_item_id']) || !isset($item['quantity'])) {
                    continue;
                }
    
                $categoryId = $item['category_item_id'];
                $qty = (int) $item['quantity'];
    
                if (!isset($totals[$categoryId])) {
                    $totals[$categoryId] = 0;
                }
    
                $totals[$categoryId] += $qty;
            }
        }
    
        // ambil nama kategori dari tabel category_items
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
    // Ambil data items dan tahun dari services
    $query = Service::select('items', \DB::raw('YEAR(service_start_date) as year'))
        ->whereNotNull('service_start_date');

        if ($this->CustomerId) {
            $query->where('customer_id', '=', $this->CustomerId);
        }

        $services = $query->get();

    $totals = []; // [category_id][year] => total qty

    foreach ($services as $service) {
        $itemsData = $service->items;

        // Jika items berupa array, skip json_decode
        if (is_array($itemsData)) {
            $items = $itemsData;
        } elseif (is_string($itemsData) && !empty($itemsData)) {
            $decoded = json_decode($itemsData, true);
            $items = is_array($decoded) ? $decoded : [];
        } else {
            continue;
        }

        foreach ($items as $item) {
            $categoryId = $item['category_item_id'] ?? null;
            $qty = isset($item['quantity']) ? (int) $item['quantity'] : 0;

            if ($categoryId && $qty > 0) {
                $year = $service->year ?? 'Unknown';
                if (!isset($totals[$categoryId])) {
                    $totals[$categoryId] = [];
                }
                if (!isset($totals[$categoryId][$year])) {
                    $totals[$categoryId][$year] = 0;
                }
                $totals[$categoryId][$year] += $qty;
            }
        }
    }

    // Ambil nama kategori
    $categoryNames = \DB::table('category_items')
        ->whereIn('id', array_keys($totals))
        ->pluck('name', 'id');

    // Siapin label kategori dan tahun unik
    $labels = [];
    $years = [];

    foreach ($totals as $categoryId => $yearData) {
        $labels[$categoryId] = $categoryNames[$categoryId] ?? "Category {$categoryId}";
        foreach (array_keys($yearData) as $year) {
            $years[$year] = true;
        }
    }

    $labels = array_values($labels);
    $years = array_keys($years);
    sort($years);

    // Bentuk dataset per tahun
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
