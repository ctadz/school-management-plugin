# Using Auto-Updater with Private GitHub Repository

Since `ctadz/school-management-plugin` is a **private repository**, WordPress cannot access it without authentication.

## Quick Setup (3 Steps)

### Step 1: Create GitHub Personal Access Token

1. Go to: https://github.com/settings/tokens/new
2. Set:
   - **Name**: `School Management Plugin Updater`
   - **Expiration**: Choose "No expiration" or a long period (1 year+)
   - **Scopes**: Check **only** `repo` (Full control of private repositories)
3. Click "Generate token"
4. **⚠️ COPY THE TOKEN NOW** (you won't see it again!)
   - It will look like: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### Step 2: Add Token to WordPress

Add this line to your `wp-config.php` file (above the "/* That's all, stop editing! */" line):

```php
// GitHub token for School Management auto-updates (private repo)
define( 'SM_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' );
```

**Important:**
- Replace `ghp_xxxx...` with your actual token
- Keep this token **SECRET** - never commit it to git
- Add to `.gitignore` if you version control wp-config.php

### Step 3: Test the Update

1. Go to **WordPress Admin → Dashboard → Updates**
2. Click "Check Again"
3. You should now see the update notification

## For Multiple Sites

If you have multiple WordPress sites using this plugin:

1. Use the **SAME token** on all sites (just copy the `define()` line to each wp-config.php)
2. OR create a separate token for each site (better for security)

## Security Notes

✅ **DO:**
- Store token in `wp-config.php` (not tracked by git)
- Use a token with minimal permissions (`repo` scope only)
- Rotate tokens periodically (generate new, update sites, delete old)

❌ **DON'T:**
- Hardcode token directly in the plugin code
- Commit token to git repository
- Share token publicly or in support tickets

## Troubleshooting

### "Update still not showing"

1. Clear WordPress cache:
   ```php
   // Add to wp-config.php temporarily, visit site, then remove
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   ```

2. Check `wp-content/debug.log` for errors like:
   ```
   GitHub API Error: ...
   GitHub API returned status code: 403
   ```

3. Verify token is correct:
   - Test it: `curl -H "Authorization: token ghp_xxxx..." https://api.github.com/repos/ctadz/school-management-plugin/releases/latest`
   - Should return JSON with release info, not 404

### "GitHub API rate limit exceeded"

Without token: 60 requests/hour per IP
With token: 5,000 requests/hour

If you hit the limit, the updater will retry after 12 hours (cache expiration).

## Alternative: Make Repository Public

If you don't need to keep the code private:

```bash
# Via GitHub CLI
gh repo edit ctadz/school-management-plugin --visibility public

# Or via web interface
# https://github.com/ctadz/school-management-plugin/settings
# → Danger Zone → Change visibility → Make public
```

After making it public, **remove the token** from wp-config.php (not needed anymore).

---

**Need Help?**
Check the [Auto-Update Documentation](Docs/AUTOMATIC-UPDATE-SYSTEM.md) for more details.
