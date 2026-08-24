<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Panduan Tahapan Portal Customer</x-slot>
        <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-400">
            <ol class="list-decimal pl-4 space-y-1">
                <li><strong>Kendaraan Diterima</strong> — otomatis saat SR/foto before ada (klik foto before di portal).</li>
                <li><strong>Antrian</strong> — otomatis saat quotation move to service (stage 2). Set status <em>Scheduled</em>.</li>
                <li><strong>Dikerjakan</strong> — update manual: <em>In Progress</em>, <em>Pending Parts</em>, atau <em>On Hold</em>.</li>
                <li><strong>Finishgood</strong> — update manual ke <em>Completed</em> + upload foto after (klik foto after di portal).</li>
            </ol>
        </div>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
