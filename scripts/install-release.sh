#!/usr/bin/env bash
#
# Install a Falcon LMS release bundle on the school server (Mac mini).
# Run as the "falcon" user outside school hours; it asks for sudo once to
# restart PHP-FPM.
#
# Usage: install-release.sh /path/to/falcon-<version>.tar.gz
#
# Layout:
#   /opt/falcon/releases/<name>   unpacked releases (the newest 3 are kept)
#   /opt/falcon/shared/.env       production settings
#   /opt/falcon/shared/storage    uploads, logs and caches (kept across releases)
#   /opt/falcon/current           symlink to the active release

set -euo pipefail

bundle="${1:?Usage: install-release.sh falcon-<version>.tar.gz}"
base="${FALCON_BASE:-/opt/falcon}"
php="${FALCON_PHP:-/opt/homebrew/opt/php@8.4/bin/php}"
restart_php="${FALCON_RESTART_PHP:-sudo /opt/homebrew/bin/brew services restart php@8.4}"

name="$(basename "$bundle" .tar.gz)"
release="$base/releases/$name"

if [ -f "${bundle}.sha256" ]; then
    (cd "$(dirname "$bundle")" && shasum -a 256 -c "$(basename "$bundle").sha256")
fi

if [ -e "$release" ]; then
    echo "$release already exists." >&2
    exit 1
fi

mkdir -p "$base/releases"
tar -xzf "$bundle" -C "$base/releases"

# Settings and uploaded files live outside the release so they survive updates.
rm -rf "$release/storage"
ln -s "$base/shared/storage" "$release/storage"
ln -s "$base/shared/.env" "$release/.env"

trap 'echo "Installation failed. Falcon may still be in maintenance mode; check the output above." >&2' ERR

if [ -L "$base/current" ]; then
    "$php" "$base/current/artisan" down --retry=15
fi

cd "$release"
"$php" artisan migrate --force
"$php" artisan optimize

ln -sfn "$release" "$base/current"

# OPcache does not watch files (see php-falcon.ini), so PHP-FPM must restart.
$restart_php
# Queue workers finish their current job and launchd starts them in the new release.
"$php" artisan queue:restart
"$php" artisan up

# Keep the three newest releases.
ls -1dt "$base"/releases/*/ | tail -n +4 | while read -r old; do
    rm -rf "$old"
done

echo "Falcon $(cat "$release/VERSION") is live."
