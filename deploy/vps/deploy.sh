#!/usr/bin/env bash
set -Eeuo pipefail
umask 0007

release_id=${1:?Usage: deploy.sh release-id health-url}
health_url=${2:?Provide the dev URL ending in /up}
[[ "$release_id" =~ ^[a-f0-9]{40}-[0-9]+-[0-9]+$ ]]
[[ "$health_url" =~ ^https?://[a-zA-Z0-9.-]+(:[0-9]+)?/up$ ]]

base=/var/www/ai-hiring-dev
shared="$base/shared"
release="$base/releases/$release_id"
archive="$base/incoming/$release_id.tar.gz"

exec 9>"$base/deploy.lock"
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }
[[ -s "$shared/.env" ]] || { echo "Configure $shared/.env first." >&2; exit 1; }
[[ -f "$archive" && ! -e "$release" ]]

[[ ! -e "$base/current" || -L "$base/current" ]] || {
    echo "$base/current must be a symlink." >&2
    exit 1
}
previous=''
if [[ -L "$base/current" ]]; then
    previous=$(readlink -f "$base/current" || true)
fi
activated=false
rollback() {
    status=$?
    trap - ERR
    if [[ "$activated" == true ]]; then
        if [[ -n "$previous" && -d "$previous" ]]; then
            ln -s "$previous" "$base/current.rollback"
            mv -Tf "$base/current.rollback" "$base/current"
            /usr/bin/php8.4 "$base/current/artisan" queue:restart || true
            echo "Restored application release: $previous" >&2
        else
            unlink "$base/current"
            echo 'First deployment failed; no previous release exists.' >&2
        fi
    fi
    echo 'Deployment failed. Database migrations were NOT rolled back.' >&2
    exit "$status"
}
trap rollback ERR

mkdir -p "$release" "$shared/storage/app/public" "$shared/storage/app/private" \
    "$shared/storage/framework/cache/data" "$shared/storage/framework/sessions" \
    "$shared/storage/framework/views" "$shared/storage/logs"
tar -xzf "$archive" -C "$release"
chgrp -R www-data "$release"
chmod -R g+rwX "$release/bootstrap/cache"
# The artifact contains only the repository's empty storage skeleton.
rm -rf -- "$release/storage"
ln -s "$shared/storage" "$release/storage"
ln -s "$shared/.env" "$release/.env"

cd "$release"
/usr/bin/php8.4 -r 'require "vendor/autoload.php";'
/usr/bin/php8.4 artisan storage:link --force
/usr/bin/php8.4 artisan migrate --force --no-interaction
/usr/bin/php8.4 artisan config:cache
/usr/bin/php8.4 artisan route:cache
/usr/bin/php8.4 artisan view:cache

ln -s "$release" "$base/current.next"
mv -Tf "$base/current.next" "$base/current"
activated=true

# Nginx passes the real release path to FPM, avoiding stale OPcache entries.
curl --fail --silent --show-error --retry 5 --retry-all-errors \
    --retry-delay 2 --max-time 10 "$health_url" > /dev/null

# Start workers on the new release only after its HTTP health check succeeds.
/usr/bin/php8.4 artisan queue:restart

rm -f -- "$archive"
echo "Deployed $release_id"
