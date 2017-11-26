FROM php:7.2-rc-cli
ARG COMPOSER_AUTH

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && \
    apt-get install -y zlib1g-dev git && \
    docker-php-ext-install sockets zip && \
    apt-get clean

# Install Composer.
COPY --from=composer:1.5 /usr/bin/composer /usr/bin/composer

# Root App folder
RUN mkdir /app
WORKDIR /app
ADD . /app

# Install dependencies.
RUN composer install --no-suggest --no-progress  --no-interaction

RUN ls -la

RUN rm -r /root/.composer/* /root/.composer
RUN ls -la /root

# Use to store the config inside a volume.
RUN mkdir /data && chmod 777 /data
VOLUME /data

ENTRYPOINT ["bash"]
