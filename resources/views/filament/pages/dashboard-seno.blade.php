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
        <label for="category_services_id" class="block text-sm font-medium text-gray-700">Category Damages</label>
            <select id="category_services_id" name="categoryDamageId" class="p-2 border rounded w-full">
                <!-- <option value="">All D</option> -->
                @foreach ($this->getCategoryItems() as $id => $name)
                    <option value="{{ $id }}" {{ request('categoryDamageId') == $id ? 'selected' : '' }}>
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
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

        <x-filament::card>
            <h3 class="text-lg font-semibold">Total Revenue</h3>
            <p class="text-2xl font-bold">{{ number_format($this->getStats()['revenue'], 2) }}</p>
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

     <div class="grid grid-cols-1 md:grid-cols-6 gap-6">

             <x-filament::card class="h-[400px]">
            <h3 class="text-lg font-semibold mb-2"> </h3>
            <canvas id="categoryItemChart" class="max-h-[300px]"></canvas>
        </x-filament::card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">

             <x-filament::card class="h-[400px]">
            <h3 class="text-lg font-semibold mb-2"> </h3>
            <canvas id="categoryYearChart" class="max-h-[300px]"></canvas>
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
        document.addEventListener("DOMContentLoaded", function () {
    const labels = @json($this->getCategoryItemQuantityData()['labels']);
    const data = @json($this->getCategoryItemQuantityData()['data']);

    console.log("Labels:", labels);
    console.log("Data:", data);

    const ctx = document.getElementById('categoryItemChart').getContext('2d');

    // fungsi buat generate warna random biar beda tiap kategori
    function getRandomColor(alpha = 0.6) {
        const r = Math.floor(Math.random() * 255);
        const g = Math.floor(Math.random() * 255);
        const b = Math.floor(Math.random() * 255);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    // bikin array warna sebanyak jumlah kategori
    const backgroundColors = labels.map(() => getRandomColor(0.6));
    const borderColors = backgroundColors.map(c => c.replace('0.6', '1'));

    // Cegah error kalau datanya kosong
    if (!labels.length || !data.length) {
        console.warn("⚠️ Tidak ada data untuk ditampilkan di chart.");
        ctx.font = "16px Arial";
        ctx.fillText("Tidak ada data untuk ditampilkan", 50, 50);
        return;
    }

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Quantity per Damages',
                data: data,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1,
                borderRadius: 6, // biar bar-nya agak rounded
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#222',
                    titleColor: '#fff',
                    bodyColor: '#fff'
                },
                title: {
                    display: true,
                    text: '📊 Total Quantity per Damages',
                    color: '#333',
                    font: { size: 18, weight: 'bold' }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Quantity', color: '#666' },
                    ticks: { stepSize: 1 }
                },
                x: {
                    title: { display: true, text: 'Damages', color: '#666' }
                }
            }
        }
    });
});

const ctx = document.getElementById('categoryYearChart').getContext('2d');
const chartData = @json($this->getCategoryItemQuantityPerYear());

// 🎨 Warna unik untuk setiap kategori
const baseColors = [
  'rgba(255, 99, 132, 0.6)',
  'rgba(54, 162, 235, 0.6)',
  'rgba(255, 206, 86, 0.6)',
  'rgba(75, 192, 192, 0.6)',
  'rgba(153, 102, 255, 0.6)',
  'rgba(255, 159, 64, 0.6)',
  'rgba(201, 203, 207, 0.6)',
];

// fungsi helper biar warna looping
function getColor(index) {
  return baseColors[index % baseColors.length];
}

// 🎨 Set warna per kategori
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: chartData.labels,
    datasets: chartData.datasets.map((set, i) => ({
      label: set.label,
      data: set.data,
      backgroundColor: chartData.labels.map((_, idx) => getColor(idx)), // warna per kategori
      borderColor: chartData.labels.map((_, idx) => getColor(idx).replace('0.6', '1')),
      borderWidth: 1
    }))
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      title: {
        display: true,
        text: 'Quantity per Damages per Year'
      },
      legend: {
        position: 'top'
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        title: { display: true, text: 'Quantity' }
      },
      x: {
        title: { display: true, text: 'Damages' }
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

