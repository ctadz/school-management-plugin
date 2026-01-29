# Release Process - Plugin Auto-Update Zip Files

This document describes how to generate zip files and create GitHub releases for the automatic update system across all three plugins.

## Overview

Each plugin has a GitHub-based auto-updater that checks the **latest GitHub release** for a newer version. When WordPress checks for updates, the updater:

1. Calls `https://api.github.com/repos/{owner}/{repo}/releases/latest`
2. Compares the release tag (e.g., `v0.6.0`) against the installed version
3. If newer, downloads the attached `.zip` asset and installs it

## Plugins and Repositories

| Plugin | Repository | Branch | Updater Looks For |
|--------|-----------|--------|-------------------|
| School Management | `ctadz/school-management-plugin` | `develop` | `school-management.zip` asset, falls back to zipball |
| Student Portal | `ahmedsebaa/school-management-student-portal` | `master` | GitHub zipball (auto-generated) |
| Calendar | `ahmedsebaa/school-management-calendar` | `develop` | GitHub zipball (auto-generated) |

## Prerequisites

- **Git** installed and configured
- **GitHub CLI (`gh`)** installed and authenticated (`gh auth login`)
  - On Windows, the path is typically: `"C:\Program Files\GitHub CLI\gh.exe"`
- All changes committed and pushed to the remote repository

## Step-by-Step Process

### 1. Bump the Version Number

Each plugin has two places where the version is defined:

**school-management** (`school-management.php`):
```php
 * Version:     0.6.0        // Plugin header
define( 'SM_VERSION', '0.6.0' );  // PHP constant
```

**school-management-student-portal** (`school-management-student-portal.php`):
```php
 * Version: 1.1.1                    // Plugin header
define( 'SMSP_VERSION', '1.1.1' );  // PHP constant
```

**school-management-calendar** (`school-management-calendar.php`):
```php
 * Version: 1.0.1                   // Plugin header
define( 'SMC_VERSION', '1.0.1' );  // PHP constant
```

**Important**: Both the header comment and the `define()` constant must match.

### 2. Commit and Push

```bash
# For each plugin directory:
git add -A
git commit -m "chore: Bump version to X.Y.Z"
git push origin <branch>
```

### 3. Generate Zip Files

Use `git archive` to create clean zip files. This command:
- Only includes tracked (committed) files
- Automatically excludes `.git/`, `.claude/`, etc.
- Sets the correct folder name inside the zip via `--prefix`

```bash
# School Management (main plugin)
cd /path/to/plugins/school-management
git archive --format=zip --prefix=school-management/ -o school-management.zip HEAD

# Student Portal
cd /path/to/plugins/school-management-student-portal
git archive --format=zip --prefix=school-management-student-portal/ -o school-management-student-portal.zip HEAD

# Calendar
cd /path/to/plugins/school-management-calendar
git archive --format=zip --prefix=school-management-calendar/ -o school-management-calendar.zip HEAD
```

**Critical**: The `--prefix` value MUST match the plugin's folder name in `wp-content/plugins/`. WordPress uses this folder name to identify the plugin during updates.

### 4. Merge develop into main

After testing in develop, merge the changes into the main branch:

```bash
# School Management
cd /path/to/plugins/school-management
git checkout main
git merge develop -m "Merge develop into main for vX.Y.Z release"
git push origin main
git checkout develop

# Calendar
cd /path/to/plugins/school-management-calendar
git checkout main
git merge develop -m "Merge develop into main for vX.Y.Z release"
git push origin main
git checkout develop

# Student Portal (if needed)
cd /path/to/plugins/school-management-student-portal
git checkout master  # Note: uses master instead of main
git merge develop -m "Merge develop into master for vX.Y.Z release"
git push origin master
git checkout develop
```

**Important**: This step ensures both `develop` and `main` branches are in sync with the release version.

### 5. Create GitHub Releases

Use the GitHub CLI to create releases with the zip files attached:

```bash
# School Management
cd /path/to/plugins/school-management
gh release create v0.6.0 school-management.zip \
  --title "v0.6.0" \
  --notes "Release notes here..." \
  --target develop

# Student Portal
cd /path/to/plugins/school-management-student-portal
gh release create v1.1.1 school-management-student-portal.zip \
  --title "v1.1.1" \
  --notes "Release notes here..." \
  --target master

# Calendar
cd /path/to/plugins/school-management-calendar
gh release create v1.0.1 school-management-calendar.zip \
  --title "v1.0.1" \
  --notes "Release notes here..." \
  --target develop
```

**Tag format**: Always use `vX.Y.Z` (e.g., `v0.6.0`). The updater strips the `v` prefix when comparing versions.

### 6. Verify

After creating the releases:

1. Visit each release page on GitHub to confirm the zip asset is attached
2. On the live WordPress site, go to **Dashboard > Updates** and click **Check Again**
3. The plugins should appear as available updates within 12 hours (cache TTL) or immediately after clicking Check Again

## Windows-Specific Notes

On Windows with Git Bash, the `gh` CLI path contains spaces. Use quotes:

```bash
"/c/Program Files/GitHub CLI/gh.exe" release create v0.6.0 ...
```

The `zip` command may not be available in Git Bash. Use `git archive` instead (it's always available since Git is installed).

## Troubleshooting

### Release already exists at that tag
```
a release with the same tag name already exists: v1.1.0
```
You need to bump the version number. The auto-updater won't trigger if the release version matches the installed version.

### Update not showing in WordPress
- The updater caches results for **12 hours**. Clear the transient in the database: `DELETE FROM wp_options WHERE option_name LIKE '%sm_github_update%'`
- Verify the release tag follows `vX.Y.Z` format
- Check that the zip asset is properly attached to the release (not just the auto-generated source zip)
- Check PHP error logs for GitHub API errors

### Wrong folder name after update
If the plugin installs to a wrong folder name (e.g., `ctadz-school-management-plugin-abc1234/`), it means the zipball fallback was used instead of a proper asset. Always attach a zip with `--prefix=plugin-folder-name/` set correctly.

## Version History

| Date | Plugin | Version | Notes |
|------|--------|---------|-------|
| 2026-01-28 | School Management | v0.6.2 | Vacation-aware subscription payments, auto-generation fix |
| 2026-01-28 | Calendar | v1.1.0 | Multi-day vacation events, payment integration |
| 2026-01-27 | School Management | v0.6.1 | GitHub updater improvements |
| 2026-01-27 | School Management | v0.6.0 | Dropdown refresh, payment alerts, roles, attendance |
| 2026-01-27 | Student Portal | v1.1.1 | Translatable JS strings, French translations |
| 2026-01-27 | Calendar | v1.0.2 | French translation update |
