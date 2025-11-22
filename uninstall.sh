#!/bin/bash
# Bankshot Tournament Display System - Uninstaller
# Run with: sudo ./uninstall.sh

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${RED}"
echo "============================================================"
echo "  BANKSHOT TOURNAMENT DISPLAY SYSTEM UNINSTALLER"
echo "============================================================"
echo -e "${NC}"

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root (use sudo)${NC}" 
   exit 1
fi

# Get the actual user (not root)
ACTUAL_USER=${SUDO_USER:-$USER}
USER_HOME=$(eval echo ~$ACTUAL_USER)

echo -e "${YELLOW}Uninstalling for user: $ACTUAL_USER${NC}"
echo ""
echo -e "${YELLOW}WARNING: This will remove all Bankshot Tournament Display System components.${NC}"
echo -e "${YELLOW}Media files in /var/www/html/media/ will be preserved.${NC}"
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [[ "$CONFIRM" != "yes" ]]; then
    echo -e "${GREEN}Uninstall cancelled.${NC}"
    exit 0
fi

echo ""
echo -e "${GREEN}Step 1: Stopping services...${NC}"
systemctl stop tournament-monitor.service 2>/dev/null || echo "tournament-monitor.service not running"
systemctl stop catt-monitor.service 2>/dev/null || echo "catt-monitor.service not running"
systemctl stop hdmi-display.service 2>/dev/null || echo "hdmi-display.service not running"

echo ""
echo -e "${GREEN}Step 2: Disabling services...${NC}"
systemctl disable tournament-monitor.service 2>/dev/null || echo "tournament-monitor.service not enabled"
systemctl disable catt-monitor.service 2>/dev/null || echo "catt-monitor.service not enabled"
systemctl disable hdmi-display.service 2>/dev/null || echo "hdmi-display.service not enabled"

echo ""
echo -e "${GREEN}Step 3: Removing service files...${NC}"
rm -f /etc/systemd/system/tournament-monitor.service
rm -f /etc/systemd/system/catt-monitor.service
rm -f /etc/systemd/system/hdmi-display.service
systemctl daemon-reload

echo ""
echo -e "${GREEN}Step 4: Removing system scripts...${NC}"
rm -f $USER_HOME/catt_monitor.py
rm -f $USER_HOME/hdmi_display_manager.sh
rm -f $USER_HOME/tournament_monitor.py

echo ""
echo -e "${GREEN}Step 5: Removing web files...${NC}"
rm -f /var/www/html/ads_display.html
rm -f /var/www/html/calculate_payouts.php
rm -f /var/www/html/calcutta.html
rm -f /var/www/html/generate_qr.php
rm -f /var/www/html/get_tournament_data.php
rm -f /var/www/html/index.php
rm -f /var/www/html/load_media.php
rm -f /var/www/html/media_manager.html
rm -f /var/www/html/payout_calculator.php
rm -f /var/www/html/save_media.php
rm -f /var/www/html/upload_file.php
rm -f /var/www/html/tournament_data.json

echo ""
echo -e "${GREEN}Step 6: Removing log files...${NC}"
rm -f /var/log/tournament_monitor.log
rm -f /var/log/catt_monitor.log
rm -f /var/log/hdmi_display.log

echo ""
echo -e "${GREEN}Step 7: Removing Composer packages from web directory...${NC}"
rm -rf /var/www/html/vendor
rm -f /var/www/html/composer.json
rm -f /var/www/html/composer.lock

echo ""
echo -e "${YELLOW}Note: The following items were NOT removed:${NC}"
echo "  - Apache2 web server (system package)"
echo "  - PHP and extensions (system packages)"
echo "  - Python packages (catt, requests)"
echo "  - Composer (system-wide installation)"
echo "  - Media files in /var/www/html/media/"
echo ""
echo "To remove media files manually, run:"
echo "  sudo rm -rf /var/www/html/media/"
echo ""
echo "To remove Python packages, run:"
echo "  pip3 uninstall catt requests"
echo ""
echo "To remove system packages, run:"
echo "  sudo apt-get remove apache2 php php-cli php-xml php-gd php-mbstring chromium"
echo "  sudo apt-get autoremove"
echo ""

echo -e "${GREEN}============================================================${NC}"
echo -e "${GREEN}  UNINSTALLATION COMPLETE!${NC}"
echo -e "${GREEN}============================================================${NC}"
echo ""
echo -e "${GREEN}Bankshot Tournament Display System has been removed.${NC}"
