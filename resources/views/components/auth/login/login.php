<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app'), Title('Masuk ke Akun - Halala Food')] class extends Component
{
    #[Rule(['required', 'string', 'email'], message: [
        'required' => 'Email wajib diisi.',
        'email' => 'Format email tidak valid.',
    ])]
    public string $email = '';

    #[Rule(['required', 'string'], message: [
        'required' => 'Kata sandi wajib diisi.',
    ])]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
            ]);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $defaultRoute = ($user && $user->roles()->exists())
            ? route('admin.dashboard')
            : route('home');

        $this->redirectIntended(default: $defaultRoute, navigate: true);
    }
};
