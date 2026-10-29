#!/usr/bin/env bash
set -Eeuo pipefail

archive=${1:?Usage: upload.sh release.tar.gz}
: "${DEPLOY_HOST:?Set DEV_SSH_HOST}"
: "${DEPLOY_USER:?Set DEV_SSH_USER}"
: "${DEPLOY_SSH_KEY:?Set DEV_SSH_PRIVATE_KEY}"
: "${DEPLOY_KNOWN_HOSTS:?Set DEV_SSH_KNOWN_HOSTS}"
: "${DEPLOY_HEALTH_URL:?Set DEV_HEALTH_URL, including /up}"
: "${RELEASE_ID:?Missing release ID}"
DEPLOY_PORT=${DEPLOY_PORT:-22}

# These values become SSH arguments and a remote shell command.
[[ "$DEPLOY_HOST" =~ ^[a-zA-Z0-9][a-zA-Z0-9.-]*$ ]]
[[ "$DEPLOY_USER" =~ ^[a-z_][a-z0-9_-]*$ ]]
[[ "$DEPLOY_PORT" =~ ^[0-9]+$ ]]
[[ "$RELEASE_ID" =~ ^[a-f0-9]{40}-[0-9]+-[0-9]+$ ]]
[[ "$DEPLOY_HEALTH_URL" =~ ^https?://[a-zA-Z0-9.-]+(:[0-9]+)?/up$ ]]

ssh_dir=$(mktemp -d)
trap 'rm -rf -- "$ssh_dir"' EXIT
chmod 700 "$ssh_dir"
printf '%s\n' "$DEPLOY_SSH_KEY" > "$ssh_dir/key"
printf '%s\n' "$DEPLOY_KNOWN_HOSTS" > "$ssh_dir/known_hosts"
chmod 600 "$ssh_dir/key" "$ssh_dir/known_hosts"

ssh_options=(-i "$ssh_dir/key" -o "UserKnownHostsFile=$ssh_dir/known_hosts"
    -o StrictHostKeyChecking=yes -o IdentitiesOnly=yes -o BatchMode=yes
    -o ConnectTimeout=15 -o ServerAliveInterval=15 -o ServerAliveCountMax=4)
target="$DEPLOY_USER@$DEPLOY_HOST"
base=/var/www/ai-hiring-dev

ssh "${ssh_options[@]}" -p "$DEPLOY_PORT" "$target" "mkdir -p '$base/incoming'"
scp "${ssh_options[@]}" -P "$DEPLOY_PORT" "$archive" "$target:$base/incoming/$RELEASE_ID.tar.gz"
ssh "${ssh_options[@]}" -p "$DEPLOY_PORT" "$target" \
    "bash -s -- '$RELEASE_ID' '$DEPLOY_HEALTH_URL'" < deploy/vps/deploy.sh
