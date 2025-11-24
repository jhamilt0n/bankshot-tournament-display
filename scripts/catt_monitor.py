#!/usr/bin/env python3
"""
Smart CATT Casting Monitor
Automatically switches Chromecast display based on tournament status
UPDATED: Uses dashcast for auto-refresh capability, 1-hour early start
"""

import json
import subprocess
import time
import socket
import logging
import re
from datetime import datetime, timedelta
from pathlib import Path

# Configuration
TOURNAMENT_DATA_FILE = '/var/www/html/tournament_data.json'
STATE_FILE = '/var/www/html/cast_state.json'
LOG_FILE = '/var/log/catt_monitor.log'
CHECK_INTERVAL = 30  # Check every 30 seconds
REFRESH_INTERVAL = 4  # Refresh dashcast every 4 cycles (2 minutes)
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
            'refresh_counter': 0
        }
    except Exception as e:
        logging.error(f"Error loading cast state: {e}")
        return {
            'is_casting_tournament': False,
            'last_tournament_url': None,
            'cast_started_at': None,
            'refresh_counter': 0
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

def catt_dashcast(url):
    """Cast a website using CATT dashcast (allows refresh)"""
    try:
        logging.info(f"Dashcasting site: {url}")
        result = subprocess.run(
            [CATT_COMMAND, 'dashcast', 'load', url],
            capture_output=True,
            text=True,
            timeout=30
        )
        
        if result.returncode == 0:
            logging.info("Site dashcast successfully")
            return True
        else:
            logging.warning(f"CATT dashcast returned non-zero: {result.stderr}")
            return False
    except Exception as e:
        logging.error(f"Error dashcasting site: {e}")
        return False

def catt_dashcast_refresh():
    """Refresh the current dashcast"""
    try:
        logging.info("Refreshing dashcast...")
        result = subprocess.run(
            [CATT_COMMAND, 'dashcast', 'reload'],
            capture_output=True,
            text=True,
            timeout=10
        )
        
        if result.returncode == 0:
            logging.info("Dashcast refreshed successfully")
            return True
        else:
            logging.warning(f"CATT dashcast refresh failed: {result.stderr}")
            return False
    except Exception as e:
        logging.error(f"Error refreshing dashcast: {e}")
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
            logging.info(f"1 hour before tournament - early casting enabled")
            return True
        
        return False
        
    except Exception as e:
        logging.error(f"Error checking early start: {e}")
        return False

def should_display_tournament(tournament_data):
    """
    Determine if tournament should be displayed
    Now includes 1-hour early start logic
    """
    try:
        # Check for early start (1 hour before tournament)
        if should_start_early_for_tournament(tournament_data):
            return True
        
        # Primary check: use the display_tournament flag from scraper
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
    """Main monitoring and casting logic with dashcast"""
    logging.info("=" * 60)
    logging.info("CATT Monitor Starting - With dashcast and auto-refresh")
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
                
                if catt_dashcast(cast_url):
                    state['is_casting_tournament'] = True
                    state['last_tournament_url'] = tournament_url
                    state['cast_started_at'] = datetime.now().isoformat()
                    state['refresh_counter'] = 0
                    save_cast_state(state)
                    logging.info("✓ Successfully started dashcasting tournament display")
            
            # SCENARIO 2: Already casting - refresh periodically
            elif should_display and state['is_casting_tournament']:
                state['refresh_counter'] = state.get('refresh_counter', 0) + 1
                
                # Refresh every REFRESH_INTERVAL cycles (default: 2 minutes)
                if state['refresh_counter'] >= REFRESH_INTERVAL:
                    logging.info(f"Periodic refresh ({state['refresh_counter']} cycles)")
                    catt_dashcast_refresh()
                    state['refresh_counter'] = 0
                    save_cast_state(state)
            
            # SCENARIO 3: Tournament no longer should be displayed
            elif not should_display and state['is_casting_tournament']:
                logging.info("Tournament no longer should be displayed - Stopping cast")
                catt_stop()
                state['is_casting_tournament'] = False
                state['last_tournament_url'] = None
                state['cast_started_at'] = None
                state['refresh_counter'] = 0
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
