<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiException extends HttpException
{
    protected string $errorCode;

    public function __construct(string $errorCode, string $message, int $statusCode = 422, array $headers = [])
    {
        $this->errorCode = $errorCode;
        $headers['X-Error-Code'] = $errorCode;

        parent::__construct($statusCode, $message, null, $headers);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public static function validation(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, 422);
    }

    public static function conflict(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, 409);
    }

    public static function forbidden(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, 403);
    }

    public static function notFound(string $errorCode, string $message): self
    {
        return new self($errorCode, $message, 404);
    }
}
