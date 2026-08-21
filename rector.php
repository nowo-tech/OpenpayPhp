<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Openpay/Data/OpenpaySession.php',
        __DIR__ . '/Openpay/Data/OpenpayHttpTransport.php',
        __DIR__ . '/Openpay/Data/CurlHttpTransport.php',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true
    );
