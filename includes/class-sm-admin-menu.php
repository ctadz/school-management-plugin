<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Admin_Menu {

    /**
     * Initialize hooks
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menus' ] );
    }

    /**
     * Add plugin menus
     */
    public static function add_menus() {
        // Top-level menu
        add_menu_page(
            __( 'School Management', 'school-management' ),
            __( 'School Management', 'school-management' ),
            'manage_options',
            'school-management',
            [ __CLASS__, 'render_dashboard' ],
            'dashicons-welcome-learn-more',
            6
        );

        // Dashboard submenu
        add_submenu_page(
            'school-management',
            __( 'Dashboard', 'school-management' ),
            __( 'Dashboard', 'school-management' ),
            'manage_options',
            'school-management',
            [ __CLASS__, 'render_dashboard' ]
        );

        // Students submenu
        add_submenu_page(
            'school-management',
            __( 'Students', 'school-management' ),
            __( 'Students', 'school-management' ),
            'manage_options',
            'school-management-students',
            [ 'SM_Students_Page', 'render_students_page' ]
        );

        // Courses submenu
        add_submenu_page(
            'school-management',
            __( 'Courses', 'school-management' ),
            __( 'Courses', 'school-management' ),
            'manage_options',
            'school-management-courses',
            [ 'SM_Courses_Page', 'render_courses_page' ]
        );

        // Enrollments submenu
        add_submenu_page(
            'school-management',
            __( 'Enrollments', 'school-management' ),
            __( 'Enrollments', 'school-management' ),
            'manage_options',
            'school-management-enrollments',
            [ 'SM_Enrollments_Page', 'render_enrollments_page' ]
        );

        // Payments submenu
        add_submenu_page(
            'school-management',
            __( 'Payments', 'school-management' ),
            __( 'Payments', 'school-management' ),
            'manage_options',
            'school-management-payments',
            [ 'SM_Payments_Page', 'render_payments_page' ]
        );

        // Teachers submenu
        add_submenu_page(
            'school-management',
            __( 'Teachers', 'school-management' ),
            __( 'Teachers', 'school-management' ),
            'manage_options',
            'school-management-teachers',
            [ 'SM_Teachers_Page', 'render_teachers_page' ]
        );

        // Levels submenu
        add_submenu_page(
            'school-management',
            __( 'Levels', 'school-management' ),
            __( 'Levels', 'school-management' ),
            'manage_options',
            'school-management-levels',
            [ 'SM_Levels_Page', 'render_levels_page' ]
        );

        // Payment Terms submenu
        add_submenu_page(
            'school-management',
            __( 'Payment Terms', 'school-management' ),
            __( 'Payment Terms', 'school-management' ),
            'manage_options',
            'school-management-payment-terms',
            [ 'SM_Payment_Terms_Page', 'render_payment_terms_page' ]
        );

        // Settings submenu
        add_submenu_page(
            'school-management',
            __( 'Settings', 'school-management' ),
            __( 'Settings', 'school-management' ),
            'manage_options',
            'school-management-settings',
            [ 'SM_Settings_Page', 'render_settings_page' ]
        );
    }

    /**
    * Render Dashboard page
    */
    public static function render_dashboard() {
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

</div>
        <div style="margin-top: 40px; background: white; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2><?php esc_html_e( 'Quick Actions', 'school-management' ); ?></h2>
            <p><?php esc_html_e( 'Common actions to get started:', 'school-management' ); ?></p>
            <p>
                <a href="?page=school-management-students&action=add" class="button button-primary"><?php esc_html_e( 'Add New Student', 'school-management' ); ?></a>
                <a href="?page=school-management-courses&action=add" class="button button-primary"><?php esc_html_e( 'Add New Course', 'school-management' ); ?></a>
                <a href="?page=school-management-enrollments&action=add" class="button button-primary"><?php esc_html_e( 'New Enrollment', 'school-management' ); ?></a>
                <a href="?page=school-management-teachers&action=add" class="button"><?php esc_html_e( 'Add New Teacher', 'school-management' ); ?></a>
                <a href="?page=school-management-settings" class="button"><?php esc_html_e( 'Settings', 'school-management' ); ?></a>
            </p>
        </div>
    </div>
    <?php
    }
}
// Initialize the menu
SM_Admin_Menu::init();