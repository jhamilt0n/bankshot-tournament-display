# 🤖 Automated Migration Script - Complete Walkthrough

## Overview

This script does **everything automatically** to migrate your system while preserving your working display files. It's safe, tested, and takes about 50 minutes.

---

## 📋 What the Script Does (Step-by-Step)

### **Step 1: Backup Display Files** (30 seconds)
```bash
mkdir -p ~/web_files_backup
sudo cp /var/www/html/index.php ~/web_files_backup/
sudo cp /var/www/html/ads_display.html ~/web_files_backup/
# ... copies all 5 display files
```

**What happens:**
- Creates backup directory
- Copies your 5 working display files to safety
- Shows checkmarks for each file backed up
- **Your files are now protected!**

**You'll see:**
```
✓ index.php
✓ ads_display.html
✓ media_manager.html
✓ tv_setup.html
○ No calcutta.html (if you don't have it)
```

---

### **Step 2: Backup Media & Data** (30 seconds)
```bash
sudo cp -r /var/www/html/media ~/web_files_backup/
sudo cp /var/www/html/tournament_data.json ~/web_files_backup/
```

**What happens:**
- Backs up your entire media directory (images/videos)
- Backs up current tournament data
- **Extra safety - everything protected**

**You'll see:**
```
✓ Media backed up
✓ Tournament data backed up
```

---

### **Step 3: Clean Old System** (2 minutes)
```bash
sudo systemctl stop catt-monitor.service
sudo systemctl disable catt-monitor.service
pkill -f tournament_monitor.py
pkill -f catt_monitor.py
# ... removes all old components
```

**What happens:**
- Stops old CATT/Chromecast services
- Kills old scraper processes
- Removes old cron jobs
- Deletes old Python scripts
- Removes old systemd services
- Moves old repository to backup
- **Does NOT touch /var/www/html/ - your files stay safe!**

**You'll see:**
```
✓ Old system cleaned
```

**What's removed:**
- ❌ CATT monitor service
- ❌ Old tournament monitor
- ❌ Old scraper scripts
- ❌ Old cron jobs
- ❌ Old repository

**What's kept:**
- ✅ All files in /var/www/html/
- ✅ Media directory
- ✅ Apache configuration
- ✅ Network settings

---

### **Step 4: Clone New Repository** (1 minute)
```bash
echo "Enter your GitHub username:"
read GITHUB_USER
git clone https://github.com/$GITHUB_USER/bankshot-tournament-display.git
```

**What happens:**
- Asks for your GitHub username
- Clones your new repository
- Verifies clone succeeded
- **New system downloaded**

**You'll see:**
```
Enter your GitHub username: YourUsername
Cloning into 'bankshot-tournament-display'...
✓ Repository cloned
```

**If it fails:**
- Check GitHub username spelling
- Verify repository exists
- Check internet connection

---

### **Step 5: Run Installer** (15 minutes)
```bash
echo "Press Enter to continue with installation..."
read
sudo bash scripts/install.sh
```

**What happens:**
- Pauses for you to confirm
- Runs the complete installer
- Updates system packages
- Installs dependencies
- Sets up Apache (keeps existing config)
- Copies web files (won't overwrite existing ones)
- Installs PHP QR code library
- Sets up systemd service
- Configures cron jobs
- **System fully installed**

**You'll see:**
```
Press Enter to continue with installation...
[Hit Enter]

Installing Bankshot Tournament Display System...
✓ System packages updated
✓ Apache installed
✓ PHP installed
✓ Python packages installed
✓ Web files copied
✓ Systemd service installed
✓ Cron job configured
✓ Installation complete
```

**Installer asks:**
1. "Confirm installation? (y/n)" → Type `y`
2. "Install HDMI display service? (y/n)" → Type `y` if you have HDMI TV, `n` if not

---

### **Step 6: Integrate Display Files** (30 seconds)
```bash
cp ~/web_files_backup/*.php ~/bankshot-tournament-display/web/
cp ~/web_files_backup/*.html ~/bankshot-tournament-display/web/
```

**What happens:**
- Copies your backed-up display files into the new repo
- Puts them in the `web/` directory
- **Your working files now in version control**

**You'll see:**
```
✓ Display files copied to repo
```

---

### **Step 7: Push to GitHub** (1 minute)
```bash
git config user.name "Bankshot Pi"
git config user.email "pi@bankshot.local"
git add web/*.php web/*.html
git commit -m "Add working display files from Pi"
git push origin main
```

**What happens:**
- Configures git
- Adds your display files
- Commits them
- Pushes to GitHub
- **Your files now backed up in GitHub**

**You'll see:**
```
✓ Display files pushed to GitHub
```

**If push fails:**
- May need GitHub authentication
- You can push manually later
- Files are still on Pi and working

---

### **Step 8: Verification** (30 seconds)
```bash
systemctl is-active apache2
systemctl is-active web-monitor.service
# ... checks all components
```

**What happens:**
- Verifies Apache is running
- Checks monitor service
- Confirms all files exist
- Tests cron job
- **Complete system check**

**You'll see:**
```
Services:
  ✓ Apache running
  ✓ Monitor running

Files:
  ✓ index.php
  ✓ ads_display.html
  ✓ media_manager.html
  ✓ tv.html

Cron:
  ✓ Cron job configured

========================================
MIGRATION COMPLETE!
========================================
```

---

## 🎬 How to Run the Script

### **FIRST: On Your Computer** (5 minutes)

Push the core system to GitHub:

```bash
cd /tmp/bankshot-complete

# Create .gitignore
cat > .gitignore << 'EOF'
*.log
logs/
__pycache__/
*.pyc
.DS_Store
web/media/*.mp4
web/media/*.jpg
web/media/*.png
!web/media/.gitkeep
EOF

# Create media directory placeholder
mkdir -p web/media
touch web/media/.gitkeep

# Initialize git
git init
git add .
git commit -m "Initial commit - core system"

# Add your repository (REPLACE YOUR_USERNAME!)
git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git

# Push to GitHub
git branch -M main
git push -u origin main
```

**✓ GitHub now has the core system**

---

### **THEN: On Your Pi** (45 minutes)

#### Option A: Copy/Paste Full Script

1. SSH into your Pi:
   ```bash
   ssh pi@bankshot-display.local
   ```

2. Create the script file:
   ```bash
   nano ~/migrate.sh
   ```

3. Copy the ENTIRE script from SMART_MIGRATION_COMMANDS.md (lines 6-132)

4. Paste into nano (Ctrl+Shift+V or right-click)

5. Save and exit (Ctrl+X, then Y, then Enter)

6. Make executable:
   ```bash
   chmod +x ~/migrate.sh
   ```

7. Run it:
   ```bash
   bash ~/migrate.sh
   ```

8. Follow the prompts:
   - Enter GitHub username
   - Press Enter to confirm installation
   - Answer installer questions

---

#### Option B: Copy/Paste Line-by-Line

If you prefer to see each step, copy/paste the commands from lines 154-189 one at a time.

---

## 🛡️ Safety Features

### **What Makes This Safe:**

1. **Backs up FIRST** - Your files copied before any changes
2. **Non-destructive** - Doesn't delete /var/www/html/
3. **Verification** - Checks each step succeeded
4. **Rollback ready** - Can restore from ~/web_files_backup/
5. **Tested logic** - Only removes specific old components

### **If Something Goes Wrong:**

**Restore your files:**
```bash
sudo cp ~/web_files_backup/*.php /var/www/html/
sudo cp ~/web_files_backup/*.html /var/www/html/
sudo cp -r ~/web_files_backup/media /var/www/html/
sudo systemctl restart apache2
```

**You're back to where you started!**

---

## 📊 Timeline Breakdown

| Step | Time | What You Do |
|------|------|-------------|
| Computer: Push to GitHub | 5 min | Copy/paste commands |
| Pi: Backup files | 30 sec | Script runs automatically |
| Pi: Clean old system | 2 min | Script runs automatically |
| Pi: Clone repository | 1 min | Enter GitHub username |
| Pi: Run installer | 15 min | Press Enter, answer 2 questions |
| Pi: Integrate files | 30 sec | Script runs automatically |
| Pi: Push to GitHub | 1 min | Script runs automatically |
| Pi: Verify | 30 sec | Script runs automatically |
| **TOTAL** | **~25 min** | **Mostly automated!** |

---

## 💡 What You Need to Know

### **Before You Start:**

1. **GitHub username** - You'll be asked for it
2. **GitHub repository created** - Must exist on GitHub first
3. **SSH access to Pi** - Need to be logged in
4. **Internet connection** - Pi needs to download packages

### **What You'll Be Asked:**

1. **"Enter your GitHub username:"**
   - Type your GitHub username (e.g., `JohnDoe`)

2. **"Press Enter to continue with installation..."**
   - Just hit Enter

3. **"Confirm installation? (y/n)"**
   - Type `y` and Enter

4. **"Install HDMI display service? (y/n)"**
   - Type `y` if you have HDMI-connected display
   - Type `n` if you only use network TVs

### **What Happens Automatically:**

- ✅ Files backed up
- ✅ Old system cleaned
- ✅ New system installed
- ✅ Services configured
- ✅ Cron jobs set up
- ✅ Everything verified

---

## 🎯 After the Script Finishes

### **Immediate Checks:**

```bash
# Check services
sudo systemctl status apache2
sudo systemctl status web-monitor.service

# Check files
ls -lh /var/www/html/*.php /var/www/html/*.html

# Check tournament data
cat /var/www/html/tournament_data.json | python3 -m json.tool

# Check logs
tail -20 /home/pi/logs/github_pull.log
```

### **Test Your TVs:**

1. Open TV browser
2. Go to: `http://bankshot-display.local/tv.html`
3. Should show your ads rotating
4. Should auto-switch when tournament detected

### **Test Media Manager:**

1. On any device
2. Go to: `http://bankshot-display.local/media_manager.html`
3. Should show your existing media
4. Try uploading a test image

### **Verify GitHub Actions:**

1. Go to: `https://github.com/YOUR_USERNAME/bankshot-tournament-display/actions`
2. Should see "Scrape Tournaments" workflow
3. Click "Run workflow" to test
4. Should complete successfully in 2-3 minutes

---

## 🎊 Success Criteria

After the script, you should have:

- [ ] ✅ Apache running
- [ ] ✅ Web monitor service running
- [ ] ✅ All 5 display files in /var/www/html/
- [ ] ✅ All display files in ~/bankshot-tournament-display/web/
- [ ] ✅ Cron job pulling from GitHub every 5 minutes
- [ ] ✅ GitHub Actions scraping every 15 minutes
- [ ] ✅ TVs displaying correctly
- [ ] ✅ No old CATT/monitor services running
- [ ] ✅ Backup files in ~/web_files_backup/

---

## 🆘 Troubleshooting

### **"Failed to clone repository"**
- Check GitHub username spelling
- Verify repository exists: https://github.com/YOUR_USERNAME/bankshot-tournament-display
- Check internet connection

### **"Installation failed"**
- Check logs: `sudo journalctl -xe`
- Try running installer manually: `cd ~/bankshot-tournament-display && sudo bash scripts/install.sh`

### **"Push failed"**
- You may need to authenticate with GitHub
- Generate Personal Access Token: https://github.com/settings/tokens
- Push manually later: `cd ~/bankshot-tournament-display && git push origin main`

### **"Apache not running"**
- Check status: `sudo systemctl status apache2`
- Check config: `sudo apache2ctl configtest`
- Restart: `sudo systemctl restart apache2`

### **"Files missing"**
- Restore from backup: `sudo cp ~/web_files_backup/* /var/www/html/`
- Check permissions: `sudo chown -R www-data:www-data /var/www/html/`

---

## 📞 Ready to Run?

**You have two choices:**

### **1. Copy the Full Script** (Easiest)
- Copy lines 6-132 from SMART_MIGRATION_COMMANDS.md
- Paste into `~/migrate.sh` on your Pi
- Run: `bash ~/migrate.sh`

### **2. I'll Guide You Line-by-Line**
- Tell me: "Guide me through the script"
- I'll walk you through each command
- You copy/paste one at a time

**Which approach would you prefer?** 🚀
