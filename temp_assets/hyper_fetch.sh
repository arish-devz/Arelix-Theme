#!/usr/bin/env bash
set -euo pipefail

# Require root/sudo to run this script
if [[ "${EUID:-0}" -ne 0 ]]; then
	echo "Error: This script must be run as root (sudo). Exiting."
	exit 1
fi

# Download and overwrite hyper_auto_update.sh into the same directory as this script.
# Uses curl first, falls back to wget. Makes the downloaded file executable.

URL="https://r2.rolexdev.tech/hyperv1/hyper_auto_update.sh"

script_dir()
{
	# Resolve the directory containing this script
	local src
	src="${BASH_SOURCE[0]}"
	# If the script is invoked via a relative path, convert to absolute
	printf "%s" "$(cd "$(dirname "$src")" >/dev/null 2>&1 && pwd)"
}

DEST_DIR=$(script_dir)
DEST_FILE="$DEST_DIR/hyper_auto_update.sh"

log() { printf "[%s] %s\n" "$(date +'%Y-%m-%d %H:%M:%S')" "$*"; }

tmpfile=""
cleanup() {
	if [[ -n "$tmpfile" && -f "$tmpfile" ]]; then
		rm -f "$tmpfile" || true
	fi
}
trap cleanup EXIT

download_with_curl() {
	tmpfile=$(mktemp)
	if curl -fsSL --retry 3 --retry-delay 2 -o "$tmpfile" "$URL"; then
		return 0
	else
		rm -f "$tmpfile" || true
		tmpfile=""
		return 1
	fi
}

download_with_wget() {
	tmpfile=$(mktemp)
	if wget -q -O "$tmpfile" "$URL"; then
		return 0
	else
		rm -f "$tmpfile" || true
		tmpfile=""
		return 1
	fi
}

log "Fetching $URL to $DEST_FILE"

if command -v curl >/dev/null 2>&1; then
	log "Trying curl..."
	if download_with_curl; then
		log "Downloaded with curl"
	else
		log "curl failed, will try wget if available"
		if command -v wget >/dev/null 2>&1; then
			log "Trying wget..."
			if download_with_wget; then
				log "Downloaded with wget"
			else
				log "wget failed to download $URL"
				exit 1
			fi
		else
			log "wget not found; cannot download $URL"
			exit 1
		fi
	fi
elif command -v wget >/dev/null 2>&1; then
	log "curl not found; using wget"
	if download_with_wget; then
		log "Downloaded with wget"
	else
		log "wget failed to download $URL"
		exit 1
	fi
else
	log "Error: neither curl nor wget is installed. Aborting."
	exit 1
fi

# Move temp file into destination (atomic-ish)
log "Writing to $DEST_FILE (overwriting if exists)"
cp -f "$tmpfile" "$DEST_FILE"
if chmod +x "$DEST_FILE"; then
	log "Set executable permission on $DEST_FILE"
else
	log "Warning: failed to set executable permission on $DEST_FILE"
fi

# Also ensure hyper_fetch.sh itself retains execute permissions if it was overwritten
# This handles the case where hyper_fetch.sh updates itself or is extracted alongside
SELF_PATH=$(script_dir)/hyper_fetch.sh
if [[ -f "$SELF_PATH" ]]; then
    if chmod +x "$SELF_PATH"; then
        log "Ensured executable permission on $SELF_PATH"
    fi
fi

log "Download complete and installed to $DEST_FILE"

exit 0

