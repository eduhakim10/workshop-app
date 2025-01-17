@extends('filament::page')

@section('content')
    <div>
        {{ $this->form }}
        <div class="mt-4">
            <x-filament::button wire:click="export">
                Export to Excel
            </x-filament::button>
        </div>
    </div>
@endsection
