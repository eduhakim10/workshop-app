<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use App\Models\Service;
use App\Models\Customer;
use App\Models\CategoryService; // Import CategoryService model
use App\Models\CategoryItem; 
use Illuminate\Support\Facades\Log;
class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $slug = 'dashboard';

    public $startDate;
    public $endDate;
    public $status;

    protected static string $view = 'filament.pages.dashboard';

    public function mount()
    {
        // dd($_SERVER);
        // Set default date range (e.g., last 30 days)
        $this->startDate = request()->query('startDate', now()->subDays(30)->format('Y-m-d'));
        $this->endDate = request()->query('endDate', now()->addDays(30)->format('Y-m-d'));
        $this->status = request()->query('status', null);
        $this->categoryServiceId = request()->query('categoryServiceId', null); // Capture category_service_id
        $this->categoryDamages = request()->query('categoryDamageId', 1); // Capture category_service_id


    
    }
 
    public function getStats()
    {
        
        $query = Service::query();
        
     //  dd($this->endDate);
        // Apply filters
        if ($this->startDate) {
            $query->whereDate('service_start_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('service_start_date', '<=', $this->endDate);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->categoryServiceId) {
            $query->where('category_service_id', $this->categoryServiceId);
        }
        $query->where('stage', '=', 2);
        $revenue = $query->sum('amount_offer_revision');
      //  $newCustomers = Customer::whereBetween('created_at', [$this->startDate, $this->endDate])->count();
      $newCustomers = Customer::whereHas('services', function ($q) {
                if ($this->startDate) {
                    $q->whereDate('service_start_date', '>=', $this->startDate);
                }
                if ($this->endDate) {
                    $q->whereDate('service_start_date', '<=', $this->endDate);
                }
                $q->whereNotNull('amount_offer_revision'); // pastikan ada revenue
                $q->where('amount_offer_revision', '>', 0); // kalau mau revenue > 0
            })
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
        $totalServices = $query->count();

        // Return data for the dashboard
        return [
            'revenue' => $revenue,
            'newCustomers' => $newCustomers,
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
        $query = Service::selectRaw('customers.name as customer, SUM(amount_offer_revision) as total_revenue, COUNT(services.id) as total_services')
            ->join('customers', 'services.customer_id', '=', 'customers.id')
            ->groupBy('customers.name');

        if ($this->startDate) {
            $query->whereDate('service_start_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('service_start_date', '<=', $this->endDate);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->categoryServiceId) {
            $query->where('category_service_id', $this->categoryServiceId);
        }

        $data = $query->get();

        return [
            'labels'   => $data->pluck('customer'),
            'revenue'  => $data->pluck('total_revenue'),
            'services' => $data->pluck('total_services'),
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
    public function getChartData()
    {
        $query = Service::query();

        // Apply filters
        if ($this->startDate) {
            $query->whereDate('service_start_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('service_start_date', '<=', $this->endDate);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->categoryServiceId) {
            $query->where('category_service_id', $this->categoryServiceId);
        }

        // Bar Chart: Revenue by Location
        $locationRevenue = $query
            ->select('location', DB::raw('SUM(amount_offer_revision) as total_revenue'))
            ->groupBy('location')
            ->get();

        // Bar Chart: Revenue by Customer
        $customerRevenue = $query
            ->select('customer_id', DB::raw('SUM(amount_offer_revision) as total_revenue'))
            ->with('customer:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'customer' => $item->customer->name ?? 'Unknown',
                    'revenue' => $item->total_revenue,
                ];
            });

        // Line Chart: Quantity of Service by Location
        $serviceQuantity = $query
            ->select('location', DB::raw('COUNT(*) as total_services'))
            ->groupBy('location')
            ->get();

        // Pie Chart: Percentage of Services by Status
        $servicePercentage = $query
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        return [
            'locationRevenue' => $locationRevenue,
            'customerRevenue' => $customerRevenue,
            'serviceQuantity' => $serviceQuantity,
            'servicePercentage' => $servicePercentage,
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
    public function getDamageByKaroseri($categoryItemid)
{
//$categoryItemName='AORI';
  //  Log::info("=== getDamageByKaroseri start ===", ['category' => $categoryItemName]);
    if ($this->categoryDamages) {
        $categoryItemid=$this->categoryDamages;
    }
    // Ambil category_item_id dari nama kategori kerusakan
    $categoryItem = CategoryItem::where('id', $categoryItemid)->first();

    if (!$categoryItem) {
        return [
            'labels' => [],
            'data' => [],
        ];
    }
    Log::info("Category found", ['id' => $categoryItem->id, 'name' => $categoryItem->name]);
    // Ambil semua service + join vehicle
    $services = Service::join('vehicles', 'services.vehicle_id', '=', 'vehicles.id')
        ->select('vehicles.karoseri', 'services.items')
        ->get();
    Log::info("Total services fetched", ['count' => $services->count()]);

    $result = [];

    foreach ($services as $service) {
        $items = $service->items; // langsung array, gak perlu decode

        if (!is_array($items)) continue;

        foreach ($items as $item) {
            if (
                isset($item['category_item_id']) &&
                (string)$item['category_item_id'] === (string)$categoryItem->id
            ) {
                $result[$service->karoseri] = ($result[$service->karoseri] ?? 0)
                    + ((int)($item['quantity'] ?? 1));
            }
        }
    }

    // Siapin data chart
    $labels = array_keys($result);
    $data   = array_values($result);
    Log::info("Result akhir", ['data' => $result]);

    return [
        'labels' => $labels,
        'data'   => $data,
    ];
}



    public function getCategoryServices()
    {
        return CategoryService::pluck('name', 'id'); // Get categories as [id => name]
    }
    public function getCategoryItems()
    {
        return CategoryItem::pluck('name', 'id'); // Get categories as [id => name]
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view dashboard');
    }


}
