#!/bin/bash
# Bankshot Tournament Display System - Wireless Universal Installer
# Run with: sudo ./install.sh

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${GREEN}"
echo "============================================================"
echo "  BANKSHOT WIRELESS DISPLAY SYSTEM INSTALLER"
echo "  Works with ALL Smart TV brands via web browser"
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

echo -e "${YELLOW}Installing for user: $ACTUAL_USER${NC}"
echo ""

# Prompt for GitHub repository
read -p "Enter your GitHub tournament data repository URL (or press Enter for default): " GITHUB_REPO
GITHUB_REPO=${GITHUB_REPO:-"https://github.com/jhamilt0n/tournament-scraper.git"}

# Prompt for hostname
read -p "Enter hostname for mDNS (default: bankshot-display): " HOSTNAME
HOSTNAME=${HOSTNAME:-"bankshot-display"}

echo ""
echo -e "${GREEN}Step 1: Setting up mDNS hostname...${NC}"
hostnamectl set-hostname "$HOSTNAME"
echo -e "${BLUE}✓ Hostname set to: ${HOSTNAME}.local${NC}"

echo ""
echo -e "${GREEN}Step 2: Updating system...${NC}"
apt-get update -qq

echo ""
echo -e "${GREEN}Step 3: Installing dependencies...${NC}"
apt-get install -y \
    apache2 \
    php \
    php-cli \
    php-xml \
    php-gd \
    php-mbstring \
    git \
    python3 \
    python3-pip \
    jq \
    chromium-browser \
    curl \
    unzip \
    avahi-daemon \
    avahi-utils

echo ""
echo -e "${GREEN}Step 4: Installing Python packages...${NC}"
sudo -u $ACTUAL_USER pip3 install --break-system-packages requests

# Note: CATT is NOT installed - we don't need it!
echo -e "${BLUE}✓ Skipping CATT (not needed for wireless system)${NC}"

echo ""
echo -e "${GREEN}Step 5: Installing Composer...${NC}"
if ! command -v composer &> /dev/null; then
    cd /tmp
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
else
    echo "Composer already installed"
fi

echo ""
echo -e "${GREEN}Step 6: Configuring Apache and PHP...${NC}"

# Increase PHP limits
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/g' /etc/php/*/apache2/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 100M/g' /etc/php/*/apache2/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/g' /etc/php/*/apache2/php.ini

# Set permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
usermod -a -G www-data $ACTUAL_USER

# Enable and start Apache
systemctl enable apache2
systemctl restart apache2

echo ""
echo -e "${GREEN}Step 7: Installing PHP QR Code library...${NC}"
cd /var/www/html
composer require chillerlan/php-qrcode

echo ""
echo -e "${GREEN}Step 8: Creating directory structure...${NC}"
mkdir -p /var/www/html/media
chown -R www-data:www-data /var/www/html/media
chmod -R 755 /var/www/html/media

touch /var/log/tournament_monitor.log
touch /var/log/hdmi_display.log
touch /var/log/web_monitor.log
chown $ACTUAL_USER:$ACTUAL_USER /var/log/tournament_monitor.log
chown $ACTUAL_USER:$ACTUAL_USER /var/log/hdmi_display.log
chown $ACTUAL_USER:$ACTUAL_USER /var/log/web_monitor.log

echo ""
echo -e "${GREEN}Step 9: Copying web files...${NC}"
cd $USER_HOME/bankshot-tournament-display

# Copy all web files
cp web/ads_display.html /var/www/html/
cp web/calculate_payouts.php /var/www/html/
cp web/calcutta.html /var/www/html/
cp web/generate_qr.php /var/www/html/
cp web/get_tournament_data.php /var/www/html/
cp web/index.php /var/www/html/
cp web/load_media.php /var/www/html/
cp web/media_manager.html /var/www/html/
cp web/payout_calculator.php /var/www/html/
cp web/save_media.php /var/www/html/
cp web/upload_file.php /var/www/html/
cp web/delete_file.php /var/www/html/
cp web/tv.html /var/www/html/
cp web/qr_setup.php /var/www/html/
cp web/tv_setup.html /var/www/html/

chown -R www-data:www-data /var/www/html
chmod -R 644 /var/www/html/*.php
chmod -R 644 /var/www/html/*.html

echo ""
echo -e "${GREEN}Step 10: Installing system scripts...${NC}"
cp scripts/hdmi_display_manager.sh $USER_HOME/
cp scripts/tournament_monitor.py $USER_HOME/
cp scripts/web_monitor.py $USER_HOME/

chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/hdmi_display_manager.sh
chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/tournament_monitor.py
chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/web_monitor.py
chmod +x $USER_HOME/hdmi_display_manager.sh
chmod +x $USER_HOME/tournament_monitor.py
chmod +x $USER_HOME/web_monitor.py

# Update GitHub repo URL in tournament_monitor.py
sed -i "s|GITHUB_REPO_URL = .*|GITHUB_REPO_URL = \"$GITHUB_REPO\"|g" $USER_HOME/tournament_monitor.py

echo ""
echo -e "${GREEN}Step 11: Installing systemd services...${NC}"
cp services/hdmi-display.service /etc/systemd/system/
cp services/tournament-monitor.service /etc/systemd/system/
cp services/web-monitor.service /etc/systemd/system/

# Update service files to use correct user
sed -i "s/User=pi/User=$ACTUAL_USER/g" /etc/systemd/system/tournament-monitor.service
sed -i "s/User=pi/User=$ACTUAL_USER/g" /etc/systemd/system/hdmi-display.service
sed -i "s/User=pi/User=$ACTUAL_USER/g" /etc/systemd/system/web-monitor.service

# Update home directory paths in service files
sed -i "s|/home/pi/|$USER_HOME/|g" /etc/systemd/system/tournament-monitor.service
sed -i "s|/home/pi/|$USER_HOME/|g" /etc/systemd/system/hdmi-display.service
sed -i "s|/home/pi/|$USER_HOME/|g" /etc/systemd/system/web-monitor.service

systemctl daemon-reload

systemctl enable tournament-monitor.service
systemctl enable hdmi-display.service
systemctl enable web-monitor.service

echo ""
echo -e "${GREEN}Step 12: Enabling and starting Avahi (mDNS)...${NC}"
systemctl enable avahi-daemon
systemctl restart avahi-daemon

echo ""
echo -e "${GREEN}Step 13: Starting services...${NC}"
systemctl start tournament-monitor.service
systemctl start hdmi-display.service
systemctl start web-monitor.service

# Get IP address
IP_ADDR=$(hostname -I | awk '{print $1}')

echo ""
echo -e "${GREEN}============================================================${NC}"
echo -e "${GREEN}  INSTALLATION COMPLETE!${NC}"
echo -e "${GREEN}============================================================${NC}"
echo ""
echo -e "${YELLOW}🎉 Your Wireless Display System is Ready!${NC}"
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}📡 UNIVERSAL WIRELESS ACCESS (All TV Brands):${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${GREEN}Primary URL (recommended):${NC}"
echo -e "   http://${HOSTNAME}.local/tv.html"
echo ""
echo -e "${GREEN}Fallback URL (if .local doesn't work):${NC}"
echo -e "   http://${IP_ADDR}/tv.html"
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}📺 TV SETUP (Works with ALL brands):${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "1. On your Smart TV, open the web browser"
echo "2. Navigate to: http://${HOSTNAME}.local/tv.html"
echo "3. Bookmark the page (optional but recommended)"
echo "4. Set as homepage (optional)"
echo ""
echo -e "${GREEN}Or use QR Code for easy setup:${NC}"
echo "   http://${HOSTNAME}.local/qr_setup.php"
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🎛️  WEB INTERFACES:${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "Media Manager:     http://${HOSTNAME}.local/media_manager.html"
echo "TV Setup Guide:    http://${HOSTNAME}.local/tv_setup.html"
echo "QR Code Setup:     http://${HOSTNAME}.local/qr_setup.php"
echo "Tournament View:   http://${HOSTNAME}.local/"
echo "HDMI Ads Display:  http://${HOSTNAME}.local/ads_display.html"
echo "Calcutta:          http://${HOSTNAME}.local/calcutta.html"
echo "Payout Calc:       http://${HOSTNAME}.local/payout_calculator.php"
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}🔧 SERVICE MANAGEMENT:${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "Check status:"
echo "   sudo systemctl status tournament-monitor.service"
echo "   sudo systemctl status web-monitor.service"
echo "   sudo systemctl status hdmi-display.service"
echo ""
echo "View logs:"
echo "   tail -f /var/log/tournament_monitor.log"
echo "   tail -f /var/log/web_monitor.log"
echo "   tail -f /var/log/hdmi_display.log"
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}✨ KEY FEATURES:${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "✅ Works with Samsung, LG, Sony, TCL, Vizio, Fire TV, Roku, etc."
echo "✅ Auto-switches between tournament and ads"
echo "✅ Survives IP address changes (uses mDNS)"
echo "✅ No additional hardware needed (no Chromecast)"
echo "✅ Zero human intervention after setup"
echo ""
echo -e "${GREEN}System is ready! Point your TV browsers to the URL above.${NC}"
echo ""
