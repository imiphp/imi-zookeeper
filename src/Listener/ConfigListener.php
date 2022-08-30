<?php

declare(strict_types=1);

namespace Imi\ZooKeeper\Listener;

use Imi\Log\Log;
use Imi\ZooKeeper\Config\ZooKeeperConfigDriver;
use Imi\ZooKeeper\Exception\ZooKeeperException;
use Psr\Log\LogLevel;
use ZooKeeper;

class ConfigListener implements IConfigListener
{
    protected ZooKeeperConfigDriver $driver;

    protected ListenerConfig $listenerConfig;

    protected bool $running = false;

    protected array $listeningLists = [];

    protected ?ZooKeeper $client = null;

    public function __construct(ZooKeeperConfigDriver $driver, ListenerConfig $listenerConfig)
    {
        $this->driver = $driver;
        $this->listenerConfig = $listenerConfig;
    }

    public function pull(bool $force = true): void
    {
        $listeningLists = &$this->listeningLists;
        $client = $this->driver->getOriginClient();
        foreach ($listeningLists as $path => $_)
        {
            try
            {
                if ($force || !$this->loadCache($path, $this->listenerConfig->getFileCacheTime()))
                {
                    $result = $client->get($path) ?: '';
                    $listeningLists[$path]['value'] = $result;
                    $this->saveCache($path, $listeningLists[$path]['value']);
                }
                else
                {
                    $this->loadCache($path);
                }
            }
            catch (\Throwable $th)
            {
                Log::log(LogLevel::ERROR, sprintf('ZooKeeper pull failed: %s', $th));
            }
        }
    }

    public function get(string $key): string
    {
        return $this->listeningLists[$key]['value'] ?? '';
    }

    public function getParsed(string $key): array
    {
        return json_decode($this->listeningLists[$key]['value'], true) ?? [];
    }

    public function addListener(string $key, ?callable $callback = null): void
    {
        $this->listeningLists[$key] = [
            'value'    => '',
            'callback' => $callback,
        ];
    }

    public function removeListener(string $key): void
    {
        if (isset($this->listeningLists[$key]))
        {
            unset($this->listeningLists[$key]);
        }
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function start(): void
    {
        $this->running = true;
        $client = $this->driver->getOriginClient();
        foreach ($this->listeningLists as $path => $_)
        {
            $client->get($path, fn (int $i, int $type, string $path) => $this->watcher($client, $i, $type, $path));
        }
        // @phpstan-ignore-next-line
        while ($this->running)
        {
            sleep(1);
        }
    }

    public function polling(): void
    {
        // 轮询监听的配置
        try
        {
            $client = ($this->client ??= $this->driver->getOriginClient());
            $listeningLists = &$this->listeningLists;
            foreach ($listeningLists as $path => $_)
            {
                $result = $client->get($path);

                $listeningLists[$path]['value'] = $result ?: '';

                if (isset($listeningLists[$path]['callback']))
                {
                    $listeningLists[$path]['callback']($this, $path);
                }
                $this->saveCache($path, $listeningLists[$path]['value']);
            }
        }
        catch (\Throwable $th)
        {
            Log::log(LogLevel::ERROR, sprintf('ZooKeeper listen failed: %s', $th));
            usleep($this->listenerConfig->getFailedTimeout() * 1000);
        }
    }

    protected function saveCache(string $key, string $value): bool
    {
        $savePath = $this->listenerConfig->getSavePath();
        if ('' === $savePath)
        {
            return false;
        }

        $fileName = $savePath . '/zookeeper';
        if (!is_dir($fileName))
        {
            mkdir($fileName, 0777, true);
        }
        $fileName .= '/' . $key;
        file_put_contents($fileName, $value);
        file_put_contents($fileName . '.meta', json_encode(['lastUpdateTime' => time()]));

        return true;
    }

    protected function loadCache(string $path, int $fileCacheTime = 0): bool
    {
        $savePath = $this->listenerConfig->getSavePath();
        if ('' === $savePath)
        {
            return false;
        }

        $fileName = $savePath . '/zookeeper/' . $path;
        if (!is_file($fileName))
        {
            return false;
        }
        $metaFileName = $fileName . '.meta';
        if (is_file($metaFileName))
        {
            $value = file_get_contents($metaFileName);
            if (false === $value)
            {
                throw new ZooKeeperException(sprintf('Failed to read the contents of file %s', $metaFileName));
            }
            $meta = json_decode($value, true);
            if (!$meta)
            {
                return false;
            }
        }
        else
        {
            $meta = [];
        }
        if ($fileCacheTime > 0 && (time() - ($meta['lastUpdateTime'] ?? 0) > $fileCacheTime))
        {
            return false;
        }
        $value = file_get_contents($fileName);
        if (false === $value)
        {
            throw new ZooKeeperException(sprintf('Failed to read the contents of file %s', $fileName));
        }
        $this->listeningLists[$path]['value'] = $value;

        return true;
    }

    protected function watcher(ZooKeeper $client, int $i, int $type, string $path): void
    {
        if ($this->running)
        {
            $callback = fn (int $i, int $type, string $path) => $this->watcher($client, $i, $type, $path);
        }
        else
        {
            $callback = null;
        }
        $result = $client->get($path, $callback);
        $this->listeningLists[$path]['value'] = $result ?: '';
        if (isset($this->listeningLists[$path]['callback']))
        {
            $this->listeningLists[$path]['callback']($this, $path);
        }
        $this->saveCache($path, $this->listeningLists[$path]['value']);
    }
}
