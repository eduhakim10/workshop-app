<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Password;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\SelectFilter;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Users Management';

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('Admin');
    }
    public static function canCreate(): bool
    {
        return auth()->user()->can('create users');
    }

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(User::class, 'email', fn ($record) => $record),
                TextInput::make('password')
                ->password()
                ->maxLength(255)
                ->minLength(8)
                ->nullable() // Allows the field to be null
                ->dehydrateStateUsing(fn ($state) => $state ? bcrypt($state) : null) // Hash only if provided
                ->label('Password')
                ->dehydrated(fn ($state) => filled($state)),
            Select::make('roles')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->required(),
            Select::make('employee_id')
            ->label('Employee')
            ->relationship('employee', 'name') // Associate with Employee model's name
            ->searchable()
            ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('roles.name')
                ->label('Role')
                ->formatStateUsing(fn ($record) => $record->roles->pluck('name')->join(', ')) // Get role names
                ->sortable()
                ->searchable(),
                TextColumn::make('employee.id_employee') // Show employee ID (from relationship)
                ->label('Employee ID')
                ->sortable(),
            TextColumn::make('employee.name') // Show employee name (from relationship)
                ->label('Employee Name')
                ->sortable(),
                TextColumn::make('created_at')->label('Created')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'employee' => 'Employee',
                    ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
