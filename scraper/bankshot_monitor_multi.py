#!/usr/bin/env python3
"""
Bankshot Billiards Tournament Monitor - Multi-Tournament Version
ENHANCED: Gets entry fee DIRECTLY from Digital Pool (tr[18]/td[2])
CLEANED: Removed unused payout_data legacy code
FIXED: URL construction removes date prefix to prevent duplicates
"""

import datetime
import time
import json
import sys
import re
import logging
import random
from logging.handlers import RotatingFileHandler
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from selenium.webdriver.common.action_chains import ActionChains


# Configuration
VENUE_NAME = "Bankshot Billiards"
VENUE_CITY = "Hilliard"
DATA_FILE = "/home/pi/tournament_data.json"
DATA_FILE_BACKUP = "/var/www/html/tournament_data.json"
LOG_FILE = "/home/pi/logs/tournament_monitor.log"


def setup_logging():
    """Configure rotating log handler with graceful fallback"""
    logger = logging.getLogger()
    logger.setLevel(logging.INFO)
    logger.handlers = []
    
    # Create log directory if it doesn't exist
    import os
    log_dir = os.path.dirname(LOG_FILE)
    if log_dir and not os.path.exists(log_dir):
        try:
            os.makedirs(log_dir, exist_ok=True)
        except Exception as e:
            print(f"Warning: Could not create log directory {log_dir}: {e}")
    
    # Try to set up file logging, fall back to console-only if it fails
    try:
        handler = RotatingFileHandler(LOG_FILE, maxBytes=5*1024*1024, backupCount=5)
        formatter = logging.Formatter('[%(asctime)s] %(message)s', datefmt='%Y-%m-%d %H:%M:%S')
        handler.setFormatter(formatter)
        logger.addHandler(handler)
    except Exception as e:
        print(f"Warning: Could not set up file logging: {e}")
        print("Continuing with console-only logging")
    
    # Always add console handler
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


def simulate_human_mouse_movement(driver):
    """Simulate random mouse movements like a human"""
    try:
        action = ActionChains(driver)
        # Get window size
        window_size = driver.get_window_size()
        width = window_size['width']
        height = window_size['height']
        
        # Random movements (3-5 moves)
        num_moves = random.randint(3, 5)
        for _ in range(num_moves):
            x = random.randint(100, width - 100)
            y = random.randint(100, height - 100)
            action.move_by_offset(x - width//2, y - height//2)
            action.perform()
            time.sleep(random.uniform(0.1, 0.3))
            action = ActionChains(driver)  # Reset chain
    except Exception:
        pass  # Silently fail - not critical


def simulate_human_scrolling(driver):
    """Simulate human-like scrolling behavior"""
    try:
        # Get page height
        total_height = driver.execute_script("return document.body.scrollHeight")
        viewport_height = driver.execute_script("return window.innerHeight")
        
        # Scroll down in chunks with random pauses
        current_position = 0
        scroll_increment = viewport_height // 3
        
        while current_position < total_height:
            # Random scroll amount (between 1/4 and 1/2 viewport)
            scroll_by = random.randint(scroll_increment, scroll_increment * 2)
            current_position += scroll_by
            
            driver.execute_script(f"window.scrollTo(0, {current_position});")
            
            # Random pause between scrolls (like human reading)
            time.sleep(random.uniform(0.3, 0.8))
            
            # Sometimes scroll back up a bit (like re-reading)
            if random.random() < 0.2:  # 20% chance
                back_scroll = random.randint(50, 150)
                current_position -= back_scroll
                driver.execute_script(f"window.scrollTo(0, {current_position});")
                time.sleep(random.uniform(0.2, 0.5))
        
        # Scroll back to top naturally
        scroll_speed = random.randint(3, 6)
        for _ in range(scroll_speed):
            current_position -= total_height // scroll_speed
            driver.execute_script(f"window.scrollTo(0, {max(0, current_position)});")
            time.sleep(random.uniform(0.1, 0.3))
        
        driver.execute_script("window.scrollTo(0, 0);")
        time.sleep(random.uniform(0.5, 1.0))
    except Exception:
        pass  # Silently fail - not critical


def setup_driver(headless=True):
    chrome_options = Options()
    
    # Make headless mode less detectable
    if headless:
        chrome_options.add_argument('--headless=new')  # Use new headless mode
    
    # Basic Chrome settings
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--start-maximized')
    chrome_options.add_argument('--disable-extensions')
    
    # Randomize window size slightly to avoid fingerprinting
    width = random.randint(1920, 1930)
    height = random.randint(1080, 1090)
    chrome_options.add_argument(f'--window-size={width},{height}')
    
    # Make it look like a real browser
    chrome_options.add_argument('--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
    chrome_options.add_argument('--disable-blink-features=AutomationControlled')
    
    # Set timezone to America/New_York
    chrome_options.add_argument('--lang=en-US')
    
    # Disable automation flags
    chrome_options.add_experimental_option("excludeSwitches", ["enable-automation", "enable-logging"])
    chrome_options.add_experimental_option('useAutomationExtension', False)
    
    # Additional stealth settings
    chrome_options.add_argument('--disable-web-security')
    chrome_options.add_argument('--disable-features=IsolateOrigins,site-per-process')
    chrome_options.add_argument('--disable-site-isolation-trials')
    
    # Set realistic prefs
    prefs = {
        "profile.default_content_setting_values.notifications": 2,
        "credentials_enable_service": False,
        "profile.password_manager_enabled": False,
        "profile.default_content_settings.popups": 0,
        "directory_upgrade": True,
        "plugins.always_open_pdf_externally": True
    }
    chrome_options.add_experimental_option("prefs", prefs)
    
    try:
        service = Service(executable_path='/usr/bin/chromedriver')
        driver = webdriver.Chrome(service=service, options=chrome_options)
        
        # Override navigator.webdriver flag
        driver.execute_cdp_cmd('Network.setUserAgentOverride', {
            "userAgent": 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        })
        
        # Set timezone to Eastern Time
        driver.execute_cdp_cmd('Emulation.setTimezoneOverride', {'timezoneId': 'America/New_York'})
        
        # Execute stealth JavaScript to hide automation
        stealth_js = """
        Object.defineProperty(navigator, 'webdriver', {
            get: () => undefined
        });
        
        // Overwrite the `plugins` property to use a custom getter
        Object.defineProperty(navigator, 'plugins', {
            get: () => [1, 2, 3, 4, 5]
        });
        
        // Overwrite the `languages` property to use a custom getter
        Object.defineProperty(navigator, 'languages', {
            get: () => ['en-US', 'en']
        });
        
        // Overwrite the `chrome` property
        window.chrome = {
            runtime: {}
        };
        
        // Pass the Permissions Test
        const originalQuery = window.navigator.permissions.query;
        window.navigator.permissions.query = (parameters) => (
            parameters.name === 'notifications' ?
                Promise.resolve({ state: Notification.permission }) :
                originalQuery(parameters)
        );
        """
        driver.execute_script(stealth_js)
        
        return driver
    except Exception as e:
        log(f"Error setting up ChromeDriver: {e}")
        raise


def get_tournament_details_from_page(driver, tournament_url, player_count):
    """Get tournament details with DIRECT entry fee extraction from Digital Pool"""
    try:
        if not tournament_url:
            return None
        
        log(f"Fetching details from: {tournament_url}")
        driver.get(tournament_url)
        
        # Random delay instead of fixed 3 seconds
        human_delay(2, 4)
        
        # Simulate human scrolling behavior
        simulate_human_scrolling(driver)
        
        # Random mouse movements
        simulate_human_mouse_movement(driver)
        
        details = {
            'start_time': None,
            'date': None,
            'entry_fee': 15,
            'format_type': 'Singles',
            'has_digital_pool_payouts': False,
            'payouts': {}
        }
        
        # Extract START TIME (tr[3]/td[2]) - But trust card date over detail date
        xpath_start_time = "/html/body/div[1]/div/div/section/section/section/main/div/div[2]/div[2]/div/div/div/div/div/div[2]/div/div[1]/div[1]/div[2]/div/div/div/div/div/div/div/div[2]/div/div/div/div/table/tbody/tr[3]/td[2]"
        try:
            elem = driver.find_element(By.XPATH, xpath_start_time)
            raw_time = elem.text.strip()
            
            log(f"DEBUG: Time field text: {repr(raw_time)}")
            
            # Check if we have local time (with timezone like America/New_York)
            has_local_time = 'America/' in raw_time or 'US/' in raw_time
            has_only_utc = '(UTC)' in raw_time and not has_local_time
            
            if has_only_utc:
                log("⚠ Only UTC time found - Digital Pool not showing local time to scraper")
                log("⚠ Skipping time extraction - will use card date instead")
                # Don't extract time or date from detail page
            else:
                # We have local time - extract it
                if raw_time:
                    # Split by newline and look for non-UTC line
                    lines = [line.strip() for line in raw_time.split('\n') if line.strip()]
                    local_time_line = lines[0] if lines else ""
                    
                    for line in lines:
                        if '(UTC)' not in line and line:
                            local_time_line = line
                            break
                    
                    details['start_time'] = clean_start_time_string(local_time_line)
                    log(f"✓ Start time: {details['start_time']}")
                    
                    # Extract date
                    date_match = re.search(r'(\w+,\s+\w+\s+\d+,\s+\d{4})', local_time_line)
                    if date_match:
                        try:
                            parsed = datetime.datetime.strptime(date_match.group(1), "%a, %b %d, %Y")
                            details['date'] = parsed.strftime("%Y/%m/%d")
                            log(f"✓ Date: {details['date']}")
                        except Exception:
                            pass
        except NoSuchElementException:
            log("⚠ Start time not found")
        
        # Extract ENTRY FEE DIRECTLY from Digital Pool (tr[18]/td[2])
        xpath_entry_fee = "/html/body/div[1]/div/div/section/section/section/main/div/div[2]/div[2]/div/div/div/div/div/div[2]/div/div[1]/div[1]/div[2]/div/div/div/div/div/div/div/div[2]/div/div/div/div/table/tbody/tr[18]/td[2]"
        try:
            elem = driver.find_element(By.XPATH, xpath_entry_fee)
            fee_text = elem.text.strip()
            
            fee_match = re.search(r'\$?([\d,]+(?:\.\d{2})?)', fee_text)
            if fee_match:
                fee_value = fee_match.group(1).replace(',', '')
                details['entry_fee'] = int(float(fee_value))
                log(f"✓ Entry fee from Digital Pool tr[18]/td[2]: ${details['entry_fee']}")
        except NoSuchElementException:
            log("⚠ Entry fee field not found at tr[18]/td[2]")
            
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
                    log(f"⚠ Calculated entry fee from pot: ${calculated}")
            except Exception:
                log("⚠ Could not calculate from pot, using default $15")
        
        # Extract FORMAT TYPE
        xpath_format = "//*[@id='rc-tabs-1-panel-details']/div/div/table/tbody/tr[5]/td[2]"
        try:
            elem = driver.find_element(By.XPATH, xpath_format)
            format_text = elem.text.strip()
            details['format_type'] = format_text if format_text else 'Singles'
            log(f"✓ Format: {details['format_type']}")
        except Exception:
            pass
        
        # Extract PAYOUTS from Digital Pool (if available)
        xpath_first = "/html/body/div[1]/div/div/section/section/section/main/div/div[2]/div[2]/div/div/div/div/div/div[2]/div/div[1]/div[2]/div[2]/table/tbody/tr[2]/td[2]"
        try:
            elem = driver.find_element(By.XPATH, xpath_first)
            first_text = elem.text.strip()
            
            if first_text and first_text != '$0' and first_text != '$0.00':
                details['has_digital_pool_payouts'] = True
                details['payouts']['1st'] = first_text
                log(f"✓ Digital Pool payout 1st: {first_text}")
        except Exception:
            pass
        
        return details
        
    except Exception as e:
        log(f"Error fetching details: {e}")
        import traceback
        traceback.print_exc()
        return None


def parse_time_string(time_str):
    """Parse time strings like '7:00 PM' and return datetime.time object"""
    try:
        time_str = time_str.strip()
        for fmt in ['%I:%M %p', '%I:%M%p', '%I %p', '%I%p']:
            try:
                parsed = datetime.datetime.strptime(time_str, fmt)
                return parsed.time()
            except Exception:
                continue
        return None
    except Exception:
        return None


def clean_start_time_string(raw):
    """Extract clean time from verbose formats"""
    if not raw:
        return None
    
    match = re.search(r'(\d{1,2}:\d{2}\s*[AP]\.?M\.?)', raw, re.IGNORECASE)
    if match:
        clean = match.group(1).strip()
        clean = re.sub(r'([AP])\.?M\.?', r'\1M', clean, flags=re.IGNORECASE)
        clean = re.sub(r'(\d)([AP]M)', r'\1 \2', clean, flags=re.IGNORECASE)
        return clean
    return raw


def search_tournaments_on_page(driver):
    """Search for Bankshot tournaments on the current page"""
    tournaments = []
    
    try:
        search_term = VENUE_NAME
        log(f"Searching for: {search_term}")
        
        # Find search input
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
        
        # Human-like interaction with search box
        search_input.click()
        human_delay(0.3, 0.7)
        search_input.clear()
        human_delay(0.2, 0.5)
        
        # Type like a human with random delays between characters
        for char in search_term:
            search_input.send_keys(char)
            time.sleep(random.uniform(0.05, 0.15))  # Randomized typing speed
        
        human_delay(0.5, 1.2)  # Pause before hitting enter (like thinking)
        
        from selenium.webdriver.common.keys import Keys
        search_input.send_keys(Keys.ENTER)
        
        log("Waiting for search results...")
        human_delay(3, 5)  # Random wait for results
        
        # Human-like scrolling to load all content
        simulate_human_scrolling(driver)
        
        # Small pause at top after scrolling
        human_delay(1, 2)
        
        # Find tournament cards
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
            except Exception:
                continue
        
        if not tournament_cards:
            log("Trying alternative approach...")
            all_divs = driver.find_elements(By.TAG_NAME, "div")
            tournament_cards = [div for div in all_divs 
                              if VENUE_NAME in div.text and re.search(r'\d{4}/\d{2}/\d{2}', div.text)]
            log(f"Found {len(tournament_cards)} potential tournament divs")
        
        log(f"Processing {len(tournament_cards)} potential tournament cards")
        
        for idx, card in enumerate(tournament_cards):
            try:
                card_text = card.text
                
                # Check if this card is for Bankshot Billiards in Hilliard
                if VENUE_NAME not in card_text or VENUE_CITY not in card_text:
                    continue
                
                log(f"\n{'='*50}")
                log(f"Card {idx} - Found matching venue!")
                log(f"{'='*50}")
                
                # Extract tournament name
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
                    try:
                        title_elem = card.find_element(By.CSS_SELECTOR, "[class*='title'], [class*='Title']")
                        if title_elem.text:
                            tournament_name = title_elem.text.strip()
                    except Exception:
                        pass
                
                if not tournament_name:
                    lines = card_text.split('\n')
                    for line in lines:
                        line = line.strip()
                        if (line and len(line) > 5 and VENUE_NAME not in line and
                            VENUE_CITY not in line and not re.match(r'^\d{4}/\d{2}/\d{2}', line)):
                            tournament_name = line
                            break
                
                if not tournament_name:
                    tournament_name = f"Tournament at {VENUE_NAME}"
                
                # Extract date
                date_match = re.search(r'(\d{4}/\d{2}/\d{2})', card_text)
                tournament_date = date_match.group(1) if date_match else None
                
                # Extract player count
                player_match = re.search(r'(\d+)\s+Players?', card_text, re.IGNORECASE)
                player_count = int(player_match.group(1)) if player_match else 0
                
                # Get tournament URL
                tournament_url = None
                try:
                    link_element = card.find_element(By.CSS_SELECTOR, "a[href*='/tournaments/']")
                    tournament_url = link_element.get_attribute('href')
                except Exception:
                    # Fallback: construct URL from tournament name and date
                    if tournament_date and tournament_name:
                        # Remove any date prefix from tournament name
                        name_for_slug = tournament_name
                        name_for_slug = re.sub(r'^\d{4}[/-]\d{2}[/-]\d{2}\s*', '', name_for_slug)
                        name_for_slug = re.sub(r'^\d{8}\s*', '', name_for_slug)
                        
                        date_no_slashes = tournament_date.replace('/', '').replace('-', '')
                        name_slug = re.sub(r'[^a-z0-9-]', '', name_for_slug.lower().replace(' ', '-'))
                        name_slug = re.sub(r'-+', '-', name_slug).strip('-')
                        tournament_url = f"https://digitalpool.com/tournaments/{date_no_slashes}-{name_slug}/"
                
                # Get comprehensive details from detail page
                start_time_str = None
                actual_date = tournament_date
                entry_fee = 15
                format_type = 'Singles'
                has_digital_pool_payouts = False
                digital_pool_payouts = {}
                
                if tournament_url:
                    details = get_tournament_details_from_page(driver, tournament_url, player_count)
                    if details:
                        if details['start_time']:
                            start_time_str = details['start_time']
                        if details['date']:
                            actual_date = details['date']
                        entry_fee = details['entry_fee']
                        format_type = details['format_type']
                        has_digital_pool_payouts = details['has_digital_pool_payouts']
                        digital_pool_payouts = details['payouts']
                
                start_time = parse_time_string(start_time_str) if start_time_str else None
                
                # Extract status
                actual_status = "Unknown"
                status_indicators = {
                    "In Progress": ["In Progress", "Live", "Active", "Playing"],
                    "Upcoming": ["Upcoming", "Scheduled", "Future"],
                    "Completed": ["Completed", "Finished", "Final", "Ended"]
                }
                
                for status, keywords in status_indicators.items():
                    if any(keyword in card_text for keyword in keywords):
                        actual_status = status
                        break
                
                if actual_status == "Unknown":
                    completion_match = re.search(r'(\d+)%\s*Complete', card_text, re.IGNORECASE)
                    if completion_match:
                        completion_pct = int(completion_match.group(1))
                        if completion_pct == 100:
                            actual_status = "Completed"
                        elif completion_pct == 0:
                            actual_status = "In Progress" if player_count > 0 else "Upcoming"
                        else:
                            actual_status = "In Progress"
                    else:
                        actual_status = "In Progress" if player_count > 0 else "Upcoming"
                
                tournament_info = {
                    'name': tournament_name,
                    'venue': f"{VENUE_NAME}, {VENUE_CITY}",
                    'date': actual_date,
                    'start_time': start_time_str,
                    'start_time_parsed': start_time.strftime("%H:%M") if start_time else None,
                    'status': actual_status,
                    'player_count': player_count,
                    'entry_fee': entry_fee,
                    'format_type': format_type,
                    'has_digital_pool_payouts': has_digital_pool_payouts,
                    'digital_pool_payouts': digital_pool_payouts,
                    'url': tournament_url,
                    'found_at': datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                }
                
                tournaments.append(tournament_info)
                log(f"✓ Tournament extracted")
                log(f"  Name: {tournament_name}")
                log(f"  Date: {actual_date}")
                log(f"  Entry Fee: ${entry_fee} (from Digital Pool)")
                log(f"  Status: {actual_status}")
                
            except Exception as e:
                log(f"Error parsing card {idx}: {e}")
                continue
        
        if not tournaments:
            log("✗ No tournaments found")
        else:
            log(f"\n✓ Found {len(tournaments)} tournament(s)")
        
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
        log("Searching for Bankshot tournaments today...")
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
        
        # Add human-like delay after page load
        human_delay(2, 4)
        
        # Simulate some initial browsing behavior
        simulate_human_mouse_movement(driver)
        human_delay(1, 2)
        
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
            log(f"  Entry fee: ${t['entry_fee']}")
            log(f"  Status: {t['status']}")
        
        return todays_tournaments
        
    except Exception as e:
        log(f"Error: {e}")
        return []
    finally:
        if driver:
            try:
                driver.quit()
            except Exception:
                pass


def determine_which_tournament_to_display(tournaments):
    """Smart logic to determine which tournament to display"""
    if not tournaments:
        return None
    
    log("\n" + "="*60)
    log("DETERMINING TOURNAMENT TO DISPLAY")
    log("="*60)
    
    # Check for "In Progress" tournaments
    in_progress = [t for t in tournaments if t['status'] == 'In Progress']
    
    if in_progress:
        log(f"Found {len(in_progress)} in progress")
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
    log(f"Selecting first scheduled: {selected['name']}")
    
    return selected


def check_previous_tournament_still_active():
    """Check if previous tournament is still in progress"""
    driver = None
    try:
        import os
        data_file = None
        for path in [DATA_FILE, 'tournament_data.json']:
            if os.path.exists(path):
                data_file = path
                break
        
        if not data_file:
            return None
            
        with open(data_file, 'r') as f:
            prev_data = json.load(f)
        
        if prev_data.get('display_tournament') and prev_data.get('status') == 'In Progress':
            tournament_date = prev_data.get('date')
            tournament_url = prev_data.get('tournament_url')
            
            if tournament_date and tournament_url:
                prev_date = datetime.datetime.strptime(tournament_date, "%Y/%m/%d").date()
                today = datetime.date.today()
                
                if prev_date < today:
                    log(f"Previous tournament from {tournament_date} was 'In Progress' - verifying...")
                    
                    try:
                        driver = setup_driver(headless=True)
                        driver.get(tournament_url)
                        human_delay(2, 4)
                        
                        # Simulate human behavior
                        simulate_human_scrolling(driver)
                        
                        page_text = driver.page_source
                        
                        if '100% Complete' in page_text or '100%' in page_text:
                            log("✓ Tournament is 100% complete")
                            prev_data['status'] = 'Completed'
                            prev_data['display_tournament'] = False
                            return prev_data
                        else:
                            log("⚠ Tournament still not 100% complete")
                            return prev_data
                            
                    except Exception as e:
                        log(f"Error checking status: {e}")
                        prev_data['status'] = 'Completed'
                        prev_data['display_tournament'] = False
                        return prev_data
                    finally:
                        if driver:
                            try:
                                driver.quit()
                            except Exception:
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
            'format_type': 'Singles',
            'has_digital_pool_payouts': False,
            'digital_pool_payouts': {},
            'last_updated': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'display_tournament': False
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
            'entry_fee': tournament.get('entry_fee', 15),
            'format_type': tournament.get('format_type', 'Singles'),
            'has_digital_pool_payouts': tournament.get('has_digital_pool_payouts', False),
            'digital_pool_payouts': tournament.get('digital_pool_payouts', {}),
            'last_updated': datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'display_tournament': should_display
        }
        
        log(f"\nTournament to display: {tournament['name']}")
        log(f"Entry Fee: ${tournament.get('entry_fee', 15)} (from Digital Pool)")
        log(f"Status: {tournament['status']}")
        log(f"Display flag: {should_display}")
    
    # Save to multiple locations with directory creation
    import os
    for file_path in [DATA_FILE, DATA_FILE_BACKUP]:
        try:
            dir_path = os.path.dirname(file_path)
            if dir_path and not os.path.exists(dir_path):
                os.makedirs(dir_path, exist_ok=True)
            
            with open(file_path, 'w') as f:
                json.dump(output_data, f, indent=2)
            log(f"✓ Saved to {file_path}")
        except Exception as e:
            log(f"✗ Error saving to {file_path}: {e}")
    
    # Also save to current directory for GitHub Actions
    try:
        with open('tournament_data.json', 'w') as f:
            json.dump(output_data, f, indent=2)
        log(f"✓ Saved to tournament_data.json (current directory)")
    except Exception as e:
        log(f"✗ Error saving to current directory: {e}")


def main():
    """Main execution"""
    log("\n" + "="*60)
    log("BANKSHOT TOURNAMENT MONITOR")
    log("ENHANCED: Direct entry fee from Digital Pool")
    log("CLEANED: Removed unused payout_data field")
    log("="*60)
    
    # Check if previous tournament still active
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
