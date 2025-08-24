<x-filament::page>
<div class="mb-6 p-4 bg-white shadow rounded-lg">
    <form method="GET" id="filterForm" class="flex flex-wrap items-end gap-4">
        <!-- Start Date -->
        <div class="flex flex-col">
            <label for="start_date" class="text-sm font-medium text-gray-700">Start Date </label>
            <input type="date" id="startDate" value="{{ old('startDate', request('startDate', now()->subDays(30)->format('Y-m-d'))) }}"  name="startDate" 
                   class="w-48 p-2 border rounded-md">
        </div>

        <!-- End Date -->
        <div class="flex flex-col">
            <label for="end_date" class="text-sm font-medium text-gray-700">End Date</label>
            <input type="date" id="endDate" name="endDate" value="{{ old('endDate', request('endDate', now()->addDays(30)->format('Y-m-d'))) }}" 
                    class="w-48 p-2 border rounded-md">
        </div>

        <!-- Status of Service -->
        <div class="flex flex-col">
            <label for="status" class="text-sm font-medium text-gray-700">Status</label>
            <select id="status" name="status" class="w-48 p-2 border rounded-md">
                <option value="">All</option>
                <option value="Scheduled" {{ request('status') == 'Scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="In Progress" {{ request('status') == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                <option value="Completed" {{ request('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                <option value="Pending Parts" {{ request('status') == 'Pending Parts' ? 'selected' : '' }}>Pending Parts</option>
                <option value="On Hold" {{ request('status') == 'Completed' ? 'selected' : '' }}>On Hold</option>
                <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'Cancelled' : '' }}>Canceled</option>
            </select>
        </div>

        <div class="flex flex-col">
        <label for="category_services_id" class="block text-sm font-medium text-gray-700">Category Services</label>
            <select id="category_services_id" name="categoryServiceId" class="p-2 border rounded w-full">
                <option value="">All Categories</option>
                @foreach ($this->getCategoryServices() as $id => $name)
                    <option value="{{ $id }}" {{ request('categoryServiceId') == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>
         <div class="flex flex-col">
        <label for="category_services_id" class="block text-sm font-medium text-gray-700">Customer</label>
            <select id="category_services_id" name="customerId" class="p-2 border rounded w-full">
                <option value="">All Customer</option>
                @foreach ($this->getListCustomer() as $id => $name)
                    <option value="{{ $id }}" {{ $this->CustomerId == $id ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>


        <!-- Button: Ensure it stays visible -->
        <div class="flex items-end">
            <button type="submit" 
                    class="px-4 py-2 text-blue-500 border border-blue-500 rounded-md hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-300">
                Apply Filter
            </button>
        </div>

    </form>
</div>


    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-filament::card>
            <h3 class="text-lg font-semibold">Total Revenue</h3>
            <p class="text-2xl font-bold">{{ number_format($this->getStats()['revenue'], 2) }}</p>
        </x-filament::card>

        <x-filament::card>
            <!-- <h3 class="text-lg font-semibold">Total Quotation</h3>
            <p class="text-2xl font-bold">0</p> -->
        </x-filament::card>

        <x-filament::card>
            <h3 class="text-lg font-semibold">Total Services</h3>
            <p class="text-2xl font-bold">{{ $this->getStats()['totalServices'] }}</p>
        </x-filament::card>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">

             <x-filament::card class="h-[400px]">
            <h3 class="text-lg font-semibold mb-2">Quantity X Revenue Service </h3>
            <canvas id="serviceChart" class="max-h-[300px]"></canvas>
        </x-filament::card>
    </div>

    <!-- Load Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- JavaScript for Charts -->
    <script>
      

        const serviceChartCtx = document.getElementById('serviceChart').getContext('2d');
        new Chart(serviceChartCtx, {
            type: 'line',
            data: {
                labels: @json($this->getServiceChartData()['labels']),
                datasets: [
                    {
                        label: 'Service Quantity',
                        data: @json($this->getServiceChartData()['quantity']),
                        backgroundColor: 'rgba(54, 162, 235, 0.3)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Revenue',
                        data: @json($this->getServiceChartData()['revenue']),
                        backgroundColor: 'rgba(255, 99, 132, 0.3)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1', // supaya revenue & qty bisa punya scale beda
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Quantity',
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Revenue',
                        }
                    }
                }
            }
        });



    </script>

    <style>
        canvas {
    max-height: 400px !important; /* Set a reasonable max height */
    width: 100% !important; /* Ensure full width */
}
.bg-blue-500 {
    background-color: #3b82f6 !important;
}
    </style>
</x-filament::page>

