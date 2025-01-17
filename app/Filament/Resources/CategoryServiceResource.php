<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryServiceResource\Pages;
use App\Filament\Resources\CategoryServiceResource\RelationManagers;
use App\Models\CategoryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\DateFilter;
use Filament\Forms\Components\DatePicker;

class CategoryServiceResource extends Resource
{
    protected static ?string $model = CategoryService::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Category Services';

    protected static ?string $pluralLabel = 'Category Services';

    protected static ?string $navigationGroup = 'Services Management';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\TextInput::make('code')
                ->label('Code')
                ->required()
                ->unique('category_services', 'code')
                ->maxLength(50),
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListCategoryServices::route('/'),
            'create' => Pages\CreateCategoryService::route('/create'),
            'edit' => Pages\EditCategoryService::route('/{record}/edit'),
        ];
    }
}
