#!/usr/bin/env python3
"""
DigitalPool Tournament Creator - Selenium Automation Script
============================================================
This script automates the creation of tournaments on DigitalPool.com

Usage:
    1. Fill out the form on create_tournament.html and click "Save as JSON"
    2. Run this script: python create_digitalpool_tournament.py tournament_data.json
    
    Or run without arguments to use interactive mode.

Requirements:
    pip install selenium webdriver-manager

Author: Bankshot Tournament Console
"""

import json
import sys
import time
import os
from datetime import datetime

try:
    from selenium import webdriver
    from selenium.webdriver.common.by import By
    from selenium.webdriver.common.keys import Keys
    from selenium.webdriver.support.ui import WebDriverWait, Select
    from selenium.webdriver.support import expected_conditions as EC
    from selenium.webdriver.chrome.service import Service
    from selenium.webdriver.chrome.options import Options
    from selenium.common.exceptions import TimeoutException, NoSuchElementException
except ImportError:
    print("ERROR: Selenium is not installed.")
    print("Please install it with: pip install selenium webdriver-manager")
    sys.exit(1)

try:
    from webdriver_manager.chrome import ChromeDriverManager
except ImportError:
    print("WARNING: webdriver-manager not installed. You may need to manage ChromeDriver manually.")
    ChromeDriverManager = None


class DigitalPoolAutomation:
    """Automates tournament creation on DigitalPool.com"""
    
    DIGITALPOOL_URL = "https://digitalpool.com"
    TOURNAMENT_BUILDER_URL = "https://digitalpool.com/tournament-builder"
    
    def __init__(self, headless=False, timeout=20):
        """
        Initialize the automation driver.
        
        Args:
            headless: Run browser in headless mode (no GUI)
            timeout: Default wait timeout in seconds
        """
        self.timeout = timeout
        self.driver = None
        self.headless = headless
        
    def setup_driver(self):
        """Configure and start the Chrome WebDriver."""
        options = Options()
        
        if self.headless:
            options.add_argument('--headless=new')
            
        options.add_argument('--no-sandbox')
        options.add_argument('--disable-dev-shm-usage')
        options.add_argument('--window-size=1920,1080')
        options.add_argument('--disable-gpu')
        options.add_argument('--disable-notifications')
        options.add_argument('--disable-software-rasterizer')
        
        # Raspberry Pi specific options
        options.add_argument('--disable-extensions')
        options.add_argument('--disable-infobars')
        options.add_argument('--remote-debugging-port=9222')
        
        # Avoid detection
        options.add_argument('--disable-blink-features=AutomationControlled')
        options.add_experimental_option('excludeSwitches', ['enable-automation'])
        options.add_experimental_option('useAutomationExtension', False)
        
        # For Raspberry Pi - use chromium-browser if chrome not found
        chrome_paths = [
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable'
        ]
        
        for chrome_path in chrome_paths:
            if os.path.exists(chrome_path):
                options.binary_location = chrome_path
                break
        
        try:
            if ChromeDriverManager:
                service = Service(ChromeDriverManager().install())
                self.driver = webdriver.Chrome(service=service, options=options)
            else:
                # Try to find chromedriver in common locations
                chromedriver_paths = [
                    '/usr/bin/chromedriver',
                    '/usr/local/bin/chromedriver',
                    '/usr/lib/chromium-browser/chromedriver'
                ]
                
                chromedriver_path = None
                for path in chromedriver_paths:
                    if os.path.exists(path):
                        chromedriver_path = path
                        break
                
                if chromedriver_path:
                    service = Service(chromedriver_path)
                    self.driver = webdriver.Chrome(service=service, options=options)
                else:
                    self.driver = webdriver.Chrome(options=options)
                    
        except Exception as e:
            print(f"ERROR: Could not start Chrome WebDriver: {e}")
            print("\nTroubleshooting:")
            print("  - Make sure Chrome/Chromium is installed: sudo apt install chromium-browser")
            print("  - Make sure ChromeDriver is installed: sudo apt install chromium-chromedriver")
            print("  - Or install webdriver-manager: pip install webdriver-manager")
            sys.exit(1)
            
        self.driver.implicitly_wait(10)
        self.wait = WebDriverWait(self.driver, self.timeout)
        
        # Execute script to hide webdriver flag
        self.driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
        
        print("✓ Chrome WebDriver initialized")
        
    def close(self):
        """Close the browser and cleanup."""
        if self.driver:
            self.driver.quit()
            print("✓ Browser closed")
            
    def wait_and_click(self, by, value, description="element"):
        """Wait for an element and click it."""
        try:
            element = self.wait.until(EC.element_to_be_clickable((by, value)))
            element.click()
            print(f"  ✓ Clicked {description}")
            return element
        except TimeoutException:
            print(f"  ✗ Timeout waiting for {description}")
            return None
            
    def wait_and_type(self, by, value, text, description="field", clear=True):
        """Wait for an input field and type text into it."""
        try:
            element = self.wait.until(EC.presence_of_element_located((by, value)))
            if clear:
                element.clear()
            element.send_keys(text)
            print(f"  ✓ Entered {description}")
            return element
        except TimeoutException:
            print(f"  ✗ Timeout waiting for {description}")
            return None
            
    def wait_and_select(self, by, value, option_text, description="dropdown"):
        """Wait for a select dropdown and choose an option."""
        try:
            element = self.wait.until(EC.presence_of_element_located((by, value)))
            select = Select(element)
            select.select_by_visible_text(option_text)
            print(f"  ✓ Selected '{option_text}' in {description}")
            return element
        except Exception as e:
            print(f"  ✗ Could not select in {description}: {e}")
            return None
            
    def login(self, email, password):
        """
        Log in to DigitalPool.
        
        Args:
            email: DigitalPool account email
            password: DigitalPool account password
        """
        print("\n[Step 1] Logging in to DigitalPool...")
        
        self.driver.get(self.DIGITALPOOL_URL)
        time.sleep(2)
        
        # Look for login button or link
        try:
            # Try clicking login/sign in button
            login_selectors = [
                "//a[contains(text(), 'Log In')]",
                "//a[contains(text(), 'Login')]",
                "//a[contains(text(), 'Sign In')]",
                "//button[contains(text(), 'Log In')]",
                "//a[contains(@href, 'login')]",
                "//a[contains(@href, 'signin')]"
            ]
            
            for selector in login_selectors:
                try:
                    login_btn = self.driver.find_element(By.XPATH, selector)
                    login_btn.click()
                    print("  ✓ Found and clicked login button")
                    break
                except NoSuchElementException:
                    continue
            
            time.sleep(2)
            
            # Enter email
            email_selectors = [
                (By.ID, "email"),
                (By.NAME, "email"),
                (By.XPATH, "//input[@type='email']"),
                (By.XPATH, "//input[contains(@placeholder, 'email')]"),
                (By.CSS_SELECTOR, "input[type='email']")
            ]
            
            email_entered = False
            for by, selector in email_selectors:
                try:
                    email_field = self.driver.find_element(by, selector)
                    email_field.clear()
                    email_field.send_keys(email)
                    email_entered = True
                    print("  ✓ Entered email")
                    break
                except NoSuchElementException:
                    continue
                    
            if not email_entered:
                print("  ✗ Could not find email field")
                return False
            
            # Enter password
            password_selectors = [
                (By.ID, "password"),
                (By.NAME, "password"),
                (By.XPATH, "//input[@type='password']"),
                (By.CSS_SELECTOR, "input[type='password']")
            ]
            
            password_entered = False
            for by, selector in password_selectors:
                try:
                    password_field = self.driver.find_element(by, selector)
                    password_field.clear()
                    password_field.send_keys(password)
                    password_entered = True
                    print("  ✓ Entered password")
                    break
                except NoSuchElementException:
                    continue
                    
            if not password_entered:
                print("  ✗ Could not find password field")
                return False
            
            # Submit login form
            time.sleep(1)
            submit_selectors = [
                "//button[@type='submit']",
                "//button[contains(text(), 'Log In')]",
                "//button[contains(text(), 'Login')]",
                "//button[contains(text(), 'Sign In')]",
                "//input[@type='submit']"
            ]
            
            for selector in submit_selectors:
                try:
                    submit_btn = self.driver.find_element(By.XPATH, selector)
                    submit_btn.click()
                    print("  ✓ Submitted login form")
                    break
                except NoSuchElementException:
                    continue
            
            # Wait for login to complete
            time.sleep(3)
            
            # Check if login was successful (look for tournament builder or user menu)
            if "tournament-builder" in self.driver.current_url or "dashboard" in self.driver.current_url:
                print("  ✓ Login successful!")
                return True
                
            # Try to navigate to tournament builder to verify login
            self.driver.get(self.TOURNAMENT_BUILDER_URL)
            time.sleep(2)
            
            print("  ✓ Login completed")
            return True
            
        except Exception as e:
            print(f"  ✗ Login error: {e}")
            return False
            
    def navigate_to_tournament_builder(self):
        """Navigate to the tournament builder page."""
        print("\n[Step 2] Opening Tournament Builder...")
        
        self.driver.get(self.TOURNAMENT_BUILDER_URL)
        time.sleep(3)
        print("  ✓ Navigated to Tournament Builder")
        
    def click_create_tournament(self):
        """Click the 'Create Tournament' button."""
        print("\n[Step 3] Starting new tournament...")
        
        create_selectors = [
            "//button[contains(text(), 'Create tournament')]",
            "//button[contains(text(), 'Create Tournament')]",
            "//a[contains(text(), 'Create tournament')]",
            "//button[contains(text(), 'New Tournament')]",
            "//*[contains(@class, 'create')]"
        ]
        
        for selector in create_selectors:
            try:
                create_btn = self.wait.until(EC.element_to_be_clickable((By.XPATH, selector)))
                create_btn.click()
                print("  ✓ Clicked 'Create Tournament'")
                time.sleep(2)
                return True
            except:
                continue
                
        print("  ✗ Could not find 'Create Tournament' button")
        return False
        
    def fill_tournament_details(self, data):
        """
        Fill in the tournament creation form.
        
        Args:
            data: Dictionary containing tournament details from JSON
        """
        print("\n[Step 4] Filling tournament details...")
        
        tournament = data.get('tournament', {})
        format_data = data.get('format', {})
        venue = data.get('venue', {})
        options = data.get('options', {})
        
        time.sleep(2)
        
        # Tournament Name
        if tournament.get('name'):
            name_selectors = [
                (By.ID, "name"),
                (By.NAME, "name"),
                (By.XPATH, "//input[contains(@placeholder, 'name')]"),
                (By.XPATH, "//input[contains(@id, 'tournament-name')]"),
                (By.XPATH, "//label[contains(text(), 'Name')]/following-sibling::input"),
                (By.XPATH, "//label[contains(text(), 'Name')]/..//input")
            ]
            
            for by, selector in name_selectors:
                try:
                    name_field = self.driver.find_element(by, selector)
                    name_field.clear()
                    name_field.send_keys(tournament['name'])
                    print(f"  ✓ Set tournament name: {tournament['name']}")
                    break
                except:
                    continue
        
        # Tournament Date
        if tournament.get('date'):
            date_selectors = [
                (By.ID, "date"),
                (By.NAME, "date"),
                (By.XPATH, "//input[@type='date']"),
                (By.XPATH, "//input[contains(@id, 'date')]")
            ]
            
            for by, selector in date_selectors:
                try:
                    date_field = self.driver.find_element(by, selector)
                    date_field.clear()
                    date_field.send_keys(tournament['date'])
                    print(f"  ✓ Set date: {tournament['date']}")
                    break
                except:
                    continue
        
        # Bracket Size
        if format_data.get('bracketSize'):
            try:
                # Look for bracket size dropdown or input
                size_value = format_data['bracketSize']
                print(f"  → Setting bracket size to {size_value}")
                # This might be a dropdown or buttons - adjust based on actual UI
            except:
                pass
        
        # Race To settings
        if format_data.get('raceToWinners'):
            print(f"  → Race to (winners): {format_data['raceToWinners']}")
            
        if format_data.get('raceToLosers'):
            print(f"  → Race to (losers): {format_data['raceToLosers']}")
        
        # Tournament Format
        if format_data.get('tournamentFormat'):
            format_map = {
                'double-elimination': 'Double Elimination',
                'single-elimination': 'Single Elimination',
                'round-robin': 'Round Robin',
                'modified-single': 'Single Modified',
                'multi-stage': 'Multi-stage'
            }
            format_text = format_map.get(format_data['tournamentFormat'], format_data['tournamentFormat'])
            print(f"  → Tournament format: {format_text}")
        
        # Access (Public/Private)
        if options.get('access'):
            access_text = 'Public' if options['access'] == 'public' else 'Private'
            print(f"  → Access: {access_text}")
        
        # Description
        if tournament.get('description'):
            desc_selectors = [
                (By.ID, "description"),
                (By.NAME, "description"),
                (By.XPATH, "//textarea[contains(@id, 'description')]"),
                (By.XPATH, "//textarea")
            ]
            
            for by, selector in desc_selectors:
                try:
                    desc_field = self.driver.find_element(by, selector)
                    desc_field.clear()
                    desc_field.send_keys(tournament['description'])
                    print("  ✓ Set description")
                    break
                except:
                    continue
        
        print("  ✓ Tournament details filled")
        return True
        
    def submit_tournament(self):
        """Submit/save the tournament."""
        print("\n[Step 5] Saving tournament...")
        
        save_selectors = [
            "//button[contains(text(), 'Save')]",
            "//button[contains(text(), 'Create')]",
            "//button[contains(text(), 'Next')]",
            "//button[@type='submit']"
        ]
        
        for selector in save_selectors:
            try:
                save_btn = self.driver.find_element(By.XPATH, selector)
                save_btn.click()
                print("  ✓ Clicked save/submit")
                time.sleep(3)
                return True
            except:
                continue
                
        print("  ! Could not find save button - tournament may need manual save")
        return False
        
    def take_screenshot(self, filename="screenshot.png"):
        """Take a screenshot of the current page."""
        self.driver.save_screenshot(filename)
        print(f"  ✓ Screenshot saved: {filename}")
        
    def create_tournament(self, data):
        """
        Complete workflow to create a tournament.
        
        Args:
            data: Dictionary containing all tournament data
        """
        try:
            self.setup_driver()
            
            # Extract credentials
            creds = data.get('credentials', {})
            email = creds.get('email')
            password = creds.get('password')
            
            if not email or not password:
                print("ERROR: Missing login credentials in data")
                return False
            
            # Login
            if not self.login(email, password):
                print("ERROR: Login failed")
                self.take_screenshot("login_error.png")
                return False
            
            # Navigate to tournament builder
            self.navigate_to_tournament_builder()
            
            # Click create tournament
            if not self.click_create_tournament():
                self.take_screenshot("create_button_error.png")
                return False
            
            # Fill in details
            self.fill_tournament_details(data)
            
            # Take screenshot before submitting
            self.take_screenshot("before_submit.png")
            
            # Submit
            self.submit_tournament()
            
            # Take final screenshot
            time.sleep(2)
            self.take_screenshot("tournament_created.png")
            
            print("\n" + "="*50)
            print("✓ TOURNAMENT CREATION COMPLETE!")
            print("="*50)
            print(f"Tournament: {data.get('tournament', {}).get('name', 'Unknown')}")
            print(f"Date: {data.get('tournament', {}).get('date', 'Unknown')}")
            print("\nCheck the screenshots for confirmation.")
            
            return True
            
        except Exception as e:
            print(f"\nERROR: {e}")
            self.take_screenshot("error.png")
            return False
            
        finally:
            # Keep browser open for 5 seconds to see result
            print("\nBrowser will close in 5 seconds...")
            time.sleep(5)
            self.close()


def load_tournament_data(filepath):
    """Load tournament data from a JSON file."""
    try:
        with open(filepath, 'r') as f:
            return json.load(f)
    except FileNotFoundError:
        print(f"ERROR: File not found: {filepath}")
        return None
    except json.JSONDecodeError as e:
        print(f"ERROR: Invalid JSON in {filepath}: {e}")
        return None


def run_automation(json_file, headless=True):
    """
    Run the automation with a JSON file.
    Can be called from other scripts (like tournament_server.py).
    
    Args:
        json_file: Path to the JSON file with tournament data
        headless: Run browser in headless mode (default True for server use)
    
    Returns:
        bool: True if successful, False otherwise
    """
    print(f"\n{'='*60}")
    print(f"Running DigitalPool Tournament Creator")
    print(f"JSON File: {json_file}")
    print(f"Headless: {headless}")
    print(f"{'='*60}\n")
    
    data = load_tournament_data(json_file)
    if not data:
        return False
    
    automation = DigitalPoolAutomation(headless=headless)
    return automation.create_tournament(data)


def interactive_mode():
    """Run in interactive mode - prompt for all values."""
    print("\n" + "="*50)
    print("INTERACTIVE MODE - Enter Tournament Details")
    print("="*50 + "\n")
    
    data = {
        'credentials': {},
        'tournament': {},
        'format': {},
        'venue': {},
        'options': {}
    }
    
    # Credentials
    data['credentials']['email'] = input("DigitalPool Email [bankshottablet@gmail.com]: ").strip() or "bankshottablet@gmail.com"
    data['credentials']['password'] = input("DigitalPool Password [Ab!2345678]: ").strip() or "Ab!2345678"
    
    print()
    
    # Tournament info
    data['tournament']['name'] = input("Tournament Name: ").strip()
    data['tournament']['date'] = input(f"Date (YYYY-MM-DD) [{datetime.now().strftime('%Y-%m-%d')}]: ").strip() or datetime.now().strftime('%Y-%m-%d')
    data['tournament']['time'] = input("Start Time (HH:MM) [19:00]: ").strip() or "19:00"
    data['tournament']['description'] = input("Description (optional): ").strip()
    
    print()
    
    # Format
    data['format']['gameType'] = input("Game Type [9-ball]: ").strip() or "9-ball"
    data['format']['tournamentFormat'] = input("Format (double-elimination/single-elimination) [double-elimination]: ").strip() or "double-elimination"
    data['format']['bracketSize'] = input("Bracket Size [32]: ").strip() or "32"
    data['format']['raceToWinners'] = input("Race To (Winners) [5]: ").strip() or "5"
    data['format']['raceToLosers'] = input("Race To (Losers) [4]: ").strip() or "4"
    
    print()
    
    # Venue
    data['venue']['name'] = input("Venue Name [Bankshot Billiards]: ").strip() or "Bankshot Billiards"
    
    # Options
    data['options']['access'] = input("Access (public/private) [public]: ").strip() or "public"
    
    return data


def main():
    print("""
╔══════════════════════════════════════════════════════════════╗
║     DigitalPool Tournament Creator - Selenium Automation     ║
║                   Bankshot Tournament Console                ║
╚══════════════════════════════════════════════════════════════╝
    """)
    
    # Parse command line arguments
    import argparse
    parser = argparse.ArgumentParser(description='Create tournaments on DigitalPool automatically')
    parser.add_argument('json_file', nargs='?', help='Path to tournament JSON file')
    parser.add_argument('--headless', '-H', action='store_true', 
                        help='Run browser in headless mode (no GUI)')
    parser.add_argument('--no-confirm', '-y', action='store_true',
                        help='Skip confirmation prompt')
    args = parser.parse_args()
    
    # Check for JSON file argument
    if args.json_file:
        json_file = args.json_file
        print(f"Loading tournament data from: {json_file}")
        data = load_tournament_data(json_file)
        
        if not data:
            sys.exit(1)
    else:
        # Interactive mode
        print("No JSON file provided. Running in interactive mode.")
        print("(To use a JSON file, run: python create_digitalpool_tournament.py <file.json>)")
        print("(Add --headless for headless mode, --no-confirm to skip confirmation)")
        
        data = interactive_mode()
    
    # Show summary
    print("\n" + "-"*50)
    print("Tournament Details:")
    print("-"*50)
    print(f"  Name: {data.get('tournament', {}).get('name', 'N/A')}")
    print(f"  Date: {data.get('tournament', {}).get('date', 'N/A')}")
    print(f"  Time: {data.get('tournament', {}).get('time', 'N/A')}")
    print(f"  Format: {data.get('format', {}).get('tournamentFormat', 'N/A')}")
    print(f"  Bracket Size: {data.get('format', {}).get('bracketSize', 'N/A')}")
    print(f"  Venue: {data.get('venue', {}).get('name', 'N/A')}")
    print("-"*50)
    
    # Confirm unless --no-confirm flag is set
    if not args.no_confirm:
        proceed = input("\nProceed with tournament creation? (y/n): ").strip().lower()
        
        if proceed != 'y':
            print("Cancelled.")
            sys.exit(0)
    else:
        print("\n--no-confirm flag set, proceeding automatically...")
    
    # Run automation
    headless = args.headless
    print(f"\nRunning in {'headless' if headless else 'visible'} mode...")
    
    automation = DigitalPoolAutomation(headless=headless)
    success = automation.create_tournament(data)
    
    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
