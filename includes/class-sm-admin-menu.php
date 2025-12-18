<?php
/**
 * Admin Menu Class
 *
 * @package SchoolManagement
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Admin_Menu {

    /**
     * Initialize hooks
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menus' ], 10 );
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_menu' ], 100 ); // Very late priority
        add_action( 'admin_head', [ __CLASS__, 'add_menu_icon_styles' ] );

    }

 /**
     * Add custom styles for colored menu icons
     */
    public static function add_menu_icon_styles() {
        ?>
        <style>
            /* Menu icon utility class */
            .sm-menu-icon {
                font-size: 17px !important;
                vertical-align: middle !important;
            }

            /* Main menu icon - School Management */
            #adminmenu .toplevel_page_school-management .wp-menu-image img {
                opacity: 1 !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-menu-image:before {
                color: #0073aa !important;
            }
            
            /* Submenu icons - force color on ALL states */
            #adminmenu .toplevel_page_school-management .wp-submenu li a .dashicons {
                color: inherit !important;
            }
            
            /* Dashboard icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="page=school-management"]:not([href*="-"]) .dashicons {
                color: #0073aa !important;
            }
            
            /* Students icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="students"] .dashicons {
                color: #0073aa !important;
            }
            
            /* Teachers icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="teachers"] .dashicons {
                color: #f56e28 !important;
            }
            
            /* Courses icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="courses"] .dashicons {
                color: #46b450 !important;
            }
            
            /* Levels icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="levels"] .dashicons {
                color: #9b59b6 !important;
            }
            
            /* Classrooms icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="classrooms"] .dashicons {
                color: #e67e22 !important;
            }
            
            /* Enrollments icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="enrollments"] .dashicons {
                color: #00a0d2 !important;
            }
            
            /* Payment Terms icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="payment-terms"] .dashicons {
                color: #16a085 !important;
            }
            
            /* Payments icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="payments"]:not([href*="payment-terms"]) .dashicons {
                color: #27ae60 !important;
            }
            
            /* Calendar icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="calendar"] .dashicons {
                color: #8e44ad !important;
            }
            
            /* Schedules icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="schedules"] .dashicons {
                color: #8e44ad !important;
            }
            
            /* Events icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="events"] .dashicons {
                color: #c0392b !important;
            }
            
            /* Settings icon */
            #adminmenu li.toplevel_page_school-management .wp-submenu li a[href*="settings"] .dashicons {
                color: #82878c !important;
            }
            
            /* Keep colors on hover and active states */
            #adminmenu .toplevel_page_school-management .wp-submenu li a:hover .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current .dashicons {
                color: inherit !important;
            }
            
            /* Also target the current/active menu item specifically */
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="students"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="students"] .dashicons {
                color: #0073aa !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="teachers"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="teachers"] .dashicons {
                color: #f56e28 !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="courses"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="courses"] .dashicons {
                color: #46b450 !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="levels"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="levels"] .dashicons {
                color: #9b59b6 !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="classrooms"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="classrooms"] .dashicons {
                color: #e67e22 !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="enrollments"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="enrollments"] .dashicons {
                color: #00a0d2 !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="payment-terms"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="payment-terms"] .dashicons {
                color: #16a085 !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="payments"]:not([href*="payment-terms"]) .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="payments"]:not([href*="payment-terms"]) .dashicons {
                color: #27ae60 !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="calendar"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="calendar"] .dashicons {
                color: #8e44ad !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="schedules"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="schedules"] .dashicons {
                color: #8e44ad !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="events"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="events"] .dashicons {
                color: #c0392b !important;
            }
            
            #adminmenu .toplevel_page_school-management .wp-submenu li.current a[href*="settings"] .dashicons,
            #adminmenu .toplevel_page_school-management .wp-submenu li a.current[href*="settings"] .dashicons {
                color: #82878c !important;
            }
        </style>
        <?php
    }
     /**
     * Add plugin menus
     */
    public static function add_menus() {
        // Top-level menu
        add_menu_page(
            __( 'School Management', 'CTADZ-school-management' ),
            __( 'School Management', 'CTADZ-school-management' ),
            'manage_school',
            'school-management',
            [ __CLASS__, 'render_dashboard' ],
            'dashicons-welcome-learn-more',
            6
        );

        // Dashboard submenu
        add_submenu_page(
            'school-management',
            __( 'Dashboard', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-dashboard sm-menu-icon"></span> ' . __( 'Dashboard', 'CTADZ-school-management' ),
            'manage_school',
            'school-management',
            [ __CLASS__, 'render_dashboard' ]
        );

        // Students submenu
        add_submenu_page(
            'school-management',
            __( 'Students', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-groups sm-menu-icon"></span> ' . __( 'Students', 'CTADZ-school-management' ),
            'manage_students',
            'school-management-students',
            [ 'SM_Students_Page', 'render_students_page' ]
        );

        // Teachers submenu
        add_submenu_page(
            'school-management',
            __( 'Teachers', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-businessperson" class="sm-menu-icon"></span> ' . __( 'Teachers', 'CTADZ-school-management' ),
            'manage_teachers',
            'school-management-teachers',
            [ 'SM_Teachers_Page', 'render_teachers_page' ]
        );

        // Courses submenu
        add_submenu_page(
            'school-management',
            __( 'Courses', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-book" class="sm-menu-icon"></span> ' . __( 'Courses', 'CTADZ-school-management' ),
            'manage_courses',
            'school-management-courses',
            [ 'SM_Courses_Page', 'render_courses_page' ]
        );

        // Levels submenu
        add_submenu_page(
            'school-management',
            __( 'Levels', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-awards" class="sm-menu-icon"></span> ' . __( 'Levels', 'CTADZ-school-management' ),
            'manage_levels',
            'school-management-levels',
            [ 'SM_Levels_Page', 'render_levels_page' ]
        );

        // Classrooms submenu
        add_submenu_page(
            'school-management',
            __( 'Classrooms', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-building" class="sm-menu-icon"></span> ' . __( 'Classrooms', 'CTADZ-school-management' ),
            'manage_classrooms',
            'school-management-classrooms',
            [ 'SM_Classrooms_Page', 'render_classrooms_page' ]
        );

        // Enrollments submenu
        add_submenu_page(
            'school-management',
            __( 'Enrollments', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-welcome-learn-more" class="sm-menu-icon"></span> ' . __( 'Enrollments', 'CTADZ-school-management' ),
            'manage_enrollments',
            'school-management-enrollments',
            [ 'SM_Enrollments_Page', 'render_enrollments_page' ]
        );

        // Attendance submenu
        add_submenu_page(
            'school-management',
            __( 'Attendance', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-yes-alt" class="sm-menu-icon"></span> ' . __( 'Attendance', 'CTADZ-school-management' ),
            'view_attendance',
            'school-management-attendance',
            [ 'SM_Attendance_Page', 'render_attendance_page' ]
        );

        // Payment Terms submenu
        add_submenu_page(
            'school-management',
            __( 'Payment Terms', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-calendar-alt" class="sm-menu-icon"></span> ' . __( 'Payment Terms', 'CTADZ-school-management' ),
            'manage_payments',
            'school-management-payment-terms',
            [ 'SM_Payment_Terms_Page', 'render_payment_terms_page' ]
        );

        // Payments submenu
        add_submenu_page(
            'school-management',
            __( 'Payments', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-money-alt" class="sm-menu-icon"></span> ' . __( 'Payments', 'CTADZ-school-management' ),
            'manage_payments',
            'school-management-payments',
            [ 'SM_Payments_Page', 'render_payments_page' ]
        );

        // Payment Alerts submenu
        add_submenu_page(
            'school-management',
            __( 'Payment Alerts', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-warning sm-menu-icon" style="color: #dc2626;"></span> ' . __( 'Payment Alerts', 'CTADZ-school-management' ),
            'manage_payments',
            'school-management-payment-alerts',
            [ 'SM_Payment_Alerts_Page', 'render_page' ]
        );
    }

    /**
     * Add settings menu last (runs after calendar add-on menus)
     */
    public static function add_settings_menu() {
        add_submenu_page(
            'school-management',
            __( 'Settings', 'CTADZ-school-management' ),
            '<span class="dashicons dashicons-admin-generic" class="sm-menu-icon"></span> ' . __( 'Settings', 'CTADZ-school-management' ),
            'manage_school_settings',
            'school-management-settings',
            [ 'SM_Settings_Page', 'render_settings_page' ]
        );
    }

    /**
    * Render Dashboard page
    */
    public static function render_dashboard() {
        // Security check
        if ( ! current_user_can( 'manage_school' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'CTADZ-school-management' ) );
        }

        global $wpdb;
    
        // Get school settings
        $settings = get_option( 'sm_school_settings', [] );
        $school_name = $settings['school_name'] ?? __( 'School Management System', 'CTADZ-school-management' );
        $school_logo = $settings['logo'] ?? '';
    
// Get statistics
        $students_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_students" );
        $levels_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_levels WHERE is_active = 1" );
        $teachers_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_teachers WHERE is_active = 1" );
        $courses_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_courses WHERE is_active = 1" );
        $enrollments_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_enrollments WHERE status = 'active'" );
        $classrooms_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_classrooms WHERE is_active = 1" );
        $payment_terms_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_payment_terms WHERE is_active = 1" );
        
        // Payments statistics
        $total_expected = floatval( $wpdb->get_var( "SELECT SUM(expected_amount) FROM {$wpdb->prefix}sm_payment_schedules" ) );
        $total_paid = floatval( $wpdb->get_var( "SELECT SUM(paid_amount) FROM {$wpdb->prefix}sm_payment_schedules" ) );
        $outstanding_balance = $total_expected - $total_paid;
        
        // Calendar statistics (if calendar plugin is active)
        $schedules_count = 0;
        $events_count = 0;
        if ( defined( 'SMC_VERSION' ) ) {
            $schedules_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}smc_schedules WHERE is_active = 1" );
            $events_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}smc_events WHERE event_date >= CURDATE()" );
        }

        ?>
        <div class="wrap">
        <!-- School Header with Logo and Name -->
        <div class="sm-dashboard-header" style="display: flex; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #0073aa;">
            <?php if ( $school_logo ) : ?>
                <img src="<?php echo esc_url( $school_logo ); ?>" alt="<?php echo esc_attr( $school_name ); ?>" style="max-height: 80px; max-width: 200px; margin-right: 20px; object-fit: contain;" />
            <?php else : ?>
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #0073aa 0%, #00a0d2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;" role="img" aria-label="<?php esc_attr_e( 'School logo', 'CTADZ-school-management' ); ?>">
                    <span class="dashicons dashicons-welcome-learn-more" style="font-size: 40px; color: white;" aria-hidden="true"></span>
                </div>
            <?php endif; ?>

            <div>
                <h1 style="margin: 0; font-size: 32px; color: #23282d;"><?php echo esc_html( $school_name ); ?></h1>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 16px;"><?php esc_html_e( 'Management Dashboard', 'CTADZ-school-management' ); ?></p>
            </div>
        </div>

<div class="sm-dashboard-widgets">
    
    <!-- Students Widget -->
    <div class="sm-widget" style="border-left: 4px solid #0073aa;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Total Students', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-groups" style="font-size: 24px; color: #0073aa; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #0073aa;"><?php echo intval( $students_count ); ?></div>
        <a href="?page=school-management-students" class="button button-primary" aria-label="<?php esc_attr_e( 'Manage Students', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Students', 'CTADZ-school-management' ); ?></a>
    </div>

    <!-- Teachers Widget -->
    <div class="sm-widget" style="border-left: 4px solid #f56e28;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Active Teachers', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-businessperson" style="font-size: 24px; color: #f56e28; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #f56e28;"><?php echo intval( $teachers_count ); ?></div>
        <a href="?page=school-management-teachers" class="button" aria-label="<?php esc_attr_e( 'Manage Teachers', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Teachers', 'CTADZ-school-management' ); ?></a>
    </div>

    <!-- Courses Widget -->
    <div class="sm-widget" style="border-left: 4px solid #46b450;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Active Courses', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-book" style="font-size: 24px; color: #46b450; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #46b450;"><?php echo intval( $courses_count ); ?></div>
        <a href="?page=school-management-courses" class="button" aria-label="<?php esc_attr_e( 'Manage Courses', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Courses', 'CTADZ-school-management' ); ?></a>
    </div>

    <!-- Levels Widget -->
    <div class="sm-widget" style="border-left: 4px solid #9b59b6;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Course Levels', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-awards" style="font-size: 24px; color: #9b59b6; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #9b59b6;"><?php echo intval( $levels_count ); ?></div>
        <a href="?page=school-management-levels" class="button" aria-label="<?php esc_attr_e( 'Manage Levels', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Levels', 'CTADZ-school-management' ); ?></a>
    </div>

    <!-- Enrollments Widget -->
    <div class="sm-widget" style="border-left: 4px solid #00a0d2;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Active Enrollments', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-welcome-learn-more" style="font-size: 24px; color: #00a0d2; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #00a0d2;"><?php echo intval( $enrollments_count ); ?></div>
        <a href="?page=school-management-enrollments" class="button" aria-label="<?php esc_attr_e( 'Manage Enrollments', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Enrollments', 'CTADZ-school-management' ); ?></a>
    </div>

    <!-- Classrooms Widget -->
    <div class="sm-widget" style="border-left: 4px solid #e67e22;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Classrooms', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-building" style="font-size: 24px; color: #e67e22; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #e67e22;"><?php echo intval( $classrooms_count ); ?></div>
        <a href="?page=school-management-classrooms" class="button" aria-label="<?php esc_attr_e( 'Manage Classrooms', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Classrooms', 'CTADZ-school-management' ); ?></a>
    </div>

    <!-- Payment Terms Widget -->
    <div class="sm-widget" style="border-left: 4px solid #16a085;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Payment Terms', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-calendar-alt" style="font-size: 24px; color: #16a085; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #16a085;"><?php echo intval( $payment_terms_count ); ?></div>
        <a href="?page=school-management-payment-terms" class="button" aria-label="<?php esc_attr_e( 'Manage Terms', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Terms', 'CTADZ-school-management' ); ?></a>
    </div>

    <!-- Payments Widget -->
    <div class="sm-widget" style="border-left: 4px solid #27ae60;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Outstanding Balance', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-money-alt" style="font-size: 24px; color: #27ae60; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #27ae60; font-size: 24px;"><?php echo number_format( $outstanding_balance, 2 ); ?> DZD</div>
        <a href="?page=school-management-payments" class="button" aria-label="<?php esc_attr_e( 'Manage Payments', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Payments', 'CTADZ-school-management' ); ?></a>
    </div>


    <!-- Payment Alerts -->
    <?php
    // Get payment alerts data
    $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
    $enrollments_table = $wpdb->prefix . 'sm_enrollments';

    // Count overdue payments
    $overdue_count = $wpdb->get_var("
        SELECT COUNT(*)
        FROM $payment_schedules_table ps
        LEFT JOIN $enrollments_table e ON ps.enrollment_id = e.id
        WHERE ps.status IN ('pending', 'partial')
        AND ps.due_date < CURDATE()
        AND e.status = 'active'
    ");

    // Count due this week (1-7 days)
    $week_count = $wpdb->get_var("
        SELECT COUNT(*)
        FROM $payment_schedules_table ps
        LEFT JOIN $enrollments_table e ON ps.enrollment_id = e.id
        WHERE ps.status IN ('pending', 'partial')
        AND ps.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND e.status = 'active'
    ");

    // Count due next week (8-14 days)
    $next_week_count = $wpdb->get_var("
        SELECT COUNT(*)
        FROM $payment_schedules_table ps
        LEFT JOIN $enrollments_table e ON ps.enrollment_id = e.id
        WHERE ps.status IN ('pending', 'partial')
        AND ps.due_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 8 DAY) AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        AND e.status = 'active'
    ");

    $total_alerts = $overdue_count + $week_count + $next_week_count;
    $alert_color = '#dc2626'; // Red for alerts
    $alert_text = '';

    if ( $overdue_count > 0 ) {
        $alert_text = sprintf( _n( '%d overdue', '%d overdue', $overdue_count, 'CTADZ-school-management' ), $overdue_count );
        $alert_color = '#dc2626'; // Red
    } elseif ( $week_count > 0 ) {
        $alert_text = sprintf( _n( '%d due this week', '%d due this week', $week_count, 'CTADZ-school-management' ), $week_count );
        $alert_color = '#f59e0b'; // Orange
    } elseif ( $next_week_count > 0 ) {
        $alert_text = sprintf( _n( '%d due next week', '%d due next week', $next_week_count, 'CTADZ-school-management' ), $next_week_count );
        $alert_color = '#eab308'; // Yellow
    } else {
        $alert_text = __( 'All up to date', 'CTADZ-school-management' );
        $alert_color = '#22c55e'; // Green
    }
    ?>
    <!-- Payment Alerts Widget -->
    <div class="sm-widget" style="border-left: 4px solid <?php echo esc_attr( $alert_color ); ?>;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Payment Alerts', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-warning" style="font-size: 24px; color: <?php echo esc_attr( $alert_color ); ?>; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: <?php echo esc_attr( $alert_color ); ?>;"><?php echo intval( $total_alerts ); ?></div>
        <?php if ( $alert_text ) : ?>
            <p class="sm-widget-label" style="color: <?php echo esc_attr( $alert_color ); ?>; margin-bottom: 15px;">
                <?php echo esc_html( $alert_text ); ?>
            </p>
        <?php endif; ?>
        <a href="?page=school-management-payment-alerts" class="button" aria-label="<?php esc_attr_e( 'View Alerts', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'View Alerts', 'CTADZ-school-management' ); ?></a>
    </div>

    <?php if ( defined( 'SMC_VERSION' ) ) : ?>
    <!-- Schedules Widget (Calendar Plugin) -->
    <div class="sm-widget" style="border-left: 4px solid #8e44ad;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Active Schedules', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-calendar" style="font-size: 24px; color: #8e44ad; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #8e44ad;"><?php echo intval( $schedules_count ); ?></div>
        <a href="?page=school-management-schedules" class="button" aria-label="<?php esc_attr_e( 'Manage Schedules', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Schedules', 'CTADZ-school-management' ); ?></a>
    </div>

    <!-- Events Widget (Calendar Plugin) -->
    <div class="sm-widget" style="border-left: 4px solid #c0392b;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'Upcoming Events', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-megaphone" style="font-size: 24px; color: #c0392b; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #c0392b;"><?php echo intval( $events_count ); ?></div>
        <a href="?page=school-management-events" class="button" aria-label="<?php esc_attr_e( 'Manage Events', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'Manage Events', 'CTADZ-school-management' ); ?></a>
    </div>

    <!-- Calendar View Widget -->
    <div class="sm-widget" style="border-left: 4px solid #2c3e50;">
        <div class="sm-widget-header">
            <h3 class="sm-widget-title"><?php esc_html_e( 'School Calendar', 'CTADZ-school-management' ); ?></h3>
            <span class="dashicons dashicons-calendar-alt" style="font-size: 24px; color: #2c3e50; opacity: 0.5;" aria-hidden="true"></span>
        </div>
        <div class="sm-widget-value" style="color: #2c3e50; font-size: 40px;" role="img" aria-label="<?php esc_attr_e( 'Calendar', 'CTADZ-school-management' ); ?>">📅</div>
        <a href="?page=school-management-calendar" class="button" aria-label="<?php esc_attr_e( 'View Calendar', 'CTADZ-school-management' ); ?>"><?php esc_html_e( 'View Calendar', 'CTADZ-school-management' ); ?></a>
    </div>
    <?php endif; ?>

</div>

<!-- Data Visualization Section -->
<section aria-labelledby="data-viz-heading">
    <h2 id="data-viz-heading" class="sr-only"><?php esc_html_e( 'Data Visualizations', 'CTADZ-school-management' ); ?></h2>
    <div class="sm-dashboard-widgets" style="margin-top: 30px;">

        <!-- Enrollment Trends Chart -->
        <div class="sm-widget" style="grid-column: span 2;">
            <div class="sm-widget-header">
                <h3 class="sm-widget-title"><?php esc_html_e( 'Enrollment Trends', 'CTADZ-school-management' ); ?></h3>
            </div>
            <canvas id="enrollmentTrendsChart" style="max-height: 300px;" role="img" aria-label="<?php esc_attr_e( 'Line chart showing enrollment trends over the last 6 months', 'CTADZ-school-management' ); ?>"></canvas>
        </div>

        <!-- Payment Status Breakdown -->
        <div class="sm-widget">
            <div class="sm-widget-header">
                <h3 class="sm-widget-title"><?php esc_html_e( 'Payment Status', 'CTADZ-school-management' ); ?></h3>
            </div>
            <canvas id="paymentStatusChart" style="max-height: 250px;" role="img" aria-label="<?php esc_attr_e( 'Doughnut chart showing payment status breakdown', 'CTADZ-school-management' ); ?>"></canvas>
        </div>

        <!-- Students by Level -->
        <div class="sm-widget">
            <div class="sm-widget-header">
                <h3 class="sm-widget-title"><?php esc_html_e( 'Students by Level', 'CTADZ-school-management' ); ?></h3>
            </div>
            <canvas id="studentsByLevelChart" style="max-height: 250px;" role="img" aria-label="<?php esc_attr_e( 'Bar chart showing student distribution across levels', 'CTADZ-school-management' ); ?>"></canvas>
        </div>

    </div>
</section>

<nav aria-labelledby="quick-actions-heading" style="margin-top: 40px; background: white; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2 id="quick-actions-heading"><?php esc_html_e( 'Quick Actions', 'CTADZ-school-management' ); ?></h2>
            <p><?php esc_html_e( 'Common actions to get started:', 'CTADZ-school-management' ); ?></p>
            <p>
                <a href="?page=school-management-students&action=add" class="button"><?php esc_html_e( 'Add New Student', 'CTADZ-school-management' ); ?></a>
                <a href="?page=school-management-courses&action=add" class="button"><?php esc_html_e( 'Add New Course', 'CTADZ-school-management' ); ?></a>
                <a href="?page=school-management-enrollments&action=add" class="button"><?php esc_html_e( 'New Enrollment', 'CTADZ-school-management' ); ?></a>
                <a href="?page=school-management-teachers&action=add" class="button"><?php esc_html_e( 'Add New Teacher', 'CTADZ-school-management' ); ?></a>
                <a href="?page=school-management-classrooms&action=add" class="button"><?php esc_html_e( 'Add Classroom', 'CTADZ-school-management' ); ?></a>
                <a href="?page=school-management-payments" class="button"><?php esc_html_e( 'View Payments', 'CTADZ-school-management' ); ?></a>
                <?php if ( defined( 'SMC_VERSION' ) ) : ?>
                <a href="?page=school-management-calendar" class="button"><?php esc_html_e( 'View Calendar', 'CTADZ-school-management' ); ?></a>
                <?php endif; ?>
                <?php if ( current_user_can( 'manage_school_settings' ) ) : ?>
                <a href="?page=school-management-settings" class="button"><?php esc_html_e( 'Settings', 'CTADZ-school-management' ); ?></a>
                <?php endif; ?>
            </p>
        </nav>

        <?php
        // Prepare chart data

        // Enrollment Trends (last 6 months)
        $enrollment_data = [];
        for ( $i = 5; $i >= 0; $i-- ) {
            $month_start = date( 'Y-m-01', strtotime( "-$i months" ) );
            $month_end = date( 'Y-m-t', strtotime( "-$i months" ) );
            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}sm_enrollments
                 WHERE created_at BETWEEN %s AND %s",
                $month_start, $month_end
            ) );
            $enrollment_data[] = [
                'month' => date( 'M Y', strtotime( $month_start ) ),
                'count' => intval( $count )
            ];
        }

        // Payment Status Breakdown
        $paid_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_payment_schedules WHERE status = 'paid'" );
        $partial_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_payment_schedules WHERE status = 'partial'" );
        $pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}sm_payment_schedules WHERE status = 'pending'" );

        // Students by Level
        $students_by_level = $wpdb->get_results(
            "SELECT l.name as level_name, COUNT(s.id) as student_count
             FROM {$wpdb->prefix}sm_levels l
             LEFT JOIN {$wpdb->prefix}sm_students s ON l.id = s.level_id
             WHERE l.is_active = 1
             GROUP BY l.id, l.name
             ORDER BY l.name"
        );
        ?>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js default config
            Chart.defaults.responsive = true;
            Chart.defaults.maintainAspectRatio = true;

            // Enrollment Trends Chart (Line)
            const enrollmentCtx = document.getElementById('enrollmentTrendsChart');
            if (enrollmentCtx) {
                new Chart(enrollmentCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode( array_column( $enrollment_data, 'month' ) ); ?>,
                        datasets: [{
                            label: '<?php esc_html_e( 'Enrollments', 'CTADZ-school-management' ); ?>',
                            data: <?php echo json_encode( array_column( $enrollment_data, 'count' ) ); ?>,
                            borderColor: '#0073aa',
                            backgroundColor: 'rgba(0, 115, 170, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }

            // Payment Status Chart (Doughnut)
            const paymentCtx = document.getElementById('paymentStatusChart');
            if (paymentCtx) {
                new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [
                            '<?php esc_html_e( 'Paid', 'CTADZ-school-management' ); ?>',
                            '<?php esc_html_e( 'Partial', 'CTADZ-school-management' ); ?>',
                            '<?php esc_html_e( 'Pending', 'CTADZ-school-management' ); ?>'
                        ],
                        datasets: [{
                            data: [
                                <?php echo intval( $paid_count ); ?>,
                                <?php echo intval( $partial_count ); ?>,
                                <?php echo intval( $pending_count ); ?>
                            ],
                            backgroundColor: ['#46b450', '#f0ad4e', '#d63638']
                        }]
                    },
                    options: {
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }

            // Students by Level Chart (Bar)
            const levelCtx = document.getElementById('studentsByLevelChart');
            if (levelCtx) {
                new Chart(levelCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode( array_column( $students_by_level, 'level_name' ) ); ?>,
                        datasets: [{
                            label: '<?php esc_html_e( 'Students', 'CTADZ-school-management' ); ?>',
                            data: <?php echo json_encode( array_map( 'intval', array_column( $students_by_level, 'student_count' ) ) ); ?>,
                            backgroundColor: '#9b59b6'
                        }]
                    },
                    options: {
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
    }
}
// Initialize the menu
SM_Admin_Menu::init();