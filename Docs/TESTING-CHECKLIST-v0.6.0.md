# Testing Checklist - v0.6.0

**Version**: 0.6.0 - Simplified Architecture
**Date**: January 13, 2026
**Tester**: _________________
**Environment**: Local Site (ctadz-school)

---

## Pre-Testing Verification

- [ ] Plugin version shows **0.6.0** in WordPress → Plugins page
- [ ] Plugin is **Active**
- [ ] No PHP errors in `wp-content/debug.log` (if WP_DEBUG enabled)
- [ ] Browser cache cleared (Ctrl+Shift+R)

---

## Test 1: Menu Structure (3-Category Architecture)

### As Administrator

**Expected**: Should see all 3 menus in WordPress admin sidebar

1. [ ] **School Management** (Academic) menu visible
   - Icon: Book/graduation cap
   - Position: Between Settings and Comments

2. [ ] **School Finances** (Financial) menu visible
   - Icon: Money/dollar sign
   - Position: After School Management

3. [ ] **School Settings** (Plugin Management) menu visible
   - Icon: Gear/settings
   - Position: After School Finances

### Verify Academic Menu Items

Click **School Management** → verify submenu items:

- [ ] Dashboard (with book icon)
- [ ] Students (with people icon)
- [ ] Teachers (with person icon)
- [ ] Courses (with book icon)
- [ ] Levels (with award icon)
- [ ] Classrooms (with building icon)
- [ ] Attendance (with checkmark icon)

### Verify Financial Menu Items

Click **School Finances** → verify submenu items:

- [ ] Dashboard (with dashboard icon)
- [ ] Enrollments & Plans (with learn icon)
- [ ] Payment Collection (with money icon)
- [ ] Payment Alerts (with warning icon - red)
- [ ] Payment Terms (with calendar icon)
- [ ] Family Discounts (with people icon - purple)

### Verify Settings Menu

Click **School Settings** → verify submenu:

- [ ] General Settings (with gear icon)

**Notes:**
```
[Write any issues here]
```

---

## Test 2: Academic Dashboard

1. [ ] Navigate to **School Management → Dashboard**
2. [ ] Page loads without errors
3. [ ] School name/logo displays at top
4. [ ] Subtitle shows "Academic Dashboard" (EN) or "Tableau de Bord Académique" (FR)

### Verify Widgets Display

- [ ] Total Students widget (with count)
- [ ] Active Teachers widget (with count)
- [ ] Active Courses widget (with count)
- [ ] Course Levels widget (with count)
- [ ] Active Enrollments widget (with count)
- [ ] Outstanding Balance widget (with amount)

### Verify Quick Actions

- [ ] "Quick Actions" section visible
- [ ] "Add New Student" button works
- [ ] "Add New Course" button works
- [ ] "New Enrollment" button works
- [ ] "Add New Teacher" button works

**Notes:**
```
[Write any issues here]
```

---

## Test 3: Financial Dashboard (NEW in v0.6.0)

1. [ ] Navigate to **School Finances → Dashboard**
2. [ ] Page loads without errors
3. [ ] School name/logo displays at top
4. [ ] Subtitle shows "Financial Dashboard" (EN) or "Tableau de Bord Financier" (FR)

### Verify Financial Widgets (NEW)

- [ ] **Outstanding Balance** widget (green border, money icon)
  - Shows total outstanding amount
  - "View Payments" button works

- [ ] **Total Expected** widget (teal border, chart icon)
  - Shows total expected revenue
  - Label: "Total revenue expected"

- [ ] **Total Collected** widget (green border, checkmark icon)
  - Shows total paid amount
  - Label: "Successfully collected"

- [ ] **Payment Alerts** widget (red/orange/yellow border depending on status)
  - Shows count of alerts
  - Shows alert text ("X overdue", "X due this week", etc.)
  - Color changes based on urgency
  - "View Alerts" button works

- [ ] **Active Enrollments** widget (blue border)
  - Shows count of active enrollments
  - "Manage Enrollments" button works

- [ ] **Payment Terms** widget (purple border)
  - Shows count of payment terms
  - "Manage Terms" button works

### Verify Quick Actions (Financial)

- [ ] "Quick Actions" section visible
- [ ] Label: "Common financial tasks:"
- [ ] "New Enrollment" button works
- [ ] "Collect Payment" button works
- [ ] "View Payment Alerts" button works
- [ ] "Manage Payment Terms" button works
- [ ] "Family Discounts" button works

### Verify Payment Status Chart

- [ ] "Payment Status Breakdown" chart visible
- [ ] Chart.js doughnut chart renders correctly
- [ ] Shows Paid, Partial, Pending segments
- [ ] Legend displays below chart
- [ ] Colors match: Green (Paid), Orange (Partial), Red (Pending)

**Notes:**
```
[Write any issues here]
```

---

## Test 4: Student Registration - Optional Enrollment (NEW)

### Test A: Register Student WITHOUT Enrollment

1. [ ] Navigate to **School Management → Students → Add New**
2. [ ] Fill in student information (name, email, phone, DOB, level)
3. [ ] Fill in parent/guardian information
4. [ ] **DO NOT** check "Enroll in a course now" checkbox
5. [ ] Click "Add Student"

**Expected Result:**
- [ ] Student created successfully
- [ ] Success message displays
- [ ] Redirects to Students list (NOT to enrollments)
- [ ] New student appears in list

### Test B: Register Student WITH Enrollment

1. [ ] Navigate to **School Management → Students → Add New**
2. [ ] Fill in student information
3. [ ] Fill in parent/guardian information
4. [ ] **CHECK** the "Enroll in a course now" checkbox

**Expected Result:**
- [ ] Enrollment section becomes visible (gray background box)
- [ ] Course dropdown appears
- [ ] Dropdown shows active courses with levels

5. [ ] Select a course from dropdown
6. [ ] Click "Add Student"

**Expected Result:**
- [ ] Student created successfully
- [ ] Success message: "Redirecting to Financial Management..."
- [ ] After 2 seconds, redirects to **School Finances → Enrollments & Plans → Add New**
- [ ] Student ID and Course ID are pre-filled in URL parameters
- [ ] Form opens with selected student and course pre-selected

### Test C: Verify Enrollment Section Only Appears for New Students

1. [ ] Go to Students list
2. [ ] Click "Edit" on any existing student

**Expected Result:**
- [ ] Enrollment section is **NOT visible** (only for new students)
- [ ] Form only shows student info and parent info sections

**Notes:**
```
[Write any issues here]
```

---

## Test 5: French Translations (NEW Strings)

### Switch to French

1. [ ] Go to **WordPress → Settings → General**
2. [ ] Change "Site Language" to **Français**
3. [ ] Click "Save Changes"
4. [ ] Clear browser cache (Ctrl+Shift+R)

### Verify Menu Translations

- [ ] "School Management" → "Gestion Scolaire"
- [ ] "School Finances" → "Finances Scolaires"
- [ ] "School Settings" → "Paramètres du Système"
- [ ] "Dashboard" → "Tableau de Bord"
- [ ] "Enrollments & Plans" → "Inscriptions & Plans"
- [ ] "Payment Collection" → "Collecte des Paiements"
- [ ] "Payment Alerts" → "Alertes de Paiement"
- [ ] "Family Discounts" → "Remises Familiales" (should already be translated)

### Verify Dashboard Translations

Navigate to **Finances Scolaires → Tableau de Bord**:

- [ ] "Financial Dashboard" → "Tableau de Bord Financier"
- [ ] "Outstanding Balance" → "Solde Impayé" (should already exist)
- [ ] "Total Expected" → "Total Attendu"
- [ ] "Total Collected" → "Total Collecté"
- [ ] "Total revenue expected" → "Revenu total attendu"
- [ ] "Successfully collected" → "Collecté avec succès"
- [ ] "Payment Alerts" → "Alertes de Paiement"
- [ ] "View Alerts" → "Voir les Alertes"
- [ ] "View Payments" → "Voir les Paiements"
- [ ] "All up to date" → "Tout est à jour" (if no alerts)

### Verify Payment Alert Plurals

Create or view payment alerts to test:

- [ ] "1 en retard" (1 overdue)
- [ ] "3 en retard" (3 overdue)
- [ ] "2 à payer cette semaine" (2 due this week)
- [ ] "5 à payer la semaine prochaine" (5 due next week)

### Verify Student Registration Translations

Navigate to **Gestion Scolaire → Étudiants → Ajouter un Nouvel Étudiant**:

- [ ] "Course Enrollment (Optional)" → "Inscription au Cours (Optionnel)"
- [ ] "You can enroll this student in a course now..." → "Vous pouvez inscrire cet étudiant..."
- [ ] "Enroll in a course now" → "Inscrire à un cours maintenant"
- [ ] "Choose a course..." → "Choisissez un cours..."
- [ ] "Payment details and enrollment setup..." → "Les détails du paiement..."

### Verify Quick Actions Translation

- [ ] "Quick Actions" → "Actions Rapides"
- [ ] "Common financial tasks:" → "Tâches financières courantes :"
- [ ] "Collect Payment" → "Collecter un Paiement"
- [ ] "View Payment Alerts" → "Voir les Alertes de Paiement"

### Switch Back to English

1. [ ] Go to **WordPress → Réglages → Général**
2. [ ] Change "Langue du site" back to **English (United States)**
3. [ ] Click "Enregistrer les modifications"

**Notes:**
```
[Write any issues here]
```

---

## Test 6: School Accountant Role (NEW)

### Create Test Accountant User

1. [ ] Navigate to **WordPress → Users → Add New**
2. [ ] Fill in user details:
   - Username: `test_accountant`
   - Email: `accountant@test.local`
   - Password: (generate strong password)
   - First Name: Test
   - Last Name: Accountant
3. [ ] Select Role: **School Accountant**
4. [ ] Click "Add New User"

### Test Accountant Login and Access

1. [ ] Log out as administrator
2. [ ] Log in as `test_accountant`

**Expected Result After Login:**
- [ ] Automatically redirects to **School Finances → Dashboard** (not Academic Dashboard)
- [ ] Admin toolbar hidden on frontend (if viewing site)

### Verify Menu Visibility for Accountant

**Menus that SHOULD be visible:**
- [ ] **School Finances** (full access)
  - Dashboard ✓
  - Enrollments & Plans ✓
  - Payment Collection ✓
  - Payment Alerts ✓
  - Payment Terms ✓
  - Family Discounts ✓

- [ ] **WordPress → Profile** (own profile only)

**Menus that should be HIDDEN:**
- [ ] School Management (Academic) - HIDDEN ✓
- [ ] School Settings - HIDDEN ✓
- [ ] WordPress → Dashboard - HIDDEN ✓
- [ ] WordPress → Posts - HIDDEN ✓
- [ ] WordPress → Media - HIDDEN ✓
- [ ] WordPress → Pages - HIDDEN ✓
- [ ] WordPress → Users - HIDDEN ✓
- [ ] WordPress → Tools - HIDDEN ✓
- [ ] WordPress → Settings - HIDDEN ✓

### Test Accountant Capabilities

As accountant, verify you CAN:
- [ ] View Financial Dashboard
- [ ] Create new enrollment
- [ ] Record payments
- [ ] View payment alerts
- [ ] Manage payment terms
- [ ] Calculate family discounts

As accountant, verify you CANNOT:
- [ ] Access Academic Dashboard (School Management)
- [ ] Create/edit students (no direct access)
- [ ] Create/edit teachers
- [ ] Create/edit courses
- [ ] Manage attendance
- [ ] Access plugin settings

### Test URL Direct Access Prevention

Try accessing these URLs directly (should be blocked):

1. [ ] `/wp-admin/admin.php?page=school-management`
   - Expected: Permission denied or redirect

2. [ ] `/wp-admin/admin.php?page=school-management-students`
   - Expected: Permission denied or redirect

3. [ ] `/wp-admin/admin.php?page=school-settings`
   - Expected: Permission denied or redirect

### Clean Up Test User

1. [ ] Log out as test accountant
2. [ ] Log back in as administrator
3. [ ] Go to **WordPress → Users**
4. [ ] Delete test_accountant user (or keep for future testing)

**Notes:**
```
[Write any issues here]
```

---

## Test 7: Verify Data Integrity

### Check Database Counts (Before/After)

Run these checks to ensure no data was lost:

#### Check Students Count
Navigate to **School Management → Students**
- [ ] Count matches expected number
- [ ] All student records display correctly
- [ ] Photos load correctly

#### Check Enrollments Count
Navigate to **School Finances → Enrollments & Plans**
- [ ] Count matches expected number
- [ ] All enrollment records display correctly
- [ ] Payment statuses show correctly

#### Check Payment Schedules
Navigate to **School Finances → Payment Collection**
- [ ] All payment schedules visible
- [ ] Amounts calculate correctly
- [ ] Dates display correctly

#### Check Family Discounts
Navigate to **School Finances → Family Discounts**
- [ ] Family groupings are correct
- [ ] Discount percentages applied correctly
- [ ] No duplicates or orphaned records

**Notes:**
```
[Write any issues here]
```

---

## Test 8: Error Checking

### Check PHP Error Log

1. [ ] Enable WordPress debugging if not already enabled:
   - Edit `wp-config.php`
   - Set `define('WP_DEBUG', true);`
   - Set `define('WP_DEBUG_LOG', true);`

2. [ ] Clear existing debug.log:
   ```bash
   rm wp-content/debug.log
   ```

3. [ ] Perform all tests above

4. [ ] Check for errors:
   ```bash
   cat wp-content/debug.log
   ```

**Expected Result:**
- [ ] No PHP errors
- [ ] No PHP warnings
- [ ] No PHP notices (or only notices from other plugins/themes)

### Check JavaScript Console

Open browser developer tools (F12) and check Console tab:

1. [ ] Visit Academic Dashboard
   - [ ] No JavaScript errors

2. [ ] Visit Financial Dashboard
   - [ ] No JavaScript errors
   - [ ] Chart.js loads successfully
   - [ ] Payment chart renders without errors

3. [ ] Visit Student Registration page
   - [ ] No JavaScript errors
   - [ ] Enrollment checkbox toggle works
   - [ ] Course dropdown appears/disappears correctly

**Notes:**
```
[Write any issues here]
```

---

## Test 9: Backward Compatibility

### Verify Old Features Still Work

Test that existing features weren't broken:

- [ ] Student registration (basic form)
- [ ] Course creation
- [ ] Teacher management
- [ ] Enrollment creation (direct, without student registration)
- [ ] Payment recording
- [ ] Family discount calculation (existing students)
- [ ] Payment alerts display
- [ ] Attendance marking (if Calendar plugin active)

**Notes:**
```
[Write any issues here]
```

---

## Test 10: Mobile Responsiveness

Test on mobile device or browser responsive mode:

1. [ ] Dashboard widgets stack correctly on mobile
2. [ ] Financial Dashboard widgets stack correctly
3. [ ] Menus are accessible on mobile
4. [ ] Student registration form is usable on mobile
5. [ ] Enrollment form is usable on mobile

**Notes:**
```
[Write any issues here]
```

---

## Final Checklist Before Git Push

### Pre-Push Verification

- [ ] All tests passed ✓
- [ ] No critical errors found ✓
- [ ] Translations working correctly ✓
- [ ] New features functioning as expected ✓
- [ ] Data integrity confirmed ✓
- [ ] Documentation updated ✓

### Files to Commit

Verify these files are ready for commit:

**Core Plugin Files:**
- [ ] `school-management.php` (version 0.6.0)
- [ ] `includes/class-sm-admin-menu.php` (3 menus, financial dashboard)
- [ ] `includes/class-sm-students-page.php` (optional enrollment)
- [ ] `includes/class-sm-roles.php` (school accountant role)

**Translation Files:**
- [ ] `languages/CTADZ-school-management-fr_FR.po` (updated)
- [ ] `languages/CTADZ-school-management-fr_FR.mo` (recompiled)
- [ ] `languages/CTADZ-school-management.pot` (metadata updated)

**Documentation Files:**
- [ ] `CHANGELOG.md` (v0.6.0 entry)
- [ ] `ROADMAP.md` (updated)
- [ ] `README.md` (v0.6.0 highlights)
- [ ] `docs/USER-GUIDE.md` (new)
- [ ] `docs/ROLES.md` (new)
- [ ] `docs/WORKFLOWS.md` (new)
- [ ] `DEPLOYMENT-GUIDE.md` (new)
- [ ] `SESSION-SUMMARY-2026-01-13.md` (new)
- [ ] `TRANSLATION-UPDATE-v0.6.0.md` (new)
- [ ] `TESTING-CHECKLIST-v0.6.0.md` (new, this file)

### Git Commands (After Testing Passes)

```bash
cd "C:\Users\ahmed\Local Sites\ctadz-school\app\public\wp-content\plugins\school-management"

# Check status
git status

# Add all changes
git add .

# Commit with descriptive message
git commit -m "Release v0.6.0 - Simplified Architecture

- Reorganized menu into 3 categories (Academic, Financial, Settings)
- Added Financial Dashboard with charts and widgets
- Simplified student registration with optional enrollment
- Added School Accountant role with financial-only access
- Updated French translations for all new strings
- Added comprehensive documentation (USER-GUIDE, ROLES, WORKFLOWS)
- Follows industry standards (OpenSIS/PowerSchool pattern)
- 100% backward compatible - no database changes

🤖 Generated with Claude Code
Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"

# Push to main branch (triggers auto-update)
git push origin main

# Create release tag
git tag -a v0.6.0 -m "Release v0.6.0 - Simplified Architecture"
git push origin v0.6.0
```

### Post-Push Monitoring

After pushing to git:

- [ ] Monitor GitHub for successful push
- [ ] Verify release tag created
- [ ] Check auto-update triggers on live site (12-hour interval)
- [ ] Monitor live site after update
- [ ] Check live site error logs
- [ ] Notify staff of new version

---

## Issue Log

Use this section to document any issues found during testing:

### Issue 1
**Severity**: [Critical/High/Medium/Low]
**Component**: [Dashboard/Menu/Translations/etc.]
**Description**:
```
[Describe the issue]
```
**Steps to Reproduce**:
1.
2.
3.

**Expected Behavior**:
```
[What should happen]
```

**Actual Behavior**:
```
[What actually happens]
```

**Fix Applied**: [Yes/No]
**Fix Description**:
```
[If fixed, describe the fix]
```

---

## Testing Summary

**Date Completed**: _________________
**Tester Name**: _________________
**Total Tests**: 10 test suites
**Tests Passed**: _____ / 10
**Tests Failed**: _____ / 10
**Critical Issues**: _____
**Minor Issues**: _____

**Overall Assessment**: [Ready for Production / Needs Fixes / Needs Major Work]

**Recommendation**: [Push to Git / Fix Issues First / Major Rework Needed]

**Tester Signature**: _________________

---

**Testing Checklist v1.0**
**For Plugin Version**: 0.6.0
**Created**: January 13, 2026
