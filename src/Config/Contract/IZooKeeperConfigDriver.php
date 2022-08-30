<?php

declare(strict_types=1);

namespace Imi\ZooKeeper\Config\Contract;

use Imi\ConfigCenter\Contract\IConfigDriver;

interface IZooKeeperConfigDriver extends IConfigDriver
{
    /**
     * {@inheritDoc}
     *
     * @return \Zookeeper|\swoole\zookeeper
     */
    public function getOriginClient();
}
