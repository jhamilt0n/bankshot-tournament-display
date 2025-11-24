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

The installer automatically pulls the latest files from GitHub:

```bash
# Download and run the installer
curl -sSL https://raw.githubusercontent.com/jhamilt0n/bankshot-tournament-display/main/install.sh -o install.sh
chmod +x install.sh
./install.sh
```

Or if you've already cloned the repository:

```bash
cd bankshot-tournament-display
chmod +x install.sh
./install.sh
```

The installer will:
- Pull latest files from GitHub repository
- Install all required packages
- Configure Apache and PHP
- Deploy web files and scripts
- Set up systemd services
- Configure permissions
- Configure terminal auto-start on boot
- Start all services automatically

### One-Line Installation

```bash
curl -sSL https://raw.githubusercontent.com/jhamilt0n/bankshot-tournament-display/main/install.sh | bash
```

This command downloads and runs the installer, which automatically pulls all files from GitHub.

## 🖥️ System Architecture

```
┌────────────────────────────────────────────────────────────────┐
│  GitHub Repository (Tournament Data Source)                    │
│  Scraper runs every 15 minutes via GitHub Actions              │
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

After installation, access these interfaces (replace `YOUR_PI_IP`):

- **Ads Display** (HDMI TV): `http://YOUR_PI_IP/ads_display.html`
- **Tournament Display** (Chromecast): `http://YOUR_PI_IP/index.php`
- **Media Manager**: `http://YOUR_PI_IP/media_manager.html`
- **Tournament Data API**: `http://YOUR_PI_IP/get_tournament_data.php`

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
   - `ad` - Shows only on HDMI TV
   - `tournament` - Shows only on Chromecast with tournament info
4. Configure schedule (days/times) and duration
5. Set active/inactive status

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

# Run installation commands
./install.sh
```

See [docs/INSTALLATION.md](docs/INSTALLATION.md) for detailed Windows deployment instructions.

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

### Permission Issues

```bash
# Fix web directory permissions
sudo chown -R www-data:www-data /var/www/html/
sudo chmod 664 /var/www/html/tournament_data.json
sudo chmod 664 /var/www/html/tournament_qr.png
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

## 🗂️ File Structure

```
bankshot-tournament-display/
├── install.sh                    # Installation script
├── uninstall.sh                  # Uninstallation script
├── web/                          # Web interface files
├── scripts/                      # System scripts
├── services/                     # Systemd services
├── assets/                       # Static assets
├── docs/                         # Documentation
└── examples/                     # Example configurations
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

### Display Logic

- **HDMI TV**: Shows media with `displayType === "ad"`
- **Chromecast**: Shows tournament info + media with `displayType === "tournament"`

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
