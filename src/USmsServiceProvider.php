<?php

namespace ClassAtlas\USms;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class USmsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('usms')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(USmsConnector::class);

        $this->app->singleton(CallbackKeyVerifier::class, function ($app) {
            return new CallbackKeyVerifier(
                key: (string) $app['config']->get('usms.callback_key', ''),
            );
        });

        $this->app->singleton(CallbackHandler::class, function ($app) {
            return new CallbackHandler(
                verifier: $app->make(CallbackKeyVerifier::class),
                timezone: (string) $app['config']->get('usms.timezone', USms::PROVIDER_TIMEZONE),
            );
        });

        $this->app->singleton(USms::class, function ($app) {
            return new USms(
                connector: $app->make(USmsConnector::class),
                callbackHandler: $app->make(CallbackHandler::class),
            );
        });
    }
}
