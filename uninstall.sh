#!/bin/bash
# Bankshot Tournament Display System - Uninstaller

set -e

RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${RED}"
echo "============================================================"
echo "  BANKSHOT TOURNAMENT DISPLAY SYSTEM UNINSTALLER"
echo "============================================================"
echo -e "${NC}"

if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}This script must be run as root (use sudo)${NC}" 
   exit 1
fi

ACTUAL_USER=${SUDO_USER:-$USER}
USER_HOME=$(eval echo ~$ACTUAL_USER)

read -p "Are you sure you want to uninstall? This will remove all files and configurations. (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Uninstall cancelled"
    exit 0
fi

echo ""
echo "Stopping services..."
systemctl stop tournament-monitor.service 2>/dev/null || true
systemctl stop catt-monitor.service 2>/dev/null || true
systemctl stop hdmi-display.service 2>/dev/null || true

echo "Disabling services..."
systemctl disable tournament-monitor.service 2>/dev/null || true
systemctl disable catt-monitor.service 2>/dev/null || true
systemctl disable hdmi-display.service 2>/dev/null || true

echo "Removing service files..."
rm -f /etc/systemd/system/tournament-monitor.service
rm -f /etc/systemd/system/catt-monitor.service
rm -f /etc/systemd/system/hdmi-display.service
systemctl daemon-reload

echo "Removing scripts..."
rm -f $USER_HOME/tournament_monitor.py
rm -f $USER_HOME/catt_monitor.py
rm -f $USER_HOME/hdmi_display_manager.sh

echo "Removing web files (keeping media)..."
cd /var/www/html
rm -f index.php
rm -f ads_display.html
rm -f media_manager.html
rm -f payout_calculator.php
rm -f calculate_payouts.php
rm -f get_tournament_data.php
rm -f generate_qr.php
rm -f load_media.php
rm -f save_media.php
rm -f upload_file.php
rm -f tournament_data.json
rm -f qr_code.png
rm -f cast_state.json

echo "Removing log files..."
rm -f /var/log/tournament_monitor.log
rm -f /var/log/catt_monitor.log
rm -f /var/log/hdmi_display.log

echo ""
echo -e "${YELLOW}Uninstall complete!${NC}"
echo ""
echo "Note: Media files in /var/www/html/media/ were preserved"
echo "To remove them: sudo rm -rf /var/www/html/media/"
echo ""
echo "To remove Python packages:"
echo "  pip3 uninstall requests catt"
