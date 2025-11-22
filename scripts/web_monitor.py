#!/usr/bin/env python3
"""
Web Display Monitor
Simple monitoring service for web-based displays
No casting needed - TVs pull data themselves via web browser
"""

import json
import logging
import time
from pathlib import Path
from datetime import datetime

# Configuration
TOURNAMENT_DATA_FILE = '/var/www/html/tournament_data.json'
LOG_FILE = '/var/log/web_monitor.log'
CHECK_INTERVAL = 60  # Check every minute

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(message)s',
    handlers=[
        logging.FileHandler(LOG_FILE),
        logging.StreamHandler()
    ]
)

def load_tournament_data():
    """Load tournament data from JSON file"""
    try:
        if Path(TOURNAMENT_DATA_FILE).exists():
            with open(TOURNAMENT_DATA_FILE) as f:
                return json.load(f)
        return None
    except Exception as e:
        logging.error(f"Error loading tournament data: {e}")
        return None

def monitor():
    """Monitor tournament status and log for debugging"""
    logging.info("=" * 60)
    logging.info("Web Display Monitor Started")
    logging.info("TVs will automatically check this server for updates")
    logging.info("=" * 60)
    
    last_status = None
    
    while True:
        try:
            data = load_tournament_data()
            
            if data:
                tournament_name = data.get('tournament_name', 'Unknown')
                display = data.get('display_tournament', False)
                status = data.get('status', 'Unknown')
                last_updated = data.get('last_updated', 'Unknown')
                
                # Create a status string
                current_status = f"{tournament_name}|{display}|{status}"
                
                # Only log when status changes
                if current_status != last_status:
                    logging.info("-" * 60)
                    logging.info(f"Tournament: {tournament_name}")
                    logging.info(f"Status: {status}")
                    logging.info(f"Should Display: {display}")
                    logging.info(f"Last Updated: {last_updated}")
                    logging.info("-" * 60)
                    
                    if display:
                        logging.info("✓ TVs will show TOURNAMENT display")
                    else:
                        logging.info("○ TVs will show ADS display")
                    
                    last_status = current_status
            else:
                if last_status != "no_data":
                    logging.warning("No tournament data file found")
                    last_status = "no_data"
            
            time.sleep(CHECK_INTERVAL)
            
        except KeyboardInterrupt:
            logging.info("Monitor stopped by user")
            break
        except Exception as e:
            logging.error(f"Error in monitor loop: {e}")
            time.sleep(CHECK_INTERVAL)

def main():
    try:
        monitor()
    except Exception as e:
        logging.error(f"Fatal error: {e}")
        raise

if __name__ == '__main__':
    main()
