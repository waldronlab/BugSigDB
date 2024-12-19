# DO NOT PUT SECRETS INTO THIS FILE (use .env.secret* files for them)
COMPOSE_FILE=compose.yml:compose.staging.auth.yml
COMPOSE_PROJECT_NAME=bugsigdb-staging

MW_SITE_SERVER=https://bugsigdb-staging.wikiworks.com
MW_SITE_FQDN=bugsigdb-staging.wikiworks.com

VARNISH_SIZE=100m

MW_DB_NAME=mediawiki
MW_DB_INSTALLDB_USER=root
MW_DB_INSTALLDB_PASS=zsGRLt!P0u

# Enable it on PRODUCTION wiki only
MW_ENABLE_SITEMAP_GENERATOR=false

# Comma separated list of IP without space, WLDR-378
# it uses the deny-ip Traefik plugin, https://plugins.traefik.io/plugins/62947363ffc0cd18356a97d1/deny-ip-plugin
IP_DENY_LIST=47.76.99.127,47.76.209.138

# # DEFINE THE FOLLOW VARIBALE VALUES in the .env.secrets* files (if required)
## .env.secret
# This user will be created if the database will be initialized from scratch
MW_ADMIN_USER=
MW_ADMIN_PASS=

MW_SECRET_KEY=
MW_NCBI_TAXONOMY_API_KEY=
MW_RECAPTCHA_SITE_KEY=
MW_RECAPTCHA_SECRET_KEY=

BASIC_USERNAME=admin
# Generate with `openssl passwd -apr1 MY_PASSWORD_RAW`, the password below is `admin`
BASIC_PASSWORD='$apr1$Okb14nu5$bkgxEqp/ym0UFBFKCQTEH/'
# touch .env.secret.updateEFO .env.secret.restic
