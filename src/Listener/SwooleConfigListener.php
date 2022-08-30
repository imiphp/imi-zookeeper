<?php

declare(strict_types=1);

namespace Imi\ZooKeeper\Listener;

use Imi\Log\Log;
use Imi\ZooKeeper\Exception\ZooKeeperException;
use Imi\ZooKeeper\Util\SwooleZookeeperUtil;
use Psr\Log\LogLevel;
use swoole\zookeeper;

class SwooleConfigListener implements IConfigListener
{
    protected zookeeper $client;

    protected ListenerConfig $listenerConfig;

    protected bool $running = false;

    protected array $listeningLists = [];

    public function __construct(zookeeper $client, ListenerConfig $listenerConfig)
    {
        $this->client = $client;
        $this->listenerConfig = $listenerConfig;
        $client->setWatcher(function (zookeeper $client, string $path) {
            if (isset($this->listeningLists[$path]))
            {
                try
                {
                    $result = $client->get($path);
                    if (false === $result)
                    {
                        SwooleZookeeperUtil::checkErrorCode($client->errCode);
                    }

                    $this->listeningLists[$path]['value'] = $result ?: '';

                    if (isset($this->listeningLists[$path]['callback']))
                    {
                        $this->listeningLists[$path]['callback']($this, $path);
                    }
                    $this->saveCache($path, $this->listeningLists[$path]['value']);
                }
                finally
                {
                    $client->watch($path);
                }
            }
        });
    }

    public function pull(bool $force = true): void
    {
        $listeningLists = $this->listeningLists;
        foreach ($listeningLists as $key => $value)
        {
            try
            {
                if ($force || !$this->loadCache($key, $this->listenerConfig->getFileCacheTime()))
                {
                    $res = $this->client->get($key, $value['version'] ?? -1) ?: '';
                    $this->listeningLists[$key]['value'] = $res[$key] ?? '';
                    $this->saveCache($key, $this->listeningLists[$key]['value']);
                }
                else
                {
                    $this->loadCache($key);
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
        foreach ($this->listeningLists as $key => $_)
        {
            $this->client->watch($key);
        }
        while ($this->running)
        {
            if (!$this->listeningLists)
            {
                usleep(100_000);
                continue;
            }
            $this->polling();
        }
    }

    public function polling(): void
    {
        // 轮询监听的配置
        try
        {
            foreach ($this->listeningLists as $path => $value)
            {
                $result = $this->client->get($path);
                if (false === $result)
                {
                    SwooleZookeeperUtil::checkErrorCode($this->client->errCode);
                }

                $this->listeningLists[$path]['value'] = $result ?: '';

                if (isset($this->listeningLists[$path]['callback']))
                {
                    $this->listeningLists[$path]['callback']($this, $path);
                }
                $this->saveCache($path, $this->listeningLists[$path]['value']);
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

    protected function loadCache(string $key, int $fileCacheTime = 0): bool
    {
        $savePath = $this->listenerConfig->getSavePath();
        if ('' === $savePath)
        {
            return false;
        }

        $fileName = $savePath . '/zookeeper/' . $key;
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

        return true;
    }
}
