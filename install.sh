#!/bin/bash
# Bankshot Tournament Display System - Complete Installer
# Run with: sudo ./install.sh

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}"
echo "============================================================"
echo "  BANKSHOT TOURNAMENT DISPLAY SYSTEM INSTALLER"
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

echo ""
echo -e "${GREEN}Step 1: Updating system...${NC}"
apt-get update

echo ""
echo -e "${GREEN}Step 2: Installing dependencies...${NC}"
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
    chromium \
    curl \
    unzip

echo ""
echo -e "${GREEN}Step 3: Installing Python packages...${NC}"
sudo -u $ACTUAL_USER pip3 install --break-system-packages requests catt

echo ""
echo -e "${GREEN}Step 4: Installing Composer...${NC}"
if ! command -v composer &> /dev/null; then
    cd /tmp
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
else
    echo "Composer already installed"
fi

echo ""
echo -e "${GREEN}Step 5: Configuring Apache and PHP...${NC}"

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
echo -e "${GREEN}Step 6: Installing PHP QR Code library...${NC}"
cd /var/www/html
composer require chillerlan/php-qrcode

echo ""
echo -e "${GREEN}Step 7: Creating directory structure...${NC}"
mkdir -p /var/www/html/media
chown -R www-data:www-data /var/www/html/media
chmod -R 755 /var/www/html/media

touch /var/log/tournament_monitor.log
touch /var/log/catt_monitor.log
touch /var/log/hdmi_display.log
chown $ACTUAL_USER:$ACTUAL_USER /var/log/tournament_monitor.log
chown $ACTUAL_USER:$ACTUAL_USER /var/log/catt_monitor.log
chown $ACTUAL_USER:$ACTUAL_USER /var/log/hdmi_display.log
chmod 666 /var/log/catt_monitor.log

echo ""
echo -e "${GREEN}Step 8: Copying web files...${NC}"
cd $USER_HOME/bankshot-tournament-display

# Copy web files
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

chown -R www-data:www-data /var/www/html
chmod -R 644 /var/www/html/*.php
chmod -R 644 /var/www/html/*.html

echo ""
echo -e "${GREEN}Step 9: Installing system scripts...${NC}"
cp catt_monitor.py $USER_HOME/
cp hdmi_display_manager.sh $USER_HOME/
cp tournament_monitor.py $USER_HOME/

chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/catt_monitor.py
chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/hdmi_display_manager.sh
chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/tournament_monitor.py
chmod +x $USER_HOME/catt_monitor.py
chmod +x $USER_HOME/hdmi_display_manager.sh
chmod +x $USER_HOME/tournament_monitor.py

# Update GitHub repo URL in tournament_monitor.py
sed -i "s|GITHUB_REPO_URL = .*|GITHUB_REPO_URL = \"$GITHUB_REPO\"|g" $USER_HOME/tournament_monitor.py

echo ""
echo -e "${GREEN}Step 10: Installing systemd services...${NC}"
cp services/catt-monitor.service /etc/systemd/system/
cp services/hdmi-display.service /etc/systemd/system/
cp services/tournament-monitor.service /etc/systemd/system/

# Update service files to use correct user
sed -i "s/User=pi/User=$ACTUAL_USER/g" /etc/systemd/system/tournament-monitor.service
sed -i "s/User=pi/User=$ACTUAL_USER/g" /etc/systemd/system/catt-monitor.service
sed -i "s/User=pi/User=$ACTUAL_USER/g" /etc/systemd/system/hdmi-display.service

# Update home directory paths in service files
sed -i "s|/home/pi/|$USER_HOME/|g" /etc/systemd/system/tournament-monitor.service
sed -i "s|/home/pi/|$USER_HOME/|g" /etc/systemd/system/catt-monitor.service
sed -i "s|/home/pi/|$USER_HOME/|g" /etc/systemd/system/hdmi-display.service

systemctl daemon-reload

systemctl enable tournament-monitor.service
systemctl enable catt-monitor.service
systemctl enable hdmi-display.service

echo ""
echo -e "${GREEN}Step 11: Starting services...${NC}"
systemctl start tournament-monitor.service
systemctl start catt-monitor.service
systemctl start hdmi-display.service

echo ""
echo -e "${GREEN}============================================================${NC}"
echo -e "${GREEN}  INSTALLATION COMPLETE!${NC}"
echo -e "${GREEN}============================================================${NC}"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo ""
echo "1. Upload media files:"
echo "   http://$(hostname -I | awk '{print $1}')/media_manager.html"
echo ""
echo "2. View displays:"
echo "   Main Display: http://$(hostname -I | awk '{print $1}')/"
echo "   HDMI Display: http://$(hostname -I | awk '{print $1}')/ads_display.html"
echo "   Calcutta: http://$(hostname -I | awk '{print $1}')/calcutta.html"
echo "   Payout Calculator: http://$(hostname -I | awk '{print $1}')/payout_calculator.php"
echo "   Chromecast: Will auto-cast when tournament starts"
echo ""
echo "3. Check service status:"
echo "   sudo systemctl status tournament-monitor.service"
echo "   sudo systemctl status catt-monitor.service"
echo "   sudo systemctl status hdmi-display.service"
echo ""
echo "4. View logs:"
echo "   tail -f /var/log/tournament_monitor.log"
echo "   tail -f /var/log/catt_monitor.log"
echo "   tail -f /var/log/hdmi_display.log"
echo ""
echo -e "${GREEN}System is ready!${NC}"
