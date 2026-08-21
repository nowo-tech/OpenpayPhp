<?php

declare(strict_types=1);

namespace Nowo\Openpay;

/**
 * Openpay country / API host selection (public docs).
 */
enum Country: string
{
    case Mx = 'MX';
    case Co = 'CO';
    case Pe = 'PE';

    public function sandboxBaseUrl(): string
    {
        return match ($this) {
            self::Mx => 'https://sandbox-api.openpay.mx',
            self::Co => 'https://sandbox-api.openpay.co',
            self::Pe => 'https://sandbox-api.openpay.pe',
        };
    }

    public function productionBaseUrl(): string
    {
        return match ($this) {
            self::Mx => 'https://api.openpay.mx',
            self::Co => 'https://api.openpay.co',
            self::Pe => 'https://api.openpay.pe',
        };
    }
}
