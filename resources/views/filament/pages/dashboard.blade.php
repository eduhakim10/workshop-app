<x-filament::page>
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
    </style>
</x-filament::page>

