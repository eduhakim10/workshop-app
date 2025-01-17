<?php
namespace App\Filament\Pages;

use App\Exports\ServicesExport;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Actions\Action;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Vehicle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\DateFilter;


class ServiceReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Service Report';
    protected static ?string $slug = 'service-report';

    protected static string $view = 'filament.pages.service-report';

    public $customer_id;
    public $vehicle_id;
    public $status;
    public $start_date;
    public $end_date;

    /**
     * Define the form schema for the export filter.
     */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('customer_id')
            ->label('Customer')
            ->options(\App\Models\Customer::pluck('name', 'id'))
            ->searchable()
            ->reactive()
            ->afterStateUpdated(function (callable $set) {
                $set('vehicle', null); // Clear vehicle field when customer changes
            }),

            Forms\Components\Select::make('vehicle_id')
            ->label('Vehicle')
            ->placeholder('Search by license plate or customer name')
            ->searchable()
            ->getSearchResultsUsing(function (string $search) {
                return \App\Models\Vehicle::with('customer') // Ensure customer relationship is loaded
                    ->where('license_plate', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    })
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(function ($vehicle) {
                        return [
                            $vehicle->id => "{$vehicle->license_plate} - {$vehicle->customer->name}",
                        ];
                    });
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
     * Handle the export action.
     */
    public function export()
    {
        $filters = [
            'customer_id' => $this->customer_id,
            'vehicle_id' => $this->vehicle_id,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
       // dd($filters);
        return Excel::download(new ServicesExport($filters), 'service_report.xlsx');
    }
    public function mount(): void
    {
        $this->form->fill();
    }
    // public function render(): \Illuminate\Contracts\View\View
    // {
    //     return view(static::$view, [
    //         'form' => $this->form,
    //     ]);
    // }
}
