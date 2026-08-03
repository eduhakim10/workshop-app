<?php

namespace App\Filament\Resources;

use Filament\Forms\Components\Actions\Action;

use App\Filament\Resources\ServiceResource;

use App\Filament\Resources\QuotationsResource\Pages;
use App\Filament\Resources\QuotationsResource\RelationManagers;
use App\Models\Service;
use App\Helpers\OfferHelper;
use App\Helpers\FormFields;
use App\Helpers\QuotationPricing;
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
use Illuminate\Support\HtmlString;

class QuotationsResource extends Resource
{
    protected static ?string $model = Service::class;

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
                Select::make('service_request_id')
                    ->label('Service Request (SR Number)')
                    ->relationship(
                        name: 'serviceRequest',
                        titleAttribute: 'sr_number'
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

                            $set('customer_id', $sr?->customer?->id);
                            $set('vehicle_id', $sr?->vehicle?->id);
                            $set('notes_before', $sr?->notes);
                        } else {
                            $set('customer_id', null);
                            $set('vehicle_id', null);
                            $set('notes_before', null);
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

                Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->required(),

                TextInput::make('offer_number')
                    ->label('Offer Number')
                    ->default(fn () => OfferHelper::generateOfferNumber())
                    ->required()
                    ->unique(
                        table: Service::class,
                        column: 'offer_number',
                        ignoreRecord: true
                    )
                    ->validationMessages([
                        'unique' => 'Nomor penawaran sudah digunakan. Silakan masukkan nomor lain.',
                    ]),

                TextInput::make('attn_quotation')
                    ->label('Attn')
                    ->placeholder('Person to attention for this quotation')
                    ->columnSpanFull(),

                // Klasifikasi Kerusakan: Ringan / Sedang / Berat
                Select::make('damage_classification')
                    ->label('Klasifikasi Kerusakan')
                    ->options([
                        'Ringan' => 'Ringan',
                        'Sedang' => 'Sedang',
                        'Berat'  => 'Berat',
                    ])
                    ->native(false)
                    ->placeholder('Pilih klasifikasi kerusakan'),

                Select::make('prepared_by')
                    ->label('Prepared by')
                    ->relationship('employee', 'name')
                    ->required(),

                Select::make('quotation_status')
                    ->label('Quotation Status')
                    ->options([
                        'Draft'     => 'Draft',
                        'Sent'      => 'Sent',
                        'Revised'   => 'Revised',
                        'Accepted'  => 'Accepted',
                        'Rejected'  => 'Rejected',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->required(),

                TextInput::make('payment_terms')
                    ->label('Payment Terms')
                    ->default('50% Down Payment, 50% after completion')
                    ->columnSpanFull(),
                TextInput::make('delivery_terms')
                    ->label('Delivery Terms')
                    ->default('Base on Schedule MTI')
                    ->columnSpanFull(),
                TextInput::make('validity_terms')
                    ->label('Validity Terms')
                    ->default('One month after this quotation, this price can be change anythime without price notice')
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),

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

                // ===== Repeater items_offer (group + items) =====
                // NB: recalc dilakukan via afterStateUpdated di outer Repeater
                // (Filament akan memantulkan perubahan inner state ke outer).
                Repeater::make('items_offer')
                    ->label('Service Groups')
                    ->reactive()
                    ->afterStateUpdated(fn (callable $get, callable $set) => self::recalcAndSet($get, $set))
                    ->schema([
                        Select::make('service_group_id')
                            ->label('Service Group')
                            ->options(ServiceGroup::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('qty')
                            ->label('Group Qty')
                            ->numeric()
                            ->default(1)
                            ->required(),

                        // Group Price otomatis dari sum(line subtotal)
                        FormFields::applyRupiahMask(TextInput::make('price'))
                            ->label('Group Price')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Auto-calculated from items.'),

                        Repeater::make('items')
                            ->label('Items')
                            ->reactive()
                            ->schema([
                                Select::make('category_item_id')
                                    ->label('Item Category')
                                    ->options(CategoryItem::pluck('name', 'id'))
                                    ->reactive()
                                    ->searchable()
                                    ->preload()
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
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->defaultItems(1)
                    ->columnSpan('full'),

                // ===== Informasi PPN =====
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

                // ===== Rincian Transaksi =====
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
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('amount_offer_revision', $state);
                    })
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
        return QuotationPricing::calcFromGroups(
            $get('items_offer') ?? [],
            $get('ppn_type'),
            $get('ppn_percent')
        );
    }

    /**
     * Re-hitung dan set field-field summary (group price, total, amount_offer).
     *
     * Dipanggil setiap kali ada perubahan pada items / discount / ppn.
     */
    protected static function recalcAndSet(callable $get, callable $set): void
    {
        $groups = $get('items_offer') ?? [];

        // Update Group Price tiap group dari sum(line subtotal)
        foreach ($groups as $gIdx => $group) {
            $groupTotals = QuotationPricing::calcGroup($group);
            $set("items_offer.{$gIdx}.price", number_format($groupTotals['subtotal'], 2, ',', '.'));
        }

        $totals = QuotationPricing::calcFromGroups(
            $groups,
            $get('ppn_type'),
            $get('ppn_percent')
        );

        $set('total_price', number_format($totals['total'], 2, ',', '.'));

        // Auto-fill amount_offer mengikuti total_price (point #3)
        $set('amount_offer', number_format($totals['total'], 2, ',', '.'));

        // Auto-fill amount_offer_revision sama dengan amount_offer
        $set('amount_offer_revision', number_format($totals['total'], 2, ',', '.'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                BadgeColumn::make('stage')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => (int) $state === 2 ? 'Moved to Service' : 'Quotation')
                    ->color(fn ($state) => (int) $state === 2 ? 'success' : 'gray'),
                TextColumn::make('offer_number')->label('Offer Number')->searchable(),
                TextColumn::make('serviceRequest.sr_number')->label('SR Number')->searchable(),
                TextColumn::make('vehicle.license_plate')->label('License Plate')->searchable(),
                TextColumn::make('subtotal_amount')
                    ->label('Sub Total ')
                    ->formatStateUsing(fn ($state) => $state !== null ? 'Rp ' . number_format((float) $state, 2, ',', '.') : '-')
                    ->toggleable(),
                TextColumn::make('ppn')
                    ->label('PPN')
                    ->getStateUsing(function ($record) {
                        $totals = QuotationPricing::calcFromGroups(
                            $record->items_offer ?? [],
                            $record->ppn_type,
                            $record->ppn_percent
                        );

                        return $totals['ppn'];
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 2, ',', '.'))
                    ->toggleable(),
                TextColumn::make('amount_offer')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => $state !== null ? 'Rp ' . number_format((float) $state, 2, ',', '.') : '-')
                    ->searchable(),
                TextColumn::make('damage_classification')
                    ->label('Klasifikasi Kerusakan')
                    ->toggleable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('location.name')->label('Location'),
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
        return parent::getEloquentQuery()
            ->whereIn('stage', [1, 2])
            ->where('created_at', '>=', '2025-02-19')
            ->orderBy('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotations::route('/create'),
            'edit'   => Pages\EditQuotations::route('/{record}/edit'),
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
