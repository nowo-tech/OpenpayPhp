<?php

declare(strict_types=1);

namespace Nowo\Openpay\Exception;

/**
 * The HTTP transport failed (cURL or PSR-18 error, timeout, invalid header).
 *
 * Extends {@see OpenpayException}, so existing `catch (OpenpayException)` blocks keep working.
 */
final class OpenpayTransportException extends OpenpayException
{
}
