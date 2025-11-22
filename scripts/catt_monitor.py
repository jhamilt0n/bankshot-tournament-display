#!/usr/bin/env python3
"""
Smart CATT Casting Monitor
Automatically switches Chromecast display based on tournament status
FIXED: Works with bankshot_monitor_multi.py data format
"""

import json
import subprocess
import time
import socket
import logging
from datetime import datetime, timedelta
from pathlib import Path

# Configuration
TOURNAMENT_DATA_FILE = '/var/www/html/tournament_data.json'
STATE_FILE = '/var/www/html/cast_state.json'
LOG_FILE = '/var/log/catt_monitor.log'
CHECK_INTERVAL = 30  # Check every 30 seconds
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
            'last_status': None,
            'cast_started_at': None,
            'failsafe_check_done': False
        }
    except Exception as e:
        logging.error(f"Error loading cast state: {e}")
        return {
            'is_casting_tournament': False,
            'last_tournament_url': None,
            'last_status': None,
            'cast_started_at': None,
            'failsafe_check_done': False
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
    """Parse start time string like '7:00 PM' or '7pm' into datetime"""
    try:
        # Try various formats
        formats = [
            '%I:%M %p',  # 7:00 PM
            '%I:%M%p',   # 7:00PM
            '%I %p',     # 7 PM
            '%I%p'       # 7PM
        ]
        
        today = datetime.now().date()
        
        for fmt in formats:
            try:
                time_obj = datetime.strptime(start_time_str.strip(), fmt).time()
                return datetime.combine(today, time_obj)
            except:
                continue
        
        return None
    except Exception as e:
        logging.error(f"Error parsing start time: {e}")
        return None

def is_today_tournament(tournament_data):
    """Check if tournament is scheduled for today"""
    try:
        tournament_date = tournament_data.get('date')
        if not tournament_date:
            return False
        
        # Tournament date format is YYYY/MM/DD
        today = datetime.now().strftime("%Y/%m/%d")
        return tournament_date == today
    except Exception as e:
        logging.error(f"Error checking tournament date: {e}")
        return False

def is_tournament_complete(tournament_data):
    """Check if tournament is completed"""
    try:
        status = tournament_data.get('status', '').lower()
        return status == 'complete' or status == 'completed'
    except Exception as e:
        logging.error(f"Error checking tournament status: {e}")
        return False

def should_display_tournament(tournament_data):
    """
    Determine if tournament should be displayed based on the scraper's display_tournament flag
    This flag is set by bankshot_monitor_multi.py based on smart logic
    """
    try:
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

def is_within_closing_time():
    """Check if we're within closing hours to continue showing tournament"""
    now = datetime.now()
    day = now.weekday()  # 0=Mon, 6=Sun
    hour = now.hour
    minute = now.minute
    current_minutes = hour * 60 + minute
    
    # Allow tournament to continue until closing time
    # Sunday (6): Until 1am Monday
    if day == 6 and current_minutes < 60:
        return True
    # Monday (0): Until 1am Tuesday  
    elif day == 0 and current_minutes < 60:
        return True
    # Tuesday (1): Until 1am Wednesday
    elif day == 1 and current_minutes < 60:
        return True
    # Wednesday (2): Until 1am Thursday
    elif day == 2 and current_minutes < 60:
        return True
    # Thursday (3): Until 1am Friday
    elif day == 3 and current_minutes < 60:
        return True
    # Friday (4): Until 2:30am Saturday
    elif day == 4 and current_minutes < 150:
        return True
    # Saturday (5): Until 2:30am Sunday
    elif day == 5 and current_minutes < 150:
        return True
    
    return False

def monitor_and_cast():
    """Main monitoring and casting logic"""
    logging.info("=" * 60)
    logging.info("CATT Monitor Starting (Fixed for bankshot_monitor_multi.py)")
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
            is_today = is_today_tournament(tournament_data)
            is_complete = is_tournament_complete(tournament_data)
            should_display = should_display_tournament(tournament_data)
            
            # Allow continued casting if tournament not complete and within closing hours
            should_continue_tournament = (
                state['is_casting_tournament'] and 
                not is_complete and 
                is_within_closing_time() and
                should_display
            )
            
            logging.debug(f"Tournament: {tournament_name}")
            logging.debug(f"  Today: {is_today}, Status: {status}, Complete: {is_complete}")
            logging.debug(f"  Should Display: {should_display}, Continue: {should_continue_tournament}")
            
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
                
                catt_stop()
                time.sleep(2)
                
                if catt_cast_site(cast_url):
                    state['is_casting_tournament'] = True
                    state['last_tournament_url'] = tournament_url
                    state['last_status'] = status
                    state['cast_started_at'] = datetime.now().isoformat()
                    state['failsafe_check_done'] = False
                    save_cast_state(state)
                    logging.info("✓ Successfully started casting tournament display")
            
            # SCENARIO 2: Continue casting incomplete tournament even if started yesterday
            elif should_continue_tournament:
                logging.debug(f"Continuing tournament cast (not complete, within closing hours)")
                # Keep casting, don't stop
            
            # SCENARIO 3: Failsafe check - 40 minutes after start time
            elif should_display and state['is_casting_tournament'] and not state['failsafe_check_done']:
                start_time_str = tournament_data.get('start_time')
                if start_time_str:
                    start_time = parse_start_time(start_time_str)
                    if start_time:
                        now = datetime.now()
                        failsafe_time = start_time + timedelta(minutes=40)
                        
                        if now >= failsafe_time:
                            logging.info(f"⚠️ FAILSAFE CHECK: 40 minutes past start time ({start_time_str})")
                            logging.info("Checking if tournament data has changed...")
                            
                            # Check if status or URL changed
                            current_url = tournament_url
                            current_status = status
                            last_url = state['last_tournament_url']
                            last_status = state['last_status']
                            
                            if current_url != last_url or current_status != last_status:
                                logging.info("Tournament data changed - Restarting cast...")
                                
                                catt_stop()
                                time.sleep(2)
                                
                                if catt_cast_site(cast_url):
                                    state['last_tournament_url'] = current_url
                                    state['last_status'] = current_status
                                    state['failsafe_check_done'] = True
                                    save_cast_state(state)
                                    logging.info("✓ Failsafe restart completed")
                            else:
                                logging.info("✓ No tournament data change - Marking failsafe as done")
                                state['failsafe_check_done'] = True
                                save_cast_state(state)
            
            # SCENARIO 4: Tournament complete or past closing time - reset state
            elif state['is_casting_tournament'] and (is_complete or not is_within_closing_time() or not should_display):
                if is_complete:
                    logging.info("Tournament completed - Resetting state")
                elif not is_within_closing_time():
                    logging.info("Past closing time - Resetting state")
                else:
                    logging.info("Tournament no longer should be displayed - Resetting state")
                    
                state['is_casting_tournament'] = False
                state['last_tournament_url'] = None
                state['last_status'] = None
                state['failsafe_check_done'] = False
                state['cast_started_at'] = None
                save_cast_state(state)
            
            # SCENARIO 5: Tournament not ready yet - just wait
            elif is_today and not should_display:
                logging.debug("Tournament today but not ready to display yet")
            
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
