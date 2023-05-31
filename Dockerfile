ARG PHP_VERSION=8.2.0
ARG COMPOSER_VERSION=2.5.5

FROM composer:${COMPOSER_VERSION} AS composer

FROM php:${PHP_VERSION}-fpm-alpine AS builder

### SYMFONY REQUIREMENT
RUN apk add --no-cache icu-dev \
  && docker-php-ext-install intl \
  && docker-php-ext-enable intl \
  && docker-php-ext-install opcache \
  && docker-php-ext-enable opcache

COPY .docker/php/symfony.ini $PHP_INI_DIR/conf.d/
### END SYMFONY REQUIREMENT

COPY --from=composer /usr/bin/composer /usr/bin/composer

## SETUP RDKAFKA EXTESIONS @see https://arnaud.le-blanc.net/php-rdkafka-doc/phpdoc/rdkafka.setup.html
RUN set -xe \
    && apk add --no-cache --update --virtual .phpize-deps $PHPIZE_DEPS \
    librdkafka-dev \
    && pecl install rdkafka
COPY ./.docker/php/kafka.ini $PHP_INI_DIR/conf.d/

CMD ["php-fpm", "-F"]

EXPOSE 9000

WORKDIR /var/www/symfony

FROM builder AS local

## symfony cli install
RUN apk add --no-cache bash git
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash
RUN apk add symfony-cli
## END symfony cli install

HEALTHCHECK --interval=5s --timeout=3s --retries=3 CMD symfony check:req

## INSTALL PHP DETECTORS (PHPCPD & PHPMD)
RUN wget -c https://phar.phpunit.de/phpcpd.phar -O /usr/local/bin/phpcpd \
    && wget -c https://phpmd.org/static/latest/phpmd.phar -O /usr/local/bin/phpmd \
    && chmod +x /usr/local/bin/phpcpd /usr/local/bin/phpmd

## XDEBUG
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions xdebug-3.2.1;
COPY .docker/php/xdebug.ini $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini
## END XDEBUG

FROM builder AS ci
ENV APP_ENV=test

## INSTALL PHP DETECTORS (PHPCPD & PHPMD)
RUN wget -c https://phar.phpunit.de/phpcpd.phar -O /usr/local/bin/phpcpd \
    && wget -c https://phpmd.org/static/latest/phpmd.phar -O /usr/local/bin/phpmd \
    && chmod +x /usr/local/bin/phpcpd /usr/local/bin/phpmd

### INSTALL DEPENDENCIES WITH DEV REQUIREMENTS
COPY composer.json composer.lock symfony.lock ./
RUN set -eux; \
    composer install --prefer-dist --no-progress --no-scripts --no-interaction --optimize-autoloader;

### COPY ADDITIONAL PROJECT FILES AND DIRECTORY
COPY bin bin/
COPY config config/
COPY public public/
COPY src src/
COPY tests tests/
COPY .env .env.test .php-cs-fixer.dist.php phpstan.neon phpunit.xml.dist phpmd.xml.dist ./

### RUN COMPOSER SCRIPTS AND CLEAR CAHE
RUN set -eux; \
    composer run-script post-install-cmd \
    composer clear-cache

## ClEAN
RUN rm -rf /tmp/* /var/cache/apk/* /var/tmp/*
