<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;

abstract class Controller
{
    protected function throwApiException(string $message, int $status = 400, array $errors = []): never
    {
        throw ApiException::make($message, $status, $errors);
    }
}
