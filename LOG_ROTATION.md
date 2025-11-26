# Log Rotation Configuration

The payout updater log is automatically rotated to prevent it from growing too large.

## Automatic Rotation

Logs are rotated when **either** condition is met:
- Log file reaches **10MB** in size
- **Weekly** (every 7 days)

## Configuration

- **Location:** `/etc/logrotate.d/bankshot-payout`
- **Log file:** `/var/www/html/payout_updater.log`
- **Rotated logs:** `/var/www/html/payout_updater.log.1.gz`, `.2.gz`, etc.
- **Retention:** 4 rotations (approximately 4 weeks)

## What Happens During Rotation

1. Current log renamed: `payout_updater.log` → `payout_updater.log.1`
2. Previous logs shifted: `.1` → `.2`, `.2` → `.3`, etc.
3. Oldest log (`.4`) is deleted
4. Logs compressed with gzip (except most recent)
5. New empty log created with proper permissions (0644, www-data:www-data)

## Manual Operations

### View Current Log
```bash
tail -f /var/www/html/payout_updater.log
```

### View Rotated Logs
```bash
# List all logs
ls -lh /var/www/html/payout_updater.log*

# View compressed log
zcat /var/www/html/payout_updater.log.1.gz | less
# or
zless /var/www/html/payout_updater.log.1.gz
```

### Force Rotation (Manual)
```bash
# Force immediate rotation
sudo logrotate -f /etc/logrotate.d/bankshot-payout

# Test rotation without actually doing it
sudo logrotate -d /etc/logrotate.d/bankshot-payout
```

### Check Rotation Status
```bash
# View logrotate status file
cat /var/lib/logrotate/status | grep bankshot
```

### Disable Rotation (Not Recommended)
```bash
sudo rm /etc/logrotate.d/bankshot-payout
```

### Re-enable Rotation
```bash
# Re-run the installer
sudo bash install.sh
```

## Disk Space Usage

With default settings:
- Current log: Up to 10MB
- Rotated logs: 4 × ~10MB compressed = ~40MB
- **Total maximum:** ~50MB

## Customization

Edit `/etc/logrotate.d/bankshot-payout`:

### Change Rotation Size
```
size 20M          # Rotate when log reaches 20MB
```

### Change Rotation Frequency
```
daily             # Rotate daily instead of weekly
monthly           # Rotate monthly
```

### Keep More History
```
rotate 8          # Keep 8 rotations instead of 4
```

### Don't Compress
```
# compress        # Comment out to disable compression
nocompress        # Or explicitly disable
```

After editing, test with:
```bash
sudo logrotate -d /etc/logrotate.d/bankshot-payout
```

## Troubleshooting

### Rotation Not Working

Check if logrotate is running:
```bash
# Check when logrotate last ran
cat /var/lib/logrotate/status

# Check system logs for errors
sudo journalctl -u logrotate

# Manually run logrotate
sudo logrotate -v /etc/logrotate.d/bankshot-payout
```

### Permissions Issues

If logs aren't rotating due to permissions:
```bash
# Fix ownership
sudo chown www-data:www-data /var/www/html/payout_updater.log*

# Fix permissions
sudo chmod 644 /var/www/html/payout_updater.log*
```

### Disk Space Issues

If you're running out of space:
```bash
# Check log sizes
du -h /var/www/html/payout_updater.log*

# Delete old rotated logs (keep only current)
sudo rm /var/www/html/payout_updater.log.[1-9]*

# Or force rotation to compress current log
sudo logrotate -f /etc/logrotate.d/bankshot-payout
```

## Log Analysis

### Count Entries
```bash
# Count total log lines
wc -l /var/www/html/payout_updater.log

# Count successful updates
grep "Successfully updated" /var/www/html/payout_updater.log | wc -l

# Count errors
grep "ERROR" /var/www/html/payout_updater.log | wc -l
```

### View Error Summary
```bash
# Show only errors from today
grep "ERROR" /var/www/html/payout_updater.log | grep "$(date +%Y-%m-%d)"

# Show last 10 errors
grep "ERROR" /var/www/html/payout_updater.log | tail -10
```

### Extract Specific Time Range
```bash
# Show logs from specific hour
grep "2025-11-26 17:" /var/www/html/payout_updater.log

# Count updates per hour today
grep "$(date +%Y-%m-%d)" /var/www/html/payout_updater.log | \
  cut -d' ' -f2 | cut -d':' -f1 | sort | uniq -c
```

## Monitoring

Create a simple monitoring script:

```bash
#!/bin/bash
# /usr/local/bin/check-payout-logs.sh

LOG_FILE="/var/www/html/payout_updater.log"
MAX_SIZE_MB=50

# Check log size
SIZE_MB=$(du -m "$LOG_FILE" 2>/dev/null | cut -f1)

if [ "$SIZE_MB" -gt "$MAX_SIZE_MB" ]; then
    echo "WARNING: Log file is ${SIZE_MB}MB (max: ${MAX_SIZE_MB}MB)"
    echo "Consider forcing rotation: sudo logrotate -f /etc/logrotate.d/bankshot-payout"
fi

# Check for recent errors (last hour)
RECENT_ERRORS=$(grep "ERROR" "$LOG_FILE" | grep "$(date +%Y-%m-%d)" | tail -5)
if [ -n "$RECENT_ERRORS" ]; then
    echo "Recent errors detected:"
    echo "$RECENT_ERRORS"
fi
```

Run it via cron:
```bash
# Check logs every hour
0 * * * * /usr/local/bin/check-payout-logs.sh
```

## Best Practices

1. **Monitor regularly:** Check logs at least weekly
2. **Review errors:** Address any ERROR entries promptly
3. **Keep defaults:** 10MB/weekly rotation is usually sufficient
4. **Don't disable:** Log rotation prevents disk space issues
5. **Archive important data:** If you need long-term logs, back them up before they're deleted

## Emergency Recovery

If you need to recover deleted logs:
```bash
# Check if any backups exist in /tmp
ls -la /tmp/bankshot-backup-*

# Logs are NOT backed up by uninstall script
# Set up your own backup if you need historical data
```

To back up logs before they're deleted:
```bash
# Add to cron (weekly backup)
0 0 * * 0 cp /var/www/html/payout_updater.log* /backup/location/
```
