<?php

declare(strict_types=1);

namespace Nowo\Openpay\Exception;

/**
 * The call was rejected locally before any request was sent (bad id, bad resource name, unavailable operation, unencodable payload).
 *
 * Extends {@see OpenpayException}, so existing `catch (OpenpayException)` blocks keep working.
 */
final class OpenpayInvalidRequestException extends OpenpayException
{
}
