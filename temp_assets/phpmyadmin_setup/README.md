# phpMyAdmin Auto-Login Setup

This directory contains the necessary files and instructions to enable auto-login for phpMyAdmin with the Hyper Theme.

## Files
*   `pma-login.php`: The script that handles the "Signon" authentication.

## Installation Instructions

### 1. Install `pma-login.php`
Copy the included `pma-login.php` file to your phpMyAdmin installation directory (typically `/var/www/phpmyadmin`).

```bash
cp pma-login.php /var/www/phpmyadmin/
chown www-data:www-data /var/www/phpmyadmin/pma-login.php
chmod 644 /var/www/phpmyadmin/pma-login.php
```

### 2. Configure phpMyAdmin (`config.inc.php`)
Edit your `/var/www/phpmyadmin/config.inc.php` file and update the authentication configuration for your server.

**Find:**
```php
$cfg['Servers'][$i]['auth_type'] = 'cookie';
```

**Replace with:**
```php
$cfg['Servers'][$i]['auth_type'] = 'signon';
$cfg['Servers'][$i]['SignonSession'] = 'PMA_signon_session';
$cfg['Servers'][$i]['SignonURL'] = 'pma-login.php';
```

### 3. Configure Frontend URL
In your Pterodactyl Panel:
1.  Go to **Admin Panel** > **Theme Settings** > **Database Manager Configuration**.
2.  Set the **phpMyAdmin URL** to:
    ```
    https://<YOUR_DOMAIN>/pma-login.php?server={host}&port={port}&username={username}&password={password}&db={database}
    ```
    *(Replace `<YOUR_DOMAIN>` with your actual domain or IP)*

## Troubleshooting
*   Ensure that `pma-login.php` is readable by the web server user (`www-data`).
*   Ensure your `blowfish_secret` is set in `config.inc.php`.
