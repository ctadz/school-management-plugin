# Deployment Guide - v0.6.0

**Version**: 0.6.0
**Deployment Date**: [To be filled]
**Deployed By**: [To be filled]

---

## Pre-Deployment Checklist

### 1. Backup Current System ✅

**Critical**: Always backup before deploying major updates!

#### Database Backup

```bash
# Using WP-CLI
wp db export backup-pre-v0.6.0.sql

# Or use phpMyAdmin
# Export entire database to SQL file
```

**Save backup to**: Safe location outside web root

#### Files Backup

```bash
# Backup current plugin
cp -r wp-content/plugins/school-management wp-content/plugins/school-management-backup-v0.5.6

# Or use hosting control panel
# Download entire wp-content/plugins/school-management folder
```

**Backup Location**: `backups/school-management-pre-v0.6.0-[DATE].zip`

---

### 2. Verify Test Environment ✅

- [ ] All tests passed on local/staging environment
- [ ] Menu structure verified (3 separate menus visible)
- [ ] Student registration workflow tested
- [ ] Enrollment workflow tested
- [ ] Payment collection tested
- [ ] Accountant role tested
- [ ] All existing data intact
- [ ] No PHP errors in error log
- [ ] No JavaScript console errors

---

### 3. Maintenance Mode ✅

Before deployment, enable maintenance mode:

**Option A: Using Plugin**
- Install "WP Maintenance Mode" plugin
- Enable maintenance mode
- Set custom message: "System upgrade in progress. Back online soon!"

**Option B: Manual**
Create `.maintenance` file in WordPress root:
```php
<?php $upgrading = time(); ?>
```

---

## Deployment Steps

### Step 1: Deploy Plugin Files

#### Method A: Git Pull (Recommended)

```bash
cd wp-content/plugins/school-management
git fetch origin
git checkout main
git pull origin main
```

#### Method B: FTP Upload

1. Download latest release: `school-management-v0.6.0.zip`
2. Extract locally
3. Upload to server, overwriting existing files:
   - `school-management/school-management.php`
   - `school-management/includes/class-sm-admin-menu.php`
   - `school-management/includes/class-sm-students-page.php`
   - `school-management/includes/class-sm-roles.php`
   - `school-management/CHANGELOG.md`
   - `school-management/ROADMAP.md`
   - `school-management/docs/` (entire folder)

#### Method C: WordPress Auto-Update

1. Go to WordPress Admin → Plugins
2. If update available, click "Update Now"
3. Wait for completion

---

### Step 2: Verify File Upload

Check version number:

```bash
# Via WP-CLI
wp plugin list

# Or check file directly
head -20 wp-content/plugins/school-management/school-management.php | grep "Version:"
```

**Expected output**: `Version: 0.6.0`

---

### Step 3: Database Migration

**Good News**: v0.6.0 requires **NO database changes**!

- No new tables
- No new columns
- No data migration scripts
- Fully backward compatible

However, verify database integrity:

```bash
# Using WP-CLI
wp db check
```

---

### Step 4: Clear Caches

#### WordPress Object Cache

```bash
wp cache flush
```

#### Plugin Caches (if applicable)

```bash
# W3 Total Cache
wp w3-total-cache flush all

# WP Super Cache
wp cache flush

# Or use plugin admin interface
```

#### Browser Cache

**Important**: All users should hard refresh:
- Windows: `Ctrl + Shift + R`
- Mac: `Cmd + Shift + R`

---

### Step 5: Verify Deployment

#### A. Check Plugin Status

1. Log in to WordPress Admin
2. Go to **Plugins**
3. Verify "School Management" shows version **0.6.0**
4. Status should be "Active"

#### B. Verify Menu Structure

1. Check sidebar menu
2. You should see **3 separate menus**:
   - ✅ School Management (Academic)
   - ✅ School Finances (Financial)
   - ✅ School Settings (Plugin Management)

#### C. Verify Academic Dashboard

1. Click **School Management → Dashboard**
2. Should load without errors
3. Check widgets display correctly:
   - Total students count
   - Active courses
   - Enrollment trends chart
   - Payment status chart

#### D. Verify Financial Dashboard

1. Click **School Finances → Dashboard**
2. Should load without errors
3. Check widgets display correctly:
   - Outstanding balance
   - Total expected/collected
   - Payment alerts
   - Payment status chart

#### E. Verify Student Registration

1. Go to **School Management → Students → Add New**
2. Check form loads correctly
3. Verify **"Enroll in course now"** checkbox appears
4. Fill test data and save
5. Verify success message

#### F. Verify Data Integrity

Run quick checks:

```sql
-- Count students (should match pre-deployment)
SELECT COUNT(*) FROM wp_sm_students;

-- Count enrollments (should match pre-deployment)
SELECT COUNT(*) FROM wp_sm_enrollments;

-- Count payments (should match pre-deployment)
SELECT COUNT(*) FROM wp_sm_payment_schedules;

-- Check for any null/corrupt data
SELECT COUNT(*) FROM wp_sm_students WHERE name IS NULL OR name = '';
```

**Expected**: All counts match pre-deployment, no corrupt data.

---

### Step 6: Role Testing

#### Create Test Accountant User

1. Go to **Users → Add New**
2. Create test user:
   ```
   Username: test_accountant
   Email: test@school.local
   Role: School Accountant
   Password: [strong password]
   ```
3. Log out as admin
4. Log in as test_accountant
5. Verify:
   - Auto-redirects to Financial Dashboard
   - Only sees "School Finances" menu
   - Cannot access Academic Management
   - Cannot access Settings
6. Test payment workflows
7. Log out

#### Verify Other Roles

Test each role briefly:
- [ ] Administrator - sees all 3 menus
- [ ] School Admin - sees Academic + Financial
- [ ] School Accountant - sees Financial only
- [ ] Teacher - sees limited menu

---

### Step 7: Disable Maintenance Mode

**Option A: Plugin**
- Disable "WP Maintenance Mode" plugin

**Option B: Manual**
```bash
rm .maintenance
```

**Option C: Automatic**
- WordPress auto-removes after plugin update completes

---

### Step 8: Monitor for Issues

#### First Hour

- [ ] Monitor error logs: `wp-content/debug.log`
- [ ] Check PHP error log
- [ ] Monitor browser console for JavaScript errors
- [ ] Test key workflows (registration, enrollment, payment)

#### First Day

- [ ] Check with staff for any issues
- [ ] Monitor user feedback
- [ ] Review error logs again
- [ ] Test on different browsers

#### First Week

- [ ] Gather user feedback
- [ ] Document any issues
- [ ] Create GitHub issues if needed
- [ ] Plan fixes if necessary

---

## Post-Deployment Tasks

### 1. Update GitHub

```bash
# Tag the release
git tag -a v0.6.0 -m "Release v0.6.0 - Simplified Architecture"
git push origin v0.6.0

# Create GitHub release
gh release create v0.6.0 \
  --title "v0.6.0 - Simplified Architecture" \
  --notes-file CHANGELOG.md \
  school-management.zip
```

### 2. Update Documentation Site (if applicable)

- Update version numbers
- Add v0.6.0 to changelog
- Update screenshots
- Publish announcements

### 3. Notify Users

**Email Template**:

```
Subject: School Management System Updated - New Features!

Dear Staff,

We've updated the School Management system to version 0.6.0 with exciting improvements:

🎉 What's New:
- Simplified menu structure (Academic, Financial, Settings)
- Easier student registration workflow
- New accountant role for financial staff
- Improved dashboards with charts

📚 Need Help?
- User Guide: [link to docs/USER-GUIDE.md]
- Role Guide: [link to docs/ROLES.md]
- Workflows: [link to docs/WORKFLOWS.md]

Please contact [support email] if you have any questions.

Best regards,
IT Team
```

### 4. Staff Training

Schedule brief training sessions for:

**Session 1: For All Staff (15 minutes)**
- Overview of new menu structure
- Where to find familiar features
- Q&A

**Session 2: For Receptionists (30 minutes)**
- New student registration workflow
- Using the "Enroll now" option
- Q&A

**Session 3: For Accountants (45 minutes)**
- School Accountant role explained
- Financial Dashboard tour
- Payment workflows
- Q&A

---

## Rollback Plan

If critical issues occur, rollback to v0.5.6:

### Quick Rollback

```bash
# Restore plugin files
rm -rf wp-content/plugins/school-management
mv wp-content/plugins/school-management-backup-v0.5.6 wp-content/plugins/school-management

# Clear caches
wp cache flush

# Verify version
wp plugin list
```

### Full Rollback (if database issues)

```bash
# Restore database
wp db import backup-pre-v0.6.0.sql

# Restore plugin files
# (as above)

# Clear all caches
wp cache flush
```

**Note**: v0.6.0 makes NO database changes, so full rollback shouldn't be needed.

---

## Troubleshooting

### Issue: Menu doesn't show 3 separate menus

**Symptoms**: Still seeing old single menu

**Solutions**:
1. Clear browser cache (hard refresh)
2. Clear WordPress object cache
3. Check plugin version is 0.6.0
4. Check for conflicting plugins
5. Check PHP error log

### Issue: Financial menu not visible

**Symptoms**: Can't see "School Finances" menu

**Solutions**:
1. Check user role has `manage_payments` capability
2. Log out and log back in
3. Clear browser cache
4. Verify plugin activated correctly

### Issue: Charts not displaying

**Symptoms**: Dashboard loads but charts missing

**Solutions**:
1. Check browser JavaScript console
2. Verify Chart.js is loading
3. Check for JavaScript conflicts
4. Try different browser

### Issue: Accountant can see academic pages

**Symptoms**: School Accountant role seeing too much

**Solutions**:
1. Verify role is exactly "School Accountant"
2. Check for role management plugins interfering
3. Deactivate other plugins temporarily
4. Check user capabilities: `wp user get [username] --field=roles`

### Issue: Enrollment redirect not working

**Symptoms**: After checking "Enroll now", no redirect

**Solutions**:
1. Check JavaScript console for errors
2. Verify form submission successful
3. Check PHP error log
4. Manually navigate to Enrollments page

---

## Performance Checklist

After deployment, verify performance:

- [ ] Dashboard loads in < 2 seconds
- [ ] Student list loads quickly (< 1 second for 100 students)
- [ ] Payment collection page responsive
- [ ] Charts render smoothly
- [ ] No N+1 database queries (check query monitor)
- [ ] Server memory usage normal
- [ ] No PHP timeouts

---

## Security Checklist

- [ ] All nonces verified
- [ ] No SQL injection vulnerabilities
- [ ] Input sanitization working
- [ ] Output escaping working
- [ ] Role capabilities enforced
- [ ] No unauthorized access possible
- [ ] Session security intact

---

## Success Criteria

Deployment is successful when:

✅ All 3 menus visible and functional
✅ Student registration workflow works with optional enrollment
✅ School Accountant role works as expected
✅ All existing data intact
✅ No PHP/JavaScript errors
✅ Performance within acceptable limits
✅ All roles tested and working
✅ Staff trained and comfortable with changes
✅ No critical bugs reported in first week

---

## Deployment Completion

**Deployed On**: [Date] [Time]
**Deployed By**: [Name]
**Version Verified**: [✓] 0.6.0
**Database Backup**: [✓] Completed
**Rollback Plan**: [✓] Ready
**Monitoring**: [✓] Active

**Status**: ✅ COMPLETE

**Sign-Off**:
- Technical Lead: ___________  Date: _______
- School Admin: ___________  Date: _______

---

## Support Contacts

**Technical Issues**:
- IT Support: [email/phone]
- GitHub Issues: https://github.com/ctadz/school-management-plugin/issues

**Training Questions**:
- Admin Team: [email/phone]

**Critical Bugs**:
- Emergency Contact: [email/phone]
- Available: 24/7

---

**Deployment Guide v1.0**
**For Plugin Version**: 0.6.0
**Created**: January 13, 2026
