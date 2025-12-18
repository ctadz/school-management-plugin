<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Payment_Alerts_Page {

    /**
     * Render the Payment Alerts page
     */
    public static function render_page() {
        // Security check
        if ( ! current_user_can( 'manage_payments' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'CTADZ-school-management' ) );
        }

        // Handle send reminder action
        if ( isset( $_POST['send_reminder'] ) && check_admin_referer( 'sm_send_reminder', 'sm_reminder_nonce' ) ) {
            $schedule_id = intval( $_POST['schedule_id'] );
            $result = self::send_payment_reminder( $schedule_id );
            
            if ( $result['success'] ) {
                echo '<div class="updated notice"><p>' . esc_html( $result['message'] ) . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html( $result['message'] ) . '</p></div>';
            }
        }

        global $wpdb;
        $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $levels_table = $wpdb->prefix . 'sm_levels';

        // Get filter parameter
        $filter = isset( $_GET['filter'] ) ? sanitize_text_field( $_GET['filter'] ) : 'overdue';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $course_filter = isset( $_GET['course'] ) ? intval( $_GET['course'] ) : 0;
        $level_filter = isset( $_GET['level'] ) ? intval( $_GET['level'] ) : 0;

        // Build base query
        $where_clauses = [
            "ps.status IN ('pending', 'partial')",
            "e.status = 'active'"
        ];

        // Date filter
        switch ( $filter ) {
            case 'overdue':
                $where_clauses[] = "ps.due_date < CURDATE()";
                break;
            case 'this_week':
                $where_clauses[] = "ps.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'next_week':
                $where_clauses[] = "ps.due_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 8 DAY) AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)";
                break;
            case 'all':
                // No date filter
                break;
        }

        // Search filter
        if ( ! empty( $search ) ) {
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $where_clauses[] = $wpdb->prepare(
                "(s.name LIKE %s OR c.name LIKE %s)",
                $search_term,
                $search_term
            );
        }

        // Course filter
        if ( $course_filter > 0 ) {
            $where_clauses[] = $wpdb->prepare( "e.course_id = %d", $course_filter );
        }

        // Level filter
        if ( $level_filter > 0 ) {
            $where_clauses[] = $wpdb->prepare( "c.level_id = %d", $level_filter );
        }

        $where_clause = 'WHERE ' . implode( ' AND ', $where_clauses );

        // Get alerts
        $alerts = $wpdb->get_results("
            SELECT ps.*, 
                   e.id as enrollment_id,
                   e.student_id,
                   s.name as student_name,
                   s.email,
                   s.phone,
                   c.name as course_name,
                   c.level_id,
                   l.name as level_name,
                   (ps.expected_amount - ps.paid_amount) as balance,
                   DATEDIFF(CURDATE(), ps.due_date) as days_overdue,
                   DATEDIFF(ps.due_date, CURDATE()) as days_until_due
            FROM $payment_schedules_table ps
            LEFT JOIN $enrollments_table e ON ps.enrollment_id = e.id
            LEFT JOIN $students_table s ON e.student_id = s.id
            LEFT JOIN $courses_table c ON e.course_id = c.id
            LEFT JOIN $levels_table l ON c.level_id = l.id
            $where_clause
            ORDER BY ps.due_date ASC, s.name ASC
        ");

        // Get filter counts for all categories
        $overdue_count = $wpdb->get_var("
            SELECT COUNT(*)
            FROM $payment_schedules_table ps
            LEFT JOIN $enrollments_table e ON ps.enrollment_id = e.id
            WHERE ps.status IN ('pending', 'partial')
            AND e.status = 'active'
            AND ps.due_date < CURDATE()
        ");

        $week_count = $wpdb->get_var("
            SELECT COUNT(*)
            FROM $payment_schedules_table ps
            LEFT JOIN $enrollments_table e ON ps.enrollment_id = e.id
            WHERE ps.status IN ('pending', 'partial')
            AND e.status = 'active'
            AND ps.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ");

        $next_week_count = $wpdb->get_var("
            SELECT COUNT(*)
            FROM $payment_schedules_table ps
            LEFT JOIN $enrollments_table e ON ps.enrollment_id = e.id
            WHERE ps.status IN ('pending', 'partial')
            AND e.status = 'active'
            AND ps.due_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 8 DAY) AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        ");

        // Get courses and levels for filters
        $courses = $wpdb->get_results( "SELECT id, name FROM $courses_table WHERE is_active = 1 ORDER BY name" );
        $levels = $wpdb->get_results( "SELECT id, name FROM $levels_table WHERE is_active = 1 ORDER BY name" );

        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-money-alt" style="color: #27ae60;"></span>
                <?php esc_html_e( 'Payment Alerts & Follow-up', 'CTADZ-school-management' ); ?>
            </h1>

            <!-- Filter Tabs -->
            <div style="margin: 20px 0; border-bottom: 2px solid #f0f0f0;">
                <div style="display: flex; gap: 10px;">
                    <a href="?page=school-management-payment-alerts&filter=overdue" 
                       class="<?php echo $filter === 'overdue' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>"
                       style="<?php echo $filter === 'overdue' ? 'border-color: #dc2626;' : ''; ?>">
                        🔴 <?php esc_html_e( 'Overdue', 'CTADZ-school-management' ); ?> 
                        <?php if ( $overdue_count > 0 ) echo '<span class="count">(' . $overdue_count . ')</span>'; ?>
                    </a>
                    <a href="?page=school-management-payment-alerts&filter=this_week" 
                       class="<?php echo $filter === 'this_week' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>"
                       style="<?php echo $filter === 'this_week' ? 'border-color: #f59e0b;' : ''; ?>">
                        ⚠️ <?php esc_html_e( 'This Week', 'CTADZ-school-management' ); ?>
                        <?php if ( $week_count > 0 ) echo '<span class="count">(' . $week_count . ')</span>'; ?>
                    </a>
                    <a href="?page=school-management-payment-alerts&filter=next_week" 
                       class="<?php echo $filter === 'next_week' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>"
                       style="<?php echo $filter === 'next_week' ? 'border-color: #eab308;' : ''; ?>">
                        🟡 <?php esc_html_e( 'Next Week', 'CTADZ-school-management' ); ?>
                        <?php if ( $next_week_count > 0 ) echo '<span class="count">(' . $next_week_count . ')</span>'; ?>
                    </a>
                    <a href="?page=school-management-payment-alerts&filter=all" 
                       class="<?php echo $filter === 'all' ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>">
                        📋 <?php esc_html_e( 'All Pending', 'CTADZ-school-management' ); ?>
                    </a>
                </div>
            </div>

            <!-- Filters and Search -->
            <div style="background: white; padding: 15px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="hidden" name="page" value="school-management-payment-alerts">
                    <input type="hidden" name="filter" value="<?php echo esc_attr( $filter ); ?>">
                    
                    <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" 
                           placeholder="<?php esc_attr_e( 'Search by student or course...', 'CTADZ-school-management' ); ?>"
                           style="width: 250px;">
                    
                    <select name="level">
                        <option value=""><?php esc_html_e( 'All Levels', 'CTADZ-school-management' ); ?></option>
                        <?php foreach ( $levels as $level ) : ?>
                            <option value="<?php echo $level->id; ?>" <?php selected( $level_filter, $level->id ); ?>>
                                <?php echo esc_html( $level->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="course">
                        <option value=""><?php esc_html_e( 'All Courses', 'CTADZ-school-management' ); ?></option>
                        <?php foreach ( $courses as $course ) : ?>
                            <option value="<?php echo $course->id; ?>" <?php selected( $course_filter, $course->id ); ?>>
                                <?php echo esc_html( $course->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="button"><?php esc_html_e( 'Filter', 'CTADZ-school-management' ); ?></button>
                    
                    <?php if ( ! empty( $search ) || $course_filter > 0 || $level_filter > 0 ) : ?>
                        <a href="?page=school-management-payment-alerts&filter=<?php echo esc_attr( $filter ); ?>" class="button">
                            <?php esc_html_e( 'Clear Filters', 'CTADZ-school-management' ); ?>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ( $alerts ) : ?>
                <p style="color: #666;">
                    <?php
                    printf(
                        esc_html__( 'Showing %d payment alerts', 'CTADZ-school-management' ),
                        count( $alerts )
                    );
                    ?>
                </p>

                <table class="wp-list-table widefat fixed striped mobile-card-layout">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Student', 'CTADZ-school-management' ); ?></th>
                            <th><?php esc_html_e( 'Course', 'CTADZ-school-management' ); ?></th>
                            <th><?php esc_html_e( 'Level', 'CTADZ-school-management' ); ?></th>
                            <th><?php esc_html_e( 'Due Date', 'CTADZ-school-management' ); ?></th>
                            <th><?php esc_html_e( 'Amount Due', 'CTADZ-school-management' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?></th>
                            <th style="width: 200px;"><?php esc_html_e( 'Actions', 'CTADZ-school-management' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $alerts as $alert ) :
                            $is_overdue = $alert->days_overdue > 0;
                            $balance = floatval( $alert->balance );
                        ?>
                            <tr>
                                <td data-label="<?php echo esc_attr__( 'Student', 'CTADZ-school-management' ); ?>">
                                    <span class="mobile-label"><?php esc_html_e( 'Student', 'CTADZ-school-management' ); ?>:</span>
                                    <strong><?php echo esc_html( $alert->student_name ); ?></strong>
                                    <?php if ( $alert->email ) : ?>
                                        <br><small style="color: #666;">📧 <?php echo esc_html( $alert->email ); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $alert->course_name ); ?></td>
                                <td><?php echo esc_html( $alert->level_name ); ?></td>
                                <td>
                                    <?php echo esc_html( date( 'M j, Y', strtotime( $alert->due_date ) ) ); ?>
                                    <br>
                                    <?php if ( $is_overdue ) : ?>
                                        <small style="color: #dc2626; font-weight: 600;">
                                            <?php printf( esc_html__( '%d days overdue', 'CTADZ-school-management' ), $alert->days_overdue ); ?>
                                        </small>
                                    <?php else : ?>
                                        <small style="color: #f59e0b;">
                                            <?php printf( esc_html__( 'In %d days', 'CTADZ-school-management' ), $alert->days_until_due ); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo number_format( $balance, 2 ); ?> DA</strong>
                                </td>
                                <td>
                                    <?php if ( $is_overdue ) : ?>
                                        <span style="color: #dc2626; font-weight: 600;">🔴 <?php esc_html_e( 'Overdue', 'CTADZ-school-management' ); ?></span>
                                    <?php elseif ( $alert->days_until_due <= 7 ) : ?>
                                        <span style="color: #f59e0b; font-weight: 600;">⚠️ <?php esc_html_e( 'Due Soon', 'CTADZ-school-management' ); ?></span>
                                    <?php else : ?>
                                        <span style="color: #eab308;">🟡 <?php esc_html_e( 'Upcoming', 'CTADZ-school-management' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <?php if ( ! empty( $alert->email ) ) : ?>
                                            <form method="post" style="display: inline;">
                                                <?php wp_nonce_field( 'sm_send_reminder', 'sm_reminder_nonce' ); ?>
                                                <input type="hidden" name="schedule_id" value="<?php echo $alert->id; ?>">
                                                <button type="submit" name="send_reminder" class="button button-small" 
                                                        onclick="return confirm('<?php esc_attr_e( 'Send payment reminder to this student?', 'CTADZ-school-management' ); ?>');">
                                                    <span class="dashicons dashicons-email" style="vertical-align: middle;"></span>
                                                    <?php esc_html_e( 'Remind', 'CTADZ-school-management' ); ?>
                                                </button>
                                            </form>
                                        <?php else : ?>
                                            <span style="color: #999; font-size: 12px;"><?php esc_html_e( 'No email', 'CTADZ-school-management' ); ?></span>
                                        <?php endif; ?>
                                        
                                        <a href="?page=school-management-payments&action=record&enrollment_id=<?php echo $alert->enrollment_id; ?>" 
                                           class="button button-small button-primary">
                                            <span class="dashicons dashicons-money-alt" style="vertical-align: middle;"></span>
                                            <?php esc_html_e( 'Record', 'CTADZ-school-management' ); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php else : ?>
                <div style="text-align: center; padding: 60px 20px; background: white; border: 1px dashed #ddd; border-radius: 4px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #22c55e; display: block; margin-bottom: 16px;"></span>
                    <h3><?php esc_html_e( 'No Payment Alerts', 'CTADZ-school-management' ); ?></h3>
                    <p><?php esc_html_e( 'All payments are up to date for this category.', 'CTADZ-school-management' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Send payment reminder email
     */
    private static function send_payment_reminder( $schedule_id ) {
        global $wpdb;
        
        $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';

        // Get payment schedule details
        $schedule = $wpdb->get_row( $wpdb->prepare("
            SELECT ps.*, 
                   e.student_id,
                   s.name as student_name,
                   s.email,
                   c.name as course_name,
                   (ps.expected_amount - ps.paid_amount) as balance
            FROM $payment_schedules_table ps
            LEFT JOIN $enrollments_table e ON ps.enrollment_id = e.id
            LEFT JOIN $students_table s ON e.student_id = s.id
            LEFT JOIN $courses_table c ON e.course_id = c.id
            WHERE ps.id = %d
        ", $schedule_id ) );

        if ( ! $schedule || empty( $schedule->email ) ) {
            return [
                'success' => false,
                'message' => __( 'Cannot send reminder: Student email not found.', 'CTADZ-school-management' )
            ];
        }

        // Get school settings
        $settings = get_option( 'sm_school_settings', [] );
        $school_name = $settings['school_name'] ?? get_bloginfo( 'name' );

        // Prepare email
        $to = $schedule->email;
        $subject = sprintf(
            __( 'Payment Reminder - %s', 'CTADZ-school-management' ),
            $school_name
        );

        $message = sprintf(
            __( 'Dear %s,', 'CTADZ-school-management' ),
            $schedule->student_name
        ) . "\n\n";

        $message .= __( 'This is a friendly reminder about your upcoming payment.', 'CTADZ-school-management' ) . "\n\n";

        $message .= __( 'Payment Details:', 'CTADZ-school-management' ) . "\n";
        $message .= sprintf( __( 'Course: %s', 'CTADZ-school-management' ), $schedule->course_name ) . "\n";
        $message .= sprintf( __( 'Amount Due: %s DA', 'CTADZ-school-management' ), number_format( $schedule->balance, 2 ) ) . "\n";
        $message .= sprintf( __( 'Due Date: %s', 'CTADZ-school-management' ), date( 'F j, Y', strtotime( $schedule->due_date ) ) ) . "\n\n";

        if ( strtotime( $schedule->due_date ) < time() ) {
            $days_overdue = floor( ( time() - strtotime( $schedule->due_date ) ) / ( 60 * 60 * 24 ) );
            $message .= sprintf(
                __( 'Note: This payment is %d days overdue. Please make payment as soon as possible.', 'CTADZ-school-management' ),
                $days_overdue
            ) . "\n\n";
        }

        $message .= __( 'Please contact the school office if you have any questions.', 'CTADZ-school-management' ) . "\n\n";
        $message .= sprintf( __( 'Best regards,%s%s', 'CTADZ-school-management' ), "\n", $school_name );

        // Send email
        $sent = wp_mail( $to, $subject, $message );

        if ( $sent ) {
            return [
                'success' => true,
                'message' => sprintf(
                    __( 'Payment reminder sent successfully to %s', 'CTADZ-school-management' ),
                    $schedule->student_name
                )
            ];
        } else {
            return [
                'success' => false,
                'message' => __( 'Failed to send reminder email. Please check your email settings.', 'CTADZ-school-management' )
            ];
        }
    }
}

// Instantiate class
new SM_Payment_Alerts_Page();
