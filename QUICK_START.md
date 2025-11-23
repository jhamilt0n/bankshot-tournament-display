# ⚡ Quick Start - 5 Minute Summary

## What You're About to Do

Migrate your Bankshot system while keeping your working display files. Total time: ~50 minutes.

---

## 🎯 Two-Phase Process

### **Phase 1: Your Computer** (5 minutes)
Push core system to GitHub

### **Phase 2: Your Pi** (45 minutes)
Run automated script to migrate

---

## 📋 Phase 1: On Your Computer

**Open terminal and run these commands:**

```bash
cd /tmp/bankshot-complete

# Create .gitignore
cat > .gitignore << 'EOF'
*.log
logs/
__pycache__/
web/media/*.mp4
web/media/*.jpg
web/media/*.png
!web/media/.gitkeep
EOF

# Create placeholder
mkdir -p web/media
touch web/media/.gitkeep

# Git setup
git init
git add .
git commit -m "Initial commit - core system"

# IMPORTANT: Replace YOUR_USERNAME with your GitHub username!
git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git

git branch -M main
git push -u origin main
```

**✅ Done! Core system is now on GitHub**

---

## 📋 Phase 2: On Your Pi

### **Step 1: Get the Script**

SSH into your Pi:
```bash
ssh pi@bankshot-display.local
```

Create the migration script:
```bash
cat > ~/migrate.sh << 'SCRIPTEOF'
#!/bin/bash
echo "=========================================="
echo "BANKSHOT SMART MIGRATION"
echo "=========================================="

# Backup display files
echo "Step 1: Backing up files..."
mkdir -p ~/web_files_backup
sudo cp /var/www/html/index.php ~/web_files_backup/ 2>/dev/null && echo "✓ index.php"
sudo cp /var/www/html/ads_display.html ~/web_files_backup/ 2>/dev/null && echo "✓ ads_display.html"
sudo cp /var/www/html/media_manager.html ~/web_files_backup/ 2>/dev/null && echo "✓ media_manager.html"
sudo cp /var/www/html/tv_setup.html ~/web_files_backup/ 2>/dev/null && echo "✓ tv_setup.html"
sudo cp /var/www/html/calcutta.html ~/web_files_backup/ 2>/dev/null || echo "○ No calcutta.html"
sudo cp -r /var/www/html/media ~/web_files_backup/ 2>/dev/null && echo "✓ Media"
sudo cp /var/www/html/tournament_data.json ~/web_files_backup/ 2>/dev/null && echo "✓ Data"
sudo chown -R pi:pi ~/web_files_backup
echo ""

# Clean old system
echo "Step 2: Cleaning old system..."
sudo systemctl stop catt-monitor.service 2>/dev/null || true
sudo systemctl disable catt-monitor.service 2>/dev/null || true
sudo systemctl stop tournament-monitor.service 2>/dev/null || true
sudo systemctl disable tournament-monitor.service 2>/dev/null || true
pkill -f tournament_monitor.py 2>/dev/null || true
pkill -f catt_monitor.py 2>/dev/null || true
crontab -l 2>/dev/null | grep -v "tournament_monitor" | grep -v "catt" | crontab - 2>/dev/null || true
rm -f ~/tournament_monitor.py ~/catt_monitor.py ~/smart_switcher_status.py 2>/dev/null || true
sudo rm -f /etc/systemd/system/catt-monitor.service 2>/dev/null || true
sudo rm -f /etc/systemd/system/tournament-monitor.service 2>/dev/null || true
sudo systemctl daemon-reload
[ -d ~/tournament-scraper ] && mv ~/tournament-scraper ~/backup_old_repo_$(date +%Y%m%d) 2>/dev/null || true
echo "✓ Old system cleaned"
echo ""

# Clone new repo
echo "Step 3: Cloning new repository..."
echo "Enter your GitHub username:"
read GITHUB_USER
cd /home/pi
git clone https://github.com/$GITHUB_USER/bankshot-tournament-display.git
if [ $? -eq 0 ]; then
    echo "✓ Repository cloned"
else
    echo "✗ Failed to clone"
    exit 1
fi
echo ""

# Run installer
echo "Step 4: Running installer..."
echo "Press Enter to start installation..."
read
cd ~/bankshot-tournament-display
sudo bash scripts/install.sh
echo ""

# Copy files to repo
echo "Step 5: Integrating your files..."
cp ~/web_files_backup/*.php ~/bankshot-tournament-display/web/ 2>/dev/null
cp ~/web_files_backup/*.html ~/bankshot-tournament-display/web/ 2>/dev/null
echo "✓ Files integrated"
echo ""

# Push to GitHub
echo "Step 6: Pushing to GitHub..."
cd ~/bankshot-tournament-display
git config user.name "Bankshot Pi"
git config user.email "pi@bankshot.local"
git add web/*.php web/*.html
git commit -m "Add display files from Pi"
git push origin main
echo ""

# Verify
echo "Step 7: Verification..."
systemctl is-active apache2 && echo "✓ Apache running"
systemctl is-active web-monitor.service && echo "✓ Monitor running"
[ -f /var/www/html/index.php ] && echo "✓ index.php exists"
[ -f /var/www/html/tv.html ] && echo "✓ tv.html exists"
crontab -l | grep -q pull_tournament_data && echo "✓ Cron configured"
echo ""
echo "=========================================="
echo "MIGRATION COMPLETE!"
echo "=========================================="
SCRIPTEOF

chmod +x ~/migrate.sh
```

### **Step 2: Run the Script**

```bash
bash ~/migrate.sh
```

### **Step 3: Answer the Prompts**

The script will ask you:

1. **"Enter your GitHub username:"**
   - Type it and press Enter

2. **"Press Enter to start installation..."**
   - Just press Enter

3. **"Confirm installation? (y/n)"**
   - Type `y` and press Enter

4. **"Install HDMI display service? (y/n)"**
   - Type `y` (if you have HDMI TV) or `n` (if not)
   - Press Enter

Then wait... the script does everything else!

---

## ✅ What to Expect

### **You'll see:**

```
==========================================
BANKSHOT SMART MIGRATION
==========================================

Step 1: Backing up files...
✓ index.php
✓ ads_display.html
✓ media_manager.html
✓ tv_setup.html
✓ Media
✓ Data

Step 2: Cleaning old system...
✓ Old system cleaned

Step 3: Cloning new repository...
Enter your GitHub username: [YOU TYPE HERE]
✓ Repository cloned

Step 4: Running installer...
Press Enter to start installation... [PRESS ENTER]
[Installer runs for 15 minutes...]
✓ Installation complete

Step 5: Integrating your files...
✓ Files integrated

Step 6: Pushing to GitHub...
✓ Pushed to GitHub

Step 7: Verification...
✓ Apache running
✓ Monitor running
✓ index.php exists
✓ tv.html exists
✓ Cron configured

==========================================
MIGRATION COMPLETE!
==========================================
```

---

## 🎯 After Migration

### **Test immediately:**

```bash
# Check services
sudo systemctl status apache2
sudo systemctl status web-monitor.service

# Test pull script
bash /home/pi/pull_tournament_data.sh

# View logs
tail -20 /home/pi/logs/github_pull.log
```

### **Test your TVs:**

Open TV browser and go to:
```
http://bankshot-display.local/tv.html
```

Should show your ads and auto-switch when tournaments detected.

---

## 🆘 If Something Goes Wrong

### **Restore your files:**

```bash
sudo cp ~/web_files_backup/* /var/www/html/
sudo systemctl restart apache2
```

Everything back to normal!

---

## 📊 Timeline

| What | Time |
|------|------|
| Computer: Push to GitHub | 5 min |
| Pi: Create script | 2 min |
| Pi: Run script (automated) | 20 min |
| Pi: Verify & test | 5 min |
| **TOTAL** | **~30 min** |

---

## 🎊 Success!

When done, you'll have:

- ✅ Working display files (same as before)
- ✅ GitHub Actions scraper (new!)
- ✅ Web-based TV system (new!)
- ✅ Everything in GitHub (new!)
- ✅ Auto-updates every 5 min (new!)
- ✅ Complete documentation (new!)

---

## 💬 Ready?

**Tell me:**
- "Let's do it" - We'll start Phase 1
- "I have questions" - Ask away
- "Walk me through it" - I'll guide each step

**Let's migrate your system!** 🚀
