<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\CategoryItem;
use App\Models\Item;
use App\Models\Customer;
use App\Helpers\FormFields;
use App\Helpers\QuotationPricing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;
use App\Models\CategoryService;
use App\Models\PortalServiceStatus;
use App\Models\ServiceGroup;
use Filament\Forms\Components\Placeholder;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Password;
use Filament\Forms\Components\Radio;
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
                Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->required(),

                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->reactive()
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('vehicle_id')
                    ->label('Vehicle')
                    ->options(fn (callable $get) => Vehicle::where('customer_id', $get('customer_id'))->pluck('license_plate', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('category_service_id')
                    ->label('Category Service')
                    ->options(CategoryService::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('offer_number')
                    ->label('Offer Number')
                    ->required(),

                // work_order_number = WAJIB karena dipakai filter API
                // (App\Http\Controllers\Api\ServicesController@index uses
                // ->whereNotNull('work_order_number'))
                TextInput::make('work_order_number')
                    ->label('Work Order Number')
                    ->required()
                    ->helperText('Wajib diisi agar data muncul di mobile app (API SPK).'),

                TextInput::make('po_number')
                    ->label('PO Number')
                    ->required(),

                Select::make('damage_classification')
                    ->label('Klasifikasi Kerusakan')
                    ->options([
                        'Ringan' => 'Ringan',
                        'Sedang' => 'Sedang',
                        'Berat'  => 'Berat',
                    ])
                    ->native(false)
                    ->placeholder('Pilih klasifikasi kerusakan'),

                DatePicker::make('handover_offer_date')->label('Handover Offer Date'),
                DatePicker::make('work_order_date')->label('Work Order Date'),

                TextInput::make('invoice_number')->label('Invoice Number'),
                DatePicker::make('invoice_handover_date')->label('Invoice Handover Date'),
                DatePicker::make('invoice_payment_date')->label('Invoice Payment Date'),

                Select::make('document_position')
                    ->options([
                        'pcs' => 'Karawang',
                        'kg' => 'Balaraja',
                        'liters' => 'Cikampek',
                        'Lembar' => 'Karawang Barat',
                        'Meter' => 'MT Haryono',
                    ])
                    ->required(),

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

                Select::make('portal_service_status_id')
                    ->label('Status Service Portal')
                    ->options(
                        PortalServiceStatus::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->helperText('Tahapan yang tampil di dashboard Customer Portal. Antrian di-set otomatis saat Move to Service; Dikerjakan & Finishgood diubah di sini.')
                    ->nullable(),

                Textarea::make('notes')->label('Notes')->columnSpanFull(),

                // ===== Items Repeater (flat) - sama format dengan Quotation =====
                Repeater::make('items')
                    ->label('Items')
                    ->reactive()
                    ->afterStateUpdated(fn (callable $get, callable $set) => self::recalcAndSet($get, $set))
                    ->schema([
                        Select::make('category_item_id')
                            ->label('Item Category')
                            ->options(CategoryItem::pluck('name', 'id'))
                            ->reactive()
                            ->searchable()
                            ->preload()
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
                                    $set('sales_price', FormFields::formatRupiah($item?->sales_price));
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->columnSpan(1)
                            ->required(),

                        FormFields::applyRupiahMask(TextInput::make('sales_price'))
                            ->label('Sales Price')
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->columnSpan(1)
                            ->suffix(fn (callable $get) =>
                                optional(Item::find($get('item_id')))->unit ?? 'pcs'
                            ),

                        TextInput::make('discount_percent')
                            ->label('Disc (%)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->reactive(),

                        Placeholder::make('line_subtotal')
                            ->label('Jumlah')
                            ->content(function (callable $get) {
                                $line = QuotationPricing::calcLine([
                                    'sales_price' => $get('sales_price'),
                                    'quantity' => $get('quantity'),
                                    'discount_percent' => $get('discount_percent'),
                                ]);
                                return 'Rp ' . number_format($line['subtotal'], 2, ',', '.');
                            }),
                    ])
                    ->columns(6)
                    ->collapsible()
                    ->defaultItems(1)
                    ->columnSpan('full')
                    ->required(),

                // ===== Informasi PPN (sync dengan Quotation) =====
                Radio::make('ppn_type')
                    ->label('Informasi PPN')
                    ->options(QuotationPricing::ppnTypeOptions())
                    ->default(QuotationPricing::PPN_NONE)
                    ->inline()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $get, callable $set) => self::recalcAndSet($get, $set))
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('ppn_percent')
                    ->label('PPN (%)')
                    ->numeric()
                    ->default(11)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->reactive()
                    ->afterStateUpdated(fn (callable $get, callable $set) => self::recalcAndSet($get, $set))
                    ->visible(fn (callable $get) => in_array($get('ppn_type'), [
                        QuotationPricing::PPN_INCLUSIVE,
                        QuotationPricing::PPN_EXCLUSIVE,
                        QuotationPricing::PPN_NOT_LEVIED,
                    ]))
                    ->required(fn (callable $get) => in_array($get('ppn_type'), [
                        QuotationPricing::PPN_INCLUSIVE,
                        QuotationPricing::PPN_EXCLUSIVE,
                        QuotationPricing::PPN_NOT_LEVIED,
                    ])),

                Placeholder::make('rincian_subtotal')
                    ->label('Subtotal (DPP)')
                    ->content(fn (callable $get) => 'Rp ' . number_format(self::summarize($get)['subtotal'], 2, ',', '.')),

                Placeholder::make('rincian_discount')
                    ->label('Total Diskon')
                    ->content(fn (callable $get) => 'Rp ' . number_format(self::summarize($get)['discount'], 2, ',', '.')),

                Placeholder::make('rincian_ppn')
                    ->label('PPN')
                    ->content(function (callable $get) {
                        $totals = self::summarize($get);
                        $rateLabel = number_format((float) ($get('ppn_percent') ?? 0), 0);
                        $typeLabel = QuotationPricing::ppnTypeLabel($get('ppn_type'));

                        return 'Rp ' . number_format($totals['ppn'], 2, ',', '.')
                            . ' — ' . $rateLabel . '% (' . $typeLabel . ')';
                    }),

                FormFields::applyRupiahMask(TextInput::make('total_price'))
                    ->label('Total Price')
                    ->disabled()
                    ->dehydrated()
                    ->columnSpan(1),

                FormFields::applyRupiahMask(TextInput::make('amount_offer'))
                    ->label('Amount Offer')
                    ->required()
                    ->helperText('Otomatis mengikuti Total Price. Bisa di-override manual.')
                    ->columnSpan(1),

                FormFields::applyRupiahMask(TextInput::make('amount_offer_revision'))
                    ->label('Amount Offer Revision')
                    ->columnSpan(1),
            ])
            ->columns(2);
    }

    /**
     * Summarize current form state ke total Rincian Transaksi.
     */
    protected static function summarize(callable $get): array
    {
        return QuotationPricing::calcFromFlat(
            $get('items') ?? [],
            $get('ppn_type'),
            $get('ppn_percent')
        );
    }

    /**
     * Re-hitung dan set field-field summary (total, amount_offer).
     */
    protected static function recalcAndSet(callable $get, callable $set): void
    {
        $items = $get('items') ?? [];

        $totals = QuotationPricing::calcFromFlat(
            $items,
            $get('ppn_type'),
            $get('ppn_percent')
        );

        $set('total_price', number_format($totals['total'], 2, ',', '.'));
        $set('amount_offer', number_format($totals['total'], 2, ',', '.'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('offer_number')->label('Offer Number')->searchable(),
                TextColumn::make('work_order_number')->label('Work Order Number')->searchable(),
                TextColumn::make('amount_offer')
                    ->label('Amount Offer')
                    ->formatStateUsing(fn ($state) => $state !== null ? 'Rp ' . number_format((float) $state, 2, ',', '.') : '-')
                    ->searchable(),
                TextColumn::make('amount_offer_revision')
                    ->label('Amount Revision')
                    ->formatStateUsing(fn ($state) => $state !== null ? 'Rp ' . number_format((float) $state, 2, ',', '.') : '-')
                    ->searchable(),
                TextColumn::make('total_price')
                    ->label('Total Price')
                    ->formatStateUsing(fn ($state) => $state !== null ? 'Rp ' . number_format((float) $state, 2, ',', '.') : '-')
                    ->toggleable(),
                TextColumn::make('damage_classification')
                    ->label('Klasifikasi Kerusakan')
                    ->toggleable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('vehicle.license_plate')->label('License Plate')->searchable(),
                TextColumn::make('location.name')->label('Location'),
                TextColumn::make('employee.name')->label('Assign'),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($record) =>
                        ($record->service_start_date)
                            ? "{$record->service_start_date} {$record->service_start_time}<br>{$record->service_due_date} {$record->service_due_time}"
                            : 'N/A'
                    )
                    ->default('NOT EMPTY')
                    ->html(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn ($state): string => match ($state) {
                        'Scheduled'   => 'blue',
                        'Completed'   => 'green',
                        'Cancelled'   => 'red',
                        'In Progress' => 'orange',
                        default       => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('portalServiceStatus.name')
                    ->label('Status Portal')
                    ->badge()
                    ->color(fn ($record) => $record->portalServiceStatus?->badge_color ?? 'gray')
                    ->placeholder('-')
                    ->toggleable(),
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
                Filter::make('work_order_number')
                    ->label('Work Order Number')
                    ->query(function (Builder $query, $data) {
                        if (!empty($data['work_order_number'])) {
                            $query->where('work_order_number', 'like', '%' . $data['work_order_number'] . '%');
                        }
                    })
                    ->form([
                        TextInput::make('work_order_number')
                            ->label('SPK Number')
                            ->placeholder('Enter SPK Number'),
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
            ->where('stage', 2)
            ->orderBy('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create users');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit'   => Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view services');
    }
}
