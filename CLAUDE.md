# CLAUDE.md — School Management Plugin: Complete Project Reference

This file is automatically loaded by Claude Code at the start of every session.
It covers all three plugins: main plugin, calendar add-on, and student portal add-on.

---

## 1. PROJECT IDENTITY

- **Organisation**: Cyber Tech Academy (CTADZ) — https://ctadz.org
- **Developer**: Ahmed Sebaa
- **Purpose**: WordPress plugin suite to manage students, courses, attendance, schedules, and payments for private schools and training centres
- **Language**: French UI (all strings translated). English default. Arabic planned.
- **License**: GPL-2.0+
- **Local dev URL**: http://ctadz-school.local
- **Local plugin path**: `C:\Users\ahmed\Local Sites\ctadz-school\app\public\wp-content\plugins\`
- **WordPress requirements**: WP 5.8+, PHP 7.4+, MySQL 5.6+

---

## 2. THE THREE PLUGINS

### 2a. Main Plugin — School Management

| Item | Value |
|------|-------|
| Folder | `school-management/` |
| Main file | `school-management.php` |
| Version | **0.6.5** |
| Version constant | `SM_VERSION` |
| DB version | `SM_DB_VERSION = '1.4.0'` |
| Text domain | `CTADZ-school-management` |
| GitHub repo | `ctadz/school-management-plugin` |
| Branch strategy | `develop` → active dev; `main` → releases |
| GitHub token constant | `SM_GITHUB_TOKEN` (set in wp-config.php) |

### 2b. Calendar Add-on — School Management Calendar

| Item | Value |
|------|-------|
| Folder | `school-management-calendar/` |
| Main file | `school-management-calendar.php` |
| Version | **1.1.1** |
| Version constant | `SMC_VERSION` |
| Text domain | `school-management-calendar` |
| GitHub repo | `ctadz/school-management-calendar` |
| Branch strategy | `develop` → active dev; `main` → releases |
| GitHub token constant | `SMC_GITHUB_TOKEN` |
| Requires | Main plugin active (`SM_VERSION` defined) |
| Check active | `defined('SMC_VERSION')` |

### 2c. Student Portal Add-on — School Management Student Portal

| Item | Value |
|------|-------|
| Folder | `school-management-student-portal/` |
| Main file | `school-management-student-portal.php` |
| Version | **1.1.2** |
| Version constant | `SMSP_VERSION` |
| DB version | `SMSP_DB_VERSION = '1.0.0'` |
| Text domain | `CTADZ-school-management-portal` |
| GitHub repo | `ctadz/school-management-student-portal` |
| Branch strategy | `develop` → active dev; `master` → releases (NOTE: uses `master` not `main`) |
| GitHub token constant | `SMSP_GITHUB_TOKEN` |
| Requires | Main plugin (`SM_User_Management` class) |

---

## 3. FILE STRUCTURE

### Main Plugin (`school-management/`)
```
school-management.php              ← Main entry point, version, constants
includes/
  sm-loader.php                    ← Requires all class files
  sm-helpers.php                   ← Shared helper functions
  sm-enqueue.php                   ← CSS/JS asset loading
  class-sm-admin-menu.php          ← ALL menus (3-category architecture)
  class-sm-admin-redirect.php      ← Role-based redirects
  class-sm-roles.php               ← Custom roles & capabilities
  class-sm-students-page.php       ← Student CRUD
  class-sm-teachers-page.php       ← Teacher CRUD
  class-sm-courses-page.php        ← Course CRUD
  class-sm-levels-page.php         ← Level CRUD
  class-sm-classrooms-page.php     ← Classroom CRUD
  class-sm-attendance-page.php     ← Attendance tracking
  class-sm-enrollments-page.php    ← Enrollments & payment schedule creation
  class-sm-payments-page.php       ← Payment collection & recording
  class-sm-payment-alerts-page.php ← Overdue payment alerts
  class-sm-payment-terms-page.php  ← Payment terms configuration
  class-sm-payment-sync.php        ← Payment sync utilities
  class-sm-family-discount.php     ← Family discount calculator (logic)
  class-sm-family-discount-tools-page.php ← Family discount admin UI
  class-sm-settings-page.php       ← Plugin settings (super admin only)
  class-sm-user-management.php     ← WordPress user integration
  class-sm-github-updater.php      ← Auto-update from GitHub
assets/
  css/sm-admin.css                 ← Main admin styles (~1350 lines, includes dark mode & responsive)
  js/                              ← Admin JS files
languages/
  CTADZ-school-management-fr_FR.po ← French translation source (edit this)
  CTADZ-school-management-fr_FR.mo ← Compiled French translation (auto-generated)
Docs/                              ← Dev documentation (excluded from zip)
file/                              ← Additional dev docs (excluded from zip)
CLAUDE.md                          ← This file (excluded from zip)
CHANGELOG.md                       ← Version history
ROADMAP.md                         ← Feature backlog
README.md                          ← Plugin readme
```

### Calendar Plugin (`school-management-calendar/`)
```
school-management-calendar.php     ← Main entry, version, constants
includes/
  smc-loader.php                   ← Requires all class files
  smc-helpers.php                  ← Vacation calculation functions
  smc-enqueue.php                  ← CSS/JS loading
  class-smc-activator.php          ← DB table creation & migrations
  class-smc-admin-menu.php         ← Calendar menu items
  class-smc-calendar-page.php      ← Weekly/monthly calendar view
  class-smc-schedules-page.php     ← Course schedule CRUD
  class-smc-events-page.php        ← Events CRUD (holidays, closures, etc.)
  class-smc-github-updater.php     ← Auto-update from GitHub
  class-smc-license.php            ← License management
assets/
  css/smc-calendar.css             ← Calendar styles (~1100 lines, includes dark mode & responsive)
languages/                         ← Translation files
CHANGELOG.md                       ← Calendar version history
```

### Student Portal Plugin (`school-management-student-portal/`)
```
school-management-student-portal.php ← Main entry, version, constants
includes/
  smsp-loader.php                  ← Requires all class files
  smsp-enqueue.php                 ← CSS/JS loading
  class-smsp-auth.php              ← Custom student authentication
  class-smsp-portal-page.php       ← Portal front-end page
  class-smsp-schedule.php          ← Student schedule display
  class-smsp-attendance.php        ← Student attendance view
  class-smsp-github-updater.php    ← Auto-update from GitHub
templates/                         ← Frontend templates
languages/                         ← Translation files
```

---

## 4. MENU ARCHITECTURE (3-Category — since v0.6.0)

### Menu 1: School Management (Academic)
- Dashboard (Academic Overview)
- Students
- Teachers
- Courses
- Levels
- Classrooms
- Attendance
- Calendar *(if Calendar plugin active)*
- Schedules *(if Calendar plugin active)*
- Events *(if Calendar plugin active)*

### Menu 2: School Finances (Financial)
- Dashboard (Financial Overview with Chart.js widgets)
- Enrollments & Plans
- Payment Collection
- Payment Alerts
- Payment Terms
- Family Discounts

### Menu 3: School Settings
- General Settings *(super admin / WordPress Administrator only)*

---

## 5. USER ROLES

| Role | Slug | Access |
|------|------|--------|
| WordPress Administrator | `administrator` | Everything including WP core |
| School Admin | `school_admin` | Academic + Financial, no WP core, no Settings |
| School Accountant | `school_accountant` | Financial only + read-only Calendar (auto-redirects to Finance Dashboard) |
| School Teacher | `school_teacher` | Own classes only (attendance + calendar). Redirects to Calendar on login |
| School Student | `school_student` | Student Portal frontend only |

### Custom capabilities
Academic: `manage_school`, `manage_students`, `manage_teachers`, `manage_courses`, `manage_levels`, `manage_classrooms`, `view_attendance`, `manage_attendance`, `mark_attendance`
Financial: `manage_payments`, `manage_enrollments`, `view_reports`
Calendar: `view_calendar`, `manage_schedules`, `manage_events`, `view_own_schedule`
Settings: `manage_school_settings`

---

## 6. DATABASE SCHEMA

All tables use WordPress `$wpdb->prefix` (default: `wp_`).

### Main Plugin Tables

**`sm_students`**
- `id`, `name`, `student_code`, `email`, `phone`, `date_of_birth`, `level_id`
- `parent_name`, `parent_phone`, `parent_email`
- `blood_type`, `picture`, `portal_access`, `created_at`

**`sm_teachers`** ← NOTE: uses `first_name` + `last_name` (NOT a single `name` field!)
- `id`, `first_name`, `last_name`, `email`, `phone`, `specialization`, `hourly_rate`
- `payment_terms`, `picture`, `created_at`

**`sm_courses`**
- `id`, `name`, `description`, `description_file`
- `language`, `level_id`, `teacher_id`, `classroom_id`
- `session_duration`, `hours_per_week`, `total_weeks`, `total_months`
- `price_per_month`, `total_price`
- `payment_model`: `'one_time'` | `'monthly_installments'` | `'quarterly'` | `'monthly_subscription'`
- `status`: `'upcoming'` | `'in_progress'` | `'completed'`
- `certification`, `max_students`, `is_active`, `created_at`

**`sm_levels`**
- `id`, `name`, `description`, `order_index`, `created_at`

**`sm_classrooms`**
- `id`, `name`, `capacity`, `location`, `facilities`, `created_at`

**`sm_enrollments`**
- `id`, `student_id`, `course_id`
- `start_date`, `end_date`, `status` (`active`/`inactive`/`completed`)
- `payment_plan`: `'monthly'` | `'quarterly'` | `'full'`
- `total_amount`, `paid_amount`, `discount_percentage`
- `book_fees`, `insurance`, `uniform`, `other_fees`
- `notes`, `created_at`

**`sm_payments`**
- `id`, `enrollment_id`, `student_id`, `course_id`
- `amount`, `paid_amount`, `due_date`, `paid_date`
- `status`: `'pending'` | `'partial'` | `'paid'` | `'overdue'`
- `payment_method`, `notes`, `created_at`

**`sm_attendance`**
- `id`, `student_id`, `course_id`, `date`
- `status`: `'present'` | `'absent'` | `'late'`
- `notes`, `created_at`

**`sm_payment_terms`**
- `id`, `name`, `description`, `due_days`, `late_fee_percentage`, `created_at`

### Calendar Plugin Tables

**`smc_schedules`**
- `id`, `course_id`, `classroom_id`, `teacher_id`
- `day_of_week` (1=Monday … 7=Sunday), `start_time`, `end_time`
- `effective_from`, `effective_until`
- `is_active`, `created_at`

**`smc_events`**
- `id`, `event_type` (`'holiday'` | `'school_closure'` | `'exam'` | `'meeting'` | `'other'`)
- `event_date` (start date), `event_end_date` (end date — for multi-day vacations, added v1.1.0)
- `title`, `description`, `created_at`
- DB version: `1.2.0`

---

## 7. PAYMENT BUSINESS LOGIC

### Payment Models (set on course, drive all behaviour)

| `payment_model` | Type | Vacation-aware? | Schedule creation |
|-----------------|------|-----------------|-------------------|
| `monthly_subscription` | Session-based | ✅ YES | First payment only; next auto-generated when current marked paid |
| `monthly_installments` | Financial plan | ❌ NO | All payments created upfront at enrollment |
| `quarterly` | Financial plan | ❌ NO | All payments created upfront at enrollment |
| `one_time` | Single payment | ❌ NO | One payment at enrollment |

### Vacation-Aware Date Calculation (subscriptions only)
- **Functions** (in `school-management-calendar/includes/smc-helpers.php`):
  - `smc_get_vacation_periods($from_date, $to_date)` — retrieves holiday/school_closure events
  - `smc_calculate_subscription_payment_date($from_date, $interval)` — calculates next date preserving day-of-month, skipping vacations
  - `smc_add_months_preserve_day($date, $months)` — preserves original day (Jan 31 → Feb 28 → Mar 31)
  - `smc_add_vacation_days_between($base_date, $vacations)` — counts vacation days to add
- **Auto-generation** triggered in `class-sm-payments-page.php` when subscription payment marked paid
- **Falls back** to simple `+1 month` if Calendar plugin not active

### Family Discounts
- Grouping key: **parent phone number** (must be identical for siblings)
- Default tiers: 2 students = 5%, 3 students = 10%, 4+ students = 15%
- Auto-applied at enrollment; bulk recalculation available in Financial menu

---

## 8. COMPLETE VERSION HISTORY

### Main Plugin

| Version | Date | Summary |
|---------|------|---------|
| v0.1–0.4 | 2025 early | Initial development (see git history) |
| v0.5.0 | Dec 2025 | Auto-update system via GitHub |
| v0.5.3 | Dec 2025 | French translations for family discounts |
| v0.5.4 | Dec 27, 2025 | Fixed GitHub updater repo URL (ahmedsebaa → ctadz) |
| v0.5.5 | Dec 27, 2025 | 100% French translations (payment alerts, family discounts) |
| v0.5.6 | Dec 2025 | Security fixes (SQL injection, session fixation, CSV injection) |
| v0.6.0 | Jan 13, 2026 | **Major restructuring**: 3-category menu, school_accountant role, simplified student registration, Financial Dashboard |
| v0.6.1 | Jan 27, 2026 | GitHub updater token support fix |
| v0.6.2 | Jan 28–30, 2026 | Vacation-aware subscription payments, auto-generation of next payment |
| v0.6.3 | Feb 1–2, 2026 | Clickable course/enrollment counts with weekly schedule views |
| v0.6.4 | Feb 26, 2026 | Fixed subscription date day-of-month preservation, improved payment status display |
| v0.6.5 | Mar 3, 2026 | Maintenance tool: Fix subscription payment dates (Settings page, super admin only) |

### Calendar Plugin

| Version | Date | Summary |
|---------|------|---------|
| v1.0.0 | Dec 18, 2025 | Initial release: schedules, events, calendar view |
| v1.0.1–1.0.2 | Jan 27, 2026 | French translation updates |
| v1.1.0 | Jan 28, 2026 | Multi-day vacation events (`event_end_date`), payment integration, DB migration 1.2.0 |
| v1.1.1 | Feb 26, 2026 | Rewritten vacation date calculation: day preservation, recursive checking |

### Student Portal Plugin

| Version | Date | Summary |
|---------|------|---------|
| v1.0.0–1.1.0 | Dec 2025 | Initial: custom auth, schedule view, attendance, responsive design |
| v1.1.1 | Jan 27, 2026 | Translatable JS strings, French translations |
| v1.1.2 | Current | Minor improvements |

---

## 9. RELEASE WORKFLOW (exact commands)

```bash
# ── MAIN PLUGIN ──────────────────────────────────────────────────
# 1. Work on develop branch. Bump version in school-management.php:
#    - Plugin header:  Version: X.Y.Z
#    - Constant:       define('SM_VERSION', 'X.Y.Z');

# 2. Commit to develop
git add <files>
git commit -m "feat: Description

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>"
git push origin develop

# 3. Merge develop → main
git checkout main
git merge develop -m "Merge develop into main for vX.Y.Z release"
git push origin main

# 4. Build zip (Docs/, CLAUDE.md, file/ auto-excluded via .gitattributes)
git archive --format=zip --prefix=school-management/ -o school-management.zip HEAD

# 5. Create GitHub release (gh CLI - note Windows path if needed)
"/c/Program Files/GitHub CLI/gh.exe" release create vX.Y.Z school-management.zip \
  --title "vX.Y.Z" \
  --notes "Release notes..." \
  --target main

# 6. Return to develop
git checkout develop

# ── CALENDAR PLUGIN ───────────────────────────────────────────────
# Same pattern. Branch: develop → main. Version constant: SMC_VERSION
git archive --format=zip --prefix=school-management-calendar/ -o school-management-calendar.zip HEAD
"/c/Program Files/GitHub CLI/gh.exe" release create vX.Y.Z school-management-calendar.zip \
  --title "vX.Y.Z" --notes "..." --target main

# ── STUDENT PORTAL ────────────────────────────────────────────────
# NOTE: uses 'master' not 'main'!  Version constant: SMSP_VERSION
git archive --format=zip --prefix=school-management-student-portal/ -o school-management-student-portal.zip HEAD
"/c/Program Files/GitHub CLI/gh.exe" release create vX.Y.Z school-management-student-portal.zip \
  --title "vX.Y.Z" --notes "..." --target master
```

### What gets excluded from all zips (via `.gitattributes`)
- `Docs/` — development documentation
- `CLAUDE.md` — this file
- `file/` — additional dev docs (main plugin only)
- `.gitattributes` itself is included (harmless)

### Auto-update mechanism
- Each plugin checks `https://api.github.com/repos/{owner}/{repo}/releases/latest` every 12 hours
- Compares tag (strips `v` prefix) against installed version constant
- Downloads attached `.zip` asset (must be named `school-management.zip` etc.)
- GitHub tokens stored in `wp-config.php` as `SM_GITHUB_TOKEN`, `SMC_GITHUB_TOKEN`, `SMSP_GITHUB_TOKEN`
- Clear update cache: `DELETE FROM wp_options WHERE option_name LIKE '%sm_github_update%'`

---

## 10. FRENCH TRANSLATION WORKFLOW

```bash
# Edit the .po file
# File: school-management/languages/CTADZ-school-management-fr_FR.po

# Compile to .mo
cd "/c/Users/ahmed/Local Sites/ctadz-school/app/public/wp-content/plugins/school-management/languages"
msgfmt -o CTADZ-school-management-fr_FR.mo CTADZ-school-management-fr_FR.po

# Common error: "duplicate message definition" → remove duplicate entries in .po
```

- All user-facing strings use `__()`, `_e()`, `esc_html__()`, `esc_attr__()` with domain `CTADZ-school-management`
- Calendar uses domain `school-management-calendar`
- Student portal uses domain `CTADZ-school-management-portal`

---

## 11. CODE CONVENTIONS & PATTERNS

### Always check for Calendar plugin before using its features
```php
$calendar_active = defined( 'SMC_VERSION' );
```

### Teacher name — ALWAYS use first_name + last_name
```php
$teacher->first_name . ' ' . $teacher->last_name  // ✅ CORRECT
$teacher->name                                       // ✗ WRONG — field does not exist
```

### Day-of-week mapping (schedules)
```php
$days = [ 1=>'Monday', 2=>'Tuesday', 3=>'Wednesday', 4=>'Thursday', 5=>'Friday', 6=>'Saturday', 7=>'Sunday' ];
```

### Courses without a schedule (used in clickable count views)
```php
$scheduled_ids = array_unique( array_column( $schedules, 'course_id' ) );
$without = array_filter( $courses, fn($c) => ! in_array( $c->id, $scheduled_ids ) );
```

### Use `payment_model` for business logic (NOT `payment_plan`)
- `payment_model` is on `sm_courses` — drives business rules
- `payment_plan` is on `sm_enrollments` — stores the billing frequency chosen at enrollment

### Status badges (CSS)
```php
<span class="sm-status-badge sm-status-active">
    <span class="sm-status-dot"></span>
    <?php esc_html_e( 'Active', 'CTADZ-school-management' ); ?>
</span>
```

### Mobile card layout (tables)
```php
<td data-label="<?php echo esc_attr__( 'Column', 'CTADZ-school-management' ); ?>">
    <span class="mobile-label"><?php esc_html_e( 'Column', 'CTADZ-school-management' ); ?>:</span>
    content
</td>
```

### Security rules (always)
- All SQL → prepared statements (`$wpdb->prepare()`)
- All output → escaped (`esc_html()`, `esc_attr()`, `esc_url()`)
- All forms → nonce verification
- All AJAX → capability check

---

## 12. KEY FEATURES IMPLEMENTED

### Main Plugin
- **Students**: CRUD, profile photo, student code, level, parent info, portal access toggle
- **Teachers**: CRUD, photo, specialization, hourly rate, payment terms
- **Courses**: CRUD, payment model, language, level, teacher, classroom, pricing, certification
- **Levels**: CRUD with ordering
- **Classrooms**: CRUD with capacity, location, facilities
- **Attendance**: Per-course, per-date, per-student (present/absent/late)
- **Enrollments**: Creates payment schedule automatically on save; family discount applied
- **Payment Collection**: Record payments, partial payments, status tracking
- **Payment Alerts**: Overdue detection, upcoming due dates
- **Payment Terms**: Configurable due days and late fees
- **Family Discounts**: Auto-grouped by parent phone; bulk recalculate tool
- **Clickable Counts** (v0.6.3): Dashboard counts link to weekly schedule views for classrooms, teachers, students
- **Financial Dashboard**: Chart.js widgets — outstanding balance, total expected/collected, alerts, status breakdown
- **Academic Dashboard**: Student/teacher/course counts with clickable navigation
- **3-Category Menu** (v0.6.0): Academic / Financial / Settings separation
- **school_accountant Role** (v0.6.0): Finance-only access, auto-redirect to Finance Dashboard
- **Optional Enrollment at Registration** (v0.6.0): Checkbox → redirect to Finance
- **GitHub Auto-Updater**: Checks GitHub Releases API every 12h
- **Maintenance Tools** (v0.6.5, Settings page, super admin only): Fix Subscription Payment Dates — previews and corrects legacy payment due dates that fall inside vacation periods; uses `SM_Payment_Sync::recalculate_subscription_due_dates($dry_run)`
- **Deactivation Protection**: Warns if Calendar/Portal plugins depend on main plugin

### Calendar Plugin
- **Schedules**: Recurring weekly sessions per course (day, time, classroom, teacher, effective period)
- **Events**: Holidays, school closures, exams, meetings, special events
- **Multi-day Vacations** (v1.1.0): `event_end_date` for vacation date ranges
- **Calendar View**: Visual weekly/monthly view
- **Payment Integration**: Vacation periods auto-extend subscription payment dates
- **Responsive CSS** (`smc-calendar.css`): ~1100 lines, dark mode, mobile-first

### Student Portal
- **Custom Authentication**: Separate student login (not WordPress login)
- **Schedule View**: Student sees their enrolled course schedules
- **Attendance View**: Student sees own attendance records
- **Responsive Design**: Mobile-friendly portal

---

## 13. SECURITY FIXES (Dec 2025 — v0.5.6)

11 vulnerabilities fixed across all plugins:
- SQL injection in 10 files (all now use prepared statements)
- Session fixation in student portal
- CSV injection in credentials export
- Authorization bypass in attendance AJAX handlers
- Weak password generation

---

## 14. UI/UX COMPLETED

- **Responsive Design**: All pages responsive. Breakpoints: 1024px, 782px, 480px. Touch targets ≥44px.
- **Dark Mode**: Full dark mode support via `@media (prefers-color-scheme: dark)`. WCAG 2.1 Level AA.
- **Search Boxes**: 500px width, consistent across all 12 pages
- **Mobile Cards**: Tables collapse to card layout on mobile with `data-label` attributes
- **Print Styles**: Calendar views have print CSS

---

## 15. PENDING WORK (from ROADMAP.md)

### High Priority
- [ ] French translations for v0.6.0+ new strings (~35–40 strings: "School Finances", "Financial Dashboard", "School Accountant", + new Maintenance Tools strings)
- [ ] Deploy v0.6.5 to live site and run the "Fix Subscription Payment Dates" maintenance tool to correct legacy payment records

### Medium Priority
- [ ] Payment Hold System (designed, not coded): Triggered by `student_vacation`/`teacher_absence` calendar events; shifts subscription payment dates; file to create: `class-sm-payment-date-shifter.php`; estimated 4–6 hours
- [ ] Attendance enhancements: bulk marking, reports export, absence notifications
- [ ] PDF receipt generation
- [ ] Financial reports (monthly/quarterly/annual)
- [ ] Manual dark mode toggle (settings + admin bar button)
- [ ] Export functionality (CSV/PDF/Excel)

### Long-term
- [ ] Arabic translation
- [ ] Multi-school/campus support
- [ ] SMS gateway integration
- [ ] Advanced reporting engine
- [ ] PHPUnit tests

---

## 16. TROUBLESHOOTING REFERENCE

| Problem | Cause | Solution |
|---------|-------|----------|
| `$teacher->name` undefined | Teachers table has `first_name`/`last_name` | Use `$teacher->first_name . ' ' . $teacher->last_name` |
| Migration tool 404 | Early `exit` in PHP file | Restructure conditionals, avoid early exits |
| `msgfmt` duplicate error | Duplicate strings in .po | Remove duplicate entries |
| Auto-update not showing | Stale 12h cache | Delete transient: `DELETE FROM wp_options WHERE option_name LIKE '%sm_github_update%'` |
| Wrong folder after update | zip built without `--prefix` | Always use `git archive --prefix=plugin-folder/` |
| Auto-update used wrong repo | Repo URL was `ahmedsebaa` not `ctadz` | Corrected to `ctadz` in all three plugin updaters |
| Subscription payment on wrong day | Old date calculation | Fixed v0.6.4 — uses `smc_add_months_preserve_day()` |

---

## 17. DEVELOPMENT ENVIRONMENT

```
OS: Windows 11 Pro
Shell: Git Bash (use Unix syntax: forward slashes, /dev/null)
Local dev: LocalWP
Plugin root: C:\Users\ahmed\Local Sites\ctadz-school\app\public\wp-content\plugins\

Git repos:
  Main:    git@github.com:ctadz/school-management-plugin.git
  Calendar: https://github.com/ctadz/school-management-calendar.git
  Portal:  https://github.com/ctadz/school-management-student-portal.git

GitHub CLI: "C:\Program Files\GitHub CLI\gh.exe"
  (wrap in quotes in bash due to spaces in path)

Git branch status at last session:
  All plugins on `develop` branch
  Main plugin: v0.6.5 committed and pushed
```

---

## 18. DOCS FOLDER REFERENCE (dev only, excluded from zip)

Located in `school-management/Docs/`:
- `SESSION-SUMMARY.md` — v0.6.2 (vacation payments) + v0.6.3 (clickable counts) details
- `SESSION-SUMMARY-2026-01-13.md` — v0.6.0 major restructuring details
- `VACATION-AWARE-PAYMENTS.md` — Full payment logic documentation
- `AUTOMATIC-UPDATE-SYSTEM.md` — Auto-update architecture and troubleshooting
- `RELEASE-PROCESS.md` — Step-by-step release commands
- `ROLES.md` — Role comparison chart and capabilities
- `WORKFLOWS.md` — 16 step-by-step staff workflows
- `USER-GUIDE.md` — End-user documentation
- `DARK-MODE-IMPLEMENTATION.md` — Dark mode technical details
- `RESPONSIVE-DESIGN-SUMMARY.md` — Responsive design overview
- `TEACHER-PAYMENTS-FEATURE-SPEC.md` — Teacher payments feature spec (future)
- `CLASSES-SECTIONS-FEATURE-SPEC.md` — Classes/sections feature spec (future)
- `TESTING-CHECKLIST-v0.6.0.md` — v0.6.0 test checklist
- `DEPLOYMENT-GUIDE.md` — Production deployment guide
- `PRIVATE-REPO-SETUP.md` — GitHub private repo setup

Located in `school-management/file/`:
- `MASTER_GUIDE.md` — Comprehensive development guide
- `FILE_LIST.md` — File inventory
- `GIT_COMMIT_GUIDE.md` — Git commit conventions
- `GIT_WORKFLOW_SUMMARY.md` — Git workflow summary
- `QUICK_START_GUIDE.md` — Quick start for new dev sessions
