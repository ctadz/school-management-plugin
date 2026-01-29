# Vacation-Aware Payment Scheduling (Subscriptions Only)

## Overview
Implemented automatic vacation/holiday period consideration when calculating next payment dates for **subscription-based enrollments ONLY**. The system now automatically skips vacation periods when scheduling subscription payments, ensuring payment dates account for actual class sessions.

## Quick Summary

| Payment Type | Vacation Aware? | Reason |
|-------------|----------------|---------|
| **Subscription** | ✅ YES | Session-based - students pay for actual classes |
| Monthly Installments | ❌ NO | Financial payment plan - just dividing total cost |
| Quarterly | ❌ NO | Financial payment plan - just dividing total cost |
| Full Payment | ❌ NO | Single payment - no schedule to adjust |

## Business Logic

### Why Only Subscriptions?

**Subscriptions (Session-Based Payment):**
- Students pay for actual classes/sessions during the month
- If there's a 2-week vacation, they only get 2 weeks of classes
- Next payment should be extended to give them a full month's worth of sessions
- ✅ **Vacations ARE considered**

**Monthly Installments (Financial Payment Plan):**
- Students pay a fixed amount per month to split the total course cost
- Example: $1000 course ÷ 10 months = $100/month
- Vacation doesn't change the total cost or payment schedule
- ❌ **Vacations are NOT considered** - it's just dividing the total price

**Quarterly Payments (Financial Payment Plan):**
- Same as monthly installments, just paid every 3 months
- ❌ **Vacations are NOT considered**

**Full Payment:**
- Single upfront payment
- ❌ **No payment schedule to adjust**

## Changes Made

### 1. Calendar Plugin Database Updates

**File:** `school-management-calendar/includes/class-smc-activator.php`

- Added `event_end_date` field to `smc_events` table for multi-day vacation periods
- Created database migration (version 1.2.0) to add the field to existing installations
- Updated table creation to include the new field with index

### 2. Helper Functions for Vacation Calculation

**File:** `school-management-calendar/includes/smc-helpers.php`

Added two new functions:

#### `smc_get_vacation_periods( $start_date, $end_date )`
- Retrieves all vacation/holiday events between two dates
- Supports event types: 'holiday', 'school_closure'
- Returns array of periods with start, end, and title

#### `smc_calculate_next_payment_date( $from_date, $interval )`
- Calculates next payment date while skipping vacation periods
- Takes a starting date and interval (e.g., '+1 month')
- Automatically pushes payment dates forward by vacation duration
- Logs adjustments for debugging

### 3. Payment Schedule Creation (Enrollments)

**File:** `school-management/includes/class-sm-enrollments-page.php`

Updated `create_payment_schedule()` method:
- For **subscriptions**: Creates only first payment (vacation-aware on subsequent payments)
- For **monthly installments**: Simple month addition (NO vacation consideration - it's a payment plan)
- For **quarterly payments**: Simple 3-month addition (NO vacation consideration - it's a payment plan)
- For **full payment**: Single payment at enrollment (no schedule to adjust)

### 4. Subscription Payment Auto-Generation

**File:** `school-management/includes/class-sm-payments-page.php`

Updated `create_next_subscription_payment()` method:
- Checks if calendar plugin is active before calculation
- Uses vacation-aware date calculation for next payment
- Falls back to simple month addition if calendar not available
- Logs vacation adjustments for tracking

### 5. Events Form UI Updates

**File:** `school-management-calendar/includes/class-smc-events-page.php`

- Added `event_end_date` field to event creation/edit form
- Added validation for end date (must be after start date)
- Updated form data handling in three places:
  - POST data submission
  - Loading existing event
  - Default values for new event
- Added user-friendly description explaining multi-day vacation usage

## How It Works

### For Subscription Enrollments Only:
1. When a subscription payment is recorded and marked as paid
2. The system calculates next month's payment date
3. It checks for any vacation periods overlapping with that date
4. If found, the payment date is shifted by the vacation duration
5. A new payment schedule entry is created with the adjusted date

### Creating Vacation Events:
1. Go to School Management → Calendar → Events
2. Create a new event with type "Holiday" or "School Closure"
3. Set the start date (Event Date)
4. Set the end date (Event End Date) for multi-day vacations
5. Leave end date empty for single-day holidays
6. The system will automatically use these events in payment calculations

## Example Scenarios

### Scenario 1: Monthly Subscription (Session-Based)
- Student enrolled: Jan 1, 2026
- Payment #1 due: Jan 1, 2026
- Payment #1 paid: Jan 10, 2026
- Vacation: Feb 1-28, 2026 (Winter Break - 28 days)
- **Result:** Payment #2 due: Mar 1, 2026 (automatically pushed by 28 days because student only got partial classes)

### Scenario 2: Multi-Day Holiday (Subscription)
- Payment due: Dec 20, 2025
- Holiday: Dec 22, 2025 - Jan 5, 2026 (15 days)
- **Result:** Next payment due: Jan 20, 2026 (pushed forward by 15 days to account for missed sessions)

### Scenario 3: Regular Monthly Installments (NOT Affected)
- 10-month course, $1000 total = $100/month
- Enrolled: Sept 1
- Winter break: Dec 20 - Jan 10
- **Result:** Payments remain on schedule:
  - Sept 1: $100
  - Oct 1: $100
  - Nov 1: $100
  - Dec 1: $100 (vacation doesn't affect payment plan)
  - Jan 1: $100
  - ... (continues normally regardless of vacations)

## Technical Details

### Vacation Detection Query:
```sql
SELECT event_date, event_end_date, title
FROM smc_events
WHERE event_type IN ('holiday', 'school_closure')
AND (event overlaps with date range)
ORDER BY event_date ASC
```

### Date Calculation Logic:
1. Calculate initial next date (e.g., current + 1 month)
2. Get all vacations within next 6 months
3. Check if calculated date falls within any vacation
4. If yes, add vacation duration to the date
5. Return adjusted date

### Backward Compatibility:
- If calendar plugin is not installed: Simple date addition (no vacation awareness)
- If calendar plugin is installed but no vacations defined: Normal date calculation
- Existing enrollments continue with their current schedules

## Database Schema

### smc_events Table (Updated):
```sql
event_date DATE NOT NULL           -- Start date of event
event_end_date DATE NULL            -- End date for multi-day events (NEW)
event_type VARCHAR(30)              -- 'holiday', 'school_closure', etc.
```

## Testing Checklist

### Test Subscription (Should Skip Vacations):
- [ ] Create a multi-day vacation event with start and end dates
- [ ] Enroll a student in a subscription course
- [ ] Record the first payment
- [ ] Verify next payment date skips the vacation period
- [ ] Test with no vacations (should work normally - just +1 month)
- [ ] Test with calendar plugin deactivated (should fallback to simple +1 month)

### Test Monthly Installments (Should NOT Skip Vacations):
- [ ] Create enrollment with regular monthly installments
- [ ] Verify payment dates are simple month additions (Jan 1, Feb 1, Mar 1, etc.)
- [ ] Verify vacation periods do NOT affect the payment schedule
- [ ] Confirm payments remain on the 1st of each month regardless of holidays

### Test Quarterly (Should NOT Skip Vacations):
- [ ] Create enrollment with quarterly payments
- [ ] Verify payment dates are simple 3-month additions
- [ ] Verify vacation periods do NOT affect the payment schedule

## Future Enhancements

Potential improvements:
- Admin setting to control vacation behavior (skip vs. postpone)
- Option to exclude certain vacation types from payment calculations
- Notification to students when payment dates are adjusted
- Report showing all vacation-adjusted payments

## Version Information

- **Calendar Plugin DB Version:** 1.2.0
- **School Management Plugin:** Compatible with v0.6.1+
- **Date Implemented:** January 28, 2026
