# Running BugSigDB Locally

This guide provides step-by-step instructions for setting up and running BugSigDB locally. There are two approaches: with Traefik (for production-like setup) and without Traefik (simpler local development).

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Getting Started](#getting-started)
3. [Initial Setup](#initial-setup)
4. [Secrets Configuration](#secrets-configuration)
5. [Database Setup](#database-setup)
6. [Networking Options](#networking-options)
   - [Without Traefik (Recommended for Local Development)](#without-traefik-recommended-for-local-development)
   - [With Traefik](#with-traefik)
7. [Starting the Stack](#starting-the-stack)
8. [Troubleshooting](#troubleshooting)
9. [Updating Images](#updating-images)
10. [Common Maintenance Tasks](#common-maintenance-tasks)

## Prerequisites

- Docker and Docker Compose 2.24 or newer (`compose.local.yml` uses `!reset`)
- Git
- Access to the BugSigDB repository
- (Optional) Access to `https://github.com/WikiTeq/docker-mediawiki-traefik` if using Traefik
- (Optional) Access to Google Cloud Storage bucket for database backups
- (Optional) `gcloud` CLI tool for downloading backups

## Getting Started

### Clone the Repository

```bash
mkdir -p docker
cd docker
git clone https://github.com/waldronlab/BugSigDB.git bugsigdb.org
cd bugsigdb.org
```

Or if you already have the repository:

```bash
cd bugsigdb.org
git checkout master
git pull
```

### Initialize Submodules

BugSigDB uses Git submodules that must be initialized:

```bash
git submodule update --init
```

## Initial Setup

### Configure Environment File

Create the `.env` file from the example:

```bash
cp .env.example .env
```

Edit the `.env` file:
- `MYSQL_ROOT_PASSWORD=anypassword` (any value works for local development)
- `MW_ADMIN_PASS=anyotherpassword` (the wiki `admin` account password, used only on a fresh install; `compose.local.yml` defaults it to `anyotherpassword` if left blank)
- `COMPOSE_PROFILES=` (clear it; the example enables Matomo, which needs host directories under `/matomo/`)
- `MW_SITE_SERVER=http://localhost:8081` (for setup **without Traefik**; this is also the default in `compose.local.yml`)
- For setup **with Traefik**: `compose.localtest.yml` sets `MW_SITE_SERVER` to `https://bugsigdb.traefik.localtest.me`, so leave it unset

Leave the `MATOMO_*` variables present but empty, as in the example; Docker Compose needs them defined even when Matomo is off.

### Configure Docker Compose files (recommended for local setup)

To avoid repeating `-f compose.yml -f compose.local.yml` on every command, you can use the `COMPOSE_FILE` environment variable as described in the Docker documentation.

For **local development without Traefik**, set:

```bash
export COMPOSE_FILE=compose.yml:compose.local.yml
```

You can set this in your shell before running commands, or add it to your environment configuration so it is loaded automatically.

For **setup with Traefik**, use the Traefik override instead:

```bash
export COMPOSE_FILE=compose.yml:compose.localtest.yml
```

For the remainder of this guide, commands using `docker compose` assume you have configured `COMPOSE_FILE` appropriately for your chosen setup.

## Secrets Configuration

All passwords for a local setup come from `.env`. The `secrets/` directory is only used by the production backup container, so nothing needs to be created there.

`compose.yml` passes `MYSQL_ROOT_PASSWORD` to MediaWiki as both the database password and the install-time password (`MW_DB_INSTALLDB_PASS`), so one value in `.env` covers both.

## Database Setup

You have two options for the database: starting fresh with an empty database, or using a backup from production.

### Option 1: Starting with an Empty Database

If you don't have a database backup, MediaWiki will initialize with an empty database. This is useful for testing the installation process but won't have any BugSigDB content.

Simply skip the database backup steps and proceed to [Networking Options](#networking-options).

### Option 2: Using a Production Database Backup (Recommended)

To get a copy of the production database with all BugSigDB content:

1. **Download a database backup from Google Cloud Storage:**

   First, ensure you have access to the backups bucket and `gcloud` CLI configured:

   ```bash
   # List available backups
   gcloud storage ls gs://backups.bugsigdb.org/

   # Download the latest backup (replace with the actual latest filename)
   gcloud storage cp gs://backups.bugsigdb.org/database20250424-010120.sql.gz/* .
   ```

   This will download `database.sql.gz` to your current directory.

2. **Extract and place the backup:**

   ```bash
   # Extract the gzipped database
   gunzip database.sql.gz

   # Copy to the __initdb directory
   cp database.sql ./__initdb/
   ```

   **Important:** The database file must be placed in the `__initdb/` directory before running `docker compose up -d --no-start`.

### Manual Database Setup (If Automated Setup Fails)

If the automated database initialization hangs or fails, you can manually create and import the database.

Stop everything, start only the database, and open a MySQL shell in it:

```bash
docker compose down
docker compose up -d db
docker compose exec db mysql -uroot -p
# Enter MYSQL_ROOT_PASSWORD from .env
```

In the MySQL shell, create the database and load the backup:

```sql
create database mediawiki;
use mediawiki;
source /docker-entrypoint-initdb.d/database.sql;
exit
```

Then start all containers:

```bash
docker compose up -d
```

## Networking Options

### Without Traefik (Recommended for Local Development)

This is the simplest setup for local development. The wiki will be accessible at `http://localhost:8081`.

**Steps:**

1. **Configure environment:**
   - Ensure `.env` has `MW_SITE_SERVER=http://localhost:8081`

2. **Use the local compose override file:**
   - Ensure `COMPOSE_FILE` includes `compose.yml:compose.local.yml` (see [Configure Docker Compose files (recommended for local setup)](#configure-docker-compose-files-recommended-for-local-setup))
   - The `compose.local.yml` file:
     - Configures port mapping for the varnish service (exposes on `localhost:8081`)
     - Removes the Traefik network dependency for local development
     - Sets `MW_SITE_SERVER` to `http://localhost:8081` (if not set in `.env`)

3. **Start the stack** (see [Starting the Stack](#starting-the-stack))

4. **Access the wiki:**
   - Navigate to `http://localhost:8081` in your browser

### With Traefik

The BugSigDB MediaWiki stack is designed to work with Traefik for production-like routing.

**Prerequisites:**
- Access to `https://github.com/WikiTeq/docker-mediawiki-traefik`

**Steps:**

1. **Start Traefik** following the instructions in that repository, using its `localtest.me` configuration.

2. **Configure the wiki stack:**
   - Create `.env` from `.env.example` if you haven't already (see [Configure Environment File](#configure-environment-file)). Leave `MW_SITE_SERVER` unset.
   - Set `BASIC_USERNAME` and `BASIC_PASSWORD` in `.env`; `compose.localtest.yml` requires them and puts the site behind basic auth.
   - Set `COMPOSE_FILE=compose.yml:compose.localtest.yml` (see [Configure Docker Compose files](#configure-docker-compose-files-recommended-for-local-setup)).

3. **Start the stack** (see [Starting the Stack](#starting-the-stack))

4. **Access the wiki** at `https://bugsigdb.traefik.localtest.me`

## Starting the Stack

### Create Volumes (First Time Only)

Create Docker volumes without starting containers:

```bash
docker compose up -d --no-start
```

This ensures all necessary volumes are created before the first full start.

### Start All Services

```bash
docker compose up -d
```

### Monitor Startup

The first startup, especially with a database backup, can take a significant amount of time (over 1 hour for a full production database). Monitor the web container logs:

```bash
docker compose logs -f web
```

**Success indicators:**

When you see the following in the logs, the wiki should be available:

```
web-1  | Run Jobs
web-1  | Starting job runner (in 10 seconds)...
web-1  | Run transcoder
web-1  | Starting transcoder (in 180 seconds)...
web-1  | Sitemap generator is disabled
web-1  | 
web-1  | 
web-1  | >>>>> run-maintenance-script.sh <<<<<
web-1  | 
web-1  | 
web-1  | Job runner started.
web-1  | Transcoder started.
```

Once you see these messages, navigate to your configured URL (`http://localhost:8081` or your Traefik domain) to access the wiki.

## Troubleshooting

### Missing Environment Variables

If you see errors about missing variables:

1. **MW_DB_INSTALLDB_PASS must be defined:**
   - This is already configured in `compose.yml` to use `MYSQL_ROOT_PASSWORD` from your `.env` file
   - Ensure `MYSQL_ROOT_PASSWORD` is set in your `.env` file

2. **MW_ADMIN_PASS must be defined:**
   - Set `MW_ADMIN_PASS` in your `.env` file, or use `compose.local.yml`, which defaults it to `anyotherpassword`

### LocalSettings.php Not Found

If you see "There is no LocalSettings.php file" or "The file /var/www/mediawiki/w/LocalSettings.php must exist":
- The Taqasta image writes its own `LocalSettings.php` into the web volume on a fresh install, and that file includes the repository's `_settings/LocalSettings.php`, which `compose.yml` bind-mounts into the container
- Ensure `_settings/LocalSettings.php` exists in your checkout and that the install finished (watch `docker compose logs -f web`)

### Semantic MediaWiki Maintenance Screen

If you see a banner about pending SemanticMediaWiki tasks or an error page stating "Semantic MediaWiki was installed and enabled but is missing an appropriate upgrade key," run the maintenance scripts:

```bash
docker compose exec web bash
php extensions/SemanticMediaWiki/maintenance/updateEntityCollation.php
php maintenance/update.php
exit
```

Or run them in a single command:

```bash
docker compose exec web bash -cl 'php extensions/SemanticMediaWiki/maintenance/updateEntityCollation.php'
docker compose exec web bash -cl 'php maintenance/update.php'
```

### Service Unavailable Error

If you see "The server is temporarily unable to service your request" at `http://localhost:8081`:

1. **Check container logs:**
   ```bash
   docker compose logs web
   ```

2. **Verify ports are configured:**
   - Ensure that port 8081 is exposed (via `compose.local.yml` for local development)
   - Check that no other service is using port 8081

3. **Check LocalSettings.php:**
   - If logs show "There is no LocalSettings.php file", see [LocalSettings.php Not Found](#localsettingsphp-not-found)

4. **Verify database connection:**
   - Check that the database container is running: `docker compose ps db`
   - Verify database credentials in secrets match environment variables

### Starting from Scratch

If you need to start completely fresh, you may need to remove Docker volumes:

```bash
# List all volumes
docker volume ls

# List volumes related to BugSigDB (adjust prefix as needed)
docker volume ls | grep bugsigdborg

# Remove specific volumes (replace with actual volume names)
docker volume rm bugsigdborg_db_data
docker volume rm bugsigdborg_web_data
docker volume rm bugsigdborg_images
# ... etc for other volumes

# Or remove all volumes (use with caution!)
docker compose down -v
```

**Warning:** Removing volumes will delete all data, including the database and uploaded images. Only do this if you want a completely fresh start.

### Missing Images / Featured Taxon Error

If you see an error like "Error creating thumbnail: File missing" in the Featured Taxon section:

1. The images volume may be empty or outdated
2. See [Updating Images](#updating-images) section below to populate it

### Extension Dependency Errors

If you see errors about missing extensions (e.g., "VariablesLua requires Scribunto to be installed"):

1. Ensure all submodules are initialized: `git submodule update --init`
2. Verify that extensions are properly mounted in `compose.yml`
3. Check that `LocalSettings.php` has the extensions enabled
4. Restart the web container: `docker compose restart web`

### Database Not Found

If MediaWiki reports that the database doesn't exist:

1. **Check if database was created:**
   ```bash
   docker compose exec db mysql -uroot -p
   # Enter MYSQL_ROOT_PASSWORD from .env
   show databases;
   ```

2. **If database is missing, create it manually:**
   ```sql
   create database mediawiki;
   exit
   ```

3. **Run MediaWiki installation/update:**
   ```bash
   docker compose exec web bash
   php maintenance/update.php
   exit
   ```

## Updating Images

The BugSigDB wiki uses a Docker volume to store uploaded images. To update the images with the latest from production:

### Download Images from Cloud Storage

```bash
# Stop the stack
docker compose down

# Download images directory (this will download to current directory)
gcloud storage cp --recursive gs://backups.bugsigdb.org/images .
```

### Copy Images to Docker Volume

1. **Find the images volume name:**
   ```bash
   docker volume ls | grep images
   ```
   
   The output will show something like `bugsigdborg_images`.

2. **Copy images from host to volume:**
   
   Replace `bugsigdborg_images` with your actual volume name and `images` with the path to your downloaded images directory:
   
   ```bash
   docker run --rm -v $(pwd)/images:/source -v bugsigdborg_images:/target busybox cp -a /source/. /target/
   ```

3. **Restart the stack:**
   ```bash
   docker compose up -d
   ```

**Note:** The images should now be available when you access the wiki. Verify by checking the Featured Taxon section on the homepage.

### Alternative: Copy from Full Images Backup

If you have the `full.images.database.tar` file:

```bash
# Extract the tar file
tar -xf full.images.database.tar

# Find the images volume
docker volume ls | grep images

# Copy to volume (adjust paths as needed)
docker run --rm -v $(pwd)/images:/source -v bugsigdborg_images:/target busybox cp -a /source/. /target/
```

## Common Maintenance Tasks

### Viewing Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f web
docker compose logs -f db
```

### Accessing Containers

```bash
# Access web container shell
docker compose exec web bash

# Access database
docker compose exec db mysql -uroot -p
# Enter MYSQL_ROOT_PASSWORD from .env
```

### Stopping and Starting

```bash
# Stop all services
docker compose down

# Start all services
docker compose up -d

# Restart a specific service
docker compose restart web
```

### Updating the Stack

```bash
# Pull latest code
git pull
git submodule update --init --recursive

# Pull updated images and restart
docker compose down
docker compose pull
docker compose up -d
```

### Running Maintenance Scripts

Common MediaWiki maintenance tasks:

```bash
# Update MediaWiki
docker compose exec web bash
php maintenance/update.php

# Update Semantic MediaWiki entity collation
php extensions/SemanticMediaWiki/maintenance/updateEntityCollation.php

# Update special pages
php maintenance/updateSpecialPages.php
```

### Verifying Installation

After setup, verify:

1. **Wiki is accessible:** Navigate to your configured URL
2. **No maintenance warnings:** Check for red banners about incomplete tasks
3. **Database content:** If using a backup, verify that pages and content are present
4. **Images load:** Check that images (especially Featured Taxon) display correctly
5. **Search works:** Test the search functionality
6. **User login:** If using a backup, try logging in with your production credentials

## Additional Resources

- [BugSigDB Main Site](https://bugsigdb.org)
- [BugSigDB Issue Tracker](https://github.com/waldronlab/BugSigDB/issues)
- [MediaWiki Documentation](https://www.mediawiki.org/)
- [Semantic MediaWiki Documentation](https://www.semantic-mediawiki.org/)

## Notes

- The first startup with a full production database can take over 1 hour. Be patient and monitor the logs.
- For local development, the "without Traefik" setup is recommended for simplicity.
- Database backups are created daily and stored in Google Cloud Storage.
- If you encounter issues not covered here, check the [BugSigDB Issue Tracker](https://github.com/waldronlab/BugSigDB/issues) or create a new issue.
