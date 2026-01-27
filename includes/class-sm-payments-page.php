<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Payments_Page {

    /**
     * Render the Payments page
     */
    public static function render_payments_page() {
        // Security check
        if ( ! current_user_can( 'manage_payments' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'CTADZ-school-management' ) );
        }

        global $wpdb;

        // Determine view
        $action = $_GET['action'] ?? 'list';
        $enrollment_id = isset( $_GET['enrollment_id'] ) ? intval( $_GET['enrollment_id'] ) : 0;

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Payments Management', 'CTADZ-school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'view':
                    if ( $enrollment_id > 0 ) {
                        self::render_enrollment_payments( $enrollment_id );
                    }
                    break;
                case 'record':
                    if ( $enrollment_id > 0 ) {
                        self::render_record_payment_form( $enrollment_id );
                    }
                    break;
                default:
                    self::render_payments_overview();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render payments overview (all enrollments with payment status)
     */
    private static function render_payments_overview() {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';

        // Get search parameter
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // Get sorting parameters
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'enrollment_date';
        $order = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), [ 'ASC', 'DESC' ] ) ? strtoupper( $_GET['order'] ) : 'DESC';

        // Get filter parameter for payment status
        $filter_status = isset( $_GET['filter_status'] ) ? sanitize_text_field( $_GET['filter_status'] ) : '';

        // Pagination
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        // Build WHERE clause - SQL and params separately
        $where_sql_parts = [ "e.status = 'active'" ];
        $where_params = [];

        // Search condition
        if ( ! empty( $search ) ) {
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $where_sql_parts[] = "(s.name LIKE %s OR c.name LIKE %s OR e.payment_plan LIKE %s)";
            $where_params[] = $search_term;
            $where_params[] = $search_term;
            $where_params[] = $search_term;
        }

        $where_sql = ! empty( $where_sql_parts ) ? 'WHERE ' . implode( ' AND ', $where_sql_parts ) : '';

        // Get total enrollments count (before status filtering)
        $count_query = "SELECT COUNT(DISTINCT e.id)
            FROM $enrollments_table e
            LEFT JOIN $students_table s ON e.student_id = s.id
            LEFT JOIN $courses_table c ON e.course_id = c.id
            $where_sql";

        if ( ! empty( $where_params ) ) {
            $total_enrollments = $wpdb->get_var( $wpdb->prepare( $count_query, $where_params ) );
        } else {
            $total_enrollments = $wpdb->get_var( $count_query );
        }

        // Validate and set ORDER BY clause
        $valid_columns = [
            'student_name' => 's.name',
            'course_name' => 'c.name',
            'payment_plan' => 'e.payment_plan',
            'total_expected' => 'total_expected',
            'total_paid' => 'total_paid',
            'next_payment_date' => 'next_payment_date',
            'enrollment_date' => 'e.enrollment_date'
        ];
        $orderby_column = isset( $valid_columns[ $orderby ] ) ? $valid_columns[ $orderby ] : 'e.enrollment_date';
        $order_clause = "$orderby_column $order";

        // Get active enrollments with payment info
        $query = "SELECT e.*,
                        s.name as student_name,
                        c.name as course_name,
                        c.price_per_month,
                        c.total_months,
                        (SELECT COUNT(*) FROM $payment_schedules_table ps WHERE ps.enrollment_id = e.id) as total_payments,
                        (SELECT COUNT(*) FROM $payment_schedules_table ps WHERE ps.enrollment_id = e.id AND ps.status = 'paid') as paid_payments,
                        (SELECT SUM(expected_amount) FROM $payment_schedules_table ps WHERE ps.enrollment_id = e.id) as total_expected,
                        (SELECT SUM(paid_amount) FROM $payment_schedules_table ps WHERE ps.enrollment_id = e.id) as total_paid,
                        (SELECT MIN(due_date) FROM $payment_schedules_table ps WHERE ps.enrollment_id = e.id AND ps.status IN ('pending', 'partial')) as next_payment_date
                 FROM $enrollments_table e
                 LEFT JOIN $students_table s ON e.student_id = s.id
                 LEFT JOIN $courses_table c ON e.course_id = c.id
                 $where_sql
                 ORDER BY $order_clause
                 LIMIT %d OFFSET %d";

        // Merge all parameters
        $all_params = array_merge( $where_params, array( $per_page, $offset ) );

        if ( ! empty( $all_params ) ) {
            $enrollments = $wpdb->get_results( $wpdb->prepare( $query, $all_params ) );
        } else {
            $enrollments = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );
        }

        // Apply status filtering after fetching (since it's calculated)
        if ( ! empty( $filter_status ) && $enrollments ) {
            $enrollments = array_filter( $enrollments, function( $enrollment ) use ( $filter_status ) {
                $total_expected = floatval( $enrollment->total_expected );
                $total_paid = floatval( $enrollment->total_paid );
                $balance = $total_expected - $total_paid;
                
                if ( $filter_status === 'paid' && $balance <= 0 ) {
                    return true;
                } elseif ( $filter_status === 'partial' && $total_paid > 0 && $balance > 0 ) {
                    return true;
                } elseif ( $filter_status === 'unpaid' && $total_paid == 0 ) {
                    return true;
                }
                return false;
            });
            
            // Update count for filtered results
            $total_enrollments = count( $enrollments );
        }

        $total_pages = ceil( $total_enrollments / $per_page );

        // Helper function to generate sortable column URL
        $get_sort_url = function( $column ) use ( $orderby, $order, $search, $filter_status ) {
            $new_order = ( $orderby === $column && $order === 'ASC' ) ? 'DESC' : 'ASC';
            $url = add_query_arg( [
                'page' => 'school-management-payments',
                'orderby' => $column,
                'order' => $new_order,
            ] );
            
            if ( ! empty( $search ) ) {
                $url = add_query_arg( 's', urlencode( $search ), $url );
            }
            if ( ! empty( $filter_status ) ) {
                $url = add_query_arg( 'filter_status', $filter_status, $url );
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
                <h2 style="margin: 0;"><?php esc_html_e( 'Payments Overview', 'CTADZ-school-management' ); ?></h2>
                <p class="description">
                    <?php 
                    $active_filters = ! empty( $search ) || ! empty( $filter_status );
                    if ( $active_filters ) {
                        if ( ! empty( $search ) && ! empty( $filter_status ) ) {
                            $status_labels = [
                                'paid' => __( 'Paid', 'CTADZ-school-management' ),
                                'partial' => __( 'Partial', 'CTADZ-school-management' ),
                                'unpaid' => __( 'Unpaid', 'CTADZ-school-management' ),
                            ];
                            printf( 
                                esc_html__( 'Showing %d enrollments matching "%s" with status: %s', 'CTADZ-school-management' ), 
                                $total_enrollments,
                                esc_html( $search ),
                                '<strong>' . esc_html( $status_labels[ $filter_status ] ?? $filter_status ) . '</strong>'
                            );
                        } elseif ( ! empty( $search ) ) {
                            printf( esc_html__( 'Showing %d enrollments matching "%s"', 'CTADZ-school-management' ), $total_enrollments, esc_html( $search ) );
                        } elseif ( ! empty( $filter_status ) ) {
                            $status_labels = [
                                'paid' => __( 'Paid', 'CTADZ-school-management' ),
                                'partial' => __( 'Partial', 'CTADZ-school-management' ),
                                'unpaid' => __( 'Unpaid', 'CTADZ-school-management' ),
                            ];
                            printf( 
                                esc_html__( 'Showing %d enrollments with status: %s', 'CTADZ-school-management' ), 
                                $total_enrollments,
                                '<strong>' . esc_html( $status_labels[ $filter_status ] ?? $filter_status ) . '</strong>'
                            );
                        }
                        echo ' <a href="?page=school-management-payments" style="margin-left: 10px;">' . esc_html__( '[Clear all filters]', 'CTADZ-school-management' ) . '</a>';
                    } else {
                        printf( esc_html__( 'Total: %d active enrollments', 'CTADZ-school-management' ), $total_enrollments );
                    }
                    ?>
                </p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <!-- Payment Status Filter -->
                <select id="filter_status" onchange="applyFiltersAndSort();">
                    <option value=""><?php esc_html_e( 'All Statuses', 'CTADZ-school-management' ); ?></option>
                    <option value="paid" <?php selected( $filter_status, 'paid' ); ?>><?php esc_html_e( 'Paid', 'CTADZ-school-management' ); ?></option>
                    <option value="partial" <?php selected( $filter_status, 'partial' ); ?>><?php esc_html_e( 'Partial', 'CTADZ-school-management' ); ?></option>
                    <option value="unpaid" <?php selected( $filter_status, 'unpaid' ); ?>><?php esc_html_e( 'Unpaid', 'CTADZ-school-management' ); ?></option>
                </select>
            </div>
        </div>

        <!-- Search Box -->
        <div class="tablenav top" style="margin-bottom: 15px;">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="school-management-payments">
                <?php if ( ! empty( $orderby ) ) : ?>
                    <input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>">
                    <input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>">
                <?php endif; ?>
                <?php if ( ! empty( $filter_status ) ) : ?>
                    <input type="hidden" name="filter_status" value="<?php echo esc_attr( $filter_status ); ?>">
                <?php endif; ?>
                <input type="search"
                       name="s"
                       value="<?php echo esc_attr( $search ); ?>"
                       placeholder="<?php esc_attr_e( 'Enter payment information', 'CTADZ-school-management' ); ?>"
                       style="margin-right: 5px;">
                <button type="submit" class="button"><?php esc_html_e( 'Search', 'CTADZ-school-management' ); ?></button>
                <?php if ( ! empty( $search ) ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'school-management-payments', 'filter_status' => $filter_status ), admin_url( 'admin.php' ) ) ); ?>" class="button" style="margin-left: 5px;">
                        <?php esc_html_e( 'Clear', 'CTADZ-school-management' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <script>
        function applyFiltersAndSort() {
            var urlParams = new URLSearchParams(window.location.search);
            var status = document.getElementById('filter_status').value;
            
            var url = '?page=school-management-payments';
            
            var search = urlParams.get('s');
            var orderby = urlParams.get('orderby');
            var order = urlParams.get('order');
            
            if (search) url += '&s=' + encodeURIComponent(search);
            if (orderby) url += '&orderby=' + orderby;
            if (order) url += '&order=' + order;
            if (status) url += '&filter_status=' + status;
            
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
                        <th class="<?php echo $orderby === 'payment_plan' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'payment_plan' ); ?>">
                                <?php esc_html_e( 'Payment Plan', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'payment_plan' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable"><?php esc_html_e( 'Progress', 'CTADZ-school-management' ); ?></th>
                        <th class="<?php echo $orderby === 'total_expected' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'total_expected' ); ?>">
                                <?php esc_html_e( 'Total Expected', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'total_expected' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'total_paid' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'total_paid' ); ?>">
                                <?php esc_html_e( 'Total Paid', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'total_paid' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'next_payment_date' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'next_payment_date' ); ?>">
                                <?php esc_html_e( 'Next Payment', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'next_payment_date' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable"><?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?></th>
                        <th class="non-sortable" style="width: 150px;"><?php esc_html_e( 'Actions', 'CTADZ-school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $enrollments as $enrollment ) :
                        $total_expected = floatval( $enrollment->total_expected );
                        $total_paid = floatval( $enrollment->total_paid );
                        $balance = $total_expected - $total_paid;
                        $progress_percent = $total_expected > 0 ? ( $total_paid / $total_expected ) * 100 : 0;

                        // Determine if this payment plan should show next payment date
                        $payment_plan = $enrollment->payment_plan ?? 'monthly';
                        $show_next_payment = in_array( $payment_plan, [ 'monthly', 'subscription' ] );
                    ?>
                        <tr>
                            <td data-label="<?php echo esc_attr__( 'Student', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Student', 'CTADZ-school-management' ); ?>:</span>
                                <strong><?php echo esc_html( $enrollment->student_name ); ?></strong>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Course', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Course', 'CTADZ-school-management' ); ?>:</span>
                                <?php echo esc_html( $enrollment->course_name ); ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Payment Plan', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Payment Plan', 'CTADZ-school-management' ); ?>:</span>
                                <?php echo esc_html( ucfirst( $payment_plan ) ); ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Progress', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Progress', 'CTADZ-school-management' ); ?>:</span>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #f0f0f1; height: 20px; border-radius: 10px; overflow: hidden;">
                                        <div style="width: <?php echo esc_attr( min( 100, $progress_percent ) ); ?>%; height: 100%; background: <?php echo $progress_percent >= 100 ? '#46b450' : '#0073aa'; ?>; transition: width 0.3s;"></div>
                                    </div>
                                    <span style="min-width: 40px; text-align: right;"><?php echo number_format( $progress_percent, 0 ); ?>%</span>
                                </div>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Total Expected', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Total Expected', 'CTADZ-school-management' ); ?>:</span>
                                <?php echo number_format( $total_expected, 2 ); ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Total Paid', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Total Paid', 'CTADZ-school-management' ); ?>:</span>
                                <?php echo number_format( $total_paid, 2 ); ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Next Payment', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Next Payment', 'CTADZ-school-management' ); ?>:</span>
                                <?php
                                if ( $show_next_payment && ! empty( $enrollment->next_payment_date ) ) {
                                    $next_date = strtotime( $enrollment->next_payment_date );
                                    $today = strtotime( 'today' );
                                    $is_overdue = $next_date < $today;
                                    $days_diff = floor( ( $next_date - $today ) / ( 60 * 60 * 24 ) );

                                    if ( $is_overdue ) {
                                        echo '<span class="text-danger" style="font-weight: 600;">';
                                        echo esc_html( date( 'M j, Y', $next_date ) );
                                        echo '<br><small style="font-weight: 400;">(' . esc_html__( 'Overdue', 'CTADZ-school-management' ) . ')</small>';
                                        echo '</span>';
                                    } elseif ( $days_diff <= 7 ) {
                                        echo '<span style="color: #f0ad4e; font-weight: 600;">';
                                        echo esc_html( date( 'M j, Y', $next_date ) );
                                        echo '<br><small style="font-weight: 400;">(' . sprintf( esc_html__( 'In %d days', 'CTADZ-school-management' ), $days_diff ) . ')</small>';
                                        echo '</span>';
                                    } else {
                                        echo '<span style="color: #50575e;">';
                                        echo esc_html( date( 'M j, Y', $next_date ) );
                                        echo '</span>';
                                    }
                                } else {
                                    echo '<span class="text-muted">—</span>';
                                }
                                ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Status', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?>:</span>
                                <?php if ( $balance <= 0 ) : ?>
                                    <span style="color: #46b450;">● <?php esc_html_e( 'Paid', 'CTADZ-school-management' ); ?></span>
                                <?php elseif ( $total_paid > 0 ) : ?>
                                    <span style="color: #f0ad4e;">● <?php esc_html_e( 'Partial', 'CTADZ-school-management' ); ?></span>
                                <?php else : ?>
                                    <span class="text-danger">● <?php esc_html_e( 'Unpaid', 'CTADZ-school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="?page=school-management-payments&action=view&enrollment_id=<?php echo intval( $enrollment->id ); ?>" class="button button-small">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <span class="button-text"><?php esc_html_e( 'View', 'CTADZ-school-management' ); ?></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            // Pagination
            if ( $total_pages > 1 ) {
                $pagination_args = [
                    'base' => add_query_arg( 'paged', '%#%' ),
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
                <span class="dashicons dashicons-money-alt" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></span>
                <h3><?php esc_html_e( 'No Active Enrollments', 'CTADZ-school-management' ); ?></h3>
                <p><?php esc_html_e( 'Enroll students in courses to start tracking payments.', 'CTADZ-school-management' ); ?></p>
                <a href="?page=school-management-enrollments&action=add" class="button button-primary">
                    <?php esc_html_e( 'Create Enrollment', 'CTADZ-school-management' ); ?>
                </a>
            </div>
        <?php endif;
    }

    /**
     * Render detailed payment view for a specific enrollment
     */
    private static function render_enrollment_payments( $enrollment_id ) {
        global $wpdb;
        global $wpdb;
        
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $enrollment_fees_table = $wpdb->prefix . 'sm_enrollment_fees';
        $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
        $payments_table = $wpdb->prefix . 'sm_payments';

        // Get enrollment details
        $enrollment = $wpdb->get_row( $wpdb->prepare(
            "SELECT e.*, 
                    s.name as student_name,
                    s.email as student_email,
                    c.name as course_name,
                    c.price_per_month,
                    c.total_months
             FROM $enrollments_table e
             LEFT JOIN $students_table s ON e.student_id = s.id
             LEFT JOIN $courses_table c ON e.course_id = c.id
             WHERE e.id = %d",
            $enrollment_id
        ) );

        if ( ! $enrollment ) {
            echo '<div class="error notice"><p>' . esc_html__( 'Enrollment not found.', 'CTADZ-school-management' ) . '</p></div>';
            return;
        }

        // Get enrollment fees
        $enrollment_fees = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $enrollment_fees_table WHERE enrollment_id = %d ORDER BY fee_type",
            $enrollment_id
        ) );

        // Get payment schedule
        $payment_schedule = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $payment_schedules_table WHERE enrollment_id = %d ORDER BY installment_number",
            $enrollment_id
        ) );

        // Get payment history
        $payment_history = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $payments_table WHERE enrollment_id = %d ORDER BY payment_date DESC, created_at DESC",
            $enrollment_id
        ) );

        // Calculate totals
        $total_fees = array_sum( array_column( $enrollment_fees, 'amount' ) );
        $total_fees_paid = array_sum( array_map( function( $fee ) {
            return $fee->status === 'paid' ? $fee->amount : 0;
        }, $enrollment_fees ) );

        $total_scheduled = array_sum( array_column( $payment_schedule, 'expected_amount' ) );
        $total_scheduled_paid = array_sum( array_column( $payment_schedule, 'paid_amount' ) );

        $grand_total = $total_fees + $total_scheduled;
        $grand_total_paid = $total_fees_paid + $total_scheduled_paid;
        $balance = $grand_total - $grand_total_paid;

        ?>
        <h2 style="margin-top: 0; margin-bottom: 20px;"><?php esc_html_e( 'Payment Details', 'CTADZ-school-management' ); ?></h2>

        <div style="margin-bottom: 30px;">
            <a href="?page=school-management-payments" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Payments', 'CTADZ-school-management' ); ?>
            </a>
        </div>
        <!-- Enrollment Info Card -->
        <div style="background: white; padding: 20px; margin-bottom: 20px; border-left: 4px solid #0073aa; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;"><?php esc_html_e( 'Enrollment Information', 'CTADZ-school-management' ); ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong><?php esc_html_e( 'Student:', 'CTADZ-school-management' ); ?></strong><br>
                    <?php echo esc_html( $enrollment->student_name ); ?>
                </div>
                <div>
                    <strong><?php esc_html_e( 'Course:', 'CTADZ-school-management' ); ?></strong><br>
                    <?php echo esc_html( $enrollment->course_name ); ?>
                </div>
                <div>
                    <strong><?php esc_html_e( 'Payment Plan:', 'CTADZ-school-management' ); ?></strong><br>
                    <?php echo esc_html( ucfirst( $enrollment->payment_plan ?? 'monthly' ) ); ?>
                </div>
                <div>
                    <strong><?php esc_html_e( 'Enrollment Date:', 'CTADZ-school-management' ); ?></strong><br>
                    <?php echo esc_html( date( 'M j, Y', strtotime( $enrollment->enrollment_date ) ) ); ?>
                </div>
            </div>
        </div>

        <!-- Payment Summary Card -->
        <div style="background: white; padding: 20px; margin-bottom: 20px; border-left: 4px solid #46b450; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0;"><?php esc_html_e( 'Payment Summary', 'CTADZ-school-management' ); ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
                <div>
                    <div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo number_format( $grand_total, 2 ); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php esc_html_e( 'Total Expected', 'CTADZ-school-management' ); ?></div>
                </div>
                <div>
                    <div style="font-size: 24px; font-weight: bold; color: #46b450;"><?php echo number_format( $grand_total_paid, 2 ); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php esc_html_e( 'Total Paid', 'CTADZ-school-management' ); ?></div>
                </div>
                <div>
                    <div style="font-size: 24px; font-weight: bold; color: <?php echo $balance > 0 ? '#dc3232' : '#46b450'; ?>;"><?php echo number_format( $balance, 2 ); ?></div>
                    <div style="color: #666; font-size: 13px;"><?php esc_html_e( 'Balance Due', 'CTADZ-school-management' ); ?></div>
                </div>
            </div>
        </div>

        <!-- Enrollment Fees Section -->
        <?php if ( $enrollment_fees ) : ?>
        <div style="background: white; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3><?php esc_html_e( 'Enrollment Fees', 'CTADZ-school-management' ); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Fee Type', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Due Date', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Paid Date', 'CTADZ-school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $enrollment_fees as $fee ) : ?>
                        <tr>
                            <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $fee->fee_type ) ) ); ?></td>
                            <td><?php echo number_format( $fee->amount, 2 ); ?></td>
                            <td><?php echo esc_html( date( 'M j, Y', strtotime( $fee->due_date ) ) ); ?></td>
                            <td>
                                <?php if ( $fee->status === 'paid' ) : ?>
                                    <span style="color: #46b450;">● <?php esc_html_e( 'Paid', 'CTADZ-school-management' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #dc3232;">● <?php esc_html_e( 'Unpaid', 'CTADZ-school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $fee->paid_date ? esc_html( date( 'M j, Y', strtotime( $fee->paid_date ) ) ) : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Payment Schedule Section -->
        <?php if ( $payment_schedule ) : ?>
        <div style="background: white; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;"><?php esc_html_e( 'Payment Schedule', 'CTADZ-school-management' ); ?></h3>
                <a href="?page=school-management-payments&action=record&enrollment_id=<?php echo intval( $enrollment_id ); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Record Payment', 'CTADZ-school-management' ); ?>
                </a>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Installment', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Expected Amount', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Due Date', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Discount', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Paid Amount', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Paid Date', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $payment_schedule as $schedule ) : 
                        $is_overdue = ( $schedule->status === 'pending' && strtotime( $schedule->due_date ) < time() );
                    ?>
                        <tr>
                            <td><strong>#<?php echo intval( $schedule->installment_number ); ?></strong></td>
                            <td><?php echo number_format( $schedule->expected_amount, 2 ); ?></td>
                            <td><?php echo esc_html( date( 'M j, Y', strtotime( $schedule->due_date ) ) ); ?></td>
                            <td>
                                <?php if ( ! empty( $schedule->discount_percentage ) && $schedule->discount_percentage > 0 ) : ?>
                                    <span style="color: #46b450; font-weight: 600;" title="<?php echo esc_attr( $schedule->discount_reason ); ?>">
                                        <?php echo number_format( $schedule->discount_percentage, 1 ); ?>%
                                    </span>
                                    <?php if ( ! empty( $schedule->discount_reason ) ) : ?>
                                        <br><small style="color: #666;"><?php echo esc_html( $schedule->discount_reason ); ?></small>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format( $schedule->paid_amount, 2 ); ?></td>
                            <td><?php echo $schedule->paid_date ? esc_html( date( 'M j, Y', strtotime( $schedule->paid_date ) ) ) : '—'; ?></td>
                            <td>
                                <?php if ( $schedule->status === 'paid' ) : ?>
                                    <span style="color: #46b450;">● <?php esc_html_e( 'Paid', 'CTADZ-school-management' ); ?></span>
                                <?php elseif ( $is_overdue ) : ?>
                                    <span style="color: #d63638;">● <?php esc_html_e( 'Overdue', 'CTADZ-school-management' ); ?></span>
                                <?php elseif ( $schedule->status === 'partial' ) : ?>
                                    <span style="color: #f0ad4e;">● <?php esc_html_e( 'Partial', 'CTADZ-school-management' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #999;">● <?php esc_html_e( 'Pending', 'CTADZ-school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Payment History Section -->
        <?php if ( $payment_history ) : ?>
        <div style="background: white; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3><?php esc_html_e( 'Payment History', 'CTADZ-school-management' ); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Method', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Reference', 'CTADZ-school-management' ); ?></th>
                        <th><?php esc_html_e( 'Notes', 'CTADZ-school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $payment_history as $payment ) : ?>
                        <tr>
                            <td><?php echo esc_html( date( 'M j, Y', strtotime( $payment->payment_date ) ) ); ?></td>
                            <td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $payment->payment_type ) ) ); ?></td>
                            <td><strong><?php echo number_format( $payment->amount, 2 ); ?></strong></td>
                            <td><?php echo esc_html( $payment->payment_method ?: '—' ); ?></td>
                            <td><?php echo esc_html( $payment->reference_number ?: '—' ); ?></td>
                            <td><?php echo esc_html( $payment->notes ?: '—' ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render record payment form
     */
    private static function render_record_payment_form( $enrollment_id ) {
        global $wpdb;
        
    // Handle form submission
        if ( isset( $_POST['sm_record_payment'] ) && check_admin_referer( 'sm_record_payment_action', 'sm_record_payment_nonce' ) ) {
            $payment_type = sanitize_text_field( $_POST['payment_type'] ?? '' );
            $reference_id = intval( $_POST['reference_id'] ?? 0 );
            $amount = floatval( $_POST['amount'] ?? 0 );
            $payment_date = sanitize_text_field( $_POST['payment_date'] ?? '' );
            $payment_method = sanitize_text_field( $_POST['payment_method'] ?? '' );
            $notes = sanitize_textarea_field( $_POST['notes'] ?? '' );

            if ( $amount > 0 && ! empty( $payment_date ) && ! empty( $payment_type ) ) {
                // Auto-generate reference number
                $enrollments_table = $wpdb->prefix . 'sm_enrollments';
                $students_table = $wpdb->prefix . 'sm_students';
                $courses_table = $wpdb->prefix . 'sm_courses';
                $enrollment_fees_table = $wpdb->prefix . 'sm_enrollment_fees';
                $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
                
                // Get student name
                $student_name = $wpdb->get_var( $wpdb->prepare(
                    "SELECT s.name FROM $enrollments_table e 
                    LEFT JOIN $students_table s ON e.student_id = s.id 
                    WHERE e.id = %d",
                    $enrollment_id
                ) );
                
                // Determine fee type label
                $fee_type_label = '';
                if ( $payment_type === 'enrollment_fee' && $reference_id > 0 ) {
                    $fee = $wpdb->get_var( $wpdb->prepare(
                        "SELECT fee_type FROM $enrollment_fees_table WHERE id = %d",
                        $reference_id
                    ) );
                    $fee_type_label = ucfirst( $fee );
                } elseif ( $payment_type === 'installment' && $reference_id > 0 ) {
                    $installment_num = $wpdb->get_var( $wpdb->prepare(
                        "SELECT installment_number FROM $payment_schedules_table WHERE id = %d",
                        $reference_id
                    ) );
                    $fee_type_label = __( 'Installment #', 'CTADZ-school-management' ) . $installment_num;
                } else {
                    $fee_type_label = __( 'Other Payment', 'CTADZ-school-management' );
                }
                
                // Generate reference: YYYYMMDD_StudentName_FeeType
                $date_prefix = date( 'Ymd', strtotime( $payment_date ) );
                $clean_student_name = str_replace( ' ', '_', $student_name );
                $reference_number = $date_prefix . '_' . $clean_student_name . '_' . str_replace( ' ', '_', $fee_type_label );

            // Insert payment record
                $payments_table = $wpdb->prefix . 'sm_payments';
                $inserted = $wpdb->insert( $payments_table, [
                    'enrollment_id' => $enrollment_id,
                    'payment_type' => $payment_type,
                    'reference_id' => $reference_id ?: null,
                    'amount' => $amount,
                    'payment_date' => $payment_date,
                    'payment_method' => $payment_method,
                    'reference_number' => $reference_number,
                    'notes' => $notes,
                ] );

                if ( $inserted ) {
                    // Update the referenced record
                    if ( $payment_type === 'enrollment_fee' && $reference_id > 0 ) {
                        $enrollment_fees_table = $wpdb->prefix . 'sm_enrollment_fees';
                        $wpdb->update( $enrollment_fees_table, [
                            'status' => 'paid',
                            'paid_date' => $payment_date,
                        ], [ 'id' => $reference_id ] );
                    } elseif ( $payment_type === 'installment' && $reference_id > 0 ) {
                        $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
                        $schedule = $wpdb->get_row( $wpdb->prepare(
                            "SELECT * FROM $payment_schedules_table WHERE id = %d",
                            $reference_id
                        ) );
                        
                        if ( $schedule ) {
                            $new_paid_amount = floatval( $schedule->paid_amount ) + $amount;
                            $new_status = 'paid';
                            if ( $new_paid_amount < $schedule->expected_amount ) {
                                $new_status = 'partial';
                            }
                            
                            $wpdb->update( $payment_schedules_table, [
                                'paid_amount' => $new_paid_amount,
                                'paid_date' => $payment_date,
                                'status' => $new_status,
                            ], [ 'id' => $reference_id ] );
                        }
                    }

                    // Trigger enrollment payment status sync
                    do_action( 'sm_payment_updated', $enrollment_id );

                    echo '<div class="updated notice"><p>' . esc_html__( 'Payment recorded successfully.', 'CTADZ-school-management' ) . '</p></div>';
                    echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-payments&action=view&enrollment_id=' . $enrollment_id . '"; }, 1500);</script>';
                }
            } else {
                echo '<div class="error notice"><p>' . esc_html__( 'Please fill all required fields.', 'CTADZ-school-management' ) . '</p></div>';
            }
        }

        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $enrollment_fees_table = $wpdb->prefix . 'sm_enrollment_fees';
        $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';

        // Get enrollment info
        $enrollment = $wpdb->get_row( $wpdb->prepare(
            "SELECT e.*, s.name as student_name, c.name as course_name
             FROM $enrollments_table e
             LEFT JOIN $students_table s ON e.student_id = s.id
             LEFT JOIN $courses_table c ON e.course_id = c.id
             WHERE e.id = %d",
            $enrollment_id
        ) );

        if ( ! $enrollment ) {
            echo '<div class="error notice"><p>' . esc_html__( 'Enrollment not found.', 'CTADZ-school-management' ) . '</p></div>';
            return;
        }

        // Get unpaid enrollment fees
        $unpaid_fees = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $enrollment_fees_table WHERE enrollment_id = %d AND status != 'paid' ORDER BY fee_type",
            $enrollment_id
        ) );

        // Get pending/partial payment schedules
        $pending_schedules = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $payment_schedules_table 
             WHERE enrollment_id = %d AND status IN ('pending', 'partial') 
             ORDER BY installment_number",
            $enrollment_id
        ) );

        ?>
        <h2 style="margin-top: 0; margin-bottom: 20px;"><?php esc_html_e( 'Record Payment', 'CTADZ-school-management' ); ?></h2>

        <div style="margin-bottom: 30px;">
            <a href="?page=school-management-payments&action=view&enrollment_id=<?php echo intval( $enrollment_id ); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Payment Details', 'CTADZ-school-management' ); ?>
            </a>
        </div>
        <div style="background: white; padding: 20px; margin-bottom: 20px; border-left: 4px solid #0073aa; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <strong><?php esc_html_e( 'Student:', 'CTADZ-school-management' ); ?></strong> <?php echo esc_html( $enrollment->student_name ); ?><br>
            <strong><?php esc_html_e( 'Course:', 'CTADZ-school-management' ); ?></strong> <?php echo esc_html( $enrollment->course_name ); ?>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_record_payment_action', 'sm_record_payment_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="payment_type"><?php esc_html_e( 'Payment Type', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="payment_type" name="payment_type" required>
                            <option value=""><?php esc_html_e( 'Select Type', 'CTADZ-school-management' ); ?></option>
                            <?php if ( $unpaid_fees ) : ?>
                                <option value="enrollment_fee"><?php esc_html_e( 'Enrollment Fee', 'CTADZ-school-management' ); ?></option>
                            <?php endif; ?>
                            <?php if ( $pending_schedules ) : ?>
                                <option value="installment"><?php esc_html_e( 'Course Installment', 'CTADZ-school-management' ); ?></option>
                            <?php endif; ?>
                            <option value="other"><?php esc_html_e( 'Other Payment', 'CTADZ-school-management' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr id="reference_row" style="display: none;">
                    <th scope="row">
                        <label for="reference_id"><?php esc_html_e( 'Payment For', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="reference_id" name="reference_id">
                            <option value=""><?php esc_html_e( 'Select...', 'CTADZ-school-management' ); ?></option>
                        </select>
                        <span id="reference_amount_display"></span>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="amount"><?php esc_html_e( 'Amount', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="amount" name="amount" step="0.01" min="0" required style="width: 200px;" />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="payment_date"><?php esc_html_e( 'Payment Date', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="date" id="payment_date" name="payment_date" value="<?php echo date( 'Y-m-d' ); ?>" required />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="payment_method"><?php esc_html_e( 'Payment Method', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <select id="payment_method" name="payment_method">
                            <option value=""><?php esc_html_e( 'Select Method', 'CTADZ-school-management' ); ?></option>
                            <option value="cash"><?php esc_html_e( 'Cash', 'CTADZ-school-management' ); ?></option>
                            <option value="check"><?php esc_html_e( 'Check', 'CTADZ-school-management' ); ?></option>
                            <option value="bank_transfer"><?php esc_html_e( 'Bank Transfer', 'CTADZ-school-management' ); ?></option>
                            <option value="card"><?php esc_html_e( 'Card', 'CTADZ-school-management' ); ?></option>
                            <option value="other"><?php esc_html_e( 'Other', 'CTADZ-school-management' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Reference Number', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <p class="description" style="margin: 0; padding: 8px 12px; background: #f0f0f1; border-radius: 4px; display: inline-block;">
                            <?php esc_html_e( 'Auto-generated on save', 'CTADZ-school-management' ); ?>
                        </p>
                        <p class="description" style="margin-top: 5px;">
                            <?php esc_html_e( 'Format: YYYYMMDD_StudentName_PaymentType', 'CTADZ-school-management' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="notes"><?php esc_html_e( 'Notes', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <textarea id="notes" name="notes" rows="3" class="large-text"></textarea>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( __( 'Record Payment', 'CTADZ-school-management' ), 'primary', 'sm_record_payment', false ); ?>
                <a href="?page=school-management-payments&action=view&enrollment_id=<?php echo intval( $enrollment_id ); ?>" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'CTADZ-school-management' ); ?></a>
            </p>
        </form>

        <script>
        // Localized strings for payments page
        var smPaymentStrings = {
            installment: '<?php echo esc_js( __( 'Installment #', 'CTADZ-school-management' ) ); ?>',
            remaining: '<?php echo esc_js( __( 'Remaining:', 'CTADZ-school-management' ) ); ?>'
        };

        jQuery(document).ready(function($) {
            var unpaidFees = <?php echo json_encode( $unpaid_fees ); ?>;
            var pendingSchedules = <?php echo json_encode( $pending_schedules ); ?>;

            $('#payment_type').on('change', function() {
                var type = $(this).val();
                var $referenceRow = $('#reference_row');
                var $referenceSelect = $('#reference_id');
                var $amountField = $('#amount');
                
                $referenceSelect.empty().append('<option value=""><?php esc_html_e( 'Select...', 'CTADZ-school-management' ); ?></option>');
                
                if (type === 'enrollment_fee') {
                    $referenceRow.show();
                    $referenceSelect.attr('required', true);
                    unpaidFees.forEach(function(fee) {
                        var feeTypeName = fee.fee_type.replace('_', ' ');
                        feeTypeName = feeTypeName.charAt(0).toUpperCase() + feeTypeName.slice(1);
                        $referenceSelect.append('<option value="' + fee.id + '" data-amount="' + fee.amount + '">' + feeTypeName + ' - ' + fee.amount + '</option>');
                    });
                } else if (type === 'installment') {
                    $referenceRow.show();
                    $referenceSelect.attr('required', true);
                    pendingSchedules.forEach(function(schedule) {
                        var remaining = parseFloat(schedule.expected_amount) - parseFloat(schedule.paid_amount);
                        $referenceSelect.append('<option value="' + schedule.id + '" data-amount="' + remaining + '">' + smPaymentStrings.installment + schedule.installment_number + ' - ' + smPaymentStrings.remaining + ' ' + remaining.toFixed(2) + '</option>');
                    });
                } else {
                    $referenceRow.hide();
                    $referenceSelect.attr('required', false);
                    $amountField.val('');
                }
            });

            $('#reference_id').on('change', function() {
                var amount = $(this).find('option:selected').data('amount');
                if (amount) {
                    $('#amount').val(amount);
                }
            });
        });
        </script>
        <?php
    }
}

// Instantiate class
new SM_Payments_Page();
