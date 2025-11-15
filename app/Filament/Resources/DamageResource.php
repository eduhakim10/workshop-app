<?php
// app/Filament/Resources/DamageResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\DamageResource\Pages;
use App\Models\Damage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class DamageResource extends Resource
{
    protected static ?string $model = Damage::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Damage Name')
                ->required()
                ->maxLength(100),

            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->maxLength(255)
                ->placeholder('Optional...'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('description')->limit(40)->toggleable(),
            TextColumn::make('created_at')->dateTime()->sortable()->since(),
        ])->filters([
            //
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDamages::route('/'),
            'create' => Pages\CreateDamage::route('/create'),
            'edit' => Pages\EditDamage::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        return 'Damage';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Damages';
    }
}
