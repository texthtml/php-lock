FROM php:7

WORKDIR /scripts

RUN apt-get update && apt-get install -y zlib1g-dev git
RUN docker-php-ext-install zip mbstring
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

ADD composer.lock /scripts/composer.lock
ADD composer.json /scripts/composer.json

RUN composer install

ENV PATH /scripts/vendor/bin/:$PATH
