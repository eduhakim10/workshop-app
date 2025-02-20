<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use App\Models\CategoryItem;



class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube'; // Example icon
    protected static ?string $navigationGroup = 'Inventory Management'; // Optional grouping

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Select::make('category_item_id')
            ->label('Category')
            ->options(CategoryItem::pluck('name', 'id')) // Ambil kategori dari tabel category_items
            ->searchable()
            ->preload()
            ->required(),
        

            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('item_code')->label('Item Code')->required()->unique(ignoreRecord: true),
            TextInput::make('quantity')->numeric()->required()->minValue(0),
            Select::make('unit')
                ->options([
                    'Lembar' => 'Lembar',
                    'Meter' => 'Meter',
                    'Jam' => 'Jam',
                    'Batang' => 'Batang',
                ])
                ->required(),
            TextInput::make('purchase_price')->label('Purchase Price')->numeric()->required(),
            TextInput::make('sales_price')->numeric()->required()->label('Sales Price')->extraAttributes(['class' => 'price-input']),
            TextInput::make('manufacturer_by')->label('Manufacturer By'),
            Textarea::make('warranty_information')->label('Warranty Information'),
            Textarea::make('notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
             TextColumn::make('category.name')->label('Category')->sortable()->searchable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('item_code')->label('Item Code')->sortable(),
            TextColumn::make('quantity')->sortable(),
            TextColumn::make('unit')->sortable(),
            TextColumn::make('purchase_price')
                ->label('Purchase Price')
                ->money('idr'),
            TextColumn::make('sales_price')
                ->label('Sales Price')
                ->money('idr'),
            TextColumn::make('manufacturer_by')
                ->label('Manufacturer'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ])
        ->defaultSort('item_code');
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
    protected function beforeSave(Model $record): void
    {
        if ($record->sales_price <= $record->purchase_price) {
            throw ValidationException::withMessages([
                'sales_price' => 'Sales price must be greater than purchase price.',
            ]);
        }
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view items');
    }

}
