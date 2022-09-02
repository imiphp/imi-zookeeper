#!/usr/bin/env bash
cd /tmp && \
curl -L -o ext-zookeeper.tar.gz "https://github.com/swoole/ext-zookeeper/archive/master.tar.gz" && \
tar zxvf ext-zookeeper.tar.gz -C ./ && cd ./ext-zookeeper-master && \
phpx build -v && \
phpx install && \
docker-php-ext-enable swoole_zookeeper && \
php --ri swoole_zookeeper