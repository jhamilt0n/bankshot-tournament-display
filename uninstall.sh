#!/bin/bash
# Bankshot Tournament Display - Uninstallation Script
# Updated: Includes sepayout_updater.log cleanup

set -e  # Exit on any error

echo "=========================================="
echo "Bankshot Tournament Display - Uninstaller"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root (use sudo)"
    exit 1
fi

WEB_DIR="/var/www/html"

echo "WARNING: This will remove all Bankshot Tournament Display files!"
echo "The following will be removed:"
echo "  - All PHP and HTML files in $WEB_DIR"
echo "  - Google credentials (google-credentials.json)"
echo "  - Composer dependencies (vendor directory)"
echo "  - Cron jobs for payout updates"
echo "  - BOTH log files (payout_updater.log & sepayout_updater.log)"
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "Uninstallation cancelled."
    exit 0
fi

echo ""
echo "Step 1: Removing cron jobs..."
crontab -u www-data -l 2>/dev/null | grep -v "update_payouts.php" | crontab -u www-data - 2>/dev/null || true
crontab -u www-data -l 2>/dev/null | grep -v "specialeventpayouts.php" | crontab -u www-data - 2>/dev/null || true
echo "✓ Cron jobs removed"

echo ""
echo "Step 2: Backing up files (to /tmp/bankshot-backup)..."
BACKUP_DIR="/tmp/bankshot-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup important files if they exist
if [ -f "$WEB_DIR/google-credentials.json" ]; then
    cp "$WEB_DIR/google-credentials.json" "$BACKUP_DIR/"
fi
if [ -f "$WEB_DIR/tournament_data.json" ]; then
    cp "$WEB_DIR/tournament_data.json" "$BACKUP_DIR/"
fi
if [ -f "$WEB_DIR/payout_updater.log" ]; then
    cp "$WEB_DIR/payout_updater.log" "$BACKUP_DIR/"
fi
if [ -f "$WEB_DIR/sepayout_updater.log" ]; then
    cp "$WEB_DIR/sepayout_updater.log" "$BACKUP_DIR/"
fi

echo "✓ Backup created at: $BACKUP_DIR"

echo ""
echo "Step 3: Removing tournament display files..."

# Remove specific tournament files
rm -f "$WEB_DIR/tournament_payout_calculator.php"
rm -f "$WEB_DIR/tournament_payout_api.php"
rm -f "$WEB_DIR/update_payouts.php"
rm -f "$WEB_DIR/specialeventpayouts.php"
rm -f "$WEB_DIR/get_ip.php"
rm -f "$WEB_DIR/get_tournament_data.php"
rm -f "$WEB_DIR/index.php"
rm -f "$WEB_DIR/calcutta.html"
rm -f "$WEB_DIR/sidepot.html"
rm -f "$WEB_DIR/ads_display.html"
rm -f "$WEB_DIR/media_manager.html"
rm -f "$WEB_DIR/generate_qr.php"
rm -f "$WEB_DIR/save_media.php"
rm -f "$WEB_DIR/load_media.php"
rm -f "$WEB_DIR/upload_file.php"
rm -f "$WEB_DIR/delete_file.php"
rm -f "$WEB_DIR/tournament_data.json"
rm -f "$WEB_DIR/tournament_qr.png"
rm -f "$WEB_DIR/Bankshot_Logo.png"

# Remove media directory
rm -rf "$WEB_DIR/media"

echo "✓ Tournament files removed"

echo ""
echo "Step 4: Removing Google credentials..."
rm -f "$WEB_DIR/google-credentials.json"
echo "✓ Google credentials removed"

echo ""
echo "Step 5: Removing Composer dependencies..."
rm -rf "$WEB_DIR/vendor"
rm -f "$WEB_DIR/composer.json"
rm -f "$WEB_DIR/composer.lock"
echo "✓ Composer dependencies removed"

echo ""
echo "Step 6: Removing BOTH log files and logrotate config..."
rm -f "$WEB_DIR/payout_updater.log"
rm -f "$WEB_DIR/payout_updater.log"*.gz
rm -f "$WEB_DIR/sepayout_updater.log"
rm -f "$WEB_DIR/sepayout_updater.log"*.gz
rm -f /etc/logrotate.d/bankshot-payout
echo "✓ Both log files and rotation config removed"

echo ""
echo "Step 7: Restoring default Apache page..."
if [ ! -f "$WEB_DIR/index.html" ]; then
    cat > "$WEB_DIR/index.html" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Apache Default Page</title>
</head>
<body>
    <h1>It works!</h1>
    <p>This is the default web page for this server.</p>
</body>
</html>
EOF
    chown www-data:www-data "$WEB_DIR/index.html"
    chmod 644 "$WEB_DIR/index.html"
fi
echo "✓ Default page restored"

echo ""
echo "=========================================="
echo "Uninstallation Complete! ✓"
echo "=========================================="
echo ""
echo "All Bankshot Tournament Display files have been removed."
echo ""
echo "Backup location: $BACKUP_DIR"
echo "  - google-credentials.json"
echo "  - tournament_data.json"
echo "  - payout_updater.log"
echo "  - sepayout_updater.log"
echo ""
echo "Apache and PHP remain installed."
echo "To completely remove Apache: sudo apt-get remove --purge apache2 php libapache2-mod-php"
echo ""
