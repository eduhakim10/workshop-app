<x-filament::page>
    <x-filament::form wire:submit.prevent="export">
        {{ $this->form }}
        <div class="mt-4">
            <x-filament::button type="submit" color="primary">
                Export to Excel
            </x-filament::button>
        </div>
    </x-filament::form>
</x-filament::page>
