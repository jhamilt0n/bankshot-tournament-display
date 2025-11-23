#!/bin/bash
# Pull tournament data from GitHub

REPO_DIR="/home/pi/bankshot-tournament-display"
DATA_FILE="/home/pi/tournament_data.json"
DATA_FILE_BACKUP="/var/www/html/tournament_data.json"
LOG_FILE="/home/pi/logs/github_pull.log"

mkdir -p "$(dirname "$LOG_FILE")"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

if [ ! -d "$REPO_DIR" ]; then
    log "ERROR: Repository directory not found: $REPO_DIR"
    exit 1
fi

cd "$REPO_DIR" || exit 1

log "Pulling latest data from GitHub..."
git pull origin main -q 2>&1 | tee -a "$LOG_FILE"

if [ $? -eq 0 ]; then
    log "✓ Successfully pulled from GitHub"
else
    log "✗ Git pull failed"
    exit 1
fi

if [ ! -f "$REPO_DIR/tournament_data.json" ]; then
    log "⚠ No tournament_data.json found in repository"
    exit 0
fi

cp "$REPO_DIR/tournament_data.json" "$DATA_FILE"
if [ $? -eq 0 ]; then
    log "✓ Copied to $DATA_FILE"
else
    log "✗ Failed to copy to $DATA_FILE"
fi

sudo cp "$REPO_DIR/tournament_data.json" "$DATA_FILE_BACKUP"
if [ $? -eq 0 ]; then
    log "✓ Copied to $DATA_FILE_BACKUP"
else
    log "✗ Failed to copy to $DATA_FILE_BACKUP"
fi

if command -v python3 &> /dev/null && [ -f "$DATA_FILE" ]; then
    TOURNAMENT_NAME=$(python3 -c "
import json
try:
    with open('$DATA_FILE', 'r') as f:
        data = json.load(f)
    print(data.get('tournament_name', 'Unknown'))
except:
    print('Error reading file')
" 2>/dev/null)
    
    log "Current tournament: $TOURNAMENT_NAME"
fi

log "Update complete"
