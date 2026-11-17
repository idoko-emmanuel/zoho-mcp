#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  zoho-mcp: build a plugin zip for uploading to an organisation's plugin library
#
#  The zip holds the committed code at HEAD plus a production vendor/, packed as
#  vendor.tar.gz to stay under the 5000-file upload limit, so the plugin never
#  runs composer at session start. That matters in Claude Code cloud sessions,
#  where GitHub zipball downloads are refused and a composer install falls back
#  to slow git clones.
#
#  Usage: scripts/build-plugin-zip.sh [output-dir]     (default: dist/)
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="$(mkdir -p "${1:-$REPO/dist}" && cd "${1:-$REPO/dist}" && pwd)"
VERSION="$(php -r 'echo json_decode(file_get_contents($argv[1]))->version;' "$REPO/.claude-plugin/plugin.json")"
ZIP="$OUT/zoho-mcp-$VERSION.zip"

if [[ -n "$(git -C "$REPO" status --porcelain)" ]]; then
  echo "Working tree has uncommitted changes; the zip is built from HEAD only." >&2
fi

BUILD="$(mktemp -d)"
trap 'rm -rf "$BUILD"' EXIT

git -C "$REPO" archive HEAD | tar -x -C "$BUILD"
composer install --working-dir="$BUILD" --no-dev --no-interaction --no-scripts \
  --no-progress --prefer-dist --optimize-autoloader --quiet

# Plugin uploads allow at most 5000 files and vendor/ alone has thousands, so it
# ships as one archive that setup.sh unpacks.
COPYFILE_DISABLE=1 tar -czf "$BUILD/vendor.tar.gz" -C "$BUILD" vendor
rm -rf "$BUILD/vendor"

rm -f "$ZIP"
(cd "$BUILD" && zip -qr -X "$ZIP" . -x '.git/*')

FILES="$(unzip -Z1 "$ZIP" | wc -l | tr -d ' ')"
if (( FILES > 5000 )); then
  echo "$ZIP has $FILES files; plugin uploads allow at most 5000." >&2
  exit 1
fi

echo "$ZIP ($(du -h "$ZIP" | cut -f1), $FILES files)"
