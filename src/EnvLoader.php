<?php

declare(strict_types=1);

namespace Lukman\Config;

use Lukman\Config\Exception\ConfigException;
use Lukman\Config\Exception\ConfigNotFoundException;

class EnvLoader
{
    /**
     * @return array<string, mixed>
     */
    public function load(string $path, bool $overwrite = false): array
    {
        if (!is_file($path)) {
            throw new ConfigNotFoundException("Env file [{$path}] not found.");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new ConfigException("Env file [{$path}] could not be read.");
        }

        $values = $this->parse($contents);

        foreach ($values as $key => $value) {
            if (!$overwrite && (array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER))) {
                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $this->stringValue($value));
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(string $contents): array
    {
        $values = [];
        $lines = preg_split('/\R/', $contents) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);

            if ($key === '') {
                continue;
            }

            $values[$key] = $this->parseValue(trim($value));
        }

        return $values;
    }

    private function parseValue(string $value): mixed
    {
        if ($value === '') {
            return '';
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            return substr($value, 1, -1);
        }

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => $this->parseNumber($value),
        };
    }

    private function parseNumber(string $value): mixed
    {
        if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
            return (int) $value;
        }

        if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
            return (float) $value;
        }

        return $value;
    }

    private function stringValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => (string) $value,
        };
    }
}
