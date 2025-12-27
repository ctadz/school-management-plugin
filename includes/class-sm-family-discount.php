<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Family Discount Calculator
 * Handles automatic family discount calculation based on parent phone number
 */
class SM_Family_Discount {

    /**
     * Normalize phone number for comparison
     * Removes all non-digit characters to allow flexible matching
     *
     * Examples:
     * - "07-12345678" becomes "0712345678"
     * - "07 12 34 56 78" becomes "0712345678"
     * - "+213 7 123 456 78" becomes "2137123456 78"
     *
     * @param string $phone Phone number to normalize
     * @return string Normalized phone number (digits only)
     */
    public static function normalize_phone( $phone ) {
        if ( empty( $phone ) ) {
            return '';
        }
        // Remove all non-digit characters
        return preg_replace( '/[^0-9]/', '', $phone );
    }

    /**
     * Check if family discounts are enabled
     *
     * @return bool
     */
    public static function is_enabled() {
        $settings = get_option( 'sm_school_settings', [] );
        return isset( $settings['family_discount_enabled'] ) && $settings['family_discount_enabled'] === 'yes';
    }

    /**
     * Get configured discount tiers
     *
     * @return array Array of tiers with 'students' and 'discount' keys
     */
    public static function get_discount_tiers() {
        $settings = get_option( 'sm_school_settings', [] );
        $tiers = $settings['family_discount_tiers'] ?? [
            [ 'students' => 2, 'discount' => 5 ],
            [ 'students' => 3, 'discount' => 10 ]
        ];

        // Sort tiers by student count descending to match highest tier first
        usort( $tiers, function( $a, $b ) {
            return $b['students'] - $a['students'];
        } );

        return $tiers;
    }

    /**
     * Count family members (students with same parent phone)
     * Uses normalized comparison to match different formats
     *
     * @param string $parent_phone Parent phone number
     * @return int Number of students in the family
     */
    public static function count_family_members( $parent_phone ) {
        global $wpdb;

        if ( empty( $parent_phone ) ) {
            return 0;
        }

        $students_table = $wpdb->prefix . 'sm_students';
        $normalized = self::normalize_phone( $parent_phone );

        if ( empty( $normalized ) ) {
            return 0;
        }

        // Use REGEXP to match phone numbers with the same digits (ignoring formatting)
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*)
            FROM $students_table
            WHERE REGEXP_REPLACE(parent_phone, '[^0-9]', '') = %s",
            $normalized
        ) );

        return intval( $count );
    }

    /**
     * Get all family members
     * Uses normalized comparison to match different formats
     *
     * @param string $parent_phone Parent phone number
     * @return array Array of student objects
     */
    public static function get_family_members( $parent_phone ) {
        global $wpdb;

        if ( empty( $parent_phone ) ) {
            return [];
        }

        $students_table = $wpdb->prefix . 'sm_students';
        $normalized = self::normalize_phone( $parent_phone );

        if ( empty( $normalized ) ) {
            return [];
        }

        // Use REGEXP to match phone numbers with the same digits (ignoring formatting)
        $students = $wpdb->get_results( $wpdb->prepare(
            "SELECT *
            FROM $students_table
            WHERE REGEXP_REPLACE(parent_phone, '[^0-9]', '') = %s
            ORDER BY name ASC",
            $normalized
        ) );

        return $students ?: [];
    }

    /**
     * Count active enrollments for family members
     * Uses normalized comparison to match different formats
     *
     * @param string $parent_phone Parent phone number
     * @return int Number of active enrollments across family
     */
    public static function count_family_enrollments( $parent_phone ) {
        global $wpdb;

        if ( empty( $parent_phone ) ) {
            return 0;
        }

        $students_table = $wpdb->prefix . 'sm_students';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $normalized = self::normalize_phone( $parent_phone );

        if ( empty( $normalized ) ) {
            return 0;
        }

        // Use REGEXP to match phone numbers with the same digits (ignoring formatting)
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT e.student_id)
            FROM $enrollments_table e
            INNER JOIN $students_table s ON e.student_id = s.id
            WHERE REGEXP_REPLACE(s.parent_phone, '[^0-9]', '') = %s
            AND e.status = 'active'",
            $normalized
        ) );

        return intval( $count );
    }

    /**
     * Calculate discount for a student based on family size
     *
     * @param int $student_id Student ID
     * @return array Array with 'percentage', 'reason', and 'family_count' keys
     */
    public static function calculate_discount_for_student( $student_id ) {
        global $wpdb;

        // Default: no discount
        $result = [
            'percentage'   => 0,
            'reason'       => null,
            'family_count' => 1
        ];

        // Check if feature is enabled
        if ( ! self::is_enabled() ) {
            return $result;
        }

        $students_table = $wpdb->prefix . 'sm_students';

        // Get student's parent phone
        $student = $wpdb->get_row( $wpdb->prepare(
            "SELECT parent_phone, parent_name FROM $students_table WHERE id = %d",
            $student_id
        ) );

        if ( ! $student || empty( $student->parent_phone ) ) {
            return $result;
        }

        // Count family members with active enrollments
        $family_count = self::count_family_enrollments( $student->parent_phone );
        $result['family_count'] = $family_count;

        // If only 1 student enrolled, no discount
        if ( $family_count < 2 ) {
            return $result;
        }

        // Get discount tiers
        $tiers = self::get_discount_tiers();

        // Find applicable tier (tiers are sorted descending)
        foreach ( $tiers as $tier ) {
            if ( $family_count >= intval( $tier['students'] ) ) {
                $result['percentage'] = floatval( $tier['discount'] );
                $result['reason'] = sprintf(
                    __( 'Family discount: %d students enrolled (%s%% discount)', 'CTADZ-school-management' ),
                    $family_count,
                    $tier['discount']
                );
                break;
            }
        }

        // Apply maximum discount cap
        $settings = get_option( 'sm_school_settings', [] );
        $max_cap = floatval( $settings['family_discount_max_cap'] ?? 10 );

        if ( $result['percentage'] > $max_cap ) {
            $original_percentage = $result['percentage'];
            $result['percentage'] = $max_cap;
            $result['reason'] = sprintf(
                __( 'Family discount: %d students enrolled (%s%% capped at %s%%)', 'CTADZ-school-management' ),
                $family_count,
                number_format( $original_percentage, 1 ),
                number_format( $max_cap, 1 )
            );
        }

        return $result;
    }

    /**
     * Calculate discount for an enrollment
     *
     * @param int $enrollment_id Enrollment ID
     * @return array Array with 'percentage', 'reason', and 'family_count' keys
     */
    public static function calculate_discount_for_enrollment( $enrollment_id ) {
        global $wpdb;

        $enrollments_table = $wpdb->prefix . 'sm_enrollments';

        // Get enrollment
        $enrollment = $wpdb->get_row( $wpdb->prepare(
            "SELECT student_id FROM $enrollments_table WHERE id = %d",
            $enrollment_id
        ) );

        if ( ! $enrollment ) {
            return [
                'percentage'   => 0,
                'reason'       => null,
                'family_count' => 1
            ];
        }

        return self::calculate_discount_for_student( $enrollment->student_id );
    }

    /**
     * Apply discount to an amount
     *
     * @param float $amount Original amount
     * @param float $discount_percentage Discount percentage (e.g., 5 for 5%)
     * @return float Discounted amount
     */
    public static function apply_discount( $amount, $discount_percentage ) {
        if ( $discount_percentage <= 0 ) {
            return $amount;
        }

        $discount_multiplier = 1 - ( $discount_percentage / 100 );
        return round( $amount * $discount_multiplier, 2 );
    }

    /**
     * Recalculate discounts for all family members
     * Called when family composition changes (new student added, parent phone updated)
     *
     * @param string $parent_phone Parent phone number
     * @return int Number of payment schedules updated
     */
    public static function recalculate_family_discounts( $parent_phone ) {
        global $wpdb;

        if ( ! self::is_enabled() || empty( $parent_phone ) ) {
            return 0;
        }

        $students_table = $wpdb->prefix . 'sm_students';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $schedules_table = $wpdb->prefix . 'sm_payment_schedules';

        // Get all students in the family
        $family_students = self::get_family_members( $parent_phone );

        if ( empty( $family_students ) ) {
            return 0;
        }

        $updated_count = 0;

        // For each student, recalculate their payment schedules
        foreach ( $family_students as $student ) {
            $discount_info = self::calculate_discount_for_student( $student->id );

            // Get all unpaid/pending payment schedules for this student (including overdue)
            $schedules = $wpdb->get_results( $wpdb->prepare(
                "SELECT ps.*
                FROM $schedules_table ps
                INNER JOIN $enrollments_table e ON ps.enrollment_id = e.id
                WHERE e.student_id = %d
                AND ps.status IN ('pending', 'unpaid')",
                $student->id
            ) );

            foreach ( $schedules as $schedule ) {
                // Calculate new amount with discount
                $base_amount = $schedule->expected_amount;

                // If there's already a discount, we need to reverse it first
                if ( $schedule->discount_percentage > 0 ) {
                    // Reverse the old discount
                    $base_amount = $base_amount / ( 1 - ( $schedule->discount_percentage / 100 ) );
                }

                // Apply new discount
                $new_amount = self::apply_discount( $base_amount, $discount_info['percentage'] );

                // Update payment schedule
                $wpdb->update(
                    $schedules_table,
                    [
                        'expected_amount'      => $new_amount,
                        'discount_percentage'  => $discount_info['percentage'],
                        'discount_reason'      => $discount_info['reason']
                    ],
                    [ 'id' => $schedule->id ]
                );

                $updated_count++;
            }
        }

        return $updated_count;
    }
}
