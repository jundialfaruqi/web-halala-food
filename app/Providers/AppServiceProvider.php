<?php

namespace App\Providers;

use App\Events\DeliveryCreated;
use App\Events\InvoiceCreated;
use App\Services\FcmService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPushNotificationListeners();
    }

    /**
     * Register FCM push notification listeners for real-time mobile alerts.
     */
    protected function registerPushNotificationListeners(): void
    {
        Event::listen(function (DeliveryCreated $event) {
            if ($event->delivery->courier_id) {
                FcmService::sendToCourier(
                    $event->delivery->courier_id,
                    'Surat Pengantaran Baru!',
                    "Surat jalan #{$event->delivery->delivery_number} untuk toko {$event->delivery->store?->name} telah ditugaskan.",
                    [
                        'type' => 'delivery',
                        'id' => (string) $event->delivery->id,
                        'delivery_id' => (string) $event->delivery->id,
                    ]
                );
            }
        });

        Event::listen(function (InvoiceCreated $event) {
            if ($event->invoice->courier_id) {
                FcmService::sendToCourier(
                    $event->invoice->courier_id,
                    'Faktur Pengantaran Baru!',
                    "Faktur #{$event->invoice->invoice_number} untuk toko {$event->invoice->store?->name} telah ditugaskan.",
                    [
                        'type' => 'invoice',
                        'id' => (string) $event->invoice->id,
                        'invoice_id' => (string) $event->invoice->id,
                    ]
                );
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
