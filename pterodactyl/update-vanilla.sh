#!/usr/bin/env bash
set -euo pipefail

DEFAULT_PREVIEW_URL="https://github.com/GravityGDev/PocketMine-MP/releases/download/v5.46.0-vanilla-preview.1/PocketMine-MP.phar"
KEEP_BACKUPS="${KEEP_BACKUPS:-5}"

usage() {
  cat <<'EOF'
Usage: update-vanilla.sh [server-directory]

Safely replaces only PocketMine-MP.phar with the latest published
GravityG Vanilla Preview build. Worlds, plugins and server configuration
are not touched.

When run on a Pterodactyl node host, stop the server in the Panel first.
The updater refuses to replace the PHAR while the matching Docker
container is running.

Optional environment variables:
  PREVIEW_URL   Override the PHAR download URL.
  KEEP_BACKUPS  Number of previous PHAR backups to retain (default: 5).
EOF
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
  usage
  exit 0
fi

SERVER_DIR="${1:-$(pwd)}"
if [[ ! -d "$SERVER_DIR" ]]; then
  echo "ERROR: server directory does not exist: $SERVER_DIR" >&2
  exit 1
fi
SERVER_DIR="$(cd "$SERVER_DIR" && pwd)"

PHAR="$SERVER_DIR/PocketMine-MP.phar"
URL_FILE="$SERVER_DIR/.vanilla-preview-url"
INFO_FILE="$SERVER_DIR/release-build-info.json"
CHECKSUM_FILE="$SERVER_DIR/PocketMine-MP.phar.sha256"
BACKUP_ROOT="$SERVER_DIR/.vanilla-backups"

if [[ ! -f "$PHAR" ]]; then
  echo "ERROR: PocketMine-MP.phar was not found in $SERVER_DIR" >&2
  exit 1
fi

# On a Pterodactyl node the container name is normally the server UUID,
# which is also the volume directory name. Refuse to hot-swap a PHAR while
# PocketMine is running because classes can be loaded lazily from the PHAR.
SERVER_ID="$(basename "$SERVER_DIR")"
if command -v docker >/dev/null 2>&1 && docker inspect "$SERVER_ID" >/dev/null 2>&1; then
  if [[ "$(docker inspect "$SERVER_ID" --format '{{.State.Running}}')" == "true" ]]; then
    echo "ERROR: Pterodactyl server $SERVER_ID is still running." >&2
    echo "Stop it from the Panel, run this updater again, then start it from the Panel." >&2
    exit 2
  fi
fi

PREVIEW_URL="${PREVIEW_URL:-}"
if [[ -z "$PREVIEW_URL" && -s "$URL_FILE" ]]; then
  PREVIEW_URL="$(head -n 1 "$URL_FILE" | tr -d '\r\n')"
fi
PREVIEW_URL="${PREVIEW_URL:-$DEFAULT_PREVIEW_URL}"

CHECKSUM_URL="${PREVIEW_URL}.sha256"
RELEASE_BASE="${PREVIEW_URL%/*}"
INFO_URL="$RELEASE_BASE/release-build-info.json"

TMP_DIR="$(mktemp -d "$SERVER_DIR/.vanilla-update.XXXXXX")"
cleanup() {
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT

echo "========================================"
echo " GravityG PocketMine-MP Vanilla Updater"
echo "========================================"
echo "Server:  $SERVER_DIR"
echo "Source:  $PREVIEW_URL"
echo

echo "Downloading latest Vanilla Preview build..."
curl --fail --location --silent --show-error --retry 3 --retry-delay 2 \
  "$PREVIEW_URL" -o "$TMP_DIR/PocketMine-MP.phar"
curl --fail --location --silent --show-error --retry 3 --retry-delay 2 \
  "$CHECKSUM_URL" -o "$TMP_DIR/PocketMine-MP.phar.sha256"

(
  cd "$TMP_DIR"
  sha256sum -c PocketMine-MP.phar.sha256
)

# Metadata is useful for showing the exact source commit, but a missing
# metadata file must not make an otherwise valid PHAR update unusable.
if ! curl --fail --location --silent --show-error --retry 2 \
  "$INFO_URL" -o "$TMP_DIR/release-build-info.json"; then
  rm -f "$TMP_DIR/release-build-info.json"
fi

OLD_HASH="$(sha256sum "$PHAR" | awk '{print $1}')"
NEW_HASH="$(sha256sum "$TMP_DIR/PocketMine-MP.phar" | awk '{print $1}')"

NEW_COMMIT=""
if [[ -f "$TMP_DIR/release-build-info.json" ]]; then
  NEW_COMMIT="$(sed -n 's/.*"git_commit"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p' "$TMP_DIR/release-build-info.json" | head -n 1)"
fi

if [[ "$OLD_HASH" == "$NEW_HASH" ]]; then
  echo
  echo "Already up to date."
  [[ -n "$NEW_COMMIT" ]] && echo "Commit: $NEW_COMMIT"
  cp "$TMP_DIR/PocketMine-MP.phar.sha256" "$CHECKSUM_FILE"
  [[ -f "$TMP_DIR/release-build-info.json" ]] && cp "$TMP_DIR/release-build-info.json" "$INFO_FILE"
  printf '%s\n' "$PREVIEW_URL" > "$URL_FILE"
  exit 0
fi

STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
BACKUP_DIR="$BACKUP_ROOT/$STAMP"
mkdir -p "$BACKUP_DIR"
cp -p "$PHAR" "$BACKUP_DIR/PocketMine-MP.phar"
[[ -f "$CHECKSUM_FILE" ]] && cp -p "$CHECKSUM_FILE" "$BACKUP_DIR/PocketMine-MP.phar.sha256"
[[ -f "$INFO_FILE" ]] && cp -p "$INFO_FILE" "$BACKUP_DIR/release-build-info.json"

echo "Backup: $BACKUP_DIR"

# Install through a temporary filename so a failed copy can never leave a
# partial PocketMine-MP.phar in place.
install -m 0644 "$TMP_DIR/PocketMine-MP.phar" "$SERVER_DIR/PocketMine-MP.phar.new"
mv -f "$SERVER_DIR/PocketMine-MP.phar.new" "$PHAR"
cp "$TMP_DIR/PocketMine-MP.phar.sha256" "$CHECKSUM_FILE"
[[ -f "$TMP_DIR/release-build-info.json" ]] && cp "$TMP_DIR/release-build-info.json" "$INFO_FILE"
printf '%s\n' "$PREVIEW_URL" > "$URL_FILE"

if [[ "$KEEP_BACKUPS" =~ ^[0-9]+$ ]] && (( KEEP_BACKUPS >= 0 )) && [[ -d "$BACKUP_ROOT" ]]; then
  mapfile -t OLD_BACKUPS < <(find "$BACKUP_ROOT" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -rn | awk '{print $2}')
  if (( ${#OLD_BACKUPS[@]} > KEEP_BACKUPS )); then
    for ((i=KEEP_BACKUPS; i<${#OLD_BACKUPS[@]}; i++)); do
      rm -rf "${OLD_BACKUPS[$i]}"
    done
  fi
fi

echo
echo "Update installed successfully."
echo "Old SHA-256: $OLD_HASH"
echo "New SHA-256: $NEW_HASH"
[[ -n "$NEW_COMMIT" ]] && echo "Commit:       $NEW_COMMIT"
echo
echo "Start the server again from Pterodactyl."
