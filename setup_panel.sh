#!/bin/bash

# Arelix Pterodactyl Setup Wrapper
# This script simplifies the process of installing Pterodactyl Panel on a fresh server.
# It leverages the trusted community installation script.

set -e

echo "================================================================="
echo "   Arelix Pterodactyl Panel Setup"
echo "================================================================="
echo "This script will install Pterodactyl Panel & Wings (Daemon)."
echo "Requirement: A fresh Ubuntu 20.04/22.04 or Debian 11/12 server."
echo "================================================================="
echo ""
echo "WARNING: This should only be run on a FRESH VPS/Server."
echo "Do not run this on your Windows machine."
echo ""
read -p "Do you want to proceed with Pterodactyl Installation? (y/n): " confirm

if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
    echo "Installation aborted."
    exit 1
fi

echo "[Arelix] Fetching trusted installer..."

# Download the widely used unofficial installer which supports Panel, Wings, and Dependencies
curl -Lo install_ptero.sh https://raw.githubusercontent.com/pterodactyl-installer/pterodactyl-installer/master/install.sh
chmod +x install_ptero.sh

echo "[Arelix] Launching installer..."
echo "--- You will be asked to choose what to install."
echo "--- Select '0' to install Panel and Wings (recommended for single server)."
echo "--- Follow the on-screen prompts for Database and Admin User setup."

./install_ptero.sh

echo ""
echo "================================================================="
echo "   Installation Process Finished"
echo "================================================================="
echo "Once the panel is reachable, you can install the Arelix Theme using:"
echo "bash <(curl -s https://raw.githubusercontent.com/arish-devz/Arelix-Theme/main/install.sh)"
echo "================================================================="
