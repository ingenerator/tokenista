<?php


namespace Ingenerator;


class TokenistaValidationResult
{
    const INVALID_EXPIRED  = 'expired';
    const INVALID_TAMPERED = 'tampered';
    const VALID            = 'valid';

    /**
     * @var bool[]
     */
    private $errors = [];

    /**
     * @var \DateTimeImmutable
     */
    private $token_expiry;

    public function __construct(array $check_status, int $expiry_ts)
    {
        $this->errors       = array_filter($check_status);
        $this->token_expiry = (new \DateTimeImmutable)->setTimestamp($expiry_ts);
    }

    /**
     * Mostly used to check why a token is invalid
     *
     * Returns `valid` for a valid token, or a comma-separated list of failure codes if the token is invalid. This
     * is commonly used to allow app logic to have a single branch for an invalid token, but to set e.g. a log level
     * or user response differently if it's just that the token has expired rather than that the signature is incorrect
     * or the value corrupt/tampered.
     *
     * @return string
     */
    public function getStatusCodes(): string
    {
        if (empty($this->errors)) {
            return static::VALID;
        }

        return implode(',', array_keys($this->errors));
    }

    public function getTokenExpiry(): \DateTimeImmutable
    {
        return $this->token_expiry;
    }

    public function isExpired(): bool
    {
        return $this->errors[static::INVALID_EXPIRED] ?? FALSE;
    }

    public function isTampered(): bool
    {
        return $this->errors[static::INVALID_TAMPERED] ?? FALSE;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

}
