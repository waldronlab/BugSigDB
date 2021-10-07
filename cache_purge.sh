#!/bin/bash

SCRIPT=$MW_HOME/maintenance/purgePage.php
logfileName=cache_purge_log

echo "Starting cache purge (in 60 seconds)..."
# Wait 10 seconds after the server starts up to give other processes time to get started
sleep 60
echo "Cache purger started"
while true; do
    logFilePrev="$logfileNow"
    logfileNow="$MW_LOG/$logfileName"_$(date +%Y%m%d)
    if [ -n "$logFilePrev" ] && [ "$logFilePrev" != "$logfileNow" ]; then
        /rotatelogs-compress.sh "$logfileNow" "$logFilePrev" &
    fi

    date >> "$logfileNow"
    # Purge the page
    echo "$MW_CACHE_PURGE_PAGE" | php "$SCRIPT" >> "$logfileNow"
    # Fetch the page to warm up the caches
    curl -I -XGET "http://localhost:8081/$MW_CACHE_PURGE_PAGE" >> "$logfileNow"

    # Wait some seconds to let the CPU do other things, like handling web requests, etc
    echo cache_purge_log waits for "$MW_CACHE_PURGE_PAUSE" seconds... >> "$logfileNow"
    sleep "$MW_CACHE_PURGE_PAUSE"
done
