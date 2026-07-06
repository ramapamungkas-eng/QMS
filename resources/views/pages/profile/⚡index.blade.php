<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Profile')]
class extends Component {
    use Toast;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $pin = '';

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'pin' => ['required', 'digits:6'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        $user = auth()->user();
        $user->update([
            'password' => $data['password'],
            'pin' => $data['pin'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation', 'pin');

        $this->success('Profile updated.', position: 'toast-bottom');
    }

    public function with(): array
    {
        return [
            'user' => auth()->user(),
        ];
    }
}; ?>

<div>
    <x-header title="Profile" subtitle="Update your password and PIN." separator />

    <x-form wire:submit="save">
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <div class="sticky top-4 overflow-hidden rounded-2xl border border-base-300 bg-gradient-to-b from-base-200/60 to-base-100">
                    <div class="flex flex-col items-center gap-3 px-6 py-8">
                        <div class="relative">
                            <img
                                src="{{ $user->profilePicUrl() }}"
                                class="h-28 w-28 rounded-full object-cover shadow-lg ring-4 ring-base-100"
                            />
                        </div>

                        <div class="text-center">
                            <p class="text-lg font-semibold leading-tight">{{ $user->name }}</p>
                            <p class="font-mono text-sm text-base-content/60">{{ $user->nik }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 border-t border-base-300 bg-base-100/60 px-6 py-5 text-sm text-base-content/70">
                        <div class="flex items-start gap-2">
                            <x-icon name="o-information-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Password must be at least 8 characters.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <x-icon name="o-key" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>PIN must be exactly 6 digits.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="rounded-2xl border border-base-300 bg-base-100">
                    <section class="px-6 py-6">
                        <h3 class="font-semibold">Change password</h3>
                        <p class="text-sm text-base-content/60">Enter your current password and choose a new one.</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <x-password label="Current password" wire:model="current_password" required class="sm:col-span-2" />
                            <x-password label="New password" wire:model="password" />
                            <x-password label="Confirm new password" wire:model="password_confirmation" />
                        </div>
                    </section>

                    <div class="border-t border-base-300"></div>

                    <section class="px-6 py-6">
                        <h3 class="font-semibold">Change PIN</h3>
                        <p class="text-sm text-base-content/60">Your 6-digit numeric PIN used for mobile verification.</p>
                        <div class="mt-4">
                            <x-input label="New PIN" wire:model="pin" icon="o-key" maxlength="6" inputmode="numeric" class="sm:w-72" />
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Save changes" icon="o-check" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
