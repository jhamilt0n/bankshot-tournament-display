#!/bin/bash
# Script to create the complete repository structure
# Run this after you've copied all the original document files

echo "=========================================="
echo "Creating Bankshot Repository Structure"
echo "=========================================="
echo ""

# Get target directory
if [ -z "$1" ]; then
    TARGET_DIR=~/bankshot-tournament-display
else
    TARGET_DIR=$1
fi

echo "Target directory: $TARGET_DIR"
echo ""

# Create directory structure
echo "Creating directory structure..."
mkdir -p "$TARGET_DIR"
mkdir -p "$TARGET_DIR/.github/workflows"
mkdir -p "$TARGET_DIR/docs"
mkdir -p "$TARGET_DIR/scraper"
mkdir -p "$TARGET_DIR/scripts"
mkdir -p "$TARGET_DIR/services"
mkdir -p "$TARGET_DIR/web"

echo "✓ Directories created"
echo ""

# Copy documentation files if they exist
echo "Copying documentation..."
[ -f /tmp/bankshot-complete/README.md ] && cp /tmp/bankshot-complete/README.md "$TARGET_DIR/"
[ -f /tmp/bankshot-complete/QUICKREF.md ] && cp /tmp/bankshot-complete/QUICKREF.md "$TARGET_DIR/"
[ -f /tmp/bankshot-complete/SYSTEM_DIAGRAM.md ] && cp /tmp/bankshot-complete/SYSTEM_DIAGRAM.md "$TARGET_DIR/"
[ -f /tmp/bankshot-complete/MIGRATION_GUIDE.md ] && cp /tmp/bankshot-complete/MIGRATION_GUIDE.md "$TARGET_DIR/"
[ -f /tmp/bankshot-complete/MIGRATION_CHECKLIST.md ] && cp /tmp/bankshot-complete/MIGRATION_CHECKLIST.md "$TARGET_DIR/"
[ -f /tmp/bankshot-complete/FILE_STRUCTURE.md ] && cp /tmp/bankshot-complete/FILE_STRUCTURE.md "$TARGET_DIR/"

[ -d /tmp/bankshot-complete/docs ] && cp /tmp/bankshot-complete/docs/* "$TARGET_DIR/docs/"
[ -d /tmp/bankshot-complete/scripts ] && cp /tmp/bankshot-complete/scripts/* "$TARGET_DIR/scripts/"

echo "✓ Documentation copied"
echo ""

# Create .gitignore
echo "Creating .gitignore..."
cat > "$TARGET_DIR/.gitignore" << 'GITIGNORE'
# OS files
.DS_Store
Thumbs.db

# Editor files
*.swp
*.swo
*~
.vscode/
.idea/

# Python
__pycache__/
*.py[cod]
*.so
*.egg-info/
.pytest_cache/

# Logs
*.log
logs/

# Temporary files
*.tmp
*.bak

# Media files (too large for git)
web/media/*.mp4
web/media/*.webm
web/media/*.mov
web/media/*.jpg
web/media/*.jpeg
web/media/*.png
web/media/*.gif

# But keep the directory
!web/media/.gitkeep
!web/media/media_config.json

# Backup files
backup_*/

# Compiled files
*.pyc
GITIGNORE

echo "✓ .gitignore created"
echo ""

# Create placeholder files
echo "Creating placeholder files..."
touch "$TARGET_DIR/web/media/.gitkeep"

# Create README files for empty directories
cat > "$TARGET_DIR/web/media/README.md" << 'MEDIA_README'
# Media Directory

This directory stores uploaded media files (images, videos).

Upload media via the Media Manager:
http://bankshot-display.local/media_manager.html

Files in this directory:
- *.jpg, *.png, *.gif - Image files
- *.mp4, *.webm - Video files
- media_config.json - Configuration for all media items
MEDIA_README

echo "✓ Placeholder files created"
echo ""

# Make scripts executable
echo "Making scripts executable..."
chmod +x "$TARGET_DIR/scripts"/*.sh 2>/dev/null || true
chmod +x "$TARGET_DIR/scripts"/*.py 2>/dev/null || true
chmod +x "$TARGET_DIR/scraper"/*.py 2>/dev/null || true

echo "✓ Scripts made executable"
echo ""

echo "=========================================="
echo "Directory structure created!"
echo "=========================================="
echo ""
echo "Next steps:"
echo ""
echo "1. Copy these files from your original documents to $TARGET_DIR:"
echo "   - .github/workflows/scrape.yml (document 3)"
echo "   - scraper/bankshot_monitor_multi.py (document 4)"
echo "   - scripts/pull_tournament_data.sh (document 6)"
echo "   - scripts/web_monitor.py (document 9 or 16)"
echo "   - scripts/hdmi_display_manager.sh (document 8 or 14)"
echo "   - services/web-monitor.service (document 19)"
echo "   - services/hdmi-display.service (document 17)"
echo "   - All web/*.php and web/*.html files (documents 13, 20-34)"
echo ""
echo "2. Initialize git repository:"
echo "   cd $TARGET_DIR"
echo "   git init"
echo "   git add ."
echo "   git commit -m 'Initial commit - consolidated system'"
echo ""
echo "3. Push to GitHub:"
echo "   git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git"
echo "   git push -u origin main"
echo ""
echo "4. Follow MIGRATION_GUIDE.md for Raspberry Pi setup"
echo ""

# Show directory tree
echo "Directory structure:"
echo ""
cd "$TARGET_DIR"
if command -v tree &> /dev/null; then
    tree -L 2
else
    find . -maxdepth 2 -type d | sort
fi

echo ""
echo "Done!"
