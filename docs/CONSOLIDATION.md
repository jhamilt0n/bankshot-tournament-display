# Repository Consolidation Guide

## What Was Changed

Your original setup had multiple scattered scripts and configurations. This consolidation combines everything into **one repository** that works seamlessly on a **Raspberry Pi 4 Model B**.

## Original Structure (Scattered)

```
Multiple separate files:
├── tournament_monitor.py (different versions)
├── bankshot_monitor_multi.py
├── smart_switcher_status.py
├── catt_monitor.py (Chromecast - obsolete)
├── Various setup scripts
├── Web files scattered
└── Different workflows
```

**Problems**:
- Confusing which files to use
- Multiple versions of same thing
- Unclear dependencies
- Hard to set up from scratch

## New Structure (Consolidated)

```
bankshot-tournament-display/
├── .github/workflows/
│   └── scrape.yml                    # GitHub Actions scraper
│
├── scraper/
│   └── bankshot_monitor_multi.py     # The ONE scraper to use
│
├── web/
│   ├── index.php                     # Tournament display
│   ├── ads_display.html              # Ads display
│   ├── tv.html                       # Auto-switching TV page
│   ├── media_manager.html            # Content manager
│   ├── calcutta.html                 # Calcutta display
│   ├── qr_setup.php                  # TV setup QR codes
│   ├── tv_setup.html                 # Setup instructions
│   ├── payout_calculator.php         # Payout calculations
│   ├── get_tournament_data.php       # API endpoint
│   ├── load_media.php                # Media loader
│   ├── save_media.php                # Media saver
│   ├── upload_file.php               # File upload
│   ├── delete_file.php               # File deletion
│   ├── calculate_payouts.php         # Payout API
│   └── generate_qr.php               # QR generator
│
├── scripts/
│   ├── install.sh                    # MAIN INSTALLER - run this!
│   ├── pull_tournament_data.sh       # Pulls from GitHub
│   ├── web_monitor.py                # Status monitor
│   └── hdmi_display_manager.sh       # Optional HDMI control
│
├── services/
│   ├── web-monitor.service           # Systemd service
│   └── hdmi-display.service          # Optional HDMI service
│
├── docs/
│   ├── SETUP.md                      # Complete setup guide
│   ├── ARCHITECTURE.md               # System architecture
│   └── CONSOLIDATION.md              # This file
│
└── README.md                         # Main documentation
```

## What Got Combined

### 1. Scraper Scripts → ONE Script

**Before**: Multiple versions
- `tournament_monitor.py` (basic)
- `bankshot_monitor_multi.py` (multi-tournament)
- Different for Pi vs GitHub

**After**: `scraper/bankshot_monitor_multi.py`
- Handles single or multiple tournaments
- Smart priority logic
- Works both on Pi and in GitHub Actions
- Keeps tournament displayed after midnight until complete

### 2. Display Scripts → Web-Based System

**Before**: Chromecast-based (CATT)
- `catt_monitor.py`
- `smart_switcher_status.py`
- Required Pi to maintain casting
- Reliability issues

**After**: Web-based (tvs.html)
- TVs use built-in browsers
- Pull-based (TVs check server)
- Auto-recovery
- No casting needed
- More reliable

### 3. Setup Scripts → ONE Installer

**Before**: Multiple partial setups
- `setup_github_pull.sh`
- `install_boot_check.sh`
- Various service installers

**After**: `scripts/install.sh`
- Does everything in one run
- Idempotent (safe to run multiple times)
- Validates each step
- Clear progress messages
- ~10-15 minutes total

## Major Improvements

### 1. Simplified Architecture

```
OLD (Complex):
GitHub Actions → Pi Scraper → Pi Caster → Chromecast → TV

NEW (Simple):
GitHub Actions → Pi Web Server → TV Browser
```

**Benefits**:
- Fewer moving parts
- More reliable
- Easier to debug
- No casting hardware needed

### 2. Better TV Support

**OLD**:
- Required Chromecast
- Connection issues
- Had to manually restart casting
- Pi had to stay connected

**NEW**:
- Uses TV's built-in browser
- Auto-reconnects if disconnected
- Works with any smart TV
- Set it and forget it

### 3. Unified Data Flow

**OLD**: Multiple paths
- Local scraping on Pi
- GitHub scraping
- Different data formats
- Confusion about which is "truth"

**NEW**: Single source of truth
1. GitHub Actions scrapes → commits JSON
2. Pi pulls → serves to TVs
3. TVs poll → auto-switch displays

**Clear, linear, predictable**

### 4. Better Media Management

**OLD**:
- Manual file editing
- No UI for media
- Hard to schedule content
- No drag-and-drop

**NEW**:
- Web UI (`media_manager.html`)
- Drag-and-drop reordering
- Visual preview
- Schedule by day/time
- Per-item display settings (ads vs tournaments)

### 5. Easier Setup

**OLD**: Manual steps
1. Install dependencies
2. Copy files manually
3. Edit multiple configs
4. Set up services one by one
5. Configure cron jobs
6. Set up casting
7. Hope it works

**NEW**: Automated
```bash
sudo bash scripts/install.sh
# Done in 10-15 minutes!
```

## What Was Removed (and Why)

### Chromecast/CATT System

**Files removed**:
- `catt_monitor.py`
- `smart_switcher_status.py`
- All CATT-related setup

**Why removed**:
- Unreliable (connection drops)
- Required extra hardware (Chromecast)
- Pi had to maintain casting
- Complex error recovery
- Didn't work with all TVs

**Replacement**:
- `tv.html` with web browser
- TVs pull updates themselves
- More reliable
- Works with any smart TV

### Multiple Scraper Versions

**Files removed**:
- Old `tournament_monitor.py`
- Various scraper copies
- Status-based vs time-based variants

**Why consolidated**:
- Confusing which to use
- Maintenance nightmare
- Feature divergence

**Replacement**:
- Single `bankshot_monitor_multi.py`
- Handles all scenarios
- One codebase to maintain

### Boot Check Scripts

**Files removed**:
- `install_boot_check.sh`
- `boot_tournament_check.sh`

**Why removed**:
- Not needed with systemd services
- Cron + systemd is more reliable
- Simpler architecture

**Replacement**:
- `web-monitor.service` (systemd)
- Cron job for git pulls
- More standard approach

## Migration from Old Setup

If you have an existing setup, here's how to migrate:

### 1. Backup Your Current System

```bash
# Backup current web files
sudo tar czf ~/backup_web_$(date +%Y%m%d).tar.gz /var/www/html/

# Backup media
cp -r /var/www/html/media ~/backup_media/

# Note your current settings
cat /var/www/html/tournament_data.json > ~/current_tournament.json
```

### 2. Stop Old Services

```bash
# Stop CATT if running
pkill -f catt_monitor

# Stop old scraper if running
pkill -f tournament_monitor

# Disable old cron jobs
crontab -e  # Remove old entries
```

### 3. Install New System

```bash
cd /home/pi
git clone https://github.com/YOUR_USERNAME/bankshot-tournament-display.git
cd bankshot-tournament-display
sudo bash scripts/install.sh
```

### 4. Restore Your Media

```bash
# Copy old media to new location
sudo cp -r ~/backup_media/* /var/www/html/media/
sudo chown -R www-data:www-data /var/www/html/media/
```

### 5. Reconfigure TVs

Instead of Chromecast:
1. Open TV's web browser
2. Visit: `http://bankshot-display.local/tv.html`
3. Bookmark the page
4. Close browser
5. Reopen bookmark

### 6. Verify Everything Works

```bash
# Check services
sudo systemctl status web-monitor.service
sudo systemctl status apache2

# Check data is flowing
cat /var/www/html/tournament_data.json

# Check logs
sudo journalctl -u web-monitor.service -f
```

### 7. Clean Up Old Files (Optional)

```bash
# Remove old Chromecast scripts
rm -rf ~/.local/bin/catt*

# Remove old scraper copies
rm -f ~/tournament_monitor.py
rm -f ~/bankshot_monitor_status.py
```

## Key Differences to Remember

### 1. No More Casting

**OLD**: `catt cast_site http://...`
**NEW**: TVs load URL themselves

### 2. Different URL Structure

**OLD**: Different pages
- `index2.php` (tournament)
- `index.php` (ads)

**NEW**: Single smart page
- `tv.html` (auto-switches)
- Or use `index.php` directly (it also rotates ads)

### 3. Media Configuration

**OLD**: Editing PHP arrays
**NEW**: Web UI at `/media_manager.html`

### 4. Data Location

**SAME**: `/var/www/html/tournament_data.json`
- Same format
- Same polling
- Same logic

### 5. Services

**OLD**: Custom Python scripts
**NEW**: Systemd services
```bash
# Check status
sudo systemctl status web-monitor.service

# View logs
sudo journalctl -u web-monitor.service
```

## Advantages of New System

### For You (Operator)

✅ **One-command install**: `sudo bash scripts/install.sh`
✅ **Web-based management**: No SSH needed for content
✅ **Better reliability**: Fewer failure points
✅ **Easier debugging**: Clear logs, simple architecture
✅ **Less maintenance**: Systemd services auto-restart

### For Users (TVs)

✅ **Works with any TV**: No special hardware needed
✅ **Auto-recovery**: Reconnects if disconnected
✅ **Faster switching**: 30-second polling (vs manual restart)
✅ **More stable**: No casting connection to maintain

### For Development

✅ **Clearer codebase**: One repo, clear structure
✅ **Better docs**: Complete guides for setup
✅ **Version controlled**: Everything in git
✅ **Easier to contribute**: Standard structure

## Common Questions

### Q: Do I need to change my GitHub Actions?

**A**: Yes, but it's simple:
1. Upload new `scrape.yml` from `.github/workflows/`
2. Upload new `bankshot_monitor_multi.py` from `scraper/`
3. Old `tournament_data.json` format is compatible

### Q: Will my old media files work?

**A**: Yes! Just copy them to `/var/www/html/media/` and add them in Media Manager.

### Q: Do I need to reconfigure everything?

**A**: TVs yes (switch from Chromecast to browser). Pi mostly automated by installer.

### Q: Can I keep using Chromecast?

**A**: Not recommended. The new system is more reliable. But if you must, you can manually cast to `http://bankshot-display.local/index.php`

### Q: What if my TV doesn't support .local addresses?

**A**: Use IP address: `http://192.168.1.XXX/tv.html`

### Q: Is the tournament detection logic the same?

**A**: Yes! Same scraper logic, just better organized.

## Verification Checklist

After migration, verify:

- [ ] Pi accessible at `http://bankshot-display.local/`
- [ ] Media Manager loads: `/media_manager.html`
- [ ] Tournament data accessible: `/tournament_data.json`
- [ ] Web monitor service running: `systemctl status web-monitor.service`
- [ ] Cron job set up: `crontab -l | grep pull_tournament_data`
- [ ] TVs can access `tv.html`
- [ ] Media displays correctly in ads mode
- [ ] GitHub Actions running successfully
- [ ] Data pulls from GitHub working

## Getting Help

If something doesn't work:

1. **Check logs first**:
   ```bash
   sudo journalctl -u web-monitor.service -f
   tail -f /home/pi/logs/github_pull.log
   ```

2. **Verify each component**:
   ```bash
   # Pi is accessible
   ping bankshot-display.local
   
   # Web server works
   curl http://bankshot-display.local/
   
   # Data file exists
   cat /var/www/html/tournament_data.json
   
   # Services running
   sudo systemctl status web-monitor.service
   sudo systemctl status apache2
   ```

3. **Check documentation**:
   - `docs/SETUP.md` - Step-by-step setup
   - `docs/ARCHITECTURE.md` - How it works
   - `README.md` - Overview

4. **Open an issue**: If still stuck, create GitHub issue with:
   - What you tried
   - Error messages
   - Log output

## Summary

This consolidation:
- **Simplifies** the architecture
- **Improves** reliability  
- **Reduces** maintenance
- **Enhances** user experience
- **Makes** setup easier

**One repository. One installer. One system.**

---

**Ready to get started?** See `docs/SETUP.md` for complete instructions!
