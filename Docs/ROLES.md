# User Roles & Capabilities Guide

**Version**: 0.6.0
**Last Updated**: January 13, 2026

---

## Overview

The School Management plugin uses role-based access control to ensure users only see and access features relevant to their responsibilities. This guide explains each role, their capabilities, and how to manage them.

---

## Available Roles

The plugin provides 4 custom roles plus standard WordPress Administrator:

1. **Administrator** (WordPress built-in)
2. **School Admin** (Custom)
3. **School Accountant** (Custom) - NEW in v0.6.0
4. **School Teacher** (Custom)
5. **School Student** (Custom)

---

## Role Comparison Chart

### Quick Reference

| Feature | Administrator | School Admin | School Accountant | School Teacher | School Student |
|---------|--------------|--------------|-------------------|----------------|----------------|
| **Academic Management** |
| Dashboard | ✅ Full | ✅ Full | ❌ No | ❌ No | ❌ No |
| Manage Students | ✅ Yes | ✅ Yes | ❌ No | ❌ No | ❌ No |
| Manage Teachers | ✅ Yes | ✅ Yes | ❌ No | ❌ No | ❌ No |
| Manage Courses | ✅ Yes | ✅ Yes | ❌ No | ❌ No | ❌ No |
| Manage Levels | ✅ Yes | ✅ Yes | ❌ No | ❌ No | ❌ No |
| Manage Classrooms | ✅ Yes | ✅ Yes | ❌ No | ❌ No | ❌ No |
| View Attendance | ✅ Yes | ✅ Yes | ❌ No | ✅ Own Classes | ✅ Own Only |
| Mark Attendance | ✅ Yes | ✅ Yes | ❌ No | ✅ Own Classes | ❌ No |
| **Financial Management** |
| Financial Dashboard | ✅ Full | ✅ Full | ✅ Full | ❌ No | ❌ No |
| Manage Enrollments | ✅ Yes | ✅ Yes | ✅ Yes | ❌ No | ❌ No |
| Manage Payments | ✅ Yes | ✅ Yes | ✅ Yes | ❌ No | ❌ No |
| View Payment Alerts | ✅ Yes | ✅ Yes | ✅ Yes | ❌ No | ❌ No |
| Manage Payment Terms | ✅ Yes | ✅ Yes | ✅ Yes | ❌ No | ❌ No |
| Family Discounts | ✅ Yes | ✅ Yes | ✅ Yes | ❌ No | ❌ No |
| **Calendar** |
| View Calendar | ✅ Yes | ✅ Yes | ✅ Read-only | ✅ Yes | ✅ Yes |
| Manage Schedules | ✅ Yes | ✅ Yes | ❌ No | ❌ No | ❌ No |
| Manage Events | ✅ Yes | ✅ Yes | ❌ No | ❌ No | ❌ No |
| **Settings** |
| Plugin Settings | ✅ Yes | ❌ No | ❌ No | ❌ No | ❌ No |
| **WordPress** |
| WordPress Admin | ✅ Full | ❌ No | ❌ No | ❌ No | ❌ No |
| Manage Users | ✅ Yes | ❌ No | ❌ No | ❌ No | ❌ No |

### Legend
- ✅ **Full**: Complete access with all permissions
- ✅ **Yes**: Full access to this feature
- ✅ **Read-only**: Can view but not modify
- ✅ **Own Classes**: Can only access their assigned classes
- ✅ **Own Only**: Can only view their own data
- ❌ **No**: No access to this feature

---

## Detailed Role Descriptions

### 1. Administrator (WordPress Built-in)

**Who**: School owner, IT administrator, super user

**Access Level**: Complete system access

#### Capabilities

**All Plugin Features**: ✅
- Complete access to Academic Management
- Complete access to Financial Management
- Complete access to Plugin Settings

**WordPress Core**: ✅
- User management
- Plugin/theme installation
- Site settings
- All WordPress administrative functions

#### What They See

**Menus Visible**:
- School Management (Academic)
- School Finances (Financial)
- School Settings (Plugin Management)
- All WordPress menus (Posts, Media, Users, etc.)

#### Login Experience

- Sees WordPress dashboard
- Can navigate to any section
- Full administrative control

#### Use Cases

- Initial plugin setup and configuration
- Creating other user accounts
- Troubleshooting and maintenance
- System-wide changes
- Backup and data management

---

### 2. School Admin

**Who**: School director, principal, academic coordinator

**Access Level**: All school functions except plugin settings

#### Capabilities

**Academic Management**: ✅ Full Access
- Manage students, teachers, courses, levels, classrooms
- View and mark attendance for all classes
- Access calendar, schedules, events

**Financial Management**: ✅ Full Access
- Manage enrollments and payment plans
- Record and track payments
- View payment alerts
- Manage payment terms
- Handle family discounts

**Plugin Settings**: ❌ No Access
- Cannot modify plugin settings
- Cannot access WordPress admin functions

#### What They See

**Menus Visible**:
- School Management (Academic) - Full access
- School Finances (Financial) - Full access
- ❌ School Settings - Hidden
- ❌ WordPress menus - Hidden

#### Login Experience

- Sees School Management dashboard (Academic)
- Can switch between Academic and Financial sections
- Clean interface without WordPress clutter

#### Use Cases

- Day-to-day school operations management
- Overseeing both academic and financial operations
- Making enrollment and payment decisions
- Coordinating between academic and financial staff

---

### 3. School Accountant (NEW in v0.6.0)

**Who**: Accountant, bookkeeper, financial staff

**Access Level**: Financial management only

#### Capabilities

**Financial Management**: ✅ Full Access
- Manage enrollments and payment plans
- Record and track all payments
- View and respond to payment alerts
- Manage payment terms
- Handle family discount calculations

**Academic Management**: ❌ No Access
- Cannot manage students, teachers, or courses
- Cannot access attendance
- ✅ **Exception**: Read-only calendar access (for schedule reference)

**Plugin Settings**: ❌ No Access

#### What They See

**Menus Visible**:
- School Finances (Financial) - Full access
- School Management → Calendar - Read-only access
- ❌ Other Academic menus - Hidden
- ❌ School Settings - Hidden
- ❌ WordPress menus - Hidden

#### Login Experience

1. Logs in to WordPress
2. **Automatically redirected** to Financial Dashboard
3. Only sees Financial menu in sidebar
4. Can view Calendar for schedule reference
5. Admin bar hidden on frontend

#### Use Cases

- Processing daily payments
- Managing student enrollments with payment plans
- Monitoring overdue payments
- Generating financial reports
- Calculating and applying family discounts
- Reviewing payment schedules

#### Why This Role?

**Security**: Accountants shouldn't accidentally modify academic data (student info, courses, attendance)

**Simplicity**: Only see relevant financial functions, reducing confusion

**Compliance**: Clear separation of duties (financial vs academic)

---

### 4. School Teacher

**Who**: Teaching staff, instructors

**Access Level**: Limited to own classes

#### Capabilities

**Own Classes**: ✅ Limited Access
- View own schedule
- Mark attendance for own classes
- View student list for own classes

**Calendar**: ✅ View Access
- See school calendar
- View own schedule

**Everything Else**: ❌ No Access
- Cannot access other teachers' classes
- Cannot access student management
- Cannot access financial data
- Cannot access plugin settings

#### What They See

**Menus Visible**:
- School Management → Calendar
- School Management → Attendance (own classes only)
- WordPress → Profile (to edit own profile)
- ❌ All other menus - Hidden

#### Login Experience

1. Logs in to WordPress
2. **Automatically redirected** to Calendar
3. Minimal menu (only calendar and attendance)
4. Admin bar hidden on frontend

#### Use Cases

- Viewing class schedule
- Marking daily attendance
- Checking student roster
- Updating own profile information

---

### 5. School Student

**Who**: Enrolled students

**Access Level**: View own data only

#### Capabilities

**Own Data**: ✅ View Only
- View own schedule
- View own grades (future feature)
- View own payment status (via Student Portal plugin)
- View school calendar

**Everything Else**: ❌ No Access
- Cannot see other students' data
- Cannot access administrative functions

#### What They See

**Menus Visible**:
- Typically uses Student Portal (separate plugin)
- Limited WordPress admin access

#### Login Experience

- Usually accesses Student Portal frontend
- Can view own information and schedule
- No administrative interface access

#### Use Cases

- Checking class schedule
- Viewing grades and attendance
- Checking payment status
- Downloading documents/certificates

---

## Technical Details

### Capability Mapping

#### Custom Capabilities

The plugin uses these custom capabilities:

**Academic Capabilities**:
- `manage_school` - Access academic dashboard
- `manage_students` - Create/edit students
- `manage_teachers` - Create/edit teachers
- `manage_courses` - Create/edit courses
- `manage_levels` - Create/edit levels
- `manage_classrooms` - Create/edit classrooms
- `view_attendance` - View attendance data
- `manage_attendance` - Mark attendance
- `mark_attendance` - Mark attendance (for teachers)

**Financial Capabilities**:
- `manage_payments` - Access all financial functions
- `manage_enrollments` - Create/edit enrollments
- `view_reports` - View financial reports

**Calendar Capabilities**:
- `view_calendar` - View school calendar
- `manage_schedules` - Create/edit schedules
- `manage_events` - Create/edit events
- `view_own_schedule` - View own schedule only

**Settings Capabilities**:
- `manage_school_settings` - Access plugin settings
- `manage_options` - WordPress admin access (built-in)

---

## Managing Roles

### Creating a New User

1. Go to **WordPress → Users → Add New**
2. Fill in user details:
   - Username (required)
   - Email (required)
   - Password (required)
   - First/Last Name (optional)
3. Select **Role** from dropdown:
   - Administrator
   - School Admin
   - School Accountant (NEW)
   - School Teacher
   - School Student
4. Click **"Add New User"**

The user can now log in with their credentials and will see only the features relevant to their role.

---

### Changing a User's Role

1. Go to **WordPress → Users**
2. Find the user
3. Click **"Edit"**
4. Scroll to **Role** dropdown
5. Select new role
6. Click **"Update User"**

**Note**: Role changes take effect immediately. The user should log out and back in to see the changes.

---

### Recommended Role Assignments

| Staff Position | Recommended Role |
|---------------|------------------|
| School Owner/Director | Administrator |
| Principal/Head | School Admin |
| Academic Coordinator | School Admin |
| Receptionist | School Admin (or custom limited role) |
| Accountant/Bookkeeper | School Accountant |
| Teacher/Instructor | School Teacher |
| Student | School Student |

---

## Security Best Practices

### Principle of Least Privilege

Give users only the access they need:
- ✅ **Do**: Assign School Accountant role to financial staff
- ❌ **Don't**: Give Administrator role to everyone
- ✅ **Do**: Use School Admin for most staff
- ❌ **Don't**: Give School Admin to teachers (use School Teacher)

### Regular Audits

- Review user list quarterly
- Remove inactive users
- Update roles when responsibilities change
- Check for users with excessive permissions

### Password Security

- Enforce strong passwords
- Use two-factor authentication (recommended plugin)
- Change passwords regularly
- Don't share account credentials

---

## Role Workflow Examples

### Scenario 1: New Student Registration

**Step 1: Receptionist (School Admin)**
- Registers student in Academic Management
- Enters student and parent information
- Checks "Enroll in course now" if ready

**Step 2: Accountant (School Accountant)**
- Sets up enrollment and payment plan
- Records initial payment
- Configures payment schedule

**Result**: Clean separation - academic staff handles registration, financial staff handles money.

---

### Scenario 2: Daily Operations

**Morning - Teacher (School Teacher)**
- Logs in → Sees Calendar
- Checks today's classes
- Marks attendance after each class

**Afternoon - Accountant (School Accountant)**
- Logs in → Sees Financial Dashboard
- Processes today's payments
- Follows up on overdue alerts

**Evening - School Admin (School Admin)**
- Reviews both dashboards
- Checks enrollment trends
- Reviews payment status
- Plans next day's schedule

---

### Scenario 3: Month-End Closing

**School Accountant**:
1. Reviews all payments for the month
2. Identifies overdue accounts
3. Generates payment reports
4. Reconciles family discounts

**School Admin**:
1. Reviews academic performance
2. Checks attendance trends
3. Evaluates enrollment numbers
4. Reviews financial summary from accountant
5. Makes strategic decisions

**Administrator**:
1. Receives summary reports
2. Reviews system logs
3. Updates plugin if needed
4. Backs up database

---

## Troubleshooting

### Issue: User can't see expected menus

**Possible Causes**:
1. Wrong role assigned
2. Plugin not activated
3. User needs to log out and back in
4. Browser cache issue

**Solutions**:
1. Check user role in WordPress → Users
2. Verify plugin is active
3. Have user log out, clear browser cache, log back in
4. Hard refresh (Ctrl+Shift+R)

---

### Issue: Accountant can access academic functions

**This should not happen**. If it does:
1. Verify user role is exactly "School Accountant"
2. Check for conflicting plugins (role management plugins)
3. Verify plugin version is 0.6.0 or higher
4. Check for custom code modifications

---

### Issue: School Admin can't access settings

**This is correct behavior**. School Admins intentionally cannot access Plugin Settings. Only WordPress Administrators can.

**Reason**: Settings affect system-wide functionality and should only be modified by super administrators.

---

## Future Enhancements

Planned improvements to role system:

- [ ] Custom capability editor (UI-based)
- [ ] More granular permission levels
- [ ] Role templates for common scenarios
- [ ] Activity logging per role
- [ ] Temporary role elevation (e.g., grant admin for 1 hour)

---

## Need Help?

- **User Guide**: See `USER-GUIDE.md` for general plugin usage
- **Workflows**: See `WORKFLOWS.md` for common scenarios
- **Support**: Contact via GitHub Issues

---

**Version**: 0.6.0
**Last Updated**: January 13, 2026
