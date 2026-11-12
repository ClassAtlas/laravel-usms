# Security policy

## Reporting a vulnerability

**Please do not open a public issue.** Report it privately through
[GitHub's vulnerability reporting](https://github.com/ClassAtlas/laravel-usms/security/advisories/new).

Include what you found, how to reproduce it, and the package version. Never include an API key, a callback key, a real
phone number or the text of a message sent to a real person: a test with Saloon's `MockClient` is enough to show any
problem with this package.

You will get an answer within a week. Once a fix is released, the advisory is published and you are credited, unless
you prefer not to be.

## What counts

Anything that lets someone send SMS at another's cost or learn something they should not, for example:

- a callback with a wrong or missing key that the package accepts;
- an API key, a callback key, a phone number or a message text reaching a log, an exception message or a dump;
- a request sent to uBill with numbers, texts or options other than the ones the caller set.

## Supported versions

Only the latest release receives security fixes. See [SUPPORT.md](SUPPORT.md).
