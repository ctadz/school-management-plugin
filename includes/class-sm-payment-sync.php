<?php
/**
 * Payment Status Synchronization
 * Automatically updates enrollment payment status based on actual payments
 *
 * @package SchoolManagement
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Payment_Sync {

    /**
     * Initialize hooks
     */
    public static function init() {
        // This will be called after payment operations
        add_action( 'sm_payment_updated', [ __CLASS__, 'sync_enrollment_status' ], 10, 1 );
        add_action( 'sm_payment_deleted', [ __CLASS__, 'sync_enrollment_status' ], 10, 1 );
    }

    /**
     * Calculate and update enrollment payment status
     * 
     * @param int $enrollment_id Enrollment ID
     */
    public static function sync_enrollment_status( $enrollment_id ) {
        global $wpdb;
        
        $enrollment_id = intval( $enrollment_id );
        if ( ! $enrollment_id ) {
            return;
        }

        // Get total expected amount from payment schedules
        $schedules_table = $wpdb->prefix . 'sm_payment_schedules';
        $total_expected = floatval( $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(expected_amount) FROM $schedules_table WHERE enrollment_id = %d",
            $enrollment_id
        ) ) );

        // Get total paid amount from payment schedules
        $total_paid = floatval( $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(paid_amount) FROM $schedules_table WHERE enrollment_id = %d",
            $enrollment_id
        ) ) );

        // Determine payment status
        $payment_status = 'pending';
        
        if ( $total_paid >= $total_expected && $total_expected > 0 ) {
            $payment_status = 'paid';
        } elseif ( $total_paid > 0 && $total_paid < $total_expected ) {
            $payment_status = 'partial';
        } elseif ( $total_paid == 0 ) {
            // Check if overdue
            $oldest_unpaid = $wpdb->get_var( $wpdb->prepare(
                "SELECT MIN(due_date) 
                 FROM $schedules_table 
                 WHERE enrollment_id = %d 
                 AND paid_amount < expected_amount 
                 AND due_date < CURDATE()",
                $enrollment_id
            ) );
            
            $payment_status = $oldest_unpaid ? 'overdue' : 'pending';
        }

        // Update enrollment payment status
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $wpdb->update(
            $enrollments_table,
            [ 'payment_status' => $payment_status ],
            [ 'id' => $enrollment_id ],
            [ '%s' ],
            [ '%d' ]
        );

        // Log the update for debugging (optional)
        error_log( sprintf(
            'SM Payment Sync: Enrollment #%d - Expected: %s, Paid: %s, Status: %s',
            $enrollment_id,
            $total_expected,
            $total_paid,
            $payment_status
        ) );
    }

    /**
     * Recalculate due dates for all pending/partial subscription payments
     * Fixes legacy records created before the vacation-aware date calculation fix (v0.6.4)
     *
     * @param bool $dry_run If true, return changes without applying them
     * @return array { changes[], total_checked, total_changed, updated_count, error? }
     */
    public static function recalculate_subscription_due_dates( $dry_run = false ) {
        global $wpdb;

        if ( ! defined( 'SMC_VERSION' ) || ! function_exists( 'smc_calculate_subscription_payment_date' ) ) {
            return [
                'error'         => 'calendar_inactive',
                'changes'       => [],
                'total_checked' => 0,
                'total_changed' => 0,
                'updated_count' => 0,
            ];
        }

        $schedules_table   = $wpdb->prefix . 'sm_payment_schedules';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $courses_table     = $wpdb->prefix . 'sm_courses';
        $students_table    = $wpdb->prefix . 'sm_students';

        // Get all pending/partial subscription payments with their enrollment context
        // Also fetch the previous installment's due_date for vacation-adjustment detection
        $payments = $wpdb->get_results(
            "SELECT ps.id, ps.enrollment_id, ps.installment_number, ps.due_date, ps.status,
                    e.start_date, e.student_id, e.course_id,
                    c.name AS course_name,
                    s.name AS student_name,
                    prev_ps.due_date AS previous_due_date
             FROM {$schedules_table} ps
             JOIN {$enrollments_table} e  ON ps.enrollment_id = e.id
             JOIN {$courses_table} c      ON e.course_id = c.id
             JOIN {$students_table} s     ON e.student_id = s.id
             LEFT JOIN {$schedules_table} prev_ps
                    ON prev_ps.enrollment_id = ps.enrollment_id
                   AND prev_ps.installment_number = ps.installment_number - 1
             WHERE c.payment_model = 'monthly_subscription'
               AND ps.status IN ('pending', 'partial')
             ORDER BY ps.enrollment_id ASC, ps.installment_number ASC"
        );

        $changes       = [];
        $updated_count = 0;

        foreach ( $payments as $payment ) {
            $correct_date = smc_calculate_subscription_payment_date(
                $payment->start_date,
                intval( $payment->installment_number ),
                $payment->previous_due_date
            );

            if ( $correct_date !== $payment->due_date ) {
                $changes[] = [
                    'id'           => intval( $payment->id ),
                    'student_name' => $payment->student_name,
                    'course_name'  => $payment->course_name,
                    'installment'  => intval( $payment->installment_number ),
                    'old_date'     => $payment->due_date,
                    'new_date'     => $correct_date,
                    'status'       => $payment->status,
                ];

                if ( ! $dry_run ) {
                    $wpdb->update(
                        $schedules_table,
                        [ 'due_date' => $correct_date ],
                        [ 'id'       => intval( $payment->id ) ],
                        [ '%s' ],
                        [ '%d' ]
                    );
                    $updated_count++;
                }
            }
        }

        return [
            'changes'       => $changes,
            'total_checked' => count( $payments ),
            'total_changed' => count( $changes ),
            'updated_count' => $dry_run ? 0 : $updated_count,
        ];
    }

    /**
     * Sync all enrollments (useful for initial setup or bulk updates)
     */
    public static function sync_all_enrollments() {
        global $wpdb;
        
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $enrollment_ids = $wpdb->get_col( "SELECT id FROM $enrollments_table" );
        
        foreach ( $enrollment_ids as $enrollment_id ) {
            self::sync_enrollment_status( $enrollment_id );
        }
        
        return count( $enrollment_ids );
    }
}

// Initialize
SM_Payment_Sync::init();