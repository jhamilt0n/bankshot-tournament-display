# Raspberry Pi Installation Guide

Complete step-by-step instructions for installing the Bankshot Tournament Display System on a Raspberry Pi.

## Table of Contents
- [Hardware Requirements](#hardware-requirements)
- [Prerequisites](#prerequisites)
- [Installation Methods](#installation-methods)
  - [Quick Install (Recommended)](#quick-install-recommended)
  - [Manual Installation](#manual-installation)
- [Post-Installation Setup](#post-installation-setup)
- [Verification](#verification)
- [Troubleshooting](#troubleshooting)
- [Updating the System](#updating-the-system)
- [Uninstalling](#uninstalling)

---

## Hardware Requirements

- **Raspberry Pi 4** (4GB RAM or higher recommended)
- **32GB+ microSD card** (Class 10 or better)
- **Raspberry Pi OS** (Debian-based, latest version)
- **Network connection** (Ethernet or WiFi)
- **Chromecast device** (on same network)
- **HDMI TV** (for direct connection)
- **Power supply** (official Raspberry Pi power supply recommended)

## Prerequisites

**Before running the install script, you MUST have:**

### 1. Install Raspberry Pi OS

If starting fresh:

1. Download **Raspberry Pi Imager**: https://www.raspberrypi.com/software/
2. Flash **Raspberry Pi OS (64-bit, Desktop version)** to your microSD card
   - ⚠️ **IMPORTANT**: Use the **Desktop** version, NOT Lite
   - The system requires a graphical environment for Chromium
3. Boot your Raspberry Pi
4. Complete initial setup wizard:
   - Set username: **pi** (recommended)
   - Set password
   - Connect to WiFi/Ethernet
   - Update software when prompted
5. Let the Pi complete first boot setup

### 2. Update System (REQUIRED)

This is **mandatory** before running the installer:

```bash
sudo apt-get update
sudo apt-get upgrade -y
sudo reboot
```

⏱️ **Wait for reboot to complete** before continuing.

### 3. Install Git (REQUIRED)

The installer needs Git to clone the repository:

```bash
# Check if git is installed
git --version

# If not installed, install it:
sudo apt-get install -y git
```

Verify git is working:
```bash
git --version
# Should show: git version 2.x.x
```

### 4. Enable SSH (Optional but HIGHLY Recommended)

This allows you to access the Pi remotely from another computer:

```bash
sudo raspi-config
# Navigate to: Interface Options → SSH → Enable
# Select Finish and reboot if prompted
```

Now you can SSH from another computer:
```bash
ssh pi@YOUR_PI_IP
```

### 5. Connect to Your Network

Ensure your Raspberry Pi has a stable internet connection:

```bash
# Test internet connection
ping -c 4 google.com

# Check your IP address
hostname -I
```

**Write down your IP address** - you'll need it later!

### 6. Verify Graphical Environment is Running

The HDMI display requires a graphical environment:

```bash
# Check if X server is running
echo $DISPLAY
# Should show: :0 or :0.0
```

If this shows nothing, you may have installed **Raspberry Pi OS Lite** by mistake. You need the **Desktop** version.

---

## What the Install Script Will Install

Once prerequisites are met, the `install.sh` script will automatically install:

### System Packages
- ✅ Apache2 web server
- ✅ PHP 8+ with extensions (xml, gd, mbstring)
- ✅ Python 3 with pip
- ✅ jq (JSON processor)
- ✅ Chromium browser
- ✅ curl and unzip

### Python Packages
- ✅ requests (HTTP library)
- ✅ catt (Cast All The Things - Chromecast control)

### PHP Packages
- ✅ Composer (PHP package manager)
- ✅ chillerlan/php-qrcode (QR code generation)

### System Scripts
- ✅ catt_monitor.py (Chromecast controller)
- ✅ hdmi_display_manager.sh (HDMI display manager)
- ✅ tournament_monitor.py (Tournament data scraper)

### System Services
- ✅ tournament-monitor.service
- ✅ catt-monitor.service
- ✅ hdmi-display.service

### Web Files
- ✅ index.php (Main tournament display)
- ✅ ads_display.html (HDMI advertising display)
- ✅ calcutta.html (Calcutta auction interface)
- ✅ media_manager.html (Media upload interface)
- ✅ payout_calculator.php (Tournament payout calculator)
- ✅ calculate_payouts.php (Payout calculation backend)
- ✅ generate_qr.php (QR code generator)
- ✅ get_tournament_data.php (Tournament data API)
- ✅ load_media.php (Media loading API)
- ✅ save_media.php (Media saving backend)
- ✅ upload_file.php (File upload handler)

**Installation time:** 10-15 minutes (depending on internet speed)

---

## Pre-Installation Checklist

Before running `./install.sh`, verify:

- [ ] Raspberry Pi OS **Desktop** version installed
- [ ] System fully updated (`sudo apt-get update && sudo apt-get upgrade`)
- [ ] Git installed and working (`git --version`)
- [ ] Internet connection active (`ping google.com`)
- [ ] IP address known (`hostname -I`)
- [ ] Graphical environment running (`echo $DISPLAY` shows `:0`)
- [ ] SSH enabled (optional, for remote access)

If all boxes are checked, you're ready to proceed with installation!

---

## Installation Methods

## Quick Install (Recommended)

This is the fastest method - approximately **10-15 minutes**.

### Step 1: Clone the Repository

```bash
cd ~
git clone https://github.com/jhamilt0n/bankshot-tournament-display.git
cd bankshot-tournament-display
```

### Step 2: Run the Installer

```bash
chmod +x install.sh
sudo ./install.sh
```

### Step 3: Follow the Prompts

The installer will ask:
```
Enter your GitHub tournament data repository URL (or press Enter for default):
```

- Press **Enter** to use the default: `https://github.com/jhamilt0n/tournament-scraper.git`
- Or enter your own tournament data repository URL

### Step 4: Wait for Installation

The installer will automatically:
- ✅ Install all dependencies (Apache, PHP, Python, etc.)
- ✅ Install CATT (Chromecast control)
- ✅ Configure PHP for file uploads
- ✅ Install PHP QR Code library
- ✅ Copy all web files
- ✅ Install system scripts
- ✅ Create and start services
- ✅ Set up log files

### Step 5: Installation Complete!

You'll see:
```
============================================================
  INSTALLATION COMPLETE!
============================================================

Next Steps:

1. Upload media files:
   http://192.168.X.X/media_manager.html

2. View displays:
   Main Display: http://192.168.X.X/
   HDMI Display: http://192.168.X.X/ads_display.html
   Calcutta: http://192.168.X.X/calcutta.html
   Payout Calculator: http://192.168.X.X/payout_calculator.php
   Chromecast: Will auto-cast when tournament starts

3. Check service status:
   sudo systemctl status tournament-monitor.service
   sudo systemctl status catt-monitor.service
   sudo systemctl status hdmi-display.service
```

**That's it! Skip to [Post-Installation Setup](#post-installation-setup)**

---

## Manual Installation

Use this method if the automatic installer fails or you want more control.

### Step 1: Install System Packages

```bash
sudo apt-get update
sudo apt-get install -y \
    apache2 \
    php \
    php-cli \
    php-xml \
    php-gd \
    php-mbstring \
    git \
    python3 \
    python3-pip \
    jq \
    chromium \
    curl \
    unzip
```

### Step 2: Install Python Packages

```bash
pip3 install --break-system-packages requests catt
```

### Step 3: Install Composer

```bash
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### Step 4: Configure PHP

```bash
# Increase upload limits
sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/g' /etc/php/*/apache2/php.ini
sudo sed -i 's/post_max_size = 8M/post_max_size = 100M/g' /etc/php/*/apache2/php.ini
sudo sed -i 's/max_execution_time = 30/max_execution_time = 300/g' /etc/php/*/apache2/php.ini
```

### Step 5: Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo usermod -a -G www-data pi
```

### Step 6: Start Apache

```bash
sudo systemctl enable apache2
sudo systemctl start apache2
```

### Step 7: Install PHP QR Code Library

```bash
cd /var/www/html
sudo composer require chillerlan/php-qrcode
```

### Step 8: Clone Repository and Copy Files

```bash
cd ~
git clone https://github.com/jhamilt0n/bankshot-tournament-display.git
cd bankshot-tournament-display

# Copy web files
sudo cp web/ads_display.html /var/www/html/
sudo cp web/calculate_payouts.php /var/www/html/
sudo cp web/calcutta.html /var/www/html/
sudo cp web/generate_qr.php /var/www/html/
sudo cp web/get_tournament_data.php /var/www/html/
sudo cp web/index.php /var/www/html/
sudo cp web/load_media.php /var/www/html/
sudo cp web/media_manager.html /var/www/html/
sudo cp web/payout_calculator.php /var/www/html/
sudo cp web/save_media.php /var/www/html/
sudo cp web/upload_file.php /var/www/html/

sudo chown -R www-data:www-data /var/www/html

# Copy scripts
cp catt_monitor.py ~/
cp hdmi_display_manager.sh ~/
cp tournament_monitor.py ~/
chmod +x ~/*.py ~/*.sh

# Copy services
sudo cp services/catt-monitor.service /etc/systemd/system/
sudo cp services/hdmi-display.service /etc/systemd/system/
sudo cp services/tournament-monitor.service /etc/systemd/system/
```

### Step 9: Create Directories and Logs

```bash
sudo mkdir -p /var/www/html/media
sudo chown -R www-data:www-data /var/www/html/media

sudo touch /var/log/tournament_monitor.log
sudo touch /var/log/catt_monitor.log
sudo touch /var/log/hdmi_display.log
sudo chown pi:pi /var/log/*.log
sudo chmod 666 /var/log/catt_monitor.log
```

### Step 10: Update GitHub Repository URL

```bash
nano ~/tournament_monitor.py
# Change line: GITHUB_REPO_URL = "https://github.com/jhamilt0n/tournament-scraper.git"
# To your repository URL
```

### Step 11: Enable and Start Services

```bash
sudo systemctl daemon-reload
sudo systemctl enable tournament-monitor.service
sudo systemctl enable catt-monitor.service
sudo systemctl enable hdmi-display.service

sudo systemctl start tournament-monitor.service
sudo systemctl start catt-monitor.service
sudo systemctl start hdmi-display.service
```

---

## Post-Installation Setup

### 1. Find Your Pi's IP Address

```bash
hostname -I
```

Example output: `192.168.4.84`

### 2. Access Media Manager

Open a web browser on any device on your network:

```
http://YOUR_PI_IP/media_manager.html
```

Example: `http://192.168.4.84/media_manager.html`

### 3. Upload Media Files

1. Click **"Choose File"** or **"Upload File"**
2. Select your image or video
3. Configure settings:
   - **Name**: Give it a descriptive name
   - **Display Type**: 
     - Select **"ad"** for HDMI TV (always shows)
     - Select **"tournament"** for Chromecast (shows during tournaments)
   - **Duration**: How long to display (in seconds)
   - **Active**: Check to enable
4. Click **"Add Media"**
5. Click **"Save"** at the bottom

### 4. Configure Chromecast (Optional)

Find your Chromecast device:

```bash
catt scan
```

If you have multiple Chromecasts, set a default:

```bash
# Edit ~/.cattrc
nano ~/.cattrc

# Add:
device: "YOUR_CHROMECAST_NAME"
```

### 5. Test HDMI Display

The HDMI display will automatically start during business hours. To test immediately:

```bash
DISPLAY=:0 chromium --kiosk http://localhost/ads_display.html &
```

Press `Ctrl+C` to stop, or just reboot.

### 6. Access Other Features

All features are accessible via web browser:

- **Main Tournament Display**: `http://YOUR_PI_IP/`
- **HDMI Ads Display**: `http://YOUR_PI_IP/ads_display.html`
- **Calcutta Auction**: `http://YOUR_PI_IP/calcutta.html`
- **Payout Calculator**: `http://YOUR_PI_IP/payout_calculator.php`
- **Media Manager**: `http://YOUR_PI_IP/media_manager.html`

---

## Verification

### Check All Services Are Running

```bash
sudo systemctl status tournament-monitor.service
sudo systemctl status catt-monitor.service
sudo systemctl status hdmi-display.service
```

All should show **"active (running)"** in green.

### View Logs

```bash
# Tournament data scraper
tail -f /var/log/tournament_monitor.log

# Chromecast controller
tail -f /var/log/catt_monitor.log

# HDMI display manager
tail -f /var/log/hdmi_display.log
```

Press `Ctrl+C` to exit.

### Test Web Interface

Open in browser:
- **Main Display**: `http://YOUR_PI_IP/`
- **HDMI Display**: `http://YOUR_PI_IP/ads_display.html`
- **Media Manager**: `http://YOUR_PI_IP/media_manager.html`
- **Calcutta**: `http://YOUR_PI_IP/calcutta.html`
- **Payout Calculator**: `http://YOUR_PI_IP/payout_calculator.php`

### Check Tournament Data

```bash
cat /var/www/html/tournament_data.json
```

Should show current tournament info (if one exists for today).

### Test CATT

```bash
# Scan for Chromecasts
catt scan

# Test casting (replace YOUR_PI_IP)
catt cast_site http://YOUR_PI_IP/
```

---

## Troubleshooting

### Services Won't Start

**Check service logs:**
```bash
sudo journalctl -u tournament-monitor.service -n 50
sudo journalctl -u catt-monitor.service -n 50
sudo journalctl -u hdmi-display.service -n 50
```

**Restart services:**
```bash
sudo systemctl restart tournament-monitor.service
sudo systemctl restart catt-monitor.service
sudo systemctl restart hdmi-display.service
```

### Apache Not Working

```bash
# Check Apache status
sudo systemctl status apache2

# View error log
sudo tail -50 /var/log/apache2/error.log

# Restart Apache
sudo systemctl restart apache2
```

### Can't Upload Large Videos

```bash
# Check PHP limits
php -i | grep -E "upload_max_filesize|post_max_size"

# Should show 100M for both
# If not, re-run:
sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/g' /etc/php/*/apache2/php.ini
sudo sed -i 's/post_max_size = 8M/post_max_size = 100M/g' /etc/php/*/apache2/php.ini
sudo systemctl restart apache2
```

### CATT Not Found

```bash
# Install CATT
pip3 install --break-system-packages catt

# Verify installation
which catt
catt --version
```

### Chromium Won't Start on HDMI

```bash
# Check if running
ps aux | grep chromium

# Check logs
tail -20 /var/log/hdmi_display.log

# Test manually
DISPLAY=:0 chromium --version
DISPLAY=:0 chromium --kiosk http://localhost/ads_display.html &
```

### No Media Showing

```bash
# Check media config exists
cat /var/www/html/media/media_config.json

# Check uploaded files
ls -la /var/www/html/media/

# Test API
curl http://localhost/load_media.php | jq
```

### Permission Errors

```bash
# Fix web directory permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Fix media directory
sudo chown -R www-data:www-data /var/www/html/media
sudo chmod -R 755 /var/www/html/media

# Fix log permissions
sudo chown pi:pi /var/log/tournament_monitor.log
sudo chown pi:pi /var/log/catt_monitor.log
sudo chown pi:pi /var/log/hdmi_display.log
sudo chmod 666 /var/log/catt_monitor.log
```

### QR Code Not Generating

```bash
# Check if QR code library is installed
ls -la /var/www/html/vendor/chillerlan

# If not found, reinstall:
cd /var/www/html
sudo composer require chillerlan/php-qrcode
```

---

## Updating the System

### Update from GitHub

```bash
cd ~/bankshot-tournament-display
git pull origin main

# Re-run installer to update files
sudo ./install.sh
```

### Update Just the Web Files

```bash
cd ~/bankshot-tournament-display

sudo cp web/ads_display.html /var/www/html/
sudo cp web/calculate_payouts.php /var/www/html/
sudo cp web/calcutta.html /var/www/html/
sudo cp web/generate_qr.php /var/www/html/
sudo cp web/get_tournament_data.php /var/www/html/
sudo cp web/index.php /var/www/html/
sudo cp web/load_media.php /var/www/html/
sudo cp web/media_manager.html /var/www/html/
sudo cp web/payout_calculator.php /var/www/html/
sudo cp web/save_media.php /var/www/html/
sudo cp web/upload_file.php /var/www/html/

sudo chown -R www-data:www-data /var/www/html
```

### Update Just the Scripts

```bash
cd ~/bankshot-tournament-display
cp catt_monitor.py ~/
cp hdmi_display_manager.sh ~/
cp tournament_monitor.py ~/
chmod +x ~/*.py ~/*.sh

# Restart services
sudo systemctl restart tournament-monitor.service
sudo systemctl restart catt-monitor.service
sudo systemctl restart hdmi-display.service
```

### Update Services

```bash
cd ~/bankshot-tournament-display
sudo cp services/catt-monitor.service /etc/systemd/system/
sudo cp services/hdmi-display.service /etc/systemd/system/
sudo cp services/tournament-monitor.service /etc/systemd/system/

sudo systemctl daemon-reload
sudo systemctl restart tournament-monitor.service
sudo systemctl restart catt-monitor.service
sudo systemctl restart hdmi-display.service
```

---

## Uninstalling

To completely remove the system:

```bash
cd ~/bankshot-tournament-display
chmod +x uninstall.sh
sudo ./uninstall.sh
```

The uninstall script will:
- ✅ Stop and disable all services
- ✅ Remove service files
- ✅ Remove system scripts (catt_monitor.py, hdmi_display_manager.sh, tournament_monitor.py)
- ✅ Remove all web files
- ✅ Remove log files
- ✅ Remove Composer packages

**Preserved items:**
- Media files in `/var/www/html/media/`
- System packages (Apache, PHP, Python packages)
- Composer (system-wide)

To remove media files:
```bash
sudo rm -rf /var/www/html/media/
```

To remove Python packages:
```bash
pip3 uninstall catt requests
```

To remove system packages:
```bash
sudo apt-get remove apache2 php php-cli php-xml php-gd php-mbstring chromium
sudo apt-get autoremove
```

---

## Additional Configuration

### Adjust Business Hours

Edit the HDMI display manager:

```bash
nano ~/hdmi_display_manager.sh
```

Find the `is_business_hours()` function and adjust times.

Then restart:
```bash
sudo systemctl restart hdmi-display.service
```

### Change Tournament Data Source

```bash
nano ~/tournament_monitor.py
```

Change line:
```python
GITHUB_REPO_URL = "https://github.com/YOUR_USERNAME/YOUR_REPO.git"
```

Restart:
```bash
sudo systemctl restart tournament-monitor.service
```

### Set Static IP Address

```bash
sudo nano /etc/dhcpcd.conf
```

Add at the end:
```
interface eth0
static ip_address=192.168.4.84/24
static routers=192.168.4.1
static domain_name_servers=192.168.4.1 8.8.8.8
```

Reboot:
```bash
sudo reboot
```

---

## Repository Structure

The `bankshot-tournament-display` repository has the following structure:

```
bankshot-tournament-display/
├── catt_monitor.py              # Chromecast controller script
├── hdmi_display_manager.sh      # HDMI display manager script
├── tournament_monitor.py        # Tournament data scraper script
├── install.sh                   # Automated installer
├── uninstall.sh                 # Automated uninstaller
├── services/
│   ├── catt-monitor.service     # Chromecast service definition
│   ├── hdmi-display.service     # HDMI display service definition
│   └── tournament-monitor.service # Tournament monitor service definition
├── web/
│   ├── ads_display.html         # HDMI advertising display
│   ├── calculate_payouts.php    # Payout calculation backend
│   ├── calcutta.html            # Calcutta auction interface
│   ├── generate_qr.php          # QR code generator
│   ├── get_tournament_data.php  # Tournament data API
│   ├── index.php                # Main tournament display
│   ├── load_media.php           # Media loading API
│   ├── media_manager.html       # Media upload interface
│   ├── payout_calculator.php    # Tournament payout calculator
│   ├── save_media.php           # Media saving backend
│   └── upload_file.php          # File upload handler
├── Directory Structure          # Visual directory structure
├── LICENSE                      # License file
├── RASPBERRY_PI_INSTALL.md      # This file
└── install.md                   # Installation documentation
```

---

## Getting Help

If you encounter issues:

1. **Check logs** for error messages
2. **Verify services** are running
3. **Test each component** individually
4. **Review this guide** for troubleshooting steps
5. **Open a GitHub issue** with:
   - Error messages
   - Log file contents
   - What you've already tried
   - Pi model and OS version

## Support Information

- **Repository**: https://github.com/jhamilt0n/bankshot-tournament-display
- **Issues**: https://github.com/jhamilt0n/bankshot-tournament-display/issues
- **Documentation**: See README.md

---

## Quick Command Reference

```bash
# Check services
sudo systemctl status tournament-monitor.service
sudo systemctl status catt-monitor.service
sudo systemctl status hdmi-display.service

# Restart services
sudo systemctl restart tournament-monitor.service
sudo systemctl restart catt-monitor.service
sudo systemctl restart hdmi-display.service

# View logs
tail -f /var/log/tournament_monitor.log
tail -f /var/log/catt_monitor.log
tail -f /var/log/hdmi_display.log

# Find IP address
hostname -I

# Test Chromecast
catt scan
catt cast_site http://YOUR_PI_IP/

# Manual HDMI test
DISPLAY=:0 chromium --kiosk http://localhost/ads_display.html &

# View tournament data
cat /var/www/html/tournament_data.json

# View media config
cat /var/www/html/media/media_config.json

# Check media files
ls -la /var/www/html/media/

# Update from GitHub
cd ~/bankshot-tournament-display && git pull

# Reinstall/update all files
cd ~/bankshot-tournament-display && sudo ./install.sh

# Uninstall completely
cd ~/bankshot-tournament-display && sudo ./uninstall.sh
```

---

**Installation complete! Your Bankshot Tournament Display System is ready to use.** 🎱🚀
