<?php

use ClassAtlas\USms\Tests\Support\RecordingMode;

it('keeps every fixture free of real numbers, brand names and headers', function (string $path): void {
    $fixture = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    expect(array_keys($fixture['headers']))->toBe(['Content-Type']);

    preg_match_all('/(?<!\d)(?:995)?5\d{8}(?!\d)/', (string) $fixture['data'], $numbers);

    expect(array_values(array_unique($numbers[0])))
        ->each->toBeIn([RecordingMode::PLACEHOLDER_NUMBER, substr(RecordingMode::PLACEHOLDER_NUMBER, -9)]);

    $payload = json_decode((string) $fixture['data'], true);

    foreach (is_array($payload) && is_array($payload['brands'] ?? null) ? $payload['brands'] : [] as $brand) {
        expect($brand['name'])->toMatch('/^Brand\d+$/');
    }
})->with(fn (): array => glob(__DIR__.'/../Fixtures/Saloon/*.json') ?: []);
