<?php
/**
 * Admin Redirect and Cleanup
 * Redirects users to School Management dashboard and hides WordPress clutter
 *
 * @package SchoolManagement
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Admin_Redirect {

    /**
     * Initialize hooks
     */
    public static function init() {
        // Redirect to School Management dashboard after login
        add_filter( 'login_redirect', [ __CLASS__, 'redirect_after_login' ], 10, 3 );
        
        // Redirect away from WordPress dashboard
        add_action( 'admin_init', [ __CLASS__, 'redirect_dashboard' ] );
        
        // Remove WordPress admin menu items
        add_action( 'admin_menu', [ __CLASS__, 'remove_admin_menus' ], 999 );
        
        // Hide WordPress admin bar items
        add_action( 'admin_bar_menu', [ __CLASS__, 'remove_admin_bar_items' ], 999 );
        
        // Custom admin footer text
        add_filter( 'admin_footer_text', [ __CLASS__, 'custom_admin_footer' ] );
        
        // Remove WordPress version from footer
        add_filter( 'update_footer', '__return_empty_string', 999 );
    }

    /**
     * Redirect users to School Management dashboard after login
     */
    public static function redirect_after_login( $redirect_to, $request, $user ) {
        // Check if user has School Management capabilities
        if ( isset( $user->ID ) && ( 
            user_can( $user, 'manage_school' ) || 
            user_can( $user, 'manage_school_settings' ) 
        ) ) {
            // Redirect to School Management dashboard
            return admin_url( 'admin.php?page=school-management' );
        }
        
        return $redirect_to;
    }

    /**
     * Redirect away from WordPress dashboard to School Management dashboard
     */
    public static function redirect_dashboard() {
        global $pagenow;
        
        // Only redirect on main dashboard page
        if ( $pagenow === 'index.php' ) {
            // Check if user has School Management capabilities
            if ( current_user_can( 'manage_school' ) || current_user_can( 'manage_school_settings' ) ) {
                wp_safe_redirect( admin_url( 'admin.php?page=school-management' ) );
                exit;
            }
        }
    }

    /**
     * Remove unnecessary WordPress admin menu items
     */
    public static function remove_admin_menus() {
        // For School Admin role (not Site Administrator)
        if ( current_user_can( 'manage_school' ) && ! current_user_can( 'manage_school_settings' ) ) {
            // Remove WordPress core menus
            remove_menu_page( 'index.php' );                  // Dashboard
            remove_menu_page( 'edit.php' );                   // Posts
            remove_menu_page( 'upload.php' );                 // Media
            remove_menu_page( 'edit.php?post_type=page' );    // Pages
            remove_menu_page( 'edit-comments.php' );          // Comments
            remove_menu_page( 'themes.php' );                 // Appearance
            remove_menu_page( 'plugins.php' );                // Plugins
            remove_menu_page( 'users.php' );                  // Users
            remove_menu_page( 'tools.php' );                  // Tools
            remove_menu_page( 'options-general.php' );        // Settings
            
            // Remove some submenu items
            remove_submenu_page( 'school-management', 'school-management-settings' );
        }
    }

    /**
     * Remove WordPress admin bar items
     */
    public static function remove_admin_bar_items( $wp_admin_bar ) {
        // For School Admin role (not Site Administrator)
        if ( current_user_can( 'manage_school' ) && ! current_user_can( 'manage_school_settings' ) ) {
            // Remove WordPress items
            $wp_admin_bar->remove_node( 'wp-logo' );          // WordPress logo
            $wp_admin_bar->remove_node( 'about' );            // About WordPress
            $wp_admin_bar->remove_node( 'wporg' );            // WordPress.org
            $wp_admin_bar->remove_node( 'documentation' );    // Documentation
            $wp_admin_bar->remove_node( 'support-forums' );   // Support
            $wp_admin_bar->remove_node( 'feedback' );         // Feedback
            $wp_admin_bar->remove_node( 'updates' );          // Updates
            $wp_admin_bar->remove_node( 'comments' );         // Comments
            $wp_admin_bar->remove_node( 'new-content' );      // + New
            $wp_admin_bar->remove_node( 'dashboard' );        // Dashboard link
        }
    }

    /**
     * Custom admin footer text
     */
    public static function custom_admin_footer( $text ) {
        if ( current_user_can( 'manage_school' ) ) {
            $settings = get_option( 'sm_school_settings', [] );
            $school_name = $settings['school_name'] ?? __( 'School Management System', 'school-management' );
            
            return sprintf(
                __( 'Thank you for using %s', 'school-management' ),
                '<strong>' . esc_html( $school_name ) . '</strong>'
            );
        }
        
        return $text;
    }
}

// Initialize
SM_Admin_Redirect::init();