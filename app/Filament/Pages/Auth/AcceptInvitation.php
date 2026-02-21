<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Url;

class AcceptInvitation extends SimplePage
{
    use WithRateLimiting;

    protected string $view = 'filament.app.pages.auth.accept-invitation';

    #[Url]
    public string $token = '';

    public ?array $data = [];

    protected ?User $invitedUser = null;

    public function mount(): void
    {
        if (empty($this->token)) {
            $this->redirect(Filament::getLoginUrl());
            return;
        }

        $this->invitedUser = User::where('invitation_token', $this->token)
            ->whereNull('invitation_accepted_at')
            ->first();

        if (! $this->invitedUser) {
            Notification::make()
                ->title('Invalid or expired invitation link.')
                ->danger()
                ->send();

            $this->redirect(Filament::getLoginUrl());
            return;
        }

        $this->form->fill([
            'name'  => $this->invitedUser->name,
            'email' => $this->invitedUser->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->disabled()
                    ->dehydrated(false),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->confirmed(),

                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function accept(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title("Too many attempts. Please wait {$exception->secondsUntilAvailable} seconds.")
                ->danger()
                ->send();
            return;
        }

        $data = $this->form->getState();

        $this->invitedUser = User::where('invitation_token', $this->token)
            ->whereNull('invitation_accepted_at')
            ->firstOrFail();

        $this->invitedUser->update([
            'name'                    => $data['name'],
            'password'                => Hash::make($data['password']),
            'invitation_accepted_at'  => now(),
            'invitation_token'        => null,
            'email_verified_at'       => now(),
        ]);

        Auth::login($this->invitedUser);

        Notification::make()
            ->title('Welcome! Your account has been set up successfully.')
            ->success()
            ->send();

        $this->redirect(Filament::getUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return 'Accept Invitation';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Set up your account';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->invitedUser
            ? "Welcome, {$this->invitedUser->name}! Please set a password to complete your registration."
            : 'Complete your account setup.';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('accept')
                ->label('Activate Account')
                ->submit('accept'),
        ];
    }
}
