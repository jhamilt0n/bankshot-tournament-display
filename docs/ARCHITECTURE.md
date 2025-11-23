# System Architecture

## Overview

The Bankshot Tournament Display System is a distributed system with three main components:

```
┌──────────────────────────────────────────────────────────────┐
│                        GITHUB CLOUD                           │
│                                                                │
│  ┌────────────────────────────────────────────────────┐      │
│  │           GitHub Actions Workflow                   │      │
│  │                                                      │      │
│  │  • Runs every 15 minutes (cron schedule)            │      │
│  │  • Executes bankshot_monitor_multi.py               │      │
│  │  • Scrapes DigitalPool.com                          │      │
│  │  • Generates tournament_data.json                   │      │
│  │  • Commits to repository                            │      │
│  └────────────────────────────────────────────────────┘      │
│                          ↓                                     │
│                    tournament_data.json                        │
└──────────────────────────────────────────────────────────────┘
                            ↓
                    (Git pull every 5 min)
                            ↓
┌──────────────────────────────────────────────────────────────┐
│                    RASPBERRY PI 4                             │
│                                                                │
│  ┌────────────────────────────────────────────────────┐      │
│  │              Cron Job (every 5 min)                 │      │
│  │  • Runs pull_tournament_data.sh                     │      │
│  │  • Pulls latest from GitHub                         │      │
│  │  • Copies tournament_data.json to /var/www/html     │      │
│  └────────────────────────────────────────────────────┘      │
│                                                                │
│  ┌────────────────────────────────────────────────────┐      │
│  │           Apache Web Server (Port 80)               │      │
│  │  • Serves static files (HTML, JS, CSS, media)       │      │
│  │  • Executes PHP scripts                             │      │
│  │  • Provides REST APIs                               │      │
│  │  • tournament_data.json (public endpoint)           │      │
│  │  • media_config.json (uploaded content)             │      │
│  └────────────────────────────────────────────────────┘      │
│                                                                │
│  ┌────────────────────────────────────────────────────┐      │
│  │      Web Monitor Service (systemd)                  │      │
│  │  • Monitors tournament_data.json                    │      │
│  │  • Logs status changes                              │      │
│  │  • Runs continuously                                │      │
│  └────────────────────────────────────────────────────┘      │
│                                                                │
│  ┌────────────────────────────────────────────────────┐      │
│  │           mDNS Service (Avahi)                      │      │
│  │  • Broadcasts: bankshot-display.local               │      │
│  │  • Enables zero-configuration networking            │      │
│  └────────────────────────────────────────────────────┘      │
└──────────────────────────────────────────────────────────────┘
                            ↑
                    (HTTP requests)
                            ↑
┌──────────────────────────────────────────────────────────────┐
│                      SMART TVs                                │
│                                                                │
│  ┌────────────────────────────────────────────────────┐      │
│  │            Web Browser (tv.html)                    │      │
│  │                                                      │      │
│  │  • Loads in iframe: index.php OR ads_display.html   │      │
│  │  • Polls server every 30 seconds                    │      │
│  │  • Checks tournament_data.json                      │      │
│  │  • Switches display based on display_tournament     │      │
│  │  • Auto-refreshes if needed                         │      │
│  └────────────────────────────────────────────────────┘      │
└──────────────────────────────────────────────────────────────┘
```

## Component Details

### 1. GitHub Actions (Cloud Scraper)

**File**: `.github/workflows/scrape.yml`

**Purpose**: Scrapes DigitalPool.com to detect active tournaments

**Schedule**: Every 15 minutes via cron expression

**Process**:
1. Sets up Ubuntu environment
2. Installs Chrome and ChromeDriver
3. Installs Python + Selenium
4. Runs `bankshot_monitor_multi.py`
5. Commits `tournament_data.json` if changed
6. Pushes to repository

**Key Features**:
- Runs in GitHub's cloud (no local resources)
- Uses headless Chrome for scraping
- Handles multiple tournaments per day
- Smart priority logic for which tournament to display
- Continues showing tournament after midnight if still in progress

### 2. Raspberry Pi 4 (Server)

#### 2a. Data Synchronization

**File**: `scripts/pull_tournament_data.sh`

**Trigger**: Cron job every 5 minutes

**Process**:
1. `cd /home/pi/bankshot-tournament-display`
2. `git pull origin main`
3. Copy `tournament_data.json` to `/var/www/html/`
4. Copy to backup location
5. Log activity

**Why every 5 minutes?**
- GitHub Actions runs every 15 min
- 5 min ensures data is fresh
- Low resource usage

#### 2b. Web Server (Apache + PHP)

**Services**:
- Static file serving (HTML, CSS, JS, images, videos)
- PHP execution for dynamic content
- REST API endpoints

**Key Endpoints**:
| Endpoint | Purpose |
|----------|---------|
| `/` or `/index.php` | Main tournament display (with ads rotation) |
| `/ads_display.html` | Ads-only display |
| `/tv.html` | Auto-switching TV page |
| `/media_manager.html` | Content management UI |
| `/tournament_data.json` | Current tournament status (public API) |
| `/get_tournament_data.php` | Tournament data with CORS headers |
| `/load_media.php` | Media configuration |
| `/save_media.php` | Save media configuration |
| `/upload_file.php` | File upload handler |
| `/calculate_payouts.php` | Payout calculation |
| `/generate_qr.php` | QR code generation |
| `/qr_setup.php` | TV setup QR codes |
| `/tv_setup.html` | TV setup instructions |
| `/calcutta.html` | Calcutta auction display |

#### 2c. Web Monitor Service

**File**: `scripts/web_monitor.py`

**Service**: `systemd` service that runs continuously

**Purpose**:
- Monitors `tournament_data.json` for changes
- Logs state transitions
- Helps with debugging

**Not critical** - system works without it, but provides useful logging

#### 2d. mDNS (Zero-Configuration Networking)

**Service**: Avahi daemon

**Purpose**: Broadcasts `bankshot-display.local` hostname

**Why?**
- TVs can use `.local` address instead of IP
- Works even if DHCP assigns new IP
- More reliable for long-term deployment

**Fallback**: If TV doesn't support mDNS, use IP address directly

### 3. Smart TVs (Clients)

#### Primary Page: tv.html

**Features**:
- **Auto-discovery**: Tries multiple methods to find Pi
  1. Current hostname (if loaded from Pi)
  2. mDNS: `bankshot-display.local`
  3. Saved URL from localStorage
  
- **Auto-switching**: 
  - Polls `/get_tournament_data.php` every 30 seconds
  - Loads `index.php` if `display_tournament: true`
  - Loads `ads_display.html` if `display_tournament: false`
  
- **Resilience**:
  - Reconnects if server goes offline
  - Re-discovers server if connection lost
  - Keeps screen awake (prevents TV sleep)

#### Display Pages

**index.php** (Tournament Display):
- Left sidebar: QR code, player count, payouts
- Right side: Media rotation (images/videos/URLs)
- Dynamic payout calculation based on players
- Auto-hides sidebar when player count = 0
- Polls every 30 seconds for player count updates
- Reloads if tournament ends or major state change

**ads_display.html** (Ads Only):
- Full-screen media rotation
- No tournament information
- Same media system as index.php
- Schedule-aware (day/time filtering)

## Data Flow

### Tournament Detection Flow

```
User registers for tournament on DigitalPool.com
    ↓
GitHub Actions scraper runs (every 15 min)
    ↓
Selenium scrapes tournament page
    ↓
Parser extracts: name, date, time, status, players
    ↓
Smart logic determines which tournament to display
    ↓
tournament_data.json generated with display_tournament flag
    ↓
Committed and pushed to GitHub
    ↓
Pi pulls data (every 5 min via cron)
    ↓
tournament_data.json copied to /var/www/html/
    ↓
TVs poll /get_tournament_data.php (every 30 sec)
    ↓
TV sees display_tournament: true
    ↓
TV switches from ads_display.html to index.php
    ↓
index.php polls for player count updates (every 30 sec)
    ↓
Sidebar shows/hides based on player count
    ↓
Tournament ends (100% complete or midnight passes)
    ↓
Scraper sets display_tournament: false
    ↓
TVs switch back to ads_display.html
```

### Media Management Flow

```
User visits /media_manager.html
    ↓
Uploads image/video OR adds URL
    ↓
Files saved to /var/www/html/media/
    ↓
Configuration saved to media_config.json
    ↓
User configures: duration, schedule, display mode
    ↓
User drags to reorder (sets order property)
    ↓
Configuration saved to media_config.json
    ↓
Display pages load /load_media.php
    ↓
Filter by: active, displayOnAds/displayOnTournaments
    ↓
Filter by: current day/time schedule
    ↓
Sort by: order property
    ↓
Create iframe for each item
    ↓
Rotate with fade transitions
    ↓
Refresh URLs on each cycle
```

## Key Design Decisions

### Why GitHub Actions for Scraping?

**Pros**:
- Runs in cloud (no Pi resources used)
- Reliable (GitHub's infrastructure)
- Free for public repos
- Easy to view logs
- Version controlled

**Cons**:
- 15-minute minimum interval
- Rate limits (but we're well under them)

**Alternative Considered**: Pi-based scraper
- Would require more Pi resources
- Would need more complex error handling
- Would be harder to debug remotely

### Why Web-Based TVs Instead of Chromecast?

**Pros**:
- No dongles needed (uses built-in TV browser)
- More reliable (no wireless casting issues)
- Simpler setup (just a bookmark)
- Works with any TV with browser
- Auto-recovery if connection lost

**Cons**:
- Requires manual TV setup initially
- Some very old TVs might not have browsers

**Alternative Considered**: Chromecast
- Original system used CATT casting
- Had reliability issues
- Required Pi to maintain connection
- Complicated error recovery

### Why PHP Instead of Node.js/Python?

**Pros**:
- Pre-installed on most Linux distros
- Simple deployment (just copy files)
- No process manager needed
- Mature web server integration
- Fast for simple operations

**Cons**:
- Less modern than Node.js
- Smaller ecosystem than Python

**Decision**: PHP is sufficient for our needs and simplifies deployment

### Why Pull-Based Instead of Push-Based?

TVs poll the server instead of server pushing to TVs.

**Pros**:
- Works with any TV (no special client needed)
- No persistent connections to maintain
- Simple to implement
- Auto-recovery (next poll succeeds)
- Scales to unlimited TVs

**Cons**:
- 30-second delay before switching
- Slightly more bandwidth (but negligible)

**Decision**: Polling is simpler and more reliable for this use case

## Resource Usage

### Raspberry Pi 4

**CPU**: < 5% average
- Web server: 1-2%
- Cron jobs: < 1% (only runs briefly)
- Web monitor: < 1%

**Memory**: ~400MB / 2GB
- Apache: 100MB
- PHP: 50MB
- Python services: 30MB
- OS: 200MB

**Disk**: ~2GB total
- OS: 1GB
- Web files: 100MB
- Media: varies (500MB - 5GB typical)

**Network**: < 1MB/day
- Git pulls: 50KB per pull
- TV connections: minimal (JSON responses)
- Media served locally (doesn't count)

### GitHub Actions

**Compute**: ~5 minutes/month
- 15 min/month of run time
- Well under free tier limits

**Storage**: < 1MB
- Just tournament_data.json

## Security Considerations

### Current Security Posture

**What's Protected**:
- System runs on private network
- No authentication (intended for internal use)
- Media upload restricted to local network

**What's NOT Protected**:
- No HTTPS (would require certificates)
- No authentication on media manager
- Anyone on network can access

**Risk Level**: **LOW**
- System is on private network
- Only displays public information (tournament brackets)
- No sensitive data stored

### Recommendations for Enhanced Security

If deploying on public network:

1. **Add HTTP Basic Auth**:
   ```apache
   <Directory /var/www/html>
       AuthType Basic
       AuthName "Restricted"
       AuthUserFile /etc/apache2/.htpasswd
       Require valid-user
   </Directory>
   ```

2. **Add HTTPS with Let's Encrypt**:
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache
   ```

3. **Firewall**: Block external access
   ```bash
   sudo ufw enable
   sudo ufw allow from 192.168.1.0/24 to any port 80
   ```

## Scaling Considerations

### Current Limitations

- **Single location**: Designed for one venue
- **Single Pi**: No redundancy
- **Local network only**: TVs must be on same network

### How to Scale

**Multiple Locations**:
- Deploy one Pi per location
- Each Pi pulls from same GitHub repo
- Each Pi filters for its venue name
- Requires modifying scraper to handle multiple venues

**High Availability**:
- Add second Pi with same setup
- Use DNS load balancing
- TVs will auto-failover to working Pi

**Internet-Accessible**:
- Forward port 80 on router
- Use HTTPS with real domain
- Add authentication
- Consider CDN for media files

## Monitoring & Maintenance

### What to Monitor

1. **GitHub Actions**: Check workflow runs succeed
2. **Pi Uptime**: Ensure Pi is online
3. **Web Server**: Apache running?
4. **Data Freshness**: tournament_data.json updating?
5. **Disk Space**: Media folder growing?

### Maintenance Tasks

**Daily**:
- Quick visual check that TVs are displaying

**Weekly**:
- Check GitHub Actions logs
- Verify tournament detection working

**Monthly**:
- Update Pi OS: `sudo apt update && apt upgrade`
- Review disk space: `df -h`
- Check web logs: `sudo tail /var/log/apache2/access.log`

**Annually**:
- Backup media files
- Review and clean old media
- Test full system recovery

## Troubleshooting Guide

### TV Not Connecting

1. Check Pi is on: `ping bankshot-display.local`
2. Check web server: `curl http://bankshot-display.local/`
3. Try IP address instead of .local
4. Check TV browser supports JavaScript

### Display Not Switching

1. Check tournament data: `cat /var/www/html/tournament_data.json`
2. Verify `display_tournament` flag
3. Wait 30 seconds (polling interval)
4. Check TV browser console for errors

### Scraper Not Finding Tournaments

1. Check GitHub Actions logs
2. Verify DigitalPool.com is accessible
3. Check search term ("Bankshot Billiards")
4. Test locally: `python3 bankshot_monitor_multi.py`

### Media Not Displaying

1. Check media_config.json exists
2. Verify file permissions: `ls -la /var/www/html/media/`
3. Check browser console for 404 errors
4. Verify media files uploaded correctly

## Future Enhancements

### Potential Improvements

1. **Mobile App**: Native iOS/Android app for TV remote control
2. **Statistics**: Track player counts, tournament frequency
3. **Multiple Venues**: Support for chain of pool halls
4. **Live Scoring**: Real-time match updates during tournaments
5. **Social Integration**: Auto-post tournament results
6. **Admin Dashboard**: Central control panel for all features
7. **Cloud Hosting**: Move from Pi to cloud server
8. **API**: Public API for third-party integrations

### Community Contributions Welcome

This is open source! Contributions welcome for:
- Better TV auto-discovery
- Enhanced media scheduling
- Mobile apps
- Additional display themes
- Multi-venue support
- Documentation improvements

---

Last Updated: 2024-01-01
Version: 1.0
