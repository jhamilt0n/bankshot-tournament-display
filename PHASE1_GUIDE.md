# Phase 1: Push to GitHub - Step by Step

## What We're Doing

Uploading your core system (scraper, installer, docs, backend files) to GitHub. This takes about 5 minutes.

---

## Prerequisites Check

Before we start, make sure you have:

- [ ] GitHub account created
- [ ] Know your GitHub username
- [ ] Terminal/command prompt open
- [ ] You're on the computer where `/tmp/bankshot-complete/` exists

**Ready? Let's go!**

---

## Step 1: Create GitHub Repository (2 minutes)

### Go to GitHub and create a new repository:

1. **Open browser:** https://github.com/new

2. **Repository name:** `bankshot-tournament-display`

3. **Description (optional):** "Bankshot Billiards tournament display system"

4. **Visibility:** 
   - Choose **Private** (recommended) or **Public**

5. **IMPORTANT:** 
   - ⚠️ **DO NOT** check "Add a README file"
   - ⚠️ **DO NOT** check "Add .gitignore"
   - ⚠️ **DO NOT** check "Choose a license"
   - Leave all boxes **unchecked**

6. **Click:** "Create repository"

7. **Copy the repository URL:**
   - You'll see: `https://github.com/YOUR_USERNAME/bankshot-tournament-display.git`
   - Keep this handy!

**✓ Repository created!**

---

## Step 2: Prepare the Files (1 minute)

Open your terminal and navigate to the repository:

```bash
cd /tmp/bankshot-complete
```

**Verify you're in the right place:**
```bash
pwd
# Should show: /tmp/bankshot-complete

ls
# Should show: README.md, docs/, scripts/, web/, etc.
```

**✓ You're in the right directory!**

---

## Step 3: Create .gitignore (30 seconds)

This tells Git which files NOT to upload:

```bash
cat > .gitignore << 'EOF'
# Logs
*.log
logs/

# Python cache
__pycache__/
*.pyc
*.pyo
.pytest_cache/

# OS files
.DS_Store
Thumbs.db
.DS_Store?
._*

# Editor files
*.swp
*.swo
*~
.vscode/
.idea/

# Large media files (too big for Git)
web/media/*.mp4
web/media/*.webm
web/media/*.mov
web/media/*.avi
web/media/*.jpg
web/media/*.jpeg
web/media/*.png
web/media/*.gif

# But keep these files
!web/media/.gitkeep
!web/media/media_config.json

# Backup directories
backup_*/
*_backup/

# Temporary files
*.tmp
*.bak
.*.tmp
EOF
```

**Verify it was created:**
```bash
cat .gitignore
# Should show the ignore rules
```

**✓ .gitignore created!**

---

## Step 4: Create Media Directory Placeholder (10 seconds)

```bash
mkdir -p web/media
touch web/media/.gitkeep
```

This creates an empty placeholder so the media directory exists in Git.

**✓ Placeholder created!**

---

## Step 5: Initialize Git (30 seconds)

```bash
git init
```

**You'll see:**
```
Initialized empty Git repository in /tmp/bankshot-complete/.git/
```

**✓ Git initialized!**

---

## Step 6: Add All Files (10 seconds)

```bash
git add .
```

**Check what will be committed:**
```bash
git status
```

**You should see:**
```
On branch master

Initial commit

Changes to be committed:
  new file:   .gitignore
  new file:   README.md
  new file:   docs/SETUP.md
  new file:   scripts/install.sh
  ... (many more files)
```

**✓ Files staged for commit!**

---

## Step 7: Make First Commit (10 seconds)

```bash
git commit -m "Initial commit - consolidated tournament display system"
```

**You'll see:**
```
[master (root-commit) abc1234] Initial commit - consolidated tournament display system
 35 files changed, 5000+ insertions(+)
 create mode 100644 README.md
 create mode 100644 docs/SETUP.md
 ... (list of files)
```

**✓ Files committed!**

---

## Step 8: Add Remote Repository (10 seconds)

**⚠️ IMPORTANT: Replace YOUR_USERNAME with your actual GitHub username!**

```bash
git remote add origin https://github.com/YOUR_USERNAME/bankshot-tournament-display.git
```

**For example, if your username is "JohnDoe":**
```bash
git remote add origin https://github.com/JohnDoe/bankshot-tournament-display.git
```

**Verify it was added:**
```bash
git remote -v
```

**You should see:**
```
origin  https://github.com/YOUR_USERNAME/bankshot-tournament-display.git (fetch)
origin  https://github.com/YOUR_USERNAME/bankshot-tournament-display.git (push)
```

**✓ Remote repository added!**

---

## Step 9: Rename Branch to Main (10 seconds)

```bash
git branch -M main
```

This renames your branch from "master" to "main" (GitHub's default).

**✓ Branch renamed!**

---

## Step 10: Push to GitHub (1 minute)

```bash
git push -u origin main
```

**What happens:**
- Git uploads all your files to GitHub
- You may be asked to authenticate

**You'll see:**
```
Enumerating objects: 50, done.
Counting objects: 100% (50/50), done.
Delta compression using up to 8 threads
Compressing objects: 100% (45/45), done.
Writing objects: 100% (50/50), 100.00 KiB | 10.00 MiB/s, done.
Total 50 (delta 5), reused 0 (delta 0)
To https://github.com/YOUR_USERNAME/bankshot-tournament-display.git
 * [new branch]      main -> main
Branch 'main' set up to track remote branch 'main' from 'origin'.
```

**✓ Files pushed to GitHub!**

---

## Step 11: Verify on GitHub (30 seconds)

1. **Open browser:** `https://github.com/YOUR_USERNAME/bankshot-tournament-display`

2. **You should see:**
   - README.md displayed on the page
   - Folders: docs/, scripts/, web/, scraper/
   - Files: MIGRATION_GUIDE.md, QUICKREF.md, etc.

3. **Click around to verify files are there**

**✓ Repository verified on GitHub!**

---

## ✅ Phase 1 Complete!

**What you just did:**
- ✅ Created GitHub repository
- ✅ Set up Git locally
- ✅ Committed all 35 files
- ✅ Pushed to GitHub

**Your core system is now in GitHub!**

---

## 🎉 Success Check

You should now have:
- [ ] GitHub repository at: `https://github.com/YOUR_USERNAME/bankshot-tournament-display`
- [ ] README.md visible on the repository page
- [ ] All folders and files uploaded
- [ ] Total of ~35 files in the repository

**Everything good?**

---

## 🚀 Ready for Phase 2?

**Phase 2 will:**
- Run on your Raspberry Pi
- Preserve your working display files
- Install the new system
- Takes about 45 minutes

**Tell me when you're ready to start Phase 2!**

Or if you had any issues with Phase 1, let me know and I'll help troubleshoot!

---

## Troubleshooting Phase 1

### **"Permission denied (publickey)"**

You need to authenticate with GitHub. Two options:

**Option A: Use GitHub Desktop** (Easiest)
1. Download GitHub Desktop
2. Sign in with your account
3. Clone the repository
4. Copy files into it
5. Commit and push

**Option B: Create Personal Access Token**
1. Go to: https://github.com/settings/tokens
2. Click "Generate new token (classic)"
3. Name it: "Bankshot Pi"
4. Check: `repo` (all repo permissions)
5. Click "Generate token"
6. Copy the token (looks like: `ghp_xxxxxxxxxxxx`)
7. When pushing, use token as password

**Option C: Use SSH key**
```bash
# Generate SSH key
ssh-keygen -t ed25519 -C "your_email@example.com"

# Copy public key
cat ~/.ssh/id_ed25519.pub

# Add to GitHub: https://github.com/settings/ssh/new
# Then try pushing again
```

---

### **"Repository not found"**

- Check repository name spelling
- Verify it's `bankshot-tournament-display` (not `bankshot-display` or similar)
- Make sure repository is created on GitHub
- Check your username in the URL

---

### **"Files not showing up on GitHub"**

- Wait 30 seconds and refresh
- Check you're on the "main" branch (dropdown near top)
- Verify push succeeded (look for "✓" in terminal)

---

## 📞 Need Help?

Tell me what happened and I'll guide you through the fix!

Otherwise, if Phase 1 is complete, say **"Phase 1 done, start Phase 2"** and we'll move to your Pi!
