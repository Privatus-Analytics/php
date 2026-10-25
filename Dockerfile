# Test image: official PHP 8.3 CLI with Composer copied from the official Composer image.
FROM composer:2.10.2 AS composer
FROM php:8.3-cli-bookworm
RUN apt-get update -qq && apt-get install -y -qq --no-install-recommends git unzip >/dev/null \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
