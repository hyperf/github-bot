FROM php:7.1

MAINTAINER huangzhhui <huangzhwork@gmail.com>

# Timezone
RUN /bin/cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo 'Asia/Shanghai' > /etc/timezone

# Libs
RUN apt-get update \
    && apt-get install -y \
        curl \
        wget \
        git \
        zip \
        libz-dev \
        libssl-dev \
        libnghttp2-dev \
        libpcre3-dev \
    && apt-get clean \
    && apt-get autoremove

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer self-update --clean-backups

# Bcmath extension
RUN docker-php-ext-install bcmath

# Swoole extension
RUN wget https://github.com/swoole/swoole-src/archive/v4.2.1.tar.gz -O swoole.tar.gz \
    && mkdir -p swoole \
    && tar -xf swoole.tar.gz -C swoole --strip-components=1 \
    && rm swoole.tar.gz \
    && ( \
        cd swoole \
        && phpize \
        && ./configure --enable-openssl --enable-http2 \
        && make -j$(nproc) \
        && make install \
    ) \
    && rm -r swoole \
    && docker-php-ext-enable swoole

ADD . /var/www/swoft

WORKDIR /var/www/swoft

RUN composer install --no-dev \
    && composer dump-autoload -o \
    && composer clearcache

EXPOSE 80

ENTRYPOINT ["php", "/var/www/swoft/bin/swoft", "start"]
