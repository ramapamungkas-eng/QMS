<?php

use App\Enums\UserRole;
use App\Models\Process;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Edit user')]
class extends Component {
    use Toast, WithFileUploads;

    public User $user;

    public string $name = '';

    public string $nik = '';

    public string $whatsapp = '';

    public string $role = '';

    public string $process_id = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $pin = '';

    public $photo;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->nik = $user->nik;
        $this->whatsapp = (string) $user->whatsapp;
        $this->role = $user->role->value;
        $this->process_id = (string) $user->process_id;
    }

    public function roles(): array
    {
        return array_map(
            fn (UserRole $role) => ['id' => $role->value, 'name' => $role->label(), 'description' => $role->description()],
            UserRole::cases(),
        );
    }

    public function processes(): array
    {
        return Process::orderBy('name')
            ->get()
            ->map(fn (Process $p) => ['id' => (string) $p->id, 'name' => $p->name])
            ->all();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:20', Rule::unique('users', 'nik')->ignore($this->user->id)],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            // Blank password/pin means "leave unchanged" — see save() below.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'pin' => ['nullable', 'digits:6'],
            'role' => ['required', new Enum(UserRole::class)],
            'process_id' => ['nullable', 'exists:processes,id', 'required_if:role,'.UserRole::Checker->value],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($data['role'] !== UserRole::Checker->value) {
            $data['process_id'] = null;
        }

        if ($this->photo) {
            $data['profile_pic'] = $this->photo->store('profile-pics', 'public');
        }

        if (blank($data['password'])) {
            unset($data['password']);
        }

        if (blank($data['pin'])) {
            unset($data['pin']);
        }

        unset($data['password_confirmation']);

        $this->user->update($data);

        $this->success('User updated.', position: 'toast-bottom', redirectTo: route('users.index'));
    }

    public function with(): array
    {
        return [
            'roles' => $this->roles(),
            'processes' => $this->processes(),
        ];
    }
}; ?>

<div>
    <x-header title="Edit user" subtitle="Update {{ $user->name }}'s account." separator>
        <x-slot:actions>
            <x-button label="Back to list" link="{{ route('users.index') }}" icon="o-arrow-left" responsive />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid gap-6 lg:grid-cols-12">
            <!-- SUMMARY SIDEBAR -->
            <div class="lg:col-span-4">
                <div class="sticky top-4 overflow-hidden rounded-2xl border border-base-300 bg-gradient-to-b from-base-200/60 to-base-100">
                    <div class="flex flex-col items-center gap-3 px-6 py-8">
                        <x-file wire:model="photo" accept="image/png, image/jpeg" class="!w-28">
                            <div class="relative">
                                <img
                                    src="{{ $photo ? $photo->temporaryUrl() : $user->profilePicUrl() }}"
                                    class="h-28 w-28 rounded-full object-cover shadow-lg ring-4 ring-base-100"
                                />
                                <span class="absolute bottom-0 right-0 grid h-8 w-8 place-items-center rounded-full bg-primary text-primary-content shadow">
                                    <x-icon name="o-camera" class="h-4 w-4" />
                                </span>
                            </div>
                        </x-file>

                        <div class="text-center">
                            <p class="text-lg font-semibold leading-tight">{{ $name ?: $user->name }}</p>
                            <p class="font-mono text-sm text-base-content/60">{{ $nik ?: $user->nik }}</p>
                        </div>

                        @if ($role)
                            <x-badge :value="UserRole::from($role)->label()" class="badge-primary badge-outline" />
                        @endif
                    </div>

                    <div class="space-y-3 border-t border-base-300 bg-base-100/60 px-6 py-5 text-sm text-base-content/70">
                        <div class="flex items-start gap-2">
                            <x-icon name="o-information-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>NIK must be unique, max 20 characters.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <x-icon name="o-key" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Leave password or PIN blank to keep the current one.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM -->
            <div class="lg:col-span-8">
                <div class="rounded-2xl border border-base-300 bg-base-100">
                    <!-- Identity -->
                    <section class="px-6 py-6">
                        <h3 class="font-semibold">Identity</h3>
                        <p class="text-sm text-base-content/60">Who this person is.</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <x-input label="Full name" wire:model="name" icon="o-user" class="sm:col-span-2" />
                            <x-input label="NIK" wire:model="nik" icon="o-identification" maxlength="20" />
                            <x-input label="WhatsApp" wire:model="whatsapp" icon="o-phone" placeholder="08xx xxxx xxxx" />
                        </div>
                    </section>

                    <div class="border-t border-base-300"></div>

                    <!-- Access -->
                    <section class="px-6 py-6">
                        <h3 class="font-semibold">Access level</h3>
                        <p class="text-sm text-base-content/60">What this person is allowed to do.</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            @foreach ($roles as $option)
                                <label wire:key="{{ 'role-'.$option['id'] }}"
                                    class="flex cursor-pointer flex-col gap-1 rounded-xl border px-3 py-3 text-sm transition
                                        {{ $role === $option['id'] ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-base-300 hover:border-base-content/30' }}"
                                >
                                    <div class="flex items-center gap-2">
                                        <input type="radio" wire:model.live="role" value="{{ $option['id'] }}" class="radio radio-primary radio-sm" />
                                        <span class="font-medium">{{ $option['name'] }}</span>
                                    </div>
                                    <span class="ml-6 text-xs text-base-content/60">{{ $option['description'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('role')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror

                        @if ($role === \App\Enums\UserRole::Checker->value)
                            <div class="mt-4">
                                <x-select
                                    label="Process"
                                    wire:model="process_id"
                                    :options="$processes"
                                    placeholder="Select a process"
                                    icon="o-cog"
                                    required
                                />
                            </div>
                        @endif
                    </section>

                    <div class="border-t border-base-300"></div>

                    <!-- Security -->
                    <section class="px-6 py-6">
                        <h3 class="font-semibold">Security</h3>
                        <p class="text-sm text-base-content/60">Leave blank to keep the current password/PIN.</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <x-password label="New password" wire:model="password" />
                            <x-password label="Confirm new password" wire:model="password_confirmation" />
                            <x-input label="New PIN" wire:model="pin" icon="o-key" maxlength="6" inputmode="numeric" class="sm:col-span-2" />
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('users.index') }}" />
            <x-button label="Save changes" icon="o-check" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>