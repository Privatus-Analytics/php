#!/bin/sh
# Runs tests, PHP-CS-Fixer (PSR-12), PHPStan and a package build in Docker
# (php:8.3-cli with Composer from the composer image). Usage: ./run-docker.sh
set -e
docker build -q -t privatus-analytics-php-test . >/dev/null
docker run --rm -v "$PWD":/app -w /app privatus-analytics-php-test sh -c '
  composer validate --strict
  composer update --no-interaction --no-progress --prefer-dist --quiet
  vendor/bin/phpunit
  vendor/bin/php-cs-fixer fix --dry-run --diff --show-progress=none
  vendor/bin/phpstan analyse --memory-limit=512M --no-progress
  mkdir -p build && composer archive --format=zip --dir=build --file=privatus-analytics --quiet
  unzip -l build/privatus-analytics.zip | tail -n 1
  rm -rf build
'
