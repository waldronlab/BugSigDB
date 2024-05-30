#!/bin/bash

# tor testing production just run the script

# for testing local docker compose instance run the script as ./test_redirects.sh 127.0.0.1
# make sure you have added compose.PRODUCTION.redirects.yml to the COMPOSE_FILE env variable in the .env file
# ON LOCAL DOCKER INSTANCE ONLY, DO NOT ADD IT ON STAGING!!!

# set -x

if [ -n "$1" ]; then
    SERVER_IP="$1"
fi

get_curl_options_for_url() {
    if [ -n "$SERVER_IP" ]; then
        host=$(echo "$1" | awk -F[/:] '{print $4}')
        port=$(echo "$1" | grep -qi "https://" && echo "443" || echo "80")
        return="--resolve $host:$port:$SERVER_IP"
        if [ "$port" == "443" ]; then
            return="$return --insecure"
        fi
        echo "$return"
    fi
}

test_url() {
    options=$(get_curl_options_for_url "$1")
    curl -vs "$1" -v ${options} 2>&1 | grep -qi "$2" && echo "passed" || echo "FAIL"
}

echo "Test HTTP to HTTPS redirect (entrypoints.web.http.redirections.entryPoint.to=websecure in the traefik config)"
test_url "http://bugsigdb.org/test" "Location: https://bugsigdb.org/test"

echo "Test www_bugsigdb_redirect"
test_url "http://www.bugsigdb.org/test" "Location: https://bugsigdb.org/test"
test_url "https://www.bugsigdb.org/test" "Location: https://bugsigdb.org/test"
