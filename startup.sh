#!/bin/sh
set -eu
cd /workspace
if curl -sf -o /dev/null --max-time 2 http://127.0.0.1:8080/; then
  exit 0
fi
export PATH="/workspace/.php:$PATH"
cd /workspace/geniuspace
php artisan serve --host=0.0.0.0 --port=8080 >>/tmp/app-startup.log 2>&1 &
