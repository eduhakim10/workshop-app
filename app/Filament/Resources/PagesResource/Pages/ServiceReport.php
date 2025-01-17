<?php

namespace App\Filament\Resources\PagesResource\Pages;

use App\Filament\Resources\PagesResource;
use Filament\Resources\Pages\Page;
use App\Exports\ServicesReportExport;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Maatwebsite\Excel\Facades\Excel;


class ServiceReport extends Page
{
    protected static string $resource = PagesResource::class;

    protected static string $view = 'filament.resources.pages-resource.pages.service-report';
    protected static ?string $navigationIcon = 'heroicon-o-document-report';

    protected static ?string $navigationLabel = 'Service Report';

    public $filters = [
        'customer_id' => null,
        'status' => null,
        'start_date' => null,
        'end_date' => null,
    ];

    public function mount(): void
    {
        $this->filters['start_date'] = now()->startOfMonth()->format('Y-m-d');
        $this->filters['end_date'] = now()->endOfMonth()->format('Y-m-d');
    }

    protected function getForms(): array
    {
        return [
            Forms\Components\Form::make('filters')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Customer')
                        ->options(\App\Models\Customer::pluck('name', 'id'))
                        ->placeholder('All Customers'),

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
                        ->placeholder('All Statuses'),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->default(now()->startOfMonth()),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->default(now()->endOfMonth()),
                ])
                ->columns(2),
        ];
    }

    public function export(): void
    {
        $filename = 'services_report_' . now()->format('Ymd_His') . '.xlsx';

        // Generate Excel file
        Excel::download(new ServicesReportExport($this->filters), $filename);
    }

    protected function getActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('Export')
                ->label('Export Report')
                ->action('export')
                ->button()
                ->color('primary'),
        ];
    }

}
