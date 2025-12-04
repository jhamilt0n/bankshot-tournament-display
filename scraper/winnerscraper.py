#!/usr/bin/env python3
"""
Digital Pool Tournament Winner Scraper
Based on working bankshot_monitor_multi.py
Extracts top 3 finishers from completed Bankshot Billiards tournaments
"""

import datetime
import time
import json
import sys
import re
import logging
import random
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.keys import Keys


# Configuration
VENUE_NAME = "Bankshot Billiards"
VENUE_CITY = "Hilliard"


def setup_logging():
    """Configure logging"""
    logger = logging.getLogger()
    logger.setLevel(logging.INFO)
    logger.handlers = []
    
    console_handler = logging.StreamHandler()
    formatter = logging.Formatter('[%(asctime)s] %(message)s', datefmt='%Y-%m-%d %H:%M:%S')
    console_handler.setFormatter(formatter)
    logger.addHandler(console_handler)
    
    return logger


logger = setup_logging()


def log(message):
    logging.info(message)


def human_delay(min_seconds=1, max_seconds=3):
    """Add random delay to simulate human behavior"""
    delay = random.uniform(min_seconds, max_seconds)
    time.sleep(delay)


def simulate_human_scrolling(driver):
    """Simulate human-like scrolling behavior"""
    try:
        total_height = driver.execute_script("return document.body.scrollHeight")
        viewport_height = driver.execute_script("return window.innerHeight")
        
        current_position = 0
        scroll_increment = viewport_height // 3
        
        while current_position < total_height:
            scroll_by = random.randint(scroll_increment, scroll_increment * 2)
            current_position += scroll_by
            driver.execute_script(f"window.scrollTo(0, {current_position});")
            time.sleep(random.uniform(0.3, 0.8))
            
            if random.random() < 0.2:
                back_scroll = random.randint(50, 150)
                current_position -= back_scroll
                driver.execute_script(f"window.scrollTo(0, {current_position});")
                time.sleep(random.uniform(0.2, 0.5))
        
        driver.execute_script("window.scrollTo(0, 0);")
        time.sleep(random.uniform(0.5, 1.0))
    except Exception:
        pass


def setup_driver(headless=True):
    """Set up Chrome driver with anti-detection measures"""
    chrome_options = Options()
    
    if headless:
        chrome_options.add_argument('--headless=new')
    
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--start-maximized')
    chrome_options.add_argument('--disable-extensions')
    
    width = random.randint(1920, 1930)
    height = random.randint(1080, 1090)
    chrome_options.add_argument(f'--window-size={width},{height}')
    
    chrome_options.add_argument('--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
    chrome_options.add_argument('--disable-blink-features=AutomationControlled')
    chrome_options.add_argument('--lang=en-US')
    
    chrome_options.add_experimental_option("excludeSwitches", ["enable-automation", "enable-logging"])
    chrome_options.add_experimental_option('useAutomationExtension', False)
    
    prefs = {
        "profile.default_content_setting_values.notifications": 2,
        "credentials_enable_service": False,
        "profile.password_manager_enabled": False,
    }
    chrome_options.add_experimental_option("prefs", prefs)
    
    try:
        # Try different chromedriver locations
        chromedriver_paths = [
            '/usr/bin/chromedriver',
            '/usr/local/bin/chromedriver',
            'chromedriver',
        ]
        
        driver = None
        for path in chromedriver_paths:
            try:
                service = Service(executable_path=path)
                driver = webdriver.Chrome(service=service, options=chrome_options)
                break
            except Exception:
                continue
        
        if not driver:
            # Let Selenium find chromedriver automatically
            driver = webdriver.Chrome(options=chrome_options)
        
        # Anti-detection JavaScript
        driver.execute_cdp_cmd('Network.setUserAgentOverride', {
            "userAgent": 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        })
        
        stealth_js = """
        Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
        Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3, 4, 5] });
        Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
        window.chrome = { runtime: {} };
        """
        driver.execute_script(stealth_js)
        
        return driver
    except Exception as e:
        log(f"Error setting up ChromeDriver: {e}")
        raise


def extract_date_from_text(text):
    """Extract date from text, handling multiple formats"""
    if not text:
        return None
    
    # Try YYYY/MM/DD or YYYY/M/D format (1 or 2 digit month/day)
    match = re.search(r'(\d{4})/(\d{1,2})/(\d{1,2})', text)
    if match:
        year = match.group(1)
        month = int(match.group(2))
        day = int(match.group(3))
        return f"{year}/{month:02d}/{day:02d}"
    
    # Try YYYY-MM-DD or YYYY-M-D format
    match = re.search(r'(\d{4})-(\d{1,2})-(\d{1,2})', text)
    if match:
        year = match.group(1)
        month = int(match.group(2))
        day = int(match.group(3))
        return f"{year}/{month:02d}/{day:02d}"
    
    # Try YYYYMMDD format (8 digits)
    match = re.search(r'(\d{8})(?:\s|/|-|$)', text)
    if match:
        date_str = match.group(1)
        return f"{date_str[:4]}/{date_str[4:6]}/{date_str[6:8]}"
    
    # Try YYYYMMD or YYYYMDD format (7 digits - Digital Pool format without leading zeros)
    match = re.search(r'(\d{7})(?:\s|/|-|$)', text)
    if match:
        date_str = match.group(1)
        # Could be YYYYMMD (month has 2 digits, day has 1) or YYYYMDD (month has 1 digit, day has 2)
        year = date_str[:4]
        rest = date_str[4:]  # 3 digits for month+day
        
        # Try to parse intelligently - if rest starts with 1, could be month 1, 10, 11, or 12
        if rest[0] == '1' and int(rest[1:]) <= 31:
            # Could be month 1X with day Y, or month 1 with day XY
            # Check if it makes sense as month 10, 11, or 12
            if rest[1] in '012' and int(rest[2]) <= 9:
                month = rest[:2]
                day = rest[2]
            else:
                month = rest[0]
                day = rest[1:]
        else:
            month = rest[0]
            day = rest[1:]
        
        return f"{year}/{int(month):02d}/{int(day):02d}"
    
    return None


def format_date_for_url(date_str):
    """Format date for Digital Pool URL (no leading zeros): YYYY/MM/DD -> YYYYMD"""
    if not date_str:
        return None
    
    try:
        # Parse the date
        parts = date_str.split('/')
        if len(parts) == 3:
            year = parts[0]
            month = str(int(parts[1]))  # Remove leading zero
            day = str(int(parts[2]))    # Remove leading zero
            return f"{year}{month}{day}"
    except:
        pass
    
    # Fallback: just remove slashes
    return date_str.replace('/', '')


def is_valid_player_name(name):
    """Check if a string looks like a valid player name (not a table header or metadata)"""
    if not name or len(name) < 2:
        return False
    
    name_lower = name.lower().strip()
    
    # Common table headers and non-name strings to filter out
    invalid_names = [
        'name', 'player', 'description', 'start date', 'end date', 'date',
        'time', 'status', 'place', 'rank', 'position', 'score', 'points',
        'wins', 'losses', 'matches', 'games', 'rating', 'fargo', 'handicap',
        'entry', 'fee', 'payout', 'prize', 'total', 'amount', 'action',
        'details', 'view', 'edit', 'delete', 'bracket', 'standings',
        'tournament', 'event', 'venue', 'location', 'format', 'type',
        'round', 'match', 'table', 'seed', 'bye', 'forfeit', 'dq',
        'n/a', 'tbd', 'unknown', 'pending', 'complete', 'in progress',
    ]
    
    # Check against invalid names
    if name_lower in invalid_names:
        return False
    
    # Filter out entry fees like "$15 Entry", "$20", etc.
    if '$' in name or 'entry' in name_lower:
        return False
    
    # Filter out dates/times - various formats
    # "Thu, Dec 4, 2025", "12:04 AM", "2025/12/3", etc.
    if re.search(r'\d{4}[/-]\d{1,2}[/-]\d{1,2}', name):  # 2025/12/3 or 2025-12-03
        return False
    if re.search(r'\d{1,2}:\d{2}\s*(AM|PM|am|pm|UTC)', name):  # 12:04 AM, 12:04 PM (UTC)
        return False
    if re.search(r'(Mon|Tue|Wed|Thu|Fri|Sat|Sun),?\s+\w+\s+\d', name, re.IGNORECASE):  # Thu, Dec 4
        return False
    if re.search(r'(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d', name, re.IGNORECASE):
        return False
    if re.search(r'\d{1,2}(st|nd|rd|th)\s+\d{4}', name):  # 4th 2025
        return False
    
    # Filter out tournament names (contain "tournament", "night", "ball", etc.)
    tournament_keywords = ['tournament', 'night', '8-ball', '9-ball', '10-ball', 
                          'eight ball', 'nine ball', 'ten ball', 'billiards',
                          'championship', 'league', 'weekly', 'monday', 'tuesday',
                          'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
    for keyword in tournament_keywords:
        if keyword in name_lower:
            return False
    
    # Must contain at least one letter
    if not re.search(r'[a-zA-Z]', name):
        return False
    
    # Shouldn't be all numbers/symbols
    if re.match(r'^[\d\$\.,%\-\+\#\@\!\?\(\)]+$', name):
        return False
    
    # Should look like a name (at least one capital letter or proper format)
    # Names typically have format like "John Smith" or "J. Smith"
    if not re.search(r'[A-Z]', name):
        return False
    
    # Filter out very long strings (names are usually < 30 chars)
    if len(name) > 35:
        return False
    
    # Filter out strings with too many numbers (player names rarely have numbers)
    digit_count = sum(c.isdigit() for c in name)
    if digit_count > 2:
        return False
    
    return True


def extract_winners_from_card_text(card_text):
    """Extract top 3 winners directly from the tournament card text"""
    top_3 = []
    found_split = False
    
    log(f"  Extracting winners from card text...")
    
    # Look for patterns like "Name - 1st" or "Name - 1st (split)"
    # Pattern handles names like "Tom Carlisle", "Matt Fitch Jr", "Craig Frye Jr"
    
    # Find all 1st place winners (could be multiple if split)
    # Name pattern: Capitalized word(s), optionally with Jr/Sr/III/etc
    name_pattern = r'([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)*(?:\s+(?:Jr|Sr|II|III|IV))?)'
    
    first_place_pattern = name_pattern + r'\s*-\s*1st(\s*\(split\))?'
    first_matches = re.findall(first_place_pattern, card_text)
    
    log(f"    1st place matches: {first_matches}")
    
    for match in first_matches:
        name = match[0].strip()
        is_split = bool(match[1])
        if is_split:
            found_split = True
        
        # Clean up the name - remove any newlines and take last part if "Complete" is in there
        name = name.replace('\n', ' ').strip()
        if 'Complete' in name:
            # Take the part after "Complete"
            parts = name.split('Complete')
            name = parts[-1].strip()
        
        # Also handle case where name starts with newline content
        if '\n' in name:
            name = name.split('\n')[-1].strip()
        
        if name and is_valid_player_name(name):
            top_3.append({"place": 1, "name": name, "split": is_split})
            log(f"    ✓ 1st place: {name} (split={is_split})")
    
    # Find 2nd place (only if no split for 1st)
    if not found_split:
        second_pattern = name_pattern + r'\s*-\s*2nd'
        second_match = re.search(second_pattern, card_text)
        if second_match:
            name = second_match.group(1).strip()
            name = name.replace('\n', ' ').strip()
            if 'Complete' in name:
                parts = name.split('Complete')
                name = parts[-1].strip()
            if name and is_valid_player_name(name):
                top_3.append({"place": 2, "name": name})
                log(f"    ✓ 2nd place: {name}")
    
    # Find 3rd place
    third_pattern = name_pattern + r'\s*-\s*3rd'
    third_match = re.search(third_pattern, card_text)
    if third_match:
        name = third_match.group(1).strip()
        name = name.replace('\n', ' ').strip()
        if 'Complete' in name:
            parts = name.split('Complete')
            name = parts[-1].strip()
        if name and is_valid_player_name(name):
            top_3.append({"place": 3, "name": name})
            log(f"    ✓ 3rd place: {name}")
    
    # Sort by place
    top_3.sort(key=lambda x: (x['place'], x.get('name', '')))
    
    return top_3


def get_top_3_from_tournament(driver, tournament_url):
    """Extract top 3 finishers from a tournament page - FALLBACK only"""
    # This is now just a fallback if card text extraction fails
    log(f"  Fallback: fetching standings from: {tournament_url}")
    
    try:
        driver.get(tournament_url)
        human_delay(3, 4)
        
        # Just return empty - card text extraction should have worked
        log("  Fallback extraction not implemented - using card text instead")
        return []
        
    except Exception as e:
        log(f"Error in fallback extraction: {e}")
        return []


def search_tournaments(driver):
    """Search for Bankshot Billiards tournaments"""
    tournaments = []
    
    try:
        log(f"Searching for: {VENUE_NAME}")
        
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
        human_delay(0.3, 0.7)
        search_input.clear()
        human_delay(0.2, 0.5)
        
        for char in VENUE_NAME:
            search_input.send_keys(char)
            time.sleep(random.uniform(0.05, 0.15))
        
        human_delay(0.5, 1.2)
        search_input.send_keys(Keys.ENTER)
        
        log("Waiting for search results...")
        human_delay(4, 6)
        
        # Scroll multiple times to load all results
        for scroll_round in range(3):
            simulate_human_scrolling(driver)
            human_delay(1, 2)
        
        # Find tournament cards using multiple methods
        card_selectors = [
            ".ant-card",
            "[class*='tournament']",
            "[class*='TournamentCard']",
            "[class*='card']",
        ]
        
        tournament_cards = []
        for selector in card_selectors:
            try:
                cards = driver.find_elements(By.CSS_SELECTOR, selector)
                if cards:
                    log(f"Found {len(cards)} elements with selector: {selector}")
                    tournament_cards = cards
                    break
            except Exception:
                continue
        
        if not tournament_cards:
            all_divs = driver.find_elements(By.TAG_NAME, "div")
            tournament_cards = [div for div in all_divs 
                              if VENUE_NAME in div.text and (
                                  re.search(r'\d{4}/\d{2}/\d{2}', div.text) or
                                  re.search(r'\d{4}-\d{2}-\d{2}', div.text)
                              )]
        
        log(f"Processing {len(tournament_cards)} potential tournament cards")
        
        # DEBUG: Print all card text snippets to see what we're finding
        for idx, card in enumerate(tournament_cards[:15]):  # Check first 15 cards
            try:
                card_text = card.text
                
                # Log every card that mentions our venue
                if VENUE_NAME in card_text:
                    log(f"\n{'='*50}")
                    log(f"DEBUG Card {idx} contains '{VENUE_NAME}'")
                    log(f"Card text (first 400 chars):")
                    log(card_text[:400])
                    log(f"{'='*50}")
                    
                    # Check completion status
                    has_100_pct = '100%' in card_text
                    has_completed = 'Completed' in card_text or 'Complete' in card_text
                    log(f"  Has '100%': {has_100_pct}")
                    log(f"  Has 'Completed': {has_completed}")
                    
                    # Extract date for debugging
                    debug_date = extract_date_from_text(card_text)
                    log(f"  Extracted date: {debug_date}")
            except Exception as e:
                log(f"Error reading card {idx}: {e}")
        
        log(f"\n{'='*50}")
        log("Now processing cards for completed tournaments...")
        log(f"{'='*50}\n")
        
        # Get today's date for comparison
        from datetime import datetime, timedelta
        today = datetime.now().date()
        
        for idx, card in enumerate(tournament_cards):
            try:
                card_text = card.text
                
                if VENUE_NAME not in card_text or VENUE_CITY not in card_text:
                    continue
                
                # Check if COMPLETED - must be 100% complete or explicitly marked completed
                is_completed = False
                
                # Look for explicit "100%" or "Completed"
                if '100%' in card_text:
                    is_completed = True
                    log(f"  Card {idx}: Found '100%' - marking as completed")
                elif 'Completed' in card_text or 'Complete' in card_text:
                    is_completed = True
                    log(f"  Card {idx}: Found 'Completed' - marking as completed")
                
                # DON'T mark as completed just because percentage is high - must be 100%
                
                if not is_completed:
                    log(f"Skipping card {idx} - not 100% completed")
                    continue
                
                log(f"\n{'='*40}")
                log(f"Found completed tournament in card {idx}")
                log(f"Card text preview: {card_text[:200]}...")
                
                tournament_name = None
                for tag in ['h1', 'h2', 'h3', 'h4', 'h5']:
                    try:
                        heading = card.find_element(By.TAG_NAME, tag)
                        if heading.text and heading.text.strip() and VENUE_NAME not in heading.text:
                            tournament_name = heading.text.strip()
                            break
                    except Exception:
                        continue
                
                if not tournament_name:
                    lines = card_text.split('\n')
                    for line in lines:
                        line = line.strip()
                        if (line and len(line) > 5 and VENUE_NAME not in line and
                            VENUE_CITY not in line and not re.match(r'^\d{4}[/-]\d{2}[/-]\d{2}$', line) and
                            '%' not in line and 'Players' not in line):
                            tournament_name = line
                            break
                
                if not tournament_name:
                    tournament_name = f"Tournament at {VENUE_NAME}"
                
                tournament_date = extract_date_from_text(card_text)
                
                # Get tournament URL
                tournament_url = None
                try:
                    link_element = card.find_element(By.CSS_SELECTOR, "a[href*='/tournaments/']")
                    tournament_url = link_element.get_attribute('href')
                    log(f"  ✓ Found URL in card: {tournament_url}")
                except Exception as e:
                    log(f"  Could not find tournament link with CSS selector: {e}")
                    try:
                        # Try finding any link in the card
                        links = card.find_elements(By.TAG_NAME, "a")
                        log(f"  Found {len(links)} links in card")
                        for link in links:
                            href = link.get_attribute('href')
                            if href and '/tournaments/' in href:
                                tournament_url = href
                                log(f"  ✓ Found URL via link scan: {tournament_url}")
                                break
                    except Exception as e2:
                        log(f"  Link scan failed: {e2}")
                
                if not tournament_url and tournament_date and tournament_name:
                    # Remove date prefix from name - handle both 2-digit and 1-digit month/day
                    name_for_slug = re.sub(r'^\d{4}[/-]\d{1,2}[/-]\d{1,2}\s*', '', tournament_name)
                    name_for_slug = re.sub(r'^\d{8}\s*', '', name_for_slug)  # YYYYMMDD
                    name_for_slug = re.sub(r'^\d{7}\s*', '', name_for_slug)  # YYYYMMD or YYYYMDD
                    name_for_slug = re.sub(r'^\d{6}\s*', '', name_for_slug)  # YYYYMD
                    # Use format without leading zeros for Digital Pool URLs
                    date_for_url = format_date_for_url(tournament_date)
                    name_slug = re.sub(r'[^a-z0-9-]', '', name_for_slug.lower().replace(' ', '-'))
                    name_slug = re.sub(r'-+', '-', name_slug).strip('-')
                    tournament_url = f"https://digitalpool.com/tournaments/{date_for_url}-{name_slug}/"
                    log(f"  Constructed URL: {tournament_url}")
                
                # Extract winners directly from card text!
                top_3 = extract_winners_from_card_text(card_text)
                if top_3:
                    log(f"  ✓ Extracted {len(top_3)} winners from card: {[p['name'] for p in top_3]}")
                else:
                    log(f"  ⚠ No winners found in card text")
                
                tournaments.append({
                    'name': tournament_name,
                    'date': tournament_date,
                    'url': tournament_url,
                    'status': 'Completed',
                    'top_3': top_3  # Store extracted winners
                })
                
                log(f"  Name: {tournament_name}")
                log(f"  Date: {tournament_date}")
                log(f"  URL: {tournament_url}")
                
            except Exception as e:
                log(f"Error parsing card {idx}: {e}")
                continue
        
        log(f"\n{'='*40}")
        log(f"Total tournaments found: {len(tournaments)}")
        for t in tournaments:
            log(f"  - {t['date']}: {t['name']}")
        
        # Filter out future tournaments - only keep past/today completed ones
        from datetime import datetime
        from zoneinfo import ZoneInfo
        
        # Use Eastern Time for date comparison
        eastern = ZoneInfo('America/New_York')
        today = datetime.now(eastern).date()
        log(f"Today's date (Eastern): {today}")
        
        past_tournaments = []
        for t in tournaments:
            if t['date']:
                try:
                    t_date = datetime.strptime(t['date'], "%Y/%m/%d").date()
                    log(f"  Comparing tournament date {t_date} vs today {today}")
                    if t_date <= today:
                        past_tournaments.append(t)
                        log(f"  ✓ Including (past/today): {t['date']} - {t['name']}")
                    else:
                        log(f"  ✗ Excluding (future): {t['date']} - {t['name']}")
                except Exception as e:
                    log(f"  ⚠ Date parse error for {t['date']}: {e} - including anyway")
                    past_tournaments.append(t)
            else:
                log(f"  ⚠ No date for {t['name']} - including anyway")
                past_tournaments.append(t)
        
        log(f"\nFiltered to {len(past_tournaments)} past/current tournaments")
        
        return past_tournaments
        
    except Exception as e:
        log(f"Error searching tournaments: {e}")
        import traceback
        traceback.print_exc()
        return []


def main():
    """Main execution"""
    log("=" * 60)
    log("BANKSHOT BILLIARDS WINNER SCRAPER")
    log("=" * 60)
    
    driver = None
    results = {
        "scrape_date": datetime.datetime.now().isoformat(),
        "search_term": VENUE_NAME,
        "most_recent_date": None,
        "tournaments": []
    }
    
    try:
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
            results["error"] = "Page load timeout"
            return results
        
        human_delay(2, 4)
        
        tournaments = search_tournaments(driver)
        
        if not tournaments:
            log("No completed tournaments found")
            results["error"] = "No completed tournaments found"
            return results
        
        log(f"\nFound {len(tournaments)} completed tournament(s)")
        
        tournaments_with_dates = [t for t in tournaments if t['date']]
        tournaments_with_dates.sort(key=lambda x: x['date'], reverse=True)
        
        if not tournaments_with_dates:
            log("No tournaments with valid dates")
            results["error"] = "No tournaments with valid dates"
            return results
        
        most_recent_date = tournaments_with_dates[0]['date']
        results["most_recent_date"] = most_recent_date
        
        most_recent_tournaments = [t for t in tournaments_with_dates if t['date'] == most_recent_date]
        
        log(f"\nMost recent date: {most_recent_date}")
        log(f"Tournaments on that date: {len(most_recent_tournaments)}")
        
        for tournament in most_recent_tournaments:
            # Use pre-extracted winners from card text (already stored in tournament['top_3'])
            top_3 = tournament.get('top_3', [])
            
            if top_3:
                log(f"✓ Using {len(top_3)} winners from card: {[p['name'] for p in top_3]}")
            else:
                log(f"⚠ No winners extracted for {tournament['name']}")
                # Fallback to page extraction only if card text failed
                if tournament['url']:
                    top_3 = get_top_3_from_tournament(driver, tournament['url'])
                    tournament['top_3'] = top_3
            
            results["tournaments"].append({
                "name": tournament['name'],
                "date": tournament['date'],
                "url": tournament['url'],
                "top_3": tournament.get('top_3', [])
            })
        
        return results
        
    except Exception as e:
        log(f"Error: {e}")
        import traceback
        traceback.print_exc()
        results["error"] = str(e)
        return results
    finally:
        if driver:
            try:
                driver.quit()
            except Exception:
                pass


def generate_html_display(results, output_path):
    """Generate HTML display page"""
    tournaments = results.get("tournaments", [])
    most_recent_date = results.get("most_recent_date", "")
    
    if most_recent_date:
        try:
            dt = datetime.datetime.strptime(most_recent_date, "%Y/%m/%d")
            formatted_date = dt.strftime("%B %d, %Y")
        except:
            formatted_date = most_recent_date
    else:
        formatted_date = "Recent"
    
    num_tournaments = len(tournaments) if tournaments else 1
    column_width = 100 / num_tournaments if num_tournaments > 0 else 100
    
    tournament_columns = ""
    
    if not tournaments:
        tournament_columns = """
        <div class="tournament-column" style="width: 100%;">
            <div class="tournament-name">No Recent Tournaments Found</div>
            <div class="winner-card">
                <div class="winner-name">Check back soon!</div>
            </div>
        </div>
        """
    else:
        for tournament in tournaments:
            name = tournament.get("name", "Tournament")
            # Clean up tournament name - remove date prefix if present
            name = re.sub(r'^\d{4}[/-]\d{1,2}[/-]\d{1,2}\s*', '', name)
            
            top_3 = tournament.get("top_3", [])
            
            winners_html = ""
            
            # Check if there's a split (multiple 1st place winners)
            has_split = any(p.get('split') for p in top_3)
            
            if has_split:
                # For splits: show both 1st place winners, then 3rd
                first_place_winners = [p for p in top_3 if p.get('place') == 1]
                third_place = next((p for p in top_3 if p.get('place') == 3), None)
                
                # First 1st place winner
                if len(first_place_winners) >= 1:
                    winners_html += f"""
                    <div class="winner-card first">
                        <div class="place-badge">1st Place (Split)</div>
                        <div class="winner-name">{first_place_winners[0].get('name', 'TBD')}</div>
                    </div>
                    """
                
                # Second 1st place winner
                if len(first_place_winners) >= 2:
                    winners_html += f"""
                    <div class="winner-card first">
                        <div class="place-badge">1st Place (Split)</div>
                        <div class="winner-name">{first_place_winners[1].get('name', 'TBD')}</div>
                    </div>
                    """
                
                # 3rd place
                if third_place:
                    winners_html += f"""
                    <div class="winner-card third">
                        <div class="place-badge">3rd Place</div>
                        <div class="winner-name">{third_place.get('name', 'TBD')}</div>
                    </div>
                    """
            else:
                # Normal case: 1st, 2nd, 3rd
                place_labels = ["1st Place", "2nd Place", "3rd Place"]
                place_classes = ["first", "second", "third"]
                
                for i in range(3):
                    place_label = place_labels[i]
                    place_class = place_classes[i]
                    
                    # Find player with this place
                    player = next((p for p in top_3 if p.get('place') == i + 1), None)
                    player_name = player.get('name', 'TBD') if player else 'TBD'
                    
                    winners_html += f"""
                    <div class="winner-card {place_class}">
                        <div class="place-badge">{place_label}</div>
                        <div class="winner-name">{player_name}</div>
                    </div>
                    """
            
            tournament_columns += f"""
            <div class="tournament-column" style="width: {column_width}%;">
                <div class="tournament-name">{name}</div>
                <div class="winners-container">
                    {winners_html}
                </div>
            </div>
            """
    
    html_content = f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bankshot Billiards - Tournament Winners</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Oswald:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * {{ margin: 0; padding: 0; box-sizing: border-box; }}
        html, body {{ width: 100%; height: 100%; overflow: hidden; }}
        body {{
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(180deg, #1a472a 0%, #0d3320 100%);
            color: white;
            display: flex;
            flex-direction: column;
            aspect-ratio: 16 / 9;
            max-height: 100vh;
            max-width: 177.78vh;
            margin: 0 auto;
        }}
        .header {{ text-align: center; padding: 2vh 2vw; flex-shrink: 0; }}
        .congratulations {{
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1.5rem, 4vw, 3.5rem);
            letter-spacing: 3px;
            color: #ffd700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 0.5vh;
        }}
        .date-line {{
            font-family: 'Oswald', sans-serif;
            font-size: clamp(0.8rem, 1.5vw, 1.3rem);
            opacity: 0.9;
            letter-spacing: 2px;
        }}
        .tournaments-wrapper {{
            flex: 1;
            display: flex;
            padding: 1vh 2vw 2vh 2vw;
            gap: 2vw;
            min-height: 0;
        }}
        .tournament-column {{
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
        }}
        .tournament-name {{
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1rem, 2.5vw, 2rem);
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 2vh;
            color: #90EE90;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            padding: 0 1vw;
            word-wrap: break-word;
            max-width: 100%;
        }}
        .winners-container {{
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2vh;
            width: 100%;
            padding: 0 1vw;
        }}
        .winner-card {{
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 2vh 2vw;
            text-align: center;
        }}
        .winner-card.first {{
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.3) 0%, rgba(255, 215, 0, 0.1) 100%);
            border-color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }}
        .winner-card.second {{
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.3) 0%, rgba(192, 192, 192, 0.1) 100%);
            border-color: #c0c0c0;
            box-shadow: 0 0 15px rgba(192, 192, 192, 0.2);
        }}
        .winner-card.third {{
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.3) 0%, rgba(205, 127, 50, 0.1) 100%);
            border-color: #cd7f32;
            box-shadow: 0 0 15px rgba(205, 127, 50, 0.2);
        }}
        .place-badge {{
            font-family: 'Oswald', sans-serif;
            font-size: clamp(0.7rem, 1.2vw, 1rem);
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.9;
            margin-bottom: 0.5vh;
        }}
        .winner-card.first .place-badge {{ color: #ffd700; }}
        .winner-card.second .place-badge {{ color: #c0c0c0; }}
        .winner-card.third .place-badge {{ color: #cd7f32; }}
        .winner-name {{
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(1.2rem, 2.5vw, 2.2rem);
            letter-spacing: 1px;
        }}
        .footer {{
            text-align: center;
            padding: 1vh 2vw;
            font-size: clamp(0.6rem, 1vw, 0.8rem);
            opacity: 0.6;
            flex-shrink: 0;
        }}
    </style>
</head>
<body>
    <div class="header">
        <div class="congratulations">🏆 Congratulations to Our Most Recent Top Finishers! 🏆</div>
        <div class="date-line">{formatted_date}</div>
    </div>
    <div class="tournaments-wrapper">
        {tournament_columns}
    </div>
    <div class="footer">
        Bankshot Billiards &bull; Updated {datetime.datetime.now().strftime("%m/%d/%Y %I:%M %p")}
    </div>
</body>
</html>
'''
    
    with open(output_path, "w") as f:
        f.write(html_content)
    
    log(f"✓ HTML display generated: {output_path}")


if __name__ == "__main__":
    results = main()
    
    output_dir = os.environ.get("OUTPUT_DIR", ".")
    
    with open(f"{output_dir}/results.json", "w") as f:
        json.dump(results, f, indent=2)
    log(f"✓ Saved results.json")
    
    print("\n" + "=" * 60)
    print("BANKSHOT BILLIARDS TOURNAMENT RESULTS")
    print("=" * 60)
    print(f"Scraped at: {results['scrape_date']}")
    if results.get('most_recent_date'):
        print(f"Most recent date: {results['most_recent_date']}")
    
    if results.get('tournaments'):
        for i, t in enumerate(results['tournaments'], 1):
            print(f"\nTournament {i}: {t['name']}")
            print(f"  Date: {t['date']}")
            print(f"  URL: {t['url']}")
            print("  Top 3:")
            for p in t.get('top_3', []):
                print(f"    {p['place']}. {p['name']}")
    else:
        print("No tournaments found")
        if results.get('error'):
            print(f"Error: {results['error']}")
    
    print("=" * 60)
    
    with open(f"{output_dir}/results.txt", "w") as f:
        f.write(f"Scraped at: {results['scrape_date']}\n")
        if results.get('most_recent_date'):
            f.write(f"Most recent date: {results['most_recent_date']}\n\n")
        for t in results.get('tournaments', []):
            f.write(f"Tournament: {t['name']}\n")
            f.write(f"Date: {t['date']}\n")
            for p in t.get('top_3', []):
                f.write(f"  {p['place']}. {p['name']}\n")
            f.write("\n")
    
    generate_html_display(results, f"{output_dir}/winners_display.html")
    
    log("\n✓ Scraper completed")
