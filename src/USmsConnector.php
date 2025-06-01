<?php

namespace ClassAtlas\USms;

use Saloon\Http\Auth\HeaderAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Traits\Plugins\AcceptsJson;

use function config;

class USmsConnector extends Connector
{
    use AcceptsJson;

    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->headers()->add('Content-Type', 'application/json');
    }

    public function resolveBaseUrl(): string
    {
        return config('usms.api_url');
    }

    protected function defaultAuth(): HeaderAuthenticator
    {
        return new HeaderAuthenticator(config('usms.api_key'), 'key');
    }
}
