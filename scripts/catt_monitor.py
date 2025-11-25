# Line 17 - Update media config path
MEDIA_CONFIG_FILE = '/var/www/html/media/media_config.json'

# Lines 364-391 - Add cooldown in SCENARIO 2
# Replace the entire SCENARIO 2 section with this:

            # SCENARIO 2: Already casting - check for file changes
            elif should_display and state['is_casting_tournament']:
                tournament_changed = (current_tournament_hash != state.get('tournament_data_hash'))
                media_changed = (current_media_hash != state.get('media_config_hash'))
                
                if tournament_changed or media_changed:
                    # Add cooldown - wait at least 10 minutes between recasts
                    if state.get('cast_started_at'):
                        last_cast = datetime.fromisoformat(state['cast_started_at'])
                        minutes_since_cast = (datetime.now() - last_cast).total_seconds() / 60
                        
                        if minutes_since_cast < 10:  # 10 minute cooldown
                            logging.info(f"⏸️  Files changed but in cooldown period ({minutes_since_cast:.1f} min since last cast)")
                            time.sleep(CHECK_INTERVAL)
                            continue
                    
                    change_reasons = []
                    if tournament_changed:
                        change_reasons.append("tournament_data.json changed")
                    if media_changed:
                        change_reasons.append("media_config.json changed")
                    
                    logging.info(f"🔄 Content changed: {', '.join(change_reasons)}")
                    logging.info("   Re-casting to update display...")
                    
                    catt_stop()
                    time.sleep(1)
                    
                    if catt_cast_site(cast_url):
                        state['cast_started_at'] = datetime.now().isoformat()  # Update cast time
                        state['tournament_data_hash'] = current_tournament_hash
                        state['media_config_hash'] = current_media_hash
                        save_cast_state(state)
                        logging.info("✓ Re-cast successful - display updated")
                    else:
                        logging.error("✗ Re-cast failed - will retry next cycle")
