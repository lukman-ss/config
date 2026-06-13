<?php

declare(strict_types=1);

namespace Lukman\Config;

use Lukman\Config\Exception\ConfigException;
use Lukman\Config\Exception\ConfigNotFoundException;

class ConfigLoader
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function load(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new ConfigNotFoundException("Config directory [{$directory}] not found.");
        }

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files, SORT_STRING);

        $configs = [];

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $name = pathinfo($file, PATHINFO_FILENAME);
            $configs[$name] = $this->loadFile($file);
        }

        return $configs;
    }

    /**
     * @return array<string, mixed>
     */
    public function loadFile(string $file): array
    {
        if (!is_file($file)) {
            throw new ConfigNotFoundException("Config file [{$file}] not found.");
        }

        $config = require $file;

        if (!is_array($config)) {
            throw new ConfigException("Config file [{$file}] must return an array.");
        }

        return $config;
    }
}
