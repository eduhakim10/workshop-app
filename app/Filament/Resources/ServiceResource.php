<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\CategoryItem;
use App\Models\Item;
use App\Models\Customer; 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use App\Models\CategoryService;
use Filament\Forms\Components\Placeholder;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Password;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

use Filament\Tables\Filters\TextFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TimePicker;
use Illuminate\Support\Facades\Log;


class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Services Management';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            // Existing fields...
            Select::make('location_id')
                ->label('Location')
                ->relationship('location', 'name') // Assuming the Location model has a `name` field
                ->required(),

            Select::make('customer_id')
                ->label('Customer')
                ->relationship('customer', 'name')
                ->reactive()
                ->searchable()
                ->required(),

            Select::make('vehicle_id')
                ->label('Vehicle')
                ->options(fn (callable $get) => Vehicle::where('customer_id', $get('customer_id'))->pluck('license_plate', 'id'))
                ->searchable()
                ->required(),
            Select::make('category_service_id')
                ->label('Category Service')
                ->options(CategoryService::pluck('name', 'id')->toArray())
                ->required()
                ->searchable(),
            TextInput::make('offer_number')->required(),
            TextInput::make('spk_number')->required(),
            TextInput::make('po_number')->required(),
            TextInput::make('amount_offer')->numeric()->required(),
            TextInput::make('amount_offer_revision')->numeric(),
            DatePicker::make('handover_offer_date'),
            TextInput::make('work_order_number'),
            DatePicker::make('work_order_date'),
            TextInput::make('invoice_number'),
            DatePicker::make('invoice_handover_date'),
            Select::make('assign_to')
                ->label('Assign to')
                ->relationship('employee', 'name')
                ->required(),
            DatePicker::make('service_start_date')
                ->label('Service Start Date')
                ->required(),
            TimePicker::make('service_start_time'),
            DatePicker::make('service_due_date'),
            TimePicker::make('service_due_time'),
            // TimePicker::make('service_start_time'),
            // TimePicker::make('service_due_time'),
            Select::make('status')
                ->options([
                    'Scheduled' => 'Scheduled',
                    'In Progress' => 'In Progress',
                    'Completed' => 'Completed',
                    'Pending Parts' => 'Pending Parts',
                    'On Hold' => 'On Hold',
                    'Cancelled' => 'Cancelled',
                ])
                ->required(),
            Textarea::make('notes'),

          

            // Items Table
            Repeater::make('items')
            ->label('Items')
            ->schema([
                Select::make('category_item_id')
                ->label('Item Category')
                ->options(CategoryItem::pluck('name', 'id'))
                ->reactive()
                ->searchable()
                ->required(),
                
                Forms\Components\Select::make('item_id')
                    ->label('Item')
                    ->options(fn (callable $get) => 
                    $get('category_item_id') 
                        ? Item::where('category_item_id', $get('category_item_id'))->pluck('name', 'id') 
                        : []
                    )
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $item = Item::find($state);
                            $set('sales_price', $item ? $item->sales_price : null);
                        }
                    })
                    ->searchable()
                    ->columnSpan(1)
                    ->required(),

                Forms\Components\TextInput::make('sales_price')
                    ->label('Sales Price')
                    ->numeric()
                    ->required()
                    ->prefix('Rp'),
                
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->columnSpan(1)
                    ->suffix(fn (callable $get) => 
                        optional(Item::find($get('item_id')))->unit ?? 'pcs'
                    ),
            ])
            ->columns(4)
            ->collapsible()
            ->defaultItems(1)
            ->columnSpan('full')
            ->required(),

    Placeholder::make('total_items_price')
        ->label('Total Price')
        ->content(function (callable $get) {
            $items = $get('items') ?? [];
            $total = 0;

            foreach ($items as $item) {
                $price = isset($item['sales_price']) ? floatval($item['sales_price']) : 0;
                $qty = isset($item['quantity']) ? floatval($item['quantity']) : 0;
                $total += $price * $qty;
            }

            return 'Rp ' . number_format($total, 0, ',', '.');
        })
        ->columnSpanFull(),
        ]);

        



        
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('vehicle.license_plate')->label('License Plate')->searchable(),
                TextColumn::make('location.name')->label('Location'), // New column for Location
                TextColumn::make('employee.name')->label('Assign'),
                TextColumn::make('duration')
                ->label('Duration')
                ->formatStateUsing(fn ($record) => 
                ($record->service_start_date) 
                ? "{$record->service_start_date} {$record->service_start_time}<br>{$record->service_due_date} {$record->service_due_time}"
                : 'N/A'
                )
                ->default('NOT EMPTY') // Force column to always have a value
                ->html(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn ($state): string => match ($state) {
                        'Scheduled' => 'blue',
                        'Completed' => 'green',
                        'Cancelled' => 'red',
                        'In Progress' => 'orange',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                Filter::make('license_plate')
                    ->label('License Plate')
                    ->query(function (Builder $query, $data) {
                        if (!empty($data['license_plate'])) {
                            $query->whereHas('vehicle', function ($vehicleQuery) use ($data) {
                                $vehicleQuery->where('license_plate', 'like', '%' . $data['license_plate'] . '%');
                            });
                        }
                    })
                    ->form([
                        TextInput::make('license_plate')
                            ->label('License Plate')
                            ->placeholder('Enter License Plate'),
                    ]),
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->options(Customer::pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('stage', 2);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function canCreate(): bool
    {
     //   dd($this->role);
  //   dd(auth()->user()->can('create users'));
        return auth()->user()->can('create users');
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view services');
    }
}
