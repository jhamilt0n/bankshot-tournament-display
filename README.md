# Bankshot Billiards Tournament Display System

Complete tournament monitoring and display system for Bankshot Billiards in Hilliard, OH.

## 🎯 What This Does

1. **Monitors Tournaments**: Scrapes DigitalPool.com every 15 minutes via GitHub Actions
2. **Smart Display**: Automatically switches between tournament brackets and advertisements
3. **Web-Based TVs**: Smart TVs pull updates directly via their browsers (no Raspberry Pi needed at TVs)
4. **Media Management**: Easy web interface to manage ads, videos, and display content
5. **QR Codes**: Automatically generates QR codes for tournament brackets

## 📁 Repository Structure

```
bankshot-tournament-display/
├── .github/workflows/
│   └── scrape.yml              # GitHub Actions - runs scraper every 15 minutes
├── scraper/
│   └── bankshot_monitor_multi.py  # Tournament scraper
├── web/
│   ├── index.php               # Main tournament display
│   ├── ads_display.html        # Ads-only display
│   ├── tv.html                 # Auto-switching TV page
│   ├── media_manager.html      # Content management interface
│   ├── calcutta.html           # Calcutta auction display
│   ├── qr_setup.php           # QR code generator for TV setup
│   ├── tv_setup.html          # TV setup instructions
│   └── *.php                  # Supporting PHP files
├── services/
│   ├── web-monitor.service    # Systemd service
│   └── hdmi-display.service   # Optional HDMI display service
├── scripts/
│   ├── install.sh             # Main installation script
│   ├── setup_web_server.sh    # Web server setup
│   └── pull_tournament_data.sh # Download data from GitHub
└── docs/
    ├── SETUP.md               # Detailed setup guide
    └── ARCHITECTURE.md        # System architecture
```

## 🚀 Quick Start

### Option 1: Full Raspberry Pi Setup (Recommended)

**What you need:**
- Raspberry Pi 4 Model B (2GB+ RAM recommended)
- MicroSD card (16GB+)
- Internet connection

**Installation:**

```bash
# 1. Clone the repository
cd /home/pi
git clone https://github.com/jhamilt0n/bankshot-tournament-display.git
cd bankshot-tournament-display

# 2. Run the installer
sudo bash scripts/install.sh

# 3. Enable GitHub Actions in your repo (see docs/SETUP.md)
```

This will:
- Install Apache, PHP, and all dependencies
- Set up the web server on port 80
- Configure automatic data pulls from GitHub
- Start the monitoring service
- Set up mDNS (bankshot-display.local)

### Option 2: Manual GitHub Setup (Scraper Only)

If you only want the scraper running on GitHub:

1. Upload `scraper/bankshot_monitor_multi.py` to your GitHub repo
2. Upload `.github/workflows/scrape.yml` 
3. Enable GitHub Actions in your repo
4. The scraper will run every 15 minutes and commit `tournament_data.json`

## 🖥️ Setting Up Your TVs

After installation, your Raspberry Pi becomes a web server. Smart TVs connect to it:

### Method 1: QR Code (Easiest)

1. On your phone, visit: `http://bankshot-display.local/qr_setup.php`
2. Scan the QR code with your TV's browser
3. Bookmark the page on your TV
4. Done!

### Method 2: Manual URL

Open your TV's web browser and navigate to:
```
http://bankshot-display.local/tv.html
```

**Supported TVs:**
- ✅ Samsung Smart TVs
- ✅ LG webOS TVs  
- ✅ Sony Android TVs
- ✅ TCL Roku/Google TVs
- ✅ Amazon Fire TV
- ✅ Any TV with a web browser

See [docs/TV_SETUP.md](docs/TV_SETUP.md) for brand-specific instructions.

## 🎬 Managing Media Content

Visit `http://bankshot-display.local/media_manager.html` to:
- Upload images and videos
- Add website URLs (e.g., Calcutta auction page)
- Set display duration for each item
- Schedule content by day/time
- Choose whether content shows on ads or tournaments
- Drag-and-drop to reorder content

## 📊 How It Works

```
┌─────────────────┐
│  GitHub Actions │  ← Scrapes DigitalPool.com every 15 min
│    (Cloud)       │
└────────┬────────┘
         │ Commits tournament_data.json
         ↓
┌─────────────────┐
│  Raspberry Pi 4 │  ← Downloads from GitHub every 5 min
│   Web Server     │  ← Serves web pages
└────────┬────────┘
         │ HTTP
         ↓
┌─────────────────┐
│   Smart TVs     │  ← Pull updates every 30 sec
│  (Web Browser)  │  ← Auto-switch displays
└─────────────────┘
```

**Display Logic:**
- When `display_tournament: true` → Show tournament display with bracket QR code
- When `display_tournament: false` → Show advertising rotation
- TVs check status every 30 seconds and switch automatically

## 🔧 Configuration

### Scraper Configuration
Edit `scraper/bankshot_monitor_multi.py`:
```python
VENUE_NAME = "Bankshot Billiards"
VENUE_CITY = "Hilliard"
```

### Web Server Configuration
Edit `/etc/hosts` to change hostname:
```bash
sudo nano /etc/hostname
# Change to: bankshot-display
sudo reboot
```

### GitHub Actions Schedule
Edit `.github/workflows/scrape.yml`:
```yaml
schedule:
  - cron: '*/15 * * * *'  # Every 15 minutes
```

## 📱 Accessing Your System

Once installed, you can access:

- **Main Display**: `http://bankshot-display.local/`
- **TV Auto-Switch**: `http://bankshot-display.local/tv.html`
- **Media Manager**: `http://bankshot-display.local/media_manager.html`
- **Calcutta**: `http://bankshot-display.local/calcutta.html`
- **QR Setup**: `http://bankshot-display.local/qr_setup.php`
- **Tournament Data**: `http://bankshot-display.local/tournament_data.json`

From any device on your network!

## 🐛 Troubleshooting

### TVs can't connect to .local address
Use IP address instead:
```bash
# Find your Pi's IP address
hostname -I
# Use: http://192.168.1.XXX/tv.html
```

### No tournament data showing up
```bash
# Check GitHub Actions logs
# Visit: https://github.com/YOUR_USERNAME/bankshot-tournament-display/actions

# Check Pi logs
sudo journalctl -u web-monitor.service -f

# Manually pull data
bash scripts/pull_tournament_data.sh
```

### Display not switching
```bash
# Check web monitor
sudo systemctl status web-monitor.service

# Restart service
sudo systemctl restart web-monitor.service

# Check tournament data
cat /var/www/html/tournament_data.json | python3 -m json.tool
```

## 📝 Advanced Usage

### Business Hours Display (Optional HDMI)
If you want a Pi-connected HDMI display with business hours control:
```bash
sudo systemctl enable hdmi-display.service
sudo systemctl start hdmi-display.service
```

Edit `scripts/hdmi_display_manager.sh` to configure hours.

### Custom Payout Structure
Edit `web/payout_calculator.php` to change payout percentages.

### Multiple Locations
Clone and configure for multiple venues - each gets its own repository and Pi.

## 🤝 Contributing

Found a bug? Have a feature request? Open an issue!

## 📄 License

MIT License - Feel free to use and modify for your pool hall!

## 🎱 Credits

Built for Bankshot Billiards, Hilliard, OH
Scrapes tournament data from DigitalPool.com

---

**Need Help?** See detailed documentation in the `docs/` folder.
