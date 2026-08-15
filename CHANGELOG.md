# Changelog

All notable changes to these reusable actions are documented in this file.

## [1.0.0] - 2026-08-15

First tagged release. Everything consuming these actions pinned `@main`, so a change here reached
every repo at once, with no way to hold a working version while a fix landed. Pinning `@v1` now
follows the newest 1.x, and `@v1.0.0` holds one exact revision.

### Added

- `tagged-release`, which releases the version a `VERSION` file names and refuses to release one
  that is already tagged. This repo now uses it on itself.

### Existing

- `commits` validates commit messages.
- `go/release` builds cross-platform binaries and publishes them.
- `js/pre-release`, `js/release`, `python/pre-release`, `python/release`, `php/pre-release`,
  `php/release`, `php/laravel/release` and `flutter/release` release from their package manifests.
- `php/coverage/check` and `php/coverage/update` track coverage between runs.
- `package/` remains as a deprecated alias for `php/`.
