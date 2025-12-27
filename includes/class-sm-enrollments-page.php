<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Enrollments_Page {

    /**
     * Create enrollment fees (insurance + books)
     */
    private static function create_enrollment_fees( $enrollment_id, $post_data ) {
        global $wpdb;
        $enrollment_fees_table = $wpdb->prefix . 'sm_enrollment_fees';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        
        $enrollment = $wpdb->get_row( $wpdb->prepare(
            "SELECT student_id, enrollment_date FROM $enrollments_table WHERE id = %d",
            $enrollment_id
        ) );
        
        if ( ! $enrollment ) {
            return;
        }
        
        $insurance_fee = floatval( $post_data['insurance_fee'] ?? 0 );
        $book_fee = floatval( $post_data['book_fee'] ?? 0 );
        $enrollment_date = $enrollment->enrollment_date;
        
        // Check if this is student's first enrollment (for insurance)
        $previous_enrollments = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $enrollments_table WHERE student_id = %d AND id < %d",
            $enrollment->student_id,
            $enrollment_id
        ) );
        
        // Add insurance fee only if first enrollment AND amount > 0
        if ( $previous_enrollments == 0 && $insurance_fee > 0 ) {
            $wpdb->insert( $enrollment_fees_table, [
                'enrollment_id' => $enrollment_id,
                'fee_type' => 'insurance',
                'amount' => $insurance_fee,
                'status' => 'unpaid',
                'due_date' => $enrollment_date,
            ] );
        }
        
        // Add book fee if amount > 0
        if ( $book_fee > 0 ) {
            $wpdb->insert( $enrollment_fees_table, [
                'enrollment_id' => $enrollment_id,
                'fee_type' => 'books',
                'amount' => $book_fee,
                'status' => 'unpaid',
                'due_date' => $enrollment_date,
            ] );
        }
    }

    /**
     * Create payment schedule based on payment plan
     */
    private static function create_payment_schedule( $enrollment_id, $post_data ) {
        global $wpdb;
        $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $courses_table = $wpdb->prefix . 'sm_courses';
        
        $enrollment = $wpdb->get_row( $wpdb->prepare(
            "SELECT e.*, c.price_per_month, c.total_months
             FROM $enrollments_table e
             LEFT JOIN $courses_table c ON e.course_id = c.id
             WHERE e.id = %d",
            $enrollment_id
        ) );

        if ( ! $enrollment ) {
            return;
        }

        $payment_plan = sanitize_text_field( $post_data['payment_plan'] ?? 'monthly' );
        $price_per_month = floatval( $enrollment->price_per_month );
        $total_months = intval( $enrollment->total_months );
        $start_date = $enrollment->start_date;

        // Calculate family discount
        $discount_info = SM_Family_Discount::calculate_discount_for_student( $enrollment->student_id );
        $discount_percentage = $discount_info['percentage'];
        $discount_reason = $discount_info['reason'];
        
        $installments = [];
        
        switch ( $payment_plan ) {
            case 'monthly':
                // One payment per month
                for ( $i = 0; $i < $total_months; $i++ ) {
                    $installments[] = [
                        'number' => $i + 1,
                        'amount' => $price_per_month,
                        'due_date' => date( 'Y-m-d', strtotime( "+$i months", strtotime( $start_date ) ) ),
                    ];
                }
                break;
                
            case 'quarterly':
                // Payment every 3 months
                $num_quarters = ceil( $total_months / 3 );
                for ( $i = 0; $i < $num_quarters; $i++ ) {
                    $months_in_quarter = min( 3, $total_months - ( $i * 3 ) );
                    $installments[] = [
                        'number' => $i + 1,
                        'amount' => $price_per_month * $months_in_quarter,
                        'due_date' => date( 'Y-m-d', strtotime( "+" . ( $i * 3 ) . " months", strtotime( $start_date ) ) ),
                    ];
                }
                break;
                
            case 'full':
                // Single payment for entire course
                $installments[] = [
                    'number' => 1,
                    'amount' => $price_per_month * $total_months,
                    'due_date' => $start_date,
                ];
                break;
        }
        
        // Insert installments into database
        foreach ( $installments as $installment ) {
            // Apply family discount to amount
            $discounted_amount = SM_Family_Discount::apply_discount(
                $installment['amount'],
                $discount_percentage
            );

            $wpdb->insert( $payment_schedules_table, [
                'enrollment_id' => $enrollment_id,
                'installment_number' => $installment['number'],
                'expected_amount' => $discounted_amount,
                'due_date' => $installment['due_date'],
                'status' => 'pending',
                'paid_amount' => 0,
                'discount_percentage' => $discount_percentage,
                'discount_reason' => $discount_reason,
            ] );
        }
    }
    /**
     * Render the Enrollments page
     */
    public static function render_enrollments_page() {
        // Security check
        if ( ! current_user_can( 'manage_enrollments' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'CTADZ-school-management' ) );
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'sm_enrollments';


        // Handle delete action
        if ( isset( $_GET['delete'] ) && check_admin_referer( 'sm_delete_enrollment_' . intval( $_GET['delete'] ) ) ) {
            $enrollment_id = intval( $_GET['delete'] );
            
            // CASCADE DELETE: Remove all related records first
            // This prevents orphaned data in the database
            
            // 1. Delete payment records (child of payment_schedules)
            $wpdb->delete(
                $wpdb->prefix . 'sm_payment_records',
                [ 'enrollment_id' => $enrollment_id ],
                [ '%d' ]
            );
            
            // 2. Delete payment schedules (child of enrollment)
            $wpdb->delete(
                $wpdb->prefix . 'sm_payment_schedules',
                [ 'enrollment_id' => $enrollment_id ],
                [ '%d' ]
            );
            
            // 3. Delete enrollment fees (insurance, books)
            $wpdb->delete(
                $wpdb->prefix . 'sm_enrollment_fees',
                [ 'enrollment_id' => $enrollment_id ],
                [ '%d' ]
            );
            
            // 4. Finally, delete the enrollment itself
            $deleted = $wpdb->delete( 
                $table, 
                [ 'id' => $enrollment_id ],
                [ '%d' ]
            );
            
            if ( $deleted ) {
                echo '<div class="updated notice"><p>' . esc_html__( 'Enrollment and all related payment data deleted successfully.', 'CTADZ-school-management' ) . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html__( 'Error deleting enrollment.', 'CTADZ-school-management' ) . '</p></div>';
            }
        }

        // Handle form submission
        if ( isset( $_POST['sm_save_enrollment'] ) && check_admin_referer( 'sm_save_enrollment_action', 'sm_save_enrollment_nonce' ) ) {
            $validation_result = self::validate_enrollment_data( $_POST );
    
            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];
                $enrollment_id = intval( $_POST['enrollment_id'] ?? 0 );
        
                if ( $enrollment_id > 0 ) {
                    // Update existing enrollment
                    $updated = $wpdb->update( $table, $data, [ 'id' => $enrollment_id ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Enrollment updated successfully.', 'CTADZ-school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-enrollments"; }, 2000);</script>';
                    }
                } else {
                    // Insert new enrollment
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        $new_enrollment_id = $wpdb->insert_id;
                
                        // Create enrollment fees and payment schedules
                        self::create_enrollment_fees( $new_enrollment_id, $_POST );
                        self::create_payment_schedule( $new_enrollment_id, $_POST );
                
                        echo '<div class="updated notice"><p>' . esc_html__( 'Enrollment added successfully with payment schedule generated.', 'CTADZ-school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-enrollments"; }, 2000);</script>';
                    }
                }
            } else {
                echo '<div class="error notice"><p><strong>' . esc_html__( 'Please correct the following errors:', 'CTADZ-school-management' ) . '</strong></p>';
                echo '<ul style="margin-left: 20px;">';
                foreach ( $validation_result['errors'] as $error ) {
                    echo '<li>' . esc_html( $error ) . '</li>';
                }
                echo '</ul></div>';
            }
        }
        // Determine view
        $action = $_GET['action'] ?? 'list';
        $enrollment = null;

        if ( $action === 'edit' && isset( $_GET['enrollment_id'] ) ) {
            $enrollment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['enrollment_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Enrollments', 'CTADZ-school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'add':
                    self::render_enrollment_form( null );
                    break;
                case 'edit':
                    self::render_enrollment_form( $enrollment );
                    break;
                default:
                    self::render_enrollments_list();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Validate enrollment data
     */
    private static function validate_enrollment_data( $post_data ) {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $levels_table = $wpdb->prefix . 'sm_levels';
        
        $errors = [];
        
        $student_id = intval( $post_data['student_id'] ?? 0 );
        $course_id = intval( $post_data['course_id'] ?? 0 );
        $enrollment_date = sanitize_text_field( trim( $post_data['enrollment_date'] ?? '' ) );
        $start_date = sanitize_text_field( trim( $post_data['start_date'] ?? '' ) );
        $end_date = sanitize_text_field( trim( $post_data['end_date'] ?? '' ) );
        $status = sanitize_text_field( trim( $post_data['status'] ?? 'active' ) );
        $payment_status = sanitize_text_field( trim( $post_data['payment_status'] ?? 'unpaid' ) );
        $notes = sanitize_textarea_field( trim( $post_data['notes'] ?? '' ) );
        $enrollment_id = intval( $post_data['enrollment_id'] ?? 0 );

        // Required field validation
        if ( $student_id <= 0 ) {
            $errors[] = __( 'Please select a student.', 'CTADZ-school-management' );
        }

        if ( $course_id <= 0 ) {
            $errors[] = __( 'Please select a course.', 'CTADZ-school-management' );
        }

        if ( empty( $enrollment_date ) ) {
            $errors[] = __( 'Enrollment date is required.', 'CTADZ-school-management' );
        }

        if ( empty( $start_date ) ) {
            $errors[] = __( 'Start date is required.', 'CTADZ-school-management' );
        }

        // Validate dates
        if ( ! empty( $start_date ) && ! empty( $enrollment_date ) && strtotime( $start_date ) < strtotime( $enrollment_date ) ) {
            $errors[] = __( 'Start date cannot be before enrollment date.', 'CTADZ-school-management' );
        }

        if ( ! empty( $end_date ) && ! empty( $start_date ) && strtotime( $end_date ) < strtotime( $start_date ) ) {
            $errors[] = __( 'End date cannot be before start date.', 'CTADZ-school-management' );
        }

        // Check for duplicate enrollment
        if ( $student_id > 0 && $course_id > 0 ) {
            $duplicate_query = "SELECT id FROM $enrollments_table WHERE student_id = %d AND course_id = %d";
            $params = [ $student_id, $course_id ];
            
            if ( $enrollment_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $params[] = $enrollment_id;
            }
            
            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $params ) );
            if ( $duplicate ) {
                $errors[] = __( 'This student is already enrolled in this course.', 'CTADZ-school-management' );
            }
        }

        // Check level prerequisite (student level must match or be below course level)
        if ( $student_id > 0 && $course_id > 0 ) {
            $student = $wpdb->get_row( $wpdb->prepare( "SELECT level_id FROM $students_table WHERE id = %d", $student_id ) );
            $course = $wpdb->get_row( $wpdb->prepare( "SELECT level_id FROM $courses_table WHERE id = %d", $course_id ) );
            
            if ( $student && $course ) {
                $student_level_order = $wpdb->get_var( $wpdb->prepare( "SELECT sort_order FROM $levels_table WHERE id = %d", $student->level_id ) );
                $course_level_order = $wpdb->get_var( $wpdb->prepare( "SELECT sort_order FROM $levels_table WHERE id = %d", $course->level_id ) );
                
                if ( $student_level_order > $course_level_order ) {
                    $errors[] = __( 'Student level is too advanced for this course. Students can only enroll in courses at their level or above.', 'CTADZ-school-management' );
                }
            }
        }

        // Check course capacity
        if ( $course_id > 0 && $enrollment_id == 0 ) { // Only check on new enrollments
            $course = $wpdb->get_row( $wpdb->prepare( "SELECT max_students FROM $courses_table WHERE id = %d", $course_id ) );
            
            if ( $course && $course->max_students > 0 ) {
                $current_enrollments = $wpdb->get_var( $wpdb->prepare( 
                    "SELECT COUNT(*) FROM $enrollments_table WHERE course_id = %d AND status IN ('active', 'completed')", 
                    $course_id 
                ) );
                
                if ( $current_enrollments >= $course->max_students ) {
                    $errors[] = sprintf( __( 'This course is full. Maximum capacity: %d students.', 'CTADZ-school-management' ), $course->max_students );
                }
            }
        }

        // Validate status
        $valid_statuses = [ 'active', 'completed', 'dropped', 'suspended' ];
        if ( ! in_array( $status, $valid_statuses ) ) {
            $errors[] = __( 'Invalid enrollment status.', 'CTADZ-school-management' );
        }

        // Validate payment status
        $valid_payment_statuses = [ 'paid', 'unpaid', 'partial', 'overdue' ];
        if ( ! in_array( $payment_status, $valid_payment_statuses ) ) {
            $errors[] = __( 'Invalid payment status.', 'CTADZ-school-management' );
        }

        // Validate payment plan against course payment model (only for new enrollments)
        if ( $enrollment_id == 0 && $course_id > 0 ) {
            $payment_plan = sanitize_text_field( $post_data['payment_plan'] ?? 'monthly' );
            $validation_result = sm_validate_enrollment_payment_plan( $course_id, $payment_plan );
            
            if ( is_wp_error( $validation_result ) ) {
                $errors[] = $validation_result->get_error_message();
            }
        }

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'student_id' => $student_id,
                    'course_id' => $course_id,
                    'enrollment_date' => $enrollment_date,
                    'start_date' => $start_date,
                    'end_date' => $end_date ?: null,
                    'status' => $status,
                    'payment_status' => $payment_status,
                    'payment_plan' => sanitize_text_field( $post_data['payment_plan'] ?? 'monthly' ),
                    'notes' => $notes,
                ]
            ];
        }

        return [ 'success' => false, 'errors' => $errors ];
    }

    /**
     * Render enrollments list
     */
    private static function render_enrollments_list() {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
        $enrollment_fees_table = $wpdb->prefix . 'sm_enrollment_fees';

        // Get search parameter
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // Get sorting parameters
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'enrollment_date';
        $order = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), [ 'ASC', 'DESC' ] ) ? strtoupper( $_GET['order'] ) : 'DESC';
        
        // Get filter parameters
        $filter_payment_status = isset( $_GET['filter_payment_status'] ) ? sanitize_text_field( $_GET['filter_payment_status'] ) : '';
        $filter_enrollment_status = isset( $_GET['filter_enrollment_status'] ) ? sanitize_text_field( $_GET['filter_enrollment_status'] ) : '';
        $filter_payment_model = isset( $_GET['filter_payment_model'] ) ? sanitize_text_field( $_GET['filter_payment_model'] ) : '';

        // Pagination
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        // Build WHERE clause - SQL and params separately
        $where_sql_parts = [];
        $where_params = [];

        // Search condition
        if ( ! empty( $search ) ) {
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $where_sql_parts[] = "(s.name LIKE %s OR c.name LIKE %s)";
            $where_params[] = $search_term;
            $where_params[] = $search_term;
        }

        // Filter conditions
        if ( ! empty( $filter_payment_status ) ) {
            $where_sql_parts[] = "e.payment_status = %s";
            $where_params[] = $filter_payment_status;
        }
        if ( ! empty( $filter_enrollment_status ) ) {
            $where_sql_parts[] = "e.status = %s";
            $where_params[] = $filter_enrollment_status;
        }
        if ( ! empty( $filter_payment_model ) ) {
            $where_sql_parts[] = "c.payment_model = %s";
            $where_params[] = $filter_payment_model;
        }

        $where_sql = ! empty( $where_sql_parts ) ? 'WHERE ' . implode( ' AND ', $where_sql_parts ) : '';

        // Validate and set ORDER BY clause
        $valid_columns = [
            'student_name' => 's.name',
            'course_name' => 'c.name',
            'enrollment_date' => 'e.enrollment_date',
            'status' => 'e.status',
            'payment_status' => 'e.payment_status',
            'payment_plan' => 'e.payment_plan'
        ];
        $orderby_column = isset( $valid_columns[ $orderby ] ) ? $valid_columns[ $orderby ] : 'e.enrollment_date';
        $order_clause = "$orderby_column $order";

        // Get total count for pagination
        $count_query = "SELECT COUNT(*) FROM $enrollments_table e LEFT JOIN $students_table s ON e.student_id = s.id LEFT JOIN $courses_table c ON e.course_id = c.id $where_sql";

        if ( ! empty( $where_params ) ) {
            $total_enrollments = $wpdb->get_var( $wpdb->prepare( $count_query, $where_params ) );
        } else {
            $total_enrollments = $wpdb->get_var( $count_query );
        }
        $total_pages = ceil( $total_enrollments / $per_page );

        // Get enrollments with student and course names, plus payment info
        $query = "SELECT e.*,
                    s.name as student_name,
                    c.name as course_name,
                    c.payment_model,
                    c.price_per_month,
                    c.total_price,
                    c.total_months
             FROM $enrollments_table e
             LEFT JOIN $students_table s ON e.student_id = s.id
             LEFT JOIN $courses_table c ON e.course_id = c.id
             $where_sql
             ORDER BY $order_clause
             LIMIT %d OFFSET %d";

        // Merge all parameters for the main query
        $all_params = array_merge( $where_params, array( $per_page, $offset ) );

        if ( ! empty( $all_params ) ) {
            $enrollments = $wpdb->get_results( $wpdb->prepare( $query, $all_params ) );
        } else {
            $enrollments = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );
        }

        // Calculate payment progress for each enrollment
        foreach ( $enrollments as $enrollment ) {
            // Get total fees (insurance + books)
            $total_fees = $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM $enrollment_fees_table WHERE enrollment_id = %d",
                $enrollment->id
            ) );
            
            // Get paid fees
            $paid_fees = $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(ef.amount), 0) 
                 FROM $enrollment_fees_table ef 
                 WHERE ef.enrollment_id = %d AND ef.status = 'paid'",
                $enrollment->id
            ) );
            
            // Get total expected from payment schedule
            $total_expected = $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(expected_amount), 0) FROM $payment_schedules_table WHERE enrollment_id = %d",
                $enrollment->id
            ) );
            
            // Get total paid from payment schedule
            $total_paid = $wpdb->get_var( $wpdb->prepare(
                "SELECT COALESCE(SUM(paid_amount), 0) FROM $payment_schedules_table WHERE enrollment_id = %d",
                $enrollment->id
            ) );
            
            // Calculate totals
            $enrollment->total_amount = $total_expected + $total_fees;
            $enrollment->paid_amount = $total_paid + $paid_fees;
            $enrollment->remaining_amount = $enrollment->total_amount - $enrollment->paid_amount;
            $enrollment->payment_progress = $enrollment->total_amount > 0 ? ( $enrollment->paid_amount / $enrollment->total_amount ) * 100 : 0;
        }

        // Helper function to generate sortable column URL
        $get_sort_url = function( $column ) use ( $orderby, $order, $search, $filter_payment_status, $filter_enrollment_status, $filter_payment_model ) {
            $new_order = ( $orderby === $column && $order === 'ASC' ) ? 'DESC' : 'ASC';
            $url = add_query_arg( [
                'page' => 'school-management-enrollments',
                'orderby' => $column,
                'order' => $new_order,
            ] );
            
            if ( ! empty( $search ) ) {
                $url = add_query_arg( 's', urlencode( $search ), $url );
            }
            if ( ! empty( $filter_payment_status ) ) {
                $url = add_query_arg( 'filter_payment_status', $filter_payment_status, $url );
            }
            if ( ! empty( $filter_enrollment_status ) ) {
                $url = add_query_arg( 'filter_enrollment_status', $filter_enrollment_status, $url );
            }
            if ( ! empty( $filter_payment_model ) ) {
                $url = add_query_arg( 'filter_payment_model', $filter_payment_model, $url );
            }
            
            return esc_url( $url );
        };

        // Helper function to get sort indicator
        $get_sort_indicator = function( $column ) use ( $orderby, $order ) {
            if ( $orderby === $column ) {
                return $order === 'ASC' ? ' ▲' : ' ▼';
            }
            return '';
        };

        ?>
        <style>
        .sm-progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f1;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            margin-bottom: 5px;
        }
        .sm-progress-fill {
            height: 100%;
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 11px;
            font-weight: bold;
        }
        .sm-progress-fill.high { background: #46b450; }
        .sm-progress-fill.medium { background: #f0ad4e; }
        .sm-progress-fill.low { background: #dc3232; }
        
        /* Sortable column styles */
        .wp-list-table thead th.sortable a,
        .wp-list-table thead th.sorted a {
            text-decoration: none;
            color: inherit;
            display: block;
            cursor: pointer;
            position: relative;
            padding-right: 20px;
        }
        
        /* Add sort icon to show column is sortable */
        .wp-list-table thead th.sortable a::after {
            content: "⇅";
            position: absolute;
            right: 0;
            opacity: 0.3;
            font-size: 14px;
            transition: opacity 0.2s;
        }
        
        .wp-list-table thead th.sortable a:hover {
            color: #0073aa;
        }
        
        .wp-list-table thead th.sortable a:hover::after {
            opacity: 0.7;
        }
        
        .wp-list-table thead th.sorted {
            background-color: #f0f0f1;
        }
        
        .wp-list-table thead th.sorted a {
            font-weight: 600;
            color: #0073aa;
        }
        
        /* Hide the double arrow when actively sorted (we show ▲ or ▼ instead) */
        .wp-list-table thead th.sorted a::after {
            display: none;
        }
        
        /* Non-sortable columns styling */
        .wp-list-table thead th.non-sortable {
            color: #646970;
            cursor: default;
        }
        </style>
        
        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php esc_html_e( 'Enrollments List', 'CTADZ-school-management' ); ?></h2>
                <p class="description">
                    <?php 
                    $active_filters = ! empty( $search ) || ! empty( $filter_payment_status ) || ! empty( $filter_enrollment_status ) || ! empty( $filter_payment_model );
                    if ( $active_filters ) {
                        printf( esc_html__( 'Showing %d filtered enrollments', 'CTADZ-school-management' ), $total_enrollments );
                        echo ' <a href="?page=school-management-enrollments" style="margin-left: 10px;">' . esc_html__( '[Clear all filters]', 'CTADZ-school-management' ) . '</a>';
                    } else {
                        printf( esc_html__( 'Total: %d enrollments', 'CTADZ-school-management' ), $total_enrollments );
                    }
                    ?>
                </p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <!-- Search Box -->
                <form method="get" style="margin: 0;">
                    <input type="hidden" name="page" value="school-management-enrollments" />
                    <?php if ( ! empty( $filter_payment_status ) ) : ?>
                        <input type="hidden" name="filter_payment_status" value="<?php echo esc_attr( $filter_payment_status ); ?>" />
                    <?php endif; ?>
                    <?php if ( ! empty( $filter_enrollment_status ) ) : ?>
                        <input type="hidden" name="filter_enrollment_status" value="<?php echo esc_attr( $filter_enrollment_status ); ?>" />
                    <?php endif; ?>
                    <?php if ( ! empty( $filter_payment_model ) ) : ?>
                        <input type="hidden" name="filter_payment_model" value="<?php echo esc_attr( $filter_payment_model ); ?>" />
                    <?php endif; ?>
                    <?php if ( ! empty( $orderby ) ) : ?>
                        <input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>" />
                    <?php endif; ?>
                    <?php if ( ! empty( $order ) ) : ?>
                        <input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>" />
                    <?php endif; ?>
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Enter enrollment information', 'CTADZ-school-management' ); ?>" />
                    <button type="submit" class="button"><?php esc_html_e( 'Search', 'CTADZ-school-management' ); ?></button>
                    <?php if ( ! empty( $search ) ) : ?>
                        <a href="<?php echo esc_url( remove_query_arg( 's' ) ); ?>" class="button"><?php esc_html_e( 'Clear', 'CTADZ-school-management' ); ?></a>
                    <?php endif; ?>
                </form>
                
                <!-- Payment Status Filter -->
                <select id="filter_payment_status" onchange="updateFilters()">
                    <option value=""><?php esc_html_e( 'All Payment Statuses', 'CTADZ-school-management' ); ?></option>
                    <option value="paid" <?php selected( $filter_payment_status, 'paid' ); ?>><?php esc_html_e( 'Paid', 'CTADZ-school-management' ); ?></option>
                    <option value="unpaid" <?php selected( $filter_payment_status, 'unpaid' ); ?>><?php esc_html_e( 'Unpaid', 'CTADZ-school-management' ); ?></option>
                    <option value="partial" <?php selected( $filter_payment_status, 'partial' ); ?>><?php esc_html_e( 'Partial', 'CTADZ-school-management' ); ?></option>
                    <option value="overdue" <?php selected( $filter_payment_status, 'overdue' ); ?>><?php esc_html_e( 'Overdue', 'CTADZ-school-management' ); ?></option>
                </select>
                
                <!-- Enrollment Status Filter -->
                <select id="filter_enrollment_status" onchange="updateFilters()">
                    <option value=""><?php esc_html_e( 'All Enrollment Statuses', 'CTADZ-school-management' ); ?></option>
                    <option value="active" <?php selected( $filter_enrollment_status, 'active' ); ?>><?php esc_html_e( 'Active', 'CTADZ-school-management' ); ?></option>
                    <option value="completed" <?php selected( $filter_enrollment_status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'CTADZ-school-management' ); ?></option>
                    <option value="dropped" <?php selected( $filter_enrollment_status, 'dropped' ); ?>><?php esc_html_e( 'Dropped', 'CTADZ-school-management' ); ?></option>
                    <option value="suspended" <?php selected( $filter_enrollment_status, 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'CTADZ-school-management' ); ?></option>
                </select>
                
                <!-- Payment Model Filter -->
                <select id="filter_payment_model" onchange="updateFilters()">
                    <option value=""><?php esc_html_e( 'All Payment Models', 'CTADZ-school-management' ); ?></option>
                    <option value="full_payment" <?php selected( $filter_payment_model, 'full_payment' ); ?>><?php esc_html_e( 'Full Payment', 'CTADZ-school-management' ); ?></option>
                    <option value="monthly_installments" <?php selected( $filter_payment_model, 'monthly_installments' ); ?>><?php esc_html_e( 'Installments', 'CTADZ-school-management' ); ?></option>
                    <option value="monthly_subscription" <?php selected( $filter_payment_model, 'monthly_subscription' ); ?>><?php esc_html_e( 'Subscription', 'CTADZ-school-management' ); ?></option>
                </select>
                
                <a href="?page=school-management-enrollments&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'New Enrollment', 'CTADZ-school-management' ); ?>
                </a>
            </div>
        </div>

        <script>
        function updateFilters() {
            var paymentStatus = document.getElementById('filter_payment_status').value;
            var enrollmentStatus = document.getElementById('filter_enrollment_status').value;
            var paymentModel = document.getElementById('filter_payment_model').value;
            var url = '?page=school-management-enrollments';
            
            // Preserve search and sort parameters
            var urlParams = new URLSearchParams(window.location.search);
            var search = urlParams.get('s');
            var orderby = urlParams.get('orderby');
            var order = urlParams.get('order');
            
            if (search) url += '&s=' + encodeURIComponent(search);
            if (orderby) url += '&orderby=' + orderby;
            if (order) url += '&order=' + order;
            if (paymentStatus) url += '&filter_payment_status=' + paymentStatus;
            if (enrollmentStatus) url += '&filter_enrollment_status=' + enrollmentStatus;
            if (paymentModel) url += '&filter_payment_model=' + paymentModel;
            
            window.location.href = url;
        }
        </script>

        <?php if ( $enrollments ) : ?>
            <table class="wp-list-table widefat fixed striped mobile-card-layout">
                <thead>
                    <tr>
                        <th class="<?php echo $orderby === 'student_name' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'student_name' ); ?>">
                                <?php esc_html_e( 'Student', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'student_name' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'course_name' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'course_name' ); ?>">
                                <?php esc_html_e( 'Course', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'course_name' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable"><?php esc_html_e( 'Payment Model', 'CTADZ-school-management' ); ?></th>
                        <th class="<?php echo $orderby === 'payment_plan' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'payment_plan' ); ?>">
                                <?php esc_html_e( 'Payment Plan', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'payment_plan' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable" style="width: 200px;"><?php esc_html_e( 'Payment Progress', 'CTADZ-school-management' ); ?></th>
                        <th class="<?php echo $orderby === 'enrollment_date' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'enrollment_date' ); ?>">
                                <?php esc_html_e( 'Enrolled', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'enrollment_date' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'status' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'status' ); ?>">
                                <?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'status' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable" style="width: 150px;"><?php esc_html_e( 'Actions', 'CTADZ-school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $enrollments as $enrollment ) : ?>
                        <tr>
                            <td data-label="<?php echo esc_attr__( 'Student', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Student', 'CTADZ-school-management' ); ?>:</span>
                                <strong><?php echo esc_html( $enrollment->student_name ?: '—' ); ?></strong>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Course', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Course', 'CTADZ-school-management' ); ?>:</span>
                                <?php echo esc_html( $enrollment->course_name ?: '—' ); ?>
                            </td>

                            <!-- Payment Model Badge -->
                            <td data-label="<?php echo esc_attr__( 'Payment Model', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Payment Model', 'CTADZ-school-management' ); ?>:</span>
                                <?php
                                // Payment model display with icons and colors (matching Courses List)
                                $payment_model_display = [
                                    'full_payment' => [
                                        'label' => __( 'Full Payment', 'CTADZ-school-management' ),
                                        'icon' => 'dashicons-money-alt',
                                        'color' => '#46b450',
                                        'bg' => '#ecf7ed',
                                    ],
                                    'monthly_installments' => [
                                        'label' => __( 'Installments', 'CTADZ-school-management' ),
                                        'icon' => 'dashicons-calendar-alt',
                                        'color' => '#00a0d2',
                                        'bg' => '#e5f5fa',
                                    ],
                                    'monthly_subscription' => [
                                        'label' => __( 'Subscription', 'CTADZ-school-management' ),
                                        'icon' => 'dashicons-update',
                                        'color' => '#f0ad4e',
                                        'bg' => '#fef8e7',
                                    ],
                                ];
                                
                                $model = $enrollment->payment_model ?? 'monthly_installments';
                                $display = $payment_model_display[ $model ] ?? $payment_model_display['monthly_installments'];
                                ?>
                                <span style="display: inline-flex; align-items: center; padding: 4px 10px; background: <?php echo esc_attr( $display['bg'] ); ?>; border-radius: 4px; font-size: 12px;">
                                    <span class="dashicons <?php echo esc_attr( $display['icon'] ); ?>" style="font-size: 14px; color: <?php echo esc_attr( $display['color'] ); ?>; margin-right: 5px;"></span>
                                    <strong style="color: <?php echo esc_attr( $display['color'] ); ?>;"><?php echo esc_html( $display['label'] ); ?></strong>
                                </span>
                            </td>

                            <!-- Payment Plan -->
                            <td data-label="<?php echo esc_attr__( 'Payment Plan', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Payment Plan', 'CTADZ-school-management' ); ?>:</span>
                                <?php
                                $payment_plan_labels = [
                                    'monthly' => __( 'Monthly', 'CTADZ-school-management' ),
                                    'quarterly' => __( 'Quarterly', 'CTADZ-school-management' ),
                                    'full' => __( 'Full Payment', 'CTADZ-school-management' ),
                                ];
                                $plan_label = $payment_plan_labels[ $enrollment->payment_plan ?? 'monthly' ] ?? $enrollment->payment_plan;
                                echo esc_html( $plan_label );
                                ?>
                            </td>

                            <!-- Payment Progress -->
                            <td data-label="<?php echo esc_attr__( 'Payment Progress', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Payment Progress', 'CTADZ-school-management' ); ?>:</span>
                                <?php
                                $progress = round( $enrollment->payment_progress );
                                $progress_class = $progress >= 75 ? 'high' : ( $progress >= 40 ? 'medium' : 'low' );
                                ?>
                                <div class="sm-progress-bar">
                                    <div class="sm-progress-fill <?php echo esc_attr( $progress_class ); ?>" style="width: <?php echo esc_attr( $progress ); ?>%;">
                                        <?php if ( $progress > 15 ) echo esc_html( $progress . '%' ); ?>
                                    </div>
                                </div>
                                <div style="font-size: 11px; color: #666; text-align: center;">
                                    <?php
                                    printf(
                                        esc_html__( '%s / %s', 'CTADZ-school-management' ),
                                        '<strong>' . number_format( $enrollment->paid_amount, 2 ) . '</strong>',
                                        number_format( $enrollment->total_amount, 2 )
                                    );
                                    ?>
                                </div>
                            </td>

                            <td data-label="<?php echo esc_attr__( 'Enrolled', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Enrolled', 'CTADZ-school-management' ); ?>:</span>
                                <?php echo esc_html( date( 'M j, Y', strtotime( $enrollment->enrollment_date ) ) ); ?>
                            </td>

                            <!-- Enrollment Status -->
                            <td data-label="<?php echo esc_attr__( 'Status', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?>:</span>
                                <?php
                                $status_colors = [
                                    'active' => '#46b450',
                                    'completed' => '#00a0d2',
                                    'dropped' => '#dc3232',
                                    'suspended' => '#f0ad4e'
                                ];
                                $color = $status_colors[ $enrollment->status ] ?? '#666';
                                $status_labels = [
                                    'active' => __( 'Active', 'CTADZ-school-management' ),
                                    'completed' => __( 'Completed', 'CTADZ-school-management' ),
                                    'dropped' => __( 'Dropped', 'CTADZ-school-management' ),
                                    'suspended' => __( 'Suspended', 'CTADZ-school-management' )
                                ];
                                $label = $status_labels[ $enrollment->status ] ?? $enrollment->status;
                                ?>
                                <span style="color: <?php echo esc_attr( $color ); ?>;">● <?php echo esc_html( $label ); ?></span>
                            </td>

                            <!-- Actions -->
                            <td class="actions">
                                <a href="?page=school-management-enrollments&action=edit&enrollment_id=<?php echo intval( $enrollment->id ); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit"></span>
                                    <span class="button-text"><?php esc_html_e( 'Edit', 'CTADZ-school-management' ); ?></span>
                                </a>
                                <?php
                                $delete_url = wp_nonce_url(
                                    '?page=school-management-enrollments&delete=' . intval( $enrollment->id ),
                                    'sm_delete_enrollment_' . intval( $enrollment->id )
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this enrollment?', 'CTADZ-school-management' ) ); ?>')">
                                    <span class="dashicons dashicons-trash text-danger"></span>
                                    <span class="button-text"><?php esc_html_e( 'Delete', 'CTADZ-school-management' ); ?></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            // Pagination
            if ( $total_pages > 1 ) {
                $page_args = [ 'page' => 'school-management-enrollments' ];
                
                if ( ! empty( $search ) ) {
                    $page_args['s'] = $search;
                }
                if ( ! empty( $orderby ) ) {
                    $page_args['orderby'] = $orderby;
                }
                if ( ! empty( $order ) ) {
                    $page_args['order'] = $order;
                }
                if ( ! empty( $filter_payment_status ) ) {
                    $page_args['filter_payment_status'] = $filter_payment_status;
                }
                if ( ! empty( $filter_enrollment_status ) ) {
                    $page_args['filter_enrollment_status'] = $filter_enrollment_status;
                }
                if ( ! empty( $filter_payment_model ) ) {
                    $page_args['filter_payment_model'] = $filter_payment_model;
                }
                
                $base_url = add_query_arg( $page_args, admin_url( 'admin.php' ) );
                
                $pagination_args = [
                    'base' => add_query_arg( 'paged', '%#%', $base_url ),
                    'format' => '',
                    'prev_text' => __( '« Previous', 'CTADZ-school-management' ),
                    'next_text' => __( 'Next »', 'CTADZ-school-management' ),
                    'total' => $total_pages,
                    'current' => $current_page,
                ];
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                echo paginate_links( $pagination_args );
                echo '</div></div>';
            }
            ?>

        <?php else : ?>
            <div class="sm-empty-state" style="text-align: center; padding: 60px 20px; background: #fafafa; border: 1px dashed #ddd; border-radius: 4px;">
                <span class="dashicons dashicons-welcome-learn-more" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></span>
                <h3><?php esc_html_e( 'No Enrollments Found', 'CTADZ-school-management' ); ?></h3>
                <p>
                    <?php 
                    if ( ! empty( $search ) || ! empty( $filter_payment_status ) || ! empty( $filter_enrollment_status ) || ! empty( $filter_payment_model ) ) {
                        esc_html_e( 'No enrollments match the selected filters or search.', 'CTADZ-school-management' );
                        echo '<br><a href="?page=school-management-enrollments">' . esc_html__( 'View all enrollments', 'CTADZ-school-management' ) . '</a>';
                    } else {
                        esc_html_e( 'Start enrolling students in courses.', 'CTADZ-school-management' );
                    }
                    ?>
                </p>
                <?php if ( empty( $search ) && empty( $filter_payment_status ) && empty( $filter_enrollment_status ) && empty( $filter_payment_model ) ) : ?>
                    <a href="?page=school-management-enrollments&action=add" class="button button-primary">
                        <?php esc_html_e( 'Create First Enrollment', 'CTADZ-school-management' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif;
    }

    /**
     * Render enrollment form
     */
    private static function render_enrollment_form( $enrollment = null ) {
        global $wpdb;
        $is_edit = ! empty( $enrollment );
        
        // Get students and courses
        $students = $wpdb->get_results( "SELECT id, name, level_id FROM {$wpdb->prefix}sm_students ORDER BY name ASC" );
        $courses = $wpdb->get_results( "SELECT c.id, c.name, c.level_id, c.max_students, c.payment_model, c.price_per_month, c.total_price, c.total_months, l.name as level_name 
                                        FROM {$wpdb->prefix}sm_courses c 
                                        LEFT JOIN {$wpdb->prefix}sm_levels l ON c.level_id = l.id 
                                        WHERE c.is_active = 1 
                                        ORDER BY c.name ASC" );
        
        $form_data = [];
        if ( isset( $_POST['sm_save_enrollment'] ) ) {
            $form_data = [
                'student_id' => intval( $_POST['student_id'] ?? 0 ),
                'course_id' => intval( $_POST['course_id'] ?? 0 ),
                'enrollment_date' => sanitize_text_field( $_POST['enrollment_date'] ?? '' ),
                'start_date' => sanitize_text_field( $_POST['start_date'] ?? '' ),
                'end_date' => sanitize_text_field( $_POST['end_date'] ?? '' ),
                'status' => sanitize_text_field( $_POST['status'] ?? 'active' ),
                'payment_status' => sanitize_text_field( $_POST['payment_status'] ?? 'unpaid' ),
                'notes' => sanitize_textarea_field( $_POST['notes'] ?? '' ),
            ];
        } elseif ( $enrollment ) {
            $form_data = [
                'student_id' => $enrollment->student_id,
                'course_id' => $enrollment->course_id,
                'enrollment_date' => $enrollment->enrollment_date,
                'start_date' => $enrollment->start_date,
                'end_date' => $enrollment->end_date,
                'status' => $enrollment->status,
                'payment_status' => $enrollment->payment_status,
                'payment_plan' => $enrollment->payment_plan,
                'notes' => $enrollment->notes,
            ];
        }
        
        // Get payment data for edit mode
        $payment_data = null;
        if ( $is_edit ) {
            $enrollment_fees_table = $wpdb->prefix . 'sm_enrollment_fees';
            $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
            $courses_table = $wpdb->prefix . 'sm_courses';
            
            // Get course payment info
            $course_info = $wpdb->get_row( $wpdb->prepare(
                "SELECT payment_model, price_per_month, total_price, total_months FROM $courses_table WHERE id = %d",
                $enrollment->course_id
            ) );
            
            // Get enrollment fees
            $fees = $wpdb->get_results( $wpdb->prepare(
                "SELECT fee_type, amount, status FROM $enrollment_fees_table WHERE enrollment_id = %d",
                $enrollment->id
            ) );
            
            $insurance_fee = 0;
            $book_fee = 0;
            foreach ( $fees as $fee ) {
                if ( $fee->fee_type === 'insurance' ) {
                    $insurance_fee = $fee->amount;
                }
                if ( $fee->fee_type === 'books' ) {
                    $book_fee = $fee->amount;
                }
            }
            
            // Get payment schedule summary
            $schedule_summary = $wpdb->get_row( $wpdb->prepare(
                "SELECT
                    COUNT(*) as total_installments,
                    SUM(expected_amount) as total_expected,
                    SUM(paid_amount) as total_paid,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_installments,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_installments,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_installments,
                    MAX(discount_percentage) as discount_percentage,
                    MAX(discount_reason) as discount_reason
                 FROM $payment_schedules_table
                 WHERE enrollment_id = %d",
                $enrollment->id
            ) );

            $payment_data = [
                'course_info' => $course_info,
                'insurance_fee' => $insurance_fee,
                'book_fee' => $book_fee,
                'schedule_summary' => $schedule_summary,
            ];
        }
        
        ?>
        <style>
        .sm-course-payment-info {
            background: #f0f6fc;
            border: 2px solid #0073aa;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .sm-course-payment-info.subscription {
            background: #fef8e7;
            border-color: #f0ad4e;
        }
        .sm-course-payment-info h4 {
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        .sm-course-payment-info.subscription h4 {
            color: #f0ad4e;
        }
        .sm-payment-model-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #0073aa;
            color: white;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .sm-course-payment-info.subscription .sm-payment-model-badge {
            background: #f0ad4e;
        }
        .sm-payment-summary-box {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
        }
        .sm-payment-summary-box h4 {
            margin: 0 0 12px 0;
            color: #333;
            font-size: 14px;
        }
        .sm-payment-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e5e5;
        }
        .sm-payment-row:last-child {
            border-bottom: none;
            font-weight: bold;
            padding-top: 12px;
            margin-top: 6px;
            border-top: 2px solid #ddd;
        }
        .sm-payment-label {
            color: #666;
        }
        .sm-payment-value {
            font-weight: 600;
            color: #333;
        }
        .sm-info-icon {
            display: inline-flex;
            align-items: center;
            background: #e7f5ff;
            border: 1px solid #91caff;
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 10px;
            font-size: 13px;
        }
        .sm-info-icon .dashicons {
            color: #1677ff;
            margin-right: 6px;
        }
        </style>
        
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-enrollments" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Enrollments', 'CTADZ-school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Enrollment', 'CTADZ-school-management' ) : esc_html__( 'New Enrollment', 'CTADZ-school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_enrollment_action', 'sm_save_enrollment_nonce' ); ?>
            <input type="hidden" name="enrollment_id" value="<?php echo esc_attr( $enrollment->id ?? '' ); ?>" />

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enrollment_student"><?php esc_html_e( 'Student', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="enrollment_student" name="student_id" required <?php echo $is_edit ? 'disabled' : ''; ?>>
                            <option value=""><?php esc_html_e( 'Select Student', 'CTADZ-school-management' ); ?></option>
                            <?php foreach ( $students as $student ) : ?>
                                <option value="<?php echo intval( $student->id ); ?>" <?php selected( $form_data['student_id'] ?? 0, $student->id ); ?>>
                                    <?php echo esc_html( $student->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ( $is_edit ) : ?>
                            <input type="hidden" name="student_id" value="<?php echo esc_attr( $form_data['student_id'] ?? '' ); ?>" />
                            <p class="description"><?php esc_html_e( 'Student cannot be changed after enrollment.', 'CTADZ-school-management' ); ?></p>
                        <?php else : ?>
                            <div id="sm-student-discount-info" style="display:none; margin-top: 10px; padding: 10px; background: #d4edda; border-left: 4px solid #28a745; color: #155724;">
                                <p style="margin: 0; font-weight: 600;">
                                    <span class="dashicons dashicons-groups" style="margin-right: 5px;"></span>
                                    <span id="sm-discount-message"></span>
                                </p>
                            </div>
                            <p class="description">
                                <a href="?page=school-management-students&action=add" target="_blank"><?php esc_html_e( 'Add new student', 'CTADZ-school-management' ); ?></a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="enrollment_course"><?php esc_html_e( 'Course', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="enrollment_course" name="course_id" required <?php echo $is_edit ? 'disabled' : ''; ?>>
                            <option value=""><?php esc_html_e( 'Select Course', 'CTADZ-school-management' ); ?></option>
                            <?php foreach ( $courses as $course ) : 
                                // Get current enrollment count
                                $current_count = $wpdb->get_var( $wpdb->prepare( 
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}sm_enrollments WHERE course_id = %d AND status IN ('active', 'completed')", 
                                    $course->id 
                                ) );
                                $spots_info = '';
                                if ( $course->max_students > 0 ) {
                                    $spots_left = $course->max_students - $current_count;
                                    $spots_info = " ($spots_left spots left)";
                                }
                            ?>
                                <option value="<?php echo intval( $course->id ); ?>" <?php selected( $form_data['course_id'] ?? 0, $course->id ); ?>>
                                    <?php echo esc_html( $course->name . ' - ' . $course->level_name . $spots_info ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ( $is_edit ) : ?>
                            <input type="hidden" name="course_id" value="<?php echo esc_attr( $form_data['course_id'] ?? '' ); ?>" />
                            <p class="description"><?php esc_html_e( 'Course cannot be changed after enrollment.', 'CTADZ-school-management' ); ?></p>
                        <?php else : ?>
                            <p class="description">
                                <a href="?page=school-management-courses&action=add" target="_blank"><?php esc_html_e( 'Add new course', 'CTADZ-school-management' ); ?></a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="enrollment_date"><?php esc_html_e( 'Enrollment Date', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="date" id="enrollment_date" name="enrollment_date" value="<?php echo esc_attr( $form_data['enrollment_date'] ?? date('Y-m-d') ); ?>" required />
                        <p class="description"><?php esc_html_e( 'Date when the student enrolled in the course.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="start_date"><?php esc_html_e( 'Start Date', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( $form_data['start_date'] ?? '' ); ?>" required />
                        <p class="description"><?php esc_html_e( 'Date when the student begins attending the course.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="end_date"><?php esc_html_e( 'End Date', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr( $form_data['end_date'] ?? '' ); ?>" />
                        <p class="description"><?php esc_html_e( 'Optional: Date when the student completes or leaves the course.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <!-- PAYMENT INFORMATION SECTION -->
                <tr>
                    <td colspan="2"><hr style="margin: 20px 0; border: none; border-top: 2px solid #0073aa;"></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <h3 style="margin: 10px 0;"><?php esc_html_e( 'Payment Information', 'CTADZ-school-management' ); ?></h3>
                        <?php if ( $is_edit ) : ?>
                            <p class="description"><?php esc_html_e( 'Payment details for this enrollment. To modify payments, use the Payments page.', 'CTADZ-school-management' ); ?></p>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'Set enrollment fees and payment schedule for this enrollment.', 'CTADZ-school-management' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ( $is_edit && $payment_data ) : ?>
                    <!-- EDIT MODE: Show payment summary (read-only) -->
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Course Payment Model', 'CTADZ-school-management' ); ?></th>
                        <td>
                            <?php
                            $payment_model_display = [
                                'full_payment' => [
                                    'label' => __( 'Full Payment', 'CTADZ-school-management' ),
                                    'icon' => 'dashicons-money-alt',
                                    'color' => '#46b450',
                                ],
                                'monthly_installments' => [
                                    'label' => __( 'Monthly Installments', 'CTADZ-school-management' ),
                                    'icon' => 'dashicons-calendar-alt',
                                    'color' => '#00a0d2',
                                ],
                                'monthly_subscription' => [
                                    'label' => __( 'Monthly Subscription', 'CTADZ-school-management' ),
                                    'icon' => 'dashicons-update',
                                    'color' => '#f0ad4e',
                                ],
                            ];
                            
                            $model = $payment_data['course_info']->payment_model ?? 'monthly_installments';
                            $display = $payment_model_display[ $model ] ?? $payment_model_display['monthly_installments'];
                            ?>
                            <span style="display: inline-flex; align-items: center; padding: 6px 12px; background: <?php echo esc_attr( $display['color'] ); ?>; color: white; border-radius: 4px; font-size: 13px;">
                                <span class="dashicons <?php echo esc_attr( $display['icon'] ); ?>" style="font-size: 16px; margin-right: 6px;"></span>
                                <strong><?php echo esc_html( $display['label'] ); ?></strong>
                            </span>
                            
                            <div style="margin-top: 10px; font-size: 13px; color: #666;">
                                <?php
                                if ( $model === 'full_payment' ) {
                                    printf( 
                                        esc_html__( 'Total Price: %s', 'CTADZ-school-management' ),
                                        '<strong>' . number_format( floatval( $payment_data['course_info']->total_price ), 2 ) . ' DA</strong>'
                                    );
                                } else {
                                    printf(
                                        esc_html__( 'Price: %s/month × %d months = %s total', 'CTADZ-school-management' ),
                                        '<strong>' . number_format( floatval( $payment_data['course_info']->price_per_month ), 2 ) . ' DA</strong>',
                                        intval( $payment_data['course_info']->total_months ),
                                        '<strong>' . number_format( floatval( $payment_data['course_info']->total_price ), 2 ) . ' DA</strong>'
                                    );
                                }
                                ?>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Payment Plan', 'CTADZ-school-management' ); ?></th>
                        <td>
                            <?php
                            $payment_plan_labels = [
                                'monthly' => __( 'Monthly Payments', 'CTADZ-school-management' ),
                                'quarterly' => __( 'Quarterly Payments', 'CTADZ-school-management' ),
                                'full' => __( 'Full Payment', 'CTADZ-school-management' ),
                            ];
                            $plan_label = $payment_plan_labels[ $form_data['payment_plan'] ?? 'monthly' ] ?? $form_data['payment_plan'];
                            ?>
                            <strong><?php echo esc_html( $plan_label ); ?></strong>
                            <p class="description"><?php esc_html_e( 'Payment plan cannot be changed after enrollment.', 'CTADZ-school-management' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enrollment Fees', 'CTADZ-school-management' ); ?></th>
                        <td>
                            <div class="sm-payment-summary-box">
                                <?php if ( $payment_data['insurance_fee'] > 0 ) : ?>
                                    <div class="sm-payment-row">
                                        <span class="sm-payment-label"><?php esc_html_e( 'Insurance Fee:', 'CTADZ-school-management' ); ?></span>
                                        <span class="sm-payment-value"><?php echo number_format( $payment_data['insurance_fee'], 2 ); ?> DA</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $payment_data['book_fee'] > 0 ) : ?>
                                    <div class="sm-payment-row">
                                        <span class="sm-payment-label"><?php esc_html_e( 'Book Fee:', 'CTADZ-school-management' ); ?></span>
                                        <span class="sm-payment-value"><?php echo number_format( $payment_data['book_fee'], 2 ); ?> DA</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( $payment_data['insurance_fee'] == 0 && $payment_data['book_fee'] == 0 ) : ?>
                                    <p style="color: #666; margin: 0;"><?php esc_html_e( 'No enrollment fees for this enrollment.', 'CTADZ-school-management' ); ?></p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Payment Schedule Summary', 'CTADZ-school-management' ); ?></th>
                        <td>
                            <div class="sm-payment-summary-box">
                                <div class="sm-payment-row">
                                    <span class="sm-payment-label"><?php esc_html_e( 'Total Installments:', 'CTADZ-school-management' ); ?></span>
                                    <span class="sm-payment-value"><?php echo intval( $payment_data['schedule_summary']->total_installments ); ?></span>
                                </div>
                                <div class="sm-payment-row">
                                    <span class="sm-payment-label"><?php esc_html_e( 'Paid:', 'CTADZ-school-management' ); ?></span>
                                    <span class="sm-payment-value" style="color: #46b450;"><?php echo intval( $payment_data['schedule_summary']->paid_installments ); ?></span>
                                </div>
                                <div class="sm-payment-row">
                                    <span class="sm-payment-label"><?php esc_html_e( 'Pending:', 'CTADZ-school-management' ); ?></span>
                                    <span class="sm-payment-value" style="color: #f0ad4e;"><?php echo intval( $payment_data['schedule_summary']->pending_installments ); ?></span>
                                </div>
                                <?php if ( $payment_data['schedule_summary']->overdue_installments > 0 ) : ?>
                                <div class="sm-payment-row">
                                    <span class="sm-payment-label"><?php esc_html_e( 'Overdue:', 'CTADZ-school-management' ); ?></span>
                                    <span class="sm-payment-value" style="color: #dc3232;"><?php echo intval( $payment_data['schedule_summary']->overdue_installments ); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $payment_data['schedule_summary']->discount_percentage ) && $payment_data['schedule_summary']->discount_percentage > 0 ) : ?>
                                <div class="sm-payment-row" style="background: #d4edda; margin: 5px -15px; padding: 8px 15px; border-radius: 4px;">
                                    <span class="sm-payment-label" style="color: #155724;">
                                        <span class="dashicons dashicons-groups" style="margin-right: 5px;"></span>
                                        <?php esc_html_e( 'Family Discount:', 'CTADZ-school-management' ); ?>
                                    </span>
                                    <span class="sm-payment-value" style="color: #155724;">
                                        <?php echo number_format( $payment_data['schedule_summary']->discount_percentage, 1 ); ?>%
                                    </span>
                                </div>
                                <?php if ( ! empty( $payment_data['schedule_summary']->discount_reason ) ) : ?>
                                <div style="padding: 8px 0; font-size: 12px; color: #666; font-style: italic;">
                                    <?php echo esc_html( $payment_data['schedule_summary']->discount_reason ); ?>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                                <div class="sm-payment-row">
                                    <span class="sm-payment-label"><?php esc_html_e( 'Total Expected:', 'CTADZ-school-management' ); ?></span>
                                    <span class="sm-payment-value"><?php echo number_format( $payment_data['schedule_summary']->total_expected, 2 ); ?> DA</span>
                                </div>
                                <div class="sm-payment-row">
                                    <span class="sm-payment-label"><?php esc_html_e( 'Total Paid:', 'CTADZ-school-management' ); ?></span>
                                    <span class="sm-payment-value" style="color: #46b450;"><?php echo number_format( $payment_data['schedule_summary']->total_paid, 2 ); ?> DA</span>
                                </div>
                            </div>
                            
                            <div class="sm-info-icon">
                                <span class="dashicons dashicons-info"></span>
                                <?php esc_html_e( 'To view detailed payment schedule and make payments, go to the Payments page.', 'CTADZ-school-management' ); ?>
                            </div>
                        </td>
                    </tr>

                <?php else : ?>
                    <!-- ADD MODE: Show editable payment fields -->
                    <tr>
                        <th scope="row">
                            <label for="insurance_fee"><?php esc_html_e( 'Insurance Fee', 'CTADZ-school-management' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="insurance_fee" name="insurance_fee" value="<?php echo esc_attr( $form_data['insurance_fee'] ?? '0' ); ?>" step="0.01" min="0" style="width: 200px;" />
                            <p class="description"><?php esc_html_e( 'One-time insurance fee (only charged for student\'s first enrollment).', 'CTADZ-school-management' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="book_fee"><?php esc_html_e( 'Book Fee', 'CTADZ-school-management' ); ?></label>
                        </th>
                        <td>
                            <input type="number" id="book_fee" name="book_fee" value="<?php echo esc_attr( $form_data['book_fee'] ?? '0' ); ?>" step="0.01" min="0" style="width: 200px;" />
                            <p class="description"><?php esc_html_e( 'Book and materials fee for this course.', 'CTADZ-school-management' ); ?></p>
                        </td>
                    </tr>

                    <!-- Course Payment Information Display -->
                    <tr id="course_payment_info_row" style="display:none;">
                        <td colspan="2">
                            <div id="course_payment_info" class="sm-course-payment-info">
                                <div class="sm-payment-model-badge"></div>
                                <h4><?php esc_html_e( 'Course Payment Requirements', 'CTADZ-school-management' ); ?></h4>
                                <p class="sm-course-name"></p>
                                <p class="sm-payment-details"></p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="payment_plan"><?php esc_html_e( 'Payment Plan', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                        </th>
                        <td>
                            <select id="payment_plan" name="payment_plan" required>
                                <option value="monthly" <?php selected( $form_data['payment_plan'] ?? 'monthly', 'monthly' ); ?>><?php esc_html_e( 'Monthly Payments', 'CTADZ-school-management' ); ?></option>
                                <option value="quarterly" <?php selected( $form_data['payment_plan'] ?? '', 'quarterly' ); ?>><?php esc_html_e( 'Quarterly Payments (Every 3 months)', 'CTADZ-school-management' ); ?></option>
                                <option value="full" <?php selected( $form_data['payment_plan'] ?? '', 'full' ); ?>><?php esc_html_e( 'Full Payment (One-time)', 'CTADZ-school-management' ); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Choose how the student will pay for the course.', 'CTADZ-school-management' ); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
                
                <tr>
                    <td colspan="2"><hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;"></td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="enrollment_status"><?php esc_html_e( 'Enrollment Status', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="enrollment_status" name="status" required>
                            <option value="active" <?php selected( $form_data['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'CTADZ-school-management' ); ?></option>
                            <option value="completed" <?php selected( $form_data['status'] ?? '', 'completed' ); ?>><?php esc_html_e( 'Completed', 'CTADZ-school-management' ); ?></option>
                            <option value="dropped" <?php selected( $form_data['status'] ?? '', 'dropped' ); ?>><?php esc_html_e( 'Dropped', 'CTADZ-school-management' ); ?></option>
                            <option value="suspended" <?php selected( $form_data['status'] ?? '', 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'CTADZ-school-management' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="payment_status"><?php esc_html_e( 'Payment Status', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="payment_status" name="payment_status" required>
                            <option value="unpaid" <?php selected( $form_data['payment_status'] ?? 'unpaid', 'unpaid' ); ?>><?php esc_html_e( 'Unpaid', 'CTADZ-school-management' ); ?></option>
                            <option value="partial" <?php selected( $form_data['payment_status'] ?? '', 'partial' ); ?>><?php esc_html_e( 'Partial Payment', 'CTADZ-school-management' ); ?></option>
                            <option value="paid" <?php selected( $form_data['payment_status'] ?? '', 'paid' ); ?>><?php esc_html_e( 'Paid', 'CTADZ-school-management' ); ?></option>
                            <option value="overdue" <?php selected( $form_data['payment_status'] ?? '', 'overdue' ); ?>><?php esc_html_e( 'Overdue', 'CTADZ-school-management' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Current payment status for this enrollment.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="enrollment_notes"><?php esc_html_e( 'Notes', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <textarea id="enrollment_notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea( $form_data['notes'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Optional notes or comments about this enrollment.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( 
                    $is_edit ? __( 'Update Enrollment', 'CTADZ-school-management' ) : __( 'Create Enrollment', 'CTADZ-school-management' ), 
                    'primary', 
                    'sm_save_enrollment', 
                    false 
                ); ?>
                <a href="?page=school-management-enrollments" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'CTADZ-school-management' ); ?></a>
            </p>
            
            <p class="description">
                <span style="color: #d63638;">*</span> <?php esc_html_e( 'Required fields', 'CTADZ-school-management' ); ?>
            </p>

        <?php if ( ! $is_edit ) : // Only for new enrollments ?>
        <script>
        jQuery(document).ready(function($) {
            
            // Handle course selection change
            $('#enrollment_course').on('change', function() {
                var courseId = $(this).val();
                
                // Hide and reset course payment info
                $('#course_payment_info_row').hide();
                $('#course_payment_info').removeClass('subscription');
                
                if (!courseId) {
                    // Reset payment plan to default options
                    resetPaymentPlanOptions();
                    return;
                }
                
                // Show loading state (don't destroy the dropdown!)
                var $paymentPlan = $('#payment_plan');
                var $paymentPlanTd = $paymentPlan.closest('td');
                
                // Disable dropdown and show loading indicator
                $paymentPlan.prop('disabled', true);
                $paymentPlan.html('<option><?php esc_html_e( 'Loading payment options...', 'CTADZ-school-management' ); ?></option>');
                
                // Remove any existing description
                $paymentPlanTd.find('.description').remove();
                
                // Make AJAX call to get course payment info
                $.ajax({
                    url: smAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sm_get_course_payment_info',
                        nonce: smAjax.nonce,
                        course_id: courseId
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            updateCoursePaymentInfo(response.data);
                            updatePaymentPlanOptions(response.data);
                        } else {
                            alert(response.data.message || smAjax.strings.error);
                            resetPaymentPlanOptions();
                        }
                    },
                    error: function() {
                        alert(smAjax.strings.error);
                        resetPaymentPlanOptions();
                    }
                });
            });
            
            // Function to update course payment info display
            function updateCoursePaymentInfo(data) {
                var $infoBox = $('#course_payment_info');
                var $infoRow = $('#course_payment_info_row');
                
                // Update badge
                $infoBox.find('.sm-payment-model-badge').text(data.payment_model_label);
                
                // Update course name
                $infoBox.find('.sm-course-name').html('<strong>' + data.course_name + '</strong>');
                
                // Update payment details
                var detailsHtml = '';
                if (data.payment_model === 'full_payment') {
                    detailsHtml = '<?php esc_html_e( 'This course requires full payment upfront:', 'CTADZ-school-management' ); ?> <strong>' + data.total_price + '</strong>';
                } else if (data.payment_model === 'monthly_installments') {
                    detailsHtml = data.price_per_month + '<?php esc_html_e( '/month for ', 'CTADZ-school-management' ); ?>' + data.total_months + '<?php esc_html_e( ' months (Total: ', 'CTADZ-school-management' ); ?>' + data.total_price + ')';
                } else if (data.payment_model === 'monthly_subscription') {
                    detailsHtml = '<?php esc_html_e( 'Flexible monthly subscription: ', 'CTADZ-school-management' ); ?><strong>' + data.price_per_month + '<?php esc_html_e( '/month', 'CTADZ-school-management' ); ?></strong><br><em><?php esc_html_e( 'Student can cancel anytime', 'CTADZ-school-management' ); ?></em>';
                    $infoBox.addClass('subscription');
                }
                $infoBox.find('.sm-payment-details').html(detailsHtml);
                
                // Show the info box
                $infoRow.show();
            }
            
            // Function to update payment plan dropdown options
            function updatePaymentPlanOptions(data) {
                var $paymentPlan = $('#payment_plan');
                var $paymentPlanTd = $paymentPlan.closest('td');
                
                // Clear current options
                $paymentPlan.empty();
                
                // Re-enable the dropdown
                $paymentPlan.prop('disabled', false);
                
                // Add options based on available plans
                $.each(data.available_plans, function(index, plan) {
                    var optionText = data.plan_descriptions[plan] || plan;
                    $paymentPlan.append('<option value="' + plan + '">' + optionText + '</option>');
                });
                
                // Remove any existing description
                $paymentPlanTd.find('.description').remove();
                
                // Add description
                var descriptionHtml = '<p class="description">';
                if (data.available_plans.length === 1) {
                    descriptionHtml += '<?php esc_html_e( 'This course only allows this payment method.', 'CTADZ-school-management' ); ?>';
                } else {
                    descriptionHtml += '<?php esc_html_e( 'Choose how the student will pay for the course.', 'CTADZ-school-management' ); ?>';
                }
                descriptionHtml += '</p>';
                
                // Append description after the select element
                $paymentPlan.after(descriptionHtml);
            }
            
            // Function to reset payment plan options to default
            function resetPaymentPlanOptions() {
                var $paymentPlan = $('#payment_plan');
                var $paymentPlanTd = $paymentPlan.closest('td');
                
                // Re-enable dropdown
                $paymentPlan.prop('disabled', false);
                
                // Reset to default options
                $paymentPlan.empty();
                $paymentPlan.append('<option value="monthly"><?php esc_html_e( 'Monthly Payments', 'CTADZ-school-management' ); ?></option>');
                $paymentPlan.append('<option value="quarterly"><?php esc_html_e( 'Quarterly Payments (Every 3 months)', 'CTADZ-school-management' ); ?></option>');
                $paymentPlan.append('<option value="full"><?php esc_html_e( 'Full Payment (One-time)', 'CTADZ-school-management' ); ?></option>');
                
                // Remove existing description
                $paymentPlanTd.find('.description').remove();
                
                // Add default description
                var descriptionHtml = '<p class="description"><?php esc_html_e( 'Choose how the student will pay for the course.', 'CTADZ-school-management' ); ?></p>';
                $paymentPlan.after(descriptionHtml);
            }
        });

        // Check for family discount when student is selected (info only)
        $('#enrollment_student').on('change', function() {
            var studentId = $(this).val();
            if (studentId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sm_get_student_discount',
                        student_id: studentId
                    },
                    success: function(response) {
                        if (response.success && response.data.discount_percentage > 0) {
                            $('#sm-discount-message').html(response.data.message);
                            $('#sm-student-discount-info').slideDown();
                        } else {
                            $('#sm-student-discount-info').slideUp();
                        }
                    }
                });
            } else {
                $('#sm-student-discount-info').slideUp();
            }
        });
        </script>
        <?php endif; ?>
        </form>
        <?php
    }

    /**
     * AJAX handler to get student discount info
     */
    public static function ajax_get_student_discount() {
        $student_id = intval( $_POST['student_id'] ?? 0 );

        if ( $student_id <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid student ID', 'CTADZ-school-management' ) ] );
        }

        $discount_info = SM_Family_Discount::calculate_discount_for_student( $student_id );

        if ( $discount_info['percentage'] > 0 ) {
            $message = sprintf(
                __( 'Family Discount: %s%% (%d students in family)', 'CTADZ-school-management' ),
                number_format( $discount_info['percentage'], 1 ),
                $discount_info['family_count']
            );

            wp_send_json_success( [
                'discount_percentage' => $discount_info['percentage'],
                'family_count' => $discount_info['family_count'],
                'message' => $message
            ] );
        } else {
            wp_send_json_success( [
                'discount_percentage' => 0,
                'message' => ''
            ] );
        }
    }

}

// Instantiate class
new SM_Enrollments_Page();

// Register AJAX handlers
add_action( 'wp_ajax_sm_get_student_discount', [ 'SM_Enrollments_Page', 'ajax_get_student_discount' ] );
