<?php

namespace ClassAtlas\USms\Tests\Support;

use Saloon\Data\RecordedResponse;
use Saloon\Http\Faking\Fixture;

/**
 * A fixture that never writes real data to the repo: the recording number is
 * swapped for a placeholder, brand names (they are customers' names) are
 * replaced, and response headers are reduced to the content type and uBill's
 * `Retry-After`.
 */
final class ScrubbedFixture extends Fixture
{
    private const int BRANDS_KEPT = 3;

    protected function beforeSave(RecordedResponse $recordedResponse): RecordedResponse
    {
        $retryAfter = null;

        foreach ($recordedResponse->headers as $name => $value) {
            if (strcasecmp((string) $name, 'Retry-After') === 0) {
                $retryAfter = is_array($value) ? reset($value) : $value;
            }
        }

        $recordedResponse->headers = ['Content-Type' => 'application/json'];

        if (is_scalar($retryAfter)) {
            $recordedResponse->headers['Retry-After'] = (string) $retryAfter;
        }

        $body = (string) $recordedResponse->data;
        $payload = json_decode($body, true);

        if (is_array($payload) && is_array($payload['brands'] ?? null)) {
            $brands = array_slice($payload['brands'], 0, self::BRANDS_KEPT);

            foreach ($brands as $index => $brand) {
                $brands[$index]['name'] = 'Brand'.($index + 1);
            }

            $payload['brands'] = $brands;

            $body = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        $recordedResponse->data = $this->scrubNumber($body);

        return $recordedResponse;
    }

    private function scrubNumber(string $body): string
    {
        $number = RecordingMode::number();

        if ($number === RecordingMode::PLACEHOLDER_NUMBER) {
            return $body;
        }

        // uBill returns some numbers without the country code.
        $local = substr($number, -9);
        $placeholderLocal = substr(RecordingMode::PLACEHOLDER_NUMBER, -9);

        return str_replace(
            [$number, $local],
            [RecordingMode::PLACEHOLDER_NUMBER, $placeholderLocal],
            $body,
        );
    }
}
