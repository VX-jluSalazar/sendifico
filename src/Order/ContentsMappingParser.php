<?php

namespace Vx\Sendifico\Order;

final class ContentsMappingParser
{
    /**
     * @return array<int, string>
     */
    public static function parse(string $value): array
    {
        $rows = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $mapping = [];

        foreach ($rows as $row) {
            $normalizedRow = trim($row);
            if ($normalizedRow === '' || str_starts_with($normalizedRow, '#')) {
                continue;
            }

            $parts = preg_split('/\s*[:=]\s*/', $normalizedRow, 2) ?: [];
            if (count($parts) !== 2) {
                continue;
            }

            $id = (int) trim((string) $parts[0]);
            $content = trim((string) $parts[1]);

            if ($id <= 0 || $content === '') {
                continue;
            }

            $mapping[$id] = $content;
        }

        ksort($mapping);

        return $mapping;
    }

    public static function normalize(string $value): string
    {
        $mapping = self::parse($value);
        $rows = [];

        foreach ($mapping as $id => $content) {
            $rows[] = sprintf('%d=%s', $id, $content);
        }

        return implode("\n", $rows);
    }

    private function __construct()
    {
    }
}
