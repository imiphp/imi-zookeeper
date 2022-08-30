<?php

declare(strict_types=1);

namespace Imi\ZooKeeper\Listener;

interface IConfigListener
{
    public function pull(bool $force = true): void;

    public function get(string $key): string;

    public function getParsed(string $key): array;

    public function addListener(string $key, ?callable $callback = null): void;

    public function removeListener(string $key): void;

    public function stop(): void;

    public function start(): void;

    public function polling(): void;
}
