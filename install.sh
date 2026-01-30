#!/bin/bash
set -e

# Configuration
PANEL_PATH="/var/www/pterodactyl"
THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" &> /dev/null && pwd)"

# Check Root
if [[ "$EUID" -ne 0 ]]; then
    echo "Error: Run as root."
    exit 1
fi

echo ">> Installing Custom Theme..."

# Copy Resources
echo ">> Copying resources..."
cp -r "$THEME_DIR/resources/"* "$PANEL_PATH/resources/"

# Rebuild Panel Assets
echo ">> Rebuilding panel assets (yarn build:production)..."
cd "$PANEL_PATH"
# Check if yarn exists, otherwise use npm
if command -v yarn &> /dev/null; then
    yarn build:production
else
    npm run build:production
fi

echo ">> Theme Installed Successfully!"
