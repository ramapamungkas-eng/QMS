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

<div class="grid min-h-screen place-items-center bg-base-200 px-4">
    <div class="w-full max-w-md">
        <div class="mb-4 text-center">
            <x-app-brand class="justify-center" />
        </div>

        <div class="rounded-2xl border border-base-300 bg-base-100 p-8 shadow-sm">
            <x-form wire:submit="login">
                <x-input
                    label="NIK"
                    placeholder="16-digit NIK"
                    wire:model="nik"
                    icon="o-identification"
                    maxlength="16"
                    inputmode="numeric"
                />
                <x-input placeholder="Password" wire:model="password" type="password" icon="o-key" />

                <x-slot:actions>
                    <x-button
                        label="Login"
                        type="submit"
                        icon="o-paper-airplane"
                        class="btn-primary w-full"
                        spinner="login"
                    />
                </x-slot:actions>
            </x-form>
        </div>
    </div>
</div>