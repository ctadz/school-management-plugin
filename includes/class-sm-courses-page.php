<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Courses_Page {
    /**
     * Render the Courses page
     */
    public static function render_courses_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_courses';

        // Handle delete action
        if ( isset( $_GET['delete'] ) && check_admin_referer( 'sm_delete_course_' . intval( $_GET['delete'] ) ) ) {
            $deleted = $wpdb->delete( $table, [ 'id' => intval( $_GET['delete'] ) ] );
            if ( $deleted ) {
                echo '<div class="updated notice"><p>' . esc_html__( 'Course deleted successfully.', 'CTADZ-school-management' ) . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html__( 'Error deleting course.', 'CTADZ-school-management' ) . '</p></div>';
            }
        }

        // Handle form submission
        if ( isset( $_POST['sm_save_course'] ) && check_admin_referer( 'sm_save_course_action', 'sm_save_course_nonce' ) ) {
            $validation_result = self::validate_course_data( $_POST );
            
            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];
                
                if ( ! empty( $_POST['course_id'] ) ) {
                    $updated = $wpdb->update( $table, $data, [ 'id' => intval( $_POST['course_id'] ) ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Course updated successfully.', 'CTADZ-school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-courses"; }, 2000);</script>';
                    }
                } else {
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Course added successfully.', 'CTADZ-school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-courses"; }, 2000);</script>';
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
        $course = null;

        if ( $action === 'edit' && isset( $_GET['course_id'] ) ) {
            $course = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['course_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Courses', 'CTADZ-school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'add':
                    self::render_course_form( null );
                    break;
                case 'edit':
                    self::render_course_form( $course );
                    break;
                default:
                    self::render_courses_list();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Validate course data
     */
    private static function validate_course_data( $post_data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_courses';
        $errors = [];
        
        // Sanitize data
        $name = sanitize_text_field( trim( $post_data['name'] ?? '' ) );
        $description = sanitize_textarea_field( trim( $post_data['description'] ?? '' ) );
        $description_file = esc_url_raw( trim( $post_data['description_file'] ?? '' ) );
        $language = sanitize_text_field( trim( $post_data['language'] ?? '' ) );
        $level_id = intval( $post_data['level_id'] ?? 0 );
        $teacher_id = intval( $post_data['teacher_id'] ?? 0 );
        $classroom_id = ! empty( $post_data['classroom_id'] ) ? intval( $post_data['classroom_id'] ) : null;
        $session_duration_hours = intval( $post_data['session_duration_hours'] ?? 0 );
        $session_duration_minutes = intval( $post_data['session_duration_minutes'] ?? 0 );
        $hours_per_week = floatval( $post_data['hours_per_week'] ?? 0 );
        $total_weeks = intval( $post_data['total_weeks'] ?? 0 );
        $total_months = intval( $post_data['total_months'] ?? 0 );
        $price_per_month = floatval( $post_data['price_per_month'] ?? 0 );
        $total_price = floatval( $post_data['total_price'] ?? 0 );
        $status = sanitize_text_field( trim( $post_data['status'] ?? 'upcoming' ) );
        $certification_type = sanitize_text_field( trim( $post_data['certification_type'] ?? '' ) );
        $certification_other = sanitize_text_field( trim( $post_data['certification_other'] ?? '' ) );
        $is_active = isset( $post_data['is_active'] ) ? 1 : 0;
        $course_id = intval( $post_data['course_id'] ?? 0 );
        
        // Sanitize payment models (checkboxes acting as exclusive selection)
        $payment_model = '';
        if ( ! empty( $post_data['payment_models'] ) && is_array( $post_data['payment_models'] ) ) {
            // Take the first (and should be only) selected model
            $selected = sanitize_text_field( $post_data['payment_models'][0] );
            $valid_models = [ 'full_payment', 'monthly_installments', 'monthly_subscription' ];
            if ( in_array( $selected, $valid_models ) ) {
                $payment_model = $selected;
            }
        }
        
        // Set default if nothing selected
        if ( empty( $payment_model ) ) {
            $payment_model = 'monthly_installments';
        }        
        
        // Required field validation
        if ( empty( $name ) ) {
            $errors[] = __( 'Course name is required.', 'CTADZ-school-management' );
        } elseif ( strlen( $name ) < 3 ) {
            $errors[] = __( 'Course name must be at least 3 characters long.', 'CTADZ-school-management' );
        }

        if ( empty( $description ) ) {
            $errors[] = __( 'Course description is required.', 'CTADZ-school-management' );
        }

        if ( empty( $language ) ) {
            $errors[] = __( 'Language is required.', 'CTADZ-school-management' );
        }

        if ( $level_id <= 0 ) {
            $errors[] = __( 'Level is required.', 'CTADZ-school-management' );
        }

        if ( $teacher_id <= 0 ) {
            $errors[] = __( 'Teacher is required.', 'CTADZ-school-management' );
        }

        if ( $session_duration_hours <= 0 && $session_duration_minutes <= 0 ) {
            $errors[] = __( 'Session duration is required.', 'CTADZ-school-management' );
        }

        if ( $hours_per_week <= 0 ) {
            $errors[] = __( 'Hours per week is required.', 'CTADZ-school-management' );
        }

        if ( $total_weeks <= 0 ) {
            $errors[] = __( 'Total weeks is required.', 'CTADZ-school-management' );
        }

        if ( $total_months <= 0 ) {
            $errors[] = __( 'Total months is required.', 'CTADZ-school-management' );
        }

        if ( $price_per_month <= 0 ) {
            $errors[] = __( 'Price per month is required.', 'CTADZ-school-management' );
        }

        if ( $total_price <= 0 ) {
            $errors[] = __( 'Total price is required.', 'CTADZ-school-management' );
        }
        
        // Validate payment models
        if ( empty( $payment_model ) ) {
            $errors[] = __( 'Please select a payment model.', 'CTADZ-school-management' );
        }

        // Check for duplicate course name
        if ( ! empty( $name ) ) {
            $duplicate_query = "SELECT id FROM $table WHERE LOWER(name) = LOWER(%s)";
            $params = [ $name ];
            
            if ( $course_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $params[] = $course_id;
            }
            
            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $params ) );
            if ( $duplicate ) {
                $errors[] = sprintf( __( 'A course with the name "%s" already exists.', 'CTADZ-school-management' ), $name );
            }
        }

        // Validate status
        $valid_statuses = [ 'upcoming', 'in_progress', 'completed', 'inactive' ];
        if ( ! in_array( $status, $valid_statuses ) ) {
            $errors[] = __( 'Invalid course status.', 'CTADZ-school-management' );
        }

        // Validate certification type if provided
        if ( ! empty( $certification_type ) ) {
            $valid_cert_types = [ 'school_diploma', 'state_diploma', 'other' ];
            if ( ! in_array( $certification_type, $valid_cert_types ) ) {
                $errors[] = __( 'Invalid certification type.', 'CTADZ-school-management' );
            }
    
        // If "other" is selected, the custom name is required
        if ( $certification_type === 'other' && empty( $certification_other ) ) {
            $errors[] = __( 'Please specify the certification name when selecting "Other".', 'CTADZ-school-management' );
            }
        }

        // Validate file URL if provided
        if ( ! empty( $description_file ) && ! filter_var( $description_file, FILTER_VALIDATE_URL ) ) {
            $errors[] = __( 'Please provide a valid file URL.', 'CTADZ-school-management' );
        }

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'name' => $name,
                    'description' => $description,
                    'description_file' => $description_file ?: null,
                    'language' => $language,
                    'level_id' => $level_id,
                    'teacher_id' => $teacher_id,
                    'classroom_id' => $classroom_id,
                    'session_duration_hours' => $session_duration_hours,
                    'session_duration_minutes' => $session_duration_minutes,
                    'hours_per_week' => $hours_per_week,
                    'total_weeks' => $total_weeks,
                    'total_months' => $total_months,
                    'price_per_month' => $price_per_month,
                    'total_price' => $total_price,
                    'payment_model' => $payment_model,
                    'certification_type' => $certification_type ?: null,
                    'certification_other' => $certification_other ?: null,
                    'status' => $status,
                    'is_active' => $is_active,
                ]
            ];
        }

        return [ 'success' => false, 'errors' => $errors ];
    }

    /**
     * Render courses list
     */
    private static function render_courses_list() {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'sm_courses';
        $levels_table = $wpdb->prefix . 'sm_levels';
        $teachers_table = $wpdb->prefix . 'sm_teachers';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';

        // Get search parameter
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // Get sorting parameters
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'name';
        $order = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), [ 'ASC', 'DESC' ] ) ? strtoupper( $_GET['order'] ) : 'ASC';

        // Get filter parameter
        $filter_payment_model = isset( $_GET['filter_payment_model'] ) ? sanitize_text_field( $_GET['filter_payment_model'] ) : '';

        // Pagination
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        // Build WHERE clause for filtering and search
        $where_clauses = [];
        
        // Search condition
        if ( ! empty( $search ) ) {
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $where_clauses[] = $wpdb->prepare( 
                "(c.name LIKE %s OR c.language LIKE %s OR CONCAT(t.first_name, ' ', t.last_name) LIKE %s)", 
                $search_term, 
                $search_term,
                $search_term 
            );
        }
        
        // Payment model filter
        if ( ! empty( $filter_payment_model ) ) {
            $where_clauses[] = $wpdb->prepare( "c.payment_model = %s", $filter_payment_model );
        }
        
        $where_clause = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

        // Get total courses count (with filter applied)
        $total_courses = $wpdb->get_var( "
            SELECT COUNT(DISTINCT c.id) 
            FROM $courses_table c 
            LEFT JOIN $teachers_table t ON c.teacher_id = t.id 
            $where_clause
        " );
        $total_pages = ceil( $total_courses / $per_page );

        // Validate and set ORDER BY clause
        $valid_columns = [
            'name' => 'c.name',
            'language' => 'c.language',
            'level' => 'l.name',
            'teacher' => 'teacher_name',
            'duration' => 'c.total_weeks',
            'price' => 'c.price_per_month',
            'enrollments' => 'enrollment_count',
            'status' => 'c.status'
        ];
        $orderby_column = isset( $valid_columns[ $orderby ] ) ? $valid_columns[ $orderby ] : 'c.name';
        $order_clause = "$orderby_column $order";

        // Get courses with level, teacher, classroom names, and enrollment count
        $query = "
            SELECT c.*, 
                   l.name as level_name, 
                   CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
                   cr.name as classroom_name,
                   COUNT(DISTINCT e.id) as enrollment_count
            FROM $courses_table c 
            LEFT JOIN $levels_table l ON c.level_id = l.id 
            LEFT JOIN $teachers_table t ON c.teacher_id = t.id 
            LEFT JOIN {$wpdb->prefix}sm_classrooms cr ON c.classroom_id = cr.id
            LEFT JOIN $enrollments_table e ON c.id = e.course_id AND e.status != 'cancelled'
            $where_clause
            GROUP BY c.id
            ORDER BY $order_clause
            LIMIT %d OFFSET %d
        ";
        
        $courses = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );

        // Helper function to generate sortable column URL
        $get_sort_url = function( $column ) use ( $orderby, $order, $search, $filter_payment_model ) {
            $new_order = ( $orderby === $column && $order === 'ASC' ) ? 'DESC' : 'ASC';
            $url = add_query_arg( [
                'page' => 'school-management-courses',
                'orderby' => $column,
                'order' => $new_order,
            ] );
            
            if ( ! empty( $search ) ) {
                $url = add_query_arg( 's', urlencode( $search ), $url );
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
        
        /* Hide the double arrow when actively sorted */
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
                <h2 style="margin: 0;"><?php esc_html_e( 'Courses List', 'CTADZ-school-management' ); ?></h2>
                <p class="description">
                    <?php 
                    $active_filters = ! empty( $search ) || ! empty( $filter_payment_model );
                    if ( $active_filters ) {
                        if ( ! empty( $search ) && ! empty( $filter_payment_model ) ) {
                            $filter_labels = [
                                'full_payment' => __( 'Full Payment', 'CTADZ-school-management' ),
                                'monthly_installments' => __( 'Monthly Installments', 'CTADZ-school-management' ),
                                'monthly_subscription' => __( 'Monthly Subscription', 'CTADZ-school-management' ),
                            ];
                            printf( 
                                esc_html__( 'Showing %d courses matching "%s" with payment model: %s', 'CTADZ-school-management' ), 
                                $total_courses,
                                esc_html( $search ),
                                '<strong>' . esc_html( $filter_labels[ $filter_payment_model ] ?? $filter_payment_model ) . '</strong>'
                            );
                        } elseif ( ! empty( $search ) ) {
                            printf( esc_html__( 'Showing %d courses matching "%s"', 'CTADZ-school-management' ), $total_courses, esc_html( $search ) );
                        } elseif ( ! empty( $filter_payment_model ) ) {
                            $filter_labels = [
                                'full_payment' => __( 'Full Payment', 'CTADZ-school-management' ),
                                'monthly_installments' => __( 'Monthly Installments', 'CTADZ-school-management' ),
                                'monthly_subscription' => __( 'Monthly Subscription', 'CTADZ-school-management' ),
                            ];
                            printf( 
                                esc_html__( 'Showing %d courses with payment model: %s', 'CTADZ-school-management' ), 
                                $total_courses,
                                '<strong>' . esc_html( $filter_labels[ $filter_payment_model ] ?? $filter_payment_model ) . '</strong>'
                            );
                        }
                        echo ' <a href="?page=school-management-courses" style="margin-left: 10px;">' . esc_html__( '[Clear all filters]', 'CTADZ-school-management' ) . '</a>';
                    } else {
                        printf( esc_html__( 'Total: %d courses', 'CTADZ-school-management' ), $total_courses );
                    }
                    ?>
                </p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <!-- Payment Model Filter -->
                <select id="filter_payment_model" onchange="applyFiltersAndSort();">
                    <option value=""><?php esc_html_e( 'All Payment Models', 'CTADZ-school-management' ); ?></option>
                    <option value="full_payment" <?php selected( $filter_payment_model, 'full_payment' ); ?>>
                        <?php esc_html_e( 'Full Payment', 'CTADZ-school-management' ); ?>
                    </option>
                    <option value="monthly_installments" <?php selected( $filter_payment_model, 'monthly_installments' ); ?>>
                        <?php esc_html_e( 'Monthly Installments', 'CTADZ-school-management' ); ?>
                    </option>
                    <option value="monthly_subscription" <?php selected( $filter_payment_model, 'monthly_subscription' ); ?>>
                        <?php esc_html_e( 'Monthly Subscription', 'CTADZ-school-management' ); ?>
                    </option>
                </select>
                
                <a href="?page=school-management-courses&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Course', 'CTADZ-school-management' ); ?>
                </a>
            </div>
        </div>

        <!-- Search Box -->
        <div class="tablenav top" style="margin-bottom: 15px;">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="school-management-courses">
                <?php if ( ! empty( $orderby ) ) : ?>
                    <input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>">
                    <input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>">
                <?php endif; ?>
                <?php if ( ! empty( $filter_payment_model ) ) : ?>
                    <input type="hidden" name="filter_payment_model" value="<?php echo esc_attr( $filter_payment_model ); ?>">
                <?php endif; ?>
                <input type="search" 
                       name="s" 
                       value="<?php echo esc_attr( $search ); ?>" 
                       placeholder="<?php esc_attr_e( 'Search courses by name, language, or teacher...', 'CTADZ-school-management' ); ?>"
                       style="width: 300px; margin-right: 5px;">
                <button type="submit" class="button"><?php esc_html_e( 'Search', 'CTADZ-school-management' ); ?></button>
                <?php if ( ! empty( $search ) ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'school-management-courses', 'filter_payment_model' => $filter_payment_model ), admin_url( 'admin.php' ) ) ); ?>" class="button" style="margin-left: 5px;">
                        <?php esc_html_e( 'Clear', 'CTADZ-school-management' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <script>
        function applyFiltersAndSort() {
            var urlParams = new URLSearchParams(window.location.search);
            var paymentModel = document.getElementById('filter_payment_model').value;
            
            var url = '?page=school-management-courses';
            
            var search = urlParams.get('s');
            var orderby = urlParams.get('orderby');
            var order = urlParams.get('order');
            
            if (search) url += '&s=' + encodeURIComponent(search);
            if (orderby) url += '&orderby=' + orderby;
            if (order) url += '&order=' + order;
            if (paymentModel) url += '&filter_payment_model=' + paymentModel;
            
            window.location.href = url;
        }
        </script>

        <?php if ( $courses ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="<?php echo $orderby === 'name' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'name' ); ?>">
                                <?php esc_html_e( 'Course Name', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'name' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'language' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'language' ); ?>">
                                <?php esc_html_e( 'Language', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'language' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'level' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'level' ); ?>">
                                <?php esc_html_e( 'Level', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'level' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'teacher' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'teacher' ); ?>">
                                <?php esc_html_e( 'Teacher', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'teacher' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'duration' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'duration' ); ?>">
                                <?php esc_html_e( 'Duration', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'duration' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable"><?php esc_html_e( 'Payment Model', 'CTADZ-school-management' ); ?></th>
                        <th class="<?php echo $orderby === 'price' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'price' ); ?>">
                                <?php esc_html_e( 'Price/Month', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'price' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'enrollments' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'enrollments' ); ?>">
                                <?php esc_html_e( 'Enrollments', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'enrollments' ); ?>
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
                    <?php foreach ( $courses as $course ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $course->name ); ?></strong></td>
                            <td><?php echo esc_html( $course->language ); ?></td>
                            <td><?php echo esc_html( $course->level_name ?: '—' ); ?></td>
                            <td><?php echo esc_html( $course->teacher_name ?: '—' ); ?></td>
                            <td><?php echo esc_html( $course->total_weeks . ' ' . __( 'weeks', 'CTADZ-school-management' ) ); ?></td>
                            <td>
                                <?php
                                // Payment model display with icons and colors
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
                                
                                $model = $course->payment_model ?? 'monthly_installments';
                                $display = $payment_model_display[ $model ] ?? $payment_model_display['monthly_installments'];
                                ?>
                                <span style="display: inline-flex; align-items: center; padding: 4px 10px; background: <?php echo esc_attr( $display['bg'] ); ?>; border-radius: 4px; font-size: 12px;">
                                    <span class="dashicons <?php echo esc_attr( $display['icon'] ); ?>" style="font-size: 14px; color: <?php echo esc_attr( $display['color'] ); ?>; margin-right: 5px;"></span>
                                    <strong style="color: <?php echo esc_attr( $display['color'] ); ?>;"><?php echo esc_html( $display['label'] ); ?></strong>
                                </span>
                            </td>
                            <td><?php echo esc_html( number_format( $course->price_per_month, 2 ) ); ?></td>
                            <td>
                                <?php
                                $count = intval( $course->enrollment_count );
                                if ( $count > 0 ) {
                                    echo '<span style="color: #2271b1;"><strong>' . esc_html( $count ) . '</strong> ' . esc_html( _n( 'student', 'students', $count, 'CTADZ-school-management' ) ) . '</span>';
                                } else {
                                    echo '<span style="color: #999;">' . esc_html__( 'No enrollments', 'CTADZ-school-management' ) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $status_colors = [
                                    'upcoming' => '#f0ad4e',
                                    'in_progress' => '#46b450',
                                    'completed' => '#00a0d2',
                                    'inactive' => '#dc3232'
                                ];
                                $color = $status_colors[ $course->status ] ?? '#666';
                                $status_labels = [
                                    'upcoming' => __( 'Upcoming', 'CTADZ-school-management' ),
                                    'in_progress' => __( 'In Progress', 'CTADZ-school-management' ),
                                    'completed' => __( 'Completed', 'CTADZ-school-management' ),
                                    'inactive' => __( 'Inactive', 'CTADZ-school-management' )
                                ];
                                $label = $status_labels[ $course->status ] ?? $course->status;
                                ?>
                                <span style="color: <?php echo esc_attr( $color ); ?>;">● <?php echo esc_html( $label ); ?></span>
                            </td>
                            <td>
                                <a href="?page=school-management-courses&action=edit&course_id=<?php echo intval( $course->id ); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                </a>
                                <?php
                                $delete_url = wp_nonce_url( 
                                    '?page=school-management-courses&delete=' . intval( $course->id ), 
                                    'sm_delete_course_' . intval( $course->id ) 
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this course?', 'CTADZ-school-management' ) ); ?>')">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle; color: #d63638;"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            // Pagination
            if ( $total_pages > 1 ) {
                $base_url = remove_query_arg( 'paged' );
                
                $pagination_args = [
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => __( '« Previous', 'CTADZ-school-management' ),
                    'next_text' => __( 'Next »', 'CTADZ-school-management' ),
                    'total' => $total_pages,
                    'current' => $current_page,
                ];
                
                // Preserve search, sorting, and filter in pagination
                $add_args = [];
                if ( ! empty( $search ) ) {
                    $add_args['s'] = urlencode( $search );
                }
                if ( ! empty( $orderby ) ) {
                    $add_args['orderby'] = $orderby;
                    $add_args['order'] = $order;
                }
                if ( ! empty( $filter_payment_model ) ) {
                    $add_args['filter_payment_model'] = $filter_payment_model;
                }
                
                if ( ! empty( $add_args ) ) {
                    $pagination_args['add_args'] = $add_args;
                }
                
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                echo paginate_links( $pagination_args );
                echo '</div></div>';
            }
            ?>

        <?php else : ?>
            <div class="sm-empty-state" style="text-align: center; padding: 60px 20px; background: #fafafa; border: 1px dashed #ddd; border-radius: 4px;">
                <span class="dashicons dashicons-book" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></span>
                <h3><?php esc_html_e( 'No Courses Found', 'CTADZ-school-management' ); ?></h3>
                <p>
                    <?php 
                    if ( ! empty( $filter_payment_model ) ) {
                        esc_html_e( 'No courses match the selected payment model filter.', 'CTADZ-school-management' );
                        echo '<br><a href="?page=school-management-courses">' . esc_html__( 'View all courses', 'CTADZ-school-management' ) . '</a>';
                    } else {
                        esc_html_e( 'Create your first course to get started.', 'CTADZ-school-management' );
                    }
                    ?>
                </p>
                <?php if ( empty( $filter_payment_model ) ) : ?>
                    <a href="?page=school-management-courses&action=add" class="button button-primary">
                        <?php esc_html_e( 'Add First Course', 'CTADZ-school-management' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif;
    }

    /**
     * Render course form
     */
    private static function render_course_form( $course = null ) {
        global $wpdb;
        $is_edit = ! empty( $course );
        
        // Get levels and teachers
        $levels = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_levels WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );
        $teachers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_teachers WHERE is_active = 1 ORDER BY last_name ASC, first_name ASC" );
        $classrooms = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_classrooms WHERE is_active = 1 ORDER BY name ASC" );

        $form_data = [];
        if ( isset( $_POST['sm_save_course'] ) ) {
            $form_data = [
                'name' => sanitize_text_field( $_POST['name'] ?? '' ),
                'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
                'description_file' => esc_url_raw( $_POST['description_file'] ?? '' ),
                'language' => sanitize_text_field( $_POST['language'] ?? '' ),
                'level_id' => intval( $_POST['level_id'] ?? 0 ),
                'teacher_id' => intval( $_POST['teacher_id'] ?? 0 ),
                'classroom_id' => intval( $_POST['classroom_id'] ?? 0 ),
                'session_duration_hours' => intval( $_POST['session_duration_hours'] ?? 0 ),
                'session_duration_minutes' => intval( $_POST['session_duration_minutes'] ?? 0 ),
                'hours_per_week' => floatval( $_POST['hours_per_week'] ?? 0 ),
                'total_weeks' => intval( $_POST['total_weeks'] ?? 0 ),
                'total_months' => intval( $_POST['total_months'] ?? 0 ),
                'price_per_month' => floatval( $_POST['price_per_month'] ?? 0 ),
                'total_price' => floatval( $_POST['total_price'] ?? 0 ),
                'certification_type' => sanitize_text_field( $_POST['certification_type'] ?? '' ),
                'certification_other' => sanitize_text_field( $_POST['certification_other'] ?? '' ),
                'status' => sanitize_text_field( $_POST['status'] ?? 'upcoming' ),
                'is_active' => isset( $_POST['is_active'] ),
                'payment_model' => isset( $_POST['payment_models'] ) && is_array( $_POST['payment_models'] ) ? $_POST['payment_models'][0] : 'monthly_installments',
            ];
        } elseif ( $course ) {
            $form_data = [
                'name' => $course->name,
                'description' => $course->description,
                'description_file' => $course->description_file,
                'language' => $course->language,
                'level_id' => $course->level_id,
                'teacher_id' => $course->teacher_id,
                'classroom_id' => $course->classroom_id,
                'session_duration_hours' => $course->session_duration_hours,
                'session_duration_minutes' => $course->session_duration_minutes,
                'hours_per_week' => $course->hours_per_week,
                'total_weeks' => $course->total_weeks,
                'total_months' => $course->total_months,
                'price_per_month' => $course->price_per_month,
                'total_price' => $course->total_price,
                'certification_type' => $course->certification_type,
                'certification_other' => $course->certification_other,
                'status' => $course->status,
                'is_active' => $course->is_active,
                'payment_model' => $course->payment_model ?? 'monthly_installments',
            ];
        }
        
        ?>
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-courses" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Courses', 'CTADZ-school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Course', 'CTADZ-school-management' ) : esc_html__( 'Add New Course', 'CTADZ-school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_course_action', 'sm_save_course_nonce' ); ?>
            <input type="hidden" name="course_id" value="<?php echo esc_attr( $course->id ?? '' ); ?>" />

            <h3><?php esc_html_e( 'Basic Information', 'CTADZ-school-management' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="course_name"><?php esc_html_e( 'Course Name', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="course_name" name="name" value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'Course name must be unique.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_description"><?php esc_html_e( 'Description', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <textarea id="course_description" name="description" rows="5" class="large-text" required><?php echo esc_textarea( $form_data['description'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Brief description of the course content and objectives.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_description_file"><?php esc_html_e( 'Description File (PDF)', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="hidden" id="course_description_file" name="description_file" value="<?php echo esc_attr( $form_data['description_file'] ?? '' ); ?>" />
        
                        <?php if ( ! empty( $form_data['description_file'] ) ) : 
                            $file_url = $form_data['description_file'];
                            $file_name = basename( parse_url( $file_url, PHP_URL_PATH ) );
                        ?>
                            <div style="padding: 15px; background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <span class="dashicons dashicons-media-document" style="font-size: 24px; color: #d63638;"></span>
                                    <div style="flex: 1;">
                                        <strong><?php echo esc_html( $file_name ); ?></strong>
                                        <p style="margin: 5px 0 0 0; color: #666; font-size: 12px;"><?php esc_html_e( 'PDF Document', 'CTADZ-school-management' ); ?></p>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 5px;">
                                    <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" class="button button-small">
                                        <span class="dashicons dashicons-visibility" style="vertical-align: middle;"></span>
                                        <?php esc_html_e( 'View', 'CTADZ-school-management' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( $file_url ); ?>" download class="button button-small">
                                        <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                                        <?php esc_html_e( 'Download', 'CTADZ-school-management' ); ?>
                                    </a>
                                    <button type="button" class="button button-small sm-remove-file" data-target="course_description_file" style="color: #d63638;">
                                        <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                        <?php esc_html_e( 'Remove', 'CTADZ-school-management' ); ?>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="button sm-upload-file" data-target="course_description_file">
                                <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                                <?php esc_html_e( 'Replace PDF', 'CTADZ-school-management' ); ?>
                            </button>
                        <?php else : ?>
                            <button type="button" class="button sm-upload-file" data-target="course_description_file">
                                <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                                <?php esc_html_e( 'Upload PDF', 'CTADZ-school-management' ); ?>
                            </button>
                        <?php endif; ?>
        
                        <p class="description"><?php esc_html_e( 'Optional: Upload a detailed PDF description of the course.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="course_language"><?php esc_html_e( 'Language', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="course_language" name="language" required>
                            <option value=""><?php esc_html_e( 'Select Language', 'CTADZ-school-management' ); ?></option>
                            <option value="French" <?php selected( $form_data['language'] ?? '', 'French' ); ?>><?php esc_html_e( 'French', 'CTADZ-school-management' ); ?></option>
                            <option value="English" <?php selected( $form_data['language'] ?? '', 'English' ); ?>><?php esc_html_e( 'English', 'CTADZ-school-management' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_level"><?php esc_html_e( 'Level', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="course_level" name="level_id" required>
                            <option value=""><?php esc_html_e( 'Select Level', 'CTADZ-school-management' ); ?></option>
                            <?php foreach ( $levels as $level ) : ?>
                                <option value="<?php echo intval( $level->id ); ?>" <?php selected( $form_data['level_id'] ?? 0, $level->id ); ?>>
                                    <?php echo esc_html( $level->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <a href="?page=school-management-levels" target="_blank"><?php esc_html_e( 'Manage levels', 'CTADZ-school-management' ); ?></a>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_teacher"><?php esc_html_e( 'Teacher', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="course_teacher" name="teacher_id" required>
                            <option value=""><?php esc_html_e( 'Select Teacher', 'CTADZ-school-management' ); ?></option>
                            <?php foreach ( $teachers as $teacher ) : ?>
                                <option value="<?php echo intval( $teacher->id ); ?>" <?php selected( $form_data['teacher_id'] ?? 0, $teacher->id ); ?>>
                                    <?php echo esc_html( $teacher->first_name . ' ' . $teacher->last_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <a href="?page=school-management-teachers" target="_blank"><?php esc_html_e( 'Manage teachers', 'CTADZ-school-management' ); ?></a>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_classroom"><?php esc_html_e( 'Classroom', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <select id="course_classroom" name="classroom_id">
                            <option value=""><?php esc_html_e( 'No Classroom Assigned', 'CTADZ-school-management' ); ?></option>
                            <?php foreach ( $classrooms as $classroom ) : ?>
                                <option value="<?php echo intval( $classroom->id ); ?>" <?php selected( $form_data['classroom_id'] ?? 0, $classroom->id ); ?>>
                                    <?php echo esc_html( $classroom->name ); ?>
                                    <?php if ( $classroom->location ) : ?>
                                        - <?php echo esc_html( $classroom->location ); ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Optional: Assign this course to a specific classroom.', 'CTADZ-school-management' ); ?>
                            <a href="?page=school-management-classrooms" target="_blank"><?php esc_html_e( 'Manage classrooms', 'CTADZ-school-management' ); ?></a>
                        </p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Duration & Schedule', 'CTADZ-school-management' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Session Duration', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" name="session_duration_hours" value="<?php echo esc_attr( $form_data['session_duration_hours'] ?? 0 ); ?>" min="0" max="24" style="width: 80px;" />
                        <span><?php esc_html_e( 'hours', 'CTADZ-school-management' ); ?></span>
                        
                        <input type="number" name="session_duration_minutes" value="<?php echo esc_attr( $form_data['session_duration_minutes'] ?? 0 ); ?>" min="0" max="59" style="width: 80px; margin-left: 10px;" />
                        <span><?php esc_html_e( 'minutes', 'CTADZ-school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Duration of each individual class session (e.g., 1h 30min).', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_hours_per_week"><?php esc_html_e( 'Hours Per Week', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_hours_per_week" name="hours_per_week" value="<?php echo esc_attr( $form_data['hours_per_week'] ?? 0 ); ?>" min="0" step="0.5" required />
                        <span><?php esc_html_e( 'hours', 'CTADZ-school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Total teaching hours per week (e.g., 3.5 for two 1h45min sessions).', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_total_weeks"><?php esc_html_e( 'Total Weeks', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_total_weeks" name="total_weeks" value="<?php echo esc_attr( $form_data['total_weeks'] ?? 0 ); ?>" min="1" required />
                        <span><?php esc_html_e( 'weeks', 'CTADZ-school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Total duration of the course in weeks.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_total_months"><?php esc_html_e( 'Total Months', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_total_months" name="total_months" value="<?php echo esc_attr( $form_data['total_months'] ?? 0 ); ?>" min="1" required />
                        <span><?php esc_html_e( 'months', 'CTADZ-school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Total duration of the course in months.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Pricing', 'CTADZ-school-management' ); ?></h3>
            <table class="form-table">
                <!-- PAYMENT MODELS - FIRST! -->
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Payment Models', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <?php
                        // Get existing payment models (comma-separated string)
                        $existing_models = isset( $form_data['payment_model'] ) ? [ $form_data['payment_model'] ] : ['monthly_installments'];
                        $existing_models = array_map( 'trim', $existing_models );
                        ?>
                        
                        <fieldset>
                            <legend class="screen-reader-text"><?php esc_html_e( 'Select available payment models', 'CTADZ-school-management' ); ?></legend>
                            
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="payment_models[]" value="full_payment" <?php checked( in_array( 'full_payment', $existing_models ) ); ?> />
                                <strong><?php esc_html_e( 'Full Payment', 'CTADZ-school-management' ); ?></strong>
                                <span style="color: #666; display: block; margin-left: 25px;">
                                    <?php esc_html_e( 'Student pays entire course price upfront in one payment', 'CTADZ-school-management' ); ?>
                                </span>
                            </label>
                            
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="payment_models[]" value="monthly_installments" <?php checked( in_array( 'monthly_installments', $existing_models ) ); ?> />
                                <strong><?php esc_html_e( 'Monthly Installments', 'CTADZ-school-management' ); ?></strong>
                                <span style="color: #666; display: block; margin-left: 25px;">
                                    <?php esc_html_e( 'Student commits to full course duration, pays monthly on anniversary date (mandatory)', 'CTADZ-school-management' ); ?>
                                </span>
                            </label>
                            
                            <label style="display: block; margin-bottom: 10px;">
                                <input type="checkbox" name="payment_models[]" value="monthly_subscription" <?php checked( in_array( 'monthly_subscription', $existing_models ) ); ?> />
                                <strong><?php esc_html_e( 'Monthly Subscription', 'CTADZ-school-management' ); ?></strong>
                                <span style="color: #666; display: block; margin-left: 25px;">
                                    <?php esc_html_e( 'Ongoing monthly payments, student can stop anytime (flexible, no commitment)', 'CTADZ-school-management' ); ?>
                                </span>
                            </label>
                        </fieldset>
                        
                        <p class="description" style="margin-top: 10px;">
                            <strong><?php esc_html_e( 'Select payment model(s) first - fields below will adjust accordingly.', 'CTADZ-school-management' ); ?></strong>
                        </p>
                    </td>
                </tr>

                <!-- PRICE PER MONTH - Always visible -->
                <tr>
                    <th scope="row">
                        <label for="course_price_per_month"><?php esc_html_e( 'Price Per Month', 'CTADZ-school-management' ); ?> <span class="sm-required-star" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_price_per_month" name="price_per_month" value="<?php echo esc_attr( $form_data['price_per_month'] ?? 0 ); ?>" min="0" step="0.01" required />
                        <p class="description"><?php esc_html_e( 'Monthly tuition fee for this course.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <!-- TOTAL COURSE PRICE - Hidden for subscription-only -->
                <tr id="total_price_row">
                    <th scope="row">
                        <label for="course_total_price"><?php esc_html_e( 'Total Course Price', 'CTADZ-school-management' ); ?> <span class="sm-required-star" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_total_price" name="total_price" value="<?php echo esc_attr( $form_data['total_price'] ?? 0 ); ?>" min="0" step="0.01" required />
                        <p class="description"><?php esc_html_e( 'Total price for the entire course duration (for full payment or installments).', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Certification', 'CTADZ-school-management' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="course_certification_type"><?php esc_html_e( 'Certification Delivered', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <select id="course_certification_type" name="certification_type">
                            <option value=""><?php esc_html_e( 'No Certification', 'CTADZ-school-management' ); ?></option>
                            <option value="school_diploma" <?php selected( $form_data['certification_type'] ?? '', 'school_diploma' ); ?>><?php esc_html_e( 'School Diploma', 'CTADZ-school-management' ); ?></option>
                            <option value="state_diploma" <?php selected( $form_data['certification_type'] ?? '', 'state_diploma' ); ?>><?php esc_html_e( 'State Diploma', 'CTADZ-school-management' ); ?></option>
                            <option value="other" <?php selected( $form_data['certification_type'] ?? '', 'other' ); ?>><?php esc_html_e( 'Other', 'CTADZ-school-management' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Type of certification or diploma awarded upon course completion.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr id="certification_other_row" style="<?php echo ( $form_data['certification_type'] ?? '' ) === 'other' ? '' : 'display:none;'; ?>">
                    <th scope="row">
                        <label for="course_certification_other"><?php esc_html_e( 'Certification Name', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="course_certification_other" name="certification_other" value="<?php echo esc_attr( $form_data['certification_other'] ?? '' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Specify the name of the certification or entity that delivers it.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Status & Availability', 'CTADZ-school-management' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="course_status"><?php esc_html_e( 'Course Status', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="course_status" name="status" required>
                            <option value="upcoming" <?php selected( $form_data['status'] ?? 'upcoming', 'upcoming' ); ?>><?php esc_html_e( 'Upcoming', 'CTADZ-school-management' ); ?></option>
                            <option value="in_progress" <?php selected( $form_data['status'] ?? '', 'in_progress' ); ?>><?php esc_html_e( 'In Progress', 'CTADZ-school-management' ); ?></option>
                            <option value="completed" <?php selected( $form_data['status'] ?? '', 'completed' ); ?>><?php esc_html_e( 'Completed', 'CTADZ-school-management' ); ?></option>
                            <option value="inactive" <?php selected( $form_data['status'] ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'CTADZ-school-management' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Current status of the course.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_is_active"><?php esc_html_e( 'Active', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="course_is_active" name="is_active" value="1" <?php checked( $form_data['is_active'] ?? true ); ?> />
                            <?php esc_html_e( 'Active (visible and available for enrollment)', 'CTADZ-school-management' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( 
                    $is_edit ? __( 'Update Course', 'CTADZ-school-management' ) : __( 'Add Course', 'CTADZ-school-management' ), 
                    'primary', 
                    'sm_save_course', 
                    false 
                ); ?>
                <a href="?page=school-management-courses" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'CTADZ-school-management' ); ?></a>
            </p>
            
            <p class="description">
                <span style="color: #d63638;">*</span> <?php esc_html_e( 'Required fields', 'CTADZ-school-management' ); ?>
            </p>

        </form>

        <script>
        jQuery(document).ready(function($) {
            console.log('Payment models JS loaded!');
            console.log('Checkboxes found:', $('input[name="payment_models[]"]').length);
            
            // Get elements
            var $paymentCheckboxes = $('input[name="payment_models[]"]');
            var $totalMonthsRow = $('#course_total_months').closest('tr');
            var $totalPriceRow = $('#total_price_row');
            var $pricePerMonthRow = $('#course_price_per_month').closest('tr');
            var $totalMonthsInput = $('#course_total_months');
            var $totalPriceInput = $('#course_total_price');
            var $pricePerMonthInput = $('#course_price_per_month');
            
            // Make checkboxes exclusive (like radio buttons)
            $paymentCheckboxes.on('change', function() {
                if ($(this).is(':checked')) {
                    // Uncheck all others
                    $paymentCheckboxes.not(this).prop('checked', false);
                }
                updateFieldRequirements();
            });
            
            function updateFieldRequirements() {
                // Get selected payment model (only one should be selected)
                var selectedModel = $paymentCheckboxes.filter(':checked').val();
                
                if (selectedModel === 'full_payment') {
                    // FULL PAYMENT: Show only Total Price
                    $pricePerMonthRow.hide();
                    $totalMonthsRow.show();
                    $totalPriceRow.show();
                    
                    $pricePerMonthInput.prop('required', false);
                    $totalMonthsInput.prop('required', true);
                    $totalPriceInput.prop('required', true);
                    
                    // Set price per month = total price / months (for database consistency)
                    var totalPrice = parseFloat($totalPriceInput.val()) || 0;
                    var totalMonths = parseInt($totalMonthsInput.val()) || 1;
                    if (totalMonths > 0 && totalPrice > 0) {
                        $pricePerMonthInput.val((totalPrice / totalMonths).toFixed(2));
                    }
                    
                } else if (selectedModel === 'monthly_installments') {
                    // MONTHLY INSTALLMENTS: Show both Price Per Month AND Total Price
                    $pricePerMonthRow.show();
                    $totalMonthsRow.show();
                    $totalPriceRow.show();
                    
                    $pricePerMonthInput.prop('required', true);
                    $totalMonthsInput.prop('required', true);
                    $totalPriceInput.prop('required', true);
                    
                } else if (selectedModel === 'monthly_subscription') {
                    // MONTHLY SUBSCRIPTION: Show only Price Per Month
                    $pricePerMonthRow.show();
                    $totalMonthsRow.hide();
                    $totalPriceRow.hide();
                    
                    $pricePerMonthInput.prop('required', true);
                    $totalMonthsInput.prop('required', false);
                    $totalPriceInput.prop('required', false);
                    
                    // Set defaults for hidden fields (for database)
                    $totalMonthsInput.val(1);
                    var monthlyPrice = parseFloat($pricePerMonthInput.val()) || 0;
                    $totalPriceInput.val(monthlyPrice.toFixed(2));
                    
                } else {
                    // Nothing selected - show all fields
                    $pricePerMonthRow.show();
                    $totalMonthsRow.show();
                    $totalPriceRow.show();
                    
                    $pricePerMonthInput.prop('required', true);
                    $totalMonthsInput.prop('required', true);
                    $totalPriceInput.prop('required', true);
                }
            }
            
            // Keep total price synced for subscription
            $pricePerMonthInput.on('input', function() {
                var selectedModel = $paymentCheckboxes.filter(':checked').val();
                
                if (selectedModel === 'monthly_subscription') {
                    var monthlyPrice = parseFloat($(this).val()) || 0;
                    $totalPriceInput.val(monthlyPrice.toFixed(2));
                }
            });
            
            // Auto-calculate for full payment
            $totalPriceInput.on('input', function() {
                var selectedModel = $paymentCheckboxes.filter(':checked').val();
                
                if (selectedModel === 'full_payment') {
                    var totalPrice = parseFloat($(this).val()) || 0;
                    var totalMonths = parseInt($totalMonthsInput.val()) || 1;
                    if (totalMonths > 0 && totalPrice > 0) {
                        $pricePerMonthInput.val((totalPrice / totalMonths).toFixed(2));
                    }
                }
            });
            
            $totalMonthsInput.on('input', function() {
                var selectedModel = $paymentCheckboxes.filter(':checked').val();
                
                if (selectedModel === 'full_payment') {
                    var totalPrice = parseFloat($totalPriceInput.val()) || 0;
                    var totalMonths = parseInt($(this).val()) || 1;
                    if (totalMonths > 0 && totalPrice > 0) {
                        $pricePerMonthInput.val((totalPrice / totalMonths).toFixed(2));
                    }
                }
            });
            
            // Run on page load
            updateFieldRequirements();
        });
        </script>

        <?php
    }
}

// Instantiate class
new SM_Courses_Page();
