# Complete Migration Guide: Old System → New Consolidated System

This guide will walk you through **safely removing** your old system and **installing** the new consolidated one.

## ⚠️ Before You Start

### What You'll Need
- SSH access to your Raspberry Pi
- Your GitHub username and repository access
- About 30-60 minutes
- Backup of any custom media files

### What Will Change
- ❌ **Removed**: CATT/Chromecast casting system
- ❌ **Removed**: Multiple scraper versions
- ❌ **Removed**: Status-based switcher
- ✅ **Added**: Web-based TV system (more reliable)
- ✅ **Added**: One unified scraper
- ✅ **Added**: Automated installer
- ✅ **Added**: Media manager web UI

---

## Part 1: Backup Your Current System

### 1.1 Backup Media Files

```bash
# Create backup directory
mkdir -p ~/backup_$(date +%Y%m%d)

# Backup web files (includes media)
sudo tar czf ~/backup_$(date +%Y%m%d)/web_backup.tar.gz /var/www/html/

# Backup current tournament data
cp /var/www/html/tournament_data.json ~/backup_$(date +%Y%m%d)/ 2>/dev/null || true
cp /home/pi/tournament_data.json ~/backup_$(date +%Y%m%d)/ 2>/dev/null || true

# Backup media directory specifically
if [ -d /var/www/html/media ]; then
    cp -r /var/www/html/media ~/backup_$(date +%Y%m%d)/
fi

# List what you backed up
echo "✓ Backup created in: ~/backup_$(date +%Y%m%d)/"
ls -lh ~/backup_$(date +%Y%m%d)/
```

### 1.2 Document Current Configuration

```bash
# Save list of installed packages
dpkg --get-selections > ~/backup_$(date +%Y%m%d)/installed_packages.txt

# Save current cron jobs
crontab -l > ~/backup_$(date +%Y%m%d)/crontab_backup.txt 2>/dev/null || echo "No crontab" > ~/backup_$(date +%Y%m%d)/crontab_backup.txt

# Save list of services
systemctl list-units --type=service --state=running > ~/backup_$(date +%Y%m%d)/running_services.txt

# Save network config
ip addr > ~/backup_$(date +%Y%m%d)/network_config.txt

echo "✓ Configuration documented"
```

### 1.3 Test Backup

```bash
# Verify backup file exists and has content
if [ -f ~/backup_$(date +%Y%m%d)/web_backup.tar.gz ]; then
    echo "✓ Backup file created: $(du -h ~/backup_$(date +%Y%m%d)/web_backup.tar.gz | cut -f1)"
else
    echo "✗ Backup failed! Do not proceed."
    exit 1
fi

# Test extraction (don't actually extract, just verify)
tar tzf ~/backup_$(date +%Y%m%d)/web_backup.tar.gz > /dev/null && echo "✓ Backup file is valid" || echo "✗ Backup file is corrupted!"
```

---

## Part 2: Clean Up Old System

### 2.1 Stop All Old Services

```bash
echo "Stopping old services..."

# Stop CATT/Chromecast services
sudo systemctl stop catt-monitor.service 2>/dev/null || true
sudo systemctl disable catt-monitor.service 2>/dev/null || true

# Stop old tournament monitors
sudo systemctl stop tournament-monitor.service 2>/dev/null || true
sudo systemctl disable tournament-monitor.service 2>/dev/null || true

# Stop old scraper services
pkill -f tournament_monitor.py 2>/dev/null || true
pkill -f catt_monitor.py 2>/dev/null || true
pkill -f smart_switcher 2>/dev/null || true

# Stop any running Chromium instances (from CATT)
pkill -f chromium 2>/dev/null || true

echo "✓ Old services stopped"
```

### 2.2 Remove Old Cron Jobs

```bash
echo "Cleaning up cron jobs..."

# Backup current crontab again (just in case)
crontab -l > ~/crontab_before_cleanup.txt 2>/dev/null || true

# Remove old cron entries
crontab -l 2>/dev/null | grep -v "tournament_monitor" | grep -v "smart_switcher" | grep -v "catt" | crontab - 2>/dev/null || true

# Verify crontab is now clean
echo "Current crontab:"
crontab -l 2>/dev/null || echo "(empty)"

echo "✓ Cron jobs cleaned"
```

### 2.3 Remove Old Scripts and Services

```bash
echo "Removing old scripts..."

# Remove old Python scripts
rm -f ~/tournament_monitor.py 2>/dev/null || true
rm -f ~/bankshot_monitor_status.py 2>/dev/null || true
rm -f ~/catt_monitor.py 2>/dev/null || true
rm -f ~/smart_switcher_status.py 2>/dev/null || true
rm -f ~/boot_tournament_check.sh 2>/dev/null || true

# Remove old service files
sudo rm -f /etc/systemd/system/catt-monitor.service 2>/dev/null || true
sudo rm -f /etc/systemd/system/tournament-monitor.service 2>/dev/null || true
sudo rm -f /etc/systemd/system/tournament-boot-check.service 2>/dev/null || true

# Reload systemd
sudo systemctl daemon-reload

echo "✓ Old scripts removed"
```

### 2.4 Clean Up Old Repository (if exists)

```bash
echo "Cleaning up old repositories..."

# Remove old tournament-scraper repo if it exists
if [ -d ~/tournament-scraper ]; then
    echo "Found old tournament-scraper directory"
    mv ~/tournament-scraper ~/backup_$(date +%Y%m%d)/old_tournament-scraper
    echo "✓ Moved to backup"
fi

# Remove any other old repo copies
find ~ -maxdepth 1 -type d -name "*tournament*" -not -name "backup_*" 2>/dev/null | while read dir; do
    echo "Found: $dir"
    read -p "Move $dir to backup? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        mv "$dir" ~/backup_$(date +%Y%m%d)/
    fi
done

echo "✓ Repositories cleaned"
```

### 2.5 Clean Up CATT/Chromecast Stuff

```bash
echo "Removing CATT/Chromecast components..."

# Remove CATT Python package
pip3 uninstall -y catt 2>/dev/null || true

# Remove CATT cache/config
rm -rf ~/.config/catt 2>/dev/null || true
rm -rf ~/.cache/catt 2>/dev/null || true

# Remove CATT binary if installed
sudo rm -f /usr/local/bin/catt 2>/dev/null || true
rm -f ~/.local/bin/catt 2>/dev/null || true

echo "✓ CATT removed"
```

### 2.6 Clean Up Web Directory (CAREFUL!)

```bash
echo "Cleaning web directory..."

# We'll keep /var/www/html but remove specific old files
cd /var/www/html

# Remove old PHP files we won't use
sudo rm -f index2.php 2>/dev/null || true
sudo rm -f old_index.php 2>/dev/null || true

# We'll keep tournament_data.json and media/ for now
# The installer will handle the rest

echo "✓ Web directory cleaned (kept media and data)"
```

### 2.7 Verify Cleanup

```bash
echo ""
echo "=== CLEANUP VERIFICATION ==="
echo ""

# Check for old services
echo "Old services still running:"
ps aux | grep -E "catt|tournament_monitor|smart_switcher" | grep -v grep || echo "(none - good!)"

echo ""
echo "Cron jobs:"
crontab -l 2>/dev/null || echo "(empty)"

echo ""
echo "Backup location:"
ls -lh ~/backup_$(date +%Y%m%d)/

echo ""
echo "✓ Cleanup complete!"
echo ""
```

---

## Part 3: Install New Consolidated System

### 3.1 Clone New Repository

```bash
echo "Installing new system..."

# Go to home directory
cd /home/pi

# Clone the new consolidated repository
# Replace YOUR_USERNAME with your GitHub username
git clone https://github.com/YOUR_USERNAME/bankshot-tournament-display.git

# Verify it downloaded
if [ -d ~/bankshot-tournament-display ]; then
    echo "✓ Repository cloned successfully"
    cd ~/bankshot-tournament-display
    ls -la
else
    echo "✗ Failed to clone repository"
    echo "Check your GitHub URL and try again"
    exit 1
fi
```

### 3.2 Run the Installer

```bash
cd ~/bankshot-tournament-display

# Run the installer
sudo bash scripts/install.sh

# The installer will:
# ✓ Update system packages
# ✓ Install Apache, PHP, Python dependencies
# ✓ Set up web server
# ✓ Configure mDNS (bankshot-display.local)
# ✓ Copy web files
# ✓ Set up systemd service
# ✓ Configure cron jobs
# ✓ Install PHP QR code library

# Follow the prompts:
# 1. Confirm installation (y)
# 2. Optional: Install HDMI display service (y/n)

# This takes about 10-15 minutes
```

### 3.3 Verify Installation

```bash
echo "Verifying installation..."

# Check web server
curl -s http://localhost/ > /dev/null && echo "✓ Web server running" || echo "✗ Web server not responding"

# Check mDNS
ping -c 2 bankshot-display.local > /dev/null 2>&1 && echo "✓ mDNS working" || echo "⚠ mDNS not working (use IP address)"

# Check services
sudo systemctl is-active apache2 && echo "✓ Apache active"
sudo systemctl is-active web-monitor.service && echo "✓ Web monitor active"
sudo systemctl is-active avahi-daemon && echo "✓ Avahi active"

# Check cron
crontab -l | grep pull_tournament_data && echo "✓ Cron job configured"

# Check files
[ -f /var/www/html/index.php ] && echo "✓ Web files installed"
[ -f /home/pi/pull_tournament_data.sh ] && echo "✓ Pull script installed"

echo ""
echo "✓ Installation verified!"
```

### 3.4 Restore Your Media Files

```bash
echo "Restoring media files..."

# Copy media from backup
if [ -d ~/backup_$(date +%Y%m%d)/media ]; then
    sudo cp -r ~/backup_$(date +%Y%m%d)/media/* /var/www/html/media/ 2>/dev/null || true
    sudo chown -R www-data:www-data /var/www/html/media/
    sudo chmod -R 755 /var/www/html/media/
    echo "✓ Media files restored"
    echo "   Now add them via Media Manager: http://bankshot-display.local/media_manager.html"
else
    echo "⚠ No media backup found - you'll need to re-upload media"
fi
```

---

## Part 4: Update GitHub Repository

### 4.1 Create/Update GitHub Repository

```bash
# On your computer or Pi, you need to:
# 1. Go to https://github.com
# 2. Create a new repository: "bankshot-tournament-display"
#    - Can be Public or Private
#    - Don't initialize with README (we have files already)
# 3. Copy the repository URL
```

### 4.2 Push New Code to GitHub

```bash
cd ~/bankshot-tournament-display

# If this is a fresh clone, set your GitHub credentials
git config user.name "Your Name"
git config user.email "your.email@example.com"

# If you cloned from a template, update the remote
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git

# Make sure you're on main branch
git branch -M main

# Push everything
git push -u origin main

# If you get authentication errors, you may need to set up a Personal Access Token:
# See: https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token
```

### 4.3 Enable GitHub Actions

```bash
# This must be done in the GitHub web interface:

# 1. Go to your repository on GitHub.com
# 2. Click the "Actions" tab
# 3. Click "I understand my workflows, go ahead and enable them"
# 4. You should see "Scrape Tournaments" workflow listed
```

### 4.4 Test GitHub Actions

```bash
# In GitHub web interface:

# 1. Click "Actions" tab
# 2. Click "Scrape Tournaments" workflow
# 3. Click "Run workflow" dropdown
# 4. Click "Run workflow" button
# 5. Wait for it to complete (should take 2-3 minutes)
# 6. Check for green checkmark ✓

# 7. Go back to "Code" tab
# 8. Look for "tournament_data.json" file
# 9. Click it to verify it has content

# If successful, the scraper is working!
```

### 4.5 Verify Pi Can Pull from GitHub

```bash
# Wait a few minutes, then on your Pi:

# Manually trigger a pull
bash /home/pi/pull_tournament_data.sh

# Check the log
cat /home/pi/logs/github_pull.log

# Verify tournament data was updated
cat /var/www/html/tournament_data.json | python3 -m json.tool

# Should show recent "last_updated" timestamp
```

---

## Part 5: Reconfigure Your TVs

### 5.1 Get Your TV Setup Information

```bash
# On your phone or computer, visit:
# http://bankshot-display.local/qr_setup.php

# This shows:
# - QR code for easy setup
# - Direct URL for manual entry
# - Backup IP-based URL

# If .local doesn't work, find IP:
hostname -I
# Then use: http://192.168.1.XXX/qr_setup.php
```

### 5.2 Configure Each TV

**For each TV, do this:**

1. **Disconnect any Chromecast** (if you have them)
   - Not needed anymore!

2. **Open TV's Web Browser**
   - Samsung: Press Home → Internet Browser
   - LG: Press Home → Web Browser
   - Fire TV: Open Silk Browser
   - Roku: Open Web Browser channel
   - Others: Find browser in apps

3. **Navigate to TV Page**
   - **Best**: Scan QR code from qr_setup.php
   - **Or**: Manually enter: `http://bankshot-display.local/tv.html`
   - **Or**: Use IP: `http://192.168.1.XXX/tv.html`

4. **Bookmark the Page**
   - This is important! You'll use this bookmark to reload

5. **Optional: Set as Homepage**
   - Makes TV auto-load on startup

6. **Test It**
   - Should show rotating ads
   - Wait 30 seconds, refresh - should still work
   - Check browser console (if available) for errors

### 5.3 Verify TV Auto-Switching

```bash
# To test auto-switching, temporarily set display_tournament to true:

sudo nano /var/www/html/tournament_data.json

# Change:
#   "display_tournament": false
# To:
#   "display_tournament": true

# Save and wait 30 seconds
# TV should switch to tournament display

# Change it back:
#   "display_tournament": false
# Save and wait 30 seconds
# TV should switch back to ads

# ✓ If this works, your TVs are properly configured!
```

---

## Part 6: Upload Media Content

### 6.1 Access Media Manager

```bash
# On any device with a browser, visit:
# http://bankshot-display.local/media_manager.html

# If .local doesn't work, use IP address:
# http://192.168.1.XXX/media_manager.html
```

### 6.2 Upload Your Content

**Upload Files:**
1. Click "Choose Files" or drag & drop
2. Select images (.jpg, .png, .gif) or videos (.mp4, .webm)
3. Files automatically upload

**Add URLs:**
1. Enter website URL: `https://example.com`
2. Or local path: `/calcutta.html`
3. Click "Add URL"

**For each item, configure:**
- **Duration**: How many seconds to display (10-30 typical)
- **Display On**: 
  - ✅ Ads (shows during non-tournament times)
  - ✅ Tournaments (shows during tournaments)
  - Or both!
- **Schedule**: 
  - Select days of week
  - Set start/end times
  - Or use "Per-Day Times" for different times each day
- **Optional End Date**: Auto-remove content after a date

**Reorder Content:**
- Drag and drop cards to reorder
- Display order matches the order shown

**Enable/Disable:**
- Click "Enable" or "Disable" button on each card
- Disabled items won't display

### 6.3 Test Media Display

```bash
# On any device, visit:
# http://bankshot-display.local/ads_display.html

# Should see your media rotating
# Check that timing is correct
# Verify all items display
```

---

## Part 7: Final Verification

### 7.1 Complete System Check

```bash
# Run this comprehensive check:

#!/bin/bash
echo "========================================"
echo "   BANKSHOT SYSTEM STATUS CHECK"
echo "========================================"
echo ""

echo "1. NETWORK"
echo "   Hostname: $(hostname)"
echo "   IP Address: $(hostname -I | awk '{print $1}')"
echo "   mDNS: $(avahi-resolve -n bankshot-display.local 2>/dev/null || echo 'not working')"
echo ""

echo "2. SERVICES"
systemctl is-active apache2 >/dev/null && echo "   ✓ Apache running" || echo "   ✗ Apache not running"
systemctl is-active web-monitor.service >/dev/null && echo "   ✓ Web monitor running" || echo "   ⚠ Web monitor not running"
systemctl is-active avahi-daemon >/dev/null && echo "   ✓ Avahi running" || echo "   ⚠ Avahi not running"
echo ""

echo "3. FILES"
[ -f /var/www/html/index.php ] && echo "   ✓ index.php exists" || echo "   ✗ index.php missing"
[ -f /var/www/html/tournament_data.json ] && echo "   ✓ tournament_data.json exists" || echo "   ✗ tournament_data.json missing"
[ -f /home/pi/pull_tournament_data.sh ] && echo "   ✓ pull script exists" || echo "   ✗ pull script missing"
echo ""

echo "4. CRON"
crontab -l | grep -q pull_tournament_data && echo "   ✓ Cron job configured" || echo "   ✗ No cron job"
echo ""

echo "5. TOURNAMENT DATA"
if [ -f /var/www/html/tournament_data.json ]; then
    echo "   Name: $(jq -r '.tournament_name' /var/www/html/tournament_data.json)"
    echo "   Status: $(jq -r '.status' /var/www/html/tournament_data.json)"
    echo "   Display: $(jq -r '.display_tournament' /var/www/html/tournament_data.json)"
    echo "   Updated: $(jq -r '.last_updated' /var/www/html/tournament_data.json)"
fi
echo ""

echo "6. MEDIA"
if [ -f /var/www/html/media/media_config.json ]; then
    MEDIA_COUNT=$(jq 'length' /var/www/html/media/media_config.json)
    ACTIVE_COUNT=$(jq '[.[] | select(.active==true)] | length' /var/www/html/media/media_config.json)
    echo "   Total items: $MEDIA_COUNT"
    echo "   Active items: $ACTIVE_COUNT"
else
    echo "   ⚠ No media configured yet"
fi
echo ""

echo "7. DISK SPACE"
df -h / | grep -v Filesystem
echo ""

echo "8. RECENT ACTIVITY"
echo "   Last git pull:"
tail -n 1 /home/pi/logs/github_pull.log 2>/dev/null || echo "   (no log yet)"
echo ""

echo "========================================"
echo "   END OF STATUS CHECK"
echo "========================================"
```

Save this as `check_system.sh` and run: `bash check_system.sh`

### 7.2 Test Tournament Detection

```bash
# Wait for next tournament at Bankshot Billiards, OR
# Manually test by editing tournament_data.json:

sudo nano /var/www/html/tournament_data.json

# Set:
#   "display_tournament": true
#   "player_count": 16

# Save, wait 30 seconds, check TV
# Should switch to tournament display with sidebar
```

### 7.3 Monitor for Issues

```bash
# Watch web monitor logs (Ctrl+C to stop)
sudo journalctl -u web-monitor.service -f

# Watch GitHub pull logs (Ctrl+C to stop)
tail -f /home/pi/logs/github_pull.log

# Watch Apache logs (Ctrl+C to stop)
sudo tail -f /var/log/apache2/error.log
```

---

## Part 8: Cleanup and Documentation

### 8.1 Clean Up Backup (Optional)

```bash
# After verifying everything works for a few days:

# Keep backup for 30 days, then remove:
# rm -rf ~/backup_YYYYMMDD/

# Or compress it:
cd ~
tar czf backup_YYYYMMDD_compressed.tar.gz backup_YYYYMMDD/
rm -rf backup_YYYYMMDD/

echo "✓ Backup compressed"
```

### 8.2 Document Your System

Create a file: `~/SYSTEM_INFO.txt`

```bash
cat > ~/SYSTEM_INFO.txt << EOF
BANKSHOT TOURNAMENT DISPLAY SYSTEM
==================================

Installation Date: $(date)
System: Raspberry Pi 4 Model B
OS: $(cat /etc/os-release | grep PRETTY_NAME | cut -d'"' -f2)

Network:
  Hostname: $(hostname)
  IP Address: $(hostname -I | awk '{print $1}')
  mDNS: bankshot-display.local

GitHub:
  Repository: https://github.com/YOUR_USERNAME/bankshot-tournament-display
  Actions: Run every 15 minutes

Important URLs:
  Main Display: http://bankshot-display.local/
  TV Page: http://bankshot-display.local/tv.html
  Media Manager: http://bankshot-display.local/media_manager.html
  QR Setup: http://bankshot-display.local/qr_setup.php

Maintenance:
  Update system: sudo apt update && sudo apt upgrade
  Check services: sudo systemctl status web-monitor.service
  View logs: sudo journalctl -u web-monitor.service -f
  Pull data: bash /home/pi/pull_tournament_data.sh

Support:
  Documentation: ~/bankshot-tournament-display/README.md
  Setup Guide: ~/bankshot-tournament-display/docs/SETUP.md
  Quick Reference: ~/bankshot-tournament-display/QUICKREF.md

EOF

echo "✓ System info documented in ~/SYSTEM_INFO.txt"
```

---

## Troubleshooting Common Issues

### Issue: TVs can't connect to .local address

**Solution**: Use IP address instead
```bash
# Find IP:
hostname -I

# Give TVs this URL:
# http://192.168.1.XXX/tv.html
```

### Issue: GitHub Actions failing

**Solution**: Check workflow file syntax
```bash
# View on GitHub:
# https://github.com/YOUR_USERNAME/bankshot-tournament-display/actions

# Look for errors in logs
# Common issue: scraper file path wrong
# Should be: scraper/bankshot_monitor_multi.py
```

### Issue: Pi not pulling from GitHub

**Solution**: Check git and cron
```bash
# Test manual pull
bash /home/pi/pull_tournament_data.sh

# Check logs
cat /home/pi/logs/github_pull.log

# Verify cron
crontab -l | grep pull_tournament_data

# Test git access
cd ~/bankshot-tournament-display
git pull
```

### Issue: Media not displaying

**Solution**: Check permissions and config
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/html/media/
sudo chmod -R 755 /var/www/html/media/

# Check config exists
cat /var/www/html/media/media_config.json | python3 -m json.tool

# View Apache errors
sudo tail -f /var/log/apache2/error.log
```

### Issue: Display not switching

**Solution**: Check tournament data and polling
```bash
# View current data
cat /var/www/html/tournament_data.json | python3 -m json.tool

# Check display_tournament flag
# Should be true to show tournament, false for ads

# Wait 30 seconds (polling interval)
# TV checks server every 30 seconds

# Check TV browser console for errors
# Add ?debug=1 to URL to see status
```

---

## Success Checklist

- [ ] Old system backed up
- [ ] Old services stopped and removed
- [ ] New repository cloned
- [ ] Installer completed successfully
- [ ] Web server accessible at http://bankshot-display.local/
- [ ] GitHub Actions enabled and running
- [ ] Pi pulling data from GitHub (check logs)
- [ ] Media uploaded and configured
- [ ] All TVs reconfigured with tv.html
- [ ] TVs auto-switch between ads and tournament display
- [ ] System documented

---

## What's Different in New System

| Old System | New System |
|------------|------------|
| Chromecast casting | Web browser on TV |
| CATT Python package | No special packages |
| Multiple scraper versions | One unified scraper |
| Manual switching | Auto-switching every 30 sec |
| Complex cron jobs | Simple git pull |
| Manual media editing | Web UI media manager |
| Various setup scripts | One installer |
| Poor error recovery | Auto-reconnect |

---

## Getting Help

If you run into issues:

1. **Check logs**:
   ```bash
   sudo journalctl -u web-monitor.service -f
   tail -f /home/pi/logs/github_pull.log
   ```

2. **Run system check**:
   ```bash
   bash check_system.sh
   ```

3. **Review documentation**:
   - `~/bankshot-tournament-display/README.md`
   - `~/bankshot-tournament-display/docs/SETUP.md`
   - `~/bankshot-tournament-display/QUICKREF.md`

4. **Check GitHub Issues**:
   - https://github.com/YOUR_USERNAME/bankshot-tournament-display/issues

---

## You're Done! 🎉

Your new consolidated system is now running:

- ✅ GitHub Actions scraping every 15 minutes
- ✅ Pi pulling data every 5 minutes  
- ✅ TVs auto-switching every 30 seconds
- ✅ Media rotating automatically
- ✅ Everything monitored and logged

**Next steps:**
1. Monitor for the next tournament to verify detection works
2. Add more media content via Media Manager
3. Share the system info file with any staff who need it
4. Set a reminder to update the system monthly

Enjoy your fully automated tournament display system!
