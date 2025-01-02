#!/bin/bash

logfileName=updateEFO_log

# usage: file_env VAR [DEFAULT]
#    ie: file_env 'XYZ_DB_PASSWORD' 'example'
# (will allow for "$XYZ_DB_PASSWORD_FILE" to fill in the value of
#  "$XYZ_DB_PASSWORD" from a file, especially for Docker's secrets feature)
file_env() {
	local var="$1"
	local fileVar="${var}_FILE"
	local def="${2:-}"
	local varValue
	varValue=$(env | grep -E "^${var}=" | sed -E -e "s/^${var}=//")
	local fileVarValue
	fileVarValue=$(env | grep -E "^${fileVar}=" | sed -E -e "s/^${fileVar}=//")
	if [ -n "${varValue}" ] && [ -n "${fileVarValue}" ]; then
		echo >&2 "error: both $var and $fileVar are set (but are exclusive)"
		exit 1
	fi
	if [ -n "${varValue}" ]; then
		export "$var"="${varValue}"
	elif [ -n "${fileVarValue}" ]; then
		export "$var"="$(cat "${fileVarValue}")"
	elif [ -n "${def}" ]; then
		export "$var"="$def"
	fi
	unset "$fileVar"
}

file_env UPDATE_EFO_BOT_PASSWORD

cd /updateEFO || exit 1
pip install --no-cache-dir -r updateEFO.requirements.txt
mkdir -p /var/log/updateEFO/

if [ -z "$UPDATE_EFO_BOT_USER" ]; then
  echo "UPDATE_EFO_BOT_USER must be defined"
  exit 1
fi

if [ -z "$UPDATE_EFO_BOT_PASSWORD" ]; then
  echo "UPDATE_EFO_BOT_PASSWORD must be defined"
  exit 1
fi

if [ -z "$UPDATE_EFO_PAUSE" ]; then
  echo "UPDATE_EFO_PAUSE must be defined"
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
      -u"$UPDATE_EFO_BOT_USER" \
      -p"$UPDATE_EFO_BOT_PASSWORD" \
      -z 15 \
      -l "$logfileNow" \
      -t http

    echo updateEFO waits for "$UPDATE_EFO_PAUSE" seconds... >> "$logfileNow"
    sleep "$UPDATE_EFO_PAUSE"
done
