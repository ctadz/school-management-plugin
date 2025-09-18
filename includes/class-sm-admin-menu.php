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

        // Dashboard submenu (renames the duplicate)
        add_submenu_page(
            'school-management',
            __( 'Dashboard', 'school-management' ),
            __( 'Dashboard', 'school-management' ),
            'manage_options',
            'school-management',
            [ __CLASS__, 'render_dashboard' ]
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
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'School Management Dashboard', 'school-management' ); ?></h1>
            <p><?php esc_html_e( 'Welcome to your School Management plugin! From here, you can manage students, teachers, courses, and more.', 'school-management' ); ?></p>
        </div>
        <?php
    }
}

// Initialize the menu
SM_Admin_Menu::init();
