#!/bin/bash

# Import environment variables from .env
# we mainly interested in MATOMO_USER and MATOMO_PASSWORD
if [ -f .env ]
then
  export $(cat .env | sed 's/#.*//g' | xargs)
fi

# Get stack information
CONTAINER_MATOMO=$(docker-compose ps matomo | tail -n +2 | sed 's/------------------------------------------------------------------------------------//g' | awk '{ print $1 }' | xargs)
CONTAINER_WEB=$(docker-compose ps web | tail -n +2 | sed 's/^------------------------------------------------------------------------------------//g' | awk '{ print $1 }' | xargs)
COMPOSE_NETWORK=$(docker inspect --format='{{range $k, $v := .NetworkSettings.Networks}}{{printf "%s\n" $k}}{{end}}' $CONTAINER_WEB | xargs)

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
docker-compose exec matomo ./console core:archive --force-all-websites --url="$MATOMO_URL"
