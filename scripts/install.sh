#!/bin/bash
# Bankshot Tournament Display System - Complete Installation
# For Raspberry Pi 4 Model B

set -e  # Exit on error

echo "=========================================="
echo "Bankshot Tournament Display Installer"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root: sudo bash scripts/install.sh"
    exit 1
fi

# Get the actual user (not root)
ACTUAL_USER="${SUDO_USER:-pi}"
USER_HOME="/home/$ACTUAL_USER"

echo "Installing for user: $ACTUAL_USER"
echo "User home: $USER_HOME"
echo ""

# Confirm installation
read -p "This will install the complete tournament display system. Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Installation cancelled"
    exit 1
fi

echo ""
echo "Step 1: Update system packages"
echo "--------------------------------------"
apt-get update
apt-get upgrade -y

echo ""
echo "Step 2: Install required packages"
echo "--------------------------------------"
apt-get install -y \
    apache2 \
    php \
    php-gd \
    php-xml \
    python3 \
    python3-pip \
    git \
    chromium \
    chromium-driver \
    avahi-daemon \
    avahi-utils \
    jq

# Install Python packages
pip3 install --break-system-packages selenium webdriver-manager

echo ""
echo "Step 3: Set up web server"
echo "--------------------------------------"

# Enable Apache modules
a2enmod rewrite
a2enmod headers

# Set up web directory
WEB_DIR="/var/www/html"
MEDIA_DIR="$WEB_DIR/media"

# Copy web files
echo "Copying web files..."
cp -r web/* $WEB_DIR/

# Create media directory
mkdir -p $MEDIA_DIR
chown -R www-data:www-data $MEDIA_DIR
chmod -R 755 $MEDIA_DIR

# Set permissions
chown -R www-data:www-data $WEB_DIR
chmod -R 755 $WEB_DIR

# Install Composer for PHP dependencies (QR code library)
if [ ! -f /usr/local/bin/composer ]; then
    echo "Installing Composer..."
    cd /tmp
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

# Install PHP dependencies
cd $WEB_DIR
if [ ! -f composer.json ]; then
    cat > composer.json << 'EOF'
{
    "require": {
        "chillerlan/php-qrcode": "^4.3"
    }
}
EOF
fi

composer install --no-dev
chown -R www-data:www-data $WEB_DIR/vendor

# Configure Apache
cat > /etc/apache2/sites-available/000-default.conf << 'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Restart Apache
systemctl restart apache2
systemctl enable apache2

echo ""
echo "Step 4: Set up mDNS hostname"
echo "--------------------------------------"

HOSTNAME="bankshot-display"
echo "$HOSTNAME" > /etc/hostname
hostnamectl set-hostname $HOSTNAME

# Configure Avahi
cat > /etc/avahi/avahi-daemon.conf << EOF
[server]
host-name=$HOSTNAME
domain-name=local
use-ipv4=yes
use-ipv6=no

[publish]
publish-addresses=yes
publish-hinfo=yes
publish-workstation=yes
publish-domain=yes
EOF

systemctl restart avahi-daemon
systemctl enable avahi-daemon

echo ""
echo "Step 5: Set up scraper and monitoring"
echo "--------------------------------------"

# Copy scraper to user home
cp scraper/bankshot_monitor_multi.py $USER_HOME/
chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/bankshot_monitor_multi.py
chmod +x $USER_HOME/bankshot_monitor_multi.py

# Create logs directory
mkdir -p $USER_HOME/logs
chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/logs

# Copy pull script
cp scripts/pull_tournament_data.sh $USER_HOME/
chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/pull_tournament_data.sh
chmod +x $USER_HOME/pull_tournament_data.sh

# Set up cron job for pulling data from GitHub
CRON_JOB="*/5 * * * * $USER_HOME/pull_tournament_data.sh > /dev/null 2>&1"
(crontab -u $ACTUAL_USER -l 2>/dev/null | grep -v "pull_tournament_data.sh"; echo "$CRON_JOB") | crontab -u $ACTUAL_USER -

echo ""
echo "Step 6: Set up systemd service"
echo "--------------------------------------"

# Copy web monitor script
cp scripts/web_monitor.py $USER_HOME/
chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/web_monitor.py
chmod +x $USER_HOME/web_monitor.py

# Install web monitor service
cp services/web-monitor.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable web-monitor.service
systemctl start web-monitor.service

echo ""
echo "Step 7: Create initial tournament data file"
echo "--------------------------------------"

cat > $WEB_DIR/tournament_data.json << 'EOF'
{
  "tournament_name": "No tournaments to display",
  "tournament_url": null,
  "venue": null,
  "date": null,
  "start_time": null,
  "status": null,
  "entry_fee": 15,
  "payout_data": null,
  "last_updated": "2024-01-01 00:00:00",
  "display_tournament": false
}
EOF

chown www-data:www-data $WEB_DIR/tournament_data.json
chmod 644 $WEB_DIR/tournament_data.json

echo ""
echo "Step 8: Optional - Set up HDMI display service"
echo "--------------------------------------"

read -p "Do you want to set up HDMI display with business hours? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    cp scripts/hdmi_display_manager.sh $USER_HOME/
    chown $ACTUAL_USER:$ACTUAL_USER $USER_HOME/hdmi_display_manager.sh
    chmod +x $USER_HOME/hdmi_display_manager.sh
    
    cp services/hdmi-display.service /etc/systemd/system/
    systemctl daemon-reload
    systemctl enable hdmi-display.service
    systemctl start hdmi-display.service
    
    echo "✓ HDMI display service installed and started"
else
    echo "○ Skipping HDMI display service"
fi

echo ""
echo "=========================================="
echo "Installation Complete!"
echo "=========================================="
echo ""
echo "✓ Web server running on port 80"
echo "✓ mDNS configured: http://bankshot-display.local"
echo "✓ Data pull scheduled every 5 minutes"
echo "✓ Web monitor service running"
echo ""
echo "Next Steps:"
echo ""
echo "1. Set up GitHub Actions:"
echo "   - Upload .github/workflows/scrape.yml to your repo"
echo "   - Upload scraper/bankshot_monitor_multi.py to your repo"
echo "   - Enable GitHub Actions in your repo settings"
echo ""
echo "2. Access your system:"
echo "   http://bankshot-display.local/media_manager.html - Manage content"
echo "   http://bankshot-display.local/qr_setup.php - Get TV setup QR codes"
echo "   http://bankshot-display.local/tv.html - Main TV page"
echo ""
echo "3. Configure your Smart TVs:"
echo "   Visit http://bankshot-display.local/tv_setup.html for instructions"
echo ""
echo "Useful Commands:"
echo "  sudo systemctl status web-monitor.service - Check monitor status"
echo "  sudo journalctl -u web-monitor.service -f - View monitor logs"
echo "  bash $USER_HOME/pull_tournament_data.sh - Manually pull data"
echo "  cat /var/www/html/tournament_data.json - View current data"
echo ""
echo "Need help? See README.md for full documentation"
echo ""
