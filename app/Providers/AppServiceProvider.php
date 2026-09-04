<?php

namespace App\Providers;

use App\Contracts\Integrations\CalendarPort;
use App\Contracts\Integrations\MailPort;
use App\Contracts\Integrations\PaymentPort;
use App\Contracts\Integrations\RiskDataPort;
use App\Contracts\Integrations\SignaturePort;
use App\Integrations\UnconfiguredProvider;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn (): TenantContext => new TenantContext);
        foreach ([MailPort::class, CalendarPort::class, PaymentPort::class, SignaturePort::class, RiskDataPort::class] as $port) {
            $this->app->bind($port, UnconfiguredProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
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
