#!/bin/bash

. /etc/profile.d/load-env-vars.sh

echo "Starting cache_purge.sh .."
nice -n 20 runuser -c /cache_purge.sh -s /bin/bash "$WWW_USER" &

echo "Starting run_apache.sh .."
bash /run-apache.sh
