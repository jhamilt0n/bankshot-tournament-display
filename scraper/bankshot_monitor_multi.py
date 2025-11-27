#!/usr/bin/env python3
"""
Bankshot Billiards Tournament Monitor - Multi-Tournament Version
ENHANCED: Gets entry fee DIRECTLY from Digital Pool (tr[18]/td[2])
"""

import datetime
import time
import json
import sys
import re
import logging
from logging.handlers import RotatingFileHandler
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException


# Configuration
VENUE_NAME = "Bankshot Billiards"
VENUE_CITY = "Hilliard"
DATA_FILE = "/home/pi/tournament_data.json"
DATA_FILE_BACKUP = "/var/www/html/tournament_data.json"
LOG_FILE = "/home/pi/logs/tournament_monitor.log"


def setup_logging():
    """Configure rotating log handler"""
    logger = logging.getLogger()
    logger.setLevel(logging.INFO)
    logger.handlers = []
    
    handler = RotatingFileHandler(LOG_FILE, maxBytes=5*1024*1024, backupCount=5)
    formatter = logging.Formatter('[%(asctime)s] %(message)s', datefmt='%Y-%m-%d %H:%M:%S')
    handler.setFormatter(formatter)
    logger.addHandler(handler)
    
    console_handler = logging.StreamHandler()
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)
    
    return logger


logger = setup_logging()


def log(message):
    logging.info(message)


def setup_driver(headless=True):
    chrome_options = Options()
    if headless:
        chrome_options.add_argument('--headless')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--start-maximized')
    chrome_options.add_argument('--user-agent=Mozilla/5.0 (X11; Linux armv7l) AppleWebKit/537.36')
    chrome_options.add_argument('--disable-extensions')
    
    try:
        service = Service(executable_path='/usr/bin/chromedriver')
        return webdriver.Chrome(service=service, options=chrome_options)
    except Exception as e:
        log(f"Error setting up ChromeDriver: {e}")
        raise


def get_tournament_details_from_page(driver, tournament_url, player_count):
    """
    Get tournament details with DIRECT entry fee extraction
    XPath: tr[18]/td[2] for entry fee
    """
    try:
        if not tournament_url:
            return None
        
        log(f"Fetching details from: {tournament_url}")
        driver.get(tournament_url)
        time.sleep(3)
        
        details = {
            'start_time': None,
            'date': None,
            'entry_fee': 15,  # default
            'format_type': 'Singles',
            'has_digital_pool_payouts': False,
            'payouts': {}
        }
        
        # Extract START TIME (tr[3]/td[2])
        xpath_start_time = "/html/body/div[1]/div/div/section/section/section/main/div/div[2]/div[2]/div/div/div/div/div/div[2]/div/div[1]/div[1]/div[2]/div/div/div/div/div/div/div/div[2]/div/div/div/div/table/tbody/tr[3]/td[2]"
        try:
            elem = driver.find_element(By.XPATH, xpath_start_time)
            raw_time = elem.text.strip()
            if raw_time:
                details['start_time'] = clean_start_time_string(raw_time)
                log(f"✓ Start time: {details['start_time']}")
                
                date_match = re.search(r'(\w+,\s+\w+\s+\d+,\s+\d{4})', raw_time)
                if date_match:
                    try:
                        parsed = datetime.datetime.strptime(date_match.group(1), "%a, %b %d, %Y")
                        details['date'] = parsed.strftime("%Y/%m/%d")
                        log(f"✓ Date: {details['date']}")
                    except:
                        pass
        except NoSuchElementException:
            log("⚠ Start time not found")
        
        # Extract ENTRY FEE DIRECTLY (tr[18]/td[2]) - PRIMARY METHOD
        xpath_entry_fee = "/html/body/div[1]/div/div/section/section/section/main/div/div[2]/div[2]/div/div/div/div/div/div[2]/div/div[1]/div[1]/div[2]/div/div/div/div/div/div/div/div[2]/div/div/div/div/table/tbody/tr[18]/td[2]"
        try:
            elem = driver.find_element(By.XPATH, xpath_entry_fee)
            fee_text = elem.text.strip()
            
            fee_match = re.search(r'\$?([\d,]+(?:\.\d{2})?)', fee_text)
            if fee_match:
                fee_value = fee_match.group(1).replace(',', '')
                details['entry_fee'] = int(float(fee_value))
                log(f"✓ Entry fee from Digital Pool: ${details['entry_fee']}")
        except NoSuchElementException:
            log("⚠ Entry fee field not found (tr[18]/td[2])")
            
            # FALLBACK: Calculate from total pot
            xpath_total_pot = "/html/body/div[1]/div/div/section/section/section/main/div/div[2]/div[2]/div/div/div/div/div/div/div/div[1]/div[2]/div[2]/table/tbody/tr[6]/td[2]"
            try:
                elem = driver.find_element(By.XPATH, xpath_total_pot)
                pot_text = elem.text.strip()
                
                pot_match = re.search(r'\$?([\d,]+)', pot_text)
                if pot_match and player_count > 0:
                    total_pot = int(pot_match.group(1).replace(',', ''))
                    calculated = total_pot // player_count
                    details['entry_fee'] = calculated
                    log(f"⚠ Calculated entry fee: ${calculated} (${total_pot} / {player_count})")
            except:
                log("⚠ Using default $15 entry fee")
        
        # Extract FORMAT TYPE
        xpath_format = "//*[@id='rc-tabs-1-panel-details']/div/div/table/tbody/tr[5]/td[2]"
        try:
            elem = driver.find_element(By.XPATH, xpath_format)
            format_text = elem.text.strip()
            details['format_type'] = format_text if format_text else 'Singles'
            log(f"✓ Format: {details['format_type']}")
        except:
            pass
        
        # Extract PAYOUTS
        xpath_first = "/html/body/div[1]/div/div/section/section/section/main/div/div[2]/div[2]/div/div/div/div/div/div[2]/div/div[1]/div[2]/div[2]/table/tbody/tr[2]/td[2]"
        try:
            elem = driver.find_element(By.XPATH, xpath_first)
            first_text = elem.text.strip()
            
            if first_text and first_text != '$0' and first_text != '$0.00':
                details['has_digital_pool_payouts'] = True
                details['payouts']['1st'] = first_text
                log(f"✓ Digital Pool payout 1st: {first_text}")
        except:
            pass
        
        return details
        
    except Exception as e:
        log(f"Error fetching details: {e}")
        return None


def clean_start_time_string(raw):
    if not raw:
        return None
    match = re.search(r'(\d{1,2}:\d{2}\s*[AP]\.?M\.?)', raw, re.IGNORECASE)
    if match:
        clean = match.group(1).strip()
        clean = re.sub(r'([AP])\.?M\.?', r'\1M', clean, flags=re.IGNORECASE)
        clean = re.sub(r'(\d)([AP]M)', r'\1 \2', clean, flags=re.IGNORECASE)
        return clean
    return raw


# ... (rest of functions remain the same - search_tournaments_on_page, etc.)
# I'll include the key parts that need updating:

def save_tournament_data(tournament):
    """Save tournament data with entry fee from Digital Pool"""
    if not tournament:
        output_data = {
            'tournament_name': 'No tournaments to display',
            'tournament_url': None,
            'entry_fee': 15,
            'display_tournament': False,
            'last_updated': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
    else:
        should_display = (tournament['status'] in ['In Progress', 'Upcoming'])
        
        output_data = {
            'tournament_name': tournament['name'],
            'tournament_url': tournament['url'],
            'venue': tournament['venue'],
            'date': tournament['date'],
            'start_time': tournament['start_time'],
            'status': tournament['status'],
            'player_count': tournament.get('player_count', 0),
            'entry_fee': tournament.get('entry_fee', 15),  # From Digital Pool!
            'format_type': tournament.get('format_type', 'Singles'),
            'has_digital_pool_payouts': tournament.get('has_digital_pool_payouts', False),
            'digital_pool_payouts': tournament.get('digital_pool_payouts', {}),
            'payout_data': 'payouts15.json',
            'last_updated': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'display_tournament': should_display
        }
        
        log(f"\n✓ Entry Fee: ${tournament.get('entry_fee', 15)} (from Digital Pool tr[18]/td[2])")
    
    for file_path in [DATA_FILE, DATA_FILE_BACKUP]:
        try:
            with open(file_path, 'w') as f:
                json.dump(output_data, f, indent=2)
            log(f"✓ Saved to {file_path}")
        except Exception as e:
            log(f"✗ Error saving: {e}")
