# School Management Plugin - User Guide

**Version**: 0.6.0
**Last Updated**: January 13, 2026

---

## Table of Contents

1. [Introduction](#introduction)
2. [What's New in v0.6.0](#whats-new-in-v060)
3. [Menu Structure Overview](#menu-structure-overview)
4. [Academic Management](#academic-management)
5. [Financial Management](#financial-management)
6. [Plugin Settings](#plugin-settings)
7. [Common Workflows](#common-workflows)
8. [Frequently Asked Questions](#frequently-asked-questions)

---

## Introduction

The School Management plugin helps you manage all aspects of your school operations, from student registration to financial tracking. Version 0.6.0 introduces a simplified, organized structure based on user feedback and industry best practices.

### Key Features

- **Student Management** - Register and track students
- **Teacher Management** - Manage teaching staff
- **Course Management** - Create and organize courses
- **Enrollment Management** - Link students to courses with payment plans
- **Payment Tracking** - Monitor payments, alerts, and family discounts
- **Attendance Tracking** - Mark and report student attendance
- **Calendar & Scheduling** - Plan classes and events

---

## What's New in v0.6.0

### 🎉 Major Improvements

#### 1. Reorganized Menu Structure
Instead of one long menu with 16+ items, you now have **3 separate, focused menus**:

- **School Management** (Academic) - For academic staff
- **School Finances** (Financial) - For accounting staff
- **School Settings** (Admin) - For system administrators

#### 2. Simplified Student Registration
- No more payment fields mixed with student information
- Optional "Enroll in course now" checkbox
- Clean workflow: Register student → Optionally enroll → Finance handles payments

#### 3. New School Accountant Role
- Dedicated role for financial staff
- Access only to financial functions
- Can't accidentally modify academic data

### Why These Changes?

**User Feedback**: "The plugin is too complicated, everything is mixed together"

**Our Solution**: Follow industry standards (OpenSIS, PowerSchool, Skyward) with clear separation between academic and financial management.

---

## Menu Structure Overview

### 1. School Management 🎓 (Academic)

**Who Uses This**: School administrators, academic coordinators, receptionists

**What's Inside**:
- **Dashboard** - Academic overview with statistics and charts
- **Students** - Register and manage student information
- **Teachers** - Manage teaching staff
- **Courses** - Create and organize courses
- **Levels** - Define skill/grade levels
- **Classrooms** - Manage physical classroom spaces
- **Attendance** - Track student attendance
- **Calendar*** - View school calendar (if calendar plugin active)
- **Schedules*** - Manage class schedules (if calendar plugin active)
- **Events*** - Create school events (if calendar plugin active)

**Purpose**: Everything related to the academic side of school operations.

---

### 2. School Finances 💰 (Financial)

**Who Uses This**: Accountants, financial staff, authorized administrators

**What's Inside**:
- **Dashboard** - Financial overview with payment statistics
- **Enrollments & Plans** - Link students to courses with payment terms
- **Payment Collection** - Record and track payments
- **Payment Alerts** - View overdue and upcoming payments
- **Payment Terms** - Manage payment schedules (monthly, quarterly, etc.)
- **Family Discounts** - Manage multi-student family discounts

**Purpose**: Everything related to financial operations and payment management.

**Note**: This menu is only visible to users with payment management capabilities (Administrators, School Admins, and Accountants).

---

### 3. School Settings ⚙️ (Plugin Management)

**Who Uses This**: Super administrators only

**What's Inside**:
- **General Settings** - School name, logo, system configuration

**Purpose**: System-level configuration that affects the entire plugin.

**Note**: This menu is only visible to WordPress administrators with `manage_options` capability.

---

## Academic Management

### Dashboard

**Location**: School Management → Dashboard

**What You See**:
- Total students, teachers, courses, levels, classrooms
- Active enrollments count
- Outstanding balance summary
- Payment alerts count
- Enrollment trends chart (last 6 months)
- Payment status breakdown
- Students by level distribution
- Quick action buttons

**Quick Actions**:
- Add New Student
- Add New Course
- New Enrollment
- Add New Teacher
- Add Classroom
- View Payments
- View Calendar

---

### Managing Students

**Location**: School Management → Students

#### Adding a New Student

1. Click **"Add New Student"** button
2. Fill in **Student Information**:
   - Full Name (required)
   - Email Address (required, must be unique)
   - Phone Number (required)
   - Date of Birth (required)
   - Level (required)
   - Picture (optional, click to upload)
   - Blood Type (optional)

3. Fill in **Parent/Guardian Information**:
   - Parent/Guardian Name (optional but recommended)
   - Parent/Guardian Phone (optional, used for family discounts)
   - Parent/Guardian Email (optional)

4. **Course Enrollment** (NEW in v0.6.0):
   - Check **"Enroll in a course now"** if you want to enroll immediately
   - Select a course from dropdown
   - Or leave unchecked to enroll later

5. Click **"Add Student"**

**What Happens Next**:
- If you checked enrollment → Redirects to Financial Management to set up payment
- If you didn't check enrollment → Returns to student list

**Family Discounts**:
If multiple students share the same Parent/Guardian Phone, they're automatically grouped as a family and eligible for discounts.

#### Editing a Student

1. Go to Students page
2. Click **"Edit"** on the student row
3. Modify information as needed
4. Click **"Update Student"**

**Note**: The enrollment checkbox only appears when adding new students, not when editing.

#### Viewing Student Details

Each student row shows:
- Name
- Email
- Phone
- Level
- Active enrollments count
- Actions (Edit, Delete)

**Tip**: Use the search box to quickly find students by name, email, or phone.

---

### Managing Teachers

**Location**: School Management → Teachers

Teachers are the instructors who teach courses. Each teacher has:
- Personal information (name, email, phone)
- Payment term (for salary scheduling)
- Hourly rate
- Profile picture
- Active/Inactive status

---

### Managing Courses

**Location**: School Management → Courses

Courses are the classes you offer. Each course has:
- Name and description
- Language
- Level (skill/grade level)
- Teacher assignment
- Duration (total weeks/months)
- Pricing (monthly price and total price)
- Payment model:
  - **Full Payment** - Pay entire amount upfront
  - **Monthly Installments** - Pay in monthly chunks (fixed plan)
  - **Monthly Subscription** - Ongoing subscription (affected by vacations)

**Payment Models Explained**:
- **Monthly Installments**: Fixed payment plan (e.g., 10-month course paid in 10 installments)
- **Monthly Subscription**: Ongoing support/tutoring with no fixed end date (like gym membership)

---

### Managing Attendance

**Location**: School Management → Attendance

Track student attendance for each session:
1. Select a course
2. Select a date
3. Mark each student: Present, Absent, or Late
4. Add notes if needed
5. Save attendance

---

## Financial Management

### Financial Dashboard

**Location**: School Finances → Dashboard

**What You See**:
- **Outstanding Balance** - Total amount owed
- **Total Expected** - Total revenue expected
- **Total Collected** - Amount already collected
- **Payment Alerts** - Overdue, due this week, due next week
- **Active Enrollments** - Number of current enrollments
- **Payment Terms** - Available payment schedules
- **Payment Status Chart** - Visual breakdown (Paid, Partial, Pending)

**Quick Actions**:
- New Enrollment
- Collect Payment
- View Payment Alerts
- Manage Payment Terms
- Family Discounts

---

### Managing Enrollments

**Location**: School Finances → Enrollments & Plans

Enrollments link students to courses with payment plans.

#### Creating a New Enrollment

**Method 1: During Student Registration** (NEW)
1. When adding a student, check "Enroll in course now"
2. Select course
3. You'll be redirected here after creating the student

**Method 2: Direct Enrollment**
1. Go to School Finances → Enrollments & Plans
2. Click **"Add New Enrollment"**
3. Select **Student**
4. Select **Course**
5. Set **Start Date**
6. Choose **Payment Term** (how they'll pay):
   - Monthly
   - Quarterly
   - Annually
   - Full upfront
7. Enter **Payment Information**:
   - Total amount
   - Paid amount (initial payment)
   - Additional fees (books, insurance, etc.)
8. Apply **Family Discount** (if applicable)
9. Click **"Create Enrollment"**

**What Happens**:
- Enrollment is created with status "Active"
- Payment schedule is automatically generated
- If family discount applies, it's calculated and applied
- Student can now attend classes

#### Payment Schedule

After creating an enrollment, a payment schedule is automatically generated with:
- Due dates based on payment term
- Expected amounts
- Paid amounts
- Outstanding balance
- Status (Pending, Partial, Paid)

---

### Collecting Payments

**Location**: School Finances → Payment Collection

#### Recording a Payment

1. Find the payment schedule entry
2. Click **"Record Payment"**
3. Enter **Amount Paid**
4. Select **Payment Date**
5. Add **Payment Method** (cash, check, transfer)
6. Add **Notes** (optional)
7. Click **"Save Payment"**

**Payment Status Updates**:
- **Pending** → No payment made yet
- **Partial** → Some payment made, balance remaining
- **Paid** → Fully paid

#### Bulk Actions

- Filter by status (Overdue, Due This Week, Due Next Week, All)
- Search by student name
- Export payment reports

---

### Payment Alerts

**Location**: School Finances → Payment Alerts

See at-a-glance which payments need attention:

**Alert Categories**:
- 🔴 **Overdue** - Payments past due date
- 🟠 **Due This Week** - Due in 1-7 days
- 🟡 **Due Next Week** - Due in 8-14 days

**Actions**:
- View student details
- Record payment directly
- Send reminder (future feature)

---

### Payment Terms

**Location**: School Finances → Payment Terms

Payment terms define how often payments are due:
- Monthly (every month)
- Quarterly (every 3 months)
- Annually (once per year)
- Custom terms

**Default Terms**:
The plugin comes with pre-configured terms, but you can add custom ones.

---

### Family Discounts

**Location**: School Finances → Family Discounts

Automatically apply discounts to families with multiple enrolled children.

**How It Works**:
1. Students are grouped by Parent/Guardian Phone number
2. Families with 2+ active enrollments get discounts
3. Discount percentages are automatically applied to payment schedules

**Default Discount Structure**:
- 2 students: 5% discount
- 3 students: 10% discount
- 4+ students: 15% discount

**Bulk Recalculation**:
If you change discount settings, use the **"Recalculate All"** tool to update existing payment schedules.

---

## Plugin Settings

**Location**: School Settings → General Settings

**Available Settings**:
- **School Name** - Displayed in dashboards and reports
- **School Logo** - Uploaded image shown in headers
- **Auto-Update Settings** - GitHub integration for plugin updates

**Access**: Super administrators only (`manage_options` capability)

---

## Common Workflows

### Workflow 1: Registering a New Student (Without Immediate Enrollment)

**Scenario**: Receptionist registers a student who will enroll later.

1. Go to **School Management → Students**
2. Click **"Add New Student"**
3. Fill in student information
4. Fill in parent information
5. Leave **"Enroll in course now"** unchecked
6. Click **"Add Student"**
7. Done! Student is registered and can be enrolled later.

**Who Does What**:
- Receptionist: Registers student
- Accountant: Handles enrollment and payment later

---

### Workflow 2: Registering and Enrolling a Student (Immediate)

**Scenario**: Student ready to start classes right away.

1. Go to **School Management → Students**
2. Click **"Add New Student"**
3. Fill in student information
4. Fill in parent information
5. Check **"Enroll in course now"**
6. Select the course from dropdown
7. Click **"Add Student"**
8. You're redirected to **School Finances → Enrollments & Plans**
9. Student and course are pre-selected
10. Fill in remaining enrollment details (start date, payment term, amounts)
11. Click **"Create Enrollment"**
12. Done! Student is registered, enrolled, and payment schedule created.

**Who Does What**:
- Receptionist: Registers student and initiates enrollment
- Accountant: Completes payment setup (or same person does both)

---

### Workflow 3: Creating an Enrollment for Existing Student

**Scenario**: Student already registered, now enrolling in a new course.

1. Go to **School Finances → Enrollments & Plans**
2. Click **"Add New Enrollment"**
3. Select **Student** from dropdown
4. Select **Course**
5. Set **Start Date**
6. Choose **Payment Term**
7. Enter **Payment Information**
8. Review and apply **Family Discount** if shown
9. Click **"Create Enrollment"**
10. Done! Enrollment active, payment schedule created.

**Who Does This**: Accountant or authorized staff with financial access

---

### Workflow 4: Recording a Payment

**Scenario**: Student makes a payment.

1. Go to **School Finances → Payment Collection**
2. Find the student's payment schedule (use search if needed)
3. Find the payment entry that's due
4. Click **"Record Payment"**
5. Enter **Amount Paid**
6. Select **Payment Date**
7. Add **Payment Method**
8. Add **Notes** (receipt number, etc.)
9. Click **"Save Payment"**
10. Status updates automatically (Partial or Paid)

**Who Does This**: Accountant or financial staff

---

### Workflow 5: Marking Attendance

**Scenario**: Teacher marks attendance after class.

1. Go to **School Management → Attendance**
2. Select **Course**
3. Select **Date**
4. Mark each student:
   - ✅ Present
   - ❌ Absent
   - ⏰ Late
5. Add **Notes** if needed
6. Click **"Save Attendance"**

**Who Does This**: Teacher or academic staff

---

## Frequently Asked Questions

### General Questions

**Q: What's the difference between v0.5.6 and v0.6.0?**
A: v0.6.0 reorganizes the plugin into 3 clear categories (Academic, Financial, Settings) and simplifies workflows. All your existing data is preserved.

**Q: Do I need to do anything to upgrade?**
A: No. If auto-updates are enabled, the plugin updates automatically. If not, update from the WordPress Plugins page.

**Q: Will my existing data be affected?**
A: No. All students, enrollments, payments, and settings are preserved. Only the menu organization changes.

---

### Student Registration

**Q: Do I have to enroll a student when I register them?**
A: No! The "Enroll in course now" checkbox is optional. You can register students and enroll them later.

**Q: What happens if I check "Enroll in course now"?**
A: After creating the student, you'll be redirected to the Financial Management section to complete the enrollment and payment setup.

**Q: Can I edit a student's enrollment checkbox later?**
A: The checkbox only appears when adding new students. To enroll existing students, use School Finances → Enrollments & Plans.

---

### Enrollments & Payments

**Q: What's the difference between "Monthly Installments" and "Monthly Subscription"?**
A:
- **Monthly Installments**: Fixed payment plan (e.g., 10-month course paid in 10 installments). Not affected by vacations.
- **Monthly Subscription**: Ongoing subscription (e.g., tutoring support). Can be affected by vacations (future feature).

**Q: How do family discounts work?**
A: Students with the same Parent/Guardian Phone number are automatically grouped as a family. Families with 2+ active enrollments receive automatic discounts.

**Q: Can I manually adjust a payment schedule?**
A: Yes. Edit individual payment entries to change amounts or due dates as needed.

**Q: What happens if a student pays more than expected?**
A: The extra amount is applied to future payments, reducing their outstanding balance.

---

### Roles & Access

**Q: Who can see the Financial Management menu?**
A: Only users with `manage_payments` capability: Administrators, School Admins, and School Accountants.

**Q: What can a School Accountant do?**
A: Accountants have full access to Financial Management (enrollments, payments, alerts, terms, family discounts) and read-only access to Calendar. They cannot access Academic Management or Settings.

**Q: How do I create an accountant user?**
A: Go to WordPress Users → Add New, create the user, and select "School Accountant" as their role.

**Q: Can I customize role capabilities?**
A: Yes, but it requires code changes or a role management plugin. Default roles are designed for typical school operations.

---

### Calendar & Scheduling

**Q: Where is the Calendar menu?**
A: If the Calendar add-on plugin is active, Calendar/Schedules/Events appear in the School Management (Academic) menu.

**Q: Do accountants see the Calendar?**
A: Yes, accountants have read-only access to the Calendar for schedule reference.

---

### Troubleshooting

**Q: I don't see the 3 separate menus.**
A: Clear your browser cache and hard refresh (Ctrl+Shift+R on Windows, Cmd+Shift+R on Mac). If still not visible, check that the plugin version is 0.6.0.

**Q: Some menu items are missing.**
A: Check your user role. Different roles see different menus:
- Teachers: Only see Academic menu
- Accountants: Only see Financial menu
- Administrators: See all 3 menus

**Q: The Financial Dashboard doesn't show charts.**
A: Ensure Chart.js is loaded. Check browser console for JavaScript errors. Contact support if issue persists.

**Q: Family discounts aren't calculating.**
A: Verify that siblings have the exact same Parent/Guardian Phone number. Even small differences (spaces, dashes) prevent grouping.

---

## Need Help?

- **Documentation**: Check this guide and other docs in the `/docs` folder
- **Changelog**: See `CHANGELOG.md` for version history
- **Roadmap**: See `ROADMAP.md` for upcoming features
- **Issues**: Report bugs at GitHub Issues

---

**Version**: 0.6.0
**Last Updated**: January 13, 2026
