# Replace the save_tournament_data() function (around line 62) with this:

def save_tournament_data(data):
    """Save tournament data to web-accessible location - only if changed"""
    try:
        # Load existing data if it exists
        existing_data = None
        if os.path.exists(OUTPUT_FILE):
            try:
                with open(OUTPUT_FILE, 'r') as f:
                    existing_data = json.load(f)
            except:
                pass
        
        # Compare data without timestamps
        data_to_compare = {k: v for k, v in data.items() if k != 'last_updated'}
        existing_to_compare = {k: v for k, v in existing_data.items() if k != 'last_updated'} if existing_data else None
        
        # Only write if data has actually changed
        if data_to_compare != existing_to_compare:
            # Add last updated timestamp
            data['last_updated'] = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            
            with open(OUTPUT_FILE, 'w') as f:
                json.dump(data, f, indent=2)
            
            logging.info(f"✓ Tournament data CHANGED - saved to {OUTPUT_FILE}")
            return True
        else:
            logging.debug("Tournament data unchanged - no write needed")
            return False
    except Exception as e:
        logging.error(f"Error saving tournament data: {e}")
        return False
