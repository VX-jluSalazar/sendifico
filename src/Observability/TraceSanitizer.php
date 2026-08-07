<?php

namespace Vx\Sendifico\Observability;

final class TraceSanitizer
{
    private const MASK = '[redacted]';
    private const MAX_DEPTH = 6;
    private const MAX_STRING_LENGTH = 500;

    private const SENSITIVE_KEY_PARTS = [
        'api_key',
        'apikey',
        'authorization',
        'bearer',
        'credential',
        'downloadurl',
        'email',
        'firstname',
        'fullname',
        'lastname',
        'password',
        'phone',
        'secret',
        'street',
        'trackingcarrierurl',
        'url',
    ];

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function sanitizePayload(array $payload): array
    {
        return $this->sanitizeArray($payload, 0);
    }

    public function sanitizeMessage(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        $message = trim($message);
        if ($message === '') {
            return '';
        }

        $message = preg_replace('/(x-api-key|api[_ -]?key|authorization|bearer)\s*[:=]\s*[^\s,;]+/i', '$1=' . self::MASK, $message) ?? $message;
        $message = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', self::MASK, $message) ?? $message;

        return $this->limitString($message);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $payload, int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return ['truncated' => true];
        }

        $sanitized = [];
        foreach ($payload as $key => $value) {
            $key = (string) $key;
            if ($this->isSensitiveKey($key)) {
                $sanitized[$key] = self::MASK;

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $depth + 1);

                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = $this->limitString($value);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '_'], '', $key));

        foreach (self::SENSITIVE_KEY_PARTS as $sensitivePart) {
            if (str_contains($normalized, strtolower(str_replace(['-', '_'], '', $sensitivePart)))) {
                return true;
            }
        }

        return false;
    }

    private function limitString(string $value): string
    {
        if (strlen($value) <= self::MAX_STRING_LENGTH) {
            return $value;
        }

        return substr($value, 0, self::MAX_STRING_LENGTH) . '...[truncated]';
    }
}
