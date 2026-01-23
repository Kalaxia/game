FROM php:8.4-fpm-alpine

# Install SUPERVISOR and dependencies
RUN apk add --update rabbitmq-c-dev icu-dev nodejs npm graphviz

# Install PHP extensions
RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
	&& docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql intl opcache \
    && pecl install amqp redis \
    && docker-php-ext-enable amqp pdo_mysql redis \
    && apk del pcre-dev ${PHPIZE_DEPS}

RUN curl -s https://getcomposer.org/installer | php
RUN mv /var/www/html/composer.phar /usr/bin/composer

WORKDIR /srv/app
