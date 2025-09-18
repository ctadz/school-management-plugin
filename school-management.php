<?php
/*
Plugin Name: School Management
Plugin URI:  https://github.com/ahmedsebaa/school-management-plugin
Description: A WordPress plugin to manage students, courses, schedules, attendance, and payments for a private school.
Version:     0.1.0
Author:      Ahmed Sebaa
Author URI:  https://github.com/ahmedsebaa
License:     GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: school-management
Domain Path: /languages
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'SM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the loader file.
require_once SM_PLUGIN_DIR . 'includes/sm-loader.php';

/**
 * Plugin activation hook
 */
register_activation_hook( __FILE__, 'sm_activate_plugin' );
function sm_activate_plugin() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Students table
    $students_table = $wpdb->prefix . 'sm_students';

    $sql = "CREATE TABLE $students_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        level varchar(50) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    error_log('✅ School Management plugin activated and students table created.');
}
