# System Diagram

## Complete System Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          GITHUB.COM (Cloud)                              │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │  Repository: bankshot-tournament-display                             │ │
│ │                                                                       │ │
│ │  📁 .github/workflows/scrape.yml                                     │ │
│ │      └── Cron: */15 * * * * (every 15 minutes)                      │ │
│ │      └── Ubuntu runner with Chrome + Python + Selenium               │ │
│ │      └── Runs: bankshot_monitor_multi.py                            │ │
│ │      └── Scrapes: digitalpool.com                                    │ │
│ │      └── Outputs: tournament_data.json                              │ │
│ │      └── Commits & Pushes to repo                                   │ │
│ │                                                                       │ │
│ │  📄 tournament_data.json (updated every 15 min if tournament found) │ │
│ │      {                                                               │ │
│ │        "tournament_name": "Sunday 9-Ball",                          │ │
│ │        "status": "In Progress",                                     │ │
│ │        "display_tournament": true,  ← KEY FLAG                     │ │
│ │        "player_count": 24,                                          │ │
│ │        ...                                                           │ │
│ │      }                                                               │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↓
                            git pull (every 5 min)
                                    ↓
┌─────────────────────────────────────────────────────────────────────────┐
│                    RASPBERRY PI 4 MODEL B                                │
│   Hostname: bankshot-display.local                                       │
│   IP: 192.168.1.XXX                                                      │
│                                                                           │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │  🕐 CRON JOB (runs as user 'pi')                                    │ │
│ │     */5 * * * * /home/pi/pull_tournament_data.sh                    │ │
│ │                                                                       │ │
│ │     📜 pull_tournament_data.sh:                                      │ │
│ │        1. cd /home/pi/bankshot-tournament-display                   │ │
│ │        2. git pull origin main                                       │ │
│ │        3. cp tournament_data.json /var/www/html/                    │ │
│ │        4. cp tournament_data.json /home/pi/                          │ │
│ │        5. Log activity to /home/pi/logs/github_pull.log             │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │  🌐 APACHE WEB SERVER (port 80)                                     │ │
│ │                                                                       │ │
│ │  Document Root: /var/www/html/                                       │ │
│ │                                                                       │ │
│ │  📄 Files:                                                           │ │
│ │     • index.php ..................... Tournament display + ads       │ │
│ │     • ads_display.html .............. Ads-only display              │ │
│ │     • tv.html ...................... Smart auto-switch page         │ │
│ │     • media_manager.html ........... Content management UI          │ │
│ │     • tournament_data.json ......... Current tournament status      │ │
│ │     • media/media_config.json ...... Media configuration            │ │
│ │     • media/*.jpg, *.mp4 ........... Uploaded media files           │ │
│ │                                                                       │ │
│ │  🔌 PHP Endpoints:                                                   │ │
│ │     • GET  /tournament_data.json .... Raw tournament data           │ │
│ │     • GET  /get_tournament_data.php . Tournament data with CORS     │ │
│ │     • GET  /load_media.php .......... Load media config             │ │
│ │     • POST /save_media.php .......... Save media config             │ │
│ │     • POST /upload_file.php ......... Upload media file             │ │
│ │     • POST /delete_file.php ......... Delete media file             │ │
│ │     • GET  /calculate_payouts.php ... Calculate tournament payouts  │ │
│ │     • GET  /generate_qr.php ......... Generate bracket QR code      │ │
│ │     • GET  /qr_setup.php ............ TV setup QR codes             │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │  ⚙️ SYSTEMD SERVICE: web-monitor.service                            │ │
│ │                                                                       │ │
│ │  Runs: /home/pi/web_monitor.py                                       │ │
│ │  Purpose: Monitor tournament_data.json for changes                   │ │
│ │  Logs to: journalctl -u web-monitor.service                         │ │
│ │                                                                       │ │
│ │  What it does:                                                       │ │
│ │    while true:                                                       │ │
│ │      1. Read /var/www/html/tournament_data.json                     │ │
│ │      2. Check display_tournament flag                               │ │
│ │      3. Log status changes                                           │ │
│ │      4. Sleep 60 seconds                                            │ │
│ │      5. Repeat                                                       │ │
│ │                                                                       │ │
│ │  Note: Not critical - provides debugging logs only                  │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │  🔊 AVAHI (mDNS) SERVICE                                            │ │
│ │                                                                       │ │
│ │  Broadcasts: bankshot-display.local                                  │ │
│ │  Enables: Zero-configuration networking                              │ │
│ │  Benefit: Works even if DHCP assigns new IP                         │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
                                    ↑
                    HTTP Requests (every 30 seconds)
                                    ↑
┌─────────────────────────────────────────────────────────────────────────┐
│                            SMART TVs                                      │
│                    (Can be 1 TV or 100 TVs)                              │
│                                                                           │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │  📺 TV #1 - Web Browser                                             │ │
│ │                                                                       │ │
│ │  URL: http://bankshot-display.local/tv.html                          │ │
│ │       (or http://192.168.1.XXX/tv.html)                             │ │
│ │                                                                       │ │
│ │  📜 tv.html Logic:                                                   │ │
│ │     1. Discover Pi server (tries .local, tries IP)                  │ │
│ │     2. setInterval(30 seconds):                                      │ │
│ │        a. Fetch /get_tournament_data.php                            │ │
│ │        b. Read display_tournament flag                               │ │
│ │        c. if (display_tournament == true):                          │ │
│ │             Load index.php in iframe                                │ │
│ │           else:                                                      │ │
│ │             Load ads_display.html in iframe                         │ │
│ │     3. Auto-reconnect if connection lost                            │ │
│ │     4. Keep screen awake                                            │ │
│ │                                                                       │ │
│ │  Current Display: [iframe showing index.php or ads_display.html]   │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │  📺 TV #2 - Web Browser                                             │ │
│ │  Same as above...                                                    │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
│                                                                           │
│ ┌─────────────────────────────────────────────────────────────────────┐ │
│ │  📺 TV #N - Web Browser                                             │ │
│ │  Same as above...                                                    │ │
│ └─────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

## Display Page Logic

### index.php (Tournament + Ads Display)

```
┌──────────────────────────────────────────────────────────────┐
│                         index.php                             │
│                                                                │
│  PHP: Checks tournament_data.json                            │
│       If display_tournament == true AND player_count > 0:    │
│         Show left sidebar with QR + payouts                  │
│       Else:                                                   │
│         Hide sidebar, full-screen media                      │
│                                                                │
│  ┌─────────────────┬──────────────────────────────────────┐ │
│  │  LEFT SIDEBAR   │     RIGHT SIDE (Media Rotation)      │ │
│  │  (20% width)    │         (80% width)                  │ │
│  │                 │                                        │ │
│  │  ┌───────────┐  │   ┌────────────────────────────┐    │ │
│  │  │ QR Code   │  │   │                            │    │ │
│  │  │ for       │  │   │   Rotating Media Content   │    │ │
│  │  │ Bracket   │  │   │                            │    │ │
│  │  └───────────┘  │   │  - Images (10-30 sec)      │    │ │
│  │                 │   │  - Videos (auto-play)      │    │ │
│  │  24 PLAYERS     │   │  - URLs (websites)         │    │ │
│  │                 │   │  - Fades between items     │    │ │
│  │  Entry: $15     │   │                            │    │ │
│  │                 │   └────────────────────────────┘    │ │
│  │  PAYOUTS:       │                                        │ │
│  │  1st: $180      │   JavaScript:                         │ │
│  │  2nd: $112      │   - Loads media_config.json          │ │
│  │  3rd/4th: $38   │   - Filters by schedule              │ │
│  │  5/6: $32       │   - Creates iframes                  │ │
│  │                 │   - Rotates with timers              │ │
│  └─────────────────┴──────────────────────────────────────┘ │
│                                                                │
│  JavaScript Polling (every 30 sec):                          │
│    fetch('/get_tournament_data.php')                         │
│      → Update player count                                   │
│      → Update payouts                                        │
│      → Show/hide sidebar                                     │
│      → Reload if display_tournament changes                  │
└──────────────────────────────────────────────────────────────┘
```

### ads_display.html (Ads-Only Display)

```
┌──────────────────────────────────────────────────────────────┐
│                     ads_display.html                          │
│                                                                │
│  ┌──────────────────────────────────────────────────────────┐│
│  │                                                            ││
│  │                                                            ││
│  │                FULL-SCREEN MEDIA ROTATION                 ││
│  │                                                            ││
│  │   Same media system as index.php but:                    ││
│  │   - No sidebar                                            ││
│  │   - 100% width                                            ││
│  │   - Only shows items with displayOnAds=true              ││
│  │                                                            ││
│  │                                                            ││
│  └──────────────────────────────────────────────────────────┘│
│                                                                │
│  JavaScript:                                                  │
│    - Same media loading logic                                │
│    - Filters by displayOnAds flag                           │
│    - Filters by schedule (day/time)                         │
│    - Creates iframes                                         │
│    - Rotates with fade transitions                          │
│    - No polling (static mode)                               │
└──────────────────────────────────────────────────────────────┘
```

## Data Flow Timeline

### Example: Tournament Day

```
Time      GitHub Actions        Raspberry Pi          TV Display
─────────────────────────────────────────────────────────────────
6:00 AM   Scrape (no tourney)   Pull (no change)      [Ads]
6:15 AM   Scrape (no tourney)   
6:20 AM                         Pull (no change)
6:30 AM   Scrape (no tourney)   
6:40 AM                         Pull (no change)
6:45 AM   Scrape (no tourney)   
7:00 AM   🎱 TOURNEY CREATED!   Pull (no change)      [Ads]
          status="Upcoming"
          display=false
7:15 AM   Scrape (still upcom.) Pull (sees upcoming)  [Ads]
7:30 AM   🎱 FIRST PLAYER!      
          status="In Progress"
          display=true ✓
7:35 AM                         Pull (gets new data!)
7:36 AM                         TVs poll server
                                (sees display=true)   [Switch!]
7:36 AM                                               [Tournament]
                                                      [+ Ads rotate]
8:00 AM   Scrape (in progress)  
8:05 AM                         Pull + Update
8:06 AM                         TVs poll
                                (update player count) [Update #]
...continuous throughout day...
11:00 PM  Scrape (completed)
          status="Completed" 
          display=false
11:05 PM                        Pull (gets update)
11:06 PM                        TVs poll
                                (sees display=false)  [Switch!]
11:06 PM                                              [Ads]
```

## Component Responsibilities

### GitHub Actions (Cloud)
✅ Scrape DigitalPool.com every 15 minutes
✅ Parse tournament data (name, time, status, players)
✅ Determine which tournament to display (smart priority)
✅ Generate tournament_data.json
✅ Commit to repository

❌ Doesn't serve web pages
❌ Doesn't talk to TVs directly
❌ Doesn't manage media

### Raspberry Pi (Server)
✅ Pull data from GitHub every 5 minutes
✅ Serve web pages (Apache)
✅ Execute PHP scripts
✅ Host media files
✅ Provide APIs for TVs
✅ Generate QR codes
✅ Calculate payouts

❌ Doesn't scrape websites (GitHub does)
❌ Doesn't push to TVs (TVs pull)

### Smart TVs (Clients)
✅ Load tv.html in web browser
✅ Poll server every 30 seconds
✅ Switch displays automatically
✅ Show media rotation
✅ Reconnect if disconnected

❌ Don't store any data
❌ Don't do any processing
❌ Don't connect to GitHub

### Media Manager (Web UI)
✅ Upload media files
✅ Add URLs
✅ Configure schedules
✅ Set display modes
✅ Reorder content

❌ Doesn't affect scraping
❌ Doesn't affect tournament detection

## Network Topology

```
                    Internet
                        │
    ┌───────────────────┼───────────────────┐
    │                   │                   │
[GitHub]           [Router/WiFi]     [DigitalPool.com]
  (Cloud)               │              (scraped by
                        │               GitHub Actions)
        ┌───────────────┴───────────────┐
        │      Local Network             │
        │   192.168.1.0/24               │
        │                                │
    [Raspberry Pi]               [Smart TVs]
  .local hostname                 (multiple)
192.168.1.XXX:80                  Web browsers
        │
   mDNS broadcast
        │
    [All devices can
     find Pi by name:
   bankshot-display.local]
```

## File Access Patterns

### Read Operations
```
GitHub → Pi: git pull (every 5 min)
TVs → Pi: HTTP GET /get_tournament_data.php (every 30 sec)
TVs → Pi: HTTP GET /media/*.jpg (on demand)
User → Pi: HTTP GET /media_manager.html (manual)
```

### Write Operations
```
GitHub Actions → GitHub: git push (when tournament found)
Pi → Pi: file copy (tournament_data.json, every 5 min)
User → Pi: HTTP POST /upload_file.php (manual)
User → Pi: HTTP POST /save_media.php (manual)
```

---

This diagram shows the complete system flow. All components work together
but are loosely coupled - if one fails, others continue working.
