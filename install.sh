#!/bin/bash
# Bankshot Tournament Display System - Installation Script
# Version 2.1 - Auto-update with logging and rotation

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
REPO_URL="https://github.com/jhamilt0n/bankshot-tournament-display.git"
INSTALL_DIR="/home/pi"
WEB_DIR="/var/www/html"
LOG_DIR="/home/pi/logs"
MEDIA_DIR="$WEB_DIR/media"

echo -e "${BLUE}========================================"
echo "Bankshot Tournament Display System"
echo "Installation Script v2.1"
echo -e "========================================${NC}"
echo ""

# Function definitions (must be before use)
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    echo -e "${RED}Please do not run this script as root or with sudo${NC}"
    echo "Run as: ./install.sh"
    exit 1
fi

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo -e "${RED}Git is not installed. Installing git...${NC}"
    sudo apt-get update
    sudo apt-get install -y git
fi

# Clone or update repository
echo "Fetching latest files from GitHub..."
TEMP_REPO="/tmp/bankshot-tournament-display"

if [ -d "$TEMP_REPO" ]; then
    echo "Removing old temporary repository..."
    rm -rf "$TEMP_REPO"
fi

echo "Cloning repository..."
git clone "$REPO_URL" "$TEMP_REPO"
if [ $? -ne 0 ]; then
    print_error "Failed to clone repository"
    exit 1
fi
print_status "Repository cloned successfully"
echo ""

# Check internet connectivity
echo "Checking internet connectivity..."
if ! ping -c 1 8.8.8.8 &> /dev/null; then
    print_error "No internet connection. Please connect to the internet and try again."
    exit 1
fi
print_status "Internet connection OK"
echo ""

# Update system
echo "Updating system packages..."
sudo apt update
print_status "System packages updated"
echo ""

# Install required packages
echo "Installing required packages..."
sudo apt install -y \
    apache2 \
    php \
    php-gd \
    php-curl \
    git \
    chromium \
    python3-pip \
    jq \
    unclutter \
    xdotool \
    lxterminal

print_status "Required packages installed"
echo ""

# Install CATT
echo "Installing CATT (Chromecast controller)..."
pip3 install --break-system-packages catt
print_status "CATT installed"
echo ""

# Configure Apache
echo "Configuring Apache web server..."
sudo a2enmod rewrite
sudo systemctl enable apache2
sudo systemctl start apache2
print_status "Apache configured"
echo ""

# Create directories
echo "Creating directories..."
mkdir -p "$LOG_DIR"
sudo mkdir -p "$MEDIA_DIR"
sudo chown -R www-data:www-data "$WEB_DIR"
sudo chmod -R 755 "$WEB_DIR"
sudo chown -R www-data:www-data "$MEDIA_DIR"
print_status "Directories created"
echo ""

# Configure PHP
echo "Configuring PHP settings..."
PHP_INI="/etc/php/8.4/apache2/php.ini"
if [ -f "$PHP_INI" ]; then
    sudo cp "$PHP_INI" "${PHP_INI}.backup"
    sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' "$PHP_INI"
    sudo sed -i 's/post_max_size = .*/post_max_size = 50M/' "$PHP_INI"
    sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
    print_status "PHP configured"
else
    print_warning "PHP config file not found at $PHP_INI"
fi
echo ""

# Deploy web files
echo "Deploying web files..."
sudo cp "$TEMP_REPO/web/"*.html "$WEB_DIR/" 2>/dev/null || true
sudo cp "$TEMP_REPO/web/"*.php "$WEB_DIR/" 2>/dev/null || true
sudo chown -R www-data:www-data "$WEB_DIR"
print_status "Web files deployed"
echo ""

# Deploy scripts
echo "Deploying scripts..."
cp "$TEMP_REPO/scripts/tournament_monitor.py" "$INSTALL_DIR/"
cp "$TEMP_REPO/scripts/catt_monitor.py" "$INSTALL_DIR/"
cp "$TEMP_REPO/scripts/hdmi_display_manager.sh" "$INSTALL_DIR/"
chmod +x "$INSTALL_DIR/"*.py
chmod +x "$INSTALL_DIR/"*.sh
print_status "Scripts deployed"
echo ""

# Deploy service files
echo "Deploying service files..."
sudo cp "$TEMP_REPO/services/tournament-monitor.service" /etc/systemd/system/
sudo cp "$TEMP_REPO/services/catt-monitor.service" /etc/systemd/system/
sudo cp "$TEMP_REPO/services/hdmi-display.service" /etc/systemd/system/
sudo systemctl daemon-reload
print_status "Service files deployed"
echo ""

# Deploy assets
echo "Deploying assets..."
if [ -f "$TEMP_REPO/assets/Bankshot_Logo.png" ]; then
    sudo cp "$TEMP_REPO/assets/Bankshot_Logo.png" "$WEB_DIR/"
    print_status "Assets deployed"
else
    print_warning "Logo file not found, skipping"
fi
echo ""

# Clean up temporary repository
echo "Cleaning up..."
rm -rf "$TEMP_REPO"
print_status "Temporary files cleaned"
echo ""

# Create tournament data file
echo "Creating tournament data file..."
sudo touch "$WEB_DIR/tournament_data.json"
sudo chown pi:www-data "$WEB_DIR/tournament_data.json"
sudo chmod 664 "$WEB_DIR/tournament_data.json"
print_status "Tournament data file created"
echo ""

# Create QR code file
echo "Creating QR code file..."
sudo touch "$WEB_DIR/tournament_qr.png"
sudo chown www-data:www-data "$WEB_DIR/tournament_qr.png"
sudo chmod 664 "$WEB_DIR/tournament_qr.png"
print_status "QR code file created"
echo ""

# Create log files
echo "Creating log files..."
sudo touch /var/log/catt_monitor.log
sudo touch /var/log/hdmi_display.log
sudo chown pi:pi /var/log/catt_monitor.log
sudo chown pi:pi /var/log/hdmi_display.log
print_status "Log files created"
echo ""

# Configure sudoers for QR generation
echo "Configuring sudoers for QR generation..."
SUDOERS_LINE="pi ALL=(www-data) NOPASSWD: /usr/bin/php $WEB_DIR/generate_qr.php"
if ! sudo grep -q "generate_qr.php" /etc/sudoers; then
    echo "$SUDOERS_LINE" | sudo tee -a /etc/sudoers > /dev/null
    print_status "Sudoers configured"
else
    print_info "Sudoers already configured"
fi
echo ""

# Fix hostname resolution
echo "Fixing hostname resolution..."
if ! grep -q "127.0.1.1 bankshot-display" /etc/hosts; then
    echo "127.0.1.1 bankshot-display" | sudo tee -a /etc/hosts > /dev/null
    print_status "Hostname configured"
else
    print_info "Hostname already configured"
fi
echo ""

# Restart Apache
echo "Restarting Apache..."
sudo systemctl restart apache2
print_status "Apache restarted"
echo ""

# Configure terminal auto-start (works for both X11 and Wayland)
echo "Configuring terminal auto-start..."

# Create LXDE autostart (for X11)
mkdir -p /home/pi/.config/lxsession/LXDE-pi/
cat > /home/pi/.config/lxsession/LXDE-pi/autostart << 'EOF'
@xset s off
@xset -dpms
@xset s noblank
@unclutter -idle 0.1 -root
@lxterminal --title="Bankshot Monitor" --geometry=120x30
EOF

# Create desktop entry (for Wayland/labwc and modern systems)
mkdir -p /home/pi/.config/autostart
cat > /home/pi/.config/autostart/bankshot-terminal.desktop << 'EOF'
[Desktop Entry]
Type=Application
Name=Bankshot Monitor Terminal
Exec=lxterminal --title="Bankshot Monitor" --geometry=120x30
Terminal=false
X-GNOME-Autostart-enabled=true
EOF

print_status "Terminal auto-start configured (X11 and Wayland)"
echo ""

# Enable services
echo "Enabling services..."
sudo systemctl enable tournament-monitor.service
sudo systemctl enable catt-monitor.service
sudo systemctl enable hdmi-display.service
print_status "Services enabled"
echo ""

# Restart services (stops and starts, whether running or not)
echo "Restarting services..."
sudo systemctl restart tournament-monitor.service
sudo systemctl restart catt-monitor.service
sudo systemctl restart hdmi-display.service
print_status "Services restarted with new files"
echo ""

# Setup automatic system updates with logging and rotation
echo "Setting up automatic system updates..."
CRON_JOB="0 4 * * * /usr/bin/apt update && /usr/bin/apt full-upgrade -y && /usr/bin/apt clean && /usr/bin/apt autoremove -y >> /home/pi/logs/auto_update.log 2>&1 && find /home/pi/logs -name 'auto_update.log' -mtime +30 -delete"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "/usr/bin/apt update && /usr/bin/apt full-upgrade"; then
    # Remove old cron job without logging
    crontab -l 2>/dev/null | grep -v "/usr/bin/apt update && /usr/bin/apt full-upgrade" | crontab -
    print_info "Removed old auto-update cron job"
fi

# Add new cron job with logging
(crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
print_status "Auto-update cron job added (runs daily at 4 AM with logging and 30-day rotation)"
echo ""

# Get IP address
IP_ADDR=$(hostname -I | awk '{print $1}')

# Final cleanup
echo "Final cleanup..."
if [ -d "$TEMP_REPO" ]; then
    rm -rf "$TEMP_REPO"
fi
print_status "Cleanup complete"
echo ""

# Print summary
echo -e "${GREEN}========================================"
echo "Installation Complete!"
echo -e "========================================${NC}"
echo ""
echo -e "${BLUE}System Information:${NC}"
echo "  IP Address: $IP_ADDR"
echo "  Hostname: $(hostname)"
echo ""
echo -e "${BLUE}Web Interfaces:${NC}"
echo "  Ads Display (HDMI):      http://$IP_ADDR/ads_display.html"
echo "  Tournament Display:      http://$IP_ADDR/index.php"
echo "  Media Manager:           http://$IP_ADDR/media_manager.html"
echo ""
echo -e "${BLUE}Service Status:${NC}"
systemctl is-active tournament-monitor.service && echo -e "  Tournament Monitor: ${GREEN}Running${NC}" || echo -e "  Tournament Monitor: ${RED}Stopped${NC}"
systemctl is-active catt-monitor.service && echo -e "  CATT Monitor:       ${GREEN}Running${NC}" || echo -e "  CATT Monitor:       ${RED}Stopped${NC}"
systemctl is-active hdmi-display.service && echo -e "  HDMI Display:       ${GREEN}Running${NC}" || echo -e "  HDMI Display:       ${RED}Stopped${NC}"
echo ""
echo -e "${BLUE}Auto-Update:${NC}"
echo "  System updates run daily at 4:00 AM"
echo "  Logs: /home/pi/logs/auto_update.log (30-day rotation)"
echo "  View cron job: crontab -l"
echo "  View log: tail -f /home/pi/logs/auto_update.log"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo "  1. Scan for Chromecast: catt scan"
echo "  2. Upload media at: http://$IP_ADDR/media_manager.html"
echo "  3. Check logs: tail -f /home/pi/logs/tournament_monitor.log"
echo ""
echo -e "${BLUE}Useful Commands:${NC}"
echo "  View logs:          tail -f /home/pi/logs/tournament_monitor.log"
echo "  Check services:     sudo systemctl status tournament-monitor"
echo "  Restart services:   sudo systemctl restart tournament-monitor"
echo "  View update log:    tail -f /home/pi/logs/auto_update.log"
echo "  Uninstall:          ./uninstall.sh"
echo ""
echo -e "${YELLOW}Note:${NC} You can safely delete this installer script:"
echo "  rm $0"
echo ""
