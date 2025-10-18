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
            __( 'School Management', 'school-management' ),
            __( 'School Management', 'school-management' ),
            'manage_school',
            'school-management',
            [ __CLASS__, 'render_dashboard' ],
            'dashicons-welcome-learn-more',
            6
        );

        // Dashboard submenu
        add_submenu_page(
            'school-management',
            __( 'Dashboard', 'school-management' ),
            '<span class="dashicons dashicons-dashboard" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Dashboard', 'school-management' ),
            'manage_school',
            'school-management',
            [ __CLASS__, 'render_dashboard' ]
        );

        // Students submenu
        add_submenu_page(
            'school-management',
            __( 'Students', 'school-management' ),
            '<span class="dashicons dashicons-groups" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Students', 'school-management' ),
            'manage_students',
            'school-management-students',
            [ 'SM_Students_Page', 'render_students_page' ]
        );

        // Teachers submenu
        add_submenu_page(
            'school-management',
            __( 'Teachers', 'school-management' ),
            '<span class="dashicons dashicons-businessperson" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Teachers', 'school-management' ),
            'manage_teachers',
            'school-management-teachers',
            [ 'SM_Teachers_Page', 'render_teachers_page' ]
        );

        // Courses submenu
        add_submenu_page(
            'school-management',
            __( 'Courses', 'school-management' ),
            '<span class="dashicons dashicons-book" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Courses', 'school-management' ),
            'manage_courses',
            'school-management-courses',
            [ 'SM_Courses_Page', 'render_courses_page' ]
        );

        // Levels submenu
        add_submenu_page(
            'school-management',
            __( 'Levels', 'school-management' ),
            '<span class="dashicons dashicons-awards" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Levels', 'school-management' ),
            'manage_levels',
            'school-management-levels',
            [ 'SM_Levels_Page', 'render_levels_page' ]
        );

        // Classrooms submenu
        add_submenu_page(
            'school-management',
            __( 'Classrooms', 'school-management' ),
            '<span class="dashicons dashicons-building" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Classrooms', 'school-management' ),
            'manage_classrooms',
            'school-management-classrooms',
            [ 'SM_Classrooms_Page', 'render_classrooms_page' ]
        );

        // Enrollments submenu
        add_submenu_page(
            'school-management',
            __( 'Enrollments', 'school-management' ),
            '<span class="dashicons dashicons-welcome-learn-more" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Enrollments', 'school-management' ),
            'manage_enrollments',
            'school-management-enrollments',
            [ 'SM_Enrollments_Page', 'render_enrollments_page' ]
        );

        // Payment Terms submenu
        add_submenu_page(
            'school-management',
            __( 'Payment Terms', 'school-management' ),
            '<span class="dashicons dashicons-calendar-alt" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Payment Terms', 'school-management' ),
            'manage_payments',
            'school-management-payment-terms',
            [ 'SM_Payment_Terms_Page', 'render_payment_terms_page' ]
        );

        // Payments submenu
        add_submenu_page(
            'school-management',
            __( 'Payments', 'school-management' ),
            '<span class="dashicons dashicons-money-alt" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Payments', 'school-management' ),
            'manage_payments',
            'school-management-payments',
            [ 'SM_Payments_Page', 'render_payments_page' ]
        );
    }
        /**
     * Add settings menu last (runs after calendar add-on menus)
     */
    public static function add_settings_menu() {
        add_submenu_page(
            'school-management',
            __( 'Settings', 'school-management' ),
            __( 'Settings', 'school-management' ),
            '<span class="dashicons dashicons-admin-generic" style="font-size: 17px; vertical-align: middle;"></span> ' . __( 'Settings', 'school-management' ),
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
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'school-management' ) );
        }

        global $wpdb;
    
        // Get school settings
        $settings = get_option( 'sm_school_settings', [] );
        $school_name = $settings['school_name'] ?? __( 'School Management System', 'school-management' );
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
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #0073aa 0%, #00a0d2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                    <span class="dashicons dashicons-welcome-learn-more" style="font-size: 40px; color: white;"></span>
                </div>
            <?php endif; ?>
            
            <div>
                <h1 style="margin: 0; font-size: 32px; color: #23282d;"><?php echo esc_html( $school_name ); ?></h1>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 16px;"><?php esc_html_e( 'Management Dashboard', 'school-management' ); ?></p>
            </div>
        </div>
        
<div class="sm-dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
    
    <!-- Students -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #0073aa; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 32px; color: #0073aa;"><?php echo intval( $students_count ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Total Students', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-groups" style="font-size: 40px; color: #0073aa; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-students" class="button"><?php esc_html_e( 'Manage Students', 'school-management' ); ?></a>
    </div>

    <!-- Teachers -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #f56e28; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 32px; color: #f56e28;"><?php echo intval( $teachers_count ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Active Teachers', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-businessperson" style="font-size: 40px; color: #f56e28; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-teachers" class="button"><?php esc_html_e( 'Manage Teachers', 'school-management' ); ?></a>
    </div>

    <!-- Courses -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #46b450; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 32px; color: #46b450;"><?php echo intval( $courses_count ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Active Courses', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-book" style="font-size: 40px; color: #46b450; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-courses" class="button"><?php esc_html_e( 'Manage Courses', 'school-management' ); ?></a>
    </div>

    <!-- Levels -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #9b59b6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 32px; color: #9b59b6;"><?php echo intval( $levels_count ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Course Levels', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-awards" style="font-size: 40px; color: #9b59b6; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-levels" class="button"><?php esc_html_e( 'Manage Levels', 'school-management' ); ?></a>
    </div>

    <!-- Enrollments -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #00a0d2; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 32px; color: #00a0d2;"><?php echo intval( $enrollments_count ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Active Enrollments', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-welcome-learn-more" style="font-size: 40px; color: #00a0d2; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-enrollments" class="button"><?php esc_html_e( 'Manage Enrollments', 'school-management' ); ?></a>
    </div>

    <!-- Classrooms -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #e67e22; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 32px; color: #e67e22;"><?php echo intval( $classrooms_count ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Classrooms', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-building" style="font-size: 40px; color: #e67e22; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-classrooms" class="button"><?php esc_html_e( 'Manage Classrooms', 'school-management' ); ?></a>
    </div>

    <!-- Payment Terms -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #16a085; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 32px; color: #16a085;"><?php echo intval( $payment_terms_count ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Payment Terms', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-calendar-alt" style="font-size: 40px; color: #16a085; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-payment-terms" class="button"><?php esc_html_e( 'Manage Terms', 'school-management' ); ?></a>
    </div>

    <!-- Payments -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #27ae60; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 20px; color: #27ae60;"><?php echo number_format( $outstanding_balance, 2 ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Outstanding Balance', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-money-alt" style="font-size: 40px; color: #27ae60; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-payments" class="button"><?php esc_html_e( 'Manage Payments', 'school-management' ); ?></a>
    </div>

    <?php if ( defined( 'SMC_VERSION' ) ) : ?>
    <!-- Schedules (Calendar Plugin) -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #8e44ad; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 32px; color: #8e44ad;"><?php echo intval( $schedules_count ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Active Schedules', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-calendar" style="font-size: 40px; color: #8e44ad; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-schedules" class="button"><?php esc_html_e( 'Manage Schedules', 'school-management' ); ?></a>
    </div>

    <!-- Events (Calendar Plugin) -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #c0392b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 32px; color: #c0392b;"><?php echo intval( $events_count ); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'Upcoming Events', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-megaphone" style="font-size: 40px; color: #c0392b; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-events" class="button"><?php esc_html_e( 'Manage Events', 'school-management' ); ?></a>
    </div>

    <!-- Calendar View -->
    <div class="sm-stat-card" style="background: white; padding: 20px; border-left: 4px solid #2c3e50; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <div style="flex: 1;">
                <h3 style="margin: 0; font-size: 24px; color: #2c3e50;">📅</h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php esc_html_e( 'School Calendar', 'school-management' ); ?></p>
            </div>
            <span class="dashicons dashicons-calendar-alt" style="font-size: 40px; color: #2c3e50; opacity: 0.3; margin-left: 15px;"></span>
        </div>
        <a href="?page=school-management-calendar" class="button"><?php esc_html_e( 'View Calendar', 'school-management' ); ?></a>
    </div>
    <?php endif; ?>

</div>
<div style="margin-top: 40px; background: white; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2><?php esc_html_e( 'Quick Actions', 'school-management' ); ?></h2>
            <p><?php esc_html_e( 'Common actions to get started:', 'school-management' ); ?></p>
            <p>
                <a href="?page=school-management-students&action=add" class="button"><?php esc_html_e( 'Add New Student', 'school-management' ); ?></a>
                <a href="?page=school-management-courses&action=add" class="button"><?php esc_html_e( 'Add New Course', 'school-management' ); ?></a>
                <a href="?page=school-management-enrollments&action=add" class="button"><?php esc_html_e( 'New Enrollment', 'school-management' ); ?></a>
                <a href="?page=school-management-teachers&action=add" class="button"><?php esc_html_e( 'Add New Teacher', 'school-management' ); ?></a>
                <a href="?page=school-management-classrooms&action=add" class="button"><?php esc_html_e( 'Add Classroom', 'school-management' ); ?></a>
                <a href="?page=school-management-payments" class="button"><?php esc_html_e( 'View Payments', 'school-management' ); ?></a>
                <?php if ( defined( 'SMC_VERSION' ) ) : ?>
                <a href="?page=school-management-calendar" class="button"><?php esc_html_e( 'View Calendar', 'school-management' ); ?></a>
                <?php endif; ?>
                <?php if ( current_user_can( 'manage_school_settings' ) ) : ?>
                <a href="?page=school-management-settings" class="button"><?php esc_html_e( 'Settings', 'school-management' ); ?></a>
                <?php endif; ?>
            </p>
        </div>
        <?php
    }
}
// Initialize the menu
SM_Admin_Menu::init();