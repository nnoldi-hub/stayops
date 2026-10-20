<?php

use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeWebhookController;
use App\Models\Host;
use App\Models\User;
use App\Services\StripeConnectService;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

// Properties — public listing (Volt)
Volt::route('/properties', 'properties.index')->name('properties.index');
Volt::route('/properties/{property:slug}', 'properties.show')->name('properties.show');
Volt::route('/properties/{property:slug}/book', 'properties.book')
    ->middleware(['auth', 'verified'])
    ->name('properties.book');
Volt::route('/bookings/{booking:reference}', 'bookings.show')
    ->middleware(['auth'])
    ->name('bookings.show');
Volt::route('/bookings/{booking:reference}/review', 'bookings.review')
    ->middleware(['auth', 'verified'])
    ->name('bookings.review');

Volt::route('/dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Comunicare — mesagerie internă
Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('/messages', 'messages.index')->name('messages.index');
    Volt::route('/messages/{conversation}', 'messages.show')->name('messages.show');
    Route::post('/messages/with/{user}', [MessagingController::class, 'startWith'])
        ->name('messages.start');
});

Route::middleware(['auth', 'verified', 'role:Host', 'require_2fa'])->group(function () {
    Volt::route('/host/subscription', 'host.subscription')->name('host.subscription');
});

Route::middleware(['auth', 'verified', 'role:Host', 'require_2fa', 'subscription'])->group(function () {
    Volt::route('/host', 'host.dashboard')->name('host.dashboard');
    Volt::route('/host/bookings', 'host.bookings')->name('host.bookings');
    Volt::route('/host/properties/create', 'host.properties.create')->name('host.properties.create');
    Volt::route('/host/properties/{property}/edit', 'host.properties.edit')->name('host.properties.edit');
    Volt::route('/host/properties/{property}/ical', 'host.properties.ical')->name('host.properties.ical');
    Volt::route('/host/connect', 'host.connect')->name('host.connect');
    Volt::route('/host/verification', 'host.verification')->name('host.verification');
    Route::get('/host/connect/return', function () {
        $user = auth()->user();
        $host = $user instanceof User ? $user->host : null;
        abort_unless($host instanceof Host, 403);
        app(StripeConnectService::class)->refreshAccountStatus($host);

        return redirect()->route('host.connect');
    })->name('host.connect.return');
    Route::get('/host/connect/refresh', function () {
        $user = auth()->user();
        $host = $user instanceof User ? $user->host : null;
        abort_unless($host instanceof Host, 403);
        $url = app(StripeConnectService::class)->createOnboardingLink(
            $host,
            route('host.connect.return'),
            route('host.connect.refresh'),
        );

        return redirect()->away($url);
    })->name('host.connect.refresh');
});

Route::middleware(['auth'])->get('/two-factor-setup', function () {
    return view('two-factor-setup');
})->name('two-factor.setup');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/impersonation/stop', [ImpersonationController::class, 'stop'])
    ->middleware(['auth'])
    ->name('impersonation.stop');
Route::get('/impersonation/landing', [ImpersonationController::class, 'landing'])
    ->middleware(['auth'])
    ->name('impersonation.landing');
Route::post('/impersonation/{user}', [ImpersonationController::class, 'start'])
    ->middleware(['auth', 'verified', 'permission:users.impersonate', 'require_2fa'])
    ->name('impersonation.start');

require __DIR__.'/auth.php';
