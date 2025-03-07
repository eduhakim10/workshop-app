<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;





class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getTableColumns(): array
    {
        return [
            TextColumn::make('license_plate')
                ->label('License Plate')
                ->searchable() // Allows text search on the column
                ->filterable(function (Builder $query, $data) {
                    $query->when($data, fn ($query, $value) => $query->where('license_plate', 'like', "%{$value}%"));
                }),
            // Other columns...
        ];
    }
    
    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Select::make('customer_id')
            ->label('Customer')
            ->relationship('customer', 'name') // Define the relationship
            ->searchable() // Enables searching by typing
            ->required(),
                
            Select::make('type')
            ->label('Type')
            ->options([
                'Wing Box' => 'Wing Box',
                'Non Wing Box' => 'Non Wing Box',
                'DumpTruck' => 'DumpTruck',
                'other' => 'Other',
            ])
            ->required()
            ->searchable(),
            TextInput::make('brand')->required(),
            TextInput::make('model')->required(),
            TextInput::make('license_plate')->required()->unique(ignoreRecord: true),
            TextInput::make('karoseri') 
            ->label('Karoseri')
            ->maxLength(255),
            TextInput::make('color'),
            TextInput::make('engine_type'),
            TextInput::make('chassis_number'),
            DatePicker::make('next_service_due_date'),
            DatePicker::make('last_service_date'),
            Textarea::make('notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            TextColumn::make('customer.name')->label('Customer')->searchable(),
            TextColumn::make('type')->label('Type')->sortable(),
            TextColumn::make('brand')->label('Brand'),
            TextColumn::make('model')->label('Model'),
            TextColumn::make('license_plate')->label('License Plate')->searchable(),
        ])
        ->filters([
            Filter::make('license_plate')
                ->label('License Plate')
                ->query(fn (Builder $query, array $data) => 
                    $query->where('license_plate', 'like', "%{$data['license_plate']}%")
                )
                ->form([
                    TextInput::make('license_plate')
                        ->label('License Plate')
                        ->placeholder('Enter License Plate'),
                ]),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }
    public static function afterSave(Model $record, Page $page): void
    {
        $page->redirect(static::getUrl('index'));
    }

    public static function getTableFilters(): array
{
    return [
        SelectFilter::make('license_plate')
            ->label('License Plate')
            ->options(function () {
                return Vehicle::query()
                    ->pluck('license_plate', 'license_plate')
                    ->toArray(); // Prepopulate dropdown with existing license plates
            }),
    ];
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
    public static function getNavigationLabel(): string
    {
        return 'Vehicles';
    }

    public static function getPluralLabel(): string
    {
        return 'Vehicles';
    }

    public static function getNavigationGroup(): string
    {
        return 'Management';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view vehicles');
    }


}
