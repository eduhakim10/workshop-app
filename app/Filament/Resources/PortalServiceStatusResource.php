<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PortalServiceStatusResource\Pages;
use App\Models\PortalServiceStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PortalServiceStatusResource extends Resource
{
    protected static ?string $model = PortalServiceStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Status Service Portal';

    protected static ?string $modelLabel = 'Status Service Portal';

    protected static ?string $pluralModelLabel = 'Status Service Portal';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50)
                ->helperText('Contoh: kendaraan_diterima, antrian, dikerjakan, finishgood'),

            Forms\Components\TextInput::make('name')
                ->label('Nama (tampil di portal)')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('sort_order')
                ->label('Urutan tahap')
                ->numeric()
                ->required()
                ->default(1)
                ->minValue(1),

            Forms\Components\Select::make('badge_color')
                ->label('Warna badge')
                ->options([
                    'gray' => 'Gray',
                    'info' => 'Info',
                    'warning' => 'Warning',
                    'success' => 'Success',
                    'danger' => 'Danger',
                    'primary' => 'Primary',
                ])
                ->default('gray')
                ->required(),

            Forms\Components\Select::make('clickable_action')
                ->label('Aksi klik di portal')
                ->options([
                    'before_photos' => 'Buka foto before',
                    'after_photos' => 'Buka foto after',
                ])
                ->placeholder('Tidak bisa diklik')
                ->nullable(),

            Forms\Components\Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('badge_color')
                    ->badge()
                    ->color(fn (string $state): string => $state),
                Tables\Columns\TextColumn::make('clickable_action')
                    ->label('Klik portal')
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortalServiceStatuses::route('/'),
            'create' => Pages\CreatePortalServiceStatus::route('/create'),
            'edit' => Pages\EditPortalServiceStatus::route('/{record}/edit'),
        ];
    }
}
