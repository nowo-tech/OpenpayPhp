# Upgrading

## Table of contents

- [From 1.0.0 to 1.0.1](#from-100-to-101)

## From 1.0.0 to 1.0.1

No breaking changes. **No application upgrade steps.**

```bash
composer update nowo-tech/openpay-php
```

## From 1.0.0 to 1.0.1

No breaking changes. **No application upgrade steps.**

```bash
composer update nowo-tech/openpay-php
```

# Upgrading to 1.0.0

1.0.0 is a **clean-room rewrite**. There is no drop-in BC with `Openpay\` / `openpay/sdk`.

```json
{
  "require": {
    "nowo-tech/openpay-php": "^1.0"
  }
}
```

| Old | New |
|-----|-----|
| `Openpay\Data\Openpay::getInstance()` | `new Client(new Credentials(...))` |
| `Openpay::reset()` / `OpenpaySession` | `Session::run()` per request |
| `replace openpay/sdk` | Removed |
| Apache-2.0 fork tree | MIT clean-room |
