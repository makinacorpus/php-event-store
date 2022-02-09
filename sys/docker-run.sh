#!/bin/bash

echo "If tests fail with lowest PHP versions, please run:"
echo ""
echo "    composer update --prefer-lowest"
echo ""

echo "Running tests on PHP 8.0"
APP_DIR="`dirname $PWD`" docker-compose -p meventstore run php80 vendor/bin/phpunit "$@"

echo "Running tests on PHP 8.1"
APP_DIR="`dirname $PWD`" docker-compose -p meventstore run php81 vendor/bin/phpunit "$@"
