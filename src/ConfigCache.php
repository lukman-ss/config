<?php

declare(strict_types=1);

namespace Lukman\Config;

use Lukman\Config\Exception\ConfigException;
use Lukman\Config\Exception\ConfigNotFoundException;

class ConfigCache
{
    /**
     * @param array<string, mixed> $items
     */
    public function write(string $path, array $items): void
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new ConfigException("Cache directory [{$directory}] could not be created.");
        }

        $contents = "<?php\n\n"
            . "declare(strict_types=1);\n\n"
            . 'return ' . var_export($items, true) . ";\n";

        if (file_put_contents($path, $contents) === false) {
            throw new ConfigException("Cache file [{$path}] could not be written.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function read(string $path): array
    {
        if (!$this->exists($path)) {
            throw new ConfigNotFoundException("Cache file [{$path}] not found.");
        }

        $items = require $path;

        if (!is_array($items)) {
            throw new ConfigException("Cache file [{$path}] must return an array.");
        }

        return $items;
    }

    public function exists(string $path): bool
    {
        return is_file($path);
    }

    public function clear(string $path): void
    {
        if ($this->exists($path)) {
            unlink($path);
        }
    }
}
