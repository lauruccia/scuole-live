<?php

namespace App\Filament\Common\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

class ChangePasswordPage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Cambia password';
    protected static ?string $title = 'Cambia password';
    protected static ?string $navigationGroup = 'Impostazioni';
    protected static string $view = 'filament.common.pages.change-password-page';
    protected static ?int $navigationSort = 999;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\TextInput::make('current_password')
                    ->label('Password attuale')
                    ->password()
                    ->revealable()
                    ->required(),

                Forms\Components\TextInput::make('password')
                    ->label('Nuova password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->same('password_confirmation'),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Conferma nuova password')
                    ->password()
                    ->revealable()
                    ->required(),
            ]);
    }

    public function save(): void
    {
        $user = auth()->user();
        $data = $this->form->getState();

        if (! $user || ! Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('La password attuale non è corretta.')
                ->danger()
                ->send();

            return;
        }

        if ($data['current_password'] === $data['password']) {
            Notification::make()
                ->title('La nuova password deve essere diversa da quella attuale.')
                ->warning()
                ->send();

            return;
        }

        $user->update([
            'password' => $data['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);

        $this->form->fill([
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        Notification::make()
            ->title('Password aggiornata con successo.')
            ->success()
            ->send();
    }
}