<?php

namespace Padlet\Exception;

/** The API answered with an error: its HTTP status, and the raw body for the harness to show. */
class ApiException extends PadletException
{
    public function __construct(string $message, public readonly int $status, public readonly ?string $body = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }
}
