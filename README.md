# 🎨 Arelix Theme

A modern, responsive, and feature-rich theme for Pterodactyl.

## ✨ Features
- **Modern Design**: Sleek dark theme with vibrant accents.
- **Server Splitter**: Efficiently manage sub-servers.
- **Billing System**: Integrated billing management UI.
- **Staff Requests**: Handle staff applications request directly.
- **Self-Contained**: Includes all necessary loaders and assets locally.

## 🚀 Installation & Update

**Important:** You must be root or use `sudo` to run the installer.

### 1. Clone the Repository
Clone this repository to your server.

```bash
git clone https://github.com/arish-devz/Arelix-Theme.git
cd Arelix-Theme
```

### 2. Run the Installer
Execute the installation script. This script acts as an all-in-one tool to install, update, or restore the theme.

```bash
chmod +x install.sh
sudo ./install.sh
```

### 3. Choose Your Option
The script will prompt you to choose an action:
- **1️⃣ Install Arelix Theme**: Installs the theme and the required `bolt.so` loader from the local source.
- **2️⃣ Upgrade Arelix Theme**: Updates existing theme files.
- **3️⃣ Restore from Backup**: Restores a previous Pterodactyl backup.

### 4. Uninstalling / Resetting
To effectively remove the theme and restore your panel to its previous state:

1.  Run the installer: `sudo ./install.sh`
2.  Select **Option 3: Restore from Backup**.
3.  Choose the backup created before you installed the theme.

If you wish to remove the theme installer files from your server:
```bash
cd ~
rm -rf Arelix-Theme
```

## 📦 What's Included?
This repository contains:
- **Installer**: `install.sh` for one-click setup.
- **Theme Package**: Pre-packaged theme assets in `release/ArelixTheme.tar.gz`.
- **Loaders**: included within the package for seamless installation.
- **No Clutter**: Source code is archived to keep the repository clean and professional.

## 🛠 Support
For support, please open an issue on GitHub or contact the developer.

---

## ❓ Frequently Asked Questions

**Q: Does this replace my existing theme?**
A: Yes, this will overwrite your current Pterodactyl theme files. We recommend backing up your `resources` folder first, though the installer creates a backup automatically.

**Q: How do I update?**
A: You can use the **Update** button in the admin or just re-run the `install.sh` script using the commands above.

**Q: Is it compatible with the latest Pterodactyl?**
A: Yes, it is designed for the standard Pterodactyl 1.x series.

---

## 🤝 Support & Contributing
This project is open-source. Feel free to open an issue if you encounter any bugs.

- [Report a Bug](https://github.com/arish-devz/Arelix-Theme/issues)
- [Request a Feature](https://github.com/arish-devz/Arelix-Theme/issues)

---
*Made with ❤️ by Arelix Development*
