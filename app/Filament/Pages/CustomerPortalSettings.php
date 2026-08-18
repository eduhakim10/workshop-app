<?php

namespace App\Filament\Pages;

use App\Services\SettingService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CustomerPortalSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $navigationLabel = 'Portal Settings';

    protected static ?string $title = 'Customer Portal Settings';

    protected static ?string $slug = 'customer-portal-settings';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.customer-portal-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view customers') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'dashboard_lookback_days' => app(SettingService::class)->dashboardLookbackDays(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dashboard default period')
                    ->description('Rentang tanggal default di dashboard Customer Portal. Customer tetap bisa mengganti filter di portal.')
                    ->schema([
                        Forms\Components\Select::make('dashboard_lookback_days')
                            ->label('Tampilkan data')
                            ->helperText('Periode dihitung mundur dari hari ini (inklusif). Contoh 30 hari = hari ini dan 29 hari sebelumnya.')
                            ->options([
                                7 => '7 hari terakhir',
                                14 => '14 hari terakhir',
                                30 => '30 hari terakhir',
                                60 => '60 hari terakhir',
                                90 => '90 hari terakhir',
                                180 => '180 hari terakhir',
                                365 => '1 tahun terakhir',
                            ])
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(SettingService::class)->set(
            SettingService::DASHBOARD_LOOKBACK_DAYS,
            (int) $data['dashboard_lookback_days'],
            'customer_portal',
        );

        Notification::make()
            ->title('Pengaturan disimpan')
            ->body('Default periode dashboard Customer Portal sudah diperbarui.')
            ->success()
            ->send();
    }
}
