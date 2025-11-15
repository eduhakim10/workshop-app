<?php
namespace App\Filament\Pages;

use App\Exports\ServicesExport;
use App\Exports\ItemsExport;
use Filament\Forms;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class ServiceReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Service Report';
    protected static ?string $slug = 'service-report';
    protected static string $view = 'filament.pages.service-report';

    public $seino_no;
    public $customer_id;
    public $location_id;
    public $vehicle_id;
    public $status;
    public $start_date;
    public $end_date;

    /**
     * Form untuk filter export.
     */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('seino_no')
                ->label('Seino / Non Seino')
                ->options([
                    'Seino' => 'Seino',
                    'Non Seino' => 'Non Seino',
                ])
                ->placeholder('All'),

            Forms\Components\Select::make('location_id')
                ->label('Location')
                ->options(\App\Models\Location::pluck('name', 'id'))
                ->searchable()
                ->reactive(),

            Forms\Components\Select::make('customer_id')
                ->label('Customer')
                ->options(\App\Models\Customer::pluck('name', 'id'))
                ->searchable()
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    $set('vehicle', null);
                }),

            Forms\Components\Select::make('vehicle_id')
                ->label('Vehicle')
                ->placeholder('Search by license plate or customer name')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return \App\Models\Vehicle::with('customer')
                        ->where('license_plate', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        })
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn($vehicle) => [
                            $vehicle->id => "{$vehicle->license_plate} - {$vehicle->customer->name}",
                        ]);
                })
                ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Vehicle::with('customer')->find($value)?->license_plate),

            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'Scheduled' => 'Scheduled',
                    'In Progress' => 'In Progress',
                    'Completed' => 'Completed',
                    'Pending Parts' => 'Pending Parts',
                    'On Hold' => 'On Hold',
                    'Cancelled' => 'Cancelled',
                ])
                ->placeholder('Select status'),

            Forms\Components\DatePicker::make('start_date')
                ->label('Service Create At Start'),

            Forms\Components\DatePicker::make('end_date')
                ->label('Service Create At To'),
        ];
    }

    /**
     * Handle Export Services.
     */
    public function export()
    {
        $filters = [
            'customer_id' => $this->customer_id,
            'location_id' => $this->location_id,
            'vehicle_id' => $this->vehicle_id,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];

        return Excel::download(new ServicesExport($filters), 'service_report.xlsx');
    }

    /**
     * Handle Export Items.
     */
    public function exportItems()
    {
        $filters = [
            'seino_no' => $this->seino_no,
            'customer_id' => $this->customer_id,
            'location_id' => $this->location_id,
            'vehicle_id' => $this->vehicle_id,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];

        return Excel::download(new ItemsExport($filters), 'items_report.xlsx');
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view reports');
    }

    /**
     * Tambahkan tombol export di Filament view.
     */
    protected function getActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportServices')
                ->label('Export Services')
                ->button()
                ->color('success')
                ->action(fn() => $this->export()),

            \Filament\Actions\Action::make('exportItems')
                ->label('Export Items')
                ->button()
                ->color('info')
                ->action(fn() => $this->exportItems()),
        ];
    }
}
