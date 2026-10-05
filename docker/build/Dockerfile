FROM php:8.5-alpine

RUN set -ex ; \
  cd /usr/local/etc/php/ && \
  mv php.ini-development php.ini && \
  true

COPY --from=ghcr.io/mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

# pcov collects the code coverage that mutation testing (docker/mutation) needs; it is off in every other process.
# php.ini-development keeps the arguments of every call in an exception's trace: a database password or a session id
# would reach the container's log, and so whoever reads it, so the arguments stay out of it here as they do in production
RUN set -e ; \
  apk add --no-cache less bash git mariadb-client unzip && \
  install-php-extensions opcache pdo_mysql intl pcov && \
  echo 'pcov.enabled=0' > /usr/local/etc/php/conf.d/zz-pcov-off.ini && \
  echo 'zend.exception_ignore_args=1' > /usr/local/etc/php/conf.d/zz-no-trace-arguments.ini && \
  true

RUN set -ex ; \
  addgroup -g 65526 httpgrp && \
  adduser -D -H -h /app -s /bin/sh -G httpgrp -u 1000 ampf-kit && \
  true

WORKDIR /app
USER ampf-kit
