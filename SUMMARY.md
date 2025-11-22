# Bankshot Wireless Display System - Complete Package Summary

## 📦 What's Included

This package contains everything needed to set up a universal wireless tournament display system that works with **ANY Smart TV brand**.

---

## 🗂️ File Structure

```
bankshot-tournament-display/
├── install.sh                          # Main installer script
├── uninstall.sh                        # Complete uninstaller
├── README.md                           # Main documentation
├── INSTALL.md                          # Detailed installation guide
├── MIGRATION.md                        # Migration from CATT guide
├── LICENSE                             # MIT License
│
├── scripts/
│   ├── tournament_monitor.py           # Pulls tournament data from GitHub
│   ├── web_monitor.py                  # Logs display status (NEW)
│   └── hdmi_display_manager.sh         # Controls HDMI display
│
├── services/
│   ├── tournament-monitor.service      # Systemd service
│   ├── web-monitor.service             # Systemd service (NEW)
│   └── hdmi-display.service            # Systemd service
│
└── web/
    ├── tv.html                         # Universal TV display (NEW)
    ├── qr_setup.php                    # QR code setup page (NEW)
    ├── tv_setup.html                   # Setup instructions (NEW)
    ├── index.php                       # Tournament display
    ├── ads_display.html                # Ad rotation display
    ├── media_manager.html              # Media management interface
    ├── calcutta.html                   # Calcutta auction display
    ├── payout_calculator.php           # Payout calculator
    ├── calculate_payouts.php           # Payout backend
    ├── get_tournament_data.php         # Tournament data API
    ├── generate_qr.php                 # QR code generator
    ├── load_media.php                  # Media loading API
    ├── save_media.php                  # Media saving backend
    ├── upload_file.php                 # File upload handler
    └── delete_file.php                 # File deletion handler
```

---

## 🆕 New Files Created

### Installation & Documentation

| File | Purpose |
|------|---------|
| `install.sh` | Updated installer (removed CATT, added mDNS) |
| `uninstall.sh` | Updated uninstaller |
| `README.md` | Complete rewrite for wireless system |
| `INSTALL.md` | Comprehensive installation guide |
| `MIGRATION.md` | Migration guide from CATT system |

### Scripts

| File | Purpose |
|------|---------|
| `scripts/web_monitor.py` | Replaces CATT monitor, logs status |
| `services/web-monitor.service` | Systemd service file |

### Web Files

| File | Purpose |
|------|---------|
| `web/tv.html` | Main TV display with auto-discovery |
| `web/qr_setup.php` | QR code generation for easy setup |
| `web/tv_setup.html` | Brand-specific setup instructions |

---

## ❌ Files Removed

These files are **NO LONGER USED** or **NO LONGER NEEDED**:

```
scripts/catt_monitor.py                 # Replaced by web_monitor.py
services/catt-monitor.service           # Replaced by web-monitor.service
```

**Note:** These files may still exist in your old repository but should not be copied to production.

---

## 📝 Key Changes Summary

### 1. Removed CATT Dependency

**Before:**
- Required `pip install catt`
- `catt_monitor.py` service
- Chromecast hardware needed
- CATT commands for casting

**After:**
- No CATT installation
- `web_monitor.py` service (simple logging)
- No hardware needed
- TVs pull data via browser

### 2. Added mDNS Support

**Before:**
- IP address changes required manual TV reconfiguration
- CATT handled mDNS for Chromecast only

**After:**
- `avahi-daemon` provides mDNS
- `bankshot-display.local` hostname works always
- TVs use hostname, not IP
- Zero human intervention on IP changes

### 3. Universal TV Support

**Before:**
- Only Chromecast-compatible devices
- Limited to specific TV brands/models

**After:**
- ANY Smart TV with web browser
- Samsung, LG, Sony, TCL, Vizio, Fire TV, Roku, etc.
- Unlimited TVs per Raspberry Pi
- No additional hardware cost

### 4. Simplified Setup

**Before:**
1. Buy Chromecast ($30-50)
2. Plug into TV
3. Configure on Pi
4. Run CATT commands

**After:**
1. Open TV browser
2. Enter URL (or scan QR code)
3. Bookmark
4. Done

---

## 🚀 Installation Steps

### Quick Install

```bash
# 1. Clone repository
git clone https://github.com/jhamilt0n/bankshot-tournament-display.git
cd bankshot-tournament-display

# 2. Run installer
chmod +x install.sh
sudo ./install.sh

# 3. Follow prompts (GitHub repo URL, hostname)

# 4. Wait 10-15 minutes for installation

# 5. Configure TVs
# On each TV: Open browser → http://bankshot-display.local/tv.html
```

---

## 📺 TV Configuration

### Option 1: QR Code (Easiest)

1. Visit on phone: `http://bankshot-display.local/qr_setup.php`
2. Scan QR code with TV browser
3. Bookmark page
4. Done!

### Option 2: Manual Entry

1. Open TV's web browser
2. Navigate to: `http://bankshot-display.local/tv.html`
3. Bookmark the page
4. Done!

---

## 🔧 System Services

### Services Installed

| Service | Purpose | Auto-Start |
|---------|---------|-----------|
| `tournament-monitor` | Pulls GitHub data | ✅ Yes |
| `web-monitor` | Logs display status | ✅ Yes |
| `hdmi-display` | Controls HDMI TV | ✅ Yes |
| `avahi-daemon` | Provides mDNS | ✅ Yes |
| `apache2` | Web server | ✅ Yes |

### Service Commands

```bash
# Check status
sudo systemctl status tournament-monitor
sudo systemctl status web-monitor
sudo systemctl status hdmi-display

# Restart services
sudo systemctl restart tournament-monitor
sudo systemctl restart web-monitor
sudo systemctl restart hdmi-display

# View logs
tail -f /var/log/tournament_monitor.log
tail -f /var/log/web_monitor.log
tail -f /var/log/hdmi_display.log
```

---

## 🌐 Web Interfaces

### Main URLs (After Installation)

| Interface | URL | Purpose |
|-----------|-----|---------|
| **TV Display** | `http://bankshot-display.local/tv.html` | Main TV display |
| **Media Manager** | `http://bankshot-display.local/media_manager.html` | Upload media |
| **QR Setup** | `http://bankshot-display.local/qr_setup.php` | Easy TV setup |
| **Setup Guide** | `http://bankshot-display.local/tv_setup.html` | Instructions |
| **Tournament View** | `http://bankshot-display.local/` | Bracket display |
| **Ads Display** | `http://bankshot-display.local/ads_display.html` | Ad rotation |
| **Calcutta** | `http://bankshot-display.local/calcutta.html` | Auction display |
| **Payout Calc** | `http://bankshot-display.local/payout_calculator.php` | Payouts |

---

## 🎯 How It Works

### Data Flow

```
1. GitHub Actions scrapes DigitalPool.com
   ↓
2. tournament-scraper repo updates tournament_data.json
   ↓
3. Raspberry Pi pulls from GitHub every 60 seconds
   ↓
4. tournament_data.json saved locally
   ↓
5. TVs check this file every 30 seconds via JavaScript
   ↓
6. TVs auto-switch between tournament and ads
```

### Display Logic

| Tournament Status | TV Shows |
|------------------|----------|
| `display_tournament: true` | Tournament brackets |
| `display_tournament: false` | Advertisements |

**Zero human intervention required!**

---

## 📋 Requirements

### Hardware
- Raspberry Pi 4 (4GB RAM) or Pi Zero 2 W
- 32GB+ microSD card (Class 10+)
- Smart TV(s) with web browser
- WiFi network

### Software
- Raspberry Pi OS Desktop (Bookworm/Bullseye)
- Internet connection
- Git installed

---

## 🐛 Troubleshooting

### Common Issues

**TV can't find `.local` address**
→ Use IP instead: `http://192.168.X.X/tv.html`

**Display not switching**
→ Check: `cat /var/www/html/tournament_data.json`
→ Verify: `"display_tournament": true/false`

**Service not running**
→ Check: `sudo systemctl status SERVICE_NAME`
→ Logs: `tail -f /var/log/SERVICE_NAME.log`

**Can't upload large files**
→ Verify: `php -i | grep upload_max_filesize`
→ Should show: `100M`

See [INSTALL.md](INSTALL.md#troubleshooting) for complete troubleshooting guide.

---

## 🔄 Updating

```bash
cd ~/bankshot-tournament-display
git pull origin main
sudo ./install.sh  # Re-run to update all files
```

Media files and settings are preserved during updates.

---

## 🗑️ Uninstalling

```bash
cd ~/bankshot-tournament-display
sudo ./uninstall.sh
```

Removes all system components except:
- Media files (preserved in `/var/www/html/media/`)
- System packages
- Hostname setting

---

## 📊 Comparison: CATT vs Wireless

| Feature | CATT System | Wireless System |
|---------|-------------|-----------------|
| **Hardware Cost** | $30-50/TV (Chromecast) | $0/TV |
| **TV Compatibility** | Chromecast only | All Smart TVs |
| **Setup Time** | 10-15 min/TV | 2-3 min/TV |
| **IP Change Handling** | ✅ Automatic | ✅ Automatic (mDNS) |
| **Scalability** | Limited by Chromecasts | Unlimited |
| **Reliability** | Hardware dependency | Browser-based |
| **Maintenance** | Manage Chromecast devices | Zero maintenance |

---

## 🎓 Documentation

### Read First
1. **[README.md](README.md)** - Overview and quick start
2. **[INSTALL.md](INSTALL.md)** - Complete installation guide

### Reference
3. **[MIGRATION.md](MIGRATION.md)** - Migrating from CATT
4. **[tv_setup.html](web/tv_setup.html)** - TV brand instructions
5. **[qr_setup.php](web/qr_setup.php)** - QR code for setup

---

## ✅ Installation Checklist

Before installing:
- [ ] Raspberry Pi OS Desktop installed (NOT Lite)
- [ ] System updated (`sudo apt-get update && upgrade`)
- [ ] Git installed (`git --version`)
- [ ] Internet connection working
- [ ] Know your Pi's IP address

After installing:
- [ ] All services show "active (running)"
- [ ] Can access `http://bankshot-display.local/`
- [ ] Media manager loads
- [ ] QR setup page works
- [ ] First TV configured successfully
- [ ] Display auto-switches based on tournament

---

## 🔐 Security Notes

### Default Security
- Web server runs on port 80 (HTTP)
- No authentication on web interfaces
- Designed for trusted local networks

### Recommendations
- Use on private WiFi network
- Keep Pi OS updated
- Set strong SSH password
- Consider firewall rules if exposed

---

## 📞 Support

### Getting Help
- **Documentation**: [INSTALL.md](INSTALL.md)
- **GitHub Issues**: https://github.com/jhamilt0n/bankshot-tournament-display/issues
- **TV Setup Guide**: `http://bankshot-display.local/tv_setup.html`

### Reporting Issues
Include:
- Error messages from logs
- `sudo systemctl status SERVICE_NAME` output
- Pi model and OS version
- What you've already tried

---

## 📜 License

This project is licensed under the MIT License - see [LICENSE](LICENSE) file.

---

## 🙏 Credits

- **Built for**: Bankshot Billiards, Hilliard, OH
- **Tournament data**: DigitalPool.com
- **QR codes**: [php-qrcode](https://github.com/chillerlan/php-qrcode)
- **Developed by**: [jhamilt0n](https://github.com/jhamilt0n)

---

## 🎱 Final Notes

### What Makes This System Special

1. **Universal Compatibility**
   - Works with literally any Smart TV
   - No brand restrictions
   - No hardware dependencies

2. **Zero Configuration**
   - mDNS handles IP changes automatically
   - TVs discover server themselves
   - One-time setup per TV, works forever

3. **Professional Quality**
   - Smooth transitions
   - Reliable switching
   - Production-ready

4. **Cost Effective**
   - $0 per TV (vs $30-50 for Chromecast)
   - Unlimited scalability
   - Lower total cost of ownership

5. **Easy Management**
   - Web-based media manager
   - Drag-and-drop uploads
   - Schedule by day/time
   - Set expiration dates

---

## 🚀 Ready to Deploy

Everything you need is included:
- ✅ Installation scripts
- ✅ System services
- ✅ Web interfaces
- ✅ Documentation
- ✅ TV setup guides
- ✅ Troubleshooting help

**Just run `sudo ./install.sh` and you're ready to go!**

---

**Made with ❤️ for the pool community** 🎱
