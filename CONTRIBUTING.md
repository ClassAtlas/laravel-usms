# Contributing

Thank you for helping. Bug reports and pull requests are welcome; for anything larger than a fix, open an issue first so
we can agree on the shape before you write it.

## Running the checks

```bash
composer update
composer test        # Pest, offline, from the committed fixtures
composer analyse     # Larastan, level 9
composer format      # Pint
```

CI runs the tests on every supported PHP and Laravel version with the lowest and the latest allowed dependencies, plus a
run on the next PHP version that never blocks a merge. Larastan, Pint and `composer validate --strict` run alongside. A
pull request needs `tests-passed` and `static-analysis` green.

| Tier         | Location          | Purpose                                                                                           |
|--------------|-------------------|---------------------------------------------------------------------------------------------------|
| **Unit**     | `tests/Unit/`     | Pure logic: enums, exception mapping, callback verification and parsing, logging, message length  |
| **Requests** | `tests/Requests/` | One file per Saloon request class — method, endpoint, body shape, DTO hydration, the error paths  |
| **Channels** | `tests/Channels/` | The notification channel: events, retries, the double-send guard                                  |
| **Http**     | `tests/Http/`     | `USmsCallbackRequest` through a real route                                                        |
| **Arch**     | `tests/ArchTest.php` | HTTP leaves only through the Saloon connector; no debugging calls                              |

## Fixtures

Response fixtures live under `tests/Fixtures/Saloon/`. The suite calls `MockConfig::throwOnMissingFixtures()` on
bootstrap — a missing fixture fails the test instead of silently hitting the network, so the default run is fully
offline. Error responses stay inline via `MockResponse::make([...])`.

Most fixtures are recordings of the live API. These are written by hand:

- `create-brand-name` and `delete-brand-name`: recording them would register or delete a real brand.
- `send-sms-otp-partial`: one login code per send cannot produce a partly refused send.
- `send-sms-otp-limited`: copied from uBill's documented example until it is recorded (its scenario is in
  `LiveSweepTest.php`; delete the fixture to record it, at the cost of one SMS).
- `inbound-sms`, `inbound-sms-empty` and `inbound-sms-message`: they need the `sms.inbound` permission and a message
  sent to a short number.

## Recording or refreshing a fixture

uBill has no sandbox, so there is no live suite in CI: it would need a real account's API key and would send real SMS.
Fixtures are recorded by hand, against a real account, and **a send fixture costs a real SMS**. Recording reads three
values from a `.env` file in the package root, which is git-ignored:

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
to the content type and `Retry-After`. A guard test fails on any fixture with another phone number, a brand name that is not `BrandN`, or
a header other than the content type and `Retry-After`. Read the fixture diff before you commit it.

## Pull requests

- One change per pull request, with a test that fails without it.
- Add a line under "Unreleased" in [CHANGELOG.md](CHANGELOG.md) for anything a user would notice.
- Never commit an API key, a callback key, a real phone number, a customer's brand name or a message sent to a real
  person.

## Releases

Releases are made by the maintainer only.

1. A pull request from `release/X.Y.Z` moves the "Unreleased" entries of `CHANGELOG.md` into a new version section with
   today's date, and updates the compare links at the bottom.
2. Check the release gate on the merged commit: `tests-passed` and `static-analysis` green.
3. Tag it, annotated, on `main`, and push the tag:

   ```bash
   git tag -a v3.2.0 -m "v3.2.0"
   git push origin v3.2.0
   ```

4. Publish the GitHub release from that tag with the changelog section as its notes; a `-beta.N` or `-rc.N` version is
   marked pre-release. Releases are immutable: a mistake is fixed by the next version, never by moving or deleting a
   tag.
