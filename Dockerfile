FROM php:7.0-cli
ARG COMPOSER_AUTH

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && \
    apt-get install -y zlib1g-dev git && \
    pecl install xdebug && \
    docker-php-ext-install sockets zip && \
    docker-php-ext-enable xdebug && \
    apt-get clean

# Install Composer.
COPY --from=composer:1.6 /usr/bin/composer /usr/bin/composer

# Root App folder
RUN mkdir /app
WORKDIR /app
VOLUME /app

ENTRYPOINT ["bash"]
