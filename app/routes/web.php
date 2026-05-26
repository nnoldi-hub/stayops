<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

// Properties — public listing (Volt)
Volt::route('/properties', 'properties.index')->name('properties.index');
Volt::route('/properties/{property:slug}', 'properties.show')->name('properties.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:Host', 'require_2fa'])->get('/host', function () {
    return view('dashboard'); // placeholder — va deveni Host dashboard în Etapa 4
})->name('host.dashboard');

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
