# Classes/Sections Feature - Future Implementation
**School Management Plugin**
**Date Documented:** November 23, 2025
**Status:** PLANNED - Not Yet Implemented

---

## 🎯 Business Need

### Current Limitation
The plugin currently has:
- **Levels** (Grades: Gr1, Gr2, Gr3...)
- **Courses** (link directly to Level)
- **Problem:** Cannot have multiple sections per grade (Gr1-A, Gr1-B, Gr1-C) when student numbers exceed classroom capacity

### Target Use Case
- **Commercial plugin** for resale to schools
- Schools need multiple sections per grade
- Each section:
  - Has its own classroom (with capacity limit)
  - Has multiple subjects/courses
  - Has a primary teacher
  - Should have its own student roster

---

## 📊 Proposed Solution: Classes/Sections Table

### New Hierarchy
```
Level (Grade) 
  └─> Class/Section (Gr1-A, Gr1-B...)
        ├─> Classroom (with capacity)
        ├─> Primary Teacher
        └─> Courses/Subjects
              └─> Enrollments
```

### Example Data Flow
1. **Create Level:** "Grade 1"
2. **Create Classes:** 
   - "Grade 1 - Section A" (Classroom 101, capacity 30, Teacher: Mrs. Smith)
   - "Grade 1 - Section B" (Classroom 102, capacity 30, Teacher: Mr. Jones)
3. **Create Courses for each class:**
   - "Math - Gr1-A" (links to Section A + Math teacher)
   - "English - Gr1-A" (links to Section A + English teacher)
   - "Math - Gr1-B" (links to Section B + Math teacher)
   - "English - Gr1-B" (links to Section B + English teacher)
4. **Enroll Students:**
   - Option A: Enroll in CLASS → automatically enrolled in all class courses
   - Option B: Enroll in individual COURSES (more flexible)

---

## 🗄️ Database Changes

### New Table: `sm_classes`
```sql
CREATE TABLE {prefix}sm_classes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    level_id INT(11) NOT NULL,
    section_name VARCHAR(50) NOT NULL,              -- "A", "B", "C" or "Section 1", "Section 2"
    display_name VARCHAR(100) NOT NULL,             -- "Grade 1 - Section A"
    classroom_id INT(11) DEFAULT NULL,
    primary_teacher_id INT(11) DEFAULT NULL,
    max_students INT(11) DEFAULT 30,
    academic_year VARCHAR(20) DEFAULT NULL,         -- "2024-2025"
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY level_id (level_id),
    KEY classroom_id (classroom_id),
    KEY primary_teacher_id (primary_teacher_id),
    KEY academic_year (academic_year),
    KEY is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Update Existing Table: `sm_courses`
```sql
-- Add class_id column to link courses to classes
ALTER TABLE {prefix}sm_courses 
ADD COLUMN class_id INT(11) DEFAULT NULL AFTER level_id,
ADD KEY class_id (class_id);

-- Note: Keep level_id for backward compatibility and reporting
-- Courses can be filtered by level OR by class
```

### Update Existing Table: `sm_enrollments`
```sql
-- Add class_id column for class-based enrollments
ALTER TABLE {prefix}sm_enrollments 
ADD COLUMN class_id INT(11) DEFAULT NULL AFTER level_id,
ADD KEY class_id (class_id);

-- Note: enrollment can be:
-- 1. Course-based: course_id is set, class_id can be NULL
-- 2. Class-based: class_id is set, course_id might be NULL (enrolled in all class courses)
```

---

## 🏗️ Implementation Steps

### Phase 1: Database & Core Classes
1. Create database migration script
2. Add activation hook to create `sm_classes` table
3. Create `class-sm-classes.php` model class with CRUD operations
4. Create helper functions for class operations

### Phase 2: Admin Interface
1. Add "Classes" menu item to admin menu
2. Create `class-sm-classes-page.php` for Classes management
3. Build Classes list page with:
   - Search and filter (by level, academic year, status)
   - Sortable columns
   - Add/Edit/Delete operations
4. Build Add/Edit class form:
   - Level dropdown
   - Section name input
   - Classroom selection (with capacity display)
   - Primary teacher selection
   - Max students input
   - Academic year input
   - Notes textarea

### Phase 3: Update Courses Module
1. Add "Class" field to course form (dropdown showing classes for selected level)
2. Update course creation to link to class_id
3. Update courses list to show class/section
4. Add filter by class

### Phase 4: Update Enrollments Module
1. Add enrollment type selection:
   - **Individual Course:** Enroll in specific course (current behavior)
   - **Class Enrollment:** Enroll in all courses for a class
2. For class enrollments:
   - Select class instead of individual course
   - Automatically create enrollments for all courses in that class
   - Check class capacity before allowing enrollment
3. Update enrollments list to show class info
4. Add class filter to enrollments list

### Phase 5: Capacity Management
1. Add capacity checking when enrolling students
2. Show "X/30 students" on classes list
3. Warning when class is near capacity
4. Prevent enrollment when class is full
5. Dashboard widget: "Classes at capacity"

### Phase 6: Reports & Dashboard
1. Update dashboard to show:
   - Classes per level
   - Classes at/near capacity
   - Students per class
2. Add class-based reports:
   - Class roster report
   - Class attendance summary
   - Class payment summary
3. Update existing reports to include class/section

### Phase 7: Migration & Compatibility
1. Data migration script for existing installations:
   - Option A: Create default class per level (e.g., "Grade 1 - Section A")
   - Option B: Keep courses without classes (backward compatible)
2. Settings page option: Enable/Disable classes feature
3. Backward compatibility mode for schools not using sections

---

## 🔧 Key Files to Create/Modify

### New Files to Create
```
includes/
  ├── class-sm-classes.php                    [Model: CRUD operations]
  └── class-sm-classes-page.php               [Admin UI: Classes management]

assets/
  └── js/
      └── sm-classes-admin.js                 [JavaScript for classes page]
```

### Files to Modify
```
school-management.php                         [Add classes table creation, menu item]
includes/
  ├── class-sm-courses-page.php               [Add class selection field]
  ├── class-sm-enrollments-page.php           [Add class enrollment option]
  └── class-sm-admin-menu.php                 [Add classes menu, update dashboard]
```

---

## ❓ Questions to Answer Before Implementation

### Enrollment Model
**Q1:** How should students enroll?
- **Option A:** Enroll in CLASS → auto-enrolled in all class courses
- **Option B:** Enroll in individual COURSES (more flexible, current behavior)
- **Option C:** Hybrid - allow both methods

**Current Thinking:** Option C (Hybrid) for maximum flexibility

---

### Class Naming Convention
**Q2:** What naming format for classes?
- Option A: "Grade 1 - Section A", "Grade 1 - Section B"
- Option B: "1A", "1B", "1C"
- Option C: "Class 101", "Class 102"
- Option D: Custom/Configurable

**Current Thinking:** Option A with Option D (make it configurable)

---

### Teacher Assignment
**Q3:** Teacher roles per class?
- **Primary Teacher:** One teacher assigned to the class (homeroom)
- **Subject Teachers:** Different teachers per course within the class
- **Current:** Only subject teachers exist (assigned to courses)

**Current Thinking:** Add primary teacher to class, keep subject teachers on courses

---

### Capacity Limits
**Q4:** Where should capacity be enforced?
- **Class level:** Max students per class (e.g., 30)
- **Classroom level:** Already exists (physical room capacity)
- **Relationship:** Class capacity ≤ Classroom capacity

**Current Thinking:** 
- Classroom capacity = physical limit (already exists)
- Class capacity = pedagogical limit (can be ≤ classroom capacity)
- When creating class, default class capacity = classroom capacity

---

### Course Grouping
**Q5:** Are courses always tied to one class?
- **Option A:** One course = one class (Math Gr1-A is different from Math Gr1-B)
- **Option B:** One course can have multiple sections/classes
- **Current:** One course = multiple enrollments from different students

**Current Thinking:** Option A - create separate course instances per class for:
- Different schedules per class
- Different teachers per class
- Different classrooms per class
- Cleaner data structure

---

### Academic Year Tracking
**Q6:** Should classes be tied to academic year?
- **Yes:** Classes are recreated each year (2024-2025, 2025-2026)
- **No:** Classes are continuous, students move up

**Current Thinking:** Yes - add academic_year field to track historical data

---

### Backward Compatibility
**Q7:** How to handle existing installations?
- **Option A:** Force migration - all courses must have classes
- **Option B:** Optional - classes feature can be enabled/disabled
- **Option C:** Auto-migrate - create default "Section A" for each level

**Current Thinking:** Option B + Option C - Make it optional but provide easy migration

---

## 🎯 MVP Feature Set (First Release)

When implementing, start with these core features:

### Must Have (MVP)
1. ✅ Classes CRUD (Create, Read, Update, Delete)
2. ✅ Link classes to levels, classrooms, primary teacher
3. ✅ Link courses to classes
4. ✅ Show class info on courses list
5. ✅ Basic capacity management (show current/max students)
6. ✅ Simple migration for existing data

### Nice to Have (Phase 2)
1. ⏳ Class-based enrollment (enroll in class → get all courses)
2. ⏳ Advanced capacity warnings
3. ⏳ Class roster reports
4. ⏳ Dashboard widgets for classes
5. ⏳ Academic year management

### Future Enhancements (Phase 3)
1. 📅 Automated class creation for new academic year
2. 📅 Bulk student assignment to classes
3. 📅 Class timetable/schedule management
4. 📅 Parent portal - view child's class
5. 📅 Class performance analytics

---

## 🚨 Important Considerations

### Data Integrity
- When deleting a class, what happens to:
  - ❓ Linked courses?
  - ❓ Existing enrollments?
  - ❓ Payment records?
- **Recommendation:** Soft delete (set is_active = 0) instead of hard delete

### Performance
- Add database indexes on:
  - level_id, class_id in courses table
  - class_id in enrollments table
- Consider caching class lists for dropdown menus

### Translation
- All class-related strings need French translation
- New terms: "Section", "Class", "Homeroom Teacher"

### Permissions
- New capability: `manage_classes`
- Who can create/edit/delete classes?

---

## 📝 User Stories

### As a School Administrator:
1. I want to create multiple sections per grade so I can organize students when one class is full
2. I want to assign a homeroom teacher to each section
3. I want to see how many students are in each section
4. I want to prevent enrolling more students than the section capacity

### As a Teacher:
1. I want to see which section I'm the homeroom teacher for
2. I want to view my section's roster
3. I want to teach multiple sections of the same subject

### As a Data Entry Clerk:
1. I want to enroll a student in a section once and have them automatically registered for all subjects
2. I want to see which sections have available spots
3. I want to move a student from one section to another

---

## 🔄 Migration Strategy

### For New Installations
- Classes feature enabled by default
- Setup wizard guides through creating first classes

### For Existing Installations
**Option 1: Auto-Migration (Recommended)**
```
For each Level:
  → Create one default Class (e.g., "Grade 1 - Section A")
  → Link all existing courses to this default class
  → Link all existing enrollments to this class
```

**Option 2: Manual Migration**
```
1. Admin enables "Classes" feature in settings
2. Plugin shows migration wizard
3. Admin creates classes manually
4. Admin assigns courses to classes
5. System validates and completes migration
```

**Option 3: Hybrid**
```
1. Auto-create default classes (as Option 1)
2. Notify admin: "Classes created - review and customize"
3. Admin can split default classes into multiple sections
4. System re-assigns courses and enrollments
```

---

## 💾 Sample Data Structure

### Example: Grade 1 with 2 Sections

**Levels Table**
```
id | name     | description
1  | Grade 1  | First Grade
```

**Classes Table**
```
id | level_id | section_name | display_name          | classroom_id | primary_teacher_id | max_students
1  | 1        | A            | Grade 1 - Section A   | 5            | 3                  | 30
2  | 1        | B            | Grade 1 - Section B   | 6            | 7                  | 28
```

**Courses Table**
```
id | name          | level_id | class_id | teacher_id | classroom_id
1  | Math Gr1-A    | 1        | 1        | 10         | 5
2  | English Gr1-A | 1        | 1        | 11         | 5
3  | Math Gr1-B    | 1        | 2        | 10         | 6
4  | English Gr1-B | 1        | 2        | 12         | 6
```

**Enrollments Table**
```
id | student_id | course_id | class_id | level_id | status
1  | 101        | 1         | 1        | 1        | active
2  | 101        | 2         | 1        | 1        | active
3  | 102        | 3         | 2        | 1        | active
4  | 102        | 4         | 2        | 1        | active
```

---

## 🎨 UI Mockup Ideas

### Classes List Page
```
[Classes] [+ Add New Class]

Search: [____________________] [🔍]
Filter: [All Levels ▼] [All Academic Years ▼] [Active ▼]

╔════════════════════════════════════════════════════════════╗
║ Class Name          | Level   | Classroom | Teacher | Students | Status  | Actions      ║
╠════════════════════════════════════════════════════════════╣
║ Grade 1 - Section A | Grade 1 | Room 101  | Mrs. S  | 28/30    | Active  | [Edit] [View]║
║ Grade 1 - Section B | Grade 1 | Room 102  | Mr. J   | 30/30    | Full    | [Edit] [View]║
║ Grade 2 - Section A | Grade 2 | Room 201  | Mrs. P  | 25/30    | Active  | [Edit] [View]║
╚════════════════════════════════════════════════════════════╝
```

### Add/Edit Class Form
```
[Add New Class]

Level: [Grade 1 ▼] *
Section Name: [A___] *
Classroom: [Room 101 (Capacity: 30) ▼]
Primary Teacher: [Mrs. Smith ▼]
Max Students: [30] (Classroom capacity: 30)
Academic Year: [2024-2025_]
Notes: [____________________________]
       [____________________________]

Status: [☑] Active

[Save Class] [Save & Add Courses] [Cancel]
```

---

## 🏁 Ready to Implement?

When you're ready to build this feature, reference this document and confirm:
1. Enrollment model preference (A, B, or C)
2. Class naming convention
3. Migration strategy choice
4. MVP vs Full feature set

Then we can proceed with implementation in phases!

---

**Document Version:** 1.0
**Last Updated:** November 23, 2025
**Status:** SPECIFICATION COMPLETE - AWAITING IMPLEMENTATION