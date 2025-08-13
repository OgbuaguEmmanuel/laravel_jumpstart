<?php

namespace App\Exceptions;

use Exception;

class SocialAuthException extends Exception
{
    public array $context;

    public function __construct(string $message, int $code = 400, array $context = [])
    {
        parent::__construct($message, $code);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
