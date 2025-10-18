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
    }

    /**
     * Add custom roles and capabilities
     */
    public static function add_roles() {
        // Check if role already exists
        if ( get_role( 'school_admin' ) ) {
            return;
        }

        // Create School Admin role with capabilities
        add_role(
            'school_admin',
            __( 'School Admin', 'school-management' ),
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