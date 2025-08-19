<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\FileUpload;



class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
                ->schema([
                    TextInput::make('id_employee')
                        ->label('Employee ID')
                        ->required()
                        ->maxLength(20)
                        ->unique(Employee::class, 'id_employee', fn ($record) => $record),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('position')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->tel()
                        ->required()
                        ->maxLength(15),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(Employee::class, 'email', fn ($record) => $record), // Add unique rule for email
                    TextInput::make('address')
                        ->maxLength(500),
                    FileUpload::make('signature')
                        ->label('Tanda Tangan')
                        ->image()
                        ->directory('signatures')
                        ->imagePreviewHeight('150')
                        ->downloadable()
                        ->openable()
                        ->nullable(),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->columns([
            TextColumn::make('id_employee')->label('Employee ID')->sortable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('phone'),
            TextColumn::make('email'),
            // TextColumn::make('address')->limit(50), // Limit address to 50 characters
            TextColumn::make('created_at')
                ->label('Created')
                ->dateTime(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
    // public static function canViewAny(): bool
    // {
    //     return auth()->user()->can('view employees');
    // }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create employees');
    }
    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit employees');
    }
    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete employees');
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view employees');
    }



}
