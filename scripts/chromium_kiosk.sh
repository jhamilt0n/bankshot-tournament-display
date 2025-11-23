#!/bin/bash
# Chromium Kiosk Mode Launcher for HDMI Display

# Wait for display server to be ready
sleep 5

# Disable screen blanking (ignore errors)
xset s off 2>/dev/null || true
xset -dpms 2>/dev/null || true
xset s noblank 2>/dev/null || true

# Hide cursor after 0.1 seconds of inactivity
unclutter -idle 0.1 -root 2>/dev/null &

# Launch Chromium in kiosk mode with DEDICATED PROFILE
# Use Wayland if available, fall back to X11
/usr/bin/chromium \
  --kiosk \
  --user-data-dir=/home/pi/.chromium-kiosk \
  --noerrdialogs \
  --disable-infobars \
  --no-first-run \
  --disable-features=TranslateUI \
  --disable-session-crashed-bubble \
  --disable-restore-session-state \
  --disable-background-networking \
  --disable-sync \
  --metrics-recording-only \
  --safebrowsing-disable-auto-update \
  --start-fullscreen \
  --incognito \
  --check-for-update-interval=31536000 \
  --ozone-platform=wayland \
  --enable-features=UseOzonePlatform \
  http://localhost/tv.html
