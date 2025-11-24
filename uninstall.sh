#!/bin/bash
# Bankshot Tournament Display System - Uninstallation Script
# Version 2.0

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${RED}========================================"
echo "Bankshot Tournament Display System"
echo "Uninstallation Script v2.0"
echo -e "========================================${NC}"
echo ""

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    echo -e "${RED}Please do not run this script as root or with sudo${NC}"
    echo "Run as: ./uninstall.sh"
    exit 1
fi

# Confirmation prompt
echo -e "${YELLOW}WARNING: This will remove all Bankshot Tournament Display files and services.${NC}"
echo ""
read -p "Are you sure you want to continue? (yes/no): " -r
echo ""
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Uninstallation cancelled."
    exit 0
fi

# Function to print status
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Stop services
echo "Stopping services..."
sudo systemctl stop tournament-monitor.service 2>/dev/null || true
sudo systemctl stop catt-monitor.service 2>/dev/null || true
sudo systemctl stop hdmi-display.service 2>/dev/null || true
print_status "Services stopped"
echo ""

# Disable services
echo "Disabling services..."
sudo systemctl disable tournament-monitor.service 2>/dev/null || true
sudo systemctl disable catt-monitor.service 2>/dev/null || true
sudo systemctl disable hdmi-display.service 2>/dev/null || true
print_status "Services disabled"
echo ""

# Remove service files
echo "Removing service files..."
sudo rm -f /etc/systemd/system/tournament-monitor.service
sudo rm -f /etc/systemd/system/catt-monitor.service
sudo rm -f /etc/systemd/system/hdmi-display.service
sudo systemctl daemon-reload
print_status "Service files removed"
echo ""

# Remove Python scripts
echo "Removing Python scripts..."
rm -f /home/pi/tournament_monitor.py
rm -f /home/pi/catt_monitor.py
rm -f /home/pi/hdmi_display_manager.sh
print_status "Python scripts removed"
echo ""

# Ask about web files
read -p "Remove web files from /var/www/html/? (yes/no): " -r
echo ""
if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Removing web files..."
    sudo rm -f /var/www/html/ads_display.html
    sudo rm -f /var/www/html/index.php
    sudo rm -f /var/www/html/calcutta.html
    sudo rm -f /var/www/html/media_manager.html
    sudo rm -f /var/www/html/payout_calculator.php
    sudo rm -f /var/www/html/delete_file.php
    sudo rm -f /var/www/html/load_media.php
    sudo rm -f /var/www/html/save_media.php
    sudo rm -f /var/www/html/upload_file.php
    sudo rm -f /var/www/html/get_tournament_data.php
    sudo rm -f /var/www/html/generate_qr.php
    sudo rm -f /var/www/html/calculate_payouts.php
    sudo rm -f /var/www/html/Bankshot_Logo.png
    sudo rm -f /var/www/html/tournament_data.json
    sudo rm -f /var/www/html/tournament_qr.png
    print_status "Web files removed"
else
    print_info "Web files kept"
fi
echo ""

# Ask about media files
read -p "Remove uploaded media files from /var/www/html/media/? (yes/no): " -r
echo ""
if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Removing media files..."
    sudo rm -rf /var/www/html/media/
    print_status "Media files removed"
else
    print_info "Media files kept"
fi
echo ""

# Ask about log files
read -p "Remove log files? (yes/no): " -r
echo ""
if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Removing log files..."
    sudo rm -f /var/log/catt_monitor.log
    sudo rm -f /var/log/hdmi_display.log
    rm -rf /home/pi/logs/tournament_monitor.log
    rmdir /home/pi/logs 2>/dev/null || true
    print_status "Log files removed"
else
    print_info "Log files kept"
fi
echo ""

# Remove sudoers entry
echo "Removing sudoers entry..."
sudo sed -i '/generate_qr.php/d' /etc/sudoers 2>/dev/null || true
print_status "Sudoers entry removed"
echo ""

# Remove hostname entry
echo "Removing hostname entry..."
sudo sed -i '/bankshot-display/d' /etc/hosts 2>/dev/null || true
print_status "Hostname entry removed"
echo ""

# Ask about CATT
read -p "Uninstall CATT (Chromecast controller)? (yes/no): " -r
echo ""
if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo "Uninstalling CATT..."
    pip3 uninstall -y catt 2>/dev/null || true
    print_status "CATT uninstalled"
else
    print_info "CATT kept"
fi
echo ""

# Remove GitHub clone
echo "Removing GitHub repository clone..."
rm -rf /tmp/tournament-scraper 2>/dev/null || true
rm -rf /tmp/bankshot-tournament-display 2>/dev/null || true
print_status "Repository clone removed"
echo ""

# Restore PHP config
echo "Restoring PHP configuration..."
PHP_INI="/etc/php/8.4/apache2/php.ini"
if [ -f "${PHP_INI}.backup" ]; then
    sudo mv "${PHP_INI}.backup" "$PHP_INI"
    sudo systemctl restart apache2
    print_status "PHP configuration restored"
else
    print_info "No PHP backup found"
fi
echo ""

echo -e "${GREEN}========================================"
echo "Uninstallation Complete!"
echo -e "========================================${NC}"
echo ""
echo -e "${BLUE}Summary:${NC}"
echo "  ✓ All services stopped and removed"
echo "  ✓ System files cleaned up"
echo ""
echo "The system packages (Apache, PHP, etc.) were NOT removed."
echo "To remove them manually, run:"
echo "  sudo apt remove apache2 php chromium"
echo ""
