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
    $table_name = $wpdb->prefix . 'sm_students';

    // --- Create table if it doesn't exist ---
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(50) NOT NULL,
        dob date NOT NULL,
        level varchar(50) NOT NULL,
        picture varchar(255) DEFAULT NULL,
        blood_type varchar(5) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // --- Add missing columns if table already existed ---
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_A);
    $existing_columns = array_column($columns, 'Field');

    $new_columns = [
        'phone'      => "ALTER TABLE $table_name ADD phone varchar(50) NOT NULL",
        'dob'        => "ALTER TABLE $table_name ADD dob date NOT NULL",
        'level'      => "ALTER TABLE $table_name ADD level varchar(50) NOT NULL",
        'picture'    => "ALTER TABLE $table_name ADD picture varchar(255) DEFAULT NULL",
        'blood_type' => "ALTER TABLE $table_name ADD blood_type varchar(5) DEFAULT NULL",
    ];

    foreach ( $new_columns as $col => $sql_col ) {
        if ( ! in_array($col, $existing_columns) ) {
            $wpdb->query($sql_col);
        }
    }

    error_log('✅ School Management plugin activated. Students table created or updated safely.');
}
