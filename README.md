# Bankshot Wireless Tournament Display System

**Universal wireless display system for tournament information and advertisements. Works with ANY Smart TV brand!**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

---

## 🎯 Overview

This system provides a professional wireless display solution for pool tournaments and advertisements. It automatically switches between tournament brackets and advertising content based on real-time tournament status.

### Key Features

- ✅ **Universal Compatibility** - Works with Samsung, LG, Sony, TCL, Vizio, Fire TV, Roku, and any TV with a web browser
- ✅ **No Additional Hardware** - No Chromecast or dongles required
- ✅ **Automatic Switching** - Seamlessly switches between tournament and ad display
- ✅ **IP Change Resilient** - Uses mDNS for zero-configuration networking
- ✅ **Zero Human Intervention** - Fully automatic after initial TV setup
- ✅ **Easy Media Management** - Web-based interface for uploading and scheduling content
- ✅ **Multiple Display Support** - Run unlimited TVs from one Raspberry Pi

---

## 🚀 Quick Start

### 1. Install on Raspberry Pi

```bash
git clone https://github.com/jhamilt0n/bankshot-tournament-display.git
cd bankshot-tournament-display
chmod +x install.sh
sudo ./install.sh
```

### 2. Configure TVs

On each Smart TV:
1. Open web browser
2. Navigate to: `http://bankshot-display.local/tv.html`
3. Bookmark the page
4. Done!

**That's it!** Your displays will now automatically show tournaments when active, and ads otherwise.

---

## 📺 Supported TV Brands

This system works with **ANY** Smart TV that has a web browser:

| Brand | Browser App | Tested |
|-------|-------------|--------|
| Samsung | Internet Browser | ✅ Yes |
| LG | Web Browser | ✅ Yes |
| Sony | Chrome | ✅ Yes |
| TCL | Varies by model | ✅ Yes |
| Vizio | Internet Apps | ✅ Yes |
| Amazon Fire TV | Silk Browser | ✅ Yes |
| Roku TV | Web Browser Channel | ✅ Yes |
| Android TV | Chrome | ✅ Yes |
| Hisense | Web Browser | ⚪ Should work |
| Any other | Any browser | ⚪ Should work |

---

## 🎛️ System Components

### Raspberry Pi Server
- **Web Server**: Apache/PHP serving dynamic content
- **Tournament Monitor**: Pulls tournament data from GitHub
- **Web Monitor**: Logs display status changes
- **HDMI Display Manager**: Controls directly connected display
- **mDNS Service**: Provides `bankshot-display.local` hostname

### Web Interfaces
- **TV Display**: Auto-switching tournament/ad display
- **Media Manager**: Upload and schedule media files
- **QR Setup**: Easy TV configuration via QR code
- **Calcutta Auction**: Live auction display
- **Payout Calculator**: Tournament payout tool

---

## 📋 Requirements

### Hardware
- Raspberry Pi 4 (4GB RAM recommended) or Pi Zero 2 W
- 32GB+ microSD card
- Smart TV(s) with web browser
- WiFi network

### Software
- Raspberry Pi OS Desktop (Bookworm or Bullseye)
- Internet connection for initial setup

---

## 📖 Documentation

- **[Installation Guide](INSTALL.md)** - Complete installation instructions
- **[TV Setup Guide](web/tv_setup.html)** - Brand-specific TV configuration
- **[Troubleshooting](INSTALL.md#troubleshooting)** - Common issues and solutions

---

## 🎬 How It Works

```
1. Tournament Monitor pulls data from GitHub every 60 seconds
2. Data is stored in tournament_data.json with display flag
3. Smart TVs check this file every 30 seconds via JavaScript
4. TVs automatically switch content based on display flag
5. Zero manual intervention required
```

### Display Logic

| Tournament Status | Display Shows |
|------------------|---------------|
| In Progress | Tournament brackets, player count, payouts |
| Upcoming | Ads (waits for tournament to start) |
| Completed | Ads |
| No Tournament | Ads |

---

## 📁 Repository Structure

```
bankshot-tournament-display/
├── install.sh                      # Main installer
├── uninstall.sh                    # Uninstaller
├── INSTALL.md                      # Installation guide
├── README.md                       # This file
├── scripts/
│   ├── tournament_monitor.py       # Pulls tournament data
│   ├── web_monitor.py              # Logs display status
│   └── hdmi_display_manager.sh     # HDMI display control
├── services/
│   ├── tournament-monitor.service  # Systemd service
│   ├── web-monitor.service         # Systemd service
│   └── hdmi-display.service        # Systemd service
└── web/
    ├── tv.html                     # Main TV display
    ├── qr_setup.php                # QR code setup
    ├── tv_setup.html               # Setup instructions
    ├── index.php                   # Tournament display
    ├── ads_display.html            # Ad rotation
    ├── media_manager.html          # Media management
    ├── calcutta.html               # Calcutta auction
    └── payout_calculator.php       # Payout calculator
```

---

## 🎨 Screenshots

### Media Manager
Upload and schedule images, videos, and website URLs:
- Drag & drop file upload
- Set display duration
- Schedule by day and time
- Set expiration dates
- Choose display mode (ads vs tournaments)

### Tournament Display
Shows when tournament is active:
- QR code for bracket access
- Player count
- Entry fee
- Payouts
- Rotating media

### Ads Display
Shows when no tournament is active:
- Full-screen media rotation
- Images and videos
- Website URLs
- Scheduled content

---

## 🔧 Configuration

### Change Hostname

```bash
sudo hostnamectl set-hostname YOUR-HOSTNAME
sudo systemctl restart avahi-daemon
```

TVs will use: `http://YOUR-HOSTNAME.local/tv.html`

### Set Static IP

Edit `/etc/dhcpcd.conf`:
```
interface eth0
static ip_address=192.168.1.100/24
static routers=192.168.1.1
```

### Adjust Business Hours

Edit `~/hdmi_display_manager.sh` to change when HDMI display is active.

---

## 🐛 Troubleshooting

### TV Can't Find Server

1. **Test mDNS**: `ping bankshot-display.local`
2. **Use IP fallback**: `http://YOUR_PI_IP/tv.html`
3. **Check Avahi**: `sudo systemctl status avahi-daemon`

### Content Not Switching

1. **Check tournament data**: `cat /var/www/html/tournament_data.json`
2. **View logs**: `tail -f /var/log/web_monitor.log`
3. **Reload TV page**: Press refresh on TV browser

### Service Not Running

```bash
sudo systemctl status tournament-monitor
sudo systemctl restart tournament-monitor
journalctl -u tournament-monitor -n 50
```

See [INSTALL.md](INSTALL.md#troubleshooting) for more solutions.

---

## 🔄 Updating

```bash
cd ~/bankshot-tournament-display
git pull origin main
sudo ./install.sh
```

This will update all components while preserving your media files and settings.

---

## 🗑️ Uninstalling

```bash
cd ~/bankshot-tournament-display
sudo ./uninstall.sh
```

Removes all system components except:
- Media files (preserved)
- System packages
- mDNS hostname

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- Built for Bankshot Billiards, Hilliard, OH
- Tournament data from DigitalPool.com
- Uses [php-qrcode](https://github.com/chillerlan/php-qrcode) for QR code generation

---

## 📞 Support

- **Issues**: https://github.com/jhamilt0n/bankshot-tournament-display/issues
- **Documentation**: [INSTALL.md](INSTALL.md)

---

## 🎱 About

This system was created to provide a professional, automated display solution for pool tournaments. It eliminates the need for manual content switching and works with any TV brand via standard web technologies.

**Tested and deployed at Bankshot Billiards, Hilliard, OH** 🎱

---

Made with ❤️ for the pool community
