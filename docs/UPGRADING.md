# Upgrading

## Table of contents

- [From 1.0.2 to 1.1.0](#from-102-to-110)
- [From 1.0.1 to 1.0.2](#from-101-to-102)
- [From 1.0.0 to 1.0.1](#from-100-to-101)
- [From 3.x fork to 1.1.0](#from-3x-fork-to-110)
- [From 3.x fork to 1.0.0](#from-3x-fork-to-100)
- [3.2.0 → 3.2.1 (fork)](#320--321-fork)
- [3.1.1.1 → 3.2.0 (fork)](#3111--320-fork)

## From 1.0.2 to 1.1.0

Additive only. **No breaking changes** for existing `charges` / `customers` / `cards` / `tokens` callers.

```bash
composer update nowo-tech/openpay-php
```

New: `$client->webhooks` and `ResourceApi::add()` (= `create()`).

## From 1.0.1 to 1.0.2

No breaking changes. **No application upgrade steps.** Documentation and package identity only.

```bash
composer update nowo-tech/openpay-php
```

## From 1.0.0 to 1.0.1

No breaking changes. **No application upgrade steps.**

```bash
composer update nowo-tech/openpay-php
```

## From 3.x fork to 1.1.0

Prefer **1.1+** over 1.0 when migrating: webhooks are available on `Client`.

Still **not** a drop-in for `Openpay\Data\*` — rewrite credentials to `Credentials` + `Session::run()`, and use array responses:

| Old (3.x fork) | New (1.1.x) |
| --- | --- |
| `$openpay->webhooks->add($payload)` → object | `$client->webhooks->add($payload)` → `array` (`id`, `status`, …) |
| `$openpay->charges->create($payload)` → object | `$client->charges->create($payload)` → `array` |
| `$res->serializableData['payment_method']->url` | `$res['payment_method']['url']` (shape from Openpay JSON) |
| `$webhook->delete()` | `$client->webhooks->delete($id)` |

```json
{
  "require": {
    "nowo-tech/openpay-php": "^1.1"
  }
}
```

## From 3.x fork to 1.0.0

1.0.0 is a **clean-room rewrite**. There is no drop-in BC with `Openpay\` / `openpay/sdk` / Nowo fork `3.1.1.1`–`3.2.1`.

Do **not** change core-nowo (or any app using `Openpay\Data\Openpay` + `OpenpayApi::createRoot()`) to `^1.0`. That line needs the 3.x fork.

```json
{
  "require": {
    "nowo-tech/openpay-php": "^1.0"
  }
}
```

| Old (3.x fork) | New (1.0.x) |
| --- | --- |
| `Openpay\Data\Openpay::getInstance()` | `new Client(new Credentials(...))` |
| `Openpay::configure()` / `OpenpayApi::createRoot()` | `Client` + `Session::run()` |
| `Openpay::reset()` / `OpenpaySession` | `Session::run()` per request |
| `replace openpay/sdk` | Removed |
| Apache-2.0 fork tree | MIT clean-room |

Packagist no longer installs `3.1.1.1`. Until that tag is restored, pin the fork via VCS:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/nowo-tech/OpenpayPhp-fork-archive.git"
    }
  ],
  "require": {
    "nowo-tech/openpay-php": "dev-master"
  }
}
```

`dev-master` on the archive is **3.2.1** (`Openpay\`, `replace` `openpay/sdk` 3.1.1).

## 3.2.0 → 3.2.1 (fork)

Patch release. **No breaking Openpay API.** Keep `^3.2` **only if Packagist serves 3.2.x tags again**.

- User-Agent becomes `OpenpayPhp/3.2.1`.
- GitHub clone URL is `nowo-tech/OpenpayPhp` (Packagist stays `nowo-tech/openpay-php`).
- Nested resource cache ids that were integers no longer TypeError on PHP 8.
- After `reset()`, still call `configure()` / `getInstance($id, $apiKey, …)` /
  `configureFromEnvironment()` / `OpenpaySession` — same as 3.2.0.

## 3.1.1.1 → 3.2.0 (fork)

This is the first Nowo fork release after the Packagist rename. It stays a
drop-in for `openpay/sdk` **3.1.1** (`Openpay\\` namespaces, `replace` in
`composer.json`). Read this before bumping if you use env vars, a manual
install, or PHP older than 8.3.

### PHP 8.3 is required

`composer.json` now has `"php": ">=8.3"`.

`3.1.1.1` declared `>=8.1` but already used `#[Override]` (PHP 8.3), so 8.1/8.2
were already broken. If you are on 8.3+, only the Composer constraint changes.

### `reset()` no longer reads `OPENPAY_*`

After `Openpay::reset()`, `getApiKey()`, `getId()`, and `getSandboxMode()`
return the cleared statics. They do **not** fall back to
`OPENPAY_API_KEY` / `OPENPAY_MERCHANT_ID` / `OPENPAY_PRODUCTION_MODE`.

That stops a later merchant from inheriting the worker environment (php-fpm /
FrankenPHP).

**Before (leaked env after reset):**

```php
Openpay::reset();
// getApiKey() could still return getenv('OPENPAY_API_KEY')
```

**After — pick one per request:**

```php
Openpay::configure($id, $apiKey, $country, $publicIp);
// or
Openpay::getInstance($id, $apiKey, $country, $publicIp);
// or, env-only apps:
Openpay::reset();
Openpay::configureFromEnvironment();
```

**Recommended** on workers:

```php
use Openpay\Data\OpenpaySession;

$session = new OpenpaySession($id, $apiKey, 'MX', $publicIp);
$charge  = $session->run(fn ($openpay) => $openpay->charges->add($payload));
```

`OpenpaySession` always calls `reset()` in `finally`.

On a **fresh process** that never called `reset()`, empty statics still fall
back to `OPENPAY_*` (same as upstream). The change is only after `reset()` /
`configure()` / `getInstance()`.
