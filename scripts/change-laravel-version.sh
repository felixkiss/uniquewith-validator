#!/usr/bin/env bash

sed -i 's/\"illuminate\/support\"\: \"5\.\*\"/\"illuminate\/support\"\: \"'$LARAVEL_VERSION'\"/' composer.json
sed -i 's/\"illuminate\/validation\"\: \"5\.\*\"/\"illuminate\/validation\"\: \"'$LARAVEL_VERSION'\"/' composer.json
