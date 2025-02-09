<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryItemResource\Pages;
use App\Filament\Resources\CategoryItemResource\RelationManagers;
use App\Models\CategoryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;

class CategoryItemResource extends Resource
{
    protected static ?string $model = CategoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Inventory Management'; // Optional grouping


    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Category Name')
                ->required()
                ->unique(),
            Textarea::make('description')
                ->label('Description')
                ->nullable(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->sortable()->searchable()->label('Category Name'),
                TextColumn::make('description')->label('Description'),
                TextColumn::make('created_at')->dateTime()->label('Created'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoryItems::route('/'),
            'create' => Pages\CreateCategoryItem::route('/create'),
            'edit' => Pages\EditCategoryItem::route('/{record}/edit'),
        ];
    }
}
