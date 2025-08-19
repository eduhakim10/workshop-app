<?php

namespace App\Filament\Resources;

use Filament\Pages\Actions\Action;
use App\Filament\Resources\ServiceResource;

use App\Filament\Resources\QuotationsResource\Pages;
use App\Filament\Resources\QuotationsResource\RelationManagers;
use App\Models\Service; 
use App\Helpers\OfferHelper;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Vehicle;
use App\Models\ServiceGroup;
use Filament\Resources\Resource;
use App\Models\CategoryItem;
use App\Models\Item;
use App\Models\Customer; 
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


class QuotationsResource extends Resource
{
    protected static ?string $model = Quotations::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Offer Management';
    public static function getModel(): string
    {
        return Service::class;
    }
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

            // Select::make('vehicle_id')
            //     ->label('Vehicle')
            //     ->options(fn (callable $get) => Vehicle::where('customer_id', $get('customer_id'))->pluck('license_plate', 'id'))
            //     ->searchable()
            //     ->required(),
            // Select::make('category_service_id')
            //     ->label('Category Service')
            //     ->options(CategoryService::pluck('name', 'id')->toArray())
            //     ->required()
            //     ->searchable(),
            // TextInput::make('offer_number')
            //     ->label('Offer Number')
            //     ->default(fn () => OfferHelper::generateOfferNumber())
            //     // ->disabled() // supaya user ga bisa ubah
            //     ->dehydrated() // tetap disimpan walaupun disabled
            //     ->required(),
            TextInput::make('offer_number')
                ->label('Offer Number')
                ->default(fn () => OfferHelper::generateOfferNumber())
                ->required()
                ->unique(
                    table: Service::class,
                    column: 'offer_number',
                    ignoreRecord: true // supaya pas edit record, dia ga validasi dirinya sendiri
                )
                ->validationMessages([
                    'unique' => 'Nomor penawaran sudah digunakan. Silakan masukkan nomor lain.',
                ]),

            TextInput::make('amount_offer')->numeric()->required(),
            TextInput::make('amount_offer_revision')->numeric(),
            TextInput::make('payment_terms')
                ->label('Payment Terms')
                ->default("50% Down Payment, 50% after completion")
                ->columnSpanFull(),
            TextInput::make('delivery_terms')
                ->label('Delivery Terms')
                ->default("Base on Schedule MTI")
                ->columnSpanFull(),
            TextInput::make('validity_terms')
                ->label('Validity Terms')
                ->default("One month after this quotation, this price can be change anythime without price notice")
                ->columnSpanFull(),

             Select::make('prepared_by')
                ->label('Prepared by')
                ->relationship('employee', 'name')
                ->required(),
         
         
            // TimePicker::make('service_start_time'),
            // TimePicker::make('service_due_time'),
            Select::make('quotation_status')
                ->options([
                    'Draft' => 'Draft',
                    'Sent' => 'Sent',
                    'Revised' => 'Revised',
                    'Accepted' => 'Accepted',
                    'Rejected' => 'Rejected',
                    'Cancelled' => 'Cancelled',
                ])
                ->required(),
            Textarea::make('notes'),

          
    Repeater::make('items_offer')
    ->label('Service Groups')
    ->schema([

        // Service Group Header
        Select::make('service_group_id')
            ->label('Service Group')
            ->options(ServiceGroup::pluck('name', 'id'))
            ->required(),

        TextInput::make('qty')
            ->label('Group Qty')
            ->numeric()
            ->default(1)
            ->required(),

        TextInput::make('price')
            ->label('Group Price')
            ->numeric()
            ->prefix('Rp')
            ->required(),

        // Nested Repeater untuk Items
        Repeater::make('items')
            ->label('Items')
            ->schema([
                Select::make('category_item_id')
                    ->label('Item Category')
                    ->options(CategoryItem::pluck('name', 'id'))
                    ->reactive()
                    ->searchable()
                    ->required(),
                
                Select::make('item_id')
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

                TextInput::make('sales_price')
                    ->label('Sales Price')
                    ->numeric()
                    ->required()
                    ->prefix('Rp'),
                
                TextInput::make('quantity')
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
    ])
    ->columns(3)
    ->collapsible()
    ->defaultItems(1)
    ->columnSpan('full'),


    Placeholder::make('total_items_price')
        ->label('Total Price')
        ->content(function (callable $get) {
            $items = $get('items_offer') ?? [];
            $total = 0;

            foreach ($items as $item) {
                $price = isset($item['sales_price']) ? floatval($item['sales_price']) : 0;
                $qty = isset($item['quantity']) ? floatval($item['quantity']) : 0;
                $total += $price * $qty;
            }

            return 'Rp ' . number_format($total, 0, ',', '.');
        })
        ->reactive() // Penting
        ->columnSpanFull(),
            ]);

        



        
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('offer_number')->label('Offer Number')->searchable(),
                TextColumn::make('amount_offer')->label('Amount')->searchable(),
                TextColumn::make('amount_offer_revision')->label('Amount Revision')->searchable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('location.name')->label('Location'), // New column for Location
                TextColumn::make('employee.name')->label('Prepared by'),
            ])
            ->filters([
                Filter::make('offer_number')
                    ->label('Offer Number')
                    ->query(function (Builder $query, $data) {
                        if (!empty($data['license_plate'])) {
                            $query->whereHas('vehicle', function ($vehicleQuery) use ($data) {
                                $vehicleQuery->where('license_plate', 'like', '%' . $data['license_plate'] . '%');
                            });
                        }
                    })
                    ->form([
                        TextInput::make('offer_number')
                            ->label('Offer Number')
                            ->placeholder('Enter Offer Number'),
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
        return parent::getEloquentQuery()->where('stage', 1); // untuk quotation
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotations::route('/create'),
            'edit' => Pages\EditQuotations::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        return 'Quotation';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Quotations';
    }

}
