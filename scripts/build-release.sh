#!/usr/bin/env bash
#
# Build an offline release bundle of Falcon LMS.
#
# Run this on a computer WITH internet access (PHP 8.4, Composer, Node 22).
# The bundle contains vendor/ and the built frontend, so the school server
# never has to download anything.
#
# Usage: scripts/build-release.sh [git-ref]      (default: HEAD)
# Output: releases/falcon-<version>.tar.gz and a .sha256 checksum file

set -euo pipefail

ref="${1:-HEAD}"
root="$(cd "$(dirname "$0")/.." && pwd)"
version="$(git -C "$root" describe --tags --always "$ref")"
name="falcon-${version}"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

echo "Building ${name} from ${ref}..."
git -C "$root" archive --format=tar --prefix="${name}/" "$ref" | tar -x -C "$work"
cd "$work/${name}"

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
rm -rf node_modules
echo "$version" > VERSION

mkdir -p "$root/releases"
tar -czf "$root/releases/${name}.tar.gz" -C "$work" "$name"
(cd "$root/releases" && shasum -a 256 "${name}.tar.gz" > "${name}.tar.gz.sha256")

echo "Done: releases/${name}.tar.gz"
