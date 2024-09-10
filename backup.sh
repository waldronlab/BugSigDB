#!/bin/bash

set -e  # Exit immediately if a command exits with a non-zero status

echo "Starting backup.."

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

echo "- creating database snapshot.."
docker-compose -f /home/wikiworks/docker/bugsigdb.org/docker-compose.yml exec -T db /bin/bash -c 'mysqldump $MYSQL_DATABASE -uroot -p"$MYSQL_ROOT_PASSWORD" | gzip' > /root/backups/db_snapshot_$TIMESTAMP.sql.gz
if [ $? -ne 0 ]; then
    echo "Error during database backup, exiting..."
    exit 1
fi

echo "- creating a separate images folder archive.."
docker-compose -f /home/wikiworks/docker/bugsigdb.org/docker-compose.yml exec -T web /bin/bash -c 'tar -C $MW_VOLUME -czf - images' > /root/backups/images_snapshot_$TIMESTAMP.tar
if [ $? -ne 0 ]; then
    echo "Error during images backup, exiting..."
    exit 1
fi

echo "- building a combined archive.."
tar -zcvf /root/backups/combined_$TIMESTAMP.tar.gz -C /root/backups/ db_snapshot_$TIMESTAMP.sql.gz images_snapshot_$TIMESTAMP.tar
if [ $? -ne 0 ]; then
    echo "Error during archive creation, exiting..."
    exit 1
fi

echo "- cleaning up temporary backup files.."
rm -f /root/backups/db_snapshot_$TIMESTAMP.sql.gz
rm -f /root/backups/images_snapshot_$TIMESTAMP.tar

echo "- uploading to Google Storage.."
gsutil cp /root/backups/combined_$TIMESTAMP.tar.gz gs://backups.bugsigdb.org/
if [ $? -ne 0 ]; then
    echo "Error during Google Storage upload, exiting..."
    exit 1
fi

echo "- cleaning up the combined archive.."
rm -f /root/backups/combined_$TIMESTAMP.tar.gz

echo "Backup has been completed!"
