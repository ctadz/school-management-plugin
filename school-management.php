<?php
/*
Plugin Name: School Management
Plugin URI:  https://github.com/ahmedsebaa/school-management-plugin
Description: A WordPress plugin to manage students, courses, schedules, attendance, and payments for a private school.
Version:     0.2.2
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
define( 'SM_VERSION', '0.2.2' );

// Include the loader file.
require_once SM_PLUGIN_DIR . 'includes/sm-loader.php';

/**
 * Plugin activation hook
 */
register_activation_hook( __FILE__, 'sm_activate_plugin' );
function sm_activate_plugin() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table names
    $students_table = $wpdb->prefix . 'sm_students';
    $levels_table = $wpdb->prefix . 'sm_levels';
    $payment_terms_table = $wpdb->prefix . 'sm_payment_terms';
    $teachers_table = $wpdb->prefix . 'sm_teachers';
    $courses_table = $wpdb->prefix . 'sm_courses';

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // --- Create Levels Table FIRST (since students reference it) ---
    $sql_levels = "CREATE TABLE $levels_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        description text,
        sort_order int DEFAULT 0,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_level_name (name)
    ) $charset_collate;";

    dbDelta( $sql_levels );

    // Insert default levels if table is empty
    $level_count = $wpdb->get_var( "SELECT COUNT(*) FROM $levels_table" );
    if ( $level_count == 0 ) {
        $default_levels = [
            ['name' => 'Beginner', 'description' => 'Entry level for new students', 'sort_order' => 1],
            ['name' => 'Intermediate', 'description' => 'For students with basic knowledge', 'sort_order' => 2],
            ['name' => 'Advanced', 'description' => 'For experienced students', 'sort_order' => 3],
            ['name' => 'Expert', 'description' => 'For mastery level students', 'sort_order' => 4],
        ];

        foreach ( $default_levels as $level ) {
            $wpdb->insert( $levels_table, $level );
        }
    }

    // --- Create Students Table ---
    $sql_students = "CREATE TABLE $students_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(50) NOT NULL,
        dob date NOT NULL,
        level_id mediumint(9) NOT NULL,
        picture varchar(255) DEFAULT NULL,
        blood_type varchar(5) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_email (email),
        KEY level_id (level_id)
    ) $charset_collate;";

    dbDelta( $sql_students );

    // Check if we need to migrate from old 'level' text column to 'level_id'
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $students_table", ARRAY_A);
    $existing_columns = array_column($columns, 'Field');

    // If old 'level' column exists (text), migrate data to level_id
    if ( in_array('level', $existing_columns) && !in_array('level_id', $existing_columns) ) {
        // Add level_id column
        $wpdb->query("ALTER TABLE $students_table ADD level_id mediumint(9) DEFAULT NULL AFTER dob");
        
        // Migrate existing data
        $students = $wpdb->get_results("SELECT id, level FROM $students_table WHERE level IS NOT NULL");
        foreach ($students as $student) {
            $level_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $levels_table WHERE LOWER(name) = LOWER(%s) LIMIT 1",
                $student->level
            ));
            if ($level_id) {
                $wpdb->update($students_table, ['level_id' => $level_id], ['id' => $student->id]);
            }
        }
        
        // Drop old level column
        $wpdb->query("ALTER TABLE $students_table DROP COLUMN level");
        
        // Make level_id NOT NULL after migration
        $wpdb->query("ALTER TABLE $students_table MODIFY level_id mediumint(9) NOT NULL");
    }

    // Add missing columns if needed
    if ( ! in_array('phone', $existing_columns) ) {
        $wpdb->query("ALTER TABLE $students_table ADD phone varchar(50) NOT NULL");
    }
    if ( ! in_array('dob', $existing_columns) ) {
        $wpdb->query("ALTER TABLE $students_table ADD dob date NOT NULL");
    }
    if ( ! in_array('picture', $existing_columns) ) {
        $wpdb->query("ALTER TABLE $students_table ADD picture varchar(255) DEFAULT NULL");
    }
    if ( ! in_array('blood_type', $existing_columns) ) {
        $wpdb->query("ALTER TABLE $students_table ADD blood_type varchar(5) DEFAULT NULL");
    }

    // --- Create Payment Terms Table ---
    $sql_payment_terms = "CREATE TABLE $payment_terms_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        description text,
        percentage decimal(5,2) DEFAULT 100.00,
        sort_order int DEFAULT 0,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_term_name (name)
    ) $charset_collate;";

    dbDelta( $sql_payment_terms );

    // Insert default payment terms if table is empty
    $terms_count = $wpdb->get_var( "SELECT COUNT(*) FROM $payment_terms_table" );
    if ( $terms_count == 0 ) {
        $default_terms = [
            ['name' => 'Monthly', 'description' => 'Paid monthly', 'percentage' => 100.00, 'sort_order' => 1],
            ['name' => 'Weekly', 'description' => 'Paid weekly', 'percentage' => 100.00, 'sort_order' => 2],
            ['name' => '50/50', 'description' => '50% upfront, 50% later', 'percentage' => 50.00, 'sort_order' => 3],
            ['name' => '1/3 Payment', 'description' => 'Paid in three installments', 'percentage' => 33.33, 'sort_order' => 4],
            ['name' => '2/3 Payment', 'description' => 'Two-thirds payment plan', 'percentage' => 66.67, 'sort_order' => 5],
        ];

        foreach ( $default_terms as $term ) {
            $wpdb->insert( $payment_terms_table, $term );
        }
    }

    // --- Create Teachers Table ---
    $sql_teachers = "CREATE TABLE $teachers_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(50) NOT NULL,
        picture varchar(255) DEFAULT NULL,
        payment_term_id mediumint(9) NOT NULL,
        hourly_rate decimal(10,2) DEFAULT 0,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_email (email),
        KEY payment_term_id (payment_term_id)
    ) $charset_collate;";

    dbDelta( $sql_teachers );

    // --- Create Courses Table ---
    $sql_courses = "CREATE TABLE $courses_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(200) NOT NULL,
        description text NOT NULL,
        description_file varchar(255) DEFAULT NULL,
        language varchar(50) NOT NULL,
        level_id mediumint(9) NOT NULL,
        teacher_id mediumint(9) NOT NULL,
        session_duration_hours int NOT NULL DEFAULT 0,
        session_duration_minutes int NOT NULL DEFAULT 0,
        hours_per_week decimal(5,2) NOT NULL,
        total_weeks int NOT NULL,
        total_months int NOT NULL,
        price_per_month decimal(10,2) NOT NULL,
        total_price decimal(10,2) NOT NULL,
        certification_type varchar(50) DEFAULT NULL,
        certification_other varchar(255) DEFAULT NULL,
        status varchar(20) DEFAULT 'upcoming',
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_course_name (name),
        KEY level_id (level_id),
        KEY teacher_id (teacher_id),
        KEY language (language),
        KEY status (status),
        KEY is_active (is_active)
    ) $charset_collate;";

    dbDelta( $sql_courses );

    // Add certification columns if they don't exist
    $course_columns = $wpdb->get_results("SHOW COLUMNS FROM $courses_table", ARRAY_A);
    $existing_course_columns = array_column($course_columns, 'Field');
    
    if ( ! in_array('certification_type', $existing_course_columns) ) {
        $wpdb->query("ALTER TABLE $courses_table ADD certification_type varchar(50) DEFAULT NULL AFTER total_price");
    }
    if ( ! in_array('certification_other', $existing_course_columns) ) {
        $wpdb->query("ALTER TABLE $courses_table ADD certification_other varchar(255) DEFAULT NULL AFTER certification_type");
    }

    error_log('✅ School Management plugin activated. All tables created or updated successfully.');
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook( __FILE__, 'sm_deactivate_plugin' );
function sm_deactivate_plugin() {
    // Keep data, just log deactivation
    error_log('ℹ️ School Management plugin deactivated. Data preserved.');
}