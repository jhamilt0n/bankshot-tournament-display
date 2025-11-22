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

read -p "Are you sure you want to uninstall? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Uninstall cancelled"
    exit 0
fi

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
rm -f index.php ads_display.html media_manager.html
rm -f payout_calculator.php calculate_payouts.php
rm -f get_tournament_data.php generate_qr.php
rm -f load_media.php save_media.php upload_file.php
rm -f tournament_data.json qr_code.png cast_state.json

echo "Removing log files..."
rm -f /var/log/tournament_monitor.log
rm -f /var/log/catt_monitor.log
rm -f /var/log/hdmi_display.log

echo ""
echo -e "${YELLOW}Uninstall complete!${NC}"
echo ""
echo "Media files preserved in /var/www/html/media/"
echo "To remove media: sudo rm -rf /var/www/html/media/"
echo ""
echo "To remove Python packages:"
echo "  pip3 uninstall requests catt"
