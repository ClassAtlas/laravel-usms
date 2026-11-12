# Changelog

All notable changes to `class-atlas/laravel-usms` are listed here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow [Semantic Versioning](https://semver.org/).
Releases before 3.1.0 are described in the [GitHub releases](https://github.com/ClassAtlas/laravel-usms/releases).

## [Unreleased]

### Added

- uBill's limit on login codes. A send marked `otp` that uBill refuses for every number throws
  `USmsRejectedException` with `SendSmsCode::OTP_RECIPIENT_LIMITED` (47); its new `retryAfter()` and
  `rejectedNumbers()` say when and which. A send it takes while refusing some numbers returns `SendSmsData` with
  `acceptedCount`, `rejectedCount`, `rejectedNumbers` (`RejectedNumberData`, with `OtpRejectionReason`) and
  `isPartial()`. The channel's `USmsSendFailedData` gains `retryAfter`.
- Incoming SMS: `USms::inbound()`, `USms::inboundAll()` and `USms::inboundMessage()`, with `InboundFilterData`,
  `InboundPageData`, `InboundMetaData` and `InboundMessageData`.
- The `sms.inbound.received` callback, which uBill POSTs as JSON: `USmsCallbackRequest` takes it alongside the GET
  callbacks and returns `InboundReceivedData`; `USms::handleJsonCallback()` is its facade form.
- `SECURITY.md`: how to report a vulnerability privately.
- `CONTRIBUTING.md`: running the checks, recording fixtures against the live API, and how releases are made.

### Changed

- `SUPPORT.md` now covers this package only. It no longer speaks for every `class-atlas/*` package, and the rule for
  new packages, which never applied here, is gone. Which versions are supported, and when they are added and dropped,
  is unchanged.
- The README follows the ClassAtlas package standard: what the package does and its badges first, then configuration,
  one section per feature, errors, logging, versioning and what it does not cover. Running the tests and recording
  fixtures moved to `CONTRIBUTING.md`.
- `USmsChannel::send()` takes the notifiable as `object` instead of `mixed`. Laravel always passes an object.
- A delivery report entry without a readable number or status throws `UnexpectedValueException`, instead of a PHP
  warning.

### Removed

- The `ext-readline` requirement. The package never used it, and it stopped installs on PHP built without readline.

## [3.1.0] - 2026-11-08

### Added

- `SUPPORT.md`: which Laravel and PHP versions are supported, and when versions are added and dropped.
- A supported-versions table in the README.

### Changed

- Every supported combination of PHP (8.3, 8.4, 8.5) and Laravel (12, 13) is now tested, with both the lowest and the
  latest allowed dependencies. Until now only PHP 8.4 with Laravel 13 was.
- `spatie/laravel-data` is required from 4.20 instead of 4.23. The package works on 4.20, the first release that
  supports Laravel 13.
- Installing the package no longer downloads its tests, CI configuration and other development files.

[Unreleased]: https://github.com/ClassAtlas/laravel-usms/compare/v3.1.0...HEAD
[3.1.0]: https://github.com/ClassAtlas/laravel-usms/compare/v3.0.1...v3.1.0
