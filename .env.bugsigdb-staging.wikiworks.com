# DO NOT PUT SECRETS INTO THIS FILE (use .env.secret* files for them)
TRAEFIK_STACK_PREFIX=STAGING
COMPOSE_FILE=compose.yml

MW_SITE_SERVER=https://bugsigdb-staging.wikiworks.com
MW_SITE_FQDN=bugsigdb-staging.wikiworks.com

VARNISH_SIZE=100m

MW_DB_NAME=mediawiki
MW_DB_INSTALLDB_USER=root
MW_DB_INSTALLDB_PASS=zsGRLt!P0u

# Enable it on PRODUCTION wiki only
MW_ENABLE_SITEMAP_GENERATOR=false

# # DEFINE THE FOLLOW VARIBALE VALUES in the .env.secrets* files (if required)
## .env.secret
# This user will be created if the database will be initialized from scratch
MW_ADMIN_USER=
MW_ADMIN_PASS=

MW_SECRET_KEY=
MW_NCBI_TAXONOMY_API_KEY=
MW_RECAPTCHA_SITE_KEY=
MW_RECAPTCHA_SECRET_KEY=

# touch .env.secret.updateEFO .env.secret.restic
