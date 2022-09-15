#!/bin/bash

logfileName=updateEFO_log

cd /updateEFO
pip install --no-cache-dir -r updateEFO.requirements.txt
mkdir -p /var/log/updateEFO/

if [ -z "$MW_BOT_USER" ]; then
  echo "MW_BOT_USER must be defined"
  exit 1
fi

if [ -z "$MW_BOT_PASSWORD" ]; then
  echo "MW_BOT_PASSWORD must be defined"
  exit 1
fi

if [ -z "$MW_UPDATE_EFO_PAUSE" ]; then
  echo "MW_UPDATE_EFO_PAUSE must be defined"
  exit 1
fi

echo "Starting updateEFO (in 300 seconds)..."
sleep 300
echo "updateEFO started"
while true; do
    logFilePrev="$logfileNow"
    logfileNow="/var/log/updateEFO/$logfileName"_$(date +%Y%m%d)
    if [ -n "$logFilePrev" ] && [ "$logFilePrev" != "$logfileNow" ]; then
        /rotatelogs-compress.sh "$logfileNow" "$logFilePrev" &
    fi

    date >> "$logfileNow"
    # Purge the page
    LC_ALL=C.UTF-8 LANG=C.UTF-8 python3 updateEFO.py \
      -s"web" \
      -u"$MW_BOT_USER" \
      -p"$MW_BOT_PASSWORD" \
      -z 1 \
      -l "$logfileNow" \
      -t http

    echo updateEFO waits for "$MW_UPDATE_EFO_PAUSE" seconds... >> "$logfileNow"
    sleep "$MW_UPDATE_EFO_PAUSE"
done
