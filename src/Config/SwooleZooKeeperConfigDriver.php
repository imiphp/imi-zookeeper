<?php

declare(strict_types=1);

namespace Imi\ZooKeeper\Config;

use Imi\Event\Event;
use Imi\ZooKeeper\Config\Contract\IZooKeeperConfigDriver;
use Imi\ZooKeeper\Config\Event\Param\ZooKeeperConfigChangeEventParam;
use Imi\ZooKeeper\Listener\ListenerConfig;
use Imi\ZooKeeper\Listener\SwooleConfigListener;
use Imi\ZooKeeper\Util\SwooleZookeeperUtil;
use swoole\zookeeper;

class SwooleZooKeeperConfigDriver implements IZooKeeperConfigDriver
{
    protected string $name = '';

    protected array $config = [];

    protected SwooleConfigListener $configListener;

    protected bool $listening = false;

    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;
        $this->configListener = new SwooleConfigListener($this->getOriginClient(), new ListenerConfig($config['listener'] ?? []));
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function push(string $key, string $value, array $options = []): void
    {
        $client = $this->getOriginClient();
        if (!$client->set($key, $value, $options['version'] ?? -1))
        {
            if (!$client->exists($key))
            {
                if ($client->create($key, $value))
                {
                    return;
                }
            }
            SwooleZookeeperUtil::checkErrorCode($client->errCode);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function pull(bool $enableCache = true): void
    {
        $this->configListener->pull(!$enableCache);
    }

    /**
     * 从配置中心获取配置原始数据.
     */
    public function getRaw(string $key, bool $enableCache = true, array $options = []): ?string
    {
        if ($enableCache)
        {
            return $this->configListener->get($key);
        }
        else
        {
            $client = $this->getOriginClient();
            $result = $client->get($key, $options['version'] ?? -1);
            if (!$result)
            {
                SwooleZookeeperUtil::checkErrorCode($client->errCode);
            }

            return $result ?: '';
        }
    }

    /**
     * 从配置中心获取配置处理后的数据.
     *
     * @return mixed
     */
    public function get(string $key, bool $enableCache = true, array $options = [])
    {
        $type = $options['type'] ?? null;
        $value = $this->getRaw($key, $enableCache, $options);
        switch ($type) {
            case 'json':
                return json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
            case 'xml':
                return simplexml_load_string($value, \SimpleXMLElement::class, \LIBXML_NOCDATA);
            case 'yml':
            case 'yaml':
                return yaml_parse($value);
            default:
                return $value;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete($keys, array $options = []): void
    {
        $client = $this->getOriginClient();
        foreach ($keys as $key)
        {
            if (!$client->delete($key, $options['version'] ?? -1))
            {
                SwooleZookeeperUtil::checkErrorCode($client->errCode);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function listen(string $imiConfigKey, string $key, array $options = []): void
    {
        $this->configListener->addListener($key, function (SwooleConfigListener $listener, string $path) use ($imiConfigKey) {
            Event::trigger('IMI.CONFIG_CENTER.CONFIG.CHANGE', [
                'driver'      => $this,
                'configKey'   => $imiConfigKey,
                'key'         => $path,
                'value'       => $listener->get($path),
                'parsedValue' => $listener->getParsed($path),
                'options'     => [
                    'listener' => $listener,
                ],
            ], $this, ZooKeeperConfigChangeEventParam::class);
        });
    }

    /**
     * 执行一次轮询配置.
     */
    public function polling(): void
    {
        $this->configListener->polling();
    }

    /**
     * 开始监听配置.
     */
    public function startListner(): void
    {
        $this->listening = true;
        $this->configListener->start();
    }

    /**
     * 停止监听配置.
     */
    public function stopListner(): void
    {
        $this->listening = false;
        $this->configListener->stop();
    }

    /**
     * 是否正在监听.
     */
    public function isListening(): bool
    {
        return $this->listening;
    }

    public function isSupportServerPush(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getOriginClient(): zookeeper
    {
        $clientConfig = $this->config['client'] ?? [];

        return new zookeeper($clientConfig['host'] ?? '127.0.0.1:2181', $clientConfig['timeout'] ?? 10);
    }
}
