<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(
        string $message,
        private readonly int $status = 400,
        private readonly array $errors = [],
    ) {
        parent::__construct($message, $status);
    }

    public static function make(string $message, int $status = 400, array $errors = []): self
    {
        return new self($message, $status, $errors);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
