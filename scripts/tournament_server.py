#!/usr/bin/env python3
"""
Tournament Server - Flask API for Bankshot Tournament Console
==============================================================
This server runs on the Raspberry Pi and handles tournament creation requests
from the web interface. It triggers GitHub Actions to run Selenium automation.

Setup:
    pip install flask flask-cors requests --break-system-packages

Configuration:
    Set your GitHub token in environment variable or config below:
    export GITHUB_TOKEN="ghp_your_token_here"

Run:
    python3 tournament_server.py

Author: Bankshot Tournament Console
"""

import os
import json
import base64
import requests
from datetime import datetime
from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

# =============================================================================
# CONFIGURATION - Update these values
# =============================================================================
TOURNAMENT_DATA_DIR = '/var/www/html/tournament_data'
LOG_FILE = '/var/www/html/tournament_data/server.log'

# GitHub Configuration
GITHUB_OWNER = 'jhamilt0n'  # Your GitHub username
GITHUB_REPO = 'bankshot-tournament-display'  # Your repo name
GITHUB_TOKEN = os.environ.get('GITHUB_TOKEN', '')  # Set via environment variable

# =============================================================================
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


def trigger_github_action(tournament_data):
    """
    Trigger GitHub Actions workflow to run Selenium automation.
    
    Uses repository_dispatch event to trigger the workflow.
    """
    if not GITHUB_TOKEN:
        log_message("ERROR: GITHUB_TOKEN not set")
        return False, "GitHub token not configured. Set GITHUB_TOKEN environment variable."
    
    url = f"https://api.github.com/repos/{GITHUB_OWNER}/{GITHUB_REPO}/dispatches"
    
    headers = {
        'Accept': 'application/vnd.github+json',
        'Authorization': f'Bearer {GITHUB_TOKEN}',
        'X-GitHub-Api-Version': '2022-11-28'
    }
    
    payload = {
        'event_type': 'create-tournament',
        'client_payload': {
            'tournament_data': tournament_data
        }
    }
    
    try:
        log_message(f"Triggering GitHub Action for: {tournament_data.get('tournament', {}).get('name', 'Unknown')}")
        
        response = requests.post(url, headers=headers, json=payload, timeout=30)
        
        if response.status_code == 204:
            log_message("GitHub Action triggered successfully")
            return True, "GitHub Action triggered successfully"
        elif response.status_code == 401:
            log_message("GitHub authentication failed - check token")
            return False, "GitHub authentication failed. Check GITHUB_TOKEN."
        elif response.status_code == 404:
            log_message("GitHub repo not found")
            return False, f"Repository {GITHUB_OWNER}/{GITHUB_REPO} not found."
        else:
            error_msg = f"GitHub API error: {response.status_code} - {response.text}"
            log_message(error_msg)
            return False, error_msg
            
    except requests.exceptions.Timeout:
        log_message("GitHub API timeout")
        return False, "GitHub API request timed out"
    except Exception as e:
        log_message(f"Error triggering GitHub Action: {e}")
        return False, str(e)


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
        'github_configured': bool(GITHUB_TOKEN),
        'github_repo': f"{GITHUB_OWNER}/{GITHUB_REPO}"
    })


@app.route('/api/tournament', methods=['POST'])
def create_tournament():
    """
    Receive tournament data, save it, and trigger GitHub Actions.
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
        
        # Check if we should auto-run
        auto_run = data.pop('autoRun', False)
        
        # Save the JSON file locally
        with open(filepath, 'w') as f:
            json.dump(data, f, indent=2)
        
        log_message(f"Saved tournament data to: {filepath}")
        
        response_data = {
            'success': True,
            'message': 'Tournament data saved',
            'filename': filename,
            'filepath': filepath
        }
        
        # Trigger GitHub Actions if requested
        if auto_run:
            if GITHUB_TOKEN:
                success, message = trigger_github_action(data)
                if success:
                    response_data['github_action'] = 'triggered'
                    response_data['message'] = 'Tournament saved and GitHub Action triggered! Check GitHub Actions for progress.'
                else:
                    response_data['github_action'] = 'failed'
                    response_data['github_error'] = message
                    response_data['message'] = f'Tournament saved but GitHub Action failed: {message}'
            else:
                response_data['github_action'] = 'not_configured'
                response_data['message'] = 'Tournament saved. GitHub token not configured - set GITHUB_TOKEN environment variable.'
        
        return jsonify(response_data)
        
    except Exception as e:
        log_message(f"Error in create_tournament: {e}")
        return jsonify({'error': str(e)}), 500


@app.route('/api/tournament/trigger/<filename>', methods=['POST'])
def trigger_for_file(filename):
    """
    Manually trigger GitHub Action for a saved tournament file.
    """
    filepath = os.path.join(TOURNAMENT_DATA_DIR, filename)
    
    if not os.path.exists(filepath):
        return jsonify({'error': 'Tournament file not found'}), 404
    
    try:
        with open(filepath, 'r') as f:
            data = json.load(f)
        
        success, message = trigger_github_action(data)
        
        if success:
            return jsonify({
                'success': True,
                'message': 'GitHub Action triggered',
                'filename': filename
            })
        else:
            return jsonify({
                'success': False,
                'error': message
            }), 500
            
    except Exception as e:
        return jsonify({'error': str(e)}), 500


@app.route('/api/tournaments', methods=['GET'])
def list_tournaments():
    """List all saved tournament JSON files."""
    try:
        files = []
        for filename in os.listdir(TOURNAMENT_DATA_DIR):
            if filename.endswith('.json'):
                filepath = os.path.join(TOURNAMENT_DATA_DIR, filename)
                stat = os.stat(filepath)
                
                # Read tournament name from file
                try:
                    with open(filepath, 'r') as f:
                        data = json.load(f)
                        name = data.get('tournament', {}).get('name', 'Unknown')
                except:
                    name = 'Unknown'
                
                files.append({
                    'filename': filename,
                    'name': name,
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


@app.route('/api/config', methods=['GET'])
def get_config():
    """Get current configuration (for debugging)."""
    return jsonify({
        'github_owner': GITHUB_OWNER,
        'github_repo': GITHUB_REPO,
        'github_token_configured': bool(GITHUB_TOKEN),
        'data_dir': TOURNAMENT_DATA_DIR
    })


if __name__ == '__main__':
    log_message("Tournament Server starting...")
    log_message(f"Data directory: {TOURNAMENT_DATA_DIR}")
    log_message(f"GitHub repo: {GITHUB_OWNER}/{GITHUB_REPO}")
    log_message(f"GitHub token configured: {bool(GITHUB_TOKEN)}")
    
    if not GITHUB_TOKEN:
        log_message("WARNING: GITHUB_TOKEN not set. Run with: GITHUB_TOKEN=ghp_xxx python3 tournament_server.py")
    
    # Run the server
    app.run(host='0.0.0.0', port=5000, debug=True)
