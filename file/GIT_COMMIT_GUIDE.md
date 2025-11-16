# GIT COMMIT GUIDE - School Management Plugin
**Complete Guide to Commit All Changes**

---

## 🎯 OVERVIEW

We need to commit TWO sets of changes:
1. **v0.4.1** - Payment model system (7 files from previous session)
2. **v0.4.2** - Courses page UI enhancement (1 file - this session)

---

## 📋 STEP-BY-STEP PROCESS

### Step 1: Check Current Git Status

Open **Git Bash** or **Command Prompt** in your plugin directory:

```bash
cd "C:\Users\ahmed\Local Sites\ctadz-school\app\public\wp-content\plugins\school-management"

# Check current branch
git branch

# Check status of all files
git status
```

**Expected output:**
- Current branch: `develop` or `main`
- List of modified/untracked files

---

### Step 2: Review What Changed

Let's see what files have been modified:

```bash
# Show modified files with changes
git status --short

# Detailed view of changes
git diff
```

**Files that should show as modified/new:**

From **v0.4.1** (previous session):
- `school-management.php` (version bump + migration)
- `includes/class-sm-payment-sync.php` (bug fix)
- `includes/class-sm-courses-page.php` (5 bugs fixed)
- `includes/class-sm-enrollments-page.php` (AJAX fix)
- `includes/sm-helpers.php` (new file)
- `includes/sm-enqueue.php` (AJAX registration)
- `includes/sm-loader.php` (helpers loading)

Documentation files (optional to commit):
- `PROJECT_ARTIFACT_UPDATED.md`
- Other .md files

---

### Step 3: Create Feature Branch (Recommended)

Good practice: Create a feature branch for the UI enhancements:

```bash
# Make sure you're on develop
git checkout develop

# Pull latest changes (if working with remote)
git pull origin develop

# Create new branch for UI work
git checkout -b feature/ui-enhancements-v0.4.2
```

**OR** if you prefer to commit directly to develop:

```bash
# Just make sure you're on develop
git checkout develop
```

---

## 📦 COMMIT #1: v0.4.1 Payment System

### Check if v0.4.1 is Already Committed

```bash
# Check commit history
git log --oneline -5

# Look for commits like:
# - "feat: payment model system"
# - "fix: payment sync bug"
# etc.
```

**If v0.4.1 is NOT committed yet:**

```bash
# Stage the 7 core files
git add school-management.php
git add includes/class-sm-payment-sync.php
git add includes/class-sm-courses-page.php
git add includes/class-sm-enrollments-page.php
git add includes/sm-helpers.php
git add includes/sm-enqueue.php
git add includes/sm-loader.php

# Review what will be committed
git status

# Commit with detailed message
git commit -m "feat: complete payment model system v0.4.1

- Added payment model support (full_payment, monthly_installments, monthly_subscription)
- Fixed payment sync database error (payment_date -> due_date)
- Added production-safe database migration for payment_model column
- Fixed AJAX dropdown loading issue in enrollments
- Added sm-helpers.php for AJAX handlers
- Updated sm-enqueue.php for AJAX registration
- Updated sm-loader.php to load helpers

BREAKING: Requires database migration (automatic on activation)
FIXES: #1 Payment model not saving
FIXES: #2 AJAX dropdown stuck on loading
FIXES: #3 Payment sync database error

Tested: LocalWP environment
Status: Production ready"
```

**If v0.4.1 IS already committed:**

```bash
# Great! Move to next step
echo "v0.4.1 already committed, moving to v0.4.2"
```

---

## 📦 COMMIT #2: v0.4.2 UI Enhancement

### Stage the Courses Page Update

```bash
# Make sure you have the updated file in place first
# (Copy class-sm-courses-page-UPDATED.php to includes/class-sm-courses-page.php)

# Stage the updated courses page
git add includes/class-sm-courses-page.php

# Review the changes
git diff --cached includes/class-sm-courses-page.php

# Commit with clear message
git commit -m "feat: add payment model display and filtering to courses list

UI Enhancements:
- Added Payment Model column with color-coded badges (green, blue, yellow)
- Added Enrollments column showing student count per course
- Added filter dropdown to filter courses by payment model
- Improved table layout (removed classroom column, better spacing)
- Added enrollment count query with proper JOIN

Features:
- Filter by: Full Payment, Monthly Installments, Monthly Subscription
- Clear filter link when filtering is active
- Pagination preserves filter state
- Color-coded badges with icons for each payment type
- Excludes cancelled enrollments from counts

Technical:
- Updated database query to include enrollment counts
- Added WHERE clause for filtering
- Proper GROUP BY for accurate counts
- Secure input sanitization
- No database schema changes

Tested: LocalWP environment
Version: v0.4.2
Status: Production ready"
```

---

## 📦 OPTIONAL: Commit Documentation

If you want to track documentation:

```bash
# Stage documentation files
git add PROJECT_ARTIFACT_UPDATED.md
git add README.md

# Commit docs separately
git commit -m "docs: update project documentation for v0.4.1 and v0.4.2

- Added PROJECT_ARTIFACT_UPDATED.md with complete session summary
- Updated README with installation and usage instructions
- Documented all bug fixes and features"
```

---

## 🔄 MERGE TO MAIN

After testing everything thoroughly:

```bash
# Make sure you're on develop (or feature branch)
git branch

# Check everything is committed
git status
# Should show: "nothing to commit, working tree clean"

# Switch to main
git checkout main

# Merge develop (or feature branch) into main
git merge develop
# OR
git merge feature/ui-enhancements-v0.4.2

# Push to remote (if you have one)
git push origin main
git push origin develop
```

---

## 🎯 QUICK REFERENCE: ALL COMMANDS IN ORDER

```bash
# === SETUP ===
cd "C:\Users\ahmed\Local Sites\ctadz-school\app\public\wp-content\plugins\school-management"
git status
git checkout develop

# === COMMIT v0.4.1 (if not done) ===
git add school-management.php
git add includes/class-sm-payment-sync.php
git add includes/class-sm-courses-page.php
git add includes/class-sm-enrollments-page.php
git add includes/sm-helpers.php
git add includes/sm-enqueue.php
git add includes/sm-loader.php
git commit -m "feat: complete payment model system v0.4.1

- Added payment model support (full_payment, monthly_installments, monthly_subscription)
- Fixed payment sync database error
- Added production-safe database migration
- Fixed AJAX dropdown loading issue
- Added helpers and AJAX registration

Tested: LocalWP
Status: Production ready"

# === UPDATE COURSES PAGE ===
# First, copy the updated file:
# copy class-sm-courses-page-UPDATED.php includes\class-sm-courses-page.php

# Then commit:
git add includes/class-sm-courses-page.php
git commit -m "feat: add payment model display and filtering to courses list

- Added Payment Model column with color-coded badges
- Added Enrollments column with student counts
- Added filter dropdown for payment models
- Improved table layout and query

Version: v0.4.2
Status: Production ready"

# === OPTIONAL: COMMIT DOCS ===
git add PROJECT_ARTIFACT_UPDATED.md
git add *.md
git commit -m "docs: update project documentation"

# === PUSH TO REMOTE (if applicable) ===
git push origin develop

# === MERGE TO MAIN (when ready) ===
git checkout main
git merge develop
git push origin main
```

---

## 🔍 VERIFY YOUR COMMITS

After committing, verify everything:

```bash
# View commit history
git log --oneline -10

# Should see something like:
# abc1234 feat: add payment model display and filtering to courses list
# def5678 feat: complete payment model system v0.4.1
# ...

# View specific commit details
git show HEAD
git show HEAD~1

# Check current status
git status
# Should show: "nothing to commit, working tree clean"
```

---

## 🎯 GIT WORKFLOW DIAGRAM

```
Current State
    ↓
Check Status (git status)
    ↓
Stage Files (git add)
    ↓
Commit v0.4.1 (git commit)
    ↓
Update Courses File
    ↓
Stage Updated File (git add)
    ↓
Commit v0.4.2 (git commit)
    ↓
Test Everything
    ↓
Merge to Main (git merge)
    ↓
Push to Remote (git push)
    ↓
Done! ✅
```

---

## 📋 CHECKLIST

Before committing:
- [ ] All 7 v0.4.1 files are in place
- [ ] Courses page updated file is in place
- [ ] Tested in LocalWP
- [ ] No uncommitted changes you want to keep

After committing:
- [ ] `git status` shows clean working tree
- [ ] `git log` shows your commits
- [ ] All files are tracked
- [ ] Ready to push/merge

---

## 🐛 COMMON ISSUES

### Issue 1: "fatal: not a git repository"
```bash
# Make sure you're in the right directory
cd "C:\Users\ahmed\Local Sites\ctadz-school\app\public\wp-content\plugins\school-management"

# Verify .git folder exists
ls -la | grep .git
```

### Issue 2: "Your branch is ahead of 'origin/develop'"
```bash
# This is normal - just push your changes
git push origin develop
```

### Issue 3: "Please tell me who you are"
```bash
# Set your git identity (one time only)
git config user.name "Ahmed Sebaa"
git config user.email "ahmed@cybertechacademy.com"
```

### Issue 4: "Changes not staged for commit"
```bash
# Stage all changes
git add .

# OR stage specific files
git add path/to/file
```

### Issue 5: "Merge conflict"
```bash
# This shouldn't happen if you're the only developer
# But if it does:
git status  # See which files have conflicts
# Edit files to resolve conflicts
git add resolved-file
git commit -m "fix: resolve merge conflicts"
```

---

## 💡 BEST PRACTICES

### Good Commit Messages:
✅ "feat: add payment model filtering to courses list"
✅ "fix: resolve database error in payment sync"
✅ "docs: update README with installation guide"

### Bad Commit Messages:
❌ "update"
❌ "changes"
❌ "wip"
❌ "asdfsdf"

### Commit Format:
```
type: short description (50 chars max)
[blank line]
- Detailed bullet point 1
- Detailed bullet point 2
[blank line]
Additional context or issue references
```

**Types:**
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation only
- `style:` Formatting, missing semicolons, etc.
- `refactor:` Code change that neither fixes a bug nor adds a feature
- `test:` Adding tests
- `chore:` Updating build tasks, package manager configs, etc.

---

## 🎯 READY TO COMMIT

**Execute these commands in order:**

1. **Check status:**
   ```bash
   cd "C:\Users\ahmed\Local Sites\ctadz-school\app\public\wp-content\plugins\school-management"
   git status
   ```

2. **Commit v0.4.1 (if needed):**
   ```bash
   git add school-management.php includes/class-sm-*.php includes/sm-*.php
   git commit -m "feat: complete payment model system v0.4.1"
   ```

3. **Update courses file:**
   ```bash
   copy class-sm-courses-page-UPDATED.php includes\class-sm-courses-page.php
   ```

4. **Commit v0.4.2:**
   ```bash
   git add includes/class-sm-courses-page.php
   git commit -m "feat: add payment model display and filtering to courses list"
   ```

5. **Verify:**
   ```bash
   git log --oneline -5
   git status
   ```

---

## 📞 NEED HELP?

If you get stuck, share:
1. Output of `git status`
2. Output of `git log --oneline -5`
3. Any error messages
4. Which step you're on

I'll help you resolve it!

---

## ✅ SUCCESS CRITERIA

You're done when:
- [ ] `git status` shows clean working tree
- [ ] `git log` shows both v0.4.1 and v0.4.2 commits
- [ ] All files are properly committed
- [ ] No "untracked files" warnings (for core files)
- [ ] Ready to merge to main or push to remote

---

**Let's get your git history clean and organized!** 🚀

**Run `git status` first and share the output - I'll guide you through the rest!**
