# Teacher Payments Feature - Specification Document
**Status:** 📋 PLANNING PHASE - Architectural Design
**Priority:** High
**Plugin Type:** Separate Plugin (like Calendar)
**Date Created:** December 29, 2024
**Last Updated:** December 29, 2024

---

## 🎯 Feature Overview

Implement a comprehensive teacher payment system as a **separate plugin** that handles multiple payment models including fixed salaries, revenue-based percentages, and profit-based percentages.

### Business Requirements

Teachers can be paid using multiple models:

1. **Fixed Salary**: Monthly or weekly agreed amount
2. **Course-Based Percentage**: Percentage of student fees for training courses (e.g., 33%, 50%, 67%)
3. **Subscription-Based Percentage**: Percentage of recurring student payments
4. **Calculation Basis Options**:
   - Revenue (total fees collected)
   - Profit (revenue minus expenses)

### Real-World Example

**Mrs. Smith's Payment Structure:**
- **English Subscription Course**: 33% (1/3) of monthly student fees per student (revenue-based)
- **A1 Training Course**: 50% of course fees per student (can be revenue or profit-based)

This creates complexity because:
- Same teacher, different courses, different payment models
- Need to track expenses for profit calculations
- Multiple calculation frequencies (monthly, per student payment, course completion)

---

## 🧩 The Core Challenge

Multiple dimensions of variability:

1. **Per Teacher**: One teacher teaches multiple courses
2. **Per Course**: Each course can have different payment model
3. **Per Payment Type**: Fixed, percentage of revenue, percentage of profit
4. **Per Time Period**: Monthly subscription vs one-time course fees
5. **Per Calculation**: When and how to calculate and pay

**Design Question:** How to balance **flexibility** vs **complexity**?

---

## 🏗️ Architectural Options

### **Option A: Course-Level Payment Agreements** ⭐ RECOMMENDED

**Concept:** Payment model is attached to each Teacher-Course relationship

**Structure:**
```
Teacher: Mrs. Smith
├─ English Subscription Course
│   └─ Payment: 33% of monthly student fees (revenue-based)
│
└─ A1 Training Course
    └─ Payment: 50% of course fees (revenue or profit-based)
```

**Database Structure:**
```sql
teacher_course_payments
├─ teacher_id
├─ course_id
├─ payment_model (fixed, percentage_revenue, percentage_profit)
├─ amount_or_percentage
├─ payment_frequency (per_student_payment, monthly, weekly, one_time)
├─ calculation_basis (revenue, profit)
└─ expense_deduction_rate (for profit-based)
```

**✅ Pros:**
- Clear: Each course has explicit payment terms
- Flexible: Different payment per course
- Easy to calculate: Look at course → get payment rule
- Easy to understand for users

**⚠️ Cons:**
- Setup overhead: Must configure each course
- No "default" teacher payment (but settings can be copied)

---

### **Option B: Teacher Default + Course Overrides**

**Concept:** Teacher has default payment model, specific courses can override

**Structure:**
```
Teacher: Mrs. Smith
├─ Default Payment: $2000/month (fixed salary)
│
├─ English Subscription Course (uses default)
│
└─ A1 Training Course (OVERRIDE)
    └─ Payment: 50% of course fees
```

**Database Structure:**
```sql
teachers table
└─ default_payment_model, default_amount, default_frequency

teacher_course_payment_overrides table
├─ teacher_id
├─ course_id
└─ payment fields (only if overriding default)
```

**✅ Pros:**
- Less setup: Most teachers use default
- Handles special cases via overrides
- Backward compatible: Can extend existing teachers table

**⚠️ Cons:**
- More complex logic: Check for override, fall back to default
- Less explicit: Must check if override exists
- Can be confusing when debugging payment calculations

---

### **Option C: Flexible Payment Rules (Advanced)**

**Concept:** Multiple payment rules can apply simultaneously with priorities

**Structure:**
```
Teacher: Mrs. Smith
├─ Rule 1: Fixed base salary $2000/month (priority 1)
├─ Rule 2: 33% of English course fees (priority 2, course_id=5)
└─ Rule 3: 50% of A1 training (priority 3, course_id=8)
```

**Database Structure:**
```sql
teacher_payment_rules
├─ teacher_id
├─ rule_name
├─ priority
├─ applies_to (all_courses, specific_course, course_type)
├─ course_id (optional)
├─ payment_model
├─ amount_or_percentage
└─ calculation_basis
```

**✅ Pros:**
- Maximum flexibility
- Can combine multiple rules (base salary + commission)
- Future-proof for complex scenarios

**❌ Cons:**
- Very complex to implement
- Harder for users to understand
- More prone to configuration errors
- Complex calculation logic (which rules apply? in what order?)

---

## 💡 Recommended Approach: Option A

### Why Option A (Course-Level Payments)?

1. **Clarity**: When viewing a course, payment terms are immediately visible
2. **Flexibility**: Handles all required scenarios:
   - English subscription: 33% of monthly student fees
   - A1 training: 50% of course fees (revenue or profit)
3. **Simplicity**: No inheritance, no overrides, no rule priority conflicts
4. **Scalability**: Easy to add more payment models later
5. **Debugging**: Easy to trace payment calculations

### How It Handles the Example:

```
Mrs. Smith teaches 2 courses:

Course 1: "English Subscription"
├─ Payment Model: percentage_revenue
├─ Percentage: 33%
├─ Frequency: per_student_payment (monthly recurring)
├─ Basis: revenue
└─ Calculation: 33% × student_monthly_fee × number_of_students

Course 2: "A1 Training"
├─ Payment Model: percentage_revenue (or percentage_profit)
├─ Percentage: 50%
├─ Frequency: per_student_payment (course installments)
├─ Basis: revenue (or profit if expenses deducted)
└─ Calculation: 50% × (student_payment - expenses) × number_of_students
```

---

## 📊 Payment Models Definition

### **Model 1: Fixed Salary**
```
Teacher receives: $X per [week/month]
Independent of: Student enrollment or course performance
Example: $2000/month
Use Case: Full-time staff teachers with guaranteed salary
```

### **Model 2: Percentage of Revenue**
```
Teacher receives: X% of student fees collected
Calculated per: Student payment received
Example:
  - Student pays $100/month
  - Teacher gets 33% = $33/student
  - 10 students = $330/month
```

### **Model 3: Percentage of Profit**
```
Teacher receives: X% of (Revenue - Expenses)
Expenses: Course costs (materials, room rental, overhead)
Example:
  - Student pays $100
  - Course expenses: $20/student
  - Profit: $80/student
  - Teacher gets 50% = $40/student
  - 10 students = $400
```

---

## 🗄️ Proposed Database Schema

### Table: `sm_teacher_course_payments`
```sql
CREATE TABLE {prefix}sm_teacher_course_payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,

    -- Payment Model Configuration
    payment_model ENUM('fixed_salary', 'percentage_revenue', 'percentage_profit') NOT NULL,

    -- Amount/Percentage
    amount DECIMAL(10,2) DEFAULT NULL,           -- For fixed_salary model
    percentage DECIMAL(5,2) DEFAULT NULL,        -- For percentage models (0.00-100.00)

    -- Payment Timing & Frequency
    payment_frequency ENUM(
        'weekly',                 -- Every week
        'monthly',                -- Every month
        'per_student_payment',    -- Each time student pays
        'course_completion'       -- When course ends
    ) DEFAULT 'monthly',

    -- Calculation Configuration
    calculation_basis ENUM('revenue', 'profit') DEFAULT 'revenue',

    -- Expense Handling (for profit-based calculations)
    fixed_expense_per_student DECIMAL(10,2) DEFAULT 0.00,
    percentage_expense_deduction DECIMAL(5,2) DEFAULT 0.00,  -- % of revenue to deduct

    -- Administrative
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    KEY teacher_id (teacher_id),
    KEY course_id (course_id),
    KEY payment_model (payment_model),
    UNIQUE KEY teacher_course (teacher_id, course_id)  -- One payment config per teacher-course
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table: `sm_teacher_payments`
```sql
CREATE TABLE {prefix}sm_teacher_payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT(11) NOT NULL,
    course_id INT(11) DEFAULT NULL,
    student_id INT(11) DEFAULT NULL,              -- If per-student payment
    enrollment_id INT(11) DEFAULT NULL,           -- Link to enrollment
    payment_schedule_id INT(11) DEFAULT NULL,     -- Link to student payment

    -- Calculation
    amount_calculated DECIMAL(10,2) NOT NULL,     -- Calculated teacher payment
    amount_paid DECIMAL(10,2) DEFAULT 0.00,       -- Actually paid amount

    -- Breakdown (JSON for transparency)
    calculation_details TEXT,
    /* Example JSON:
    {
        "model": "percentage_revenue",
        "percentage": 50,
        "student_payment": 100,
        "expenses": 20,
        "revenue": 100,
        "profit": 80,
        "teacher_share": 40
    }
    */

    -- Payment Tracking
    payment_date DATE DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,      -- Cash, Bank Transfer, Check, etc.
    reference_number VARCHAR(100) DEFAULT NULL,

    -- Status
    status ENUM('pending', 'paid', 'cancelled', 'on_hold') DEFAULT 'pending',

    -- Administrative
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes
    KEY teacher_id (teacher_id),
    KEY course_id (course_id),
    KEY student_id (student_id),
    KEY enrollment_id (enrollment_id),
    KEY payment_date (payment_date),
    KEY status (status),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table: `sm_course_expenses`
```sql
-- Track expenses per course for profit calculations
CREATE TABLE {prefix}sm_course_expenses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    course_id INT(11) NOT NULL,

    expense_type ENUM('materials', 'classroom_rental', 'overhead', 'other') NOT NULL,
    expense_description VARCHAR(255),

    -- Amount Configuration
    amount DECIMAL(10,2) NOT NULL,
    is_per_student TINYINT(1) DEFAULT 0,          -- Fixed per course or per student?

    -- Period
    expense_date DATE,

    -- Administrative
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    KEY course_id (course_id),
    KEY expense_type (expense_type),
    KEY expense_date (expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🎯 MVP Feature Set (Phase 1)

### Must Have for Initial Release:
1. ✅ Teacher-Course payment configuration (CRUD)
2. ✅ Fixed salary model (monthly)
3. ✅ Percentage of revenue model
4. ✅ Manual payment recording
5. ✅ Payment history tracking
6. ✅ Basic reporting (teacher earnings by course)

### Phase 2 Features:
1. ⏳ Percentage of profit model with expense tracking
2. ⏳ Automated payment calculation (trigger on student payment)
3. ⏳ Weekly salary option
4. ⏳ Payment reminders/alerts
5. ⏳ Advanced reporting (monthly summaries, tax reports)

### Phase 3 Features:
1. 📅 Multi-currency support
2. 📅 Automated invoice generation
3. 📅 Tax withholding calculations
4. 📅 Payment approval workflow
5. 📅 Integration with accounting software

---

## 🤔 Decision Points (To Be Answered Next Session)

### **Scenario 1: Configuration Preference**
If a teacher has 5 courses, would you prefer:
- **Option A**: Configure payment for each of the 5 courses individually
- **Option B**: Set one default payment, override 2 special courses

### **Scenario 2: Audit & Transparency**
When reviewing teacher payments, would you prefer:
- **Option A**: Look at course → see exact payment rule immediately
- **Option C**: Look at teacher → see list of rules → determine which applies

### **Scenario 3: Future Complexity**
Do you anticipate needing:
- Base salary + commission on courses?
- Different rates for different student types (VIP students, group discounts)?
- Tiered percentages (first 10 students 50%, next 10 students 30%)?

### **Scenario 4: Payment Timing**
When should teacher get paid?
- Immediately when student pays?
- Monthly batch (collect all payments, calculate once)
- After course completion?
- Manual approval process?

### **Scenario 5: Student Non-Payment Risk**
If a student hasn't fully paid their course fee:
- Teacher gets paid based on what student actually paid?
- Teacher gets paid full percentage (school absorbs risk)?
- Teacher gets paid only after student completes payment?

---

## 🔧 Plugin Architecture

### Plugin Name
`school-management-teacher-payments`

### Plugin Structure
```
school-management-teacher-payments/
├── school-management-teacher-payments.php    [Main plugin file]
├── includes/
│   ├── class-smtp-teacher-course-payments.php    [Model: Payment configs]
│   ├── class-smtp-teacher-payments.php           [Model: Payment history]
│   ├── class-smtp-course-expenses.php            [Model: Course expenses]
│   ├── class-smtp-payment-calculator.php         [Business logic: Calculate payments]
│   ├── class-smtp-payment-configs-page.php       [Admin UI: Configure payments]
│   ├── class-smtp-payment-history-page.php       [Admin UI: Payment tracking]
│   ├── class-smtp-reports-page.php               [Admin UI: Reports]
│   └── class-smtp-github-updater.php             [Auto-update system]
├── assets/
│   ├── css/
│   │   └── smtp-admin.css
│   └── js/
│       └── smtp-admin.js
├── languages/
│   ├── school-management-teacher-payments.pot
│   ├── school-management-teacher-payments-fr_FR.po
│   └── school-management-teacher-payments-fr_FR.mo
└── README.md
```

### Dependencies
- Requires: School Management plugin (main)
- Integrates with:
  - `sm_teachers` table
  - `sm_courses` table
  - `sm_enrollments` table
  - `sm_payment_schedules` table (student payments)

---

## 🚀 Implementation Steps

### Step 1: Planning & Design (CURRENT)
- [x] Define payment models
- [x] Propose architectural options
- [ ] User decides on Option A, B, or C
- [ ] Answer decision-point scenarios
- [ ] Finalize database schema

### Step 2: Database Setup
- [ ] Create database migration script
- [ ] Add plugin activation hook
- [ ] Create tables with proper indexes
- [ ] Add sample data for testing

### Step 3: Core Models
- [ ] Create CRUD classes for each table
- [ ] Build payment calculator class
- [ ] Write automated tests

### Step 4: Admin Interface
- [ ] Payment configuration page (link teachers to courses with payment model)
- [ ] Payment history page (view/record payments)
- [ ] Course expenses page (track costs for profit calculations)

### Step 5: Integration
- [ ] Hook into student payment events
- [ ] Trigger teacher payment calculations
- [ ] Update dashboards

### Step 6: Reporting
- [ ] Teacher earnings summary
- [ ] Course profitability report
- [ ] Payment due/overdue alerts

### Step 7: Testing & Deployment
- [ ] Test all payment models with sample data
- [ ] French translations
- [ ] Create GitHub repository
- [ ] Package as installable plugin

---

## 📝 Notes & Considerations

### Current Status of `sm_payment_terms` Table
- **Status**: Exists but currently unused
- **Decision Needed**:
  - Repurpose for teacher payment terms?
  - Keep separate and create new tables?
  - **Recommendation**: Keep separate - `sm_payment_terms` is for student payment schedules, teacher payments need different structure

### Calculation Trigger Points
Teacher payments can be calculated/generated:
1. **Real-time**: When student makes payment (auto-calculate teacher share)
2. **Scheduled**: Cron job runs monthly/weekly to batch calculate
3. **Manual**: Admin clicks "Generate Payments" button
4. **Event-based**: When enrollment status changes or course completes

### Data Integrity Considerations
- What happens to teacher payments if:
  - Student gets refund?
  - Enrollment is cancelled?
  - Course is deleted?
- **Recommendation**: Soft deletes and status tracking (paid/pending/cancelled)

---

## 🎓 Example Use Cases

### Use Case 1: English Subscription Course
```
Teacher: Mrs. Smith
Course: English Conversation (Monthly Subscription)
Payment Model: percentage_revenue
Percentage: 33%
Frequency: per_student_payment (monthly)
Calculation: revenue

Students:
├─ Student A: $100/month → Teacher gets $33
├─ Student B: $100/month → Teacher gets $33
└─ Student C: $100/month → Teacher gets $33

Monthly Teacher Payment: $99
```

### Use Case 2: A1 Training Course (Profit-Based)
```
Teacher: Mr. Jones
Course: A1 French Training (3-month course)
Payment Model: percentage_profit
Percentage: 50%
Frequency: per_student_payment
Calculation: profit

Course Fees per Student: $300 (total course)
Course Expenses per Student: $50 (materials + overhead)
Profit per Student: $250

Students:
├─ Student X: $300 → Profit $250 → Teacher gets $125
├─ Student Y: $300 → Profit $250 → Teacher gets $125
└─ Student Z: $300 → Profit $250 → Teacher gets $125

Total Teacher Payment for Course: $375
```

### Use Case 3: Fixed Salary Teacher
```
Teacher: Mrs. Davis
Payment Model: fixed_salary
Amount: $2000
Frequency: monthly
Independent of: Course enrollment

All Courses:
├─ Math Grade 1
├─ Math Grade 2
└─ Science Grade 3

Monthly Teacher Payment: $2000 (regardless of student count)
```

---

## 🔍 Open Questions for Next Session

1. Which architectural option do you prefer (A, B, or C)?
2. How should we handle course expenses tracking?
3. What payment timing makes most sense for your school?
4. Should we allow combining payment models (base salary + commission)?
5. Do we need approval workflow before paying teachers?
6. Any specific reports/exports needed (tax forms, etc.)?

---

**Document Version:** 1.0
**Status:** AWAITING ARCHITECTURAL DECISION
**Next Steps:** Review options, answer scenario questions, finalize approach
**Estimated Development Time (MVP):** 20-25 hours after design approval
