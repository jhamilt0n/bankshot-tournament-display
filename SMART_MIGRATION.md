# Smart Migration: Preserve Your Working Display Files

## Strategy

You already have 5 working display files on your Pi. Instead of recreating them, we'll:
1. ✅ Push the backend/core system to GitHub (what we have)
2. ✅ Clean up old scraper/monitoring stuff on Pi
3. ✅ Pull new system to Pi
4. ✅ Copy your existing 5 display files into the new structure
5. ✅ Push those files back to GitHub

This way you keep what works and upgrade everything else!

---

## Step-by-Step Migration

### STEP 1: Backup Your Pi's Display Files (5 minutes)

SSH into your Pi and backup the working display files:

```bash
# Create backup directory
mkdir -p ~/web_files_backup

# Copy your working display files
sudo cp /var/www/html/index.php ~/web_files_backup/
sudo cp /var/www/html/ads_display.html ~/web_files_backup/
sudo cp /var/www/html/media_manager.html ~/web_files_backup/
sudo cp /var/www/html/tv_setup.html ~/web_files_backup/
sudo cp /var/www/html/calcutta.html ~/web_files_backup/ 2>/dev/null || echo "No calcutta.html"

# Verify backup
ls -lh ~/web_files_backup/
echo "✓ Display files backed up"
```

### STEP 2: Push Current System to GitHub (5 minutes)

On your computer (where you have `/tmp/bankshot-complete/`):

```bash
cd /tmp/bankshot-complete

# Create .gitignore
cat > .gitignore << 'GITEOF'
*.log
logs/
__pycache__/
*.pyc
.DS_Store
Thumbs.db
web/media/*.mp4
web/media/*.webm
web/media/*.mov
web/media/*.jpg
web/media/*.jpeg
web/media/*.png
web/media/*.gif
!web/media/.gitkeep
!web/media/media_config.json
backup_*/
*.tmp
*.bak
GITEOF

# Create placeholder in web/media
mkdir -p web/media
touch web/media/.gitkeep

# Initialize git
git init
git add .
git commit -m "Initial commit - core system + backend APIs"

# Add your GitHub remote (replace YOUR_USERNAME)
git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git

# Push to GitHub
git branch -M main
git push -u origin main
```

**✓ Now GitHub has the core system (without the 5 display files)**

---

### STEP 3: Clean Up Old System on Pi (10 minutes)

SSH to your Pi:

```bash
# Stop old services
echo "Stopping old services..."
sudo systemctl stop catt-monitor.service 2>/dev/null || true
sudo systemctl disable catt-monitor.service 2>/dev/null || true
sudo systemctl stop tournament-monitor.service 2>/dev/null || true
sudo systemctl disable tournament-monitor.service 2>/dev/null || true

# Kill old processes
pkill -f tournament_monitor.py 2>/dev/null || true
pkill -f catt_monitor.py 2>/dev/null || true
pkill -f smart_switcher 2>/dev/null || true

# Remove old cron jobs
crontab -l 2>/dev/null | grep -v "tournament_monitor" | grep -v "catt" | grep -v "smart_switcher" | crontab - 2>/dev/null || true

# Remove old Python scripts (NOT in /var/www/html)
rm -f ~/tournament_monitor.py 2>/dev/null || true
rm -f ~/bankshot_monitor_status.py 2>/dev/null || true
rm -f ~/catt_monitor.py 2>/dev/null || true
rm -f ~/smart_switcher_status.py 2>/dev/null || true
rm -f ~/boot_tournament_check.sh 2>/dev/null || true

# Remove old service files
sudo rm -f /etc/systemd/system/catt-monitor.service 2>/dev/null || true
sudo rm -f /etc/systemd/system/tournament-monitor.service 2>/dev/null || true
sudo systemctl daemon-reload

# Remove CATT (Chromecast tool - not needed anymore)
pip3 uninstall -y catt 2>/dev/null || true

# Remove old repository if it exists
if [ -d ~/tournament-scraper ]; then
    mv ~/tournament-scraper ~/backup_old_repo_$(date +%Y%m%d)
    echo "✓ Moved old repo to backup"
fi

echo ""
echo "✓ Old system cleaned up"
echo ""
```

**Important: We did NOT touch `/var/www/html/` - your display files are safe!**

---

### STEP 4: Clone New System to Pi (5 minutes)

```bash
# Clone the new repository
cd /home/pi
git clone https://github.com/YOUR_USERNAME/bankshot-tournament-display.git

# Verify it downloaded
cd ~/bankshot-tournament-display
ls -la

echo "✓ New system cloned"
```

---

### STEP 5: Run Installer (15 minutes)

```bash
cd ~/bankshot-tournament-display

# Run the installer
sudo bash scripts/install.sh

# Answer prompts:
#   - Confirm installation: y
#   - Install HDMI display service: y (if you use HDMI) or n

# The installer will:
# ✓ Install dependencies
# ✓ Set up Apache (already configured)
# ✓ Copy web files (will NOT overwrite your existing ones if they exist)
# ✓ Set up systemd service
# ✓ Configure cron jobs
# ✓ Install PHP QR library
```

**Note: The installer is smart and won't overwrite existing files in `/var/www/html/`**

---

### STEP 6: Integrate Your Display Files (5 minutes)

Now copy your backed-up display files into the new repo structure:

```bash
cd ~/bankshot-tournament-display

# Copy your display files into the repo
cp ~/web_files_backup/index.php web/
cp ~/web_files_backup/ads_display.html web/
cp ~/web_files_backup/media_manager.html web/
cp ~/web_files_backup/tv_setup.html web/
cp ~/web_files_backup/calcutta.html web/ 2>/dev/null || true

# Verify they're there
ls -lh web/*.php web/*.html

echo "✓ Display files integrated into new repo"
```

---

### STEP 7: Push Display Files to GitHub (2 minutes)

```bash
cd ~/bankshot-tournament-display

# Configure git (if not already done)
git config user.name "Your Name"
git config user.email "your.email@example.com"

# Add the display files
git add web/*.php web/*.html

# Commit
git commit -m "Add working display files from Pi"

# Push to GitHub
git push origin main

echo "✓ Display files now in GitHub!"
```

---

### STEP 8: Verify Everything Works (5 minutes)

```bash
# Check services
sudo systemctl status apache2
sudo systemctl status web-monitor.service

# Check tournament data file
cat /var/www/html/tournament_data.json | python3 -m json.tool

# Check cron
crontab -l | grep pull_tournament_data

# Test pull script
bash /home/pi/pull_tournament_data.sh

# Check logs
tail -20 /home/pi/logs/github_pull.log

echo ""
echo "System Status:"
echo "✓ Web server: $(systemctl is-active apache2)"
echo "✓ Monitor: $(systemctl is-active web-monitor.service)"
echo "✓ Display files: $(ls /var/www/html/*.php /var/www/html/*.html 2>/dev/null | wc -l) files"
```

---

## Verification Checklist

After migration, verify:

- [ ] Old services stopped (catt-monitor, old tournament-monitor)
- [ ] Old cron jobs removed
- [ ] New repository cloned to `/home/pi/bankshot-tournament-display`
- [ ] New systemd service running (`web-monitor.service`)
- [ ] New cron job pulling from GitHub every 5 minutes
- [ ] Your 5 display files still in `/var/www/html/`
- [ ] Your 5 display files also in repo (`~/bankshot-tournament-display/web/`)
- [ ] Your 5 display files pushed to GitHub
- [ ] TVs still displaying correctly
- [ ] Media files still in `/var/www/html/media/`

---

## What This Achieves

### Before:
- ❌ Old scraper running locally
- ❌ CATT/Chromecast system
- ❌ Multiple monitor scripts
- ❌ Scattered files
- ✅ Working display files

### After:
- ✅ GitHub Actions scraper (cloud-based)
- ✅ Web-based TV system (no dongles)
- ✅ One unified monitor service
- ✅ Everything in one repo
- ✅ Same working display files (preserved!)
- ✅ Display files now version controlled in GitHub

---

## File Locations After Migration

```
GitHub Repository:
  - All core files ✅
  - Your 5 display files ✅
  - Documentation ✅

Raspberry Pi:
  /home/pi/bankshot-tournament-display/
    - Git repo with all files
    
  /var/www/html/
    - Your working display files (same as before)
    - Backend PHP files (from new system)
    - media/ directory (unchanged)
    - tournament_data.json (pulled from GitHub)
```

---

## Rollback Plan (If Something Goes Wrong)

If you need to rollback:

```bash
# Restore your old files
sudo cp ~/web_files_backup/* /var/www/html/

# Restart Apache
sudo systemctl restart apache2

# Your display files are safe!
```

---

## After Migration: Testing

1. **Test TV Display:**
   - Visit: `http://bankshot-display.local/tv.html`
   - Should auto-switch between ads and tournaments

2. **Test Media Manager:**
   - Visit: `http://bankshot-display.local/media_manager.html`
   - Should load your existing media

3. **Test Tournament Display:**
   - Edit `/var/www/html/tournament_data.json`
   - Set `"display_tournament": true`
   - Wait 30 seconds
   - TV should switch to tournament display

4. **Test GitHub Sync:**
   - Check GitHub Actions: https://github.com/YOUR_USERNAME/bankshot-tournament-display/actions
   - Should see scraper running every 15 minutes
   - Should see `tournament_data.json` updating

---

## Key Benefits

✅ **Keep what works** - Your tested display files preserved
✅ **Upgrade infrastructure** - New scraper, monitoring, automation
✅ **Version control** - All files now in GitHub
✅ **No downtime** - TVs keep working throughout migration
✅ **Clean system** - Old cruft removed
✅ **One repository** - Everything organized

---

## Timeline

| Step | Time | What Happens |
|------|------|--------------|
| 1. Backup display files | 5 min | Copy 5 files to safety |
| 2. Push to GitHub | 5 min | Upload core system |
| 3. Clean old system | 10 min | Remove old scripts/services |
| 4. Clone new repo | 5 min | Download from GitHub |
| 5. Run installer | 15 min | Set up new system |
| 6. Integrate files | 5 min | Move your files to repo |
| 7. Push to GitHub | 2 min | Upload display files |
| 8. Verify | 5 min | Check everything works |
| **Total** | **~50 min** | **System migrated!** |

---

## Next Steps After Migration

1. **Test thoroughly** - Make sure TVs switch correctly
2. **Monitor logs** - Watch for any errors
3. **Update media** - Add new content via Media Manager
4. **Document changes** - Note any custom modifications
5. **Set reminder** - Update system monthly

---

## Questions?

Common issues:

**Q: What if installer overwrites my files?**
A: It won't - installer checks if files exist before copying

**Q: Will my media files be lost?**
A: No - they're in `/var/www/html/media/` and untouched

**Q: Can I still edit files directly on Pi?**
A: Yes, but better to push to GitHub so it's version controlled

**Q: What if GitHub scraper finds no tournaments?**
A: Pi keeps displaying last known data until next tournament

---

## You're Ready!

This approach gives you:
- ✅ Best of both worlds
- ✅ Keep your working display files
- ✅ Get new infrastructure
- ✅ Everything in version control
- ✅ No risky file recreation

**Follow the steps above and you'll have a clean migration in ~50 minutes!**

Any questions? Let me know which step you want to start with! 🚀
