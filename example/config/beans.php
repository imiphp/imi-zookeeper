<?php

declare(strict_types=1);

use function Imi\env;
use Imi\Util\Imi;

$rootPath = \dirname(__DIR__) . '/';

return [
    'hotUpdate'    => [
        'status'    => false, // 关闭热更新去除注释，不设置即为开启，建议生产环境关闭

        // --- 文件修改时间监控 ---
        // 'monitorClass'    =>    \Imi\HotUpdate\Monitor\FileMTime::class,
        'timespan'    => 1, // 检测时间间隔，单位：秒

        // --- Inotify 扩展监控 ---
        // 'monitorClass'    =>    \Imi\HotUpdate\Monitor\Inotify::class,
        // 'timespan'    =>    1, // 检测时间间隔，单位：秒，使用扩展建议设为0性能更佳

        // 'includePaths'    =>    [], // 要包含的路径数组
        'excludePaths'    => [
            $rootPath . '.git',
            $rootPath . 'bin',
            $rootPath . 'logs',
        ], // 要排除的路径数组，支持通配符*
    ],
    'ConfigCenter' => [
        // 'mode'    => \Imi\ConfigCenter\Enum\Mode::WORKER, // 工作进程模式
        // 'mode'    => \Imi\ConfigCenter\Enum\Mode::PROCESS, // 进程模式
        'mode'    => env('IMI_CONFIG_CENTER_MODE', \Imi\ConfigCenter\Enum\Mode::PROCESS),
        'configs' => [
            'zk' => [
                'driver'  => Imi::checkAppType('swoole') ? \Imi\ZooKeeper\Config\SwooleZooKeeperConfigDriver::class : \Imi\ZooKeeper\Config\ZooKeeperConfigDriver::class,
                // 客户端连接配置
                'client'  => [
                    'host'    => env('IMI_ZOOKEEPER_HOST', '127.0.0.1:2181'),
                    'timeout' => 10,
                ],
                // 监听器配置
                'listener' => [
                    'timeout'         => 30000, // 配置监听器长轮询超时时间，单位：毫秒
                    'failedWaitTime'  => 3000, // 失败后等待重试时间，单位：毫秒
                    'savePath'        => Imi::getRuntimePath('config-cache'), // 配置保存路径，默认为空不保存到文件。php-fpm 模式请一定要设置！
                    'fileCacheTime'   => 30, // 文件缓存时间，默认为0时不受缓存影响，此配置只影响 pull 操作。php-fpm 模式请一定要设置为大于0的值！
                    'pollingInterval' => 10000, // 客户端轮询间隔时间，单位：毫秒
                ],
                // 配置项
                'configs' => [
                    'zooKeeper' => [
                        'key'  => '/imi-zooKeeper-key1',
                        'type' => 'json',
                    ],
                ],
            ],
        ],
    ],
    'AutoRunProcessManager' => [
        'processes' => [
            'TestProcess',
        ],
    ],
    'ErrorLog' => [
        'exceptionLevel' => \E_ERROR | \E_PARSE | \E_CORE_ERROR | \E_COMPILE_ERROR | \E_USER_ERROR | \E_RECOVERABLE_ERROR,
    ],
];
