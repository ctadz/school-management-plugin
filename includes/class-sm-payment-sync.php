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