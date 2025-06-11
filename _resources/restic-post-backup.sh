#!/bin/sh

# Exit immediately if a command exits with a non-zero status
set -e

# Define constants
RCLONE_DATE=$(date +"%Y%m%d-%H%M%S")
SERVICE_ACCOUNT_FILE="/run/secrets/restic-GCS-account"
DATABASE_FILE="/data/database.sql.gz"
IMAGES_SRC="/data/images/"

# Step 1: Copy database backup to Google Cloud Storage
if [ -f "$DATABASE_FILE" ]; then
  echo "Copying database backup to Google Cloud Storage..."
  rclone copy "$DATABASE_FILE" ":google cloud storage:backups.bugsigdb.org/database${RCLONE_DATE}.sql.gz" \
    --gcs-service-account-file "$SERVICE_ACCOUNT_FILE" --gcs-bucket-policy-only

  echo "Removing local database backup file..."
  rm "$DATABASE_FILE"
else
  echo "No database backup file found at $DATABASE_FILE"
fi

# Step 2: Sync image directories to Google Cloud Storage
echo "Checking image directories..."
for dir in ${IMAGES_SRC}[0-9a-f]; do
  if [ -d "$dir" ] && [ "$(ls -A "$dir" 2>/dev/null)" ]; then
    echo "Syncing non-empty directory $dir..."
    rclone sync "$dir" ":google cloud storage:backups.bugsigdb.org/images/$(basename "$dir")" \
      --gcs-service-account-file "$SERVICE_ACCOUNT_FILE" --gcs-bucket-policy-only
  else
    echo "Skipping empty or non-existent directory $dir."
  fi
done

echo "Post-backup process completed."
