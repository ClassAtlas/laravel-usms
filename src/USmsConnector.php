<?php

namespace ClassAtlas\USms;

use ClassAtlas\USms\Support\Settings;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Saloon\Http\Auth\HeaderAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\AcceptsJson;

use function config;

class USmsConnector extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return Settings::string('usms.api_url');
    }

    protected function defaultAuth(): HeaderAuthenticator
    {
        return new HeaderAuthenticator(Settings::string('usms.api_key'), 'key');
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function defaultConfig(): array
    {
        return [
            'connect_timeout' => Settings::integer('usms.connect_timeout', 10),
            'timeout' => Settings::integer('usms.timeout', 30),
        ];
    }

    /**
     * Attach opt-in request/response logging.
     *
     * Intentionally minimal: only method, url and status are logged. Headers
     * and bodies are NEVER included — they carry the API key, recipient phone
     * numbers and message text. tests/Unit/USmsLoggingTest.php locks this in.
     */
    public function boot(PendingRequest $pendingRequest): void
    {
        if (! (bool) config('usms.logging_enabled', false)) {
            return;
        }

        $channel = Settings::nullableString('usms.log_channel');

        $logger = $channel === null ? Log::getFacadeRoot() : Log::channel($channel);

        if (! $logger instanceof LoggerInterface) {
            return;
        }

        $pendingRequest->middleware()->onRequest(function (PendingRequest $request) use ($logger): void {
            $logger->info('[USms] request', [
                'method' => $request->getMethod()->value,
                'url' => $request->getUrl(),
            ]);
        });

        $pendingRequest->middleware()->onResponse(function (Response $response) use ($logger): void {
            $logger->info('[USms] response', [
                'status' => $response->status(),
                'url' => (string) $response->getPendingRequest()->getUrl(),
            ]);
        });
    }
}
