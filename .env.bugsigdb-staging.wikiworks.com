# DO NOT PUT SECRETS INTO THIS FILE (use docker secrets and files in the secrets directory)
COMPOSE_FILE=compose.yml:compose.staging.auth.yml

MW_SITE_SERVER=https://bugsigdb-staging.wikiworks.com
MW_SITE_FQDN=bugsigdb-staging.wikiworks.com

VARNISH_SIZE=100m

# Enable it on PRODUCTION wiki only
MW_ENABLE_SITEMAP_GENERATOR=false

# Comma separated list of IP without space, WLDR-378
# it uses the deny-ip Traefik plugin, https://plugins.traefik.io/plugins/62947363ffc0cd18356a97d1/deny-ip-plugin
IP_DENY_LIST=47.76.99.127,47.76.209.138

BASIC_USERNAME=admin
# Generate with `openssl passwd -apr1 MY_PASSWORD_RAW`, the password below is `admin`
BASIC_PASSWORD='$apr1$Okb14nu5$bkgxEqp/ym0UFBFKCQTEH/'
