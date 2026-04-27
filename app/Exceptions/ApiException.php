<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    private int $status;

    private array $errors;

    public function __construct(string $message, int $status = 400, array $errors = [])
    {
        parent::__construct($message, $status);

        $this->status = $status;
        $this->errors = $errors;
    }

    public static function make(string $message, int $status = 400, array $errors = []): self
    {
        return new self($message, $status, $errors);
    }

    public static function badRequest(): self
    {
        return new self('Bad request.', 400);
    }

    public static function unauthorized(): self
    {
        return new self('Unauthenticated.', 401);
    }

    public static function forbidden(): self
    {
        return new self('You are not allowed to perform this action.', 403);
    }

    public static function notFound(): self
    {
        return new self('Resource not found.', 404);
    }

    public static function conflict(): self
    {
        return new self('Request conflict.', 409);
    }

    public static function unprocessable(): self
    {
        return new self('The request could not be processed.', 422);
    }

    public static function badGateway(): self
    {
        return new self('Bad gateway.', 502);
    }

    public static function serverError(): self
    {
        return new self('Server error.', 500);
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
