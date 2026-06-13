<?php

declare(strict_types=1);

namespace Lukman\Config;

class Config
{
    public function __construct(
        private Repository $repository = new Repository(),
        private EnvLoader $env = new EnvLoader(),
        private ConfigLoader $loader = new ConfigLoader(),
        private ConfigCache $cache = new ConfigCache(),
    ) {
    }

    public function repository(): Repository
    {
        return $this->repository;
    }

    public function env(): EnvLoader
    {
        return $this->env;
    }

    public function loader(): ConfigLoader
    {
        return $this->loader;
    }

    public function cache(): ConfigCache
    {
        return $this->cache;
    }

    public function loadEnv(string $path, bool $overwrite = false): self
    {
        $this->env->load($path, $overwrite);

        return $this;
    }

    public function loadDirectory(string $directory): self
    {
        $this->repository->replace(array_replace_recursive(
            $this->repository->all(),
            $this->loader->load($directory),
        ));

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->repository->get($key, $default);
    }

    public function set(string $key, mixed $value): self
    {
        $this->repository->set($key, $value);

        return $this;
    }

    public function has(string $key): bool
    {
        return $this->repository->has($key);
    }

    public function forget(string $key): self
    {
        $this->repository->forget($key);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->repository->all();
    }

    public function cacheTo(string $path): self
    {
        $this->cache->write($path, $this->repository->all());

        return $this;
    }

    public function loadCache(string $path): self
    {
        $this->repository->replace($this->cache->read($path));

        return $this;
    }

    public function string(string $key, ?string $default = null): ?string
    {
        return $this->repository->string($key, $default);
    }

    public function int(string $key, ?int $default = null): ?int
    {
        return $this->repository->int($key, $default);
    }

    public function float(string $key, ?float $default = null): ?float
    {
        return $this->repository->float($key, $default);
    }

    public function bool(string $key, ?bool $default = null): ?bool
    {
        return $this->repository->bool($key, $default);
    }

    /**
     * @param array<mixed>|null $default
     *
     * @return array<mixed>|null
     */
    public function array(string $key, ?array $default = null): ?array
    {
        return $this->repository->array($key, $default);
    }

    public function required(string $key): mixed
    {
        return $this->repository->required($key);
    }

    /**
     * @param array<string, mixed> $items
     */
    public function merge(array $items): self
    {
        $this->repository->merge($items);

        return $this;
    }

    /**
     * @param array<string, mixed> $items
     */
    public function defaults(array $items): self
    {
        $this->repository->defaults($items);

        return $this;
    }

    public function push(string $key, mixed $value): self
    {
        $this->repository->push($key, $value);

        return $this;
    }

    public function prepend(string $key, mixed $value): self
    {
        $this->repository->prepend($key, $value);

        return $this;
    }

    public function freeze(): self
    {
        $this->repository->freeze();

        return $this;
    }

    public function unfreeze(): self
    {
        $this->repository->unfreeze();

        return $this;
    }

    public function frozen(): bool
    {
        return $this->repository->frozen();
    }
}
