<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeWebhookController;
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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:Host', 'require_2fa'])->group(function () {
    Volt::route('/host', 'host.dashboard')->name('host.dashboard');
    Volt::route('/host/bookings', 'host.bookings')->name('host.bookings');
    Volt::route('/host/properties/create', 'host.properties.create')->name('host.properties.create');
    Volt::route('/host/properties/{property}/edit', 'host.properties.edit')->name('host.properties.edit');
    Volt::route('/host/properties/{property}/ical', 'host.properties.ical')->name('host.properties.ical');
    Volt::route('/host/connect', 'host.connect')->name('host.connect');
    Route::get('/host/connect/return', function () {
        app(\App\Services\StripeConnectService::class)->refreshAccountStatus(auth()->user()->host);
        return redirect()->route('host.connect');
    })->name('host.connect.return');
    Route::get('/host/connect/refresh', function () {
        $host = auth()->user()->host;
        $url  = app(\App\Services\StripeConnectService::class)->createOnboardingLink(
            $host,
            route('host.connect.return'),
            route('host.connect.refresh'),
        );
        return redirect()->away($url);
    })->name('host.connect.refresh');
});

Route::middleware(['auth', 'verified', 'role:Provider'])->get('/provider', function () {
    return view('dashboard'); // placeholder — va deveni Provider dashboard în Etapa 5
})->name('provider.dashboard');

Route::middleware(['auth'])->get('/two-factor-setup', function () {
    return view('two-factor-setup');
})->name('two-factor.setup');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
