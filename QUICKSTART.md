# Quick Start Guide - Bankshot Wireless Display System

**Get up and running in 15 minutes!**

---

## ⚡ Prerequisites (5 minutes)

### 1. Fresh Raspberry Pi OS Install

```bash
# Update system
sudo apt-get update
sudo apt-get upgrade -y
sudo reboot
```

### 2. Install Git

```bash
sudo apt-get install -y git
```

### 3. Get Your IP Address

```bash
hostname -I
# Write this down: _______________
```

---

## 🚀 Installation (10 minutes)

### Step 1: Clone Repository

```bash
cd ~
git clone https://github.com/jhamilt0n/bankshot-tournament-display.git
cd bankshot-tournament-display
```

### Step 2: Run Installer

```bash
chmod +x install.sh
sudo ./install.sh
```

**During installation, you'll be asked:**

```
Enter your GitHub tournament data repository URL (or press Enter for default):
```
→ Press **Enter** (uses default)

```
Enter hostname for mDNS (default: bankshot-display):
```
→ Press **Enter** (uses default)

**Wait 10-15 minutes...**

---

## 📺 Configure Your First TV (2 minutes)

### Option A: QR Code (Fastest)

1. **On your phone**, open browser and visit:
   ```
   http://bankshot-display.local/qr_setup.php
   ```

2. **On your TV**, open web browser and scan the QR code

3. **Bookmark** the page

4. ✅ **Done!**

### Option B: Manual Entry

1. **On your TV**, open web browser

2. Navigate to:
   ```
   http://bankshot-display.local/tv.html
   ```

3. **Bookmark** the page

4. ✅ **Done!**

---

## ✅ Verify It's Working

### 1. Check Services

```bash
sudo systemctl status tournament-monitor
sudo systemctl status web-monitor
sudo systemctl status hdmi-display
```

All should show **green "active (running)"**

### 2. Check Tournament Data

```bash
cat /var/www/html/tournament_data.json
```

Should show current tournament info or "No tournaments"

### 3. Test from Browser

On your phone or computer:
```
http://bankshot-display.local/
```

Should load the tournament display page

---

## 🎬 Upload Your First Media

1. Visit:
   ```
   http://bankshot-display.local/media_manager.html
   ```

2. **Click "Choose File"** or drag & drop

3. **Configure:**
   - Name: "Test Image"
   - Duration: 10 seconds
   - ☑ Display on Ads
   - ☑ Display on Tournaments

4. **Click "Add Media"**

5. **Click "Save"** at bottom

6. ✅ Your media is now in rotation!

---

## 🎯 What Happens Next?

### Automatic Behavior

1. **Tournament Monitor** checks GitHub every 60 seconds
2. **TV browsers** check status every 30 seconds
3. **When tournament starts:**
   - TVs automatically show tournament display
   - Shows bracket, player count, payouts
4. **When no tournament:**
   - TVs automatically show ads
   - Rotates your uploaded media

**Zero human intervention required!**

---

## 🔧 Quick Commands

### Check Everything

```bash
# Service status
sudo systemctl status tournament-monitor web-monitor hdmi-display

# View logs
tail -f /var/log/*monitor*.log

# Test tournament data
cat /var/www/html/tournament_data.json | jq
```

### Restart Everything

```bash
sudo systemctl restart tournament-monitor web-monitor hdmi-display
```

### View All URLs

```bash
echo "TV Display:      http://$(hostname).local/tv.html"
echo "Media Manager:   http://$(hostname).local/media_manager.html"
echo "QR Setup:        http://$(hostname).local/qr_setup.php"
echo "Setup Guide:     http://$(hostname).local/tv_setup.html"
```

---

## 📱 Supported TV Brands

Works with **ANY** Smart TV that has a web browser:

✅ Samsung Smart TV
✅ LG webOS TV
✅ Sony Android TV
✅ TCL Roku/Google TV
✅ Vizio Smart TV
✅ Amazon Fire TV (Silk browser)
✅ Roku TV (Web browser channel)
✅ Hisense Smart TV
✅ Any other TV with web browser

---

## 🆘 Troubleshooting

### Can't Access `.local` Address

**Problem:** TV shows "Cannot connect to bankshot-display.local"

**Solution:** Use IP address instead
```bash
# Get your Pi's IP
hostname -I

# Use on TV:
http://YOUR_IP_ADDRESS/tv.html
```

### Display Not Switching

**Problem:** TV stuck on ads or tournament

**Solution 1:** Check tournament data
```bash
cat /var/www/html/tournament_data.json
```
Look for: `"display_tournament": true` (tournament) or `false` (ads)

**Solution 2:** Refresh TV browser
Press reload/refresh button on TV's browser

**Solution 3:** Enable debug mode
On TV, visit: `http://bankshot-display.local/tv.html?debug=1`
You'll see status messages in top-right corner

### Service Not Running

**Problem:** Service shows "inactive" or "failed"

**Solution:**
```bash
# Restart the service
sudo systemctl restart SERVICE_NAME

# View error logs
journalctl -u SERVICE_NAME -n 50
```

---

## 📞 Need More Help?

### Documentation
- **Full Install Guide**: [INSTALL.md](INSTALL.md)
- **TV Setup Guide**: `http://bankshot-display.local/tv_setup.html`
- **README**: [README.md](README.md)

### Get Support
- **GitHub Issues**: https://github.com/jhamilt0n/bankshot-tournament-display/issues
- **View Logs**: `tail -f /var/log/web_monitor.log`

---

## ✨ Next Steps

### Add More TVs

Repeat the TV configuration for each additional TV:
1. Open TV browser
2. Go to `http://bankshot-display.local/tv.html`
3. Bookmark
4. Done!

**No limit on number of TVs!**

### Upload More Media

Visit media manager:
```
http://bankshot-display.local/media_manager.html
```

- Upload images, videos, or URLs
- Set schedules (days/times)
- Set expiration dates
- Choose where to display (ads vs tournaments)

### Configure HDMI Display (Optional)

If you have a TV connected directly via HDMI to the Pi:
- It automatically shows ads during business hours
- Edit `/home/pi/hdmi_display_manager.sh` to adjust hours
- Service runs automatically, no setup needed

---

## 🎉 You're Done!

Your wireless tournament display system is now:

✅ **Running** - All services active
✅ **Configured** - At least one TV set up
✅ **Automatic** - Auto-switches based on tournament status
✅ **Maintained** - Zero human intervention needed
✅ **Scalable** - Add unlimited TVs at no cost

---

## 📊 System Summary

```
Raspberry Pi (Web Server)
    ↓
WiFi Network
    ↓
Smart TV Browsers
    (Auto-discover: bankshot-display.local)
    (Auto-check: every 30 seconds)
    (Auto-switch: tournament ↔ ads)
```

**Total setup time:** ~15 minutes
**Cost per TV:** $0
**Human intervention:** Zero (after setup)
**Compatibility:** 100% (all Smart TVs)

---

**Welcome to the Bankshot Wireless Display System!** 🎱📺

Start adding media and watch your displays come to life! 🎬
