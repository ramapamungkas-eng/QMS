<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Users')]
class extends Component {
    use Toast, WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public ?string $role = null;

    public bool $drawer = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    // Reset pagination whenever a filter changes, to avoid landing on an empty page.
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    // Clear filters
    public function clear(): void
    {
        $this->reset('search', 'role');
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->error('You cannot delete your own account.', position: 'toast-bottom');

            return;
        }

        $user = User::findOrFail($id);
        $user->delete();

        $this->success("User #$id deleted.", position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'profile_pic', 'label' => '', 'class' => 'w-12', 'sortable' => false],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'nik', 'label' => 'NIK', 'class' => 'w-40'],
            ['key' => 'whatsapp', 'label' => 'WhatsApp', 'class' => 'w-40', 'sortable' => false],
            ['key' => 'role', 'label' => 'Role', 'class' => 'w-40'],
        ];
    }

    // Role options for the filter drawer select.
    public function roles(): array
    {
        return array_map(
            fn (UserRole $role) => ['id' => $role->value, 'name' => $role->label()],
            UserRole::cases(),
        );
    }

    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('nik', 'like', "%{$this->search}%")
                    ->orWhere('whatsapp', 'like', "%{$this->search}%");
            })
            ->when($this->role, fn (Builder $query) => $query->where('role', $this->role))
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'users' => $this->users(),
            'headers' => $this->headers(),
            'roles' => $this->roles(),
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Users" subtitle="Manage who can access the system." separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search name, NIK, or WhatsApp..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
            <x-button label="New user" link="{{ route('users.create') }}" icon="o-plus" class="btn-primary" responsive />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card shadow>
        <x-table :headers="$headers" :rows="$users" :sort-by="$sortBy" with-pagination>
            @scope('cell_profile_pic', $user)
                <div class="avatar">
                    <div class="h-8 w-8 rounded-full">
                        <img src="{{ $user->profilePicUrl() }}" alt="{{ $user->name }}">
                    </div>
                </div>
            @endscope

            @scope('cell_nik', $user)
                <span class="font-mono">{{ $user->nik }}</span>
            @endscope

            @scope('cell_whatsapp', $user)
                {{ $user->whatsapp ?? '—' }}
            @endscope

            @scope('cell_role', $user)
                <x-badge :value="$user->role->label()" class="badge-outline" />
            @endscope

            @scope('cell_created_at', $user)
                {{ $user->created_at->diffForHumans() }}
            @endscope

            @scope('actions', $user)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" link="{{ route('users.edit', $user) }}" class="btn-ghost btn-sm" />
                    <x-button
                        icon="o-trash"
                        wire:click="delete({{ $user->id }})"
                        wire:confirm="Are you sure you want to delete this user?"
                        spinner
                        class="btn-ghost btn-sm text-error"
                        :disabled="$user->id === auth()->id()"
                    />
                </div>
            @endscope
        </x-table>
    </x-card>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Search name, NIK, or WhatsApp..." wire:model.live.debounce="search" icon="o-magnifying-glass" @keydown.enter="$wire.drawer = false" />

        <x-select label="Role" wire:model.live="role" :options="$roles" placeholder="All roles" class="mt-4" />

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>