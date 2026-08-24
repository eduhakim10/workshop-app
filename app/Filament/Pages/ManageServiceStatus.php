<?php

namespace App\Filament\Pages;

use App\Models\Service;
use App\Support\ServicePresenter;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageServiceStatus extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $navigationLabel = 'Service Status Portal';

    protected static ?string $title = 'Kelola Status Service (Portal Customer)';

    protected static ?string $slug = 'manage-service-status';

    protected static ?int $navigationSort = 98;

    protected static string $view = 'filament.pages.manage-service-status';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view services') ?? true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Service::query()
                    ->where('stage', 2)
                    ->with(['vehicle', 'customer', 'beforePhotos', 'afterPhotos'])
            )
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('spk_number')
                    ->label('SPK / Ref')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle.license_plate')
                    ->label('Plat')
                    ->searchable(),

                Tables\Columns\TextColumn::make('portal_step')
                    ->label('Tahap Portal')
                    ->badge()
                    ->getStateUsing(fn (Service $record) => ServicePresenter::serviceProgress($record)['step_label'])
                    ->color(fn (Service $record) => match (ServicePresenter::serviceProgress($record)['step']) {
                        1 => 'gray',
                        2 => 'info',
                        3 => 'warning',
                        4 => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Status Service')
                    ->options([
                        'Scheduled' => 'Scheduled (Antrian)',
                        'In Progress' => 'In Progress (Dikerjakan)',
                        'Pending Parts' => 'Pending Parts',
                        'On Hold' => 'On Hold',
                        'Completed' => 'Completed (Selesai)',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->selectablePlaceholder(false),

                Tables\Columns\TextColumn::make('before_photos')
                    ->label('Foto Before')
                    ->getStateUsing(fn (Service $record) => $record->beforePhotos->count() . ' foto')
                    ->url(fn (Service $record) => $record->service_request_id
                        ? route('service-requests.show', $record->service_request_id)
                        : null)
                    ->openUrlInNewTab()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('after_photos')
                    ->label('Foto After')
                    ->getStateUsing(fn (Service $record) => $record->afterPhotos->count() . ' foto')
                    ->url(fn (Service $record) => $record->service_request_id
                        ? route('service-requests.after', $record->service_request_id)
                        : null)
                    ->openUrlInNewTab()
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Scheduled' => 'Scheduled',
                        'In Progress' => 'In Progress',
                        'Pending Parts' => 'Pending Parts',
                        'On Hold' => 'On Hold',
                        'Completed' => 'Completed',
                        'Cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Form Service')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Service $record) => \App\Filament\Resources\ServiceResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum ada service aktif')
            ->emptyStateDescription('Service dengan stage 2 akan muncul di sini untuk dikelola status portal-nya.');
    }
}
