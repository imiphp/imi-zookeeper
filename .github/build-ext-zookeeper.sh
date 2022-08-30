#!/usr/bin/env bash
cd /tmp && \
curl -L -o ext-zookeeper.tar.gz "https://github.com/Yurunsoft/ext-zookeeper/archive/20220728.tar.gz" && \
tar zxvf ext-zookeeper.tar.gz -C ./ && cd ./ext-zookeeper-20220728 && \
phpx build -v && \
phpx install && \
docker-php-ext-enable swoole_zookeeper && \
php --ri swoole_zookeeper