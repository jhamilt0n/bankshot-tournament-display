# Complete Repository File Structure

## What You Have - Ready to Upload to GitHub

All files are in `/tmp/bankshot-complete/` and ready to be uploaded to your GitHub repository.

### 📁 Root Directory Files

```
bankshot-tournament-display/
├── README.md                     # Main documentation - START HERE
├── QUICKREF.md                   # Quick reference card
├── SYSTEM_DIAGRAM.md             # Visual system architecture
├── MIGRATION_GUIDE.md            # Detailed migration from old system
├── MIGRATION_CHECKLIST.md        # Printable checklist
```

### 📁 Documentation (docs/)

```
docs/
├── SETUP.md                      # Complete setup guide
├── ARCHITECTURE.md               # Technical architecture details
└── CONSOLIDATION.md              # Why/how we consolidated
```

### 📁 GitHub Actions (.github/workflows/)

**You need to copy this from your original documents:**

```
.github/
└── workflows/
    └── scrape.yml                # GitHub Actions workflow
```

**Content**: Use the `scrape.yml` from document index 3 in your original files

### 📁 Scraper (scraper/)

**You need to copy this from your original documents:**

```
scraper/
└── bankshot_monitor_multi.py     # Main tournament scraper
```

**Content**: Use `bankshot_monitor_multi.py` from document index 4 in your original files

### 📁 Scripts (scripts/)

**You already have:**
```
scripts/
└── install.sh                    # Main installer (already created)
```

**You need to copy these from your original documents:**

```
scripts/
├── pull_tournament_data.sh       # Pulls from GitHub (document 6)
├── web_monitor.py                # Status monitor (document 9 or 16)
└── hdmi_display_manager.sh       # Optional HDMI control (document 8 or 14)
```

### 📁 Services (services/)

**You need to copy these from your original documents:**

```
services/
├── web-monitor.service           # Systemd service (document 19)
└── hdmi-display.service          # Optional HDMI service (document 17)
```

### 📁 Web Files (web/)

**You need to copy ALL these from your original documents:**

```
web/
├── index.php                     # Main display (document 13 or 26)
├── ads_display.html              # Ads display (document 20)
├── tv.html                       # Auto-switching TV page (document 32)
├── media_manager.html            # Content manager (document 28)
├── calcutta.html                 # Calcutta display (document 22)
├── qr_setup.php                  # TV setup QR codes (document 30)
├── tv_setup.html                 # Setup instructions (document 33)
├── payout_calculator.php         # Payout calculations (document 29)
├── get_tournament_data.php       # API endpoint (document 25)
├── load_media.php                # Media loader (document 27)
├── save_media.php                # Media saver (document 31)
├── upload_file.php               # File upload (document 34)
├── delete_file.php               # File deletion (document 23)
├── calculate_payouts.php         # Payout API (document 21)
└── generate_qr.php               # QR generator (document 24)
```

---

## Complete Checklist - What to Do

### ✅ Step 1: Create Directory Structure on Your Computer

```bash
# Create a working directory
mkdir -p ~/bankshot-tournament-display-new
cd ~/bankshot-tournament-display-new

# Create subdirectories
mkdir -p .github/workflows
mkdir -p docs
mkdir -p scraper
mkdir -p scripts
mkdir -p services
mkdir -p web

# Copy files from /tmp/bankshot-complete/
cp /tmp/bankshot-complete/README.md .
cp /tmp/bankshot-complete/QUICKREF.md .
cp /tmp/bankshot-complete/SYSTEM_DIAGRAM.md .
cp /tmp/bankshot-complete/MIGRATION_GUIDE.md .
cp /tmp/bankshot-complete/MIGRATION_CHECKLIST.md .

cp /tmp/bankshot-complete/docs/* docs/
cp /tmp/bankshot-complete/scripts/* scripts/
```

### ✅ Step 2: Copy Files from Your Original Documents

Now you need to extract these from your chat and create the files:

#### .github/workflows/scrape.yml
- **From**: Document 3 (`.github/workflows/scrape.yml`)
- **To**: `.github/workflows/scrape.yml`
- **Note**: This runs the scraper every 15 minutes in GitHub Actions

#### scraper/bankshot_monitor_multi.py
- **From**: Document 4 (`bankshot_monitor_multi.py`)
- **To**: `scraper/bankshot_monitor_multi.py`
- **Note**: The main tournament scraper script

#### scripts/pull_tournament_data.sh
- **From**: Document 6 (`pull_tournament_data.sh`)
- **To**: `scripts/pull_tournament_data.sh`
- **Note**: Downloads tournament data from GitHub every 5 min

#### scripts/web_monitor.py
- **From**: Document 9 or 16 (`scripts/web_monitor.py` or `scripts/tournament_monitor.py`)
- **To**: `scripts/web_monitor.py`
- **Note**: Use the one that monitors tournament_data.json

#### scripts/hdmi_display_manager.sh
- **From**: Document 8 or 14 (`scripts/hdmi_display_manager.sh`)
- **To**: `scripts/hdmi_display_manager.sh`
- **Note**: Optional - only if you want HDMI-connected display

#### services/web-monitor.service
- **From**: Document 19 (`services/web-monitor.service`)
- **To**: `services/web-monitor.service`

#### services/hdmi-display.service
- **From**: Document 17 (`services/hdmi-display.service`)
- **To**: `services/hdmi-display.service`
- **Note**: Optional

#### All web/ files
Copy from documents 13, 20-34:
- index.php (doc 13 or 26)
- ads_display.html (doc 20)
- tv.html (doc 32)
- media_manager.html (doc 28)
- calcutta.html (doc 22)
- qr_setup.php (doc 30)
- tv_setup.html (doc 33)
- payout_calculator.php (doc 29)
- get_tournament_data.php (doc 25)
- load_media.php (doc 27)
- save_media.php (doc 31)
- upload_file.php (doc 34)
- delete_file.php (doc 23)
- calculate_payouts.php (doc 21)
- generate_qr.php (doc 24)

### ✅ Step 3: Verify File Structure

Your final structure should look like:

```
bankshot-tournament-display/
├── README.md
├── QUICKREF.md
├── SYSTEM_DIAGRAM.md
├── MIGRATION_GUIDE.md
├── MIGRATION_CHECKLIST.md
│
├── .github/
│   └── workflows/
│       └── scrape.yml
│
├── docs/
│   ├── SETUP.md
│   ├── ARCHITECTURE.md
│   └── CONSOLIDATION.md
│
├── scraper/
│   └── bankshot_monitor_multi.py
│
├── scripts/
│   ├── install.sh
│   ├── pull_tournament_data.sh
│   ├── web_monitor.py
│   └── hdmi_display_manager.sh
│
├── services/
│   ├── web-monitor.service
│   └── hdmi-display.service
│
└── web/
    ├── index.php
    ├── ads_display.html
    ├── tv.html
    ├── media_manager.html
    ├── calcutta.html
    ├── qr_setup.php
    ├── tv_setup.html
    ├── payout_calculator.php
    ├── get_tournament_data.php
    ├── load_media.php
    ├── save_media.php
    ├── upload_file.php
    ├── delete_file.php
    ├── calculate_payouts.php
    └── generate_qr.php
```

### ✅ Step 4: Make Scripts Executable

```bash
cd ~/bankshot-tournament-display-new
chmod +x scripts/*.sh
chmod +x scripts/*.py
chmod +x scraper/*.py
```

### ✅ Step 5: Create GitHub Repository

1. Go to https://github.com
2. Click "New repository"
3. Name: `bankshot-tournament-display`
4. Choose Public or Private
5. **Don't** initialize with README
6. Click "Create repository"

### ✅ Step 6: Push to GitHub

```bash
cd ~/bankshot-tournament-display-new

# Initialize git
git init
git add .
git commit -m "Initial commit - consolidated tournament display system"

# Add remote
git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git

# Push
git branch -M main
git push -u origin main
```

### ✅ Step 7: Enable GitHub Actions

1. Go to your repo on GitHub
2. Click "Actions" tab
3. Click "I understand my workflows, go ahead and enable them"

### ✅ Step 8: On Your Raspberry Pi

Follow the **MIGRATION_CHECKLIST.md** file:

1. Backup current system
2. Clean up old system
3. Clone new repository
4. Run installer
5. Configure TVs
6. Upload media

---

## Quick File Reference

### Which document has which file?

| File | Document # | Notes |
|------|-----------|-------|
| scrape.yml | 3 | GitHub Actions workflow |
| bankshot_monitor_multi.py | 4 | Main scraper |
| pull_tournament_data.sh | 6 | Git pull script |
| web_monitor.py | 9, 16 | Status monitor |
| hdmi_display_manager.sh | 8, 14 | Optional HDMI |
| web-monitor.service | 19 | Systemd service |
| hdmi-display.service | 17 | Optional service |
| index.php | 13, 26 | Main display |
| ads_display.html | 20 | Ads display |
| calculate_payouts.php | 21 | Payout API |
| calcutta.html | 22 | Calcutta display |
| delete_file.php | 23 | File deletion |
| generate_qr.php | 24 | QR generator |
| get_tournament_data.php | 25 | Tournament API |
| load_media.php | 27 | Load media config |
| media_manager.html | 28 | Content manager |
| payout_calculator.php | 29 | Payout logic |
| qr_setup.php | 30 | TV setup QR |
| save_media.php | 31 | Save media config |
| tv.html | 32 | Auto-switch page |
| tv_setup.html | 33 | Setup guide |
| upload_file.php | 34 | File upload |

---

## I Can Help Extract Files

Would you like me to create all the missing files for you by extracting them from your original documents? I can:

1. Create each file individually
2. Put them in the correct directory structure
3. Make sure they're all ready to upload

Just let me know and I'll extract all the web files, scripts, and services from your documents!

---

## Summary

**What you have ready:**
- ✅ All documentation (README, guides, checklists)
- ✅ Main installer script
- ✅ Directory structure defined

**What you need to do:**
- 📝 Copy/extract files from your original documents
- 📝 Create GitHub repository
- 📝 Push everything to GitHub
- 📝 Run migration on your Raspberry Pi

**Time estimate:**
- Extract files: 15 minutes
- Upload to GitHub: 5 minutes
- Run migration on Pi: 45 minutes
- **Total: ~1 hour**

Ready to proceed? Let me know if you want me to help extract the files!
