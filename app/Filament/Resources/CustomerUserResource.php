<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerUserResource\Pages;
use App\Models\CustomerUser;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomerUserResource extends Resource
{
    protected static ?string $model = CustomerUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Portal Users';
    }

    public static function getModelLabel(): string
    {
        return 'Portal User';
    }

    public static function getPluralLabel(): string
    {
        return 'Portal Users';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view customers') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create customers') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('edit customers') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete customers') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('customer_id')
                    ->label('Customer (Company)')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit'
                        ? 'Kosongkan jika tidak ingin mengubah password.'
                        : null),
                Select::make('role')
                    ->options([
                        'Admin' => 'Admin',
                        'Finance' => 'Finance',
                        'Purchasing' => 'Purchasing',
                        'Viewer' => 'Viewer',
                    ])
                    ->required()
                    ->default('Viewer'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'danger',
                        'Finance' => 'warning',
                        'Purchasing' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('role')
                    ->options([
                        'Admin' => 'Admin',
                        'Finance' => 'Finance',
                        'Purchasing' => 'Purchasing',
                        'Viewer' => 'Viewer',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerUsers::route('/'),
            'create' => Pages\CreateCustomerUser::route('/create'),
            'edit' => Pages\EditCustomerUser::route('/{record}/edit'),
        ];
    }
}
