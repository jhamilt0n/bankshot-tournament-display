#!/usr/bin/env python3
"""
Bankshot Billiards Tournament Monitor - Multi-Tournament Version
FIXED: Uses DOM element parsing instead of text parsing to properly capture tournament data
WITH LOG ROTATION: Automatically manages log file size
Handles multiple tournaments per day with smart priority logic:
- Shows first scheduled tournament until later one starts
- Switches to latest "In Progress" tournament
- Keeps showing tournament even after midnight until completed
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
    
    # Remove any existing handlers
    logger.handlers = []
    
    # Create rotating file handler
    # maxBytes: rotate when file reaches 5MB
    # backupCount: keep 5 old log files (scraper.log.1, scraper.log.2, etc.)
    handler = RotatingFileHandler(
        LOG_FILE,
        maxBytes=5*1024*1024,  # 5MB
        backupCount=5
    )
    
    # Set format
    formatter = logging.Formatter(
        '[%(asctime)s] %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    handler.setFormatter(formatter)
    
    # Add handler to logger
    logger.addHandler(handler)
    
    # Also log to console
    console_handler = logging.StreamHandler()
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)
    
    return logger


# Initialize logging with rotation
logger = setup_logging()


def log(message):
    """Log message using the configured logger"""
    logging.info(message)


def setup_driver(headless=True):
    """Setup Chrome WebDriver"""
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


def parse_time_string(time_str):
    """Parse time strings like '7:00 PM' and return datetime.time object"""
    try:
        time_str = time_str.strip()
        for fmt in ['%I:%M %p', '%I:%M%p', '%I %p', '%I%p']:
            try:
                parsed = datetime.datetime.strptime(time_str, fmt)
                return parsed.time()
            except:
                continue
        return None
    except:
        return None


def search_tournaments_on_page(driver):
    """Search for Bankshot tournaments on the current page using DOM parsing"""
    tournaments = []
    
    try:
        # Search for venue
        search_term = VENUE_NAME
        log(f"Searching for: {search_term}")
        
        # Find and use search input
        search_input = None
        selectors = [
            "input.ant-input",
            "input[type='text']",
            "//input[contains(@class, 'ant-input')]",
        ]
        
        for selector in selectors:
            try:
                if selector.startswith('//'):
                    search_input = driver.find_element(By.XPATH, selector)
                else:
                    search_input = driver.find_element(By.CSS_SELECTOR, selector)
                
                if search_input.is_displayed() and search_input.is_enabled():
                    break
                else:
                    search_input = None
            except NoSuchElementException:
                continue
        
        if not search_input:
            log("✗ Could not find search input")
            return []
        
        search_input.click()
        time.sleep(0.5)
        search_input.clear()
        time.sleep(0.5)
        
        for char in search_term:
            search_input.send_keys(char)
            time.sleep(0.05)
        
        time.sleep(1)
        
        from selenium.webdriver.common.keys import Keys
        search_input.send_keys(Keys.ENTER)
        
        log("Waiting for search results...")
        time.sleep(8)
        
        # Scroll to load all content
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(2)
        driver.execute_script("window.scrollTo(0, 0);")
        time.sleep(2)
        
        # Try multiple selector strategies to find tournament cards
        card_selectors = [
            ".ant-card",
            "[class*='tournament']",
            "[class*='TournamentCard']",
            ".card",
            "div[class*='Card']"
        ]
        
        tournament_cards = []
        for selector in card_selectors:
            try:
                cards = driver.find_elements(By.CSS_SELECTOR, selector)
                if cards:
                    log(f"Found {len(cards)} elements with selector: {selector}")
                    tournament_cards = cards
                    break
            except:
                continue
        
        if not tournament_cards:
            log("Could not find tournament cards with standard selectors, trying alternative approach...")
            # Fallback: look for any div that contains both venue and date pattern
            all_divs = driver.find_elements(By.TAG_NAME, "div")
            tournament_cards = [div for div in all_divs 
                              if VENUE_NAME in div.text and re.search(r'\d{4}/\d{2}/\d{2}', div.text)]
            log(f"Found {len(tournament_cards)} potential tournament divs with venue and date")
        
        log(f"Processing {len(tournament_cards)} potential tournament cards")
        
        for idx, card in enumerate(tournament_cards):
            try:
                card_text = card.text
                
                # Check if this card is for Bankshot Billiards in Hilliard
                if VENUE_NAME not in card_text:
                    continue
                
                if VENUE_CITY not in card_text:
                    log(f"Card {idx}: Found {VENUE_NAME} but not in {VENUE_CITY}, skipping")
                    continue
                
                log(f"\n{'='*50}")
                log(f"Card {idx} - Found matching venue!")
                log(f"{'='*50}")
                
                # Extract tournament name
                tournament_name = None
                
                # Strategy 1: Look for heading elements
                for tag in ['h1', 'h2', 'h3', 'h4', 'h5']:
                    try:
                        heading = card.find_element(By.TAG_NAME, tag)
                        if heading.text and heading.text.strip() and VENUE_NAME not in heading.text:
                            tournament_name = heading.text.strip()
                            break
                    except:
                        continue
                
                # Strategy 2: Look for elements with 'title' class
                if not tournament_name:
                    try:
                        title_elem = card.find_element(By.CSS_SELECTOR, "[class*='title'], [class*='Title'], [class*='name'], [class*='Name']")
                        if title_elem.text and title_elem.text.strip():
                            tournament_name = title_elem.text.strip()
                    except:
                        pass
                
                # Strategy 3: Look for first meaningful text line
                if not tournament_name:
                    lines = card_text.split('\n')
                    for line in lines:
                        line = line.strip()
                        if (line and 
                            len(line) > 5 and 
                            VENUE_NAME not in line and
                            VENUE_CITY not in line and
                            not re.match(r'^\d{4}/\d{2}/\d{2}', line) and
                            'Showing tournaments' not in line):
                            tournament_name = line
                            break
                
                if not tournament_name:
                    tournament_name = f"Tournament at {VENUE_NAME}"
                
                # Extract date
                date_match = re.search(r'(\d{4}/\d{2}/\d{2})', card_text)
                tournament_date = date_match.group(1) if date_match else None
                
                # Extract time
                start_time_str = None
                priority_patterns = [
                    (r'(?:Tournament\s+)?Start[s]?[:\s]+(\d{1,2}(?::\d{2})?\s*[AP]\.?M\.?)', 'Tournament Start'),
                    (r'(?:Play\s+)?Start[s]?[:\s]+(\d{1,2}(?::\d{2})?\s*[AP]\.?M\.?)', 'Play Start'),
                    (r'Start\s+Time[:\s]+(\d{1,2}(?::\d{2})?\s*[AP]\.?M\.?)', 'Start Time'),
                ]
                
                for pattern, label in priority_patterns:
                    time_match = re.search(pattern, card_text, re.IGNORECASE)
                    if time_match:
                        start_time_str = time_match.group(1).strip()
                        break
                
                start_time = parse_time_string(start_time_str) if start_time_str else None
                
                # Extract status and player count
                actual_status = "Unknown"
                player_count = 0
                
                player_match = re.search(r'(\d+)\s+Players?', card_text, re.IGNORECASE)
                if player_match:
                    player_count = int(player_match.group(1))
                
                # Extract entry fee
                entry_fee = 15  # default
                fee_patterns = [
                    r'\$(\d+)(?:\.\d+)?\s*(?:entry|buy-in|fee)',
                    r'(?:entry|buy-in|fee)[:\s]*\$(\d+)',
                ]
                for pattern in fee_patterns:
                    fee_match = re.search(pattern, card_text, re.IGNORECASE)
                    if fee_match:
                        entry_fee = int(fee_match.group(1))
                        break

                # Check for explicit status keywords
                status_indicators = {
                    "In Progress": ["In Progress", "Live", "Active", "Playing"],
                    "Upcoming": ["Upcoming", "Scheduled", "Future"],
                    "Completed": ["Completed", "Finished", "Final", "Ended"]
                }
                
                for status, keywords in status_indicators.items():
                    if any(keyword in card_text for keyword in keywords):
                        actual_status = status
                        break
                
                # If no explicit keyword, infer from context
                if actual_status == "Unknown":
                    completion_match = re.search(r'(\d+)%\s*Complete', card_text, re.IGNORECASE)
                    if completion_match:
                        completion_pct = int(completion_match.group(1))
                        
                        if completion_pct == 100:
                            actual_status = "Completed"
                        elif completion_pct == 0:
                            if player_count > 0:
                                actual_status = "In Progress"
                            else:
                                actual_status = "Upcoming"
                        elif completion_pct > 0 and completion_pct < 100:
                            actual_status = "In Progress"
                    else:
                        if player_count > 0:
                            actual_status = "In Progress"
                        elif tournament_date:
                            today = datetime.date.today()
                            today_str = today.strftime("%Y/%m/%d")
                            
                            if tournament_date == today_str:
                                actual_status = "Upcoming"
                            elif tournament_date < today_str:
                                actual_status = "Completed"
                
                # Get tournament URL
                tournament_url = None
                try:
                    link_element = card.find_element(By.CSS_SELECTOR, "a[href*='/tournaments/']")
                    tournament_url = link_element.get_attribute('href')
                except:
                    if tournament_date and tournament_name:
                        date_no_slashes = tournament_date.replace('/', '')
                        name_for_url = tournament_name
                        name_for_url = re.sub(r'^\d{4}/\d{2}/\d{2}\s+', '', name_for_url)
                        name_slug = re.sub(r'[^a-z0-9-]', '', name_for_url.lower().replace(' ', '-'))
                        name_slug = re.sub(r'-+', '-', name_slug).strip('-')
                        tournament_url = f"https://digitalpool.com/tournaments/{date_no_slashes}-{name_slug}/"
                
                tournament_info = {
                    'name': tournament_name,
                    'venue': f"{VENUE_NAME}, {VENUE_CITY}",
                    'date': tournament_date,
                    'start_time': start_time_str,
                    'start_time_parsed': start_time.strftime("%H:%M") if start_time else None,
                    'status': actual_status,
                    'player_count': player_count,
                    'entry_fee': entry_fee,
                    'url': tournament_url,
                    'found_at': datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                }
                
                tournaments.append(tournament_info)
                log(f"✓ Successfully extracted tournament info")
                log(f"  Name: {tournament_name}")
                log(f"  Date: {tournament_date}")
                log(f"  Status: {actual_status}")
                
            except Exception as e:
                log(f"Error parsing tournament card {idx}: {e}")
                continue
        
        if not tournaments:
            log("✗ No tournaments found for Hilliard location")
        else:
            log(f"\n✓ Found {len(tournaments)} tournament(s) total")
        
        return tournaments
        
    except Exception as e:
        log(f"Error searching tournaments: {e}")
        import traceback
        traceback.print_exc()
        return []


def get_all_todays_tournaments():
    """Get all tournaments at Bankshot for today"""
    driver = None
    
    try:
        log("="*60)
        log("Searching for ALL Bankshot tournaments today...")
        log("="*60)
        
        driver = setup_driver(headless=True)
        driver.get("https://www.digitalpool.com/tournaments")
        
        log("Waiting for page to load...")
        try:
            WebDriverWait(driver, 20).until(
                EC.presence_of_element_located((By.CSS_SELECTOR, "input"))
            )
            log("✓ Page loaded")
        except TimeoutException:
            log("✗ Page load timeout")
            return []
        
        time.sleep(3)
        
        # Search for tournaments
        all_tournaments = search_tournaments_on_page(driver)
        
        if not all_tournaments:
            log("No tournaments found")
            return []
        
        # Filter to today's date
        today = datetime.date.today()
        today_str = today.strftime("%Y/%m/%d")
        
        todays_tournaments = [t for t in all_tournaments if t['date'] == today_str]
        
        log(f"\nFound {len(todays_tournaments)} tournament(s) for today ({today_str})")
        
        for t in todays_tournaments:
            log(f"  Tournament: {t['name']}")
            log(f"  Start time: {t['start_time']}")
            log(f"  Status: {t['status']}")
        
        return todays_tournaments
        
    except Exception as e:
        log(f"Error: {e}")
        return []
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass


def determine_which_tournament_to_display(tournaments):
    """
    Smart logic to determine which tournament to display
    """
    if not tournaments:
        return None
    
    log("\n" + "="*60)
    log("DETERMINING WHICH TOURNAMENT TO DISPLAY")
    log("="*60)
    
    # Check for any "In Progress" tournaments
    in_progress = [t for t in tournaments if t['status'] == 'In Progress']
    
    if in_progress:
        log(f"Found {len(in_progress)} tournament(s) in progress")
        
        if len(in_progress) > 1:
            sorted_in_progress = sorted(in_progress, 
                                       key=lambda x: x['start_time_parsed'] if x['start_time_parsed'] else "00:00",
                                       reverse=True)
            selected = sorted_in_progress[0]
            log(f"Multiple in progress - selecting latest: {selected['name']}")
        else:
            selected = in_progress[0]
        
        return selected
    
    log("No tournaments in progress")
    not_completed = [t for t in tournaments if t['status'] != 'Completed']
    
    if not not_completed:
        return None
    
    sorted_tournaments = sorted(not_completed,
                                key=lambda x: x['start_time_parsed'] if x['start_time_parsed'] else "00:00")
    
    selected = sorted_tournaments[0]
    log(f"Selecting first scheduled tournament: {selected['name']}")
    
    return selected


def check_previous_tournament_still_active():
    """Check if we were displaying a tournament that's still in progress - ACTUALLY VERIFY STATUS"""
    driver = None
    try:
        with open(DATA_FILE, 'r') as f:
            prev_data = json.load(f)
        
        if prev_data.get('display_tournament') and prev_data.get('status') == 'In Progress':
            tournament_date = prev_data.get('date')
            tournament_url = prev_data.get('tournament_url')
            
            if tournament_date and tournament_url:
                prev_date = datetime.datetime.strptime(tournament_date, "%Y/%m/%d").date()
                today = datetime.date.today()
                
                if prev_date < today:
                    log(f"Previous tournament from {tournament_date} was 'In Progress' - verifying actual status...")
                    
                    # ACTUALLY FETCH THE TOURNAMENT PAGE TO CHECK STATUS
                    try:
                        driver = setup_driver(headless=True)
                        driver.get(tournament_url)
                        time.sleep(3)
                        
                        page_text = driver.page_source
                        
                        # Check for 100% completion
                        if '100% Complete' in page_text or '100%' in page_text:
                            log("✓ Tournament is 100% complete - stopping display")
                            prev_data['status'] = 'Completed'
                            prev_data['display_tournament'] = False
                            return prev_data
                        else:
                            log("⚠ Tournament still not 100% complete - continuing to display")
                            return prev_data
                            
                    except Exception as e:
                        log(f"Error fetching tournament status: {e}")
                        # If we can't fetch, assume it's completed after midnight
                        log("Assuming tournament completed (couldn't verify)")
                        prev_data['status'] = 'Completed'
                        prev_data['display_tournament'] = False
                        return prev_data
                    finally:
                        if driver:
                            try:
                                driver.quit()
                            except:
                                pass
        
        return None
    except Exception as e:
        log(f"Error checking previous tournament: {e}")
        return None

def save_tournament_data(tournament):
    """Save tournament data to JSON files"""
    if not tournament:
        output_data = {
            'tournament_name': 'No tournaments to display',
            'tournament_url': None,
            'venue': None,
            'date': None,
            'start_time': None,
            'status': None,
            'entry_fee': 15,
            'payout_data': None,
            'last_updated': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'display_tournament': False
        }
    else:
        payout_data = 'payouts15.json'
        if tournament['name']:
            if '8-ball' in tournament['name'].lower():
                payout_data = 'payouts20.json'
        
        should_display = (tournament['status'] in ['In Progress', 'Upcoming'])
        
        output_data = {
            'tournament_name': tournament['name'],
            'tournament_url': tournament['url'],
            'venue': tournament['venue'],
            'date': tournament['date'],
            'start_time': tournament['start_time'],
            'status': tournament['status'],
            'player_count': tournament.get('player_count', 0),
            'entry_fee': tournament.get('entry_fee', 15),
            'payout_data': payout_data,
            'last_updated': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'display_tournament': should_display
        }
        
        log(f"\nTournament to display: {tournament['name']}")
        log(f"Status: {tournament['status']}")
        log(f"Display flag: {should_display}")
    
    # Save to both locations
    for file_path in [DATA_FILE, DATA_FILE_BACKUP]:
        try:
            with open(file_path, 'w') as f:
                json.dump(output_data, f, indent=2)
            log(f"✓ Saved to {file_path}")
        except Exception as e:
            log(f"✗ Error saving to {file_path}: {e}")


def main():
    """Main execution"""
    log("\n" + "="*60)
    log("BANKSHOT BILLIARDS TOURNAMENT MONITOR - MULTI-TOURNAMENT")
    log("="*60)
    
    # Check if previous tournament might still be active
    prev_tournament_data = check_previous_tournament_still_active()
    
    if prev_tournament_data:
        log("Using previous tournament data (after-midnight scenario)")
        try:
            with open(DATA_FILE, 'w') as f:
                json.dump(prev_tournament_data, f, indent=2)
            with open(DATA_FILE_BACKUP, 'w') as f:
                json.dump(prev_tournament_data, f, indent=2)
        except Exception as e:
            log(f"✗ Error saving: {e}")
        
        log("\n" + "="*60)
        log("MONITOR COMPLETED")
        log("="*60)
        sys.exit(0 if prev_tournament_data.get('display_tournament') else 1)
    
    # Get all today's tournaments
    tournaments = get_all_todays_tournaments()
    
    # Determine which one to display
    selected_tournament = determine_which_tournament_to_display(tournaments)
    
    # Save results
    save_tournament_data(selected_tournament)
    
    log("\n" + "="*60)
    log("MONITOR COMPLETED")
    log("="*60)
    
    sys.exit(0 if selected_tournament else 1)


if __name__ == "__main__":
    main()
