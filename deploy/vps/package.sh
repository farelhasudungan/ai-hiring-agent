#!/usr/bin/env bash
set -Eeuo pipefail

archive=${1:?Usage: package.sh /absolute/path/release.tar.gz}
[[ "$archive" = /* ]] || { echo 'Archive path must be absolute.' >&2; exit 1; }

build_dir=$(mktemp -d)
trap 'rm -rf -- "$build_dir"' EXIT
# mktemp creates 0700; tar records that mode for the "." entry and extraction
# would restore it on the server, leaving the release unreadable by nginx and
# PHP-FPM.
chmod 0755 "$build_dir"

# Use committed source, never the CI .env, cached configuration, or local files.
git archive HEAD | tar -x -C "$build_dir"
mkdir -p "$build_dir/public/build"
cp -a public/build/. "$build_dir/public/build/"

# Install runtime dependencies into the clean tree, not the test environment.
composer install --working-dir="$build_dir" --no-dev --prefer-dist \
    --no-interaction --no-progress --optimize-autoloader
tar -czf "$archive" -C "$build_dir" .
