# Changelog

All notable changes to the School Management plugin will be documented in this file.

## [0.6.0] - 2026-01-13

### Major Restructuring - Simplified Architecture

**Based on user feedback: "Plugin is too complicated, everything is mixed together"**

This release represents a major architectural improvement focused on simplification and role-based separation.

#### Added

- **3-Category Menu Structure**
  - New separate top-level menu: "School Finances" (Financial Management)
  - New separate top-level menu: "School Settings" (Plugin Management)
  - Reorganized existing menu into "School Management" (Academic focus)

- **School Accountant Role**
  - New role: `school_accountant` with financial-only access
  - Full access to: Payments, Enrollments, Payment Terms, Family Discounts
  - Read-only access to: Calendar (for schedule reference)
  - No access to: Academic Management, Settings
  - Auto-redirects to Financial Dashboard on login
  - Hidden admin bar on frontend

- **Simplified Student Registration**
  - Added optional "Enroll in course now" checkbox during student creation
  - Smart redirect: If enrollment selected → redirects to Financial Management
  - Clean workflow: Academic staff register students, Finance staff handle payments

- **Financial Dashboard**
  - Dedicated dashboard for financial overview
  - Outstanding balance, total expected, total collected widgets
  - Payment alerts summary
  - Active enrollments count
  - Payment status breakdown chart
  - Quick actions for common financial tasks

#### Changed

- **Menu Organization**
  - Academic menu: Students, Teachers, Courses, Levels, Classrooms, Attendance, Calendar/Schedules/Events
  - Financial menu: Dashboard, Enrollments & Plans, Payment Collection, Payment Alerts, Payment Terms, Family Discounts
  - Settings menu: General Settings (super admin only)

- **Enrollments Page**
  - Moved from Academic menu to Financial menu
  - Now labeled "Enrollments & Plans" to clarify financial focus

- **Family Discount Tools**
  - Moved from Academic menu to Financial menu
  - Removed super admin restriction (now accessible to accountants)

- **Role Customization**
  - Enhanced menu customization for `school_teacher` role
  - Added menu customization for `school_accountant` role
  - Updated redirect logic for both roles

#### Technical Details

- Modified Files:
  - `school-management.php` - Version bump
  - `includes/class-sm-admin-menu.php` - Complete menu restructuring, added Financial Dashboard
  - `includes/class-sm-students-page.php` - Added optional enrollment workflow
  - `includes/class-sm-roles.php` - Added school_accountant role and menu customization

#### Benefits

- **Clear Separation**: Academic and financial functions completely separated
- **Role-Based Security**: Accountants can't access academic data, academic staff can't access finances
- **Simplified Workflows**: No forced enrollment during student registration
- **Industry Standard**: Architecture matches leading school management systems (OpenSIS, PowerSchool, Skyward)
- **Better User Experience**: Less confusion, clearer navigation, role-appropriate dashboards

#### Migration Notes

- **No database changes required** - All existing data preserved
- **Backward compatible** - Existing workflows still function
- **New role available** - Create accountant users via WordPress Users menu
- **Calendar plugin compatible** - Integration maintained

---

## [0.5.6] - 2025-01-XX

### Security & Stability

#### Fixed

- Security improvements across all forms
- Enhanced input validation and sanitization

---

## [0.5.5] - 2025-01-XX

### Features & Improvements

#### Added

- French translations (100% complete)
- Payment alerts system
- Family discount tools
- Auto-update functionality via GitHub
- Mobile responsive design improvements

#### Changed

- Enhanced payment scheduling
- Improved attendance tracking

---

## Earlier Versions

See git history for detailed changes in versions 0.1.0 through 0.5.4.

---

## Upcoming Features (Roadmap)

See [ROADMAP.md](ROADMAP.md) for planned features and improvements.
