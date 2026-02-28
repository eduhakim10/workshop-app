<?php

namespace App\Filament\Resources;

// use Filament\Pages\Actions\Action;
use Filament\Forms\Components\Actions\Action; // ✅ bener

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
use App\Models\ServiceRequest;
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
          
            // Select::make('service_request_id')
            // ->label('Service Request (SR Number)')
            // ->relationship(
            //     name: 'serviceRequest', // nama relasi di model Service
            //     titleAttribute: 'sr_number' // tampilkan sr_number
            // )
            // ->options(function () {
            //     $usedSrIds = \App\Models\Service::whereNotNull('service_request_id')
            //         ->pluck('service_request_id')
            //         ->unique()
            //         ->toArray();
        
            //     return \App\Models\ServiceRequest::whereNotIn('id', $usedSrIds)
            //         ->with('customer')
            //         ->get()
            //         ->mapWithKeys(fn($sr) => [
            //             $sr->id => ($sr->sr_number ?? 'No SR') . ' - ' . ($sr->customer?->name ?? 'No Customer')
            //         ])
            //         ->toArray();
            // })
            // ->searchable()
            // ->preload()
            // ->reactive()
            // ->afterStateUpdated(function ($state, callable $set) {
            //     if ($state) {
            //         $sr = \App\Models\ServiceRequest::with('customer')->find($state);
            //         if ($sr && $sr->customer) {
            //             $set('customer_id', $sr->customer->id);
            //         }
            //         if ($sr?->vehicle) {
            //             $set('vehicle_id', $sr->vehicle->id);
            //         }
            //     } else {
            //         $set('customer_id', null);
            //     }
            // })
            // ->suffixAction( // 👈 tombol preview di sebelah kanan dropdown
            //     Action::make('preview')
            //         ->icon('heroicon-o-eye')
            //         ->label('Preview')
            //         ->url(fn ($state) => $state 
            //             ? route('service-requests.show', $state) // arahkan ke detail page
            //             : null, true // true = open in new tab
            //         )
            //         ->visible(fn ($state) => filled($state)) // tampil hanya kalau ada yg dipilih
            // )
            // ->required(),

            Select::make('service_request_id')
                    ->label('Service Request (SR Number)')
                    ->relationship(
                        name: 'serviceRequest', // relasi di model Service
                        titleAttribute: 'sr_number' // tampilkan sr_number
                    )
                    ->options(function () {
                        $usedSrIds = \App\Models\Service::whereNotNull('service_request_id')
                            ->pluck('service_request_id')
                            ->unique()
                            ->toArray();

                        return \App\Models\ServiceRequest::whereNotIn('id', $usedSrIds)
                            ->with(['customer', 'vehicle'])
                            ->get()
                            ->mapWithKeys(fn($sr) => [
                                $sr->id => ($sr->sr_number ?? 'No SR') 
                                    . ' - ' . ($sr->customer?->name ?? 'No Customer')
                                    . ' - ' . ($sr->vehicle?->license_plate ?? 'No Vehicle')
                            ])
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $sr = \App\Models\ServiceRequest::with(['customer', 'vehicle'])->find($state);

                            if ($sr?->customer) {
                                $set('customer_id', $sr->customer->id);
                            } else {
                                $set('customer_id', null);
                            }

                            if ($sr?->vehicle) {
                                $set('vehicle_id', $sr->vehicle->id);
                            } else {
                                $set('vehicle_id', null);
                            }

                            if ($sr?->notes) {
                                $set('notes_before', $sr?->notes);
                            } else {
                                $set('notes_before', null);

                            }

                          
                        } else {
                            $set('customer_id', null);
                            $set('vehicle_id', null);
                        }
                    })
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('preview')
                            ->icon('heroicon-o-eye')
                            ->label('Preview')
                            ->url(fn ($state) => $state 
                                ? route('service-requests.show', $state)
                                : null, true
                            )
                            ->visible(fn ($state) => filled($state))
                    )
                    ->required(),

        
            Select::make('customer_id')
            ->label('Customer')
            ->relationship('customer', 'name')
            ->searchable()
            ->disabled() 
            ->dehydrated() 
            ->required(),

            Select::make('vehicle_id')
            ->label('Vehicle')
            ->relationship('vehicle', 'license_plate')
            ->searchable()
            ->disabled()
            ->dehydrated()
            ->required(),
        
        



        
        
            // Existing fields...
            Select::make('location_id')
                ->label('Location')
                ->relationship('location', 'name') // Assuming the Location model has a `name` field
                ->required(),

        
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

            TextInput::make('attn_quotation')
                ->label('Attn')
                ->placeholder('Person to attention for this quotation')
                ->columnSpanFull(),

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
            Textarea::make('notes_before')
    ->label('Notes Before')
    ->afterStateHydrated(function ($state, $set, $record) {
        if (!$state && $record?->service_request_id) {
            $sr = \App\Models\ServiceRequest::find($record->service_request_id);
            $set('notes_before', $sr?->notes);
        }
    })
    ->disabled()
    ->dehydrated()
    ->columnSpanFull(),


    
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
        $groups = $get('items_offer') ?? [];
        $total = 0;

        foreach ($groups as $group) {
            $items = $group['items'] ?? [];
            foreach ($items as $item) {
                $price = isset($item['sales_price']) ? floatval($item['sales_price']) : 0;
                $qty = isset($item['quantity']) ? floatval($item['quantity']) : 0;
                $total += $price * $qty;
            }
        }

        return 'Rp ' . number_format($total, 0, ',', '.');
    })
    ->reactive() // biar update realtime
    ->columnSpanFull(),
            ]);

        



        
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('offer_number')->label('Offer Number')->searchable(),
                TextColumn::make('serviceRequest.sr_number')->label('SR Number')->searchable(),
                TextColumn::make('vehicle.license_plate')->label('License Plate')->searchable(),
                TextColumn::make('amount_offer')->label('Amount')->searchable(),
                TextColumn::make('amount_offer_revision')->label('Amount Revision')->searchable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('location.name')->label('Location'), // New column for Location
                TextColumn::make('employee.name')->label('Prepared by'),
            ])
            ->filters([
                Filter::make('sr_number')
                    ->label('SR Number')
                    ->query(function (Builder $query, $data) {
                        if (!empty($data['sr_number'])) {
                            $query->whereHas('serviceRequest', function ($q) use ($data) {
                                $q->where('sr_number', 'like', '%' . $data['sr_number'] . '%');
                            });
                        }
                    })
                    ->form([
                        TextInput::make('sr_number')
                            ->label('SR Number')
                            ->placeholder('Enter SR Number'),
                    ]),
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
                Filter::make('offer_number')
                    ->label('Offer Number')
                    ->query(function (Builder $query, $data) {
                        if (!empty($data['offer_number'])) {
                            $query->where('offer_number', 'like', '%' . $data['offer_number'] . '%');
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
        // Tampilkan quotations dengan stage 1 (Quotation) dan 2 (Draft/Revision)
        return parent::getEloquentQuery()
            ->whereIn('stage', [1, 2])
            ->where('created_at', '>=', '2025-02-19')
              ->orderBy('created_at', 'desc');
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
