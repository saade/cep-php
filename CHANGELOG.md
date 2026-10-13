# Changelog

All notable changes to `cep-php` will be documented in this file.

## v1.2.0 - 2026-10-13

### v1.2.0

This is the last 1.x release. It makes lookups faster and more reliable; v2 will follow with breaking changes.

#### What's new

- **OpenCEP is queried first.** `Cep::get()` now asks [OpenCEP](https://opencep.com) and only falls back to the other providers when it can't resolve the CEP.
- **New providers**: `OpenCepProvider` and `WideNetProvider`.
- **`Cep::race()`**: race only the providers you choose, e.g. `Cep::race('28895-190', [ViaCepProvider::class, BrasilApiProvider::class])`.
- **Saloon 4 support.** The constraint is now `^3.0|^4.0`, so the package installs in a fresh project again.

#### Fixes

- **Providers are now really queried concurrently.** The fallback providers were being awaited one after another; the first one to answer now wins and the remaining requests are cancelled.
- **Correios**: addresses were read from the wrong part of the response and came back empty. UTF-8 responses are also handled now, not only ISO-8859-1.
- **Correios (alt)**: the request is sent with `tipoCEP=ALL`, and empty results and captcha responses are treated as failures.
- **ViaCEP and BrasilAPI**: a response without a CEP is treated as a failure instead of being returned as an empty address.
- **BrasilAPI** now uses the `v1` endpoint.

#### Behaviour changes

- Timeouts dropped from 30s (10s for BrasilAPI) to 5s per provider, and 3s for OpenCEP.
- A CEP with more than 8 digits returns `null` without sending any request.
- `$cep->provider` can now also be `open-cep` or `widenet`.

#### Internal

- Added a Pest test suite (`composer test`, and `composer test:live` against the real providers).

**Full Changelog**: https://github.com/saade/cep-php/compare/v1.1.0...v1.2.0

## v1.1.0 - 2024-04-23

**Full Changelog**: https://github.com/saade/cep-php/compare/v1.0.1...v1.1.0

## v1.0.1 - 2024-04-23

**Full Changelog**: https://github.com/saade/cep-php/compare/v1.0.0...v1.0.1

## v1.0.0 - 2023-10-26

**Full Changelog**: https://github.com/saade/cep-php/commits/v1.0.0
