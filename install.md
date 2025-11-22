# Bankshot Wireless Display System - Installation Guide

## 🎯 Overview

This system provides **universal wireless display** for tournament information and advertisements. Works with **ANY Smart TV brand** via web browser - no additional hardware required!

### Key Features

- ✅ **Works with ALL TV brands**: Samsung, LG, Sony, TCL, Vizio, Fire TV, Roku, etc.
- ✅ **Auto-switches content**: Tournament display ↔ Advertisements
- ✅ **Survives IP changes**: Uses mDNS (`.local` addresses)
- ✅ **Zero human intervention**: Fully automatic after initial setup
- ✅ **No additional hardware**: No Chromecast, no dongles needed
- ✅ **Easy management**: Web-based media manager

---

## 📋 Hardware Requirements

### Raspberry Pi Server
- **Raspberry Pi 4** (4GB RAM recommended) or **Raspberry Pi Zero 2 W** (budget option)
- **32GB+ microSD card** (Class 10 or better)
- **Network connection** (Ethernet or WiFi)
- **Power supply** (official Raspberry Pi power supply recommended)

### Display TVs
- **Any Smart TV with web browser** (2015 or newer recommended)
- **Connected to same WiFi network** as Raspberry Pi

### Optional HDMI Display
- **HDMI cable** for direct TV connection (if you want one TV hardwired)

---

## 🚀 Quick Start Installation

### Prerequisites

**Before running the install script, you MUST have:**

1. **Raspberry Pi OS Desktop** installed (NOT Lite version)
2. **System fully updated**:
   ```bash
   sudo apt-get update
   sudo apt-get upgrade -y
   sudo reboot
   ```
3. **Git installed**:
   ```bash
   sudo apt-get install -y git
   ```
4. **Internet connection** active and working
5. **Know your Pi's IP address**:
   ```bash
   hostname -I
   ```

### Installation Steps

1. **Clone the repository:**
   ```bash
   cd ~
   git clone https://github.com/jhamilt0n/bankshot-tournament-display.git
   cd bankshot-tournament-display
   ```

2. **Run the installer:**
   ```bash
   chmod +x install.sh
   sudo ./install.sh
   ```

3. **Follow the prompts:**
   - Enter your tournament data repository URL (or use default)
   - Enter hostname for mDNS (default: `bankshot-display`)

4. **Wait for installation** (10-15 minutes)

5. **Installation complete!** The installer will show you the URLs to use.

---

## 📺 TV Setup Instructions

### Option 1: QR Code Setup (Easiest)

1. On your phone or tablet, visit:
   ```
   http://bankshot-display.local/qr_setup.php
   ```

2. On your Smart TV:
   - Open the web browser app
   - Scan the QR code displayed on your phone
   - Bookmark the page
   - **Done!**

### Option 2: Manual URL Entry

1. On your Smart TV, open the web browser

2. Navigate to:
   ```
   http://bankshot-display.local/tv.html
   ```

3. Bookmark the page (recommended)

4. **Optional:** Set as homepage for auto-load on TV startup

---

## 📱 TV Brand-Specific Instructions

### Samsung Smart TV

1. Press **Home** button on remote
2. Navigate to **Internet Browser** app
3. Enter URL: `http://bankshot-display.local/tv.html`
4. Press **Tools** → **Settings**
5. Select **Set as Homepage**
6. Enable **Full Screen Mode**

### LG webOS TV

1. Press **Home** button on remote
2. Open **Web Browser** app
3. Enter URL: `http://bankshot-display.local/tv.html`
4. Bookmark the page (Star icon)
5. Settings → **Set to launch on startup** (optional)

### Amazon Fire TV

1. Install **Silk Browser** (from Apps store)
2. Open Silk Browser
3. Enter URL: `http://bankshot-display.local/tv.html`
4. Menu → **Add to Favorites**
5. Settings → **Set as Homepage**
6. Fire TV Settings → Display → **Screen Saver: Never**

### Roku TV

1. Add **Web Browser** channel (from Roku Channel Store)
2. Open Web Browser channel
3. Enter URL: `http://bankshot-display.local/tv.html`
4. Bookmark the page

### Sony/Android TV / Google TV

1. Install **Chrome** or **Firefox** browser (from Google Play Store)
2. Open browser
3. Enter URL: `http://bankshot-display.local/tv.html`
4. Settings → **Set as Homepage**
5. Enable **Kiosk Mode** (if available)

### TCL Roku TV / TCL Google TV

Same as Roku TV or Android TV instructions above (depends on your model).

---

## 🎛️ Web Interfaces

After installation, you can access these interfaces from any device on your network:

### Main Interfaces

| Interface | URL | Purpose |
|-----------|-----|---------|
| **TV Display** | `http://bankshot-display.local/tv.html` | Main display for Smart TVs |
| **Media Manager** | `http://bankshot-display.local/media_manager.html` | Upload/manage media files |
| **QR Setup** | `http://bankshot-display.local/qr_setup.php` | QR code for easy TV setup |
| **TV Setup Guide** | `http://bankshot-display.local/tv_setup.html` | Setup instructions |

### Additional Pages

| Interface | URL | Purpose |
|-----------|-----|---------|
| **Tournament Display** | `http://bankshot-display.local/` | Main tournament view with brackets |
| **Ads Display** | `http://bankshot-display.local/ads_display.html` | Advertising rotation (HDMI) |
| **Calcutta Auction** | `http://bankshot-display.local/calcutta.html` | Live Calcutta auction display |
| **Payout Calculator** | `http://bankshot-display.local/payout_calculator.php` | Tournament payout calculator |

---

## 📁 Managing Media Files

### Upload Media

1. Visit: `http://bankshot-display.local/media_manager.html`

2. **Upload Files:**
   - Click **"Choose File"** or drag & drop
   - Supported formats: JPG, PNG, GIF, MP4, WEBM
   - Max file size: 100MB

3. **Add URLs:**
   - Enter website URL or local path
   - Examples:
     - `https://example.com`
     - `/calcutta.html`

4. **Configure Media:**
   - **Name**: Descriptive name
   - **Duration**: Display time in seconds
   - **Display On**: 
     - ☑ **Ads** - Shows on HDMI TV during business hours
     - ☑ **Tournaments** - Shows during active tournaments
   - **Schedule**: Set days and times when media should display
   - **End Date**: Optional expiration date

5. **Save Changes**

### Media Display Logic

- **Tournament Active**: Shows media marked "Display on Tournaments"
- **No Tournament**: Shows media marked "Display on Ads"
- **HDMI Display**: Shows "Ads" media during business hours only
- **Wireless TVs**: Auto-switch based on tournament status

---

## 🔧 System Management

### Check Service Status

```bash
sudo systemctl status tournament-monitor.service
sudo systemctl status web-monitor.service
sudo systemctl status hdmi-display.service
```

All should show **"active (running)"** in green.

### View Logs

```bash
# Tournament data scraper
tail -f /var/log/tournament_monitor.log

# Web display monitor
tail -f /var/log/web_monitor.log

# HDMI display manager
tail -f /var/log/hdmi_display.log
```

Press `Ctrl+C` to exit.

### Restart Services

```bash
sudo systemctl restart tournament-monitor.service
sudo systemctl restart web-monitor.service
sudo systemctl restart hdmi-display.service
```

### Update System

```bash
cd ~/bankshot-tournament-display
git pull origin main
sudo ./install.sh  # Re-run installer to update files
```

---

## 🐛 Troubleshooting

### TVs Can't Access `.local` Address

**Problem:** TV shows "Cannot connect" or "Server not found" when accessing `bankshot-display.local`

**Solutions:**

1. **Check mDNS is running on Pi:**
   ```bash
   sudo systemctl status avahi-daemon
   sudo systemctl restart avahi-daemon
   ```

2. **Use IP address instead:**
   - Find Pi's IP: `hostname -I`
   - Use `http://192.168.X.X/tv.html` instead

3. **Set static IP on router:**
   - Log into router admin panel
   - Find DHCP settings
   - Reserve IP for Pi's MAC address

### Display Not Auto-Switching

**Problem:** TV stuck on ads or tournament display

**Solutions:**

1. **Check tournament data:**
   ```bash
   cat /var/www/html/tournament_data.json
   ```

2. **Verify display flag:**
   - Should show `"display_tournament": true` for tournament
   - Should show `"display_tournament": false` for ads

3. **Check web monitor logs:**
   ```bash
   tail -20 /var/log/web_monitor.log
   ```

4. **Reload TV page:**
   - Refresh browser on TV (usually F5 or reload button)

### Tournament Data Not Updating

**Problem:** Old tournament still showing or no tournament data

**Solutions:**

1. **Check tournament monitor:**
   ```bash
   sudo systemctl status tournament-monitor.service
   tail -50 /var/log/tournament_monitor.log
   ```

2. **Manually trigger update:**
   ```bash
   cd /tmp/tournament-scraper
   git pull origin main
   ```

3. **Restart service:**
   ```bash
   sudo systemctl restart tournament-monitor.service
   ```

### Can't Upload Large Videos

**Problem:** Upload fails for files over 2MB

**Solutions:**

```bash
# Verify PHP limits are set correctly
php -i | grep -E "upload_max_filesize|post_max_size"

# Should show 100M for both
# If not, run:
sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/g' /etc/php/*/apache2/php.ini
sudo sed -i 's/post_max_size = 8M/post_max_size = 100M/g' /etc/php/*/apache2/php.ini
sudo systemctl restart apache2
```

### HDMI Display Not Starting

**Problem:** Connected HDMI TV shows no output

**Solutions:**

1. **Check service:**
   ```bash
   sudo systemctl status hdmi-display.service
   tail -20 /var/log/hdmi_display.log
   ```

2. **Verify graphical environment:**
   ```bash
   echo $DISPLAY
   # Should show: :0 or :0.0
   ```

3. **Test manually:**
   ```bash
   DISPLAY=:0 chromium --kiosk http://localhost/ads_display.html &
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
sudo chown pi:pi /var/log/web_monitor.log
sudo chown pi:pi /var/log/hdmi_display.log
```

---

## 🔄 Updating the System

### Update from GitHub

```bash
cd ~/bankshot-tournament-display
git pull origin main
sudo ./install.sh  # Re-run to update all files
```

### Update Just Web Files

```bash
cd ~/bankshot-tournament-display
sudo cp web/*.html /var/www/html/
sudo cp web/*.php /var/www/html/
sudo chown -R www-data:www-data /var/www/html
```

### Update Just Scripts

```bash
cd ~/bankshot-tournament-display
cp scripts/*.py ~/
cp scripts/*.sh ~/
chmod +x ~/*.py ~/*.sh
sudo systemctl restart tournament-monitor.service
sudo systemctl restart web-monitor.service
sudo systemctl restart hdmi-display.service
```

---

## 🗑️ Uninstalling

To completely remove the system:

```bash
cd ~/bankshot-tournament-display
chmod +x uninstall.sh
sudo ./uninstall.sh
```

The uninstall script will:
- ✅ Stop and disable all services
- ✅ Remove service files
- ✅ Remove system scripts
- ✅ Remove all web files
- ✅ Remove log files

**Preserved items:**
- Media files in `/var/www/html/media/`
- System packages (Apache, PHP, Python)
- mDNS hostname setting

To remove media files:
```bash
sudo rm -rf /var/www/html/media/
```

To reset hostname:
```bash
sudo hostnamectl set-hostname raspberrypi
```

---

## 📊 System Architecture

```
GitHub Repository
    ↓
Raspberry Pi (Web Server)
    ├── Apache/PHP (Web Interface)
    ├── Python Scripts (Data Management)
    └── mDNS Service (bankshot-display.local)
    ↓
WiFi Network
    ↓
Smart TV Browsers
    ├── Auto-discover server
    ├── Check tournament status every 30 sec
    └── Auto-switch content
```

### Data Flow

1. **Tournament Monitor** pulls data from GitHub every 60 seconds
2. **Web Monitor** logs tournament status changes
3. **TV Browsers** check `get_tournament_data.php` every 30 seconds
4. **TVs auto-switch** between tournament and ads based on status
5. **HDMI Display** shows ads during business hours only

---

## 🎓 Advanced Configuration

### Change mDNS Hostname

```bash
sudo hostnamectl set-hostname NEW-HOSTNAME
sudo systemctl restart avahi-daemon
```

TVs will now use: `http://NEW-HOSTNAME.local/tv.html`

### Adjust Business Hours

Edit HDMI display manager:

```bash
nano ~/hdmi_display_manager.sh
```

Find `is_business_hours()` function and adjust times.

Restart:
```bash
sudo systemctl restart hdmi-display.service
```

### Change Tournament Data Source

```bash
nano ~/tournament_monitor.py
```

Change:
```python
GITHUB_REPO_URL = "https://github.com/YOUR_USERNAME/YOUR_REPO.git"
```

Restart:
```bash
sudo systemctl restart tournament-monitor.service
```

### Set Static IP

```bash
sudo nano /etc/dhcpcd.conf
```

Add at end:
```
interface eth0
static ip_address=192.168.1.100/24
static routers=192.168.1.1
static domain_name_servers=192.168.1.1 8.8.8.8
```

Reboot:
```bash
sudo reboot
```

---

## 📞 Getting Help

### Check Documentation

- **Installation Guide**: This file (INSTALL.md)
- **README**: Overview and quick start
- **TV Setup Guide**: `http://bankshot-display.local/tv_setup.html`

### View Logs

```bash
# All logs in one view
tail -f /var/log/tournament_monitor.log /var/log/web_monitor.log /var/log/hdmi_display.log
```

### Test Components

```bash
# Test web server
curl http://localhost/

# Test tournament data API
curl http://localhost/get_tournament_data.php | jq

# Test media API
curl http://localhost/load_media.php | jq

# Test mDNS
avahi-browse -a
```

### Report Issues

Open a GitHub issue with:
- Error messages from logs
- Output of `sudo systemctl status SERVICE_NAME`
- Pi model and OS version
- What you've already tried

**Repository**: https://github.com/jhamilt0n/bankshot-tournament-display

---

## 📝 Quick Reference Commands

```bash
# Check all services
sudo systemctl status tournament-monitor web-monitor hdmi-display

# Restart all services
sudo systemctl restart tournament-monitor web-monitor hdmi-display

# View all logs
tail -f /var/log/*monitor*.log

# Get Pi's URLs
echo "mDNS: http://$(hostname).local/tv.html"
echo "IP: http://$(hostname -I | awk '{print $1}')/tv.html"

# Test tournament data
cat /var/www/html/tournament_data.json | jq

# Update system
cd ~/bankshot-tournament-display && git pull && sudo ./install.sh
```

---

## ✅ Installation Checklist

- [ ] Raspberry Pi OS Desktop installed
- [ ] System updated (`sudo apt-get update && upgrade`)
- [ ] Git installed
- [ ] Internet connection working
- [ ] Repository cloned
- [ ] `install.sh` executed successfully
- [ ] Services running (green status)
- [ ] Can access web interfaces
- [ ] QR setup page loads
- [ ] Media manager accessible
- [ ] First TV configured and working
- [ ] Tournament data updating

**If all checked, you're ready to go! 🎉**

---

## 🎯 Support

For questions, issues, or contributions:

- **GitHub**: https://github.com/jhamilt0n/bankshot-tournament-display
- **Issues**: https://github.com/jhamilt0n/bankshot-tournament-display/issues

---

**Installation complete! Your Bankshot Wireless Display System is ready to use.** 🎱📺
