<?php

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function fitlifeAccessAdmin(): User
{
    Role::findOrCreate('admin', 'web');

    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

it('keeps public registration disabled', function () {
    expect(Route::has('register'))->toBeFalse();

    $this->get('/register')->assertNotFound();

    $this->postJson('/register', [
        'name' => 'Test user',
        'email' => 'test@example.test',
        'password' => 'password',
    ])->assertStatus(405);

    expect(User::count())->toBe(0);
});

it('keeps public login disabled', function () {
    expect(Route::has('login'))->toBeFalse();

    $this->get('/login')->assertNotFound();
});

it('keeps public profile and dashboard disabled', function () {
    $this->actingAs(User::factory()->create());

    expect(Route::has('profile.edit'))->toBeFalse();
    expect(Route::has('dashboard'))->toBeFalse();

    $this->get('/profile')->assertNotFound();
    $this->get('/dashboard')->assertNotFound();
});

it('keeps public password reset disabled', function () {
    expect(Route::has('password.request'))->toBeFalse();

    $this->get('/forgot-password')->assertNotFound();
});

it('keeps public email verification disabled', function () {
    expect(Route::has('verification.notice'))->toBeFalse();
    expect(Route::has('verification.verify'))->toBeFalse();
});

it('allows guests to access the admin login page', function () {
    $this->get('/admin/login')->assertOk();
});

it('redirects guests away from the admin dashboard', function () {
    $this->get('/admin')
        ->assertRedirect(
            route('filament.admin.auth.login')
        );
});

it('denies admin access to ordinary users', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin')->assertForbidden();
});

it('allows administrators to access the admin dashboard', function () {
    $this->actingAs(fitlifeAccessAdmin());

    $this->get('/admin')->assertOk();
});

it('allows administrators to authenticate through filament', function () {
    $admin = fitlifeAccessAdmin();

    Filament::setCurrentPanel(
        Filament::getPanel('admin')
    );

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($admin, 'web');
});

it('rejects invalid administrator passwords', function () {
    $admin = fitlifeAccessAdmin();

    Filament::setCurrentPanel(
        Filament::getPanel('admin')
    );

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $admin->email,
            'password' => 'incorrect-password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest('web');
});

it('rejects ordinary users at filament login', function () {
    $user = User::factory()->create();

    Filament::setCurrentPanel(
        Filament::getPanel('admin')
    );

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest('web');
});

it('allows administrators to logout', function () {
    $this->actingAs(fitlifeAccessAdmin());

    $this->post('/admin/logout')->assertRedirect();

    $this->assertGuest('web');
});
