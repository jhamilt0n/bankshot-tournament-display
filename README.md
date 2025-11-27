# Bankshot Tournament Display System

A comprehensive dual-display tournament management system for pool halls featuring automatic tournament detection, Chromecast casting, HDMI display management, and media rotation.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## 🎯 Features

- ✅ **Automatic Tournament Detection** - Scrapes Digital Pool every 15 minutes via GitHub Actions
- ✅ **Dual Display Support** - Chromecast for tournament info + HDMI TV for advertisements
- ✅ **Real-time Updates** - Player count, payouts, and bracket updates every 60 seconds
- ✅ **QR Code Generation** - Mobile access to tournament brackets
- ✅ **Business Hours Scheduling** - Automatic display on/off based on hours
- ✅ **Media Management** - Web-based upload and scheduling interface
- ✅ **Separate Channels** - Independent ad and tournament media rotation
- ✅ **Smart Casting Logic** - Only casts when tournament has players
- ✅ **Auto-Start Terminal** - Monitor window opens on boot for easy system status viewing
- ✅ **Special Event Support** - Added money tournament payouts with separate log file

## 📋 Requirements

### Hardware
- Raspberry Pi 4 (4GB+ RAM recommended)
- Chromecast device (any generation)
- TV with HDMI connection
- Network connection (WiFi or Ethernet)

### Software
- Debian 13 (Trixie) or Raspberry Pi OS
- Apache 2.4+
- PHP 8.4+
- Python 3.11+
- Chromium browser

## 🚀 Quick Start

### Automated Installation (Recommended)

**Note:** The installer must be run with root privileges to install system packages and configure services. Use `sudo` to run it:

```bash
# Clone the repository
git clone https://github.com/jhamilt0n/bankshot-tournament-display.git
cd bankshot-tournament-display

# Run the installer with sudo
sudo bash install.sh
```

Or download and run directly from GitHub:

```bash
curl -sSL https://raw.githubusercontent.com/jhamilt0n/bankshot-tournament-display/main/install.sh -o install.sh
chmod +x install.sh
sudo bash install.sh
```

The installer will:
- Install all required packages (Apache, PHP, Composer, etc.)
- Configure Apache and PHP
- Deploy web files and scripts
- Set up systemd services
- Configure permissions
- Configure terminal auto-start on boot
- Set up BOTH payout log files (regular + special events)
- Start all services automatically

### One-Line Installation

```bash
curl -sSL https://raw.githubusercontent.com/jhamilt0n/bankshot-tournament-display/main/install.sh | sudo bash
```

## 🖥️ System Architecture

```
┌────────────────────────────────────────────────────────────────┐
│  GitHub Repository (Tournament Data Source)                    │
│  • Scraper runs every 15 minutes via GitHub Actions            │
│  • Updates tournament_data.json in repo                         │
└───────────────────┬────────────────────────────────────────────┘
                    │ (pulls every 60s)
                    ▼
┌────────────────────────────────────────────────────────────────┐
│  Raspberry Pi (Tournament Display System)                      │
├────────────────────────────────────────────────────────────────┤
│  • Tournament Monitor (Python) - Pulls from GitHub             │
│  • CATT Monitor (Python) - Controls Chromecast                 │
│  • HDMI Display Manager (Bash) - Business hours control        │
│  • Apache + PHP - Web interface & APIs                         │
│  • Media Manager - Upload & schedule content                   │
│  • Pi API (get_ip.php) - IP discovery endpoint                 │
│                                                                 │
│  Payout Systems (Google Sheets Integration):                   │
│  • update_payouts.php → payout_updater.log                     │
│  • specialeventpayouts.php → sepayout_updater.log              │
└─────┬──────────────────────────────────────────┬───────────────┘
      │                                          │
      ▼                                          ▼
┌─────────────────────┐                 ┌─────────────────────┐
│  Chromecast TV      │                 │  HDMI TV            │
├─────────────────────┤                 ├─────────────────────┤
│  Shows:             │                 │  Shows:             │
│  • Tournament info  │                 │  • Ads only         │
│  • Bracket          │                 │  • Never tournament │
│  • Tournament media │                 │  • Business hours   │
└─────────────────────┘                 └─────────────────────┘
```

## 📱 Web Interfaces

After installation, access these interfaces (replace `YOUR_PI_IP` with your Pi's IP address):

- **Tournament Display** (Main): `http://YOUR_PI_IP/`
- **Ads Display** (HDMI TV): `http://YOUR_PI_IP/ads_display.html`
- **Media Manager**: `http://YOUR_PI_IP/media_manager.html`
- **Tournament Data API**: `http://YOUR_PI_IP/get_tournament_data.php`
- **Pi IP Discovery API**: `http://YOUR_PI_IP/get_ip.php`
- **Payout API**: `http://YOUR_PI_IP/tournament_payout_api.php`
- **Calcutta Display**: `http://YOUR_PI_IP/calcutta.html`
- **Side Pot Display**: `http://YOUR_PI_IP/sidepot.html`

## ⚙️ Configuration

### Business Hours (HDMI Display)

Edit `/home/pi/hdmi_display_manager.sh`:

- **Sunday**: 12pm - Monday 1am
- **Monday**: 3pm - Tuesday 1am
- **Tuesday-Thursday**: 12pm - 1am
- **Friday**: 12pm - Saturday 2:30am
- **Saturday**: 12pm - Sunday 2:30am

```bash
# After editing
sudo systemctl restart hdmi-display
```

### Chromecast Configuration

```bash
# Scan for Chromecast devices
catt scan

# If you have multiple Chromecasts, edit the monitor:
nano /home/pi/catt_monitor.py
# Change line 14 to:
# CATT_COMMAND = '/home/pi/.local/bin/catt -d "Your Chromecast Name"'

# Restart service
sudo systemctl restart catt-monitor
```

### Media Management

1. Open `http://YOUR_PI_IP/media_manager.html`
2. Upload images (JPG, PNG, GIF, WEBP) or videos (MP4, WEBM, MOV, AVI)
3. Set **Display Type**:
   - `Ads` - Shows only on HDMI TV
   - `Tournaments` - Shows only on Chromecast with tournament info
4. Configure schedule (days/times) and duration
5. Set active/inactive status

### Google Sheets Integration

The system supports automatic payout calculations with Google Sheets:

**Two Independent Payout Systems:**
1. **Regular Tournaments** - `update_payouts.php` → `payout_updater.log`
2. **Special Events** - `specialeventpayouts.php` → `sepayout_updater.log`

Both run via cron every minute, completely separate.

See `GOOGLE_SHEETS_SETUP.md` for setup instructions.

## 🔧 Service Management

### Check Service Status

```bash
sudo systemctl status tournament-monitor
sudo systemctl status catt-monitor
sudo systemctl status hdmi-display
```

### View Logs

```bash
# Tournament monitor
tail -f /home/pi/logs/tournament_monitor.log

# CATT monitor
tail -f /var/log/catt_monitor.log

# HDMI display
tail -f /var/log/hdmi_display.log

# Regular payout updater
tail -f /var/www/html/payout_updater.log

# Special event payout updater
tail -f /var/www/html/sepayout_updater.log
```

### Restart Services

```bash
sudo systemctl restart tournament-monitor
sudo systemctl restart catt-monitor
sudo systemctl restart hdmi-display
```

## 🪟 Windows Deployment

For deploying from Windows via PowerShell:

```powershell
# Copy files using SCP
scp *.html pi@YOUR_PI_IP:/tmp/
scp *.php pi@YOUR_PI_IP:/tmp/
scp *.py pi@YOUR_PI_IP:/tmp/
scp *.service pi@YOUR_PI_IP:/tmp/

# SSH into Raspberry Pi
ssh pi@YOUR_PI_IP

# Run installation
sudo bash install.sh
```

## 📊 Log Management

Both payout log files are automatically rotated:
- **Size**: Rotates when reaching 10MB
- **Frequency**: Weekly rotation
- **Retention**: Keeps 4 weeks of compressed logs
- **Location**: `/etc/logrotate.d/bankshot-payout`

View log status:
```bash
# Check rotation status
cat /var/lib/logrotate/status | grep bankshot

# Force rotation (testing)
sudo logrotate -f /etc/logrotate.d/bankshot-payout
```

## 🐛 Troubleshooting

### Services Not Starting

```bash
# Check service logs
sudo journalctl -u tournament-monitor -n 50
sudo journalctl -u catt-monitor -n 50
sudo journalctl -u hdmi-display -n 50
```

### Chromecast Not Found

```bash
# Verify network connectivity
ping 8.8.8.8

# Scan for devices
catt scan

# Check CATT version
catt --version
```

### Tournament Data Not Updating

```bash
# Check tournament monitor log
tail -50 /home/pi/logs/tournament_monitor.log

# Verify GitHub access
cd /tmp/tournament-scraper
git pull
```

### Payout Updates Not Working

```bash
# Check both payout logs
tail -50 /var/www/html/payout_updater.log
tail -50 /var/www/html/sepayout_updater.log

# Verify cron jobs
crontab -u www-data -l

# Test Google Sheets connection
php /var/www/html/update_payouts.php
php /var/www/html/specialeventpayouts.php
```

### Permission Issues

```bash
# Fix web directory permissions
sudo chown -R www-data:www-data /var/www/html/
sudo chmod 664 /var/www/html/tournament_data.json
sudo chmod 664 /var/www/html/tournament_qr.png
sudo chmod 664 /var/www/html/payout_updater.log
sudo chmod 664 /var/www/html/sepayout_updater.log
```

### Terminal Not Auto-Starting

```bash
# Check your desktop session type
echo $DESKTOP_SESSION

# For Wayland (labwc) - use desktop entry:
mkdir -p /home/pi/.config/autostart
cat > /home/pi/.config/autostart/bankshot-terminal.desktop << 'EOF'
[Desktop Entry]
Type=Application
Name=Bankshot Monitor Terminal
Exec=lxterminal --title="Bankshot Monitor" --geometry=120x30
Terminal=false
X-GNOME-Autostart-enabled=true
EOF

# For X11 (LXDE-pi) - use autostart file:
mkdir -p /home/pi/.config/lxsession/LXDE-pi/
cat > /home/pi/.config/lxsession/LXDE-pi/autostart << 'EOF'
@xset s off
@xset -dpms
@xset s noblank
@unclutter -idle 0.1 -root
@lxterminal --title="Bankshot Monitor" --geometry=120x30
EOF

# Reboot to test
sudo reboot
```

See [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md) for more solutions.

## 📚 Documentation

- **[Installation Guide](docs/INSTALLATION.md)** - Detailed installation instructions
- **[Configuration Guide](docs/CONFIGURATION.md)** - Customize your setup
- **[Troubleshooting Guide](docs/TROUBLESHOOTING.md)** - Common issues and solutions
- **[API Documentation](docs/API.md)** - API endpoints and usage
- **[Google Sheets Setup](GOOGLE_SHEETS_SETUP.md)** - Payout automation setup

## 🗂️ File Structure

```
bankshot-tournament-display/
├── install.sh                          # Installation script
├── uninstall.sh                        # Uninstallation script
├── .github/workflows/scrape.yml        # GitHub Actions scraper
├── web/                                # Web interface files
│   ├── index.php                       # Main tournament display
│   ├── ads_display.html                # HDMI TV ad display
│   ├── media_manager.html              # Media upload interface
│   ├── get_ip.php                      # Pi IP discovery API
│   ├── tournament_payout_api.php       # Payout calculation API
│   ├── update_payouts.php              # Regular tournament payouts
│   ├── specialeventpayouts.php         # Special event payouts
│   ├── calcutta.html                   # Calcutta display
│   ├── sidepot.html                    # Side pot display
│   └── media/                          # Uploaded media files
├── scripts/                            # System scripts
│   ├── tournament_monitor.py           # GitHub repo monitor
│   ├── catt_monitor.py                 # Chromecast controller
│   └── hdmi_display_manager.sh         # HDMI business hours
├── scraper/                            # Tournament scraper
│   └── bankshot_monitor_multi.py       # Multi-tournament scraper
├── services/                           # Systemd services
│   ├── tournament-monitor.service
│   ├── catt-monitor.service
│   └── hdmi-display.service
├── tournament_data.json                # Current tournament data
└── bankshot-payout-logrotate           # Log rotation config
```

## 🔄 How It Works

### Data Flow

1. **GitHub Actions** (every 15 minutes)
   - Scrapes digitalpool.com for Bankshot tournaments
   - Updates `tournament_data.json`
   - Commits to repository

2. **Tournament Monitor** (every 60 seconds)
   - Pulls latest data from GitHub
   - Saves to `/var/www/html/tournament_data.json`
   - Generates QR code for tournament URL

3. **CATT Monitor** (continuous)
   - Reads tournament data
   - Casts to Chromecast when:
     - Tournament has players (player_count > 0)
     - Status is "In Progress" or "Upcoming"
   - Stops casting when tournament ends

4. **HDMI Display Manager** (continuous)
   - Checks business hours
   - Starts/stops Chromium in kiosk mode
   - Displays ads during business hours

5. **Payout Updaters** (every 60 seconds)
   - Read Google Sheets data
   - Calculate tournament payouts
   - Write results back to sheets
   - Log to separate files

### Display Logic

- **HDMI TV**: Shows media with `displayOnAds === true`
- **Chromecast**: Shows tournament info + media with `displayOnTournaments === true`

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Created for Bankshot Billiards, Hilliard, OH
- Tournament data sourced from [Digital Pool](https://digitalpool.com)
- Built with open-source tools and libraries

## 📞 Support

For issues, questions, or suggestions:
- Open an [Issue](https://github.com/jhamilt0n/bankshot-tournament-display/issues)
- Check [Troubleshooting Guide](docs/TROUBLESHOOTING.md)
- Review [Documentation](docs/)

## 🎱 About Bankshot Billiards

Bankshot Billiards is a premier pool hall in Hilliard, Ohio, hosting weekly tournaments and special events. This system was custom-built to enhance the tournament experience for players and spectators.

---

**Made with ❤️ for the pool community**
