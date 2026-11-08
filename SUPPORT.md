# Support policy

This policy covers every public ClassAtlas package (`class-atlas/*` on Packagist). Each package's README shows the
versions it supports today; this page explains how that list is chosen and when it changes.

## Which versions we support

- **Laravel:** every major that still receives security fixes from Laravel
  ([Laravel's support policy](https://laravel.com/docs/releases#support-policy)).
- **PHP:** every version that still receives security fixes from PHP
  ([supported versions](https://www.php.net/supported-versions.php)) and is accepted by at least one Laravel major we
  support.
- **Other dependencies** (Saloon, spatie/laravel-data and the like): their current major. Minimum versions are what the
  code needs and are set by hand. Automated updates widen a version range; they never raise its minimum.

A new package starts with the versions that have at least six months of security support left, so it does not begin
life with a version about to be dropped.

## When versions are added and dropped

- **Added:** a new Laravel major or PHP version is supported within two weeks of its release, in a **minor** release.
- **Dropped:** a Laravel major or PHP version is dropped on the day its security support ends, never earlier, in a
  **minor** release with a "Removed" entry in the changelog. Nothing breaks for anyone: Composer keeps an app that is
  still on the old Laravel or PHP at the last release that fits it.
- **Major releases** are kept for changes to a package's own public API.

## What "supported" means

- A package's `composer.json` and its CI test matrix list exactly the same versions, and every combination is tested
  with both the lowest and the latest allowed dependencies.
- Bug and security fixes go into the latest release only. Older releases are not patched.

## Upcoming changes

The maintainers schedule each known date in advance (a new Laravel or PHP release, an end of security support), so
additions and removals happen on time. Every one is announced in the package's changelog.

## Getting help

Questions and bug reports go to the issues of the package concerned. Please include the package, Laravel and PHP
versions you use.
