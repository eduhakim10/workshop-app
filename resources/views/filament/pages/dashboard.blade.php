<x-filament::page>
<div class="mb-6 p-4 bg-white shadow rounded-lg">
    <form method="GET" id="filterForm" class="flex flex-wrap items-end gap-4">
        <!-- Start Date -->
        <div class="flex flex-col">
            <label for="start_date" class="text-sm font-medium text-gray-700">Start Date</label>
            <input type="date" id="startDate" name="startDate" 
                   class="w-48 p-2 border rounded-md">
        </div>

        <!-- End Date -->
        <div class="flex flex-col">
            <label for="end_date" class="text-sm font-medium text-gray-700">End Date</label>
            <input type="date" id="endDate'" name="endDate'" 
                   class="w-48 p-2 border rounded-md">
        </div>

        <!-- Status of Service -->
        <div class="flex flex-col">
            <label for="status" class="text-sm font-medium text-gray-700">Status</label>
            <select id="status" name="status" class="w-48 p-2 border rounded-md">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
                <option value="canceled">Canceled</option>
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
            <h3 class="text-lg font-semibold">Total New Customers</h3>
            <p class="text-2xl font-bold">{{ $this->getStats()['newCustomers'] }}</p>
        </x-filament::card>

        <x-filament::card>
            <h3 class="text-lg font-semibold">Total Services</h3>
            <p class="text-2xl font-bold">{{ $this->getStats()['totalServices'] }}</p>
        </x-filament::card>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-filament::card class="h-[400px]">
            <h3 class="text-lg font-semibold mb-2">Revenue by Location</h3>
            <canvas id="locationRevenueChart" class="max-h-[300px]"></canvas>
        </x-filament::card>

        <x-filament::card class="h-[400px]">
            <h3 class="text-lg font-semibold mb-2">Revenue by Customer</h3>
            <canvas id="customerRevenueChart" class="max-h-[300px]"></canvas>
        </x-filament::card>

        <x-filament::card class="h-[400px]">
            <h3 class="text-lg font-semibold mb-2">Quantity of Service by Location</h3>
            <canvas id="serviceQuantityChart" class="max-h-[300px]"></canvas>
        </x-filament::card>

        <x-filament::card class="h-[400px]">
            <h3 class="text-lg font-semibold mb-2">Service Percentage</h3>
            <canvas id="servicePercentageChart" class="max-h-[300px]"></canvas>
        </x-filament::card>
    </div>

    <!-- Load Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- JavaScript for Charts -->
    <script>
        const locationRevenueCtx = document.getElementById('locationRevenueChart').getContext('2d');
        new Chart(locationRevenueCtx, {
            type: 'bar',
            data: {
                labels: @json($this->getLocationRevenueData()['labels']),
                datasets: [{
                    label: 'Revenue by Location',
                    data: @json($this->getLocationRevenueData()['data']),
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        const customerRevenueCtx = document.getElementById('customerRevenueChart').getContext('2d');
        new Chart(customerRevenueCtx, {
            type: 'bar',
            data: {
                labels: @json($this->getCustomerRevenueData()['labels']),
                datasets: [{
                    label: 'Revenue by Customer',
                    data: @json($this->getCustomerRevenueData()['data']),
                    backgroundColor: 'rgba(255, 159, 64, 0.5)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        const serviceQuantityCtx = document.getElementById('serviceQuantityChart').getContext('2d');
        new Chart(serviceQuantityCtx, {
            type: 'line',
            data: {
                labels: @json($this->getServiceQuantityData()['labels']),
                datasets: [{
                    label: 'Service Quantity',
                    data: @json($this->getServiceQuantityData()['data']),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        const servicePercentageCtx = document.getElementById('servicePercentageChart').getContext('2d');
        new Chart(servicePercentageCtx, {
            type: 'pie',
            data: {
                labels: @json($this->getServicePercentageData()['labels']),
                datasets: [{
                    label: 'Service Percentage',
                    data: @json($this->getServicePercentageData()['data']),
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
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

