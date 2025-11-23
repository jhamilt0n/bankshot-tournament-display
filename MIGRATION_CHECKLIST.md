# Migration Checklist - Print This!

## 📋 Quick Migration Steps

### BEFORE YOU START
- [ ] Have SSH access to your Raspberry Pi
- [ ] Have your GitHub username ready
- [ ] Set aside 30-60 minutes
- [ ] Have your phone ready to scan QR codes

---

## PART 1: BACKUP (15 minutes)

```bash
# 1. Create backup directory
mkdir -p ~/backup_$(date +%Y%m%d)

# 2. Backup everything
sudo tar czf ~/backup_$(date +%Y%m%d)/web_backup.tar.gz /var/www/html/
cp /var/www/html/tournament_data.json ~/backup_$(date +%Y%m%d)/
cp -r /var/www/html/media ~/backup_$(date +%Y%m%d)/ 2>/dev/null || true
crontab -l > ~/backup_$(date +%Y%m%d)/crontab_backup.txt 2>/dev/null || true

# 3. Verify backup exists
ls -lh ~/backup_$(date +%Y%m%d)/
```

**✓ Backup complete when you see files listed above**

---

## PART 2: CLEANUP (10 minutes)

```bash
# 1. Stop old services
sudo systemctl stop catt-monitor.service 2>/dev/null || true
sudo systemctl disable catt-monitor.service 2>/dev/null || true
pkill -f tournament_monitor.py 2>/dev/null || true
pkill -f catt_monitor.py 2>/dev/null || true

# 2. Clean cron
crontab -l 2>/dev/null | grep -v "tournament_monitor" | grep -v "catt" | crontab -

# 3. Remove old scripts
rm -f ~/tournament_monitor.py ~/catt_monitor.py ~/smart_switcher_status.py

# 4. Remove old services
sudo rm -f /etc/systemd/system/catt-monitor.service
sudo systemctl daemon-reload

# 5. Verify cleanup
ps aux | grep -E "catt|tournament_monitor" | grep -v grep
# Should show nothing
```

**✓ Cleanup complete when no processes show above**

---

## PART 3: INSTALL NEW SYSTEM (15 minutes)

```bash
# 1. Clone repository
cd /home/pi
git clone https://github.com/YOUR_USERNAME/bankshot-tournament-display.git
cd bankshot-tournament-display

# 2. Run installer
sudo bash scripts/install.sh

# Answer prompts:
#   - Confirm installation: y
#   - Install HDMI display service: y (if you have HDMI TV) or n

# Wait for completion...
```

**✓ Install complete when you see "Installation Complete!" message**

---

## PART 4: VERIFY INSTALLATION (5 minutes)

```bash
# Quick verification
curl -s http://localhost/ > /dev/null && echo "✓ Web server OK" || echo "✗ Failed"
sudo systemctl is-active apache2 && echo "✓ Apache OK"
sudo systemctl is-active web-monitor.service && echo "✓ Monitor OK"
ping -c 2 bankshot-display.local > /dev/null && echo "✓ mDNS OK" || echo "⚠ Use IP"
crontab -l | grep pull_tournament_data && echo "✓ Cron OK"
```

**✓ Verification complete when all show ✓ or OK**

---

## PART 5: GITHUB SETUP (10 minutes)

### On GitHub.com:

1. **Create Repository**
   - [ ] Go to https://github.com
   - [ ] Click "New repository"
   - [ ] Name: `bankshot-tournament-display`
   - [ ] Choose Public or Private
   - [ ] Don't initialize with README
   - [ ] Click "Create repository"

2. **Push Code**
   ```bash
   cd ~/bankshot-tournament-display
   git remote remove origin 2>/dev/null || true
   git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git
   git branch -M main
   git push -u origin main
   ```
   - [ ] Enter GitHub username
   - [ ] Enter GitHub password (or Personal Access Token)

3. **Enable Actions**
   - [ ] Go to repo on GitHub
   - [ ] Click "Actions" tab
   - [ ] Click "I understand my workflows, go ahead and enable them"

4. **Test Actions**
   - [ ] Click "Scrape Tournaments" workflow
   - [ ] Click "Run workflow" → "Run workflow"
   - [ ] Wait for green checkmark ✓
   - [ ] Go to "Code" tab
   - [ ] Verify `tournament_data.json` exists

**✓ GitHub setup complete when Actions show green checkmark**

---

## PART 6: CONFIGURE TVs (5 minutes per TV)

### Get URL First:
```bash
# On phone/computer, visit:
http://bankshot-display.local/qr_setup.php

# Or if .local doesn't work:
hostname -I  # Use this IP
# Then: http://YOUR_IP/qr_setup.php
```

### For EACH TV:

1. **Open TV's Web Browser**
   - [ ] Samsung: Home → Internet Browser
   - [ ] LG: Home → Web Browser
   - [ ] Fire TV: Silk Browser
   - [ ] Roku: Web Browser channel
   - [ ] Other: Find browser app

2. **Navigate to TV Page**
   - [ ] Scan QR code (easiest!)
   - [ ] OR manually enter: `http://bankshot-display.local/tv.html`
   - [ ] OR use IP: `http://YOUR_IP/tv.html`

3. **Bookmark the Page**
   - [ ] Save bookmark
   - [ ] (Optional) Set as homepage

4. **Test**
   - [ ] Should show rotating ads
   - [ ] Wait 30 seconds
   - [ ] Should still be working

**✓ TV configured when displaying ads correctly**

---

## PART 7: UPLOAD MEDIA (10 minutes)

### On any computer/phone browser:

1. **Access Media Manager**
   ```
   http://bankshot-display.local/media_manager.html
   ```

2. **Upload Files**
   - [ ] Click "Choose Files" or drag & drop
   - [ ] Upload images (.jpg, .png) or videos (.mp4)

3. **Add URLs** (if needed)
   - [ ] Enter website URL: `https://example.com`
   - [ ] Or local path: `/calcutta.html`
   - [ ] Click "Add URL"

4. **Configure Each Item**
   - [ ] Set duration (10-30 seconds typical)
   - [ ] Check "Ads" and/or "Tournaments"
   - [ ] Set schedule (days/times)
   - [ ] Click "Enable"

5. **Reorder** (drag cards to reorder)

6. **Test**
   ```
   http://bankshot-display.local/ads_display.html
   ```
   - [ ] Verify all items display
   - [ ] Check timing is correct

**✓ Media setup complete when ads_display.html shows your content**

---

## FINAL CHECKS

### System Health Check:
```bash
# On Pi, run:
echo "Web Server:" && curl -s http://localhost/ > /dev/null && echo "✓ OK"
echo "Services:" && sudo systemctl is-active apache2 web-monitor.service
echo "Tournament Data:" && cat /var/www/html/tournament_data.json | python3 -m json.tool | head -n 3
echo "Media Items:" && [ -f /var/www/html/media/media_config.json ] && echo "$(cat /var/www/html/media/media_config.json | python3 -c "import sys,json; print(len(json.load(sys.stdin)))" 2>/dev/null || echo 0) items"
echo "Cron:" && crontab -l | grep pull_tournament_data > /dev/null && echo "✓ Running"
```

**All should show ✓ or OK**

### Wait for Next Tournament:
- [ ] Monitor GitHub Actions (should run every 15 min)
- [ ] When tournament detected, verify TVs auto-switch
- [ ] Check sidebar shows player count and payouts

---

## ✅ MIGRATION COMPLETE!

You now have:
- ✓ GitHub Actions scraping every 15 minutes
- ✓ Pi pulling data every 5 minutes
- ✓ TVs auto-switching every 30 seconds
- ✓ Web-based media management
- ✓ No Chromecast needed
- ✓ Automatic error recovery

### Important URLs to Bookmark:

| URL | Purpose |
|-----|---------|
| `http://bankshot-display.local/media_manager.html` | Manage content |
| `http://bankshot-display.local/qr_setup.php` | Get TV QR codes |
| `http://bankshot-display.local/tournament_data.json` | Current status |

### Useful Commands:

```bash
# Check status
sudo systemctl status web-monitor.service

# View logs
sudo journalctl -u web-monitor.service -f
tail -f /home/pi/logs/github_pull.log

# Pull data manually
bash /home/pi/pull_tournament_data.sh

# View current tournament
cat /var/www/html/tournament_data.json | python3 -m json.tool
```

### Need Help?

1. Check `~/bankshot-tournament-display/MIGRATION_GUIDE.md` for detailed troubleshooting
2. Check `~/bankshot-tournament-display/QUICKREF.md` for quick reference
3. Check GitHub Actions logs for scraper issues
4. Check `sudo journalctl -u web-monitor.service` for Pi issues

---

**Keep this checklist for future reference!**

Last Updated: $(date)
System: Raspberry Pi 4 at $(hostname -I | awk '{print $1}')
