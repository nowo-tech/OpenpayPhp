<?php

declare(strict_types=1);

namespace Nowo\Openpay\Exception;

/**
 * The API answered with a body that is not valid JSON.
 *
 * Extends {@see OpenpayException}, so existing `catch (OpenpayException)` blocks keep working.
 */
final class OpenpayInvalidResponseException extends OpenpayException
{
}
