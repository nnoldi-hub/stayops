<?php

namespace App\Providers;

use App\Events\BookingCheckedOut;
use App\Listeners\CreateCleaningTask;
use App\Models\Booking;
use App\Observers\BookingObserver;
use App\Services\StripeConnectService;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(StripeConnectService::class, fn () => new StripeConnectService(Cashier::stripe()));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Booking::observe(BookingObserver::class);

        Event::listen(BookingCheckedOut::class, CreateCleaningTask::class);

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn (): View => view('partials.impersonation-banner'),
        );
    }
}
