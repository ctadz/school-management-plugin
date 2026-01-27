<?php
/**
 * Role Management Class
 *
 * @package SchoolManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Roles {

    /**
     * Initialize roles
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'add_roles' ) );
        add_action( 'admin_init', array( __CLASS__, 'redirect_teachers_from_dashboard' ) );
        add_action( 'after_setup_theme', array( __CLASS__, 'hide_admin_bar_for_teachers' ) );
        add_action( 'admin_menu', array( __CLASS__, 'customize_teacher_menu' ), 999 );
    }

    /**
     * Customize admin menu for teachers and accountants
     */
    public static function customize_teacher_menu() {
        $current_user = wp_get_current_user();

        // Customize menu for teachers
        if ( in_array( 'school_teacher', $current_user->roles ) ) {
            // Remove unnecessary menu items for teachers
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

            // Keep only: Profile, Calendar, and potentially School Management items
        }

        // Customize menu for accountants
        if ( in_array( 'school_accountant', $current_user->roles ) ) {
            // Remove WordPress default menus
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

            // Remove Academic Management menu (they should only see Financial Management)
            remove_menu_page( 'school-management' );          // Academic Management

            // Remove Settings menu (they don't have access)
            remove_menu_page( 'school-settings' );            // School Settings

            // Keep only: Profile, School Finances menu
        }
    }

    /**
     * Redirect teachers and accountants away from dashboard
     */
    public static function redirect_teachers_from_dashboard() {
        $current_user = wp_get_current_user();

        // Get current page
        global $pagenow;

        // Redirect school_teacher role
        if ( in_array( 'school_teacher', $current_user->roles ) ) {
            // If on dashboard (index.php) or trying to access dashboard
            if ( $pagenow === 'index.php' || ( empty( $_GET['page'] ) && $pagenow === 'admin.php' ) ) {
                // Check if calendar plugin is active
                if ( class_exists( 'SMC_Calendar_Page' ) ) {
                    // Redirect to calendar
                    wp_redirect( admin_url( 'admin.php?page=smc-calendar' ) );
                    exit;
                } else {
                    // Calendar not active, redirect to profile
                    wp_redirect( admin_url( 'profile.php' ) );
                    exit;
                }
            }
        }

        // Redirect school_accountant role
        if ( in_array( 'school_accountant', $current_user->roles ) ) {
            // If on dashboard (index.php) or trying to access dashboard
            if ( $pagenow === 'index.php' || ( empty( $_GET['page'] ) && $pagenow === 'admin.php' ) ) {
                // Redirect to Financial Dashboard
                wp_redirect( admin_url( 'admin.php?page=school-finances' ) );
                exit;
            }
        }
    }

    /**
     * Hide admin bar for teachers and accountants on frontend
     */
    public static function hide_admin_bar_for_teachers() {
        $current_user = wp_get_current_user();

        if ( in_array( 'school_teacher', $current_user->roles ) || in_array( 'school_accountant', $current_user->roles ) ) {
            // Hide admin bar on frontend
            show_admin_bar( false );
        }
    }

    /**
     * Add custom roles and capabilities
     */
    public static function add_roles() {
        // Create School Admin role with capabilities
        if ( ! get_role( 'school_admin' ) ) {
            add_role(
                'school_admin',
                __( 'School Admin', 'CTADZ-school-management' ),
                array(
                    // WordPress core capabilities
                    'read'                   => true,
                    'edit_posts'             => false,
                    'delete_posts'           => false,

                    // School Management capabilities
                    'manage_school'          => true,
                    'manage_students'        => true,
                    'manage_teachers'        => true,
                    'manage_courses'         => true,
                    'manage_levels'          => true,
                    'manage_enrollments'     => true,
                    'manage_attendance'      => true,
                    'view_attendance'        => true,
                    'manage_payments'        => true,
                    'manage_classrooms'      => true,
                    'view_reports'           => true,

                    // Calendar capabilities (for calendar add-on)
                    'view_calendar'          => true,
                    'manage_schedules'       => true,
                    'manage_events'          => true,



                    // Settings - EXCLUDED for School Admin
                    'manage_school_settings' => false,
                )
            );
        }

        // Create School Teacher role
        if ( ! get_role( 'school_teacher' ) ) {
            add_role(
                'school_teacher',
                __( 'School Teacher', 'CTADZ-school-management' ),
                array(
                    'read'                   => true,
                    'view_calendar'          => true,
                    'view_own_schedule'      => true,
                    'view_attendance'        => true,
                    'mark_attendance'        => true,
                )
            );
        }

        // Create School Student role
        if ( ! get_role( 'school_student' ) ) {
            add_role(
                'school_student',
                __( 'School Student', 'CTADZ-school-management' ),
                array(
                    'read'                   => true,
                    'view_calendar'          => true,
                    'view_own_schedule'      => true,
                    'view_own_grades'        => true,
                    'view_own_payments'      => true,
                )
            );
        }

        // Create School Accountant role (Financial Management only)
        if ( ! get_role( 'school_accountant' ) ) {
            add_role(
                'school_accountant',
                __( 'School Accountant', 'CTADZ-school-management' ),
                array(
                    // WordPress core capabilities
                    'read'                   => true,
                    'edit_posts'             => false,
                    'delete_posts'           => false,

                    // Financial Management capabilities (FULL ACCESS)
                    'manage_payments'        => true,
                    'manage_enrollments'     => true,
                    'view_reports'           => true,

                    // Academic Management capabilities (READ-ONLY)
                    'view_calendar'          => true,

                    // Explicitly DENIED capabilities
                    'manage_school'          => false,
                    'manage_students'        => false,
                    'manage_teachers'        => false,
                    'manage_courses'         => false,
                    'manage_levels'          => false,
                    'manage_classrooms'      => false,
                    'manage_attendance'      => false,
                    'view_attendance'        => false,
                    'manage_schedules'       => false,
                    'manage_events'          => false,
                    'manage_school_settings' => false,
                )
            );
        }
    }

    /**
     * Add capabilities to Administrator role
     */
    public static function add_caps_to_admin() {
        $admin_role = get_role( 'administrator' );
        
        if ( $admin_role ) {
            $admin_role->add_cap( 'manage_school' );
            $admin_role->add_cap( 'manage_students' );
            $admin_role->add_cap( 'manage_teachers' );
            $admin_role->add_cap( 'manage_courses' );
            $admin_role->add_cap( 'manage_levels' );
            $admin_role->add_cap( 'manage_enrollments' );
            $admin_role->add_cap( 'manage_attendance' );
            $admin_role->add_cap( 'view_attendance' );
            $admin_role->add_cap( 'manage_payments' );
            $admin_role->add_cap( 'manage_classrooms' );
            $admin_role->add_cap( 'view_reports' );
            $admin_role->add_cap( 'view_calendar' );
            $admin_role->add_cap( 'manage_schedules' );
            $admin_role->add_cap( 'manage_events' );
            $admin_role->add_cap( 'manage_school_settings' );
        }
    }

    /**
     * Remove custom roles (for plugin deactivation)
     */
    public static function remove_roles() {
        remove_role( 'school_admin' );
        remove_role( 'school_teacher' );
        remove_role( 'school_student' );
        remove_role( 'school_accountant' );
    }

    /**
     * Remove capabilities from Administrator role
     */
    public static function remove_caps_from_admin() {
        $admin_role = get_role( 'administrator' );
        
        if ( $admin_role ) {
            $admin_role->remove_cap( 'manage_school' );
            $admin_role->remove_cap( 'manage_students' );
            $admin_role->remove_cap( 'manage_teachers' );
            $admin_role->remove_cap( 'manage_courses' );
            $admin_role->remove_cap( 'manage_levels' );
            $admin_role->remove_cap( 'manage_enrollments' );
            $admin_role->remove_cap( 'manage_attendance' );
            $admin_role->remove_cap( 'view_attendance' );
            $admin_role->remove_cap( 'manage_payments' );
            $admin_role->remove_cap( 'manage_classrooms' );
            $admin_role->remove_cap( 'view_reports' );
            $admin_role->remove_cap( 'view_calendar' );
            $admin_role->remove_cap( 'manage_schedules' );
            $admin_role->remove_cap( 'manage_events' );
            $admin_role->remove_cap( 'manage_school_settings' );
        }
    }
}