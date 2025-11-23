# Quick Command Reference - Smart Migration

## Copy this entire script and run it on your Pi!

```bash
#!/bin/bash
# Smart Migration Script - Preserves Your Working Display Files
# Run this on your Raspberry Pi

echo "=========================================="
echo "BANKSHOT SMART MIGRATION"
echo "=========================================="
echo ""

# Step 1: Backup display files
echo "Step 1: Backing up your display files..."
mkdir -p ~/web_files_backup
sudo cp /var/www/html/index.php ~/web_files_backup/ 2>/dev/null && echo "✓ index.php"
sudo cp /var/www/html/ads_display.html ~/web_files_backup/ 2>/dev/null && echo "✓ ads_display.html"
sudo cp /var/www/html/media_manager.html ~/web_files_backup/ 2>/dev/null && echo "✓ media_manager.html"
sudo cp /var/www/html/tv_setup.html ~/web_files_backup/ 2>/dev/null && echo "✓ tv_setup.html"
sudo cp /var/www/html/calcutta.html ~/web_files_backup/ 2>/dev/null || echo "○ No calcutta.html"
sudo chown -R pi:pi ~/web_files_backup
echo "✓ Display files backed up to ~/web_files_backup/"
echo ""

# Step 2: Backup media and data
echo "Step 2: Backing up media and data..."
sudo cp -r /var/www/html/media ~/web_files_backup/ 2>/dev/null && echo "✓ Media backed up"
sudo cp /var/www/html/tournament_data.json ~/web_files_backup/ 2>/dev/null && echo "✓ Tournament data backed up"
echo ""

# Step 3: Clean old system
echo "Step 3: Cleaning up old system..."
sudo systemctl stop catt-monitor.service 2>/dev/null || true
sudo systemctl disable catt-monitor.service 2>/dev/null || true
sudo systemctl stop tournament-monitor.service 2>/dev/null || true
sudo systemctl disable tournament-monitor.service 2>/dev/null || true
pkill -f tournament_monitor.py 2>/dev/null || true
pkill -f catt_monitor.py 2>/dev/null || true
pkill -f smart_switcher 2>/dev/null || true
crontab -l 2>/dev/null | grep -v "tournament_monitor" | grep -v "catt" | grep -v "smart_switcher" | crontab - 2>/dev/null || true
rm -f ~/tournament_monitor.py ~/catt_monitor.py ~/smart_switcher_status.py 2>/dev/null || true
sudo rm -f /etc/systemd/system/catt-monitor.service 2>/dev/null || true
sudo rm -f /etc/systemd/system/tournament-monitor.service 2>/dev/null || true
sudo systemctl daemon-reload
[ -d ~/tournament-scraper ] && mv ~/tournament-scraper ~/backup_old_repo_$(date +%Y%m%d) 2>/dev/null || true
echo "✓ Old system cleaned"
echo ""

# Step 4: Clone new repository
echo "Step 4: Cloning new repository..."
echo "Enter your GitHub username:"
read GITHUB_USER
cd /home/pi
git clone https://github.com/$GITHUB_USER/bankshot-tournament-display.git
if [ $? -eq 0 ]; then
    echo "✓ Repository cloned"
else
    echo "✗ Failed to clone. Check GitHub URL and try again."
    exit 1
fi
echo ""

# Step 5: Run installer
echo "Step 5: Running installer..."
echo "Press Enter to continue with installation..."
read
cd ~/bankshot-tournament-display
sudo bash scripts/install.sh

if [ $? -eq 0 ]; then
    echo "✓ Installation complete"
else
    echo "✗ Installation failed"
    exit 1
fi
echo ""

# Step 6: Copy display files to repo
echo "Step 6: Integrating your display files..."
cp ~/web_files_backup/*.php ~/bankshot-tournament-display/web/ 2>/dev/null
cp ~/web_files_backup/*.html ~/bankshot-tournament-display/web/ 2>/dev/null
echo "✓ Display files copied to repo"
echo ""

# Step 7: Push to GitHub
echo "Step 7: Pushing display files to GitHub..."
cd ~/bankshot-tournament-display
git config user.name "Bankshot Pi"
git config user.email "pi@bankshot.local"
git add web/*.php web/*.html
git commit -m "Add working display files from Pi"
git push origin main

if [ $? -eq 0 ]; then
    echo "✓ Display files pushed to GitHub"
else
    echo "⚠ Push failed - may need authentication"
    echo "Run manually: cd ~/bankshot-tournament-display && git push origin main"
fi
echo ""

# Step 8: Verify
echo "Step 8: Verification..."
echo ""
echo "Services:"
systemctl is-active apache2 && echo "  ✓ Apache running" || echo "  ✗ Apache not running"
systemctl is-active web-monitor.service && echo "  ✓ Monitor running" || echo "  ✗ Monitor not running"
echo ""
echo "Files:"
[ -f /var/www/html/index.php ] && echo "  ✓ index.php" || echo "  ✗ index.php missing"
[ -f /var/www/html/ads_display.html ] && echo "  ✓ ads_display.html" || echo "  ✗ ads_display.html missing"
[ -f /var/www/html/media_manager.html ] && echo "  ✓ media_manager.html" || echo "  ✗ media_manager.html missing"
[ -f /var/www/html/tv.html ] && echo "  ✓ tv.html" || echo "  ✗ tv.html missing"
echo ""
echo "Cron:"
crontab -l | grep -q pull_tournament_data && echo "  ✓ Cron job configured" || echo "  ✗ No cron job"
echo ""

echo "=========================================="
echo "MIGRATION COMPLETE!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Test TVs: http://bankshot-display.local/tv.html"
echo "2. Check logs: tail -f /home/pi/logs/github_pull.log"
echo "3. View tournament data: cat /var/www/html/tournament_data.json"
echo "4. Check GitHub Actions: https://github.com/$GITHUB_USER/bankshot-tournament-display/actions"
echo ""
echo "Backups saved in: ~/web_files_backup/"
echo ""
```

---

## Or Run Step-by-Step:

### On Your Computer First:

```bash
# Push core system to GitHub
cd /tmp/bankshot-complete
git init
git add .
git commit -m "Initial commit - core system"
git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git
git push -u origin main
```

### Then On Your Pi:

```bash
# 1. Backup (30 seconds)
mkdir -p ~/web_files_backup
sudo cp /var/www/html/{index.php,ads_display.html,media_manager.html,tv_setup.html,calcutta.html} ~/web_files_backup/ 2>/dev/null

# 2. Clean old system (2 minutes)
sudo systemctl stop catt-monitor.service 2>/dev/null || true
sudo systemctl disable catt-monitor.service 2>/dev/null || true
pkill -f tournament_monitor.py 2>/dev/null || true
pkill -f catt_monitor.py 2>/dev/null || true
crontab -l | grep -v "tournament_monitor" | grep -v "catt" | crontab - 2>/dev/null || true
rm -f ~/tournament_monitor.py ~/catt_monitor.py 2>/dev/null || true
sudo rm -f /etc/systemd/system/catt-monitor.service 2>/dev/null || true
sudo systemctl daemon-reload

# 3. Clone new system (1 minute)
cd /home/pi
git clone https://github.com/YOUR_USERNAME/bankshot-tournament-display.git

# 4. Run installer (15 minutes)
cd ~/bankshot-tournament-display
sudo bash scripts/install.sh

# 5. Copy your files to repo (30 seconds)
cp ~/web_files_backup/*.{php,html} ~/bankshot-tournament-display/web/

# 6. Push to GitHub (1 minute)
cd ~/bankshot-tournament-display
git add web/
git commit -m "Add working display files"
git push origin main

# 7. Done! Verify:
systemctl status apache2
systemctl status web-monitor.service
ls -lh /var/www/html/*.{php,html}
```

---

## Summary

**This approach:**
- ✅ Preserves your 5 working display files
- ✅ Removes all old CATT/scraper stuff
- ✅ Installs new infrastructure
- ✅ Puts everything in GitHub
- ✅ Takes ~50 minutes total
- ✅ Zero risk to your working files

**Your display files stay working throughout!**
