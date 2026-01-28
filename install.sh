#!/bin/bash
# =============================================
# ARELIX THEME INSTALLER
# =============================================
set -e

# --- CONFIGURATION ---
# REPLACE THIS WITH YOUR GITHUB REPOSITORY (Username/RepoName)
GITHUB_REPO="arish-devz/Arelix-Theme"
GITHUB_BRANCH="main"
BASE_URL="https://raw.githubusercontent.com/$GITHUB_REPO/$GITHUB_BRANCH"

# --- Check Root Permission ---
if [[ "$EUID" -ne 0 ]]; then
    echo "Error: This script must be run as root or with sudo privileges."
    exit 1
fi

# --- Define panel path (default /var/www/pterodactyl) ---
read -rp "Enter your Pterodactyl panel path [/var/www/pterodactyl]: " PANEL_PATH
PANEL_PATH=${PANEL_PATH:-/var/www/pterodactyl}

if [[ ! -d "$PANEL_PATH" ]]; then
    echo "Error: Path $PANEL_PATH does not exist!"
    exit 1
fi

echo ""
echo "=================================================================="
echo "    ___               ___        ______  __                      "
echo "   /   |  ________  / (_)  __  /_  __/ / /_  ___  ____ ___  ___ "
echo "  / /| | / ___/ _ \/ / / |/_/   / /   / __ \/ _ \/ __ \`__ \/ _ \\"
echo " / ___ |/ /  /  __/ / />  <    / /   / / / /  __/ / / / / /  __/"
echo "/_/  |_/_/   \___/_/_/_/|_|   /_/   /_/ /_/\___/_/ /_/ /_/\___/ "
echo "                                                                "
echo "           >> PREMIUM PTERODACTYL THEME INSTALLER <<            "
echo "=================================================================="
echo ""
echo "1) Install Arelix Theme"
echo "2) Upgrade Arelix Theme"
echo "3) Restore backup"
echo "=================================================================="
read -rp "Select an option [1-3]: " OPTION

# --- Backup function ---
backup_panel() {
    echo ">> [BACKUP] Initiating panel backup..."
    cd /var/www || exit
    tar -czf "arelix_backup_$(date +%Y%m%d_%H%M%S).tar.gz" pterodactyl/
    echo ">> [BACKUP] Backup completed successfully."
}

# --- Manage backups ---
manage_backups() {

    mapfile -t BACKUPS < <(find /var/www -type f -name "arelix_backup_*.tar.gz" -print | sort -r)
    if [ ${#BACKUPS[@]} -eq 0 ]; then
        return
    fi
    echo "Existing backups:"
    for i in "${!BACKUPS[@]}"; do
        echo "$((i+1))) ${BACKUPS[$i]}"
    done
    MOST_RECENT="${BACKUPS[0]}"
    echo ">> [INFO] Most recent backup: $MOST_RECENT"
    read -rp "Delete old backups? (y/N): " DELETE_OLD
    if [[ "$DELETE_OLD" =~ ^[Yy]$ ]]; then
        echo "Enter numbers to delete (space separated): "
        read -r TO_DELETE
        for num in $TO_DELETE; do
            if [[ "$num" =~ ^[0-9]+$ ]] && [ "$num" -ge 1 ] && [ "$num" -le ${#BACKUPS[@]} ]; then
                SELECTED="${BACKUPS[$((num-1))]}"
                if [ "$SELECTED" = "$MOST_RECENT" ]; then
                    read -rp "Warning: Deleting most recent backup $SELECTED. Confirm? (y/N): " CONFIRM_DELETE_RECENT
                    if [[ "$CONFIRM_DELETE_RECENT" =~ ^[Yy]$ ]]; then
                        rm "$SELECTED"
                        echo ">> [DELETE] Removed $SELECTED"
                    fi
                else
                    rm "$SELECTED"
                    echo ">> [DELETE] Removed $SELECTED"
                fi
            fi
        done
    fi
}

# --- Remove old assets ---
remove_old_assets() {
    echo ">> [CLEAN] Removing old assets..."
    find "$PANEL_PATH/public/assets" -type f \( -name "*.js" -o -name "*.json" -o -name "*.js.map" \) -delete
}

# --- Download/Install Assets ---
install_hyperv1_files() {
    echo ">> [INSTALL] Fetching Arelix Theme assets..."
    cd "$PANEL_PATH" || exit
    
    TAR_FILE="ArelixTheme.tar"
    DOWNLOAD_URL="$BASE_URL/assets/ArelixTheme.tar"

    # Clean up previous downloads
    rm -f "$TAR_FILE"

    echo ">> [DOWNLOAD] Downloading from $DOWNLOAD_URL"
    
    # Try downloading from GitHub
    if command -v curl >/dev/null 2>&1; then
        if curl -fsSL --retry 3 --retry-delay 2 -o "$TAR_FILE" "$DOWNLOAD_URL"; then
            echo ">> [SUCCESS] Downloaded assets."
        else
            echo "Error: Download failed! Check your GITHUB_REPO setting in the script."
            exit 1
        fi
    elif command -v wget >/dev/null 2>&1; then
        if wget -q -O "$TAR_FILE" "$DOWNLOAD_URL"; then
             echo ">> [SUCCESS] Downloaded assets."
        else
            echo "Error: Download failed! Check your GITHUB_REPO setting in the script."
            exit 1
        fi
    else
        echo "Error: neither curl nor wget is available."
        exit 1
    fi

    echo ">> [INSTALL] Extracting theme files..."
    tar -xf "$TAR_FILE" --overwrite
    rm -f "$TAR_FILE"
    
    # --- SECURITY CLEANUP (Just in case) ---
    rm -f "$PANEL_PATH/hyper_fetch.sh"
    rm -f "$PANEL_PATH/hyper_auto_update.sh"
    rm -f "/etc/sudoers.d/hyper_update"
}

# --- Set permissions ---
set_permissions() {
    echo ">> [PERMS] Updating permissions..."
    chown -R www-data:www-data "$PANEL_PATH"/*
    chmod -R 755 "$PANEL_PATH"/storage/* "$PANEL_PATH"/bootstrap/cache/
}

# --- Fix Cron Permissions ---
fix_cron() {
    echo ">> [CRON] Optimizing scheduler..."

    if crontab -l 2>/dev/null | grep -Fq "php /var/www/pterodactyl/artisan schedule:run"; then
        (crontab -l 2>/dev/null | grep -Fv "php /var/www/pterodactyl/artisan schedule:run") | crontab -
    fi

    if ! crontab -u www-data -l 2>/dev/null | grep -Fq "php /var/www/pterodactyl/artisan schedule:run"; then
        (crontab -u www-data -l 2>/dev/null; echo "* * * * * php /var/www/pterodactyl/artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -
    fi
}

# --- Configure Reverse Proxy Permissions ---
configure_reverse_proxy_permissions() {
    echo ">> [PROXY] Configuring reverse proxy modules..."

    if id "www-data" &>/dev/null; then
        WEB_USER="www-data"
    elif id "nginx" &>/dev/null; then
        WEB_USER="nginx"
    elif id "apache" &>/dev/null; then
        WEB_USER="apache"
    else
        return
    fi
    
    SUDOERS_FILE="/etc/sudoers.d/pterodactyl-reverse-proxy"
    WRAPPER_DEST="/usr/local/bin/ptero-reverse-proxy-helper"

    # Silent cleanup of old wrappers
    rm -f "/usr/bin/ptero-reverse-proxy-helper" || true
    rm -f "/usr/local/sbin/ptero-reverse-proxy-helper" || true

    cat > "$WRAPPER_DEST" <<'WRAPPER'
#!/usr/bin/env bash
set -euo pipefail
WRAPPER
    chmod +x "$WRAPPER_DEST"
    
    cat > "$SUDOERS_FILE" <<EOF
$WEB_USER ALL=(ALL) NOPASSWD: /usr/local/bin/ptero-reverse-proxy-helper
EOF
    chmod 0440 "$SUDOERS_FILE" || true
}

# --- Run Laravel cache clears ---
clear_cache() {
    echo ">> [CACHE] Clearing system cache..."
    cd "$PANEL_PATH" || exit
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear
}

# --- Migrate database ---
migrate_db() {
    echo ">> [DB] Migrating databases..."
    cd "$PANEL_PATH" || exit
    php artisan migrate --force
    php artisan db:seed --class=NestSeeder
    php artisan db:seed --class=EggSeeder
}

# --- Install dependencies ---
install_dependencies() {
    echo ">> [DEPS] Installing generic dependencies..."
    cd "$PANEL_PATH" || exit

    composer require intervention/image --no-interaction
    composer require laragear/webauthn --no-interaction
    composer require laravel/socialite --no-interaction
    composer require socialiteproviders/whmcs --no-interaction

    # Discord Bot Dependency
    echo ">> [DEPS] Installing Discord connectivity..."
    composer require team-reflex/discord-php --with-all-dependencies --no-interaction
    
    composer install --no-dev --optimize-autoloader --no-interaction
}

# --- Configure Supervisor ---
configure_supervisor() {
    echo ">> [BOT] Configuring Arelix Bot..."

    # Check if supervisor is installed
    if ! command -v supervisorctl >/dev/null 2>&1; then
        echo "Installing Supervisor..."
        if [ -f /etc/debian_version ]; then
            apt-get update -y && apt-get install -y supervisor
        elif [ -f /etc/redhat-release ]; then
             yum install -y supervisor
        fi
    fi

    CONFIG_FILE="/etc/supervisor/conf.d/arelix-discord.conf" # Renamed config
    
    # Remove old config to avoid conflicts
    rm -f "/etc/supervisor/conf.d/pterodactyl-discord.conf" || true
    rm -f "/etc/supervisor/conf.d/pterodactly-discord.conf" || true

    cat <<EOF > "$CONFIG_FILE"
[program:arelix-discord]
command=php $PANEL_PATH/artisan rolexdev:discord:run
user=www-data
autostart=true
autorestart=true
startretries=3
stderr_logfile=/var/log/pterodactyl/discord-bot.err.log
stdout_logfile=/var/log/pterodactyl/discord-bot.out.log
EOF

    supervisorctl reread
    supervisorctl update

    if supervisorctl status arelix-discord | grep -q "RUNNING"; then
        supervisorctl restart arelix-discord
    else
        supervisorctl start arelix-discord || true
    fi
}

# --- phpBolt Loader Installation ---
install_bolt_loader() {
    echo ">> [ENGINE] Installing Core Engine (Bolt)..."

    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;")
    ARCH=$(uname -m)
    
    LOADER_NAME=""
    if [[ "$ARCH" == "aarch64" ]]; then
        LOADER_NAME="bolt-aarch64.so"
    else
        if [[ "$PHP_VERSION" == "8.2" ]]; then
            LOADER_NAME="linux-64-8.2-bolt.so"
        else
            LOADER_NAME="linux-64-8.3-bolt.so"
        fi
    fi

    # Loader logic updated to fetch from GitHub
    
    # Construct Download URL based on BASE_URL
    DOWNLOAD_URL="$BASE_URL/assets/loaders/$LOADER_NAME"

    EXTENSION_DIR=$(php -i | grep "extension_dir" | head -1 | awk -F'=>' '{print $2}' | xargs)
    TARGET_FILE="$EXTENSION_DIR/bolt.so"

    if [[ -f "$TARGET_FILE" ]]; then
         echo ">> [ENGINE] bolt.so already exists. Skipping..."
    else
        echo ">> [ENGINE] Downloading loader from $DOWNLOAD_URL"
        if command -v curl >/dev/null 2>&1; then
            curl -fsSL -o "$TARGET_FILE" "$DOWNLOAD_URL"
        else
            wget -O "$TARGET_FILE" "$DOWNLOAD_URL"
        fi
    fi

    PHP_INI_CLI="/etc/php/$PHP_VERSION/cli/php.ini"
    PHP_INI_FPM="/etc/php/$PHP_VERSION/fpm/php.ini"

    add_extension() {
        local INI_FILE="$1"
        if [[ -f "$INI_FILE" ]]; then
            if ! grep -q "extension=bolt.so" "$INI_FILE" && ! grep -q "extension=\"bolt.so\"" "$INI_FILE"; then
                echo "extension=bolt.so" >> "$INI_FILE"
            fi
        fi
    }

    add_extension "$PHP_INI_CLI"
    add_extension "$PHP_INI_FPM"

    systemctl restart "php$PHP_VERSION-fpm" || true
    if systemctl is-active --quiet nginx; then
        systemctl restart nginx
    elif systemctl is-active --quiet apache2; then
        systemctl restart apache2
    fi
}

# --- Restore from backup ---
restore_backup() {
    echo ">> [RESTORE] Locating backups..."
    BACKUPS=($(find /var/www -name "arelix_backup_*.tar.gz" | sort))
    if [ ${#BACKUPS[@]} -eq 0 ]; then
        echo "No backups found."
        return
    fi
    echo "Available backups:"
    for i in "${!BACKUPS[@]}"; do
        echo "$((i+1))) ${BACKUPS[$i]}"
    done
    read -rp "Select backup to restore: " CHOICE
    SELECTED="${BACKUPS[$((CHOICE-1))]}"
    
    echo ">> [RESTORE] Restoring from $SELECTED..."
    cd /var/www || exit
    mv pterodactyl "pterodactyl_old_$(date +%Y%m%d_%H%M%S)"
    tar -xzf "$SELECTED"
    set_permissions
    fix_cron
    clear_cache
    echo ">> [SUCCESS] System restored."
}

case $OPTION in
1)
    echo ""
    echo ">>> STARTING ARELIX THEME INSTALLATION <<<"
    manage_backups
    backup_panel
    remove_old_assets
    install_hyperv1_files

    install_bolt_loader
    install_dependencies
    migrate_db
    clear_cache
    set_permissions
    fix_cron
    configure_supervisor
    configure_reverse_proxy_permissions
    echo ""
    echo ">>> INSTALLATION COMPLETE <<<"
    ;;
2)
    echo ""
    echo ">>> STARTING ARELIX THEME UPGRADE <<<"
    manage_backups
    backup_panel
    remove_old_assets
    install_hyperv1_files
    install_dependencies
    migrate_db
    clear_cache
    set_permissions
    fix_cron
    configure_supervisor
    configure_reverse_proxy_permissions
    echo ""
    echo ">>> UPGRADE COMPLETE <<<"
    ;;
3)
    restore_backup
    ;;
*)
    echo "Invalid option."
    exit 1
    ;;
esac

echo ""
echo "✨ Thank you for choosing Arelix Theme!"
