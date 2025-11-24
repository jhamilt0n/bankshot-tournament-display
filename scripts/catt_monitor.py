#!/usr/bin/env python3
"""
Smart CATT Casting Monitor
Automatically switches Chromecast display based on tournament status
UPDATED: Monitors file changes and re-casts only when content changes
"""

import json
import subprocess
import time
import socket
import logging
import re
import hashlib
from datetime import datetime
from pathlib import Path

# Configuration
TOURNAMENT_DATA_FILE = '/var/www/html/tournament_data.json'
MEDIA_CONFIG_FILE = '/var/www/html/media_config.json'
STATE_FILE = '/var/www/html/cast_state.json'
LOG_FILE = '/var/log/catt_monitor.log'
CHECK_INTERVAL = 15  # Check every 15 seconds
CATT_COMMAND = '/home/pi/.local/bin/catt'

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_FILE),
        logging.StreamHandler()
    ]
)

def get_local_ip():
    """Get the local IP address of the Pi"""
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(('8.8.8.8', 80))
        ip_address = s.getsockname()[0]
        s.close()
        return ip_address
    except Exception as e:
        logging.error(f"Error getting IP address: {e}")
        return None

def get_file_hash(filepath):
    """Get MD5 hash of file contents"""
    try:
        if not Path(filepath).exists():
            return None
        with open(filepath, 'rb') as f:
            return hashlib.md5(f.read()).hexdigest()
    except Exception as e:
        logging.error(f"Error hashing file {filepath}: {e}")
        return None

def is_business_hours():
    """Check if current time is within business hours"""
    try:
        now = datetime.now()
        day = now.isoweekday()  # 1=Mon, 7=Sun
        hour = now.hour
        minute = now.minute
        current_minutes = hour * 60 + minute
        
        # Business hours in minutes since midnight
        if day == 7:  # Sunday: 12pm - Monday 1am (next day)
            return current_minutes >= 720  # After 12:00pm
        elif day == 1:  # Monday: Closes 1am, Opens 3pm - Tuesday 1am
            if current_minutes < 60:  # Before 1:00am (from Sunday)
                return True
            elif current_minutes >= 900:  # After 3:00pm
                return True
        elif day in [2, 3, 4]:  # Tuesday-Thursday: Closes 1am, Opens 12pm - 1am
            if current_minutes < 60:  # Before 1:00am (from previous day)
                return True
            elif current_minutes >= 720:  # After 12:00pm
                return True
        elif day == 5:  # Friday: Closes 1am, Opens 12pm - Saturday 2:30am
            if current_minutes < 60:  # Before 1:00am (from Thursday)
                return True
            elif current_minutes >= 720:  # After 12:00pm
                return True
        elif day == 6:  # Saturday: Closes 2:30am, Opens 12pm - Sunday 2:30am
            if current_minutes < 150:  # Before 2:30am (from Friday)
                return True
            elif current_minutes >= 720:  # After 12:00pm
                return True
        
        return False
    except Exception as e:
        logging.error(f"Error checking business hours: {e}")
        return False

def load_tournament_data():
    """Load tournament data from JSON file"""
    try:
        if Path(TOURNAMENT_DATA_FILE).exists():
            with open(TOURNAMENT_DATA_FILE, 'r') as f:
                return json.load(f)
        return None
    except Exception as e:
        logging.error(f"Error loading tournament data: {e}")
        return None

def load_cast_state():
    """Load the current cast state"""
    try:
        if Path(STATE_FILE).exists():
            with open(STATE_FILE, 'r') as f:
                return json.load(f)
        return {
            'is_casting_tournament': False,
            'last_tournament_url': None,
            'cast_started_at': None,
            'tournament_data_hash': None,
            'media_config_hash': None
        }
    except Exception as e:
        logging.error(f"Error loading cast state: {e}")
        return {
            'is_casting_tournament': False,
            'last_tournament_url': None,
            'cast_started_at': None,
            'tournament_data_hash': None,
            'media_config_hash': None
        }

def save_cast_state(state):
    """Save the current cast state"""
    try:
        with open(STATE_FILE, 'w') as f:
            json.dump(state, f, indent=2)
        return True
    except Exception as e:
        logging.error(f"Error saving cast state: {e}")
        return False

def catt_stop():
    """Stop current CATT cast"""
    try:
        logging.info("Stopping current cast...")
        result = subprocess.run(
            [CATT_COMMAND, 'stop'],
            capture_output=True,
            text=True,
            timeout=10
        )
        
        if result.returncode == 0:
            logging.info("Cast stopped successfully")
            return True
        else:
            logging.warning(f"CATT stop returned non-zero: {result.stderr}")
            return False
    except Exception as e:
        logging.error(f"Error stopping cast: {e}")
        return False

def catt_cast_site(url):
    """Cast a website using CATT"""
    try:
        logging.info(f"Casting site: {url}")
        result = subprocess.run(
            [CATT_COMMAND, 'cast_site', url],
            capture_output=True,
            text=True,
            timeout=30
        )
        
        if result.returncode == 0:
            logging.info("Site cast successfully")
            return True
        else:
            logging.warning(f"CATT cast returned non-zero: {result.stderr}")
            return False
    except Exception as e:
        logging.error(f"Error casting site: {e}")
        return False

def parse_start_time(start_time_str):
    """Parse start time string like '7:00 PM' to minutes since midnight"""
    try:
        if not start_time_str:
            return None
        
        # Extract hour and minute
        match = re.search(r'(\d+):?(\d+)?\s*(AM|PM)', start_time_str, re.IGNORECASE)
        if not match:
            return None
        
        hour = int(match.group(1))
        minute = int(match.group(2)) if match.group(2) else 0
        meridiem = match.group(3).upper()
        
        # Convert to 24-hour format
        if meridiem == 'PM' and hour != 12:
            hour += 12
        elif meridiem == 'AM' and hour == 12:
            hour = 0
        
        return hour * 60 + minute
    except Exception as e:
        logging.error(f"Error parsing start time '{start_time_str}': {e}")
        return None

def should_start_early_for_tournament(tournament_data):
    """Check if we should start casting 1 hour before tournament"""
    try:
        # Check if tournament is today
        tournament_date = tournament_data.get('date')
        if not tournament_date:
            return False
        
        today = datetime.now().strftime('%Y/%m/%d')
        if tournament_date != today:
            return False
        
        # Check if tournament has a start time
        start_time_str = tournament_data.get('start_time')
        if not start_time_str:
            return False
        
        # Parse start time
        tournament_start = parse_start_time(start_time_str)
        if tournament_start is None:
            return False
        
        # Get current time in minutes
        now = datetime.now()
        current_minutes = now.hour * 60 + now.minute
        
        # Check if we're within 1 hour before tournament start
        early_start = tournament_start - 60
        
        if current_minutes >= early_start and current_minutes < tournament_start:
            return True
        
        return False
        
    except Exception as e:
        logging.error(f"Error checking early start: {e}")
        return False

def should_display_tournament(tournament_data):
    """
    Determine if tournament should be displayed
    Includes: 1-hour early start, active tournaments, OR business hours
    """
    try:
        # Check for early start (1 hour before tournament)
        if should_start_early_for_tournament(tournament_data):
            return True
        
        # Check business hours - always cast during business hours
        if is_business_hours():
            return True
        
        # Check for active tournament display
        should_display = tournament_data.get('display_tournament', False)
        
        # Additional safety checks
        status = tournament_data.get('status', '')
        tournament_name = tournament_data.get('tournament_name', '')
        
        # Don't display if explicitly marked as "No tournaments"
        if 'no tournament' in tournament_name.lower():
            return False
        
        # Status should be "In Progress" or "Upcoming"
        if status not in ['In Progress', 'Upcoming', 'in_progress', 'upcoming']:
            return False
        
        return should_display
        
    except Exception as e:
        logging.error(f"Error checking display status: {e}")
        return False

def monitor_and_cast():
    """Main monitoring and casting logic - re-casts only on file changes"""
    logging.info("=" * 60)
    logging.info("CATT Monitor Starting - File change monitoring")
    logging.info("Casts during: Business hours OR Tournament active")
    logging.info("Re-casts only when tournament_data.json or media_config.json changes")
    logging.info("=" * 60)
    
    state = load_cast_state()
    
    while True:
        try:
            # Load current tournament data
            tournament_data = load_tournament_data()
            
            if not tournament_data:
                logging.debug("No tournament data found")
                time.sleep(CHECK_INTERVAL)
                continue
            
            # Get tournament info
            tournament_name = tournament_data.get('tournament_name', 'Unknown')
            tournament_url = tournament_data.get('tournament_url')
            status = tournament_data.get('status', 'Unknown')
            player_count = tournament_data.get('player_count', 0)
            should_display = should_display_tournament(tournament_data)
            
            # Get file hashes to detect changes
            current_tournament_hash = get_file_hash(TOURNAMENT_DATA_FILE)
            current_media_hash = get_file_hash(MEDIA_CONFIG_FILE)
            
            logging.debug(f"Tournament: {tournament_name}")
            logging.debug(f"  Status: {status}, Players: {player_count}, Should Display: {should_display}")
            
            # Get local IP for casting
            local_ip = get_local_ip()
            if not local_ip:
                logging.error("Could not determine local IP address")
                time.sleep(CHECK_INTERVAL)
                continue
            
            cast_url = f"http://{local_ip}/"
            
            # SCENARIO 1: Tournament should be displayed and we're not casting yet
            if should_display and not state['is_casting_tournament']:
                logging.info(f"🎱 Tournament ready to display")
                logging.info(f"   Name: {tournament_name}")
                logging.info(f"   Status: {status}")
                logging.info(f"   Players: {player_count}")
                
                catt_stop()
                time.sleep(2)
                
                if catt_cast_site(cast_url):
                    state['is_casting_tournament'] = True
                    state['last_tournament_url'] = tournament_url
                    state['cast_started_at'] = datetime.now().isoformat()
                    state['tournament_data_hash'] = current_tournament_hash
                    state['media_config_hash'] = current_media_hash
                    save_cast_state(state)
                    logging.info("✓ Successfully started casting tournament display")
            
            # SCENARIO 2: Already casting - check for file changes
            elif should_display and state['is_casting_tournament']:
                tournament_changed = (current_tournament_hash != state.get('tournament_data_hash'))
                media_changed = (current_media_hash != state.get('media_config_hash'))
                
                if tournament_changed or media_changed:
                    change_reasons = []
                    if tournament_changed:
                        change_reasons.append("tournament_data.json changed")
                    if media_changed:
                        change_reasons.append("media_config.json changed")
                    
                    logging.info(f"🔄 Content changed: {', '.join(change_reasons)}")
                    logging.info("   Re-casting to update display...")
                    
                    catt_stop()
                    time.sleep(1)
                    
                    if catt_cast_site(cast_url):
                        state['tournament_data_hash'] = current_tournament_hash
                        state['media_config_hash'] = current_media_hash
                        save_cast_state(state)
                        logging.info("✓ Re-cast successful - display updated")
                    else:
                        logging.error("✗ Re-cast failed - will retry next cycle")
            
            # SCENARIO 3: Tournament no longer should be displayed
            elif not should_display and state['is_casting_tournament']:
                logging.info("Tournament no longer should be displayed - Stopping cast")
                catt_stop()
                state['is_casting_tournament'] = False
                state['last_tournament_url'] = None
                state['cast_started_at'] = None
                state['tournament_data_hash'] = None
                state['media_config_hash'] = None
                save_cast_state(state)
            
            time.sleep(CHECK_INTERVAL)
            
        except KeyboardInterrupt:
            logging.info("Monitor stopped by user")
            break
        except Exception as e:
            logging.error(f"Error in monitor loop: {e}")
            import traceback
            traceback.print_exc()
            time.sleep(CHECK_INTERVAL)

def main():
    monitor_and_cast()

if __name__ == '__main__':
    main()
