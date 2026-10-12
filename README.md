# USms

Laravel package for the [uBill SMS API](https://ubill.ge/api-docs). Built on [Saloon](https://docs.saloon.dev)
and [Spatie Laravel Data](https://spatie.be/docs/laravel-data).

---

## Installation

```bash
composer require class-atlas/laravel-usms
```

Add credentials to `.env`:

```env
UBILL_SMS_API_KEY=your-api-key
UBILL_CALLBACK_KEY=your-callback-key
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

---

## Quick Start

### 1. Send a message

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

### 2. Handle the callback

Callbacks are optional. A callback that is not answered is retried for a second and a half and then never again, so an
app that must know what became of every message reads `deliveryReport()` on a schedule instead, or as well. One report
answers for every recipient of a personalized batch. ClassAtlas itself reads reports only and takes no callbacks.

uBill reports delivery by calling one HTTPS URL, set in its API application settings, with a GET request. The package
ships a `USmsCallbackRequest` FormRequest. Type-hint it in your controller action and Laravel's container will verify
the callback key and parse the query string for you — the action only ever runs for a verified callback.

```php
use ClassAtlas\USms\DataObjects\Callbacks\BrandActivatedData;
use ClassAtlas\USms\DataObjects\Callbacks\DeliveryUpdatedData;
use ClassAtlas\USms\Http\USmsCallbackRequest;

Route::get('/usms/callback', function (USmsCallbackRequest $request) {
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

    // Anything else arrives as UnknownCallbackData. Acknowledge it too:
    // uBill sends every future event to this same URL.

    return response('', 200);
})->name('usms.callback');
```

| Situation                                   | Response |
|---------------------------------------------|----------|
| Wrong or missing `key`                      | 403      |
| `UBILL_CALLBACK_KEY` not configured         | 403      |
| Known event with a malformed payload        | 422      |
| Event this package does not model           | reaches the controller as `UnknownCallbackData` |

If you'd rather keep verification explicit, the facade form still works:

```php
use ClassAtlas\USms\Exceptions\InvalidCallbackKeyException;

try {
    $callback = USms::handleCallback($request->query());
} catch (InvalidCallbackKeyException) {
    return response('', 403);
}
```

---

## Advanced Usage

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

### A different text per number

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

### Laravel notifications

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
| Sending is disabled                       | Dispatches `USmsWasSent` with `sendSmsData` null                             |
| uBill refused it (bad number, no balance) | Dispatches `USmsSendFailed` (`statusID`, `code`, `message`) and does not throw |
| The message has no numbers                | Sends nothing and dispatches nothing: uBill would only refuse it             |
| Timeout, 5xx or rate limit                | Throws, so the queue retries the send                                        |

Once uBill has accepted a message, an exception thrown by a `USmsWasSent` listener is reported and not rethrown. A
failed job would be retried, and the retry would send — and bill — the same SMS again.

A timeout is not proof that nothing was sent, and uBill cannot be asked whether a request it never answered was
received. A retry after one can deliver the same SMS twice. Where that matters more than a message lost, send outside
the channel and do not retry a request that got no clear answer; ClassAtlas does so for everything a school sends its
guardians.

### Delivery reports

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

### Brand names and balance

```php
USms::listBrandName();           // BrandNameData — ->brands[] with id, name, authorized, createdAt
USms::createBrandName('MyBrand'); // BrandNameCreateData — ->brandID
USms::deleteBrandName(123);
USms::balance();                 // SmsBalanceData — ->sms
```

### Error handling

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

Both specific exceptions extend `USmsException`. Network failures surface as Saloon's `FatalRequestException`.

uBill reuses the same numbers with a different meaning on each endpoint (40 is "not enough SMS" on a send and
"brand name already added" on a brand create), so every endpoint has its own code enum: `SendSmsCode`,
`BrandNameCreateCode`, `BrandNameDeleteCode`, `DeliveryReportCode`.

---

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

Not yet observed: a callback for `NOT_DELIVERED` or `ERROR`, and the `sms.brand.activated` callback.
The `date` is parsed leniently — a callback whose date cannot be read still yields its delivery status, with `date`
null.

---

## Testing

```bash
composer install
vendor/bin/pest --compact
```

CI runs this suite on every push to `main` and on every pull request (`.github/workflows/tests.yml`).

| Tier         | Location          | Purpose                                                                                           |
|--------------|-------------------|---------------------------------------------------------------------------------------------------|
| **Unit**     | `tests/Unit/`     | Pure logic: enums, exception mapping, callback verification and parsing, logging, message length  |
| **Requests** | `tests/Requests/` | One file per Saloon request class — method, endpoint, body shape, DTO hydration, the error paths  |
| **Channels** | `tests/Channels/` | The notification channel: events, retries, the double-send guard                                  |
| **Http**     | `tests/Http/`     | `USmsCallbackRequest` through a real route                                                        |

### Fixtures

Response fixtures live under `tests/Fixtures/Saloon/`. The suite calls `MockConfig::throwOnMissingFixtures()` on
bootstrap — a missing fixture fails the test instead of silently hitting the network, so the default run is fully
offline. Error responses stay inline via `MockResponse::make([...])`.

All fixtures except `create-brand-name` and `delete-brand-name` are recordings of the live API. Those two are written
by hand, because recording them would register or delete a real brand.

### Recording or refreshing a fixture

Recording talks to the real account and **a send fixture costs a real SMS**. It reads three values from a `.env` file
in the package root, which is git-ignored:

```env
UBILL_SMS_API_KEY=your-api-key
UBILL_CLASS_ATLAS_ID=the-brand-id-to-send-from
UBILL_SAFE_SENDER=the-number-that-receives-the-test-messages
```

Delete the fixture you want to refresh, then run its scenario from `tests/Recording/LiveSweepTest.php`:

```bash
rm tests/Fixtures/Saloon/send-sms.json tests/Fixtures/Saloon/delivery-report.json
USMS_FIXTURE_RECORDING=1 vendor/bin/pest --filter='records a plain send'
```

> **Always pass `--filter`, and never delete `create-brand-name.json` or `delete-brand-name.json`.**
> With `USMS_FIXTURE_RECORDING=1` set, *any* test whose fixture is missing calls the live account, not only the
> scenarios in `LiveSweepTest.php`. Running the whole suite with the flag after deleting fixtures sends real SMS from
> the request tests. If one of the two brand fixtures is missing, it also registers a real brand named `MyBrand` or
> tries to delete brand `123`.

A sweep scenario whose fixture still exists is skipped. Fixtures are written through `ScrubbedFixture`: the recording
number becomes a placeholder, brand names (they are customers' names) are replaced, and response headers are reduced
to the content type.

---

## Config reference

Full list of `.env` overrides supported by `config/usms.php`:

```env
UBILL_SMS_API_KEY=
UBILL_CALLBACK_KEY=                  # callback validation key; while empty every callback is rejected

UBILL_API_URL=https://api.ubill.dev
UBILL_SENDING_DISABLED=false         # true: send() returns null without calling uBill
UBILL_TIMEZONE=Asia/Tbilisi          # timezone of sendTime and of a callback's date

UBILL_TIMEOUT=30
UBILL_CONNECT_TIMEOUT=10

UBILL_LOG_REQUESTS=false             # logs method, url and status only — never headers or bodies
UBILL_LOG_CHANNEL=                   # defaults to app log channel
```
