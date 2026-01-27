# Common Workflows Guide

**Version**: 0.6.0
**Last Updated**: January 13, 2026

---

## Table of Contents

1. [Student Registration Workflows](#student-registration-workflows)
2. [Enrollment Workflows](#enrollment-workflows)
3. [Payment Workflows](#payment-workflows)
4. [Attendance Workflows](#attendance-workflows)
5. [Administrative Workflows](#administrative-workflows)
6. [End-of-Month/Period Workflows](#end-of-monthperiod-workflows)

---

## Student Registration Workflows

### Workflow 1: Quick Student Registration (No Immediate Enrollment)

**Scenario**: Parent calls to register child, but hasn't decided on course yet.

**Who**: Receptionist or School Admin

**Steps**:

1. Navigate to **School Management → Students**
2. Click **"Add New Student"** button
3. Fill in **Student Information**:
   ```
   ✓ Full Name: e.g., "Ahmed Benali"
   ✓ Email: e.g., "ahmed.benali@email.com"
   ✓ Phone: e.g., "0555123456"
   ✓ Date of Birth: Select from calendar
   ✓ Level: Select appropriate level
   ☐ Picture: Click to upload (optional)
   ☐ Blood Type: Select if known (optional)
   ```
4. Fill in **Parent/Guardian Information**:
   ```
   ✓ Parent Name: e.g., "Mohamed Benali"
   ✓ Parent Phone: e.g., "0666789012"
   ☐ Parent Email: e.g., "m.benali@email.com" (optional)
   ```
   **Note**: Parent phone is important for family discounts

5. **DO NOT** check "Enroll in course now"
6. Click **"Add Student"**
7. You're returned to student list with success message

**Result**: Student is registered and can be enrolled later

**Time**: ~2 minutes

---

### Workflow 2: Student Registration with Immediate Enrollment

**Scenario**: Parent wants to register and immediately enroll child in a course.

**Who**: Receptionist (registration) + Accountant (payment)

**Steps**:

#### Part A: Registration (Receptionist)

1. Navigate to **School Management → Students**
2. Click **"Add New Student"**
3. Fill in **Student Information** (as in Workflow 1)
4. Fill in **Parent/Guardian Information** (as in Workflow 1)
5. **CHECK** ✓ "Enroll in course now"
6. A course dropdown appears
7. Select the desired course from dropdown
8. Click **"Add Student"**
9. Success message: "Student added successfully"
10. Another message: "Redirecting to Financial Management..."
11. **You're automatically redirected** to enrollment page

#### Part B: Enrollment & Payment Setup (Accountant or same person)

12. You arrive at **School Finances → Enrollments & Plans → Add**
13. Student and Course are **pre-selected**
14. Fill in remaining details:
    ```
    ✓ Start Date: Select date
    ✓ Payment Term: Select (Monthly, Quarterly, Annually, Full)
    ✓ Total Amount: Auto-filled from course price
    ☐ Paid Amount: Enter initial payment (if any)
    ☐ Book Fees: Enter if applicable
    ☐ Insurance: Enter if applicable
    ☐ Uniform: Enter if applicable
    ☐ Other Fees: Enter if applicable
    ```
15. Review **Family Discount** (if siblings exist)
16. Click **"Create Enrollment"**
17. Success! Payment schedule is automatically generated

**Result**: Student is registered, enrolled, and payment schedule created

**Time**: ~5 minutes

---

### Workflow 3: Registering Siblings

**Scenario**: Parent enrolling multiple children from same family.

**Who**: Receptionist or School Admin

**Steps**:

1. Register **first child** (Workflow 1 or 2)
2. **IMPORTANT**: Note the Parent Phone number used
3. Register **second child**:
   - Use **EXACT same Parent Phone number**
   - Even small differences prevent family grouping
   ```
   ✓ Correct: "0666789012" for both
   ✗ Wrong: "0666789012" and "0666 789 012" (spaces)
   ```
4. Register **third child** (if applicable) with same Parent Phone
5. When you enroll siblings, system **automatically applies family discount**

**Family Discount Structure** (default):
- 2 students: 5% discount
- 3 students: 10% discount
- 4+ students: 15% discount

**Result**: Siblings grouped as family, discounts automatically applied

**Time**: ~5 minutes per student

---

## Enrollment Workflows

### Workflow 4: Enrolling an Existing Student

**Scenario**: Student already registered, now wants to enroll in a course.

**Who**: Accountant or authorized staff

**Steps**:

1. Navigate to **School Finances → Enrollments & Plans**
2. Click **"Add New Enrollment"**
3. **Select Student** from dropdown:
   - Type name to search
   - Or scroll to find student
4. **Select Course** from dropdown
5. Set **Start Date**:
   - Usually current date or next session start
   - Payment schedule starts from this date
6. Choose **Payment Term**:
   - **Monthly**: Pay every month
   - **Quarterly**: Pay every 3 months
   - **Annually**: Pay once per year
   - **Full**: Pay entire amount upfront
7. Enter **Payment Information**:
   ```
   ✓ Total Amount: Auto-calculated from course price
   ☐ Paid Amount: Enter if initial payment made
   ☐ Book Fees: Additional charges
   ☐ Insurance: Additional charges
   ☐ Uniform: Additional charges
   ☐ Other Fees: Additional charges
   ```
8. Review **Family Discount** section:
   - If siblings exist, discount % shown
   - System automatically calculates new amounts
9. Add **Notes** (optional):
   - Special arrangements
   - Payment agreements
10. Click **"Create Enrollment"**

**What Happens Next**:
- Enrollment created with status "Active"
- Payment schedule automatically generated
- Due dates calculated based on payment term
- Family discount applied if applicable
- Student can now attend classes

**Result**: Student enrolled with payment schedule

**Time**: ~3 minutes

---

### Workflow 5: Modifying an Existing Enrollment

**Scenario**: Need to change enrollment details (e.g., pause enrollment, change payment plan).

**Who**: Accountant or School Admin

**Steps**:

1. Navigate to **School Finances → Enrollments & Plans**
2. Find the enrollment (use search or filter)
3. Click **"Edit"**
4. Modify fields as needed:
   - Change status (Active, Paused, Completed)
   - Adjust dates
   - Update amounts
   - Change payment term
5. Click **"Update Enrollment"**
6. **Warning**: Changing payment term will regenerate schedule
7. Confirm changes

**Result**: Enrollment updated

**Time**: ~2 minutes

---

## Payment Workflows

### Workflow 6: Recording a Single Payment

**Scenario**: Student makes a payment.

**Who**: Accountant or financial staff

**Steps**:

1. Navigate to **School Finances → Payment Collection**
2. **Find the payment schedule**:
   - Use search box (student name)
   - Or browse by status filter
   - Or sort by due date
3. Locate the payment entry that's due
4. Click **"Record Payment"** or **"Edit"**
5. Enter **Payment Details**:
   ```
   ✓ Amount Paid: Enter amount received
   ✓ Payment Date: Select date (usually today)
   ✓ Payment Method: Select (Cash, Check, Transfer, Card)
   ☐ Notes: Add receipt number, transaction ID, etc.
   ```
6. Click **"Save Payment"**

**Status Updates Automatically**:
- If full amount paid → Status: **Paid** ✓
- If partial amount paid → Status: **Partial** ⚠
- If no payment → Status: **Pending** ⏳

**Result**: Payment recorded, status updated, balance calculated

**Time**: ~1 minute per payment

---

### Workflow 7: Recording Multiple Payments (Monthly Batch)

**Scenario**: Processing all payments received today.

**Who**: Accountant

**Steps**:

1. Navigate to **School Finances → Payment Collection**
2. Have all payment receipts/records ready
3. For each payment:
   a. Search student name
   b. Find due payment entry
   c. Click "Record Payment"
   d. Enter amount, date, method
   e. Save
   f. Move to next
4. Use **filter by status** to focus on pending payments
5. Check **"Due This Week"** to prioritize
6. Record all payments systematically

**Tip**: Keep payment receipts organized by date for faster processing

**Result**: All payments recorded for the day

**Time**: ~1 minute per payment (varies by volume)

---

### Workflow 8: Handling Partial Payments

**Scenario**: Student can only pay part of amount due.

**Who**: Accountant

**Steps**:

1. Find the payment schedule entry
2. Click **"Record Payment"**
3. Enter **Amount Paid** (less than expected amount)
   ```
   Expected: 5000 DZD
   Student pays: 3000 DZD
   Remaining: 2000 DZD
   ```
4. Select **Payment Date** and **Method**
5. Add **Note**: "Partial payment, remaining 2000 DZD to be paid by [date]"
6. Click **"Save Payment"**

**What Happens**:
- Status changes to **Partial** ⚠
- Remaining balance calculated and displayed
- Payment appears in alerts until fully paid
- Can record additional payments later

**Result**: Partial payment recorded, balance tracked

**Time**: ~2 minutes

---

### Workflow 9: Handling Overpayments

**Scenario**: Student pays more than expected amount.

**Who**: Accountant

**Steps**:

1. Find the payment schedule entry
2. Click **"Record Payment"**
3. Enter **Amount Paid** (more than expected)
   ```
   Expected: 5000 DZD
   Student pays: 7000 DZD
   Overpayment: 2000 DZD
   ```
4. Add **Note**: "Overpayment 2000 DZD applied to next installment"
5. Click **"Save Payment"**

**What Happens**:
- Current payment marked as **Paid** ✓
- Excess amount applied to next payment
- Next payment's expected amount reduced by excess

**Result**: Overpayment credited to future installments

**Time**: ~2 minutes

---

### Workflow 10: Following Up on Overdue Payments

**Scenario**: Monthly follow-up on late payments.

**Who**: Accountant or School Admin

**Steps**:

1. Navigate to **School Finances → Payment Alerts**
2. Review **Overdue** section (red alerts 🔴)
3. For each overdue payment:
   a. Note student name and amount
   b. Check how many days overdue
   c. Click student name to view full details
   d. Review payment history
4. Create follow-up list:
   ```
   Student Name | Amount | Days Overdue | Parent Phone
   Ahmed Benali | 5000   | 15 days      | 0666789012
   Sara Mansouri| 3500   | 7 days       | 0555234567
   ```
5. Contact parents:
   - By phone (preferred)
   - By email
   - By SMS (future feature)
6. Record any promises to pay in notes
7. Monitor daily until paid

**Tip**: Follow up weekly on overdue accounts

**Result**: Improved payment collection

**Time**: ~5 minutes per student

---

## Attendance Workflows

### Workflow 11: Daily Attendance Marking

**Scenario**: Teacher marks attendance after class.

**Who**: Teacher or academic staff

**Steps**:

1. Navigate to **School Management → Attendance**
2. Select **Course** from dropdown
3. Select **Date** (usually today)
4. Student list appears with radio buttons:
   ```
   ○ Present   ○ Absent   ○ Late
   ```
5. For each student, select appropriate status:
   - **Present** ✓ - Student attended
   - **Absent** ✗ - Student did not attend
   - **Late** ⏰ - Student arrived late
6. Add **Notes** for specific students (optional):
   - "Left early for medical appointment"
   - "Arrived 15 minutes late"
7. Click **"Save Attendance"**
8. Success message appears

**Result**: Attendance recorded for the session

**Time**: ~2 minutes for 10 students

---

### Workflow 12: Reviewing Attendance History

**Scenario**: Check a student's attendance pattern.

**Who**: Teacher, School Admin

**Steps**:

1. Navigate to **School Management → Attendance**
2. Select **Course**
3. Use **date range** filter:
   - From: Month start
   - To: Today
4. Click **"Show Attendance"**
5. View attendance table with:
   - Dates in rows
   - Students in columns
   - Status markers (✓ ✗ ⏰)
6. Identify patterns:
   - Frequent absences
   - Consistent lateness
   - Perfect attendance

**Export** (if needed):
- Click "Export" button
- Save as Excel or PDF
- Use for parent meetings

**Result**: Attendance pattern identified

**Time**: ~3 minutes

---

## Administrative Workflows

### Workflow 13: Monthly Report Generation

**Scenario**: End of month, need reports for management.

**Who**: School Admin or Accountant

**Steps**:

#### Financial Report

1. Navigate to **School Finances → Dashboard**
2. Note key metrics:
   ```
   Outstanding Balance: _______
   Total Expected: _______
   Total Collected: _______
   Collection Rate: _______%
   ```
3. Navigate to **Payment Alerts**
4. Count overdue accounts
5. Navigate to **Payment Collection**
6. Export payment data for the month
7. Compile into monthly financial report

#### Academic Report

1. Navigate to **School Management → Dashboard**
2. Note key metrics:
   ```
   Total Students: _______
   New Enrollments: _______
   Active Courses: _______
   Average Attendance: _______%
   ```
3. Navigate to **Attendance**
4. Export attendance summary
5. Compile into monthly academic report

**Result**: Complete monthly report

**Time**: ~30 minutes

---

### Workflow 14: Family Discount Recalculation

**Scenario**: Discount settings changed, need to recalculate all families.

**Who**: Accountant or Administrator

**Steps**:

1. Navigate to **School Finances → Family Discounts**
2. Review current discount structure:
   ```
   2 students: 5%
   3 students: 10%
   4+ students: 15%
   ```
3. If changes needed:
   a. Click **"Edit Discount Structure"**
   b. Modify percentages
   c. Save changes
4. Click **"Recalculate All Family Discounts"**
5. Confirm the action
6. System processes all enrollments
7. Review summary:
   ```
   Families affected: 45
   Payment schedules updated: 127
   ```
8. Verify a few families manually

**Warning**: This recalculates ALL active payment schedules

**Result**: All family discounts updated

**Time**: ~5 minutes (+ processing time)

---

### Workflow 15: Creating a New Course

**Scenario**: Adding a new course to the catalog.

**Who**: School Admin or Administrator

**Steps**:

1. Navigate to **School Management → Courses**
2. Click **"Add New Course"**
3. Fill in **Course Details**:
   ```
   ✓ Name: e.g., "Advanced English - B2 Level"
   ✓ Description: Course objectives and content
   ☐ Description File: Upload PDF syllabus (optional)
   ✓ Language: Select language
   ✓ Level: Select appropriate level
   ✓ Teacher: Select assigned teacher
   ✓ Session Duration: Hours and minutes
   ✓ Hours per Week: e.g., 6 hours
   ✓ Total Weeks: e.g., 20 weeks
   ✓ Total Months: e.g., 5 months
   ✓ Price per Month: e.g., 5000 DZD
   ✓ Total Price: e.g., 25000 DZD
   ```
4. Select **Payment Model**:
   - ☐ Full Payment
   - ☐ Monthly Installments
   - ☐ Monthly Subscription
5. Set **Status**:
   - Upcoming (not yet started)
   - In Progress (currently running)
   - Completed (finished)
6. Add **Certification** info (optional):
   - School diploma
   - State diploma
   - Other
7. Set **Max Students** (optional)
8. Check **Is Active** ✓
9. Click **"Add Course"**

**Result**: Course created and available for enrollment

**Time**: ~5 minutes

---

## End-of-Month/Period Workflows

### Workflow 16: Month-End Closing Process

**Scenario**: Last day of the month, close books.

**Who**: Accountant + School Admin

**Steps**:

#### Day 1-5: Payment Follow-Up

1. Review **Payment Alerts** → Overdue
2. Contact all overdue accounts
3. Record any payments received
4. Document promises to pay

#### Day 25-30: Final Collections

1. Process all payments received
2. Update payment statuses
3. Generate overdue list for next month
4. Send reminder notices (if applicable)

#### Last Day: Reporting

1. Navigate to **Financial Dashboard**
2. Take screenshots of:
   - Outstanding balance
   - Total collected
   - Payment alerts summary
3. Export **Payment Collection** data:
   - Filter by date range (this month)
   - Export to Excel
4. Calculate metrics:
   ```
   Total Expected this month: _______
   Total Collected: _______
   Collection Rate: _______%
   Outstanding: _______
   ```
5. Generate **Attendance Report**:
   - Export attendance data for month
   - Calculate attendance percentages
6. Compile reports for management

#### First Day of Next Month

1. Review carried-over overdue accounts
2. Set targets for new month
3. Plan follow-up strategy

**Result**: Month closed, reports generated

**Time**: ~2-3 hours over month-end period

---

## Quick Reference Cards

### For Receptionists

**Daily Tasks**:
1. Register new students
2. Answer enrollment inquiries
3. Direct payment questions to accountant

**Key Workflows**: 1, 2, 3

---

### For Accountants

**Daily Tasks**:
1. Record payments received
2. Check payment alerts
3. Follow up on overdue accounts
4. Process enrollments

**Weekly Tasks**:
1. Review payment collection rate
2. Contact overdue accounts
3. Generate payment reports

**Monthly Tasks**:
1. Month-end closing
2. Generate financial reports
3. Recalculate family discounts (if needed)

**Key Workflows**: 4, 5, 6, 7, 8, 9, 10, 14, 16

---

### For Teachers

**Daily Tasks**:
1. Check class schedule
2. Mark attendance after each class

**Weekly Tasks**:
1. Review attendance patterns
2. Report concerns to admin

**Key Workflows**: 11, 12

---

### For School Admins

**Daily Tasks**:
1. Monitor dashboards
2. Handle escalations
3. Review alerts

**Weekly Tasks**:
1. Review enrollment numbers
2. Check financial status
3. Coordinate staff

**Monthly Tasks**:
1. Generate reports
2. Review performance
3. Plan next month

**Key Workflows**: All workflows

---

## Tips for Efficiency

### Use Search Function
- Type student name instead of scrolling
- Faster than browsing large lists

### Filter Views
- Use status filters (Overdue, Pending, Paid)
- Focus on what needs attention

### Batch Similar Tasks
- Record all payments at once
- Mark attendance for all classes together
- Process enrollments in batches

### Keep Notes
- Always add notes for special cases
- Future reference helps troubleshooting
- Maintains institutional knowledge

### Regular Reviews
- Check dashboards daily
- Review alerts weekly
- Run reports monthly

---

**Version**: 0.6.0
**Last Updated**: January 13, 2026
