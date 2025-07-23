# Containerized MediaWiki for bugsigdb.org

## Briefly

This repo contains [Docker Compose](https://docs.docker.com/compose/) containers to run the [MediaWiki](https://www.mediawiki.org/) software.

Clone the repo. Then create and start the containers:
```sh
cd docker-bugsigdb.org
docker compose up -d --no-start
# copy a database dump (*.sql or *.sql.gz) to the __initdb directory if needed
docker run --rm -v <images/directory>:/source -v <volume_prefix>_images:/target busybox cp -a /source/. /target/
# copy .env.example to .env and modify as needed (see the Settings section)
cp .env.example .env
docker compose up -d
```
Wait for the completion of the build and initialization process and access it via `http://localhost:8081` in a browser.

## Architecture of mediawiki containers

Running `docker compose up -d` will start the containers:

- `db` - MySQL [official container](https://hub.docker.com/_/mysql/), used as the database backend for MediaWiki.
- `web` - Apache/MediaWiki container (Taqasta) with PHP 7.4 and MediaWiki 1.39.x
- `redis` - Redis is an open-source key-value store used as the cache backend
- `matomo` - [Matomo](https://matomo.org/) analytics instance
- `elasticsearch` - Advanced search engine
- `varnish` - A reverse caching proxy and HTTP accelerator
- `restic` - (production only) Modern backup container performing incremental backups to both S3 storage and Google Cloud Storage (GCS)
- `updateEFO` - (production only) A Python script that updates EFO links on glossary pages automatically

## Settings

Settings can be adjusted via the `.env` file created from `.env.example`. Environment and other general configuration are in the `compose.yml` and environment-specific overrides (`compose.staging.yml`, `compose.PRODUCTION.yml`) files, in the *environment* sections.

Additionally:

- `_resources` directory: contains favicon, logo, styles, and customizations for the chameleon skin and additional MediaWiki extensions.
- `_settings/LocalSettings.php`: contains settings for MediaWiki core and extensions. If customization is required, change them there.
- For production backups with restic, create the file `./secrets/restic-GCS-account.json`, containing your Google Cloud Storage credentials.

### db

The database used is the official MySQL 8 container.  
The most important environment variable is `MYSQL_ROOT_PASSWORD`; it specifies the password set for the MySQL `root` superuser account.

If changed, ensure corresponding database passwords (`MW_DB_PASS` in the web section) are updated accordingly.

### web

#### environment variables

- `MW_SITE_SERVER` configures [$wgServer](https://www.mediawiki.org/wiki/Manual:$wgServer); set this to the server host and include the protocol like `https://bugsigdb.org`
- `MW_SITE_NAME` configures [$wgSitename](https://www.mediawiki.org/wiki/Manual:$wgSitename)
- `MW_SITE_LANG` configures [$wgLanguageCode](https://www.mediawiki.org/wiki/Manual:$wgLanguageCode)
- `MW_DEFAULT_SKIN` configures [$wgDefaultSkin](https://www.mediawiki.org/wiki/Manual:$wgDefaultSkin)
- `MW_ENABLE_UPLOADS` configures [$wgEnableUploads](https://www.mediawiki.org/wiki/Manual:$wgEnableUploads)
- `MW_ADMIN_USER` configures the default administrator username
- `MW_ADMIN_PASSWORD` configures the default administrator password
- `MW_DB_NAME` specifies the database name MediaWiki uses
- `MW_DB_USER` specifies the DB user MediaWiki uses; default is `root`
- `MW_DB_PASS` specifies the DB user password; must match your MySQL password
- `MW_PROXY_SERVERS` configures [$wgSquidServers](https://www.mediawiki.org/wiki/Manual:$wgSquidServers) for reverse proxies (typically `varnish:80`)
- `MW_MAIN_CACHE_TYPE` configures [$wgMainCacheType](https://www.mediawiki.org/wiki/Manual:$wgMainCacheType). (`CACHE_REDIS` is recommended)
- `MW_LOAD_EXTENSIONS` provided as comma-separated list of MediaWiki extensions to load during container startup
- `MW_LOAD_SKINS` comma-separated list of MediaWiki skins available for use
- `MW_SEARCH_TYPE` configures the search backend (typically `CirrusSearch`)
- `MW_NCBI_TAXONOMY_API_URL` optionally sets custom URL for the NCBI API endpoint
- `MW_NCBI_TAXONOMY_API_KEY`, `MW_RECAPTCHA_SITE_KEY`, `MW_RECAPTCHA_SECRET_KEY` optional/requested third-party API keys
- `MW_ENABLE_SITEMAP_GENERATOR` enables sitemap generator script on production (`true/false`)

### restic (production only)

The restic container handles scheduled backups (weekly/monthly retention settings) through incremental snapshots:

- `RESTIC_PASSWORD` - password to encrypt backup
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` - access credentials for S3-compatible storage
- `BACKUP_CRON`, `CHECK_CRON` - cron schedule for automatic backup and check operations

### updateEFO (production only)

This Python-based container automatically updates EFO terms and links in the glossary:

- `UPDATE_EFO_BOT_PASSWORD` - authentication password for bot account
- `UPDATE_EFO_PAUSE` - update frequency in seconds (default 86400 sec / 24h)

Note: the script may produce extra load to the wiki so it's recommended to schedule it for nigh time, also worth to
consider that it takes time to process all the pages so average script cycle is ~4-8 hours. You can change sleep
timeouts via `-z` parameter.

### matomo

Matomo instance provides website analytics:

- Default admin username: `admin`
- `MATOMO_PASSWORD` - sets the initial password for matomo administration panel

### varnish

Varnish cache container used as reverse proxy and front-end cache server:

- `VARNISH_SIZE` - amount of RAM to dedicate to caching (e.g., `100m`)

### Basic Authentication (Staging Only)

- `BASIC_USERNAME` - basic http username
- `BASIC_PASSWORD` - basic http password (hashed using `openssl passwd -apr1`)

## Data

### Bind mounts
Used to just binding a certain directory or file from the host inside the container. We use:
- `./__initdb` directory is used to pass the database dump for stack initialization

### Named volumes
Data that must be persistent across container life cycles are stored in docker volumes:
- `db_data` (MySQL databases and working directories, attached to `db` service)
- `elasticsearch_data` (Elasticsearch nodes, attached to `elasticsearch` service)
- `web_data` (Miscellaneous MediaWiki files and directories that must be persistent by design, attached to `web` service )
- `images` (MediaWiki upload directory, attached to `web` service and used in `restic` service (read-only))
- `redis_data` (Redis cache)
- `varnish_data` (Varnish cache)
- `matomo_data` (Analytics data)
- `restic_data` (Space mounted to the `restic` service for operations with snapshots)

Docker containers write files to volumes using internal users.

## Log files

Log files are stored in the `_logs` directory.

## Keeping up to date

**Make a full backup of the wiki, including both the database and the files.**
While the upgrade scripts are well-maintained and robust, things could still go awry.
```sh
cd <docker stack directory>
docker-compose exec db /bin/bash -c 'mysqldump --all-databases -uroot -p"$MYSQL_ROOT_PASSWORD" 2>/dev/null | gzip | base64 -w 0' | base64 -d > backup_$(date +"%Y%m%d_%H%M%S").sql.gz
docker-compose exec web /bin/bash -c 'tar -c $MW_VOLUME $MW_HOME/images 2>/dev/null | base64 -w 0' | base64 -d > backup_$(date +"%Y%m%d_%H%M%S").tar
```

For picking up the latest changes, stop, rebuild and start containers:
```sh
cd <docker stack directory>
git pull
docker-compose up -d
```
The upgrade process is fully automated and includes the launch of all necessary maintenance scripts.

## Purging homepage SMW caches

The image is configured to automatically purge the homepage once per hour. You can
configure this using the following environment variables:

```
MW_CACHE_PURGE_PAUSE=3600
MW_CACHE_PURGE_PAGE=Main_Page
```

---

## Docker Compose Overrides & Environments

The deployment is organized as follows:

- `compose.yml`: common container definitions, typically used in development environment
- `compose.staging.yml`: staging-specific overrides (hostnames, basic auth)
- `compose.PRODUCTION.yml`: production-specific overrides including health-checks, backups, and special scripts

Before running docker compose commands, link your environment configuration as follows:

```bash
ln -sf compose.staging.yml compose.override.yml  # staging environment
# OR
ln -sf compose.PRODUCTION.yml compose.override.yml  # production environment
```

Then use `docker compose up -d` as usual. Docker Compose automatically merges the files.

## Updating Active user count

To work around T333776 we run maintenance/updateSpecialPages.php once a day. This ensures the count of active users on Special:CreateAccount stays up to date.

# bugsigdb-related links

* [bugsigdb.org](https://bugsigdb.org): A Comprehensive Database of Published Microbial Signatures
* [BugSigDB issue tracker](https://github.com/waldronlab/BugSigDB/issues): Report bugs or feature requests for bugsigdb.org
* [BugSigDBExports](https://github.com/waldronlab/BugSigDBExports): Hourly data exports of bugsigdb.org
* [Stable data releases](https://zenodo.org/records/6468009): Periodic manually-reviewed stable data releses on Zenodo
* [bugsigdbr](https://bioconductor.org/packages/bugsigdbr/): R/Bioconductor access to published microbial signatures from BugSigDB
* [Curation issues](https://github.com/waldronlab/BugSigDBcuration/issues): Report curation issues, requests studies to be added
* [bugSigSimple](https://github.com/waldronlab/bugSigSimple): Simple analyses of BugSigDB data in R
* [BugSigDBStats](https://github.com/waldronlab/BugSigDBStats): Statistics and trends of BugSigDB
* [BugSigDBPaper](https://github.com/waldronlab/BugSigDBPaper): Reproduces analyses of the [Nature Biotechnology publication](https://www.nature.com/articles/s41587-023-01872-y)
* [community-bioc Slack Team](https://slack.bioconductor.org/): Join #bugsigdb channel
