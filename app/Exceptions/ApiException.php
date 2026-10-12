<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Domain-level API exception.
 *
 * Carries a stable `error_code` (snake-uppercase) plus optional field-level
 * errors so the frontend can highlight the offending field without parsing
 * the human-readable message.
 */
class ApiException extends HttpException
{
    protected string $errorCode;

    /**
     * @var array<string, array<int, string>>
     */
    protected array $errors = [];

    /**
     * @param  array<string, array<int, string>|string>  $errors  field -> message(s)
     */
    public function __construct(
        string $errorCode,
        string $message,
        int $statusCode = 422,
        array $errors = [],
        array $headers = []
    ) {
        $this->errorCode = $errorCode;
        $this->errors = self::normalizeErrors($errors);
        $headers['X-Error-Code'] = $errorCode;

        parent::__construct($statusCode, $message, null, $headers);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param  array<string, mixed>  $errors
     * @return array<string, array<int, string>>
     */
    private static function normalizeErrors(array $errors): array
    {
        $normalized = [];
        foreach ($errors as $field => $messages) {
            if (is_string($messages)) {
                $normalized[$field] = [$messages];

                continue;
            }
            if (is_array($messages)) {
                $normalized[$field] = array_values(array_map(
                    static fn ($m) => is_string($m) ? $m : (string) $m,
                    $messages
                ));

                continue;
            }
            $normalized[$field] = [(string) $messages];
        }

        return $normalized;
    }

    public static function validation(string $errorCode, string $message, array $errors = []): self
    {
        return new self($errorCode, $message, 422, $errors);
    }

    public static function conflict(string $errorCode, string $message, array $errors = []): self
    {
        return new self($errorCode, $message, 409, $errors);
    }

    public static function forbidden(string $errorCode, string $message, array $errors = []): self
    {
        return new self($errorCode, $message, 403, $errors);
    }

    public static function notFound(string $errorCode, string $message, array $errors = []): self
    {
        return new self($errorCode, $message, 404, $errors);
    }
}
