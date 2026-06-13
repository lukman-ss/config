<?php

declare(strict_types=1);

namespace Lukman\Config;

use Lukman\Config\Exception\ConfigException;
use Lukman\Config\Exception\ConfigNotFoundException;

class Repository
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(
        private array $items = [],
        private bool $frozen = false,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        if (!str_contains($key, '.')) {
            return $default;
        }

        $items = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($items) || !array_key_exists($segment, $items)) {
                return $default;
            }

            $items = $items[$segment];
        }

        return $items;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureMutable();

        if (!str_contains($key, '.')) {
            $this->items[$key] = $value;
            return;
        }

        $segments = explode('.', $key);
        $last = array_pop($segments);
        $items = &$this->items;

        foreach ($segments as $segment) {
            if (!isset($items[$segment]) || !is_array($items[$segment])) {
                $items[$segment] = [];
            }

            $items = &$items[$segment];
        }

        $items[$last] = $value;
    }

    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->items)) {
            return true;
        }

        if (!str_contains($key, '.')) {
            return false;
        }

        $items = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($items) || !array_key_exists($segment, $items)) {
                return false;
            }

            $items = $items[$segment];
        }

        return true;
    }

    public function forget(string $key): void
    {
        $this->ensureMutable();

        if (array_key_exists($key, $this->items)) {
            unset($this->items[$key]);
            return;
        }

        if (!str_contains($key, '.')) {
            return;
        }

        $segments = explode('.', $key);
        $last = array_pop($segments);
        $items = &$this->items;

        foreach ($segments as $segment) {
            if (!isset($items[$segment]) || !is_array($items[$segment])) {
                return;
            }

            $items = &$items[$segment];
        }

        unset($items[$last]);
    }

    /**
     * @param array<string, mixed> $items
     */
    public function replace(array $items): void
    {
        $this->ensureMutable();

        $this->items = $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function string(string $key, ?string $default = null): ?string
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->get($key);

        if ($value === null || is_string($value)) {
            return $value;
        }

        throw new ConfigException("Config value [{$key}] must be a string.");
    }

    public function int(string $key, ?int $default = null): ?int
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->get($key);

        if ($value === null || is_int($value)) {
            return $value;
        }

        throw new ConfigException("Config value [{$key}] must be an integer.");
    }

    public function float(string $key, ?float $default = null): ?float
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->get($key);

        if ($value === null || is_float($value)) {
            return $value;
        }

        throw new ConfigException("Config value [{$key}] must be a float.");
    }

    public function bool(string $key, ?bool $default = null): ?bool
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->get($key);

        if ($value === null || is_bool($value)) {
            return $value;
        }

        throw new ConfigException("Config value [{$key}] must be a boolean.");
    }

    /**
     * @param array<mixed>|null $default
     *
     * @return array<mixed>|null
     */
    public function array(string $key, ?array $default = null): ?array
    {
        if (!$this->has($key)) {
            return $default;
        }

        $value = $this->get($key);

        if ($value === null || is_array($value)) {
            return $value;
        }

        throw new ConfigException("Config value [{$key}] must be an array.");
    }

    public function required(string $key): mixed
    {
        if (!$this->has($key)) {
            throw new ConfigNotFoundException("Required config value [{$key}] is missing.");
        }

        return $this->get($key);
    }

    /**
     * @param array<string, mixed> $items
     */
    public function merge(array $items): void
    {
        $this->ensureMutable();

        $this->items = $this->mergeRecursive($this->items, $items);
    }

    /**
     * @param array<string, mixed> $items
     */
    public function defaults(array $items): void
    {
        $this->ensureMutable();

        $this->items = $this->mergeRecursive($items, $this->items);
    }

    public function push(string $key, mixed $value): void
    {
        $this->ensureMutable();

        if (!$this->has($key)) {
            $this->set($key, [$value]);
            return;
        }

        $items = $this->get($key);

        if (!is_array($items)) {
            throw new ConfigException("Config value [{$key}] must be an array.");
        }

        $items[] = $value;
        $this->set($key, $items);
    }

    public function prepend(string $key, mixed $value): void
    {
        $this->ensureMutable();

        if (!$this->has($key)) {
            $this->set($key, [$value]);
            return;
        }

        $items = $this->get($key);

        if (!is_array($items)) {
            throw new ConfigException("Config value [{$key}] must be an array.");
        }

        array_unshift($items, $value);
        $this->set($key, $items);
    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    public function unfreeze(): void
    {
        $this->frozen = false;
    }

    public function frozen(): bool
    {
        return $this->frozen;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeRecursive($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    private function ensureMutable(): void
    {
        if ($this->frozen) {
            throw new ConfigException('Config repository is frozen.');
        }
    }
}
