#!/bin/bash
# Bankshot Tournament Display - Installation Script
# Updated: Added Special Event Payouts with sepayout_updater.log

set -e  # Exit on any error

echo "=========================================="
echo "Bankshot Tournament Display - Installer"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root (use sudo)"
    exit 1
fi

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
WEB_DIR="/var/www/html"

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
# Copy all PHP files
cp "$SCRIPT_DIR"/web/*.php "$WEB_DIR/"
cp "$SCRIPT_DIR"/web/*.html "$WEB_DIR/"

# Copy media directory if it exists
if [ -d "$SCRIPT_DIR/web/media" ]; then
    cp -r "$SCRIPT_DIR/web/media" "$WEB_DIR/"
fi

# Copy any image files (logo, etc)
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

# Install Google API client
echo "Installing Google API PHP client..."
composer install --no-dev --optimize-autoloader

echo ""
echo "Step 5: Setting file permissions..."
chown -R www-data:www-data "$WEB_DIR"
chmod 755 "$WEB_DIR"
chmod 644 "$WEB_DIR"/*.php 2>/dev/null || true
chmod 644 "$WEB_DIR"/*.html 2>/dev/null || true
chmod 755 "$WEB_DIR"/update_payouts.php
chmod 755 "$WEB_DIR"/specialeventpayouts.php

# Set permissions for media directory
if [ -d "$WEB_DIR/media" ]; then
    chmod 755 "$WEB_DIR/media"
    chmod 644 "$WEB_DIR/media"/* 2>/dev/null || true
fi

# Create BOTH log files with proper permissions
echo "Creating log files..."
touch "$WEB_DIR/payout_updater.log"
touch "$WEB_DIR/sepayout_updater.log"
chown www-data:www-data "$WEB_DIR/payout_updater.log"
chown www-data:www-data "$WEB_DIR/sepayout_updater.log"
chmod 664 "$WEB_DIR/payout_updater.log"
chmod 664 "$WEB_DIR/sepayout_updater.log"

echo ""
echo "Step 6: Enabling Apache modules and restarting service..."
# Try multiple PHP versions
a2enmod php8.4 2>/dev/null || a2enmod php8.2 2>/dev/null || a2enmod php8.1 2>/dev/null || a2enmod php8.0 2>/dev/null || a2enmod php7.4 2>/dev/null || echo "PHP module already enabled"
systemctl restart apache2
systemctl enable apache2

echo ""
echo "Step 7: Setting up log rotation for BOTH logs..."
# Create logrotate config for both payout logs
cat > /etc/logrotate.d/bankshot-payout << 'EOF'
# Logrotate configuration for Bankshot Tournament Payout Updaters

/var/www/html/payout_updater.log {
    size 10M
    weekly
    rotate 4
    compress
    delaycompress
    missingok
    notifempty
    create 0664 www-data www-data
    postrotate
    endscript
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
    postrotate
    endscript
}
EOF

chmod 644 /etc/logrotate.d/bankshot-payout
echo "✓ Log rotation configured for both logs (weekly, 10MB max, keep 4 weeks)"

echo ""
echo "Step 8: Setting up automatic payout updates (cron jobs)..."

# Check if google-credentials.json exists
if [ -f "$WEB_DIR/google-credentials.json" ]; then
    echo "Google credentials found. Setting up cron jobs..."
    
    # Remove any existing cron jobs
    crontab -u www-data -l 2>/dev/null | grep -v "update_payouts.php" | grep -v "specialeventpayouts.php" | crontab -u www-data - 2>/dev/null || true
    
    # Add BOTH cron jobs
    (crontab -u www-data -l 2>/dev/null; echo "# Regular tournament payouts") | crontab -u www-data -
    (crontab -u www-data -l 2>/dev/null; echo "* * * * * /usr/bin/php $WEB_DIR/update_payouts.php >> $WEB_DIR/payout_updater.log 2>&1") | crontab -u www-data -
    
    (crontab -u www-data -l 2>/dev/null; echo "# Special event payouts with added money") | crontab -u www-data -
    (crontab -u www-data -l 2>/dev/null; echo "* * * * * /usr/bin/php $WEB_DIR/specialeventpayouts.php >> $WEB_DIR/sepayout_updater.log 2>&1") | crontab -u www-data -
    
    echo "✓ Both cron jobs installed - payouts update every minute"
    echo "  - Regular tournaments: update_payouts.php → payout_updater.log"
    echo "  - Special events: specialeventpayouts.php → sepayout_updater.log"
else
    echo "⚠ WARNING: google-credentials.json not found!"
    echo ""
    echo "To enable automatic Google Sheets updates:"
    echo "1. Follow the instructions in GOOGLE_SHEETS_SETUP.md"
    echo "2. Upload your google-credentials.json to $WEB_DIR/"
    echo "3. Run: sudo bash install.sh"
    echo ""
    echo "The system will work without Google Sheets integration."
fi

echo ""
echo "Step 9: Testing Apache configuration..."
if systemctl is-active --quiet apache2; then
    echo "✓ Apache is running"
else
    echo "✗ Apache is not running - check logs with: sudo systemctl status apache2"
    exit 1
fi

# Get local IP address
LOCAL_IP=$(hostname -I | awk '{print $1}')

echo ""
echo "=========================================="
echo "Installation Complete! ✓"
echo "=========================================="
echo ""
echo "🌐 Access your tournament display at:"
echo "   http://$LOCAL_IP/"
echo "   http://$(hostname).local/"
echo ""
echo "📊 Media Manager:"
echo "   http://$LOCAL_IP/media_manager.html"
echo ""
echo "📺 Ads Display (HDMI TV):"
echo "   http://$LOCAL_IP/ads_display.html"
echo ""
echo "🎱 API Endpoints:"
echo "   http://$LOCAL_IP/tournament_payout_api.php"
echo "   http://$LOCAL_IP/get_ip.php"
echo "   http://$LOCAL_IP/get_tournament_data.php"
echo ""
echo "📋 Calcutta & Side Pot:"
echo "   http://$LOCAL_IP/calcutta.html"
echo "   http://$LOCAL_IP/sidepot.html"
echo ""
echo "📝 Log files:"
echo "   Regular tournaments: $WEB_DIR/payout_updater.log"
echo "   Special events: $WEB_DIR/sepayout_updater.log"
echo ""
if [ ! -f "$WEB_DIR/google-credentials.json" ]; then
    echo "⚠ Remember to set up Google Sheets integration!"
    echo "  See GOOGLE_SHEETS_SETUP.md for instructions"
    echo ""
fi
echo "View logs: tail -f $WEB_DIR/payout_updater.log"
echo "           tail -f $WEB_DIR/sepayout_updater.log"
echo ""
