# Command Palette / Quick Actions Feature
**Priority:** High
**Status:** 📋 Planned for Future Implementation
**Estimated Development Time:** 5-6 hours
**Complexity:** Medium

---

## 🎯 Feature Overview

Implement a modern Command Palette (like GitHub, VS Code, Linear) to provide quick access to common actions throughout the School Management System.

### Problem Statement
Current "Quick Actions" card is buried at the bottom of the dashboard, making it invisible without scrolling. This defeats the purpose of quick access to frequently-used actions.

### Solution
Implement a searchable Command Palette with:
- Keyboard shortcut activation (Ctrl+K / Cmd+K)
- Visual button trigger for discoverability
- Role-based action filtering
- Customizable favorites and recent actions
- Category organization

---

## 🎨 User Experience Design

### Primary Interaction
```
User presses Ctrl+K (or Cmd+K on Mac)
           ↓
Command Palette opens as modal overlay
           ↓
User types to search or browses categories
           ↓
Selects action with Enter or mouse click
           ↓
Action executes, palette closes
```

### Secondary Interaction
```
User clicks "⚡ Quick Actions (Ctrl+K)" button in header
           ↓
Command Palette opens
           ↓
(Same flow as above)
```

---

## 🖼️ UI Mockup

### Header Integration
```
┌────────────────────────────────────────────────────────────┐
│ School Management System              [⚡ Quick Actions ⌘K] │
└────────────────────────────────────────────────────────────┘
```

### Command Palette Modal
```
┌──────────────────────────────────────────────────────────┐
│                                                           │
│  🔍 Search actions...                            [Esc]   │
│  ─────────────────────────────────────────────────────   │
│                                                           │
│  📌 FAVORITES                                            │
│  › ➕ Add New Student                          Ctrl+N    │
│  › 💰 View Payments                                      │
│                                                           │
│  🕐 RECENT                                               │
│  › 📝 New Enrollment                                     │
│  › 📊 View Reports                                       │
│                                                           │
│  📚 ACADEMIC                                             │
│  › ➕ Add New Teacher                                    │
│  › 🏫 Add Classroom                                      │
│  › 📚 Add Course                                         │
│  › 📊 Add Level                                          │
│                                                           │
│  💰 FINANCIAL                                            │
│  › 💳 View Payments                                      │
│  › 🔔 Payment Alerts                                     │
│  › 📊 Financial Reports                                  │
│  › 📋 Payment Terms                                      │
│                                                           │
│  ⚙️ SETTINGS                                             │
│  › 🎨 Customize Quick Actions                            │
│  › ⚙️ System Settings                                    │
│                                                           │
└──────────────────────────────────────────────────────────┘

  Use ↑↓ to navigate, ↵ to select, Esc to close
```

### Search State
```
┌──────────────────────────────────────────────────────────┐
│                                                           │
│  🔍 pay▊                                         [Esc]   │
│  ─────────────────────────────────────────────────────   │
│                                                           │
│  Found 4 actions matching "pay":                         │
│                                                           │
│  › 💰 View Payments                                      │
│  › 🔔 Payment Alerts                                     │
│  › 📊 Financial Reports                                  │
│  › 📋 Payment Terms                                      │
│                                                           │
└──────────────────────────────────────────────────────────┘
```

### Customization Panel
```
┌──────────────────────────────────────────────────────────┐
│  🎨 Customize Quick Actions                              │
│  ─────────────────────────────────────────────────────   │
│                                                           │
│  Enable/Disable Actions:                                 │
│  ☑ Add New Student                            [★ Pin]   │
│  ☑ Add New Teacher                            [☆ Pin]   │
│  ☑ Add Course                                 [☆ Pin]   │
│  ☐ Add Level                                  [☆ Pin]   │
│  ☑ New Enrollment                             [★ Pin]   │
│  ☑ View Payments                              [★ Pin]   │
│  ☐ Payment Alerts                             [☆ Pin]   │
│  ☑ View Reports                               [☆ Pin]   │
│                                                           │
│  Role Presets:                                           │
│  [School Admin] [Accountant] [Teacher] [Manager]         │
│                                                           │
│                               [Cancel] [Save Changes]    │
└──────────────────────────────────────────────────────────┘
```

---

## 🏗️ Technical Architecture

### File Structure
```
school-management/
├── includes/
│   ├── class-sm-command-palette.php          (Core logic)
│   ├── class-sm-command-palette-actions.php  (Action registry)
│   ├── class-sm-command-palette-ajax.php     (AJAX handlers)
│   └── class-sm-user-preferences.php         (User settings)
├── assets/
│   ├── js/
│   │   ├── sm-command-palette.js             (Main JS)
│   │   └── sm-command-palette.min.js         (Minified)
│   └── css/
│       ├── sm-command-palette.css            (Styles)
│       └── sm-command-palette.min.css        (Minified)
└── languages/
    └── (Translation strings)
```

### Database Schema

#### New Table: `wp_sm_user_quick_actions`
```sql
CREATE TABLE wp_sm_user_quick_actions (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    action_id VARCHAR(100) NOT NULL,
    is_enabled TINYINT(1) DEFAULT 1,
    is_favorite TINYINT(1) DEFAULT 0,
    sort_order INT(11) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_action (user_id, action_id),
    KEY user_id (user_id),
    KEY action_id (action_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### New Table: `wp_sm_action_history`
```sql
CREATE TABLE wp_sm_action_history (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    action_id VARCHAR(100) NOT NULL,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY action_id (action_id),
    KEY executed_at (executed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 📋 Action Registry

### Action Definition Structure
```php
array(
    'id' => 'add_student',
    'title' => __( 'Add New Student', 'CTADZ-school-management' ),
    'icon' => '➕',
    'category' => 'academic',
    'url' => '?page=school-management-students&action=add',
    'capability' => 'manage_students',
    'keywords' => array( 'student', 'add', 'new', 'enroll' ),
    'roles' => array( 'school_admin', 'administrator' ),
    'default_enabled' => true,
    'default_favorite' => false,
)
```

### Default Actions by Category

#### Academic (📚)
- Add New Student
- Add New Teacher
- Add Course
- Add Level
- New Enrollment
- Add Classroom
- View Calendar

#### Financial (💰)
- View Payments
- Payment Alerts
- Financial Reports
- Payment Terms
- Export Payments

#### Reports (📊)
- Student Reports
- Financial Reports
- Attendance Reports
- Teacher Reports

#### Settings (⚙️)
- System Settings
- Customize Quick Actions
- User Management

---

## 🎭 Role-Based Presets

### School Administrator
```php
array(
    'enabled' => array(
        'add_student', 'add_teacher', 'add_course',
        'new_enrollment', 'add_classroom', 'view_calendar'
    ),
    'favorites' => array(
        'add_student', 'new_enrollment', 'view_calendar'
    )
)
```

### Accountant
```php
array(
    'enabled' => array(
        'view_payments', 'payment_alerts', 'financial_reports',
        'payment_terms', 'export_payments'
    ),
    'favorites' => array(
        'view_payments', 'payment_alerts', 'financial_reports'
    )
)
```

### General Manager
```php
array(
    'enabled' => array(
        'student_reports', 'financial_reports', 'attendance_reports',
        'teacher_reports', 'system_settings'
    ),
    'favorites' => array(
        'financial_reports', 'student_reports'
    )
)
```

### Teacher
```php
array(
    'enabled' => array(
        'my_schedule', 'mark_attendance', 'enter_grades',
        'my_students', 'my_courses'
    ),
    'favorites' => array(
        'my_schedule', 'mark_attendance', 'my_students'
    )
)
```

---

## 💻 Code Examples

### Core Class Structure

#### class-sm-command-palette.php
```php
<?php
class SM_Command_Palette {

    private static $instance = null;
    private $actions = array();

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->register_default_actions();
    }

    private function init_hooks() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_footer', array( $this, 'render_modal' ) );
        add_action( 'wp_ajax_sm_get_quick_actions', array( $this, 'ajax_get_actions' ) );
        add_action( 'wp_ajax_sm_save_preferences', array( $this, 'ajax_save_preferences' ) );
        add_action( 'wp_ajax_sm_track_action', array( $this, 'ajax_track_action' ) );
    }

    public function register_action( $action ) {
        if ( $this->validate_action( $action ) ) {
            $this->actions[ $action['id'] ] = $action;
        }
    }

    public function get_actions_for_user( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        $user_preferences = $this->get_user_preferences( $user_id );
        $user = get_userdata( $user_id );

        $filtered_actions = array();

        foreach ( $this->actions as $action ) {
            // Check capability
            if ( ! current_user_can( $action['capability'] ) ) {
                continue;
            }

            // Check role (if specified)
            if ( ! empty( $action['roles'] ) ) {
                $has_role = false;
                foreach ( $action['roles'] as $role ) {
                    if ( in_array( $role, $user->roles ) ) {
                        $has_role = true;
                        break;
                    }
                }
                if ( ! $has_role ) {
                    continue;
                }
            }

            // Check if user disabled this action
            if ( isset( $user_preferences[ $action['id'] ] )
                 && ! $user_preferences[ $action['id'] ]['enabled'] ) {
                continue;
            }

            $filtered_actions[] = $action;
        }

        return $filtered_actions;
    }

    // ... more methods
}
```

### JavaScript Implementation

#### sm-command-palette.js
```javascript
(function($) {
    'use strict';

    const CommandPalette = {

        $modal: null,
        $searchInput: null,
        actions: [],
        filteredActions: [],
        selectedIndex: 0,

        init: function() {
            this.cacheElements();
            this.bindEvents();
            this.loadActions();
        },

        cacheElements: function() {
            this.$modal = $('#sm-command-palette');
            this.$searchInput = $('#sm-cp-search');
            this.$actionsList = $('#sm-cp-actions-list');
            this.$trigger = $('#sm-cp-trigger');
        },

        bindEvents: function() {
            // Keyboard shortcut: Ctrl+K or Cmd+K
            $(document).on('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.open();
                }

                // Escape to close
                if (e.key === 'Escape' && this.$modal.is(':visible')) {
                    this.close();
                }
            });

            // Click trigger button
            this.$trigger.on('click', () => this.open());

            // Search input
            this.$searchInput.on('input', () => this.handleSearch());

            // Keyboard navigation in modal
            this.$searchInput.on('keydown', (e) => this.handleKeyboard(e));

            // Click outside to close
            this.$modal.on('click', (e) => {
                if ($(e.target).is(this.$modal)) {
                    this.close();
                }
            });
        },

        open: function() {
            this.$modal.fadeIn(200);
            this.$searchInput.focus().val('');
            this.renderActions(this.actions);
            this.selectedIndex = 0;
        },

        close: function() {
            this.$modal.fadeOut(200);
        },

        handleSearch: function() {
            const query = this.$searchInput.val().toLowerCase();

            if (query === '') {
                this.renderActions(this.actions);
                return;
            }

            this.filteredActions = this.actions.filter(action => {
                const searchText = [
                    action.title,
                    action.category,
                    ...action.keywords
                ].join(' ').toLowerCase();

                return searchText.includes(query);
            });

            this.renderActions(this.filteredActions);
        },

        handleKeyboard: function(e) {
            const currentActions = this.filteredActions.length > 0
                ? this.filteredActions
                : this.actions;

            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.selectedIndex = Math.min(
                        this.selectedIndex + 1,
                        currentActions.length - 1
                    );
                    this.updateSelection();
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                    this.updateSelection();
                    break;

                case 'Enter':
                    e.preventDefault();
                    this.executeAction(currentActions[this.selectedIndex]);
                    break;
            }
        },

        executeAction: function(action) {
            // Track action in history
            this.trackAction(action.id);

            // Close modal
            this.close();

            // Execute action
            if (action.callback) {
                action.callback();
            } else if (action.url) {
                window.location.href = action.url;
            }
        },

        // ... more methods
    };

    $(document).ready(function() {
        CommandPalette.init();
    });

})(jQuery);
```

---

## 🎨 CSS Styling

### Key Styles
```css
/* Command Palette Modal */
#sm-command-palette {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 100000;
    display: none;
    align-items: flex-start;
    justify-content: center;
    padding-top: 10vh;
}

.sm-cp-container {
    background: #fff;
    width: 90%;
    max-width: 640px;
    border-radius: 12px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Search Input */
.sm-cp-search {
    width: 100%;
    padding: 20px 24px;
    font-size: 16px;
    border: none;
    border-bottom: 1px solid #e0e0e0;
    outline: none;
}

/* Actions List */
.sm-cp-actions-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 8px;
}

.sm-cp-action-item {
    padding: 12px 16px;
    cursor: pointer;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: background 0.15s;
}

.sm-cp-action-item:hover,
.sm-cp-action-item.selected {
    background: #f5f5f5;
}

.sm-cp-action-icon {
    font-size: 20px;
    width: 24px;
    text-align: center;
}

.sm-cp-action-title {
    flex: 1;
    font-size: 14px;
    color: #333;
}

.sm-cp-action-shortcut {
    font-size: 12px;
    color: #999;
    font-family: monospace;
}

/* Category Headers */
.sm-cp-category {
    padding: 12px 16px 8px;
    font-size: 11px;
    font-weight: 600;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Dark Mode */
@media (prefers-color-scheme: dark) {
    .sm-cp-container {
        background: #1e1e1e;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8);
    }

    .sm-cp-search {
        background: #1e1e1e;
        color: #e0e0e0;
        border-bottom-color: #333;
    }

    .sm-cp-action-item:hover,
    .sm-cp-action-item.selected {
        background: #2a2a2a;
    }

    .sm-cp-action-title {
        color: #e0e0e0;
    }
}
```

---

## 🔧 Implementation Phases

### Phase 1: MVP (5-6 hours)
**Goal:** Basic working command palette

- [ ] Create core class structure
- [ ] Register default actions
- [ ] Build modal HTML/CSS
- [ ] Implement keyboard shortcut (Ctrl+K)
- [ ] Add visual trigger button
- [ ] Basic search functionality
- [ ] Execute actions on selection
- [ ] Role-based filtering

**Deliverables:**
- Working command palette
- 15-20 default actions
- Search and execute
- Role filtering

### Phase 2: Customization (3-4 hours)
**Goal:** User preferences and customization

- [ ] Create database tables
- [ ] Build customization panel UI
- [ ] Save/load user preferences
- [ ] Enable/disable actions
- [ ] Pin favorites
- [ ] Role-based presets

**Deliverables:**
- Customization panel
- User preferences saved
- Favorites system
- Role presets

### Phase 3: Enhanced UX (2-3 hours)
**Goal:** Polish and advanced features

- [ ] Recent actions tracking
- [ ] Recent actions display (top of list)
- [ ] Keyboard navigation polish
- [ ] Category grouping
- [ ] Icons/emojis
- [ ] Loading states
- [ ] Error handling

**Deliverables:**
- Recent actions
- Better UX
- Category organization
- Professional polish

### Phase 4: Advanced Features (Optional - 4-5 hours)
**Goal:** Power user features

- [ ] Fuzzy search algorithm
- [ ] Keyboard shortcuts for actions
- [ ] Action aliases/synonyms
- [ ] Quick stats in actions (e.g., "5 overdue")
- [ ] Action history analytics
- [ ] Export/import preferences
- [ ] Admin dashboard for action management

---

## 📊 Success Metrics

### User Adoption
- % of users who open command palette in first week
- Average uses per user per day
- Keyboard shortcut vs button usage ratio

### Efficiency
- Time to complete common actions (before/after)
- Reduction in clicks to reach actions
- Most-used actions (optimize based on data)

### Customization
- % of users who customize actions
- Average number of favorites per user
- Most commonly disabled actions

---

## 🧪 Testing Checklist

### Functionality
- [ ] Ctrl+K opens palette (Windows/Linux)
- [ ] Cmd+K opens palette (Mac)
- [ ] Visual button opens palette
- [ ] Esc closes palette
- [ ] Click outside closes palette
- [ ] Search filters actions
- [ ] Arrow keys navigate
- [ ] Enter executes action
- [ ] Actions respect user capabilities
- [ ] Role filtering works correctly

### Customization
- [ ] Can enable/disable actions
- [ ] Can pin/unpin favorites
- [ ] Preferences save correctly
- [ ] Preferences load on next visit
- [ ] Role presets apply correctly

### UI/UX
- [ ] Modal centers correctly
- [ ] Responsive on all screen sizes
- [ ] Works on mobile/tablet
- [ ] Dark mode styling correct
- [ ] Animations smooth
- [ ] No UI glitches

### Performance
- [ ] Loads quickly (<100ms)
- [ ] Search is fast (<50ms)
- [ ] No lag with 100+ actions
- [ ] Database queries optimized

---

## 🌐 Internationalization

### Translatable Strings
```php
// Main strings
__( 'Quick Actions', 'CTADZ-school-management' )
__( 'Search actions...', 'CTADZ-school-management' )
__( 'Favorites', 'CTADZ-school-management' )
__( 'Recent', 'CTADZ-school-management' )
__( 'Customize Quick Actions', 'CTADZ-school-management' )

// Help text
__( 'Use ↑↓ to navigate, ↵ to select, Esc to close', 'CTADZ-school-management' )
__( 'Press Ctrl+K to open Quick Actions', 'CTADZ-school-management' )

// Categories
__( 'Academic', 'CTADZ-school-management' )
__( 'Financial', 'CTADZ-school-management' )
__( 'Reports', 'CTADZ-school-management' )
__( 'Settings', 'CTADZ-school-management' )
```

---

## 🔐 Security Considerations

### Capability Checks
- Every action must have `capability` defined
- Check capabilities before executing
- Filter actions based on user role
- Validate AJAX requests with nonces

### Data Validation
- Sanitize search input
- Validate action IDs
- Escape output
- Prevent SQL injection in custom queries

### AJAX Security
```php
// Verify nonce
check_ajax_referer( 'sm_command_palette_nonce', 'nonce' );

// Check capability
if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( 'Insufficient permissions' );
}

// Sanitize input
$action_id = sanitize_text_field( $_POST['action_id'] );
```

---

## 📱 Mobile Considerations

### Touch Optimization
- Larger touch targets (44px minimum)
- Swipe to close gesture
- Virtual keyboard handling
- Prevent body scroll when open

### Responsive Design
```css
@media (max-width: 768px) {
    .sm-cp-container {
        width: 100%;
        height: 100%;
        max-width: none;
        border-radius: 0;
        padding-top: env(safe-area-inset-top);
    }

    .sm-cp-actions-list {
        max-height: calc(100vh - 120px);
    }
}
```

---

## 🔄 Future Enhancements

### Potential Additions
1. **Global Search Integration**
   - Search data (students, courses) alongside actions
   - Unified search experience

2. **Custom Actions**
   - Users create custom URL shortcuts
   - Bookmarklet-style functionality

3. **Action Chains**
   - Execute multiple actions in sequence
   - "Add Student → Enroll in Course → Create Payment"

4. **Voice Commands**
   - "Alexa, open Quick Actions"
   - Voice search for actions

5. **AI Suggestions**
   - Smart action recommendations
   - Based on user behavior and time of day

6. **Team Sharing**
   - Share favorite actions with team
   - Organization-wide action templates

---

## 📚 References & Inspiration

### Industry Examples
- **GitHub:** https://github.com (Press Cmd+K)
- **Linear:** https://linear.app (Press Cmd+K)
- **VS Code:** Command Palette (Ctrl+Shift+P)
- **Notion:** Quick Find (Cmd+K)
- **Slack:** Command Palette (Cmd+K)

### Libraries
- **kbar:** https://kbar.vercel.app/ (React)
- **ninja-keys:** https://github.com/ssleptsov/ninja-keys (Web Component)
- **cmdk:** https://cmdk.paco.me/ (React)

### Articles
- "Designing Command Palettes" by Linear
- "The Rise of the Command Palette" by UX Collective
- "Keyboard-First UX" by Superhuman

---

## 💰 Business Value

### Time Savings
- **Before:** 3-5 clicks to reach common actions
- **After:** 2 keystrokes (Ctrl+K, Enter)
- **Estimated savings:** 2-3 minutes per user per day
- **ROI:** High for frequent users

### User Satisfaction
- Modern, professional UX
- Matches expectations from other tools
- Reduces friction in workflow
- Power users love keyboard shortcuts

### Competitive Advantage
- Few school management systems have this
- Shows technical sophistication
- Improves user retention

---

## ✅ Definition of Done

Feature is complete when:
- [ ] Command palette opens with Ctrl+K
- [ ] Visual button works
- [ ] Search filters actions correctly
- [ ] All default actions registered
- [ ] Role-based filtering works
- [ ] User preferences save/load
- [ ] Customization panel functional
- [ ] Recent actions tracked
- [ ] Favorites system works
- [ ] Fully responsive
- [ ] Dark mode support
- [ ] All strings translatable
- [ ] Security audit passed
- [ ] Performance benchmarks met
- [ ] Documentation complete
- [ ] User testing successful

---

**Status:** 📋 Ready for implementation when scheduled
**Next Steps:** Schedule development sprint, assign resources
**Contact:** Development team for questions

---

*Document created: 2025-12-18*
*Last updated: 2025-12-18*
*Version: 1.0*
