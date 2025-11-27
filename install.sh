#!/bin/bash
# Bankshot Tournament Display - Installation Script
# Fixed: Properly detects repository directory

set -e  # Exit on any error

echo "=========================================="
echo "Bankshot Tournament Display - Installer"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "ERROR: Please run as root"
    echo "Usage: sudo bash install.sh"
    exit 1
fi

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
WEB_DIR="/var/www/html"

# Check if we're in the repository directory
if [ ! -d "$SCRIPT_DIR/web" ]; then
    echo "ERROR: Cannot find 'web' directory!"
    echo "Current directory: $SCRIPT_DIR"
    echo ""
    echo "Please run this script from the repository root:"
    echo "  cd ~/bankshot-tournament-display"
    echo "  sudo bash install.sh"
    exit 1
fi

echo "✓ Repository found at: $SCRIPT_DIR"
echo ""

echo "Step 1: Installing system dependencies..."
apt-get update
apt-get install -y apache2 php libapache2-mod-php php-curl php-json php-mbstring php-xml unzip curl git

echo ""
echo "Step 2: Installing Composer (PHP package manager)..."
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    echo "✓ Composer installed successfully"
else
    echo "✓ Composer already installed"
fi

echo ""
echo "Step 3: Copying files to web directory..."
cp "$SCRIPT_DIR"/web/*.php "$WEB_DIR/"
cp "$SCRIPT_DIR"/web/*.html "$WEB_DIR/"

if [ -d "$SCRIPT_DIR/web/media" ]; then
    cp -r "$SCRIPT_DIR/web/media" "$WEB_DIR/"
fi

if ls "$SCRIPT_DIR"/web/*.png &> /dev/null; then
    cp "$SCRIPT_DIR"/web/*.png "$WEB_DIR/"
fi

echo ""
echo "Step 4: Setting up Composer dependencies..."
cd "$WEB_DIR"
if [ ! -f composer.json ]; then
    echo "Creating composer.json..."
    cat > composer.json << 'EOF'
{
    "require": {
        "google/apiclient": "^2.0"
    }
}
EOF
fi

composer install --no-dev --optimize-autoloader

echo ""
echo "Step 5: Setting file permissions..."
chown -R www-data:www-data "$WEB_DIR"
chmod 755 "$WEB_DIR"
chmod 644 "$WEB_DIR"/*.php 2>/dev/null || true
chmod 644 "$WEB_DIR"/*.html 2>/dev/null || true
chmod 755 "$WEB_DIR"/update_payouts.php
chmod 755 "$WEB_DIR"/specialeventpayouts.php

if [ -d "$WEB_DIR/media" ]; then
    chmod 755 "$WEB_DIR/media"
    chmod 644 "$WEB_DIR/media"/* 2>/dev/null || true
fi

touch "$WEB_DIR/payout_updater.log"
touch "$WEB_DIR/sepayout_updater.log"
chown www-data:www-data "$WEB_DIR/payout_updater.log"
chown www-data:www-data "$WEB_DIR/sepayout_updater.log"
chmod 664 "$WEB_DIR/payout_updater.log"
chmod 664 "$WEB_DIR/sepayout_updater.log"

echo ""
echo "Step 6: Enabling Apache modules and restarting service..."
a2enmod php8.4 2>/dev/null || a2enmod php8.2 2>/dev/null || a2enmod php8.1 2>/dev/null || a2enmod php8.0 2>/dev/null || a2enmod php7.4 2>/dev/null || echo "PHP module already enabled"
systemctl restart apache2
systemctl enable apache2

echo ""
echo "Step 7: Setting up log rotation..."
cat > /etc/logrotate.d/bankshot-payout << 'EOF'
/var/www/html/payout_updater.log {
    size 10M
    weekly
    rotate 4
    compress
    delaycompress
    missingok
    notifempty
    create 0664 www-data www-data
}

/var/www/html/sepayout_updater.log {
    size 10M
    weekly
    rotate 4
    compress
    delaycompress
    missingok
    notifempty
    create 0664 www-data www-data
}
EOF

chmod 644 /etc/logrotate.d/bankshot-payout
echo "✓ Log rotation configured"

echo ""
echo "Step 8: Setting up cron jobs..."

if [ -f "$WEB_DIR/google-credentials.json" ]; then
    crontab -u www-data -l 2>/dev/null | grep -v "update_payouts.php" | grep -v "specialeventpayouts.php" | crontab -u www-data - 2>/dev/null || true
    
    (crontab -u www-data -l 2>/dev/null; echo "* * * * * /usr/bin/php $WEB_DIR/update_payouts.php >> $WEB_DIR/payout_updater.log 2>&1") | crontab -u www-data -
    (crontab -u www-data -l 2>/dev/null; echo "* * * * * /usr/bin/php $WEB_DIR/specialeventpayouts.php >> $WEB_DIR/sepayout_updater.log 2>&1") | crontab -u www-data -
    
    echo "✓ Cron jobs installed"
else
    echo "⚠ WARNING: google-credentials.json not found!"
    echo "To enable Google Sheets integration:"
    echo "1. Follow GOOGLE_SHEETS_SETUP.md"
    echo "2. Upload google-credentials.json to $WEB_DIR/"
    echo "3. Run: cd $SCRIPT_DIR && sudo bash install.sh"
fi

echo ""
echo "Step 9: Testing Apache..."
if systemctl is-active --quiet apache2; then
    echo "✓ Apache is running"
else
    echo "✗ Apache failed - check: sudo systemctl status apache2"
    exit 1
fi

LOCAL_IP=$(hostname -I | awk '{print $1}')

echo ""
echo "=========================================="
echo "Installation Complete! ✓"
echo "=========================================="
echo ""
echo "Access: http://$LOCAL_IP/"
echo "        http://$(hostname).local/"
echo ""
