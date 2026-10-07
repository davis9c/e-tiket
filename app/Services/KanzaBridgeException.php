<?php

namespace App\Services;

use RuntimeException;

/** An upstream failure. The message is intentionally safe for local logs. */
class KanzaBridgeException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?string $requiredScope = null,
        public readonly ?int $retryAfter = null,
        public readonly bool $invalidCredentials = false,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
