# Conformance example app (Laravel 12 + Blade). Build with the package folder
# as the context:
#   docker build -f example/Dockerfile .
FROM composer:2.10.2 AS composer
FROM php:8.3-cli-bookworm
RUN apt-get update -qq && apt-get install -y -qq --no-install-recommends git unzip >/dev/null \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1
WORKDIR /app
RUN composer create-project laravel/laravel:^12.0 . --prefer-dist --no-scripts --no-progress --quiet
# The package from this repo's own source, not from Packagist.
COPY composer.json /pkg/composer.json
COPY src/ /pkg/src/
COPY config/ /pkg/config/
RUN composer config repositories.privatus '{"type": "path", "url": "/pkg", "options": {"symlink": false, "versions": {"privatus/analytics": "1.0.0"}}}' \
    && composer require privatus/analytics:1.0.0 --no-progress --quiet
COPY example/routes/ routes/
COPY example/bootstrap/app.php bootstrap/app.php
COPY example/resources/views/ resources/views/
COPY example/app/ app/
RUN composer dump-autoload --optimize --quiet
# No .env file: settings come from here and from the suite (PRIVATUS_SITE_ID,
# PRIVATUS_SCRIPT_HOST). PRIVATUS_ENABLED forces the tag on in any environment.
ENV APP_ENV=production APP_DEBUG=false APP_KEY=base64:Y29uZm9ybWFuY2Vjb25mb3JtYW5jZWNvbmZvcm1hbmM= \
    LOG_CHANNEL=stderr SESSION_DRIVER=array CACHE_STORE=array PRIVATUS_ENABLED=true \
    PHP_CLI_SERVER_WORKERS=4 PORT=8080
EXPOSE 8080
# PHP's own server with several workers, so a browser's spare connections do not block it.
WORKDIR /app/public
CMD ["sh", "-c", "exec php -S 0.0.0.0:${PORT} ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]
