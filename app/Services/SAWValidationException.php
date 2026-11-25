<?php

namespace App\Services;

use RuntimeException;

/** Dilempar SAWService::calculate() bila input tidak valid. Memuat semua pesan error sekaligus. */
class SAWValidationException extends RuntimeException
{
    /** @var list<string> */
    private array $errors;

    /** @param list<string> $errors */
    public function __construct(array $errors)
    {
        $this->errors = array_values($errors);

        parent::__construct('Input perhitungan SAW tidak valid: ' . implode(' | ', $this->errors));
    }

    /** @return list<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
