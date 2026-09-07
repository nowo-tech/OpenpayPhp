# Openpay PHP SDK (Nowo)

[![CI](https://github.com/nowo-tech/OpenpayPhp/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/OpenpayPhp/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/openpay-php.svg?style=flat)](https://packagist.org/packages/nowo-tech/openpay-php) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php)](https://php.net)

> ⭐ **Found this useful?** [Install from Packagist](https://packagist.org/packages/nowo-tech/openpay-php) · Star on [GitHub](https://github.com/nowo-tech/OpenpayPhp).

Clean-room PHP SDK for the **public Openpay REST API**. Namespace `Nowo\Openpay\`. License **MIT**. Release **1.0.2**.

Independent implementation from public docs — **not** a redistribution of `openpay/sdk`. No process-wide static merchant credentials (FrankenPHP worker-safe).

![FrankenPHP Friendly Worker Mode](docs/images/frankenphp-friendly.png)

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Upgrading](docs/UPGRADING.md)
- [Security](docs/SECURITY.md)
- [Coverage](docs/COVERAGE.md)
- [FrankenPHP demo](docs/DEMO-FRANKENPHP.md)
- [Spec-driven development](docs/SPEC-DRIVEN-DEVELOPMENT.md)
- [Spec Kit](docs/SPEC-KIT.md)
- [GitHub CI](docs/GITHUB_CI.md)
- [Changelog](docs/CHANGELOG.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)

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
);

$session = new Session($credentials);
$customer = $session->run(static fn (Client $client) => $client->customers->create([
    'name' => 'Demo',
    'email' => 'demo@example.test',
]));
```

## Install

```bash
composer require nowo-tech/openpay-php:^1.0
```

## Tests

```bash
composer test
composer test-coverage
make qa
```

## License

**MIT** — see [LICENSE](LICENSE).

Independent clean-room client for the public Openpay REST API. Not a redistribution of `openpay/sdk`. “Openpay” is a trademark of its owners.
