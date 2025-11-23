# Quick Reference Card

## 📋 Quick Start (New Installation)

```bash
# 1. Clone repository
git clone https://github.com/YOUR_USERNAME/bankshot-tournament-display.git
cd bankshot-tournament-display

# 2. Run installer
sudo bash scripts/install.sh

# 3. Enable GitHub Actions (see SETUP.md)

# 4. Configure TVs to: http://bankshot-display.local/tv.html
```

## 🔗 Important URLs

| URL | Purpose |
|-----|---------|
| `http://bankshot-display.local/` | Main display |
| `http://bankshot-display.local/tv.html` | TV auto-switch page |
| `http://bankshot-display.local/media_manager.html` | Manage content |
| `http://bankshot-display.local/qr_setup.php` | TV setup QR codes |
| `http://bankshot-display.local/tournament_data.json` | Current data (API) |

## 🛠️ Common Commands

### System Management
```bash
# Check services
sudo systemctl status web-monitor.service
sudo systemctl status apache2

# Restart services  
sudo systemctl restart web-monitor.service
sudo systemctl restart apache2

# View logs
sudo journalctl -u web-monitor.service -f
tail -f /home/pi/logs/github_pull.log
```

### Data Management
```bash
# View current tournament data
cat /var/www/html/tournament_data.json | python3 -m json.tool

# Manually pull from GitHub
bash /home/pi/pull_tournament_data.sh

# Check cron jobs
crontab -l
```

### Web Server
```bash
# Check Apache status
sudo systemctl status apache2

# View Apache logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log

# Test web server
curl http://localhost/
```

### Network
```bash
# Find Pi's IP address
hostname -I

# Test mDNS
ping -c 3 bankshot-display.local

# Check network connectivity
ip addr show
```

## 📁 Important File Locations

| File/Directory | Purpose |
|----------------|---------|
| `/var/www/html/` | Web root |
| `/var/www/html/tournament_data.json` | Current tournament data |
| `/var/www/html/media/` | Uploaded media files |
| `/var/www/html/media/media_config.json` | Media configuration |
| `/home/pi/bankshot-tournament-display/` | Repository |
| `/home/pi/logs/` | Log files |
| `/home/pi/pull_tournament_data.sh` | GitHub pull script |
| `/home/pi/web_monitor.py` | Monitor service |

## 🐛 Troubleshooting Quick Fixes

### TVs Can't Connect
```bash
# Use IP address instead
hostname -I
# Give TVs: http://192.168.1.XXX/tv.html
```

### Display Not Switching
```bash
# Check tournament data
cat /var/www/html/tournament_data.json

# Restart web monitor
sudo systemctl restart web-monitor.service

# Wait 30 seconds (polling interval)
```

### Scraper Not Working
```bash
# Check GitHub Actions logs at:
# https://github.com/YOUR_USERNAME/bankshot-tournament-display/actions

# Test locally
cd /home/pi
python3 bankshot_monitor_multi.py
```

### Media Not Showing
```bash
# Check media config
cat /var/www/html/media/media_config.json

# Fix permissions
sudo chown -R www-data:www-data /var/www/html/media/
sudo chmod -R 755 /var/www/html/media/
```

### Web Server Down
```bash
# Restart Apache
sudo systemctl restart apache2

# Check status
sudo systemctl status apache2

# Check logs
sudo tail /var/log/apache2/error.log
```

## 📊 System Status Check

Run this to check everything:
```bash
#!/bin/bash
echo "=== System Status ==="
echo "Hostname: $(hostname)"
echo "IP Address: $(hostname -I)"
echo ""
echo "=== Services ==="
sudo systemctl status apache2 | grep Active
sudo systemctl status web-monitor.service | grep Active
sudo systemctl status avahi-daemon | grep Active
echo ""
echo "=== Tournament Data ==="
if [ -f /var/www/html/tournament_data.json ]; then
    echo "✓ tournament_data.json exists"
    cat /var/www/html/tournament_data.json | jq -r '.tournament_name, .status, .display_tournament'
else
    echo "✗ tournament_data.json missing"
fi
echo ""
echo "=== Media ==="
if [ -f /var/www/html/media/media_config.json ]; then
    MEDIA_COUNT=$(cat /var/www/html/media/media_config.json | jq 'length')
    echo "✓ $MEDIA_COUNT media items configured"
else
    echo "✗ No media configured"
fi
echo ""
echo "=== Disk Space ==="
df -h / | grep -v Filesystem
echo ""
echo "=== Recent Logs ==="
echo "Last 5 web monitor entries:"
sudo journalctl -u web-monitor.service -n 5 --no-pager
```

Save as `check_status.sh`, make executable: `chmod +x check_status.sh`

## 🔄 Update Procedures

### Update System Software
```bash
sudo apt update
sudo apt upgrade -y
sudo reboot
```

### Update Repository
```bash
cd /home/pi/bankshot-tournament-display
git pull origin main
# Rerun installer if major changes
sudo bash scripts/install.sh
```

### Update Media
Visit: `http://bankshot-display.local/media_manager.html`

## 📞 Support Resources

| Resource | Location |
|----------|----------|
| Complete Setup Guide | `docs/SETUP.md` |
| Architecture Details | `docs/ARCHITECTURE.md` |
| Consolidation Guide | `docs/CONSOLIDATION.md` |
| GitHub Issues | https://github.com/YOUR_USERNAME/bankshot-tournament-display/issues |

## 💡 Tips & Best Practices

### For Reliable Operation
- Use Ethernet instead of WiFi when possible
- Keep Pi powered on 24/7
- Monitor disk space monthly
- Update system software monthly
- Test tournament detection before events

### For Better Performance  
- Use mDNS `.local` address when possible
- Compress large media files
- Limit media rotation to 10-15 items
- Set appropriate display durations (10-30 seconds)

### For Easier Maintenance
- Bookmark Media Manager URL
- Keep TV remotes accessible for browser access
- Document any custom modifications
- Save backups of media_config.json

## 🔐 Default Credentials

**System**: None by default (local network only)

**To add HTTP authentication**:
```bash
sudo htpasswd -c /etc/apache2/.htpasswd admin
# Enter password when prompted

# Add to Apache config:
sudo nano /etc/apache2/sites-available/000-default.conf
# Add:
#   AuthType Basic
#   AuthName "Restricted"
#   AuthUserFile /etc/apache2/.htpasswd
#   Require valid-user

sudo systemctl restart apache2
```

## 📈 Monitoring Dashboard

Create simple monitoring at: `/var/www/html/status.php`
```php
<?php
$data = json_decode(file_get_contents('tournament_data.json'), true);
$media = json_decode(file_get_contents('media/media_config.json'), true);
?>
<h1>System Status</h1>
<h2>Tournament</h2>
<pre><?php print_r($data); ?></pre>
<h2>Media Items</h2>
<p>Total: <?php echo count($media); ?></p>
<p>Active: <?php echo count(array_filter($media, fn($m) => $m['active'])); ?></p>
<h2>System</h2>
<p>Disk: <?php echo shell_exec('df -h / | grep -v Filesystem'); ?></p>
<p>Uptime: <?php echo shell_exec('uptime'); ?></p>
```

Visit: `http://bankshot-display.local/status.php`

---

**Keep this reference card handy for quick access to common commands and URLs!**

Print-friendly version: Add `?print` to this URL in your browser and use Print to PDF.
