#!/bin/bash
APP_DIR="`dirname $PWD`" docker-compose -p meventstore up -d --build --remove-orphans --force-recreate
