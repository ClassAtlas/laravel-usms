# uBill SMS for Laravel

[![Latest version](https://img.shields.io/packagist/v/class-atlas/laravel-usms.svg)](https://packagist.org/packages/class-atlas/laravel-usms)
[![Tests](https://github.com/ClassAtlas/laravel-usms/actions/workflows/tests.yml/badge.svg)](https://github.com/ClassAtlas/laravel-usms/actions/workflows/tests.yml)
[![Downloads](https://img.shields.io/packagist/dt/class-atlas/laravel-usms.svg)](https://packagist.org/packages/class-atlas/laravel-usms)
[![License](https://img.shields.io/packagist/l/class-atlas/laravel-usms.svg)](LICENSE)

A Laravel client for the [uBill SMS API](https://ubill.ge/en/api-docs): sends, personalized batches, delivery reports,
brand names and callbacks. Built on [Saloon](https://docs.saloon.dev) and
[Spatie Laravel Data](https://spatie.be/docs/laravel-data).

- Every failure throws; a returned object always means uBill accepted the request.
- uBill's status codes are typed per endpoint, because uBill gives the same number a different meaning on each.
- A notification listener that throws never makes the channel send, and bill, the same SMS twice.
- Logs never carry the API key, a phone number or a message text.

## Supported versions

| Package | Laravel | PHP           |
|---------|---------|---------------|
| 3.x     | 12, 13  | 8.3, 8.4, 8.5 |

Every combination is tested in CI, with both the lowest and the latest allowed dependencies. A Laravel or PHP version
is supported for as long as it receives security fixes; [SUPPORT.md](SUPPORT.md) says when versions are added and
dropped. Changes are listed in [CHANGELOG.md](CHANGELOG.md).

## Installation

```bash
composer require class-atlas/laravel-usms
```

The package provides a facade that is auto-registered via Laravel package discovery:

```php
use USms;

USms::balance();
```

Dependency injection works identically if you prefer it:

```php
public function __construct(private \ClassAtlas\USms\USms $usms) {}
```

### There is no sandbox

uBill has one environment. Every call reaches the real account: a send costs a real SMS, and registering or deleting a
brand name changes the account. Set `UBILL_SENDING_DISABLED=true` wherever real messages must not go out — `send()` and
`sendPersonalized()` then return `null` without making a request.

## Configuration

An app needs only its API key, and its callback key if it takes callbacks:

```env
UBILL_SMS_API_KEY=your-api-key
UBILL_CALLBACK_KEY=your-callback-key
```

Publish `config/usms.php` with `php artisan vendor:publish --tag=usms-config` if you want to change its defaults. Every
setting can also come from `.env`:

```env
UBILL_SMS_API_KEY=
UBILL_CALLBACK_KEY=                  # callback validation key; while empty every callback is rejected

UBILL_API_URL=https://api.ubill.dev
UBILL_SENDING_DISABLED=false         # true: send() returns null without calling uBill
UBILL_TIMEZONE=Asia/Tbilisi          # timezone of sendTime and of a callback's date

UBILL_TIMEOUT=30
UBILL_CONNECT_TIMEOUT=10

UBILL_LOG_REQUESTS=false             # see Logging
UBILL_LOG_CHANNEL=                   # defaults to the app's log channel
```

## Sending a message

```php
use ClassAtlas\USms\DataObjects\MessageData;

$sent = USms::send(new MessageData(
    brandId: 29,
    numbers: ['9955XXXXXXXX'],
    text: 'Hello from uBill!',
));

$sent->smsID; // keep this to look the message up later
```

Numbers are in international format without `00` or `+`.

### Send options

```php
USms::send(new MessageData(
    brandId: 29,
    numbers: ['9955XXXXXXXX'],
    text: 'Your code is 4821',
    stopList: false,                               // false sends even to a number on the stop list
    otp: true,                                     // highest priority; authentication codes only
    sendAt: now()->addHour(),                      // schedule the message
    callbackUrl: 'https://example.com/usms/cb',    // callback URL for this message alone
    notificationId: 'your-own-reference',          // carried to the events below
));
```

- `sendAt` takes any `DateTimeInterface` and is sent to uBill as wall-clock time in `usms.timezone`.
- `callbackUrl` only works once a default callback URL is configured in the uBill API application settings.
- uBill may restrict an account that marks ordinary messages as `otp`.

### Login codes and uBill's limit on them

uBill protects login codes (`otp: true`) number by number: a number still in its cooldown, or over its limit, is
refused. A refused number is neither sent nor charged, and the rest of the send goes on.

When every number is refused, the send throws `USmsRejectedException` with `SendSmsCode::OTP_RECIPIENT_LIMITED` (47):
`retryAfter()` says in how many seconds the first of them may be sent to again, and `rejectedNumbers()` names them.
When only some are refused, the send returns as usual and names them:

```php
$sent = USms::send($message);

if ($sent->isPartial()) {
    $sent->acceptedCount;              // queued and charged
    $sent->rejectedCount;

    foreach ($sent->rejectedNumbers as $rejected) {
        $rejected->index;              // the number's position in your request
        $rejected->number;
        $rejected->rejectionReason();  // OtpRejectionReason::OTP_COOLDOWN or OTP_NUMBER_LIMIT, null if new
        $rejected->retryAfter;         // seconds
    }
}
```

Send again only the refused numbers, once their wait has passed: sending the whole batch again would send the
accepted ones twice. The owner of the uBill API application can switch this protection off.

## A different text per number

One request, one `smsID` for the whole batch:

```php
use ClassAtlas\USms\DataObjects\PersonalizedMessageData;
use ClassAtlas\USms\DataObjects\RecipientData;

USms::sendPersonalized(new PersonalizedMessageData(
    brandId: 29,
    recipients: [
        new RecipientData('9955XXXXXXXX', 'Hello Nika'),
        new RecipientData('9955XXXXXXXX', 'Hello Giorgi'),
    ],
));
```

It takes the same `stopList`, `otp`, `sendAt` and `callbackUrl` options.

A large batch takes uBill longer to answer. Pass `timeout` (seconds) to give
that one request longer than `UBILL_TIMEOUT`; uBill allows up to 100:

```php
USms::sendPersonalized(new PersonalizedMessageData(brandId: 29, recipients: $recipients, timeout: 90));
```

## Laravel notifications

Implement `HasUsmsChannel` on the notification and add the channel to `via()`:

```php
use ClassAtlas\USms\Channels\USmsChannel;
use ClassAtlas\USms\Contracts\HasUsmsChannel;
use ClassAtlas\USms\DataObjects\MessageData;

class OrderShipped extends Notification implements HasUsmsChannel
{
    public function via(object $notifiable): array
    {
        return [USmsChannel::class];
    }

    public function toUSms(object $notifiable): MessageData
    {
        return new MessageData(
            brandId: 29,
            numbers: [$notifiable->phone_number],
            text: 'Your order has shipped.',
            notificationId: $this->id,
        );
    }
}
```

The channel announces the outcome with an event:

| Outcome                                   | What the channel does                                                        |
|-------------------------------------------|------------------------------------------------------------------------------|
| uBill accepted the message                | Dispatches `USmsWasSent` (`notificationId`, `sendSmsData`, `notificationType`, `parts`) |
| uBill took it but refused some login codes | Dispatches `USmsWasSent`; `sendSmsData->rejectedNumbers` names the refused numbers |
| Sending is disabled                       | Dispatches `USmsWasSent` with `sendSmsData` null                             |
| uBill refused it (bad number, no balance, every login code limited) | Dispatches `USmsSendFailed` (`statusID`, `code`, `message`, `retryAfter`) and does not throw |
| The message has no numbers                | Sends nothing and dispatches nothing: uBill would only refuse it             |
| Timeout, 5xx or rate limit                | Throws, so the queue retries the send                                        |

Once uBill has accepted a message, an exception thrown by a `USmsWasSent` listener is reported and not rethrown. A
failed job would be retried, and the retry would send — and bill — the same SMS again.

A timeout is not proof that nothing was sent, and uBill cannot be asked whether a request it never answered was
received. A retry after one can deliver the same SMS twice. Where that matters more than a message lost, send outside
the channel and do not retry a request that got no clear answer.

## Delivery reports

```php
$report = USms::deliveryReport($smsId);

foreach ($report->result as $item) {
    $item->number;
    $item->statusID;   // raw provider status
    $item->status();   // ?DeliveryStatus
    $item->isFinal();
}
```

`DeliveryStatus` cases: `SENT` (0), `RECEIVED` (1), `NOT_DELIVERED` (2), `AWAITING_STATUS` (3), `ERROR` (4).
`isFinal()` is true for `RECEIVED`, `NOT_DELIVERED` and `ERROR`; `isDelivered()` only for `RECEIVED`. A status uBill
adds later is kept as its raw `statusID`, has no enum case, and counts as not final.

## Incoming SMS

Messages sent to the company's short number can be read once the uBill API application has the `sms.inbound`
permission:

```php
use ClassAtlas\USms\DataObjects\InboundFilterData;

$page = USms::inbound();                       // the last 24 hours, oldest first, 50 at a time

foreach ($page->result as $message) {
    $message->eventID;   // uBill's id for the message, the same in the callback
    $message->from;      // the sender, local nine-digit format: 5XXXXXXXX
    $message->to;        // the short number
    $message->text;
    $message->noSms;     // the short number's "no SMS" policy, set by uBill's administrator
    $message->sendAt;    // ?CarbonImmutable, UTC
}

USms::inbound(new InboundFilterData(date: today()));                     // one whole day in usms.timezone
USms::inbound(new InboundFilterData(from: $from, to: $to, limit: 100)); // a window; `to` is exclusive

foreach (USms::inboundAll(new InboundFilterData(date: today())) as $message) {
    // every page of the day, read as you go (a LazyCollection)
}

USms::inboundMessage('29a24670-e631-4bf3-b593-ea35df838115');           // one message by its eventID
```

- A page's `meta` holds its window (`from`, `to`, in UTC), `limit`, `count`, `hasMore` and `nextCursor`. To read the
  next page yourself, pass `from`, `to` and `nextCursor` back, so the window stays fixed; `inboundAll()` does that.
- A filter takes a `date` or a `from`/`to` window, not both, and 1 to 100 messages a page; anything else is refused
  before the request.
- uBill answers an invalid filter with HTTP 400, a missing permission with 401, an IP it does not allow with 403 and
  an unknown cursor or `eventID` with 404. Each is thrown as a `USmsException` with that code.

## Callbacks

Callbacks are optional. uBill calls one HTTPS URL, set in its API application settings, for every event: a GET for a
delivery update or a brand activation, a POST with a JSON body for an incoming message. The package ships a
`USmsCallbackRequest` FormRequest that takes both. Type-hint it in your controller action and Laravel's container will
verify the callback key and parse the callback for you — the action only ever runs for a verified callback.

```php
use ClassAtlas\USms\DataObjects\Callbacks\BrandActivatedData;
use ClassAtlas\USms\DataObjects\Callbacks\DeliveryUpdatedData;
use ClassAtlas\USms\DataObjects\Callbacks\InboundReceivedData;
use ClassAtlas\USms\Http\USmsCallbackRequest;

Route::match(['get', 'post'], '/usms/callback', function (USmsCallbackRequest $request) {
    $callback = $request->callback();

    if ($callback instanceof DeliveryUpdatedData) {
        $callback->smsID;
        $callback->number;
        $callback->status();   // ?DeliveryStatus
        $callback->isFinal();  // nothing further will be reported
    }

    if ($callback instanceof BrandActivatedData) {
        $callback->brandID;
    }

    if ($callback instanceof InboundReceivedData) {
        // The same fields as an incoming message read from the API.
        $callback->eventID;
        $callback->text;
    }

    // Anything else arrives as UnknownCallbackData. Acknowledge it too:
    // uBill sends every future event to this same URL.

    return response('', 200);
})->name('usms.callback');
```

| Situation                                   | Response |
|---------------------------------------------|----------|
| Wrong or missing `key` (GET), or `X-Webhook-Token` and body `key` (POST) | 403 |
| `UBILL_CALLBACK_KEY` not configured         | 403      |
| Known event with a malformed payload        | 422      |
| Event this package does not model           | reaches the controller as `UnknownCallbackData` |

- A delivery callback that is not answered is retried for a second and a half and then never again, so an app that
  must know what became of every message reads `deliveryReport()` on a schedule instead, or as well. One report
  answers for every recipient of a personalized batch.
- An incoming message is delivered at least once: up to five attempts, after 1, 5 and 30 minutes and 2 hours, each
  given 10 seconds. Answer with a 2xx once it is stored, store its `eventID` with a unique constraint, and answer a
  repeat with success too.

If you'd rather keep verification explicit, the facade form still works:

```php
use ClassAtlas\USms\Exceptions\InvalidCallbackKeyException;

try {
    $callback = $request->isMethod('POST')
        ? USms::handleJsonCallback($request->json()->all(), $request->header('X-Webhook-Token'))
        : USms::handleCallback($request->query());
} catch (InvalidCallbackKeyException) {
    return response('', 403);
}
```

## Brand names and balance

```php
USms::listBrandName();           // BrandNameData — ->brands[] with id, name, authorized, createdAt
USms::deleteBrandName(123);
USms::balance();                 // SmsBalanceData — ->sms

// BrandNameCreateData — ->brandID
USms::createBrandName('MyBrand', 'https://mybrand.ge — payment reminders and notices to our customers');
```

uBill requires the `description` and refuses one longer than 500 characters. It should name the brand's website,
Facebook/Instagram page or application, and say how the brand name will be used.

## Errors

Every method throws on failure; a returned DTO always means success.

```php
use ClassAtlas\USms\Enums\SendSmsCode;
use ClassAtlas\USms\Exceptions\USmsException;
use ClassAtlas\USms\Exceptions\USmsRateLimitedException;
use ClassAtlas\USms\Exceptions\USmsRejectedException;

try {
    USms::send($message);
} catch (USmsRejectedException $e) {
    $e->responseCode();   // e.g. SendSmsCode::INSUFFICIENT_BALANCE, or null if uBill added a new code
    $e->statusId();       // the raw statusID
    $e->retryAfter();     // seconds, for login codes refused by uBill's limit
    $e->getMessage();     // uBill's own message
} catch (USmsRateLimitedException $e) {
    $e->retryAfter();     // seconds, when uBill says
} catch (USmsException $e) {
    $e->getCode();        // HTTP status — bad API key, gateway error…
}
```

| Exception                  | When                                                                 |
|----------------------------|----------------------------------------------------------------------|
| `USmsRejectedException`    | uBill understood the request and refused it (HTTP 200, non-zero `statusID`). Retrying gets the same answer. |
| `USmsRateLimitedException` | HTTP 429 or `statusID` 429                                           |
| `USmsException`            | Anything else uBill answered with: 401, 5xx, a non-JSON gateway page |

Both specific exceptions extend `USmsException`.

A network failure before uBill's answer starts to arrive is not a `USmsException`, and it comes in two shapes. A
connection that was never made (the host did not resolve or refused, the TLS handshake failed, or the connect timeout
ran out) surfaces as Saloon's `FatalRequestException`. A failure after the connection was made, the request timeout
among them, surfaces as PSR-18's `Psr\Http\Client\NetworkExceptionInterface` (Guzzle 8's `NetworkException`), which
Saloon does not wrap. Catch both to handle every network failure:

```php
use Psr\Http\Client\NetworkExceptionInterface;
use Saloon\Exceptions\Request\FatalRequestException;

try {
    USms::balance();
} catch (FatalRequestException|NetworkExceptionInterface $e) {
    // uBill could not be reached, or did not answer in time
}
```

A connection that drops or times out after uBill's response headers arrived is a `USmsException` instead: Saloon
reads it as a response, and the message names the HTTP status uBill had started to answer with. On Guzzle 7, Saloon
wraps every failure that has no response, timeouts at any point included, as `FatalRequestException`.

uBill reuses the same numbers with a different meaning on each endpoint (40 is "not enough SMS" on a send and
"brand name already added" on a brand create), so every endpoint has its own code enum: `SendSmsCode`,
`BrandNameCreateCode`, `BrandNameDeleteCode`, `DeliveryReportCode`.

## Logging

With `UBILL_LOG_REQUESTS=true`, every call writes two lines to `UBILL_LOG_CHANNEL` (the app's default channel when
empty): the method and URL of the request, then the status of the answer. Headers and bodies are never logged: they
carry the API key, phone numbers and message texts.

## Provider behaviour worth knowing

Observed against the live API in October 2026. Where it differs from the published docs, this is what happens.

- The brand list is returned under `brands`; the docs show `data`.
- A delivery report for an unknown `smsID` answers `statusID` 12 ("SMS not found"), which the docs do not list. It is
  modelled as `DeliveryReportCode::SMS_NOT_FOUND`.
- `SENT` (0) means uBill accepted the message, not that an operator has it: a scheduled message reports 0 before its
  send time, and an ordinary one can stay at 0 for a few minutes before turning `RECEIVED`.
- A `sendAt` in the past is not refused. The message is sent immediately.
- `sendTime` is read as Tbilisi time. A message scheduled for 09:32 `Asia/Tbilisi` was still `SENT` at 09:31 and
  `RECEIVED` within a minute of 09:32.
- Delivery reports return numbers without the country code (`555XXXXXX`), whatever format was sent.
- A personalized batch that names the same number twice sends two messages under one `smsID`; the report lists both.
- The API rate-limits, although the docs state no limit. A bad API key answers HTTP 401 with `statusID` 401.

### Delivery callbacks

Observed with a test API application whose callback address pointed at a capture endpoint.

- The callback is a GET whose query string carries `key`, `event`, `smsID`, `number`, `statusID` and `date`, in that
  order. `event` is `sms.delivery.updated`.
- A delivered message gets one callback, when it reaches the phone. Nothing is sent for `SENT`.
- While the phone is unreachable nothing is sent at all. A message to a phone that was offline for seven minutes
  stayed `SENT` in its delivery report throughout, never `AWAITING_STATUS`, and got its single `RECEIVED` callback
  once the phone was back.
- A message long enough to go out in several parts still gets one callback. A personalized batch gets one per entry,
  each carrying the batch's `smsID`.
- `number` comes without the country code (`555XXXXXX`), as in delivery reports. Match a callback by `smsID`.
- `date` is the time the message was delivered, in Tbilisi time. The callback itself arrived 8 to 45 seconds later.
- A per-message `callbackUrl` replaces the default address for that message.
- A callback answered with HTTP 500 was sent twice more within 1.5 seconds and then not again in the 15 minutes that
  followed. An update missed for longer than that is gone, so keep a fallback (`deliveryReport()`) for messages that
  never reach a final status.

Not yet observed: a callback for `NOT_DELIVERED` or `ERROR`, the `sms.brand.activated` callback, the limit on login
codes (`statusID` 47) and incoming messages. The last two are modelled on uBill's documented examples of November 2026.
The `date` is parsed leniently — a callback whose date cannot be read still yields its delivery status, with `date`
null.

## Versioning

The package follows [Semantic Versioning](https://semver.org):

- **Minor** releases add endpoints, options, response properties and enum cases, add support for a new Laravel or PHP
  version, and drop a Laravel or PHP version on the day its security support ends (see [SUPPORT.md](SUPPORT.md)).
- **Patch** releases fix bugs.
- **Major** releases remove or rename something public.

Everything this README shows is public. Classes marked `@internal` are not, and may change in any release.

## Not covered

| What                                                                  | Status      | Why                                                            |
|-----------------------------------------------------------------------|-------------|----------------------------------------------------------------|
| Sending with a GET request (`GET /v1/sms/send`)                       | Not planned | The POST send does the same, without the length limit of a URL |
| Sending XML (`/v1/sms/sendXml`)                                       | Not planned | The JSON send does the same                                    |
| uBill's Billing API (customers, accounts, payments)                   | Not planned | It belongs to uBill's billing product, not to SMS              |

Open an issue if you need something here sooner.

## Security

Report vulnerabilities privately, as [SECURITY.md](SECURITY.md) describes, never in a public issue.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md): running the checks, recording fixtures against the live API, and how releases
are made.

## License

MIT. See [LICENSE](LICENSE).
