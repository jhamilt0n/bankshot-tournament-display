#!/usr/bin/env python3
"""
Tournament Server - Flask API for Bankshot Tournament Console
==============================================================
This server runs on the Raspberry Pi and handles tournament creation requests
from the web interface.

Setup:
    pip install flask flask-cors selenium webdriver-manager

Run:
    python tournament_server.py
    
    Or with gunicorn for production:
    gunicorn -w 1 -b 0.0.0.0:5000 tournament_server:app

The server will:
1. Receive tournament data from the HTML form
2. Save it as a JSON file
3. Optionally trigger the Selenium script to create the tournament on DigitalPool

Author: Bankshot Tournament Console
"""

import os
import json
import subprocess
import threading
from datetime import datetime
from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # Allow cross-origin requests from the HTML page

# Configuration
TOURNAMENT_DATA_DIR = '/var/www/html/tournament_data'
SELENIUM_SCRIPT_PATH = '/var/www/html/scripts/create_digitalpool_tournament.py'
LOG_FILE = '/var/www/html/tournament_data/server.log'

# Create data directory if it doesn't exist
os.makedirs(TOURNAMENT_DATA_DIR, exist_ok=True)


def log_message(message):
    """Log a message to file and console."""
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    log_line = f"[{timestamp}] {message}"
    print(log_line)
    try:
        with open(LOG_FILE, 'a') as f:
            f.write(log_line + '\n')
    except:
        pass


def run_selenium_script(json_filepath):
    """Run the Selenium script in a separate thread."""
    try:
        log_message(f"Starting Selenium script with: {json_filepath}")
        
        # Run the script with headless and no-confirm flags
        result = subprocess.run(
            ['python3', SELENIUM_SCRIPT_PATH, json_filepath, '--headless', '--no-confirm'],
            capture_output=True,
            text=True,
            timeout=300  # 5 minute timeout
        )
        
        if result.returncode == 0:
            log_message(f"Selenium script completed successfully")
            log_message(f"Output: {result.stdout}")
        else:
            log_message(f"Selenium script failed with code {result.returncode}")
            log_message(f"Error: {result.stderr}")
            
    except subprocess.TimeoutExpired:
        log_message("Selenium script timed out after 5 minutes")
    except Exception as e:
        log_message(f"Error running Selenium script: {e}")


@app.route('/')
def index():
    """Serve the main page."""
    return send_from_directory('/var/www/html', 'create_tournament.html')


@app.route('/api/health', methods=['GET'])
def health_check():
    """Health check endpoint."""
    return jsonify({
        'status': 'ok',
        'timestamp': datetime.now().isoformat(),
        'data_dir': TOURNAMENT_DATA_DIR,
        'selenium_script': os.path.exists(SELENIUM_SCRIPT_PATH)
    })


@app.route('/api/tournament', methods=['POST'])
def create_tournament():
    """
    Receive tournament data and optionally trigger Selenium script.
    
    Expected JSON body:
    {
        "credentials": {...},
        "tournament": {...},
        "format": {...},
        "venue": {...},
        "options": {...},
        "meta": {...},
        "autoRun": true/false  // Whether to automatically run Selenium
    }
    """
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({'error': 'No data provided'}), 400
        
        # Extract tournament info for filename
        tournament_name = data.get('tournament', {}).get('name', 'tournament')
        safe_name = "".join(c if c.isalnum() or c in (' ', '-', '_') else '_' for c in tournament_name)
        safe_name = safe_name.replace(' ', '_')[:50]
        
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"{timestamp}_{safe_name}.json"
        filepath = os.path.join(TOURNAMENT_DATA_DIR, filename)
        
        # Check if we should auto-run Selenium
        auto_run = data.pop('autoRun', False)
        
        # Save the JSON file
        with open(filepath, 'w') as f:
            json.dump(data, f, indent=2)
        
        log_message(f"Saved tournament data to: {filepath}")
        
        response_data = {
            'success': True,
            'message': 'Tournament data saved',
            'filename': filename,
            'filepath': filepath
        }
        
        # Optionally trigger Selenium script
        if auto_run:
            if os.path.exists(SELENIUM_SCRIPT_PATH):
                # Run in background thread so we can respond immediately
                thread = threading.Thread(target=run_selenium_script, args=(filepath,))
                thread.start()
                response_data['selenium'] = 'started'
                response_data['message'] = 'Tournament data saved and Selenium script started'
            else:
                response_data['selenium'] = 'script_not_found'
                response_data['warning'] = f'Selenium script not found at {SELENIUM_SCRIPT_PATH}'
        
        return jsonify(response_data)
        
    except Exception as e:
        log_message(f"Error in create_tournament: {e}")
        return jsonify({'error': str(e)}), 500


@app.route('/api/tournament/run/<filename>', methods=['POST'])
def run_tournament_script(filename):
    """
    Manually trigger the Selenium script for a saved tournament file.
    """
    filepath = os.path.join(TOURNAMENT_DATA_DIR, filename)
    
    if not os.path.exists(filepath):
        return jsonify({'error': 'Tournament file not found'}), 404
    
    if not os.path.exists(SELENIUM_SCRIPT_PATH):
        return jsonify({'error': 'Selenium script not found'}), 500
    
    # Run in background thread
    thread = threading.Thread(target=run_selenium_script, args=(filepath,))
    thread.start()
    
    return jsonify({
        'success': True,
        'message': 'Selenium script started',
        'filename': filename
    })


@app.route('/api/tournaments', methods=['GET'])
def list_tournaments():
    """List all saved tournament JSON files."""
    try:
        files = []
        for filename in os.listdir(TOURNAMENT_DATA_DIR):
            if filename.endswith('.json'):
                filepath = os.path.join(TOURNAMENT_DATA_DIR, filename)
                stat = os.stat(filepath)
                files.append({
                    'filename': filename,
                    'size': stat.st_size,
                    'created': datetime.fromtimestamp(stat.st_mtime).isoformat()
                })
        
        # Sort by creation date, newest first
        files.sort(key=lambda x: x['created'], reverse=True)
        
        return jsonify({'tournaments': files})
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/tournaments/<filename>', methods=['GET'])
def get_tournament(filename):
    """Get the contents of a specific tournament file."""
    filepath = os.path.join(TOURNAMENT_DATA_DIR, filename)
    
    if not os.path.exists(filepath):
        return jsonify({'error': 'File not found'}), 404
    
    try:
        with open(filepath, 'r') as f:
            data = json.load(f)
        return jsonify(data)
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/tournaments/<filename>', methods=['DELETE'])
def delete_tournament(filename):
    """Delete a tournament file."""
    filepath = os.path.join(TOURNAMENT_DATA_DIR, filename)
    
    if not os.path.exists(filepath):
        return jsonify({'error': 'File not found'}), 404
    
    try:
        os.remove(filepath)
        log_message(f"Deleted tournament file: {filename}")
        return jsonify({'success': True, 'message': 'File deleted'})
    except Exception as e:
        return jsonify({'error': str(e)}), 500


if __name__ == '__main__':
    log_message("Tournament Server starting...")
    log_message(f"Data directory: {TOURNAMENT_DATA_DIR}")
    log_message(f"Selenium script: {SELENIUM_SCRIPT_PATH}")
    
    # Run the server
    app.run(host='0.0.0.0', port=5000, debug=True)
