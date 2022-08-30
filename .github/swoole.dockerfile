ARG SWOOLE_VERSION
ARG PHP_VERSION

FROM phpswoole/swoole:${SWOOLE_VERSION}-php${PHP_VERSION}

RUN set -eux \
    && apt update && apt install -y cmake libzookeeper-mt-dev

RUN docker-php-ext-install -j$(nproc) pcntl mysqli

RUN cd /tmp && \
    curl -L -o php-zookeeper.tar.gz "https://github.com/php-zookeeper/php-zookeeper/archive/master.tar.gz" && \
    tar zxvf php-zookeeper.tar.gz -C ./ && cd ./php-zookeeper-master && \
    phpize && ./configure && make -j install && \
    docker-php-ext-enable zookeeper
