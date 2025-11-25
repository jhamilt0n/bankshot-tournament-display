#!/bin/bash
# Business Hours Display Manager for HDMI TV
# Manages Chromium browser for ad display during business hours

DISPLAY_URL="http://localhost/ads_display.html"
TOURNAMENT_DATA="/var/www/html/tournament_data.json"
LOG_FILE="/var/log/hdmi_display.log"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

is_business_hours() {
    local day=$(date +%u)
    local hour=$(date +%H | sed 's/^0//')
    local minute=$(date +%M | sed 's/^0//')
    local current_minutes=$((hour * 60 + minute))
    
    case $day in
        7) [ $current_minutes -ge 720 ] && return 0 ;;
        1) [ $current_minutes -lt 60 ] || [ $current_minutes -ge 900 ] && return 0 ;;
        2|3|4) [ $current_minutes -lt 60 ] || [ $current_minutes -ge 720 ] && return 0 ;;
        5) [ $current_minutes -lt 60 ] || [ $current_minutes -ge 720 ] && return 0 ;;
        6) [ $current_minutes -lt 150 ] || [ $current_minutes -ge 720 ] && return 0 ;;
    esac
    return 1
}

is_chromium_running() {
    pgrep chromium > /dev/null
}

start_chromium() {
    log "Starting Chromium in fullscreen mode"
    
    export XAUTHORITY=/home/pi/.Xauthority
    xhost +local: 2>/dev/null
    
    # Hide mouse cursor
    pkill unclutter 2>/dev/null
    DISPLAY=:0 unclutter -idle 0 -root &
    
    rm -rf /home/pi/.config/chromium/Singleton* 2>/dev/null
    
    # Disable crash recovery bubble
    sed -i 's/"exited_cleanly":false/"exited_cleanly":true/' ~/.config/chromium/Default/Preferences 2>/dev/null
    sed -i 's/"exit_type":"Crashed"/"exit_type":"Normal"/' ~/.config/chromium/Default/Preferences 2>/dev/null
    
    sleep 1
    
    # Start Chromium in fullscreen (disable GPU to prevent flashing)
    DISPLAY=:0 chromium \
        --start-fullscreen \
        --password-store=basic \
        --disable-gpu \
        --disable-software-rasterizer \
        --noerrdialogs \
        --disable-infobars \
        --no-first-run \
        --disable-session-crashed-bubble \
        --disable-restore-session-state \
        --disable-popup-blocking \
        "$DISPLAY_URL" > /dev/null 2>&1 &
    
    sleep 3
    
    # Force fullscreen with F11
    DISPLAY=:0 xdotool key F11 2>/dev/null
    sleep 1
    DISPLAY=:0 xdotool key F11 2>/dev/null
    
    log "Chromium started in fullscreen"
}

stop_chromium() {
    log "Stopping Chromium"
    pkill -f chromium
    pkill unclutter
    sleep 1
}

# Main loop
log "=== HDMI Display Manager Starting ==="
log "Business hours mode"

while true; do
    if is_business_hours; then
        if ! is_chromium_running; then
            start_chromium
        fi
    else
        if is_chromium_running; then
            log "Outside business hours - stopping display"
            stop_chromium
        fi
    fi
    sleep 60
done
