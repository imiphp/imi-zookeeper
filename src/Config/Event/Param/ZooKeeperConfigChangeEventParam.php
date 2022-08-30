<?php

declare(strict_types=1);

namespace Imi\ZooKeeper\Config\Event\Param;

use Imi\ConfigCenter\Event\Param\ConfigChangeEventParam;
use Imi\ZooKeeper\Listener\IConfigListener;

class ZooKeeperConfigChangeEventParam extends ConfigChangeEventParam
{
    protected ?IConfigListener $listener = null;

    public function __construct(string $eventName, array $data = [], ?object $target = null)
    {
        parent::__construct($eventName, $data, $target);
        $this->listener = $data['options']['listener'] ?? null;
    }

    public function getListener(): ?IConfigListener
    {
        return $this->listener;
    }
}
