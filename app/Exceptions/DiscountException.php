<?php

namespace App\Exceptions;

use RuntimeException;

class DiscountException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $meta = []
    ) {
        parent::__construct($message);
    }

    public function toArray(): array
    {
        return [
            'error' => $this->getMessage(),
            'code' => $this->errorCode,
            'meta' => $this->meta,
        ];
    }
}
