# Complete Setup Guide

This guide will walk you through setting up the complete Bankshot Tournament Display system from scratch.

## Prerequisites

### Hardware
- **Raspberry Pi 4 Model B** (2GB RAM minimum, 4GB+ recommended)
- **MicroSD Card** (16GB minimum, 32GB+ recommended, Class 10)
- **Power Supply** (Official Raspberry Pi 4 power supply or equivalent 5V/3A USB-C)
- **Internet Connection** (Ethernet recommended, WiFi works)
- **Smart TV(s)** with web browser capability

### Optional Hardware
- **HDMI Display** (if you want a Pi-connected display)
- **Keyboard and Mouse** (for initial setup)

### Accounts Needed
- GitHub account (free)
- Access to your router (for IP address checking)

## Part 1: Raspberry Pi Initial Setup

### 1.1 Install Raspberry Pi OS

1. Download [Raspberry Pi Imager](https://www.raspberrypi.com/software/)
2. Insert your MicroSD card
3. Open Raspberry Pi Imager
4. Choose:
   - **OS**: Raspberry Pi OS (64-bit) - Recommended
   - **Storage**: Your MicroSD card
5. Click the gear icon ⚙️ for advanced options:
   - Set hostname: `bankshot-display`
   - Enable SSH
   - Set username: `pi` 
   - Set password: (your choice)
   - Configure WiFi (if needed)
6. Click **Write** and wait for completion
7. Insert card into Raspberry Pi and power on

### 1.2 Connect to Your Pi

**Option A: Direct connection**
```bash
# Connect monitor, keyboard, mouse to Pi
# Log in with your credentials
```

**Option B: SSH (headless)**
```bash
# Find Pi's IP address from your router
# Or use hostname if mDNS works
ssh pi@bankshot-display.local
# Enter your password
```

### 1.3 Update System
```bash
sudo apt update
sudo apt upgrade -y
sudo reboot
```

## Part 2: Install Tournament Display System

### 2.1 Clone Repository

```bash
cd /home/pi
git clone https://github.com/YOUR_USERNAME/bankshot-tournament-display.git
cd bankshot-tournament-display
```

### 2.2 Run Installer

```bash
sudo bash scripts/install.sh
```

**What the installer does:**
- Installs Apache web server and PHP
- Installs Python and Selenium
- Sets up mDNS (bankshot-display.local)
- Configures web directory and permissions
- Installs systemd services
- Sets up cron jobs
- Installs PHP QR code library

**During installation, you'll be asked:**
1. Confirm installation (y/n)
2. Whether to install HDMI display service (y/n)

⏱️ **Installation takes 10-15 minutes**

### 2.3 Verify Installation

```bash
# Check web server
curl http://localhost/

# Check web monitor service
sudo systemctl status web-monitor.service

# Check cron job
crontab -l | grep pull_tournament_data

# Test mDNS hostname
ping -c 3 bankshot-display.local
```

## Part 3: Set Up GitHub Actions (Tournament Scraper)

This is what automatically checks DigitalPool.com for tournaments.

### 3.1 Create GitHub Repository

1. Go to https://github.com
2. Click **New repository**
3. Name it: `bankshot-tournament-display`
4. Make it **Public** or **Private** (your choice)
5. Don't initialize with README (we'll push our existing repo)
6. Click **Create repository**

### 3.2 Push Your Local Repository

```bash
cd /home/pi/bankshot-tournament-display

# Add your GitHub repo as remote
git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git

# Push everything
git branch -M main
git push -u origin main
```

### 3.3 Enable GitHub Actions

1. Go to your repository on GitHub
2. Click **Actions** tab
3. Click **"I understand my workflows, go ahead and enable them"**
4. You should see **"Scrape Tournaments"** workflow

### 3.4 Test the Scraper

1. Go to **Actions** tab
2. Click **"Scrape Tournaments"**
3. Click **"Run workflow"** dropdown
4. Click **"Run workflow"** button
5. Wait for the workflow to complete (green checkmark)
6. Check the **Code** tab - you should see `tournament_data.json` file

### 3.5 Verify Pi is Pulling Data

```bash
# Wait a few minutes, then check
cat /var/www/html/tournament_data.json | python3 -m json.tool

# Check logs
tail -f /home/pi/logs/github_pull.log
```

## Part 4: Set Up Smart TVs

### 4.1 Get Setup Information

**On your phone or computer**, visit:
```
http://bankshot-display.local/qr_setup.php
```

This will show:
- QR code for easy TV setup
- Direct URL to use
- Backup IP-based URL

### 4.2 Configure Each TV

**Method 1: QR Code (Recommended)**
1. Open your TV's web browser
2. Look for QR code scanning feature
3. Scan the QR code from qr_setup.php
4. Bookmark the page
5. (Optional) Set as homepage

**Method 2: Manual URL Entry**
1. Open your TV's web browser
2. Navigate to: `http://bankshot-display.local/tv.html`
3. Bookmark the page
4. (Optional) Set as homepage

**For brand-specific instructions**, visit:
```
http://bankshot-display.local/tv_setup.html
```

### 4.3 Test TV Display

1. Your TV should show the rotating media (ads)
2. Open browser console (if available) - check for errors
3. Add `?debug=1` to URL to see status messages

## Part 5: Upload Media Content

### 5.1 Access Media Manager

Visit: `http://bankshot-display.local/media_manager.html`

### 5.2 Upload Your Content

**Upload Files:**
- Click "Choose Files" or drag & drop
- Supported: JPG, PNG, GIF, MP4, WEBM
- Set duration (in seconds)

**Add URLs:**
- Enter website URL or local path
- Examples:
  - `https://example.com`
  - `/calcutta.html`

**For each item, configure:**
- **Duration**: How long to display
- **Display On**: Ads, Tournaments, or both
- **Schedule**: Days and times to show
- **End Date**: Optional expiration date

### 5.3 Test Media Display

Visit `http://bankshot-display.local/ads_display.html` to preview your ad rotation.

## Part 6: Testing Tournament Display

### 6.1 Wait for a Real Tournament

The system will automatically detect when a tournament starts at Bankshot Billiards.

**What happens:**
1. GitHub Actions scrapes DigitalPool.com every 15 minutes
2. When tournament status is "In Progress", data is updated
3. Pi pulls updated data every 5 minutes
4. TVs check status every 30 seconds
5. Display automatically switches to tournament view

### 6.2 Manual Testing (Optional)

You can manually create test data:

```bash
sudo nano /var/www/html/tournament_data.json
```

Change to:
```json
{
  "tournament_name": "Test Tournament",
  "tournament_url": "https://digitalpool.com/tournaments/test/",
  "venue": "Bankshot Billiards, Hilliard",
  "date": "2024/12/25",
  "start_time": "7:00 PM",
  "status": "In Progress",
  "player_count": 16,
  "entry_fee": 15,
  "payout_data": "payouts15.json",
  "last_updated": "2024-12-25 19:00:00",
  "display_tournament": true
}
```

Save and wait 30 seconds - TVs should switch to tournament display.

## Part 7: Optional Features

### 7.1 Calcutta Auction Display

If you run Calcutta auctions, you can display them:

1. Set up Google Sheets with your auction data
2. Get the web app URL from Apps Script
3. Update `web/calcutta.html` with your URL
4. Add as URL in Media Manager: `/calcutta.html`

### 7.2 HDMI-Connected Display

If you installed the HDMI display service:

```bash
# Edit business hours
sudo nano /home/pi/hdmi_display_manager.sh

# Restart service
sudo systemctl restart hdmi-display.service
```

### 7.3 Custom Payout Structure

Edit payout percentages:
```bash
sudo nano /var/www/html/payout_calculator.php
```

## Troubleshooting

### TVs Can't Connect to .local Address

Some older TVs don't support mDNS. Use IP address instead:

```bash
# Find your Pi's IP
hostname -I

# Use this URL on TVs
http://192.168.1.XXX/tv.html
```

### Scraper Not Finding Tournaments

Check GitHub Actions logs:
1. Go to your repo on GitHub
2. Click **Actions** tab
3. Click latest workflow run
4. Expand logs and look for errors

Common issues:
- Search term incorrect (should be "Bankshot Billiards")
- DigitalPool.com website changed
- Rate limiting

### Display Not Switching

```bash
# Check web monitor
sudo systemctl status web-monitor.service

# View logs
sudo journalctl -u web-monitor.service -f

# Check tournament data
cat /var/www/html/tournament_data.json | python3 -m json.tool

# Restart service
sudo systemctl restart web-monitor.service
```

### Media Not Showing

```bash
# Check media config
cat /var/www/html/media/media_config.json | python3 -m json.tool

# Check file permissions
ls -la /var/www/html/media/

# Fix permissions
sudo chown -R www-data:www-data /var/www/html/media
sudo chmod -R 755 /var/www/html/media
```

### Pi Can't Pull from GitHub

```bash
# Check logs
tail -f /home/pi/logs/github_pull.log

# Test manually
bash /home/pi/pull_tournament_data.sh

# Check cron
crontab -l
```

## Maintenance

### Regular Tasks

**Weekly:**
- Check that TVs are still connected and displaying correctly
- Verify tournament data is updating

**Monthly:**
- Update Raspberry Pi OS: `sudo apt update && sudo apt upgrade`
- Check disk space: `df -h`
- Review logs for errors: `sudo journalctl -u web-monitor.service --since "1 month ago"`

### Backup Important Files

```bash
# Backup media config
cp /var/www/html/media/media_config.json ~/backup/

# Backup custom modifications
cp /var/www/html/*.php ~/backup/
```

## Getting Help

1. **Check Logs First:**
   ```bash
   # Web monitor
   sudo journalctl -u web-monitor.service -f
   
   # GitHub pull
   tail -f /home/pi/logs/github_pull.log
   
   # Apache errors
   sudo tail -f /var/log/apache2/error.log
   ```

2. **Check System Status:**
   ```bash
   # All services
   sudo systemctl status web-monitor.service
   sudo systemctl status apache2
   sudo systemctl status avahi-daemon
   
   # Network
   ip addr
   ping -c 3 google.com
   ```

3. **Common Commands:**
   ```bash
   # Restart everything
   sudo systemctl restart web-monitor.service
   sudo systemctl restart apache2
   
   # Pull data now
   bash /home/pi/pull_tournament_data.sh
   
   # View tournament data
   cat /var/www/html/tournament_data.json | python3 -m json.tool
   ```

## Next Steps

Now that everything is set up:

1. ✅ Add your media content via Media Manager
2. ✅ Test TVs are displaying correctly
3. ✅ Wait for next tournament to verify automatic switching
4. ✅ Bookmark the Media Manager for easy access
5. ✅ Share the TV setup URL with staff if needed

**Important URLs to Bookmark:**
- Media Manager: `http://bankshot-display.local/media_manager.html`
- TV Setup QR: `http://bankshot-display.local/qr_setup.php`
- Current Data: `http://bankshot-display.local/tournament_data.json`

---

**Questions?** Open an issue on GitHub or check the main README.md
