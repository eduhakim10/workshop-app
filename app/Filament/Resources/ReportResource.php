<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Filament\Resources\ReportResource\RelationManagers;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Select::make('location_id')
                ->label('Location')
                ->options(Location::pluck('name', 'id'))
                ->searchable()
                ->placeholder('All Locations'),
                
            Select::make('vehicle_id')
                ->label('Vehicle')
                ->options(Vehicle::pluck('license_plate', 'id'))
                ->searchable()
                ->placeholder('All Vehicles'),

            Select::make('customer_id')
                ->label('Customer')
                ->options(Customer::pluck('name', 'id'))
                ->searchable()
                ->placeholder('All Customers'),

            Select::make('status')
                ->label('Status')
                ->options([
                    'Scheduled' => 'Scheduled',
                    'In Progress' => 'In Progress',
                    'Completed' => 'Completed',
                    'Pending Parts' => 'Pending Parts',
                    'On Hold' => 'On Hold',
                    'Cancelled' => 'Cancelled',
                ])
                ->placeholder('All Status'),

            DatePicker::make('created_at_start')
                ->label('Service Create At Start'),

            DatePicker::make('created_at_to')
                ->label('Service Create At To'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
