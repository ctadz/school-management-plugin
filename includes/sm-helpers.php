<?php
/**
 * Helper Functions for School Management
 * 
 * @package SchoolManagement
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX Handler: Get Course Payment Information
 * Returns course payment model and pricing details for enrollment form
 */
function sm_ajax_get_course_payment_info() {
    // Verify nonce for security
    check_ajax_referer( 'sm_enrollment_nonce', 'nonce' );
    
    // Check user permissions
    if ( ! current_user_can( 'manage_enrollments' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'CTADZ-school-management' ) ] );
    }
    
    $course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
    
    if ( ! $course_id ) {
        wp_send_json_error( [ 'message' => __( 'Invalid course ID.', 'CTADZ-school-management' ) ] );
    }
    
    global $wpdb;
    $courses_table = $wpdb->prefix . 'sm_courses';
    
    // Get course payment information
    $course = $wpdb->get_row( $wpdb->prepare(
        "SELECT payment_model, total_months, price_per_month, total_price, name 
         FROM $courses_table 
         WHERE id = %d",
        $course_id
    ) );
    
    if ( ! $course ) {
        wp_send_json_error( [ 'message' => __( 'Course not found.', 'CTADZ-school-management' ) ] );
    }
    
    // Determine available payment plans based on course payment model
    $payment_model = $course->payment_model ?? 'monthly_installments';
    $available_plans = [];
    $plan_descriptions = [];
    $is_subscription = false;
    
    switch ( $payment_model ) {
        case 'full_payment':
            $available_plans = [ 'full' ];
            $plan_descriptions = [
                'full' => sprintf(
                    __( 'Full Payment - Pay %s upfront for entire course', 'CTADZ-school-management' ),
                    number_format( floatval( $course->total_price ), 2 )
                )
            ];
            break;
            
        case 'monthly_installments':
            $available_plans = [ 'monthly', 'quarterly' ];
            $plan_descriptions = [
                'monthly' => sprintf(
                    __( 'Monthly Installments - %s/month for %d months (Total: %s)', 'CTADZ-school-management' ),
                    number_format( floatval( $course->price_per_month ), 2 ),
                    intval( $course->total_months ),
                    number_format( floatval( $course->total_price ), 2 )
                ),
                'quarterly' => sprintf(
                    __( 'Quarterly Payments - Every 3 months', 'CTADZ-school-management' )
                )
            ];
            break;
            
        case 'monthly_subscription':
            $available_plans = [ 'monthly' ];
            $is_subscription = true;
            $plan_descriptions = [
                'monthly' => sprintf(
                    __( 'Monthly Subscription - %s/month (Flexible, cancel anytime)', 'CTADZ-school-management' ),
                    number_format( floatval( $course->price_per_month ), 2 )
                )
            ];
            break;
            
        default:
            // If payment_model is not set or unknown, allow all options
            $available_plans = [ 'monthly', 'quarterly', 'full' ];
            $plan_descriptions = [
                'monthly' => __( 'Monthly Payments', 'CTADZ-school-management' ),
                'quarterly' => __( 'Quarterly Payments (Every 3 months)', 'CTADZ-school-management' ),
                'full' => __( 'Full Payment (One-time)', 'CTADZ-school-management' )
            ];
            break;
    }
    
    // Return course payment information
    wp_send_json_success( [
        'payment_model' => $payment_model,
        'available_plans' => $available_plans,
        'plan_descriptions' => $plan_descriptions,
        'is_subscription' => $is_subscription,
        'course_name' => $course->name,
        'price_per_month' => number_format( floatval( $course->price_per_month ), 2 ),
        'total_months' => intval( $course->total_months ),
        'total_price' => number_format( floatval( $course->total_price ), 2 ),
        'payment_model_label' => sm_get_payment_model_label( $payment_model )
    ] );
}
add_action( 'wp_ajax_sm_get_course_payment_info', 'sm_ajax_get_course_payment_info' );

/**
 * Get human-readable label for payment model
 * 
 * @param string $payment_model The payment model key
 * @return string Human-readable label
 */
function sm_get_payment_model_label( $payment_model ) {
    $labels = [
        'full_payment' => __( 'Full Payment', 'CTADZ-school-management' ),
        'monthly_installments' => __( 'Monthly Installments', 'CTADZ-school-management' ),
        'monthly_subscription' => __( 'Monthly Subscription', 'CTADZ-school-management' )
    ];
    
    return $labels[ $payment_model ] ?? __( 'Monthly Installments', 'CTADZ-school-management' );
}

/**
 * Validate enrollment payment plan against course payment model
 * 
 * @param int $course_id The course ID
 * @param string $payment_plan The selected payment plan
 * @return bool|WP_Error True if valid, WP_Error if invalid
 */
function sm_validate_enrollment_payment_plan( $course_id, $payment_plan ) {
    global $wpdb;
    $courses_table = $wpdb->prefix . 'sm_courses';
    
    $course = $wpdb->get_row( $wpdb->prepare(
        "SELECT payment_model FROM $courses_table WHERE id = %d",
        $course_id
    ) );
    
    if ( ! $course ) {
        return new WP_Error( 'invalid_course', __( 'Course not found.', 'CTADZ-school-management' ) );
    }
    
    $payment_model = $course->payment_model ?? 'monthly_installments';
    
    // Define valid combinations
    $valid_combinations = [
        'full_payment' => [ 'full' ],
        'monthly_installments' => [ 'monthly', 'quarterly' ],
        'monthly_subscription' => [ 'monthly' ]
    ];
    
    $allowed_plans = $valid_combinations[ $payment_model ] ?? [ 'monthly', 'quarterly', 'full' ];
    
    if ( ! in_array( $payment_plan, $allowed_plans ) ) {
        return new WP_Error( 
            'invalid_payment_plan', 
            sprintf(
                __( 'The selected payment plan "%s" is not available for this course. This course requires: %s', 'CTADZ-school-management' ),
                $payment_plan,
                sm_get_payment_model_label( $payment_model )
            )
        );
    }
    
    return true;
}

/**
 * AJAX Handler: Refresh Dropdown Options
 * Returns updated options for dependent dropdowns without page reload
 *
 * @since 0.6.0
 */
function sm_ajax_refresh_dropdown() {
    // Verify nonce for security
    check_ajax_referer( 'sm_dropdown_refresh', 'nonce' );

    // Check user permissions (any school management capability)
    if ( ! current_user_can( 'manage_students' ) &&
         ! current_user_can( 'manage_courses' ) &&
         ! current_user_can( 'manage_teachers' ) &&
         ! current_user_can( 'manage_enrollments' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'CTADZ-school-management' ) ] );
    }

    $entity = isset( $_POST['entity'] ) ? sanitize_text_field( $_POST['entity'] ) : '';

    if ( empty( $entity ) ) {
        wp_send_json_error( [ 'message' => __( 'No entity specified.', 'CTADZ-school-management' ) ] );
    }

    global $wpdb;
    $options = [];

    switch ( $entity ) {
        case 'levels':
            // Get all active levels
            $levels = $wpdb->get_results(
                "SELECT id, name FROM {$wpdb->prefix}sm_levels WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
            );
            foreach ( $levels as $level ) {
                $options[] = [
                    'id'    => $level->id,
                    'label' => $level->name,
                ];
            }
            break;

        case 'teachers':
            // Get all active teachers
            $teachers = $wpdb->get_results(
                "SELECT id, first_name, last_name FROM {$wpdb->prefix}sm_teachers WHERE is_active = 1 ORDER BY last_name ASC, first_name ASC"
            );
            foreach ( $teachers as $teacher ) {
                $options[] = [
                    'id'    => $teacher->id,
                    'label' => $teacher->first_name . ' ' . $teacher->last_name,
                ];
            }
            break;

        case 'classrooms':
            // Get all active classrooms
            $classrooms = $wpdb->get_results(
                "SELECT id, name, location FROM {$wpdb->prefix}sm_classrooms WHERE is_active = 1 ORDER BY name ASC"
            );
            foreach ( $classrooms as $classroom ) {
                $label = $classroom->name;
                if ( $classroom->location ) {
                    $label .= ' - ' . $classroom->location;
                }
                $options[] = [
                    'id'    => $classroom->id,
                    'label' => $label,
                ];
            }
            break;

        case 'payment_terms':
            // Get all active payment terms
            $terms = $wpdb->get_results(
                "SELECT id, name FROM {$wpdb->prefix}sm_payment_terms WHERE is_active = 1 ORDER BY sort_order ASC, name ASC"
            );
            foreach ( $terms as $term ) {
                $options[] = [
                    'id'    => $term->id,
                    'label' => $term->name,
                ];
            }
            break;

        case 'students':
            // Get all students
            $students = $wpdb->get_results(
                "SELECT id, name FROM {$wpdb->prefix}sm_students ORDER BY name ASC"
            );
            foreach ( $students as $student ) {
                $options[] = [
                    'id'    => $student->id,
                    'label' => $student->name,
                ];
            }
            break;

        case 'courses':
            // Get all active courses with level info
            $courses = $wpdb->get_results(
                "SELECT c.id, c.name, c.max_students, l.name as level_name
                 FROM {$wpdb->prefix}sm_courses c
                 LEFT JOIN {$wpdb->prefix}sm_levels l ON c.level_id = l.id
                 WHERE c.is_active = 1
                 ORDER BY c.name ASC"
            );
            foreach ( $courses as $course ) {
                // Get current enrollment count for capacity display
                $current_count = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}sm_enrollments WHERE course_id = %d AND status IN ('active', 'completed')",
                    $course->id
                ) );

                $label = $course->name;
                if ( $course->level_name ) {
                    $label .= ' - ' . $course->level_name;
                }
                if ( $course->max_students > 0 ) {
                    $spots_left = $course->max_students - $current_count;
                    $label .= sprintf( ' (%d spots left)', $spots_left );
                }

                $options[] = [
                    'id'    => $course->id,
                    'label' => $label,
                ];
            }
            break;

        default:
            wp_send_json_error( [ 'message' => __( 'Unknown entity type.', 'CTADZ-school-management' ) ] );
    }

    wp_send_json_success( [ 'options' => $options ] );
}
add_action( 'wp_ajax_sm_refresh_dropdown', 'sm_ajax_refresh_dropdown' );
