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
    
    match = re.search(r'(\d{4}/\d{2}/\d{2})', text)
    if match:
        return match.group(1)
    
    match = re.search(r'(\d{4}-\d{2}-\d{2})', text)
    if match:
        return match.group(1).replace('-', '/')
    
    match = re.search(r'(\d{8})(?:\s|/|-|$)', text)
    if match:
        date_str = match.group(1)
        return f"{date_str[:4]}/{date_str[4:6]}/{date_str[6:8]}"
    
    return None


def get_top_3_from_tournament(driver, tournament_url):
    """Extract top 3 finishers from a tournament page"""
    try:
        log(f"Fetching standings from: {tournament_url}")
        driver.get(tournament_url)
        human_delay(3, 5)
        simulate_human_scrolling(driver)
        
        top_3 = []
        
        # Try clicking on "Standings" tab if it exists
        try:
            standings_tab = driver.find_element(By.XPATH, "//div[contains(text(), 'Standings')]")
            standings_tab.click()
            human_delay(2, 3)
        except NoSuchElementException:
            pass
        
        # Method 1: Try to find standings table
        standings_xpaths = [
            "//table[contains(@class, 'standings')]//tbody//tr",
            "//div[contains(@class, 'standings')]//tr",
            "//div[contains(@class, 'ant-table')]//tbody//tr",
            "//table//tbody//tr",
        ]
        
        for xpath in standings_xpaths:
            try:
                rows = driver.find_elements(By.XPATH, xpath)
                if len(rows) >= 3:
                    for i, row in enumerate(rows[:3]):
                        try:
                            cells = row.find_elements(By.TAG_NAME, "td")
                            if cells:
                                player_name = None
                                for cell in cells[:3]:
                                    text = cell.text.strip()
                                    if text and not re.match(r'^[\d\$\.,%]+$', text):
                                        player_name = text
                                        break
                                
                                if player_name:
                                    player_name = re.sub(r'^\d+[\.\)]\s*', '', player_name)
                                    player_name = player_name.split('\n')[0].strip()
                                    
                                    if player_name and len(player_name) > 1:
                                        top_3.append({
                                            "place": i + 1,
                                            "name": player_name
                                        })
                        except Exception:
                            continue
                    
                    if len(top_3) >= 3:
                        log(f"✓ Found top 3 via table: {[p['name'] for p in top_3]}")
                        return top_3
            except Exception:
                continue
        
        # Method 2: Look for bracket/final results
        try:
            winner_selectors = [
                "[class*='winner']",
                "[class*='champion']",
                "[class*='first-place']",
                "[class*='1st']",
            ]
            
            for selector in winner_selectors:
                try:
                    winner = driver.find_element(By.CSS_SELECTOR, selector)
                    if winner.text.strip():
                        name = winner.text.strip().split('\n')[0]
                        if name and not re.match(r'^[\d\$\.,%]+$', name):
                            top_3.append({"place": 1, "name": name})
                            break
                except NoSuchElementException:
                    continue
        except Exception:
            pass
        
        # Method 3: Try to find from page text patterns
        if len(top_3) < 3:
            try:
                page_text = driver.find_element(By.TAG_NAME, "body").text
                
                place_patterns = [
                    (r'1st\s*(?:Place)?[:\s]+([A-Za-z][A-Za-z\s\.]+)', 1),
                    (r'2nd\s*(?:Place)?[:\s]+([A-Za-z][A-Za-z\s\.]+)', 2),
                    (r'3rd\s*(?:Place)?[:\s]+([A-Za-z][A-Za-z\s\.]+)', 3),
                    (r'First\s*(?:Place)?[:\s]+([A-Za-z][A-Za-z\s\.]+)', 1),
                    (r'Second\s*(?:Place)?[:\s]+([A-Za-z][A-Za-z\s\.]+)', 2),
                    (r'Third\s*(?:Place)?[:\s]+([A-Za-z][A-Za-z\s\.]+)', 3),
                ]
                
                for pattern, place in place_patterns:
                    if not any(p['place'] == place for p in top_3):
                        match = re.search(pattern, page_text, re.IGNORECASE)
                        if match:
                            name = match.group(1).strip()
                            if name and len(name) > 1:
                                top_3.append({"place": place, "name": name})
            except Exception:
                pass
        
        top_3.sort(key=lambda x: x['place'])
        
        if top_3:
            log(f"✓ Found {len(top_3)} finishers: {[p['name'] for p in top_3]}")
        else:
            log("✗ Could not extract standings")
        
        return top_3[:3]
        
    except Exception as e:
        log(f"Error extracting standings: {e}")
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
        human_delay(3, 5)
        simulate_human_scrolling(driver)
        human_delay(1, 2)
        
        card_selectors = [
            ".ant-card",
            "[class*='tournament']",
            "[class*='TournamentCard']",
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
        
        for idx, card in enumerate(tournament_cards):
            try:
                card_text = card.text
                
                if VENUE_NAME not in card_text or VENUE_CITY not in card_text:
                    continue
                
                is_completed = '100%' in card_text or 'Completed' in card_text
                
                if not is_completed:
                    continue
                
                log(f"\nFound completed tournament in card {idx}")
                
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
                            VENUE_CITY not in line and not re.match(r'^\d{4}[/-]\d{2}[/-]\d{2}', line)):
                            tournament_name = line
                            break
                
                if not tournament_name:
                    tournament_name = f"Tournament at {VENUE_NAME}"
                
                tournament_date = extract_date_from_text(card_text)
                
                tournament_url = None
                try:
                    link_element = card.find_element(By.CSS_SELECTOR, "a[href*='/tournaments/']")
                    tournament_url = link_element.get_attribute('href')
                except Exception:
                    if tournament_date and tournament_name:
                        name_for_slug = re.sub(r'^\d{4}[/-]\d{2}[/-]\d{2}\s*', '', tournament_name)
                        name_for_slug = re.sub(r'^\d{8}\s*', '', name_for_slug)
                        date_no_slashes = tournament_date.replace('/', '').replace('-', '')
                        name_slug = re.sub(r'[^a-z0-9-]', '', name_for_slug.lower().replace(' ', '-'))
                        name_slug = re.sub(r'-+', '-', name_slug).strip('-')
                        tournament_url = f"https://digitalpool.com/tournaments/{date_no_slashes}-{name_slug}/"
                
                tournaments.append({
                    'name': tournament_name,
                    'date': tournament_date,
                    'url': tournament_url,
                    'status': 'Completed'
                })
                
                log(f"  Name: {tournament_name}")
                log(f"  Date: {tournament_date}")
                log(f"  URL: {tournament_url}")
                
            except Exception as e:
                log(f"Error parsing card {idx}: {e}")
                continue
        
        return tournaments
        
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
            if tournament['url']:
                top_3 = get_top_3_from_tournament(driver, tournament['url'])
                tournament['top_3'] = top_3
            else:
                tournament['top_3'] = []
            
            results["tournaments"].append({
                "name": tournament['name'],
                "date": tournament['date'],
                "url": tournament['url'],
                "top_3": tournament['top_3']
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
            top_3 = tournament.get("top_3", [])
            
            winners_html = ""
            place_labels = ["1st Place", "2nd Place", "3rd Place"]
            place_classes = ["first", "second", "third"]
            
            for i in range(3):
                place_label = place_labels[i]
                place_class = place_classes[i]
                player_name = top_3[i]["name"] if i < len(top_3) else "TBD"
                
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
