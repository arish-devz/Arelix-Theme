# 🎨 Arelix Theme for Pterodactyl

> The ultimate premium, dark-mode inspired theme for your Pterodactyl Panel.

![Arelix Theme Banner](https://via.placeholder.com/800x200.png?text=Arelix+Theme+Banner)

## ✨ Features
- **Modern Glassmorphism UI**: A stunning, high-contrast design that feels premium.
- **Fully Responsive**: Optimized for all devices, from desktops to mobile phones.
- **Customizable Colors**: Easily tweak the look to match your brand identity.
- **Optimized Performance**: Lightweight assets ensure your panel remains lightning fast.
- **Enhanced Security**:
  - Zero external tracking.
  - No license key requirement.
  - All source code is transparent and locally hosted.
- **One-Click Updates**: In-app updater connects directly to this repository.

---

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

## 📦 What's Included?
This repository is pre-packed with:
- **Loaders**: `bolt.so` for PHP 8.2, 8.3 (x86 & ARM64) are included in `Arelix_Source/loaders`.
- **Source Code**: All theme assets, views, and controllers are in `Arelix_Source`.
- **No External Downloads**: Installation uses local files, ensuring stability and security.

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
