<?php

namespace ClassAtlas\USms\Tests\Support;

use Dotenv\Dotenv;
use Saloon\MockConfig;

/**
 * Gate Saloon's fixture machinery for the USms test suite.
 *
 * Default behaviour: if a fixture is missing, the suite fails loudly instead
 * of silently making a real network call. Set USMS_FIXTURE_RECORDING=1 to let
 * Saloon hit the real API and record any missing fixture on first run.
 *
 * uBill has no sandbox. Recording talks to the live account: a send fixture
 * costs a real SMS, delivered to UBILL_SAFE_SENDER.
 */
final class RecordingMode
{
    public const string PLACEHOLDER_NUMBER = '995555000000';

    public static function bootstrap(): void
    {
        if (self::isRecording()) {
            self::loadEnv();

            return;
        }

        MockConfig::throwOnMissingFixtures();
    }

    public static function isRecording(): bool
    {
        $flag = getenv('USMS_FIXTURE_RECORDING');

        return $flag !== false && $flag !== '' && $flag !== '0';
    }

    /**
     * The number test messages go to: the real one while recording, a
     * placeholder otherwise, so no real number is ever written to the repo.
     */
    public static function number(): string
    {
        $number = self::isRecording() ? getenv('UBILL_SAFE_SENDER') : false;

        return $number !== false && $number !== '' ? $number : self::PLACEHOLDER_NUMBER;
    }

    public static function brandId(): int
    {
        $brandId = self::isRecording() ? getenv('UBILL_CLASS_ATLAS_ID') : false;

        return $brandId !== false && $brandId !== '' ? (int) $brandId : 1;
    }

    /**
     * Pull UBILL_* vars from the package's .env into the process environment
     * so the tests talk to the real account during recording.
     */
    private static function loadEnv(): void
    {
        $envFile = realpath(__DIR__.'/../../.env');

        if ($envFile === false || ! is_readable($envFile)) {
            return;
        }

        foreach (Dotenv::parse((string) file_get_contents($envFile)) as $key => $value) {
            if (! str_starts_with($key, 'UBILL_') || $value === null) {
                continue;
            }

            // Do not overwrite vars already provided on the command line or
            // in the real environment.
            if (getenv($key) !== false && getenv($key) !== '') {
                continue;
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
