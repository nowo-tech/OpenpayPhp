<?php

declare(strict_types=1);

namespace Nowo\Openpay\Exception;

/**
 * Invalid merchant credentials or end-user IP.
 *
 * Extends {@see OpenpayException}, so existing `catch (OpenpayException)` blocks keep working.
 */
final class OpenpayConfigurationException extends OpenpayException
{
}
