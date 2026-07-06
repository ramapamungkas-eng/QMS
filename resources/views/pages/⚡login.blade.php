<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.empty')]
#[Title('Login')]
class extends Component {

    #[Rule('required')]
    public string $nik = '';

    #[Rule('required')]
    public string $password = '';

    public function mount(): void
    {
        if (auth()->user()) {
            redirect('/');
        }
    }

    public function login(): void
    {
        $credentials = $this->validate();

        if (auth()->attempt($credentials)) {
            request()->session()->regenerate();

            redirect()->intended('/');

            return;
        }

        $this->addError('nik', 'The provided credentials do not match our records.');
    }
};
?>

<div class="relative grid min-h-screen place-items-center bg-grid px-4">
    <div class="absolute inset-0 bg-gradient-to-b from-primary/5 to-transparent pointer-events-none"></div>
    <div class="w-full max-w-md relative">
        <div class="mb-6 text-center">
            <div class="flex flex-col items-center gap-2">
                <div class="grid h-14 w-14 place-items-center rounded-2xl bg-primary shadow-lg">
                    <x-icon name="o-cog" class="w-7 h-7 text-primary-content" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-base-content">基準 QJUN</h1>
                    <p class="text-xs text-base-content/50 tracking-wider uppercase font-medium">Quality Management System</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-base-300 bg-base-100 p-8 shadow-lg shadow-base-300/40">
            <h2 class="text-lg font-semibold text-base-content mb-6">Sign in</h2>

            <x-form wire:submit="login">
                <x-input
                    label="NIK"
                    placeholder="Enter your 16-digit NIK"
                    wire:model="nik"
                    icon="o-identification"
                    maxlength="16"
                    inputmode="numeric"
                />
                <x-input
                    label="Password"
                    placeholder="Enter your password"
                    wire:model="password"
                    type="password"
                    icon="o-key"
                />

                <x-slot:actions>
                    <x-button
                        label="Sign in"
                        type="submit"
                        icon="o-paper-airplane"
                        class="btn-primary w-full mt-2"
                        spinner="login"
                    />
                </x-slot:actions>
            </x-form>
        </div>

        <p class="mt-4 text-center text-[10px] text-base-content/30 tracking-wider uppercase">
            &copy; {{ date('Y') }} 基準 QJUN | <code>Proudly built by <a href="//wa.me/6285160185678">Ramonymous dev</code></a>
        </p>
    </div>
</div>