<?php

namespace ClassAtlas\USms\Tests;

use ClassAtlas\USms\USmsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            USmsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('usms.api_url', getenv('UBILL_API_URL') ?: 'https://api.ubill.dev');
        $app['config']->set('usms.api_key', getenv('UBILL_SMS_API_KEY') ?: 'test-api-key');
        $app['config']->set('usms.is_sending_disabled', false);
        $app['config']->set('usms.callback_key', 'test-callback-key');
        $app['config']->set('usms.timezone', 'Asia/Tbilisi');
        $app['config']->set('usms.logging_enabled', false);
    }
}
