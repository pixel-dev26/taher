#!/bin/sh
set -e

# The named volumes mount as fresh, root-owned directories the first time
# Docker creates them — this can't be fixed at image-build time, since the
# volume doesn't exist until the container actually starts. Apache's worker
# processes run as www-data, not root, and SQLite (in WAL mode) needs to
# create -wal/-shm files *in this directory*, not just write the database
# file itself — so the directory's own ownership matters, not only the
# file's. Idempotent and cheap, so it's safe to run on every start.
chown www-data:www-data /var/www/taher-data
chown -R www-data:www-data /var/www/html/storage/app/public

exec docker-php-entrypoint "$@"
