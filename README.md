# Openpay PHP SDK (Nowo)

[![CI](https://github.com/nowo-tech/OpenpayPhp/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/OpenpayPhp/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/openpay-php.svg?style=flat)](https://packagist.org/packages/nowo-tech/openpay-php) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/openpay-php.svg)](https://packagist.org/packages/nowo-tech/openpay-php) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php)](https://php.net) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/openpay-php.svg?style=social&label=Star)](https://github.com/nowo-tech/OpenpayPhp) [![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen)](#tests-and-coverage)

> ⭐ **Found this useful?** [Install from Packagist](https://packagist.org/packages/nowo-tech/openpay-php) · Star on [GitHub](https://github.com/nowo-tech/OpenpayPhp).

Clean-room PHP SDK for the **public Openpay REST API**. Namespace `Nowo\Openpay\`. License **MIT**. Release **1.1.4**.

Independent implementation from public docs — **not** a redistribution of `openpay/sdk`. No process-wide static merchant credentials (FrankenPHP worker-safe, including when the kernel is **not** reset between requests).

![FrankenPHP Friendly Worker Mode](docs/images/frankenphp-friendly.png)

This bundle is **FrankenPHP worker mode friendly**.

## Table of contents

- [Features](#features)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Requirements](#requirements)
- [Documentation](#documentation)
  - [Additional documentation](#additional-documentation)
- [Tests and coverage](#tests-and-coverage)
- [License](#license)

## Features

- Merchant resources: **charges**, **customers**, **cards**, **tokens**, **webhooks** (plus customer-scoped charges and cards).
- Request-scoped `Credentials` / `Session` / `Client` — no static merchant state, safe in FrankenPHP workers.
- Injectable `HttpClient` (default `CurlHttpClient` with connect/request timeouts and OS-store TLS), or any PSR-18 client through `Psr18HttpClient`.
- Typed API errors (`OpenpayApiException` with HTTP status, error code, category, request id).
- Countries: Mexico, Colombia, Peru (sandbox and production hosts).

## Installation

```bash
composer require nowo-tech/openpay-php:^1.1
```

See [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Quick start

```php
use Nowo\Openpay\Client;
use Nowo\Openpay\Country;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Session;

$credentials = new Credentials(
    merchantId: getenv('OPENPAY_MERCHANT_ID') ?: '',
    privateKey: getenv('OPENPAY_PRIVATE_KEY') ?: '',
    country: Country::Mx,
    sandbox: true,
    publicIp: $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
);

$session = new Session($credentials);
$customer = $session->run(static fn (Client $client) => $client->customers->create([
    'name' => 'Demo',
    'email' => 'demo@example.test',
]));
```

## Requirements

- PHP **8.3+** (`>=8.3 <8.6`) with `ext-curl` and `ext-json`
- No framework dependency (plain PHP library; the Symfony 8 demo is illustrative)

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Engram](docs/ENGRAM.md)
- [Spec-driven development](docs/SPEC-DRIVEN-DEVELOPMENT.md)
- [GitHub Spec Kit](docs/SPEC-KIT.md)

### Additional documentation

- [Coverage](docs/COVERAGE.md)
- [FrankenPHP demo](docs/DEMO-FRANKENPHP.md)
- [FrankenPHP worker mode audit](docs/FRANKENPHP-WORKER-AUDIT.md)
- [GitHub Actions CI requirements](docs/GITHUB_CI.md)
- [PSR evaluation (REQ-CS-007)](docs/PSR.md)
- [Branching](docs/BRANCHING.md)

## Tests and coverage

```bash
composer test
composer test-coverage
make qa
```

- Tests: PHPUnit (unit)
- PHP: **100%** lines (148/148; gate ≥ 99% via `make release-check`, see [docs/COVERAGE.md](docs/COVERAGE.md))
- TS/JS: N/A
- Python: N/A

## License

**MIT** — see [LICENSE](LICENSE).

Independent clean-room client for the public Openpay REST API. Not a redistribution of `openpay/sdk`. “Openpay” is a trademark of its owners.
