<?php

use App\Models\User;
use Livewire\Livewire;

it('redirects guests to login', function () {
    $this->get('/')->assertRedirect('/login');
});

it('logs in with valid credentials', function () {
    User::factory()->create([
        'nik' => '123456',
        'password' => 'password',
    ]);

    $this->startSession();

    Livewire::test('pages::login')
        ->set('nik', '123456')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect('/');

    $this->assertAuthenticated();
});

it('fails login with invalid credentials', function () {
    User::factory()->create([
        'nik' => '123456',
        'password' => 'password',
    ]);

    $this->startSession();

    Livewire::test('pages::login')
        ->set('nik', '123456')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('nik');

    $this->assertGuest();
});

it('logs out the user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
