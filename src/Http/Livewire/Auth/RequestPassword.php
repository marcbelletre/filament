<?php

namespace Filament\Http\Livewire\Auth;

use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Action;
use Filament\Fields;
use Filament\Traits\WithNotifications;
use Illuminate\Support\Facades\Password;

class RequestPassword extends Action
{
    use WithNotifications;
    use WithRateLimiting;

    public $email;

    public function fields()
    {
        return [
            Fields\Text::make('email')
                ->label('filament::fields.labels.email')
                ->hint('[' . __('filament::auth.backToLogin') . '](' . route('filament.auth.login') . ')')
                ->email()
                ->autofocus()
                ->autocomplete('email')
                ->required()
                ->email(),
        ];
    }

    public function submit()
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->addError('email', __('auth.throttle', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => ceil($exception->secondsUntilAvailable / 60),
            ]));

            return;
        }

        $requestStatus = Password::broker('filament_users')->sendResetLink($this->validate());

        if (Password::RESET_LINK_SENT !== $requestStatus) {
            $this->addError('email', __('filament::auth.' . $requestStatus));

            return;
        }

        $this->notify(__('filament::auth.' . $requestStatus));
    }

    public function render()
    {
        return view('filament::.auth.request-password')
            ->layout('filament::layouts.auth', ['title' => 'filament::auth.resetPassword']);
    }
}