<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceGroupResource\Pages;
use App\Filament\Resources\ServiceGroupResource\RelationManagers;
use App\Models\ServiceGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceGroupResource extends Resource
{
   protected static ?string $model = ServiceGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Service Groups';

    public static function getLabel(): ?string { return 'Service Group'; }
    public static function getPluralLabel(): ?string { return 'Service Groups'; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->validationMessages([
                    'unique' => 'Service Group name must be unique.',
                ]),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->nullable(),
        ])->columns(1);
    }

   
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServiceGroups::route('/'),
            'create' => Pages\CreateServiceGroup::route('/create'),
            'edit'   => Pages\EditServiceGroup::route('/{record}/edit'),
        ];
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

   
}
