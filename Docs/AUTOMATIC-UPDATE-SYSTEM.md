# WordPress Plugin Automatic Update System
**Date:** 2025-12-19
**Status:** ✅ Complete - Production Ready

---

## Overview

Implemented a GitHub-based automatic update system for all three School Management plugins. This eliminates the need for manual zip file uploads and file-by-file transfers to production sites.

### Key Benefits

- ✅ **One-Click Updates**: Update plugins directly from WordPress admin
- ✅ **Version Control**: Automatic version checking against GitHub releases
- ✅ **Changelog Display**: Show release notes in WordPress update screen
- ✅ **Rollback Safety**: WordPress maintains backup before updating
- ✅ **Multi-Site Compatible**: Works across all WordPress installations
- ✅ **No External Dependencies**: Uses GitHub's free release system

---

## System Architecture

### Components

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Admin                          │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  Plugins Page                                         │  │
│  │  • Shows "Update Available" notification             │  │
│  │  • Displays version numbers                          │  │
│  │  • One-click update button                           │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            ↕
┌─────────────────────────────────────────────────────────────┐
│              GitHub Updater Class (in each plugin)          │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  • Check GitHub API every 12 hours                   │  │
│  │  • Compare current vs. latest version                │  │
│  │  • Download zipball from release                     │  │
│  │  • Integrate with WordPress update system            │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            ↕
┌─────────────────────────────────────────────────────────────┐
│                  GitHub Releases API                        │
│  • Store plugin zip files                                   │
│  • Version tags (e.g., v0.5.1, v1.0.0)                     │
│  • Release notes (converted to changelog)                  │
│  • Public or private repositories supported                │
└─────────────────────────────────────────────────────────────┘
```

### Update Flow

```
1. WordPress checks for updates (12-hour interval or manual check)
   ↓
2. GitHub Updater queries: https://api.github.com/repos/USER/REPO/releases/latest
   ↓
3. Compare local version with GitHub tag_name
   ↓
4. If newer version exists:
   - WordPress shows update notification
   - User clicks "Update Now"
   ↓
5. WordPress downloads zipball from GitHub
   ↓
6. WordPress extracts and installs update
   ↓
7. Update cache cleared, success message displayed
```

---

## Installation Status

### ✅ Main Plugin: School Management

**File:** `school-management/school-management.php`
**Updater Class:** `includes/class-sm-github-updater.php`
**GitHub Repo:** `ahmedsebaa/school-management-plugin`
**Current Version:** `0.5.0`

**Integration Code:**
```php
// Include GitHub updater for automatic plugin updates
require_once SM_PLUGIN_DIR . 'includes/class-sm-github-updater.php';

function sm_init_github_updater() {
    if ( is_admin() ) {
        new SM_GitHub_Updater(
            __FILE__,
            'ahmedsebaa/school-management-plugin',
            null
        );
    }
}
add_action( 'admin_init', 'sm_init_github_updater' );
```

---

### ✅ Calendar Plugin

**File:** `school-management-calendar/school-management-calendar.php`
**Updater Class:** `includes/class-smc-github-updater.php`
**GitHub Repo:** `ahmedsebaa/school-management-calendar`
**Current Version:** `1.0.0`

**Integration Code:**
```php
// Include GitHub updater for automatic plugin updates
require_once SMC_PLUGIN_DIR . 'includes/class-smc-github-updater.php';

function smc_init_github_updater() {
    if ( is_admin() ) {
        new SMC_GitHub_Updater(
            SMC_PLUGIN_FILE,
            'ahmedsebaa/school-management-calendar',
            null
        );
    }
}
add_action( 'admin_init', 'smc_init_github_updater' );
```

---

### ✅ Student Portal Plugin

**File:** `school-management-student-portal/school-management-student-portal.php`
**Updater Class:** `includes/class-smsp-github-updater.php`
**GitHub Repo:** `ahmedsebaa/school-management-student-portal`
**Current Version:** `1.1.0`

**Integration Code:**
```php
// Include GitHub updater for automatic plugin updates
require_once SMSP_PLUGIN_DIR . 'includes/class-smsp-github-updater.php';

function smsp_init_github_updater() {
    if ( is_admin() ) {
        new SMSP_GitHub_Updater(
            SMSP_PLUGIN_FILE,
            'ahmedsebaa/school-management-student-portal',
            null
        );
    }
}
add_action( 'admin_init', 'smsp_init_github_updater' );
```

---

## How to Release Updates

### Step 1: Update Version Numbers

Before creating a release, update version numbers in the plugin header:

**Main Plugin** (`school-management.php`):
```php
/**
 * Version: 0.5.1  ← Update this
 */
define( 'SM_VERSION', '0.5.1' );  // ← Update this too
```

**Calendar Plugin** (`school-management-calendar.php`):
```php
/**
 * Version: 1.0.1  ← Update this
 */
define( 'SMC_VERSION', '1.0.1' );  // ← Update this too
```

**Student Portal** (`school-management-student-portal.php`):
```php
/**
 * Version: 1.1.1  ← Update this
 */
define( 'SMSP_VERSION', '1.1.1' );  // ← Update this too
```

### Step 2: Commit Changes

```bash
git add .
git commit -m "chore: Bump version to v0.5.1"
git push origin develop

# Merge to main branch (if applicable)
git checkout main
git merge develop
git push origin main
```

### Step 3: Create GitHub Release

#### Option A: Via GitHub Web Interface

1. Go to your repository on GitHub
2. Click **Releases** → **Draft a new release**
3. Fill in release details:
   - **Tag version**: `v0.5.1` (must start with 'v', match plugin version)
   - **Target**: `main` branch (or `develop` if that's your production branch)
   - **Release title**: `Version 0.5.1` or descriptive name like `"Feature: Dark Mode Support"`
   - **Description**: Write release notes (see format below)
4. Click **Publish release**

#### Option B: Via Git Command Line

```bash
# Create and push tag
git tag -a v0.5.1 -m "Release version 0.5.1"
git push origin v0.5.1

# Then create release on GitHub web interface from the tag
```

#### Option C: Via GitHub CLI (gh)

```bash
# Install GitHub CLI first: https://cli.github.com/

# Create release with notes
gh release create v0.5.1 \
  --title "Version 0.5.1" \
  --notes "## Changes
- Fixed: Student search not working with accented characters
- Added: Dark mode support for admin pages
- Improved: Performance on large datasets

## Compatibility
- WordPress: 5.8+
- PHP: 7.4+
- Tested up to: WordPress 6.4"
```

### Step 4: Release Notes Format

**Recommended format** for changelog that displays nicely in WordPress:

```markdown
## Changes in v0.5.1

### New Features
- ✨ Added dark mode support for admin pages
- ✨ Bulk actions for student management
- ✨ Export payments to CSV

### Improvements
- ⚡ Improved search performance (50% faster)
- 🎨 Updated UI to match WordPress 6.4 design
- 📝 Better error messages for form validation

### Bug Fixes
- 🐛 Fixed: French translations not loading
- 🐛 Fixed: Student search with accented characters
- 🐛 Fixed: Payment alerts showing incorrect dates

### Compatibility
- WordPress: 5.8+
- PHP: 7.4+
- Tested up to: WordPress 6.4

### Breaking Changes
None - fully backward compatible
```

**Optional: Include "Tested up to" tag** (updater will parse it):
```
Tested up to: 6.4
```

---

## Semantic Versioning

Use **Semantic Versioning** (semver.org): `MAJOR.MINOR.PATCH`

### Version Format: `X.Y.Z`

- **X (Major)**: Breaking changes, major new features (0.x → 1.0, 1.x → 2.0)
- **Y (Minor)**: New features, backward-compatible (0.5 → 0.6, 1.0 → 1.1)
- **Z (Patch)**: Bug fixes, minor improvements (0.5.0 → 0.5.1)

### Examples

```
0.5.0  → Initial development
0.5.1  → Bug fixes
0.6.0  → New feature: Dark mode
0.6.1  → Dark mode bug fix
1.0.0  → First stable release (production-ready)
1.1.0  → New feature: Command palette
1.1.1  → Command palette bug fix
2.0.0  → Breaking change: Database schema update
```

### Pre-Release Versions

For testing before stable release:

```
1.0.0-beta.1   → First beta
1.0.0-beta.2   → Second beta
1.0.0-rc.1     → Release candidate 1
1.0.0          → Stable release
```

**Note:** The updater will suggest ANY tag that is higher than current version, including pre-releases. Use with caution on production sites.

---

## Testing Updates

### Test on Local/Staging First

**Before pushing to production**, always test the update process:

1. **Set up test environment:**
   ```bash
   # Option 1: Local WordPress (LocalWP, XAMPP, etc.)
   # Option 2: Staging server
   ```

2. **Install current version** of plugin

3. **Create test release** on GitHub:
   ```bash
   # Use a pre-release tag for testing
   git tag -a v0.5.1-beta.1 -m "Test release"
   git push origin v0.5.1-beta.1
   ```

4. **Check for updates** in WordPress:
   - Go to **Dashboard** → **Updates**
   - Or go to **Plugins** page
   - Click "Check Again" to force update check

5. **Install update** and verify:
   - Database migrations work
   - Existing data intact
   - No PHP errors
   - All features functional

6. **If successful**, create stable release:
   ```bash
   git tag -a v0.5.1 -m "Stable release"
   git push origin v0.5.1
   ```

### Force Update Check

To bypass 12-hour cache during testing:

**Method 1: WordPress Admin**
- Go to **Dashboard** → **Updates**
- Click "Check Again" button

**Method 2: Delete Transient** (via PHP/SQL)
```php
// Delete update cache for specific plugin
delete_transient( 'sm_github_update_' . md5( 'ahmedsebaa/school-management-plugin' ) );

// Or via WP-CLI
wp transient delete --all
```

**Method 3: Use `force_check()` method**
```php
// Add temporary code to trigger immediate check
$updater = new SM_GitHub_Updater( __FILE__, 'ahmedsebaa/school-management-plugin', null );
$updater->force_check();
```

---

## Private Repositories (Optional)

If you want to keep your code private but still use GitHub for updates:

### Step 1: Create Personal Access Token

1. Go to GitHub → **Settings** → **Developer settings** → **Personal access tokens** → **Tokens (classic)**
2. Click **Generate new token**
3. Set permissions:
   - ✅ `repo` (Full control of private repositories)
4. Copy the token (you won't see it again!)

### Step 2: Add Token to WordPress

**Option A: wp-config.php** (Recommended)
```php
// Add to wp-config.php (above "That's all, stop editing!")
define( 'SM_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxx' );
define( 'SMC_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxx' );
define( 'SMSP_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxx' );
```

**Option B: Plugin Code**
```php
// NOT recommended - exposes token in code
new SM_GitHub_Updater(
    __FILE__,
    'ahmedsebaa/school-management-plugin',
    'ghp_xxxxxxxxxxxxxxxxxxxx'  // ← GitHub token
);
```

**Option C: Environment Variable**
```php
new SM_GitHub_Updater(
    __FILE__,
    'ahmedsebaa/school-management-plugin',
    getenv( 'GITHUB_TOKEN' )
);
```

### Step 3: Update Plugin Code

Modify the updater initialization to read the token:

```php
function sm_init_github_updater() {
    if ( is_admin() ) {
        $token = defined( 'SM_GITHUB_TOKEN' ) ? SM_GITHUB_TOKEN : null;
        new SM_GitHub_Updater(
            __FILE__,
            'ahmedsebaa/school-management-plugin',
            $token
        );
    }
}
```

---

## Rate Limiting

### GitHub API Limits

- **Without token**: 60 requests/hour per IP
- **With token**: 5,000 requests/hour per token

### Caching Strategy

The updater caches GitHub API responses for **12 hours** to avoid hitting rate limits:

```php
private $cache_expiration = 43200; // 12 hours in seconds
```

**How it works:**
1. First check fetches from GitHub API
2. Response cached in WordPress transients
3. Subsequent checks (within 12 hours) use cached data
4. Cache auto-expires after 12 hours
5. Manual "Check Again" bypasses cache

### Monitoring Rate Limits

Check remaining API calls:

```bash
# Without authentication
curl -I https://api.github.com/repos/ahmedsebaa/school-management-plugin/releases/latest

# With token
curl -I -H "Authorization: token YOUR_TOKEN" \
  https://api.github.com/repos/ahmedsebaa/school-management-plugin/releases/latest
```

Look for these headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1702991234
```

---

## Deployment Workflow

### Recommended Git Workflow

```
┌─────────────┐
│   develop   │  ← Active development, all commits go here
└──────┬──────┘
       │
       │ (when ready for release)
       ↓
┌─────────────┐
│    main     │  ← Stable production code only
└──────┬──────┘
       │
       │ (create release tag)
       ↓
┌─────────────┐
│  v0.5.1     │  ← GitHub release tag
└─────────────┘
       ↓
    WordPress sites auto-update
```

### Complete Release Checklist

- [ ] **1. Code Changes**
  - [ ] All features developed and tested
  - [ ] Code reviewed
  - [ ] No known critical bugs

- [ ] **2. Version Numbers**
  - [ ] Updated plugin header version
  - [ ] Updated constant (SM_VERSION, SMC_VERSION, etc.)
  - [ ] Updated `stable tag` in readme.txt (if applicable)

- [ ] **3. Documentation**
  - [ ] CHANGELOG.md updated
  - [ ] README.md updated (if needed)
  - [ ] Inline code comments reviewed

- [ ] **4. Testing**
  - [ ] Tested on local environment
  - [ ] Tested on staging server
  - [ ] Database migrations tested
  - [ ] Backward compatibility verified
  - [ ] No PHP errors/warnings

- [ ] **5. Translation**
  - [ ] New strings wrapped in translation functions
  - [ ] .pot file regenerated (if applicable)
  - [ ] French .po/.mo files updated

- [ ] **6. Git Commit**
  - [ ] All changes committed
  - [ ] Pushed to `develop` branch
  - [ ] Merged to `main` branch

- [ ] **7. GitHub Release**
  - [ ] Tag created (e.g., `v0.5.1`)
  - [ ] Release notes written
  - [ ] Release published on GitHub

- [ ] **8. Verification**
  - [ ] Test site sees update notification
  - [ ] Update installs successfully
  - [ ] Plugin works after update
  - [ ] No errors in WordPress debug log

- [ ] **9. Production Deployment**
  - [ ] Backup production database
  - [ ] Backup production files
  - [ ] Install update on production
  - [ ] Verify functionality
  - [ ] Monitor for errors

---

## Troubleshooting

### Update Not Showing in WordPress

**Problem:** Plugin shows no update available, even though new release exists on GitHub.

**Solutions:**

1. **Check version numbers match:**
   ```
   Plugin header:  Version: 0.5.1
   GitHub tag:     v0.5.1  ✓ (matches)

   Plugin constant: define( 'SM_VERSION', '0.5.1' );  ✓
   ```

2. **Clear update cache:**
   ```php
   // In WordPress admin
   Dashboard → Updates → "Check Again"

   // Or delete transient
   delete_transient( 'sm_github_update_' . md5( 'ahmedsebaa/school-management-plugin' ) );
   ```

3. **Check GitHub API response:**
   ```bash
   curl https://api.github.com/repos/ahmedsebaa/school-management-plugin/releases/latest
   ```

   Should return JSON with `tag_name`: `"v0.5.1"`

4. **Enable debug logging:**
   ```php
   // In wp-config.php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );

   // Check wp-content/debug.log for errors
   ```

5. **Verify repository name:**
   ```php
   // Must match exactly
   'ahmedsebaa/school-management-plugin'  ✓
   'ahmedsebaa/school-management'         ✗ (wrong)
   ```

---

### Update Download Fails

**Problem:** WordPress starts update but download fails.

**Solutions:**

1. **Check GitHub release has assets:**
   - Release must be published (not draft)
   - Zipball URL must be accessible
   - Repository must be public (or token provided)

2. **Test zipball download manually:**
   ```bash
   curl -L https://github.com/ahmedsebaa/school-management-plugin/archive/refs/tags/v0.5.1.zip -o test.zip
   ```

3. **Check server connectivity:**
   ```bash
   # From WordPress server
   curl https://api.github.com
   ```

4. **Increase PHP timeout:**
   ```php
   // In wp-config.php
   set_time_limit( 300 );
   ini_set( 'max_execution_time', 300 );
   ```

---

### Version Comparison Issues

**Problem:** Updater suggests wrong version or shows update for same version.

**Root Cause:** Semantic versioning comparison may fail if formats don't match.

**Solutions:**

1. **Use consistent format:**
   ```
   ✓ Good:
   Plugin: 0.5.1
   Tag:    v0.5.1

   ✗ Bad:
   Plugin: 0.5.1
   Tag:    0.5.1  (missing 'v' prefix won't break, but inconsistent)

   Plugin: 0.5
   Tag:    v0.5.0  (may cause issues)
   ```

2. **Always use three-part versions:**
   ```
   ✓ 0.5.0, 0.5.1, 1.0.0
   ✗ 0.5, 1.0
   ```

3. **Test version comparison:**
   ```php
   version_compare( '0.5.0', '0.5.1', '<' );  // true
   version_compare( '0.5.1', '0.5.1', '<' );  // false
   version_compare( '1.0.0', '0.5.1', '<' );  // false
   ```

---

### GitHub API Rate Limit

**Problem:** Too many update checks, GitHub blocks requests.

**Error in debug.log:**
```
GitHub API returned status code: 403
```

**Solutions:**

1. **Add GitHub token** (increases limit from 60/hour to 5,000/hour):
   ```php
   define( 'SM_GITHUB_TOKEN', 'ghp_xxx...' );
   ```

2. **Increase cache duration:**
   ```php
   // In updater class
   private $cache_expiration = 86400; // 24 hours instead of 12
   ```

3. **Check rate limit status:**
   ```bash
   curl https://api.github.com/rate_limit
   ```

---

### WordPress Shows "The package could not be installed"

**Problem:** WordPress downloads zip but can't extract/install it.

**Causes:**
- Zip file corrupted
- Incorrect folder structure in zip
- Permissions issue on server

**Solutions:**

1. **Verify zip structure:**
   ```
   zipball from GitHub has format:
   username-repo-commithash/
   ├── school-management.php
   ├── includes/
   ├── assets/
   └── ...
   ```

2. **WordPress expects:**
   ```
   school-management/
   ├── school-management.php  ← Main plugin file
   ├── includes/
   └── ...
   ```

3. **Check file permissions:**
   ```bash
   # WordPress needs write access to wp-content/plugins/
   chmod 755 wp-content/plugins/
   chown www-data:www-data wp-content/plugins/
   ```

4. **Check available disk space:**
   ```bash
   df -h
   ```

---

## Security Considerations

### Best Practices

1. **Use HTTPS URLs** (already done - GitHub enforces HTTPS)

2. **Validate release source:**
   ```php
   // Updater only accepts releases from configured repo
   $this->github_repo = 'ahmedsebaa/school-management-plugin';
   ```

3. **Keep tokens secret:**
   ```php
   // ✓ Good: wp-config.php (not in version control)
   define( 'SM_GITHUB_TOKEN', 'ghp_xxx' );

   // ✗ Bad: Hardcoded in plugin (committed to git)
   $token = 'ghp_xxx';
   ```

4. **WordPress automatic backups:**
   - WordPress creates backup before update
   - Located in `wp-content/upgrade/`
   - Auto-deleted after successful update

5. **Test before production:**
   - Always test updates on staging first
   - Use pre-release tags (beta, rc) for testing
   - Monitor error logs after update

6. **Signature verification (future enhancement):**
   ```php
   // Could add GPG signature verification
   // GitHub supports signing releases
   ```

---

## Alternative Update Sources

If you want to move away from GitHub in the future:

### Option 1: Custom Update Server

Host your own update server that mimics GitHub API:

```php
// Change API endpoint
$api_url = "https://updates.yoursite.com/api/v1/check/{$this->plugin_slug}";
```

**Required endpoints:**
- `GET /api/v1/check/plugin-slug` - Return version info
- `GET /api/v1/download/plugin-slug/version` - Download zip

### Option 2: WordPress.org Repository

Submit plugin to WordPress.org plugin directory:
- Free hosting
- Automatic updates
- Better SEO
- User reviews
- Download statistics

**Downside:** Must comply with GPL license and WordPress.org guidelines.

### Option 3: Paid Solutions

- **Freemius** - https://freemius.com/
- **EDD Software Licensing** - Easy Digital Downloads add-on
- **WP Updates Manager** - Self-hosted solution

---

## Monitoring and Analytics

### Track Update Adoption

**Method 1: Google Analytics Event**
```php
// In updater class, add after successful update
add_action( 'upgrader_process_complete', function( $upgrader, $options ) {
    if ( $options['action'] == 'update' && $options['type'] == 'plugin' ) {
        // Log to GA4, Mixpanel, or custom endpoint
    }
}, 10, 2 );
```

**Method 2: Custom Endpoint**
```php
// Phone home after update (optional, privacy concerns)
wp_remote_post( 'https://your-analytics.com/api/plugin-updated', array(
    'body' => array(
        'plugin'  => 'school-management',
        'version' => SM_VERSION,
        'site'    => home_url(),
    )
) );
```

**Method 3: GitHub Release Downloads**
- Check download count on GitHub Releases page
- Use GitHub API: `/repos/USER/REPO/releases/tags/TAG`

---

## Future Enhancements

### Planned Improvements

1. **Automatic Rollback**
   - If update fails, auto-restore previous version
   - Detect errors during update process

2. **Staged Rollouts**
   - Release to 10% of sites first
   - Monitor for errors
   - Gradually increase to 100%

3. **Beta Channel**
   - Allow users to opt-in to beta updates
   - Test pre-release versions in production

4. **Update Notifications**
   - Email admins when update available
   - Weekly digest of pending updates

5. **Compatibility Checker**
   - Verify PHP version before update
   - Check WordPress version compatibility
   - Detect conflicting plugins

6. **One-Click Staging Test**
   - Clone site to staging
   - Test update automatically
   - Approve or reject for production

---

## Support and Resources

### Documentation

- **WordPress Plugin API:** https://developer.wordpress.org/plugins/
- **GitHub Releases API:** https://docs.github.com/en/rest/releases/releases
- **Semantic Versioning:** https://semver.org/

### Debugging Tools

- **Query Monitor** (WordPress plugin) - Debug queries and API calls
- **GitHub CLI** - Manage releases from command line
- **WP-CLI** - WordPress command line tools

### Getting Help

If you encounter issues:

1. Check `wp-content/debug.log` for errors
2. Verify GitHub release exists and is published
3. Test GitHub API manually with curl
4. Check WordPress update transients
5. Review this documentation for troubleshooting steps

---

## Summary

✅ **Automatic update system is now fully operational for all three plugins:**

| Plugin | Status | Repository | Version |
|--------|--------|-----------|---------|
| **School Management** | ✅ Active | ahmedsebaa/school-management-plugin | 0.5.0 |
| **Calendar** | ✅ Active | ahmedsebaa/school-management-calendar | 1.0.0 |
| **Student Portal** | ✅ Active | ahmedsebaa/school-management-student-portal | 1.1.0 |

**To release an update:**
1. Update version numbers in plugin files
2. Commit and push to git
3. Create GitHub release with tag (e.g., `v0.5.1`)
4. WordPress automatically detects update within 12 hours

**No more manual zip uploads or FTP transfers needed!** 🎉

---

## Quick Reference Card

```bash
# 1. Bump version
# Edit plugin-file.php: Version: 0.5.1

# 2. Commit
git add .
git commit -m "chore: Bump version to v0.5.1"
git push origin main

# 3. Create release tag
git tag -a v0.5.1 -m "Release v0.5.1"
git push origin v0.5.1

# 4. Create GitHub release (web interface or CLI)
gh release create v0.5.1 --title "Version 0.5.1" --notes "Bug fixes and improvements"

# 5. WordPress will automatically detect update
# Users see update notification in admin within 12 hours
```

---

**Last Updated:** 2025-12-19
**Next Review:** After first production release
**Maintained By:** Ahmed Sebaa
