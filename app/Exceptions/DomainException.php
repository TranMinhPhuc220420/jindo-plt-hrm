<?php

namespace App\Exceptions;

use Exception;

/**
 * Domain rule failure mapped to a stable API error envelope.
 *
 * @see docs/04-backend/ERROR_HANDLING.md
 */
class DomainException extends Exception
{
    /**
     * @param  array<string, array<int, string>|string>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        string $message,
        protected string $errorCode,
        protected int $status = 422,
        protected ?array $errors = null,
        protected array $meta = [],
    ) {
        // Resolve Laravel lang catalogs (`lang/{locale}.json` / `lang/{locale}/*.php`).
        parent::__construct(__($message));
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, array<int, string>|string>|null
     */
    public function errors(): ?array
    {
        return $this->errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return $this->meta;
    }
}
