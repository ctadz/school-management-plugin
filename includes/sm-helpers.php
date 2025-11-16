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
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'school-management' ) ] );
    }
    
    $course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
    
    if ( ! $course_id ) {
        wp_send_json_error( [ 'message' => __( 'Invalid course ID.', 'school-management' ) ] );
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
        wp_send_json_error( [ 'message' => __( 'Course not found.', 'school-management' ) ] );
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
                    __( 'Full Payment - Pay %s upfront for entire course', 'school-management' ),
                    number_format( floatval( $course->total_price ), 2 )
                )
            ];
            break;
            
        case 'monthly_installments':
            $available_plans = [ 'monthly', 'quarterly' ];
            $plan_descriptions = [
                'monthly' => sprintf(
                    __( 'Monthly Installments - %s/month for %d months (Total: %s)', 'school-management' ),
                    number_format( floatval( $course->price_per_month ), 2 ),
                    intval( $course->total_months ),
                    number_format( floatval( $course->total_price ), 2 )
                ),
                'quarterly' => sprintf(
                    __( 'Quarterly Payments - Every 3 months', 'school-management' )
                )
            ];
            break;
            
        case 'monthly_subscription':
            $available_plans = [ 'monthly' ];
            $is_subscription = true;
            $plan_descriptions = [
                'monthly' => sprintf(
                    __( 'Monthly Subscription - %s/month (Flexible, cancel anytime)', 'school-management' ),
                    number_format( floatval( $course->price_per_month ), 2 )
                )
            ];
            break;
            
        default:
            // If payment_model is not set or unknown, allow all options
            $available_plans = [ 'monthly', 'quarterly', 'full' ];
            $plan_descriptions = [
                'monthly' => __( 'Monthly Payments', 'school-management' ),
                'quarterly' => __( 'Quarterly Payments (Every 3 months)', 'school-management' ),
                'full' => __( 'Full Payment (One-time)', 'school-management' )
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
        'full_payment' => __( 'Full Payment', 'school-management' ),
        'monthly_installments' => __( 'Monthly Installments', 'school-management' ),
        'monthly_subscription' => __( 'Monthly Subscription', 'school-management' )
    ];
    
    return $labels[ $payment_model ] ?? __( 'Monthly Installments', 'school-management' );
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
        return new WP_Error( 'invalid_course', __( 'Course not found.', 'school-management' ) );
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
                __( 'The selected payment plan "%s" is not available for this course. This course requires: %s', 'school-management' ),
                $payment_plan,
                sm_get_payment_model_label( $payment_model )
            )
        );
    }
    
    return true;
}
