#!/bin/bash

MATOMO_USER="admin"
MATOMO_PASSWORD="yg668g7ABC453"
MATOMO_URL="http://matomo"

# Get stack information
CONTAINER_MATOMO=$(docker inspect -f '{{.Name}}' "$(docker-compose ps -q matomo)" | cut -c2-)
CONTAINER_WEB=$(docker inspect -f '{{.Name}}' "$(docker-compose ps -q web)" | cut -c2-)
COMPOSE_NETWORK=$(docker inspect --format='{{range $k, $v := .NetworkSettings.Networks}}{{printf "%s\n" $k}}{{end}}' "$CONTAINER_WEB" | xargs)

# Run logs parser using Python 3, fetch logs from
# _logs/httpd/ mount point
docker run \
  --rm \
  --network "$COMPOSE_NETWORK" \
  --volumes-from="$CONTAINER_MATOMO" \
  -v "$(pwd)"/_logs/httpd:/var/log/httpd \
  --link "$CONTAINER_MATOMO" \
  python:3-alpine python /var/www/html/misc/log-analytics/import_logs.py \
  --url="$MATOMO_URL" \
  --login="$MATOMO_USER" \
  --password="$MATOMO_PASSWORD" \
  --idsite=1 \
  --recorders=4 \
  /var/log/httpd/access_log.current

# Run maintenance tasks on the Matomo
docker-compose exec -T matomo ./console core:archive --force-all-websites --url="$MATOMO_URL"
