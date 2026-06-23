<?php

namespace App\Services;

class SalaryImportResult
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?array $data,
        public readonly int $statusCode,
    ) {}

    public static function success(string $message): self
    {
        return new self(
            success: true,
            message: $message,
            data: null,
            statusCode: 200,
        );
    }

    /**
     * @param  list<string>  $errors
     */
    public static function validationFailed(array $errors): self
    {
        return new self(
            success: false,
            message: 'Import gagal. Perbaiki kesalahan pada file Excel.',
            data: ['errors' => $errors],
            statusCode: 422,
        );
    }
}
