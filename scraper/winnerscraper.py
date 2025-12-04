#!/usr/bin/env python3
"""
Digital Pool Tournament Winner Scraper
Scrapes Bankshot Billiards tournaments from digitalpool.com
and returns top 3 finishers from the most recent tournament(s).
Also generates a themed HTML display page.
"""

import asyncio
import json
import re
import os
from datetime import datetime
from playwright.async_api import async_playwright, TimeoutError as PlaywrightTimeout


class DigitalPoolScraper:
    def __init__(self):
        self.base_url = "https://digitalpool.com"
        self.results = {
            "scrape_date": datetime.now().isoformat(),
            "search_term": "Bankshot Billiards",
            "tournaments": []
        }
    
    async def run(self):
        """Main entry point for the scraper."""
        async with async_playwright() as p:
            browser = await p.chromium.launch(
                headless=True,
                args=['--no-sandbox', '--disable-setuid-sandbox']
            )
            context = await browser.new_context(
                viewport={"width": 1920, "height": 1080},
                user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
            )
            page = await context.new_page()
            
            try:
                await self.scrape_tournaments(page)
            except Exception as e:
                print(f"Error during scraping: {e}")
                self.results["error"] = str(e)
            finally:
                await browser.close()
        
        return self.results
    
    async def scrape_tournaments(self, page):
        """Navigate to tournaments and find Bankshot Billiards events."""
        print("Navigating to Digital Pool tournaments page...")
        await page.goto(f"{self.base_url}/tournaments", wait_until="networkidle", timeout=60000)
        await page.wait_for_timeout(3000)
        
        print("Searching for Bankshot Billiards...")
        await self.search_for_venue(page, "Bankshot Billiards")
        
        tournament_links = await self.find_tournament_links(page)
        print(f"Found {len(tournament_links)} tournament links")
        
        if not tournament_links:
            print("No tournament links found via search, trying alternative methods...")
            tournament_links = await self.find_links_by_text(page, "Bankshot")
        
        tournaments_by_date = {}
        
        for link in tournament_links[:20]:
            tournament_data = await self.get_tournament_details(page, link)
            if tournament_data:
                date_key = tournament_data.get("date", "unknown")
                if date_key not in tournaments_by_date:
                    tournaments_by_date[date_key] = []
                tournaments_by_date[date_key].append(tournament_data)
        
        if tournaments_by_date:
            sorted_dates = sorted(
                [d for d in tournaments_by_date.keys() if d != "unknown"],
                reverse=True
            )
            
            if sorted_dates:
                most_recent_date = sorted_dates[0]
                self.results["most_recent_date"] = most_recent_date
                self.results["tournaments"] = tournaments_by_date[most_recent_date]
                print(f"\nMost recent tournament date: {most_recent_date}")
                print(f"Number of tournaments on this date: {len(self.results['tournaments'])}")
            else:
                all_tournaments = []
                for tournaments in tournaments_by_date.values():
                    all_tournaments.extend(tournaments)
                self.results["tournaments"] = all_tournaments[:5]
    
    async def search_for_venue(self, page, venue_name):
        """Search for a specific venue in the tournaments page."""
        search_selectors = [
            'input[placeholder*="Search"]',
            'input[placeholder*="search"]',
            'input[type="search"]',
            'input[name="search"]',
            'input[name="q"]',
            '.ant-input',
            'input.search',
            '#search-input'
        ]
        
        for selector in search_selectors:
            try:
                search_input = await page.wait_for_selector(selector, timeout=3000)
                if search_input:
                    await search_input.click()
                    await search_input.fill(venue_name)
                    await page.keyboard.press("Enter")
                    await page.wait_for_timeout(3000)
                    print(f"Searched using selector: {selector}")
                    return True
            except PlaywrightTimeout:
                continue
        
        print("No search input found")
        return False
    
    async def find_tournament_links(self, page):
        """Find all tournament links on the current page."""
        links = []
        
        selectors = [
            'a[href*="/tournaments/"]',
            'a[href*="/tournament/"]',
            '.tournament-link',
            '.ant-table-row a',
            'tr a[href*="tournament"]'
        ]
        
        for selector in selectors:
            try:
                elements = await page.query_selector_all(selector)
                for elem in elements:
                    href = await elem.get_attribute("href")
                    if href and "/tournaments/" in href:
                        try:
                            row = await elem.evaluate("el => el.closest('tr, .ant-table-row, .tournament-card, div')")
                            text = await page.evaluate("el => el ? el.innerText : ''", row)
                            if "Bankshot" in text or "bankshot" in text.lower():
                                links.append(href)
                        except:
                            links.append(href)
            except:
                continue
        
        return list(set(links))
    
    async def find_links_by_text(self, page, text_pattern):
        """Find links by searching page text content."""
        links = []
        
        try:
            all_links = await page.query_selector_all('a')
            for link in all_links:
                try:
                    href = await link.get_attribute("href")
                    inner_text = await link.inner_text()
                    if href and text_pattern.lower() in inner_text.lower():
                        if "/tournaments/" in href or "/tournament/" in href:
                            links.append(href)
                except:
                    continue
        except Exception as e:
            print(f"Error finding links by text: {e}")
        
        return list(set(links))
    
    async def get_tournament_details(self, page, link):
        """Get details for a specific tournament including top 3 finishers."""
        full_url = link if link.startswith("http") else f"{self.base_url}{link}"
        print(f"\nFetching tournament: {full_url}")
        
        try:
            await page.goto(full_url, wait_until="networkidle", timeout=30000)
            await page.wait_for_timeout(2000)
            
            tournament = {
                "url": full_url,
                "name": "",
                "date": "",
                "top_3": []
            }
            
            tournament["name"] = await self.extract_tournament_name(page)
            tournament["date"] = await self.extract_tournament_date(page)
            tournament["top_3"] = await self.extract_top_3(page)
            
            if tournament["top_3"]:
                print(f"  Name: {tournament['name']}")
                print(f"  Date: {tournament['date']}")
                print(f"  Top 3: {[p['name'] for p in tournament['top_3']]}")
                return tournament
            else:
                print(f"  Could not extract standings")
                return None
                
        except Exception as e:
            print(f"  Error: {e}")
            return None
    
    async def extract_tournament_name(self, page):
        """Extract tournament name from the page."""
        selectors = ['h1', 'h2', '.tournament-title', '.tournament-name', '[class*="title"]']
        
        for selector in selectors:
            try:
                elem = await page.query_selector(selector)
                if elem:
                    text = await elem.inner_text()
                    if text and len(text) > 3:
                        return text.strip()
            except:
                continue
        
        try:
            return await page.title()
        except:
            return "Unknown Tournament"
    
    async def extract_tournament_date(self, page):
        """Extract tournament date from the page."""
        date_selectors = ['[class*="date"]', 'time', '.tournament-date', 'span:has-text("202")']
        
        for selector in date_selectors:
            try:
                elem = await page.query_selector(selector)
                if elem:
                    text = await elem.inner_text()
                    date = self.parse_date(text)
                    if date:
                        return date
            except:
                continue
        
        try:
            body_text = await page.inner_text("body")
            date_patterns = [
                r'(\d{1,2}/\d{1,2}/\d{4})',
                r'(\d{4}-\d{2}-\d{2})',
                r'((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]* \d{1,2},? \d{4})',
            ]
            
            for pattern in date_patterns:
                match = re.search(pattern, body_text)
                if match:
                    return match.group(1)
        except:
            pass
        
        return "unknown"
    
    def parse_date(self, text):
        """Try to parse a date string."""
        if not text:
            return None
        
        formats = ["%m/%d/%Y", "%Y-%m-%d", "%B %d, %Y", "%b %d, %Y", "%d %B %Y", "%d %b %Y"]
        
        for fmt in formats:
            try:
                dt = datetime.strptime(text.strip(), fmt)
                return dt.strftime("%Y-%m-%d")
            except:
                continue
        
        return None
    
    async def extract_top_3(self, page):
        """Extract top 3 finishers from tournament standings."""
        top_3 = []
        
        try:
            row_selectors = [
                'table tbody tr',
                '.ant-table-row',
                '[class*="standing"] [class*="row"]',
                '[class*="result"] [class*="row"]',
                '[class*="rank"]'
            ]
            
            for selector in row_selectors:
                rows = await page.query_selector_all(selector)
                if len(rows) >= 3:
                    for i, row in enumerate(rows[:3]):
                        text = await row.inner_text()
                        name = self.extract_player_name(text)
                        if name:
                            top_3.append({"place": i + 1, "name": name})
                    if len(top_3) >= 3:
                        return top_3
        except:
            pass
        
        try:
            for place in range(1, 4):
                place_selectors = [
                    f'[class*="place-{place}"]',
                    f'[class*="rank-{place}"]',
                ]
                
                for selector in place_selectors:
                    try:
                        elem = await page.query_selector(selector)
                        if elem:
                            text = await elem.inner_text()
                            name = self.extract_player_name(text)
                            if name:
                                top_3.append({"place": place, "name": name})
                                break
                    except:
                        continue
        except:
            pass
        
        if len(top_3) < 3:
            try:
                items = await page.query_selector_all('ol li')
                for i, item in enumerate(items[:3]):
                    text = await item.inner_text()
                    name = self.extract_player_name(text)
                    if name:
                        top_3.append({"place": i + 1, "name": name})
            except:
                pass
        
        if len(top_3) < 3:
            try:
                results = await page.evaluate('''
                    () => {
                        const results = [];
                        const standingsSection = document.querySelector('[class*="standing"], [class*="result"], [class*="bracket"]');
                        if (standingsSection) {
                            const spans = standingsSection.querySelectorAll('span');
                            for (let i = 0; i < Math.min(spans.length, 10); i++) {
                                const text = spans[i].innerText.trim();
                                if (text && text.length > 2 && text.length < 50) {
                                    results.push(text);
                                }
                            }
                        }
                        return results;
                    }
                ''')
                
                for i, text in enumerate(results[:3]):
                    name = self.extract_player_name(text)
                    if name:
                        top_3.append({"place": i + 1, "name": name})
            except:
                pass
        
        return top_3[:3]
    
    def extract_player_name(self, text):
        """Clean and extract player name from text."""
        if not text:
            return None
        
        text = text.strip()
        text = re.sub(r'^(1st|2nd|3rd|1\.|2\.|3\.|\d+\.|\d+\))\s*', '', text)
        
        lines = [l.strip() for l in text.split('\n') if l.strip()]
        if lines:
            text = lines[0]
        
        if '\t' in text:
            text = text.split('\t')[0]
        
        text = ' '.join(text.split())
        
        if text and len(text) > 1 and len(text) < 50:
            if re.search(r'[a-zA-Z]', text):
                return text
        
        return None


def generate_html_display(results, output_path):
    """Generate a themed HTML display page for the winners."""
    
    tournaments = results.get("tournaments", [])
    most_recent_date = results.get("most_recent_date", "")
    
    if most_recent_date and most_recent_date != "unknown":
        try:
            dt = datetime.strptime(most_recent_date, "%Y-%m-%d")
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
            
            for i, player in enumerate(top_3):
                place_label = place_labels[i] if i < 3 else f"{i+1}th Place"
                place_class = place_classes[i] if i < 3 else ""
                player_name = player.get("name", "TBD")
                
                winners_html += f"""
                <div class="winner-card {place_class}">
                    <div class="place-badge">{place_label}</div>
                    <div class="winner-name">{player_name}</div>
                </div>
                """
            
            for i in range(len(top_3), 3):
                place_label = place_labels[i]
                place_class = place_classes[i]
                winners_html += f"""
                <div class="winner-card {place_class}">
                    <div class="place-badge">{place_label}</div>
                    <div class="winner-name">TBD</div>
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
        Bankshot Billiards &bull; Updated {datetime.now().strftime("%m/%d/%Y %I:%M %p")}
    </div>
</body>
</html>
'''
    
    with open(output_path, "w") as f:
        f.write(html_content)
    
    print(f"HTML display generated: {output_path}")


def format_results(results):
    """Format results for display."""
    output = []
    output.append("=" * 60)
    output.append("BANKSHOT BILLIARDS TOURNAMENT RESULTS")
    output.append("=" * 60)
    output.append(f"Scraped at: {results['scrape_date']}")
    
    if "most_recent_date" in results:
        output.append(f"Most recent tournament date: {results['most_recent_date']}")
    
    output.append("")
    
    if results.get("tournaments"):
        for i, tournament in enumerate(results["tournaments"], 1):
            output.append(f"Tournament {i}: {tournament['name']}")
            output.append(f"  Date: {tournament['date']}")
            output.append(f"  URL: {tournament['url']}")
            output.append("  Top 3 Finishers:")
            for player in tournament["top_3"]:
                output.append(f"    {player['place']}. {player['name']}")
            output.append("")
    else:
        output.append("No tournaments found.")
        if results.get("error"):
            output.append(f"Error: {results['error']}")
    
    output.append("=" * 60)
    return "\n".join(output)


async def main():
    """Main entry point."""
    scraper = DigitalPoolScraper()
    results = await scraper.run()
    
    output_dir = os.environ.get("OUTPUT_DIR", ".")
    
    with open(f"{output_dir}/results.json", "w") as f:
        json.dump(results, f, indent=2)
    
    print(format_results(results))
    
    with open(f"{output_dir}/results.txt", "w") as f:
        f.write(format_results(results))
    
    generate_html_display(results, f"{output_dir}/winners_display.html")
    
    return results


if __name__ == "__main__":
    asyncio.run(main())
