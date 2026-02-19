<?php

namespace App\Filament\Pages;

use App\Models\GoogleAccount;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class GoogleSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cloud';
    protected static ?string $navigationGroup = 'Impostazioni';
    protected static ?string $navigationLabel = 'Google (Scuola)';
    protected static ?string $slug = 'google-settings';
    protected static string $view = 'filament.pages.google-settings';
    protected static ?int $navigationSort = 999;

    public static function canAccess(): bool
    {
        $u = Auth::user();
        return $u?->hasAnyRole(['superadmin', 'amministrazione', 'segreteria']) ?? false;
    }

    public ?GoogleAccount $account = null;

    public function mount(): void
    {
        $this->account = GoogleAccount::query()->firstOrCreate(
            ['id' => 1],
            ['label' => 'Scuola', 'calendar_id' => config('services.google.calendar_id', 'primary')]
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect_google')
                ->label('Collega account Google')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(route('google.oauth.redirect'))
                ->openUrlInNewTab(),

            Action::make('disconnect_google')
                ->label('Scollega')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => !empty($this->account?->access_token))
                ->action(function () {
                    $acc = GoogleAccount::query()->find(1);
                    if ($acc) {
                        $acc->access_token = null;
                        $acc->refresh_token = null;
                        $acc->expires_at = null;
                        $acc->email = null;
                        $acc->save();
                    }

                    Notification::make()->title('Account scollegato')->success()->send();
                    $this->mount();
                }),
        ];
    }
}
