# imi-zookeeper

[![Latest Version](https://img.shields.io/packagist/v/imiphp/imi-zookeeper.svg)](https://packagist.org/packages/imiphp/imi-zookeeper)
[![Php Version](https://img.shields.io/badge/php-%3E=7.4-brightgreen.svg)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=4.8.0-brightgreen.svg)](https://github.com/swoole/swoole-src)
[![IMI License](https://img.shields.io/github/license/imiphp/imi-zookeeper.svg)](https://github.com/imiphp/imi-zookeeper/blob/master/LICENSE)

## 介绍

此项目是 imi 框架的 ZooKeeper 组件。

> 正在开发中，随时可能修改，请勿用于生产环境！

**支持的功能：**

* [x] 配置中心

## 安装

### PHP 扩展

* Swoole 用户请安装 [swoole-zookeeper](https://github.com/swoole/ext-zookeeper) 扩展。

* 非 Swoole 用户请安装 [php-zookeeper](<https://github.com/php-zookeeper/php-zookeeper>) 扩展。

### 本组件

本项目可以使用composer安装，遵循psr-4自动加载规则，在你的 `composer.json` 中加入下面的内容:

```json
{
    "require": {
        "imiphp/imi-zookeeper": "~2.1.0"
    }
}
```

然后执行 `composer update` 安装。

## 使用说明

### 配置

`@app.beans`：

```php
[
    'ConfigCenter' => [
        // 'mode'    => \Imi\ConfigCenter\Enum\Mode::WORKER, // 工作进程模式
        'mode'    => \Imi\ConfigCenter\Enum\Mode::PROCESS, // 进程模式
        'configs' => [
            'zookeeper' => [
                'driver'  => \Imi\ZooKeeper\Config\SwooleZooKeeperConfigDriver::class, // Swoole 驱动
                // 'driver'  => \Imi\ZooKeeper\Config\ZooKeeperConfigDriver::class, // 非 Swoole 驱动
                // 客户端连接配置
                'client'  => [
                    'host'    => env('IMI_ZOOKEEPER_HOST', '127.0.0.1:2181'), // 主机名:端口
                    'timeout' => 10, // 网络请求超时时间，单位：秒
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
                    'zookeeper' => [
                        'key'   => 'imi-zooKeeper-key1',
                        'type'  => 'json', // 配置内容类型
                    ],
                ],
            ],
        ],
    ],
]
```

### 获取配置

```php
\Imi\Config::get('zookeeper'); // 对应 imi-zooKeeper-key1
```

### 写入配置

```php
/** @var \Imi\ConfigCenter\ConfigCenter $configCenter */
$configCenter = App::getBean('ConfigCenter');
$name = 'imi-zooKeeper-key1';
$value = json_encode(['imi' => 'niubi']);
$configCenter->getDriver('zookeeper')->push($name, $value);
```

## 社群

**imi 框架交流群：** 17916227 [![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://jq.qq.com/?_wv=1027&k=5wXf4Zq)

**微信群：**

<img src="https://github.com/imiphp/imi/raw/2.1/res/wechat.png" alt="imi" width="256px" />

**打赏赞助：** <https://www.imiphp.com/donate.html>

## 运行环境

* [PHP](https://php.net/) >= 7.4
* [Composer](https://getcomposer.org/) >= 2.0
* [Swoole](https://www.swoole.com/) >= 4.8.0
* [imi](https://www.imiphp.com/) >= 2.1

## 版权信息

`imi-zookeeper` 遵循 MIT 开源协议发布，并提供免费使用。
