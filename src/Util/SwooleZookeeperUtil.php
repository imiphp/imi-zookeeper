<?php

declare(strict_types=1);

namespace Imi\ZooKeeper\Util;

use Imi\ZooKeeper\Exception\ZooKeeperException;

class SwooleZookeeperUtil
{
    public const ERROR_CODE_MESSAGES = [
        -1   => 'ZSYSTEMERROR: System error',
        -2   => 'ZRUNTIMEINCONSISTENCY: A runtime inconsistency was found',
        -3   => 'ZDATAINCONSISTENCY: A data inconsistency was found',
        -4   => 'ZCONNECTIONLOSS: Connection to the server has been lost',
        -5   => 'ZMARSHALLINGERROR: Error while marshalling or unmarshalling data',
        -6   => 'ZUNIMPLEMENTED: Operation is unimplemented',
        -7   => 'ZOPERATIONTIMEOUT: Operation timeout',
        -8   => 'ZBADARGUMENTS: Invalid arguments',
        -9   => 'ZINVALIDSTATE: Invliad zhandle state',
        -100 => 'ZAPIERROR: Api error',
        -101 => 'ZNONODE: Node does not exist',
        -102 => 'ZNOAUTH: Not authenticated',
        -103 => 'ZBADVERSION: Version conflict',
        -108 => 'ZNOCHILDRENFOREPHEMERALS: Ephemeral nodes may not have children',
        -110 => 'ZNODEEXISTS: The node already exists',
        -111 => 'ZNOTEMPTY: The node has children',
        -112 => 'ZSESSIONEXPIRED: The session has been expired by the server',
        -113 => 'ZINVALIDCALLBACK: Invalid callback specified',
        -114 => 'ZINVALIDACL: Invalid ACL specified',
        -115 => 'ZAUTHFAILED: Client authentication failed',
        -116 => 'ZCLOSING: ZooKeeper is closing',
        -117 => 'ZNOTHING: (not error) no server responses to process',
        -118 => 'ZSESSIONMOVED: session moved to another server, so operation is ignored',
    ];

    private function __construct()
    {
    }

    public static function checkErrorCode(int $errorCode): void
    {
        if (0 !== $errorCode)
        {
            static::throwErrorCode($errorCode);
        }
    }

    public static function throwErrorCode(int $errorCode): void
    {
        throw new ZooKeeperException(self::ERROR_CODE_MESSAGES[$errorCode] ?? 'Unknown', $errorCode);
    }
}
