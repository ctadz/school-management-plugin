<?php
/*
Plugin Name: School Management
Plugin URI:  https://github.com/ahmedsebaa/school-management-plugin
Description: A WordPress plugin to manage students, courses, schedules, attendance, and payments for a private school.
Version:     0.5.3
Author:      Ahmed Sebaa
Author URI:  https://github.com/ahmedsebaa
License:     GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: CTADZ-school-management
Domain Path: /languages
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'SM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SM_VERSION', '0.5.3' );
define( 'SM_DB_VERSION', '1.4.0' );

/**
 * Load plugin text domain for translations
 */
function sm_load_textdomain() {
    load_plugin_textdomain(
        'CTADZ-school-management',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'sm_load_textdomain' );

// Include the loader file.
require_once SM_PLUGIN_DIR . 'includes/sm-loader.php';

// Include roles management
require_once SM_PLUGIN_DIR . 'includes/class-sm-roles.php';

// Include GitHub updater for automatic plugin updates
require_once SM_PLUGIN_DIR . 'includes/class-sm-github-updater.php';

// Include family discount calculator
require_once SM_PLUGIN_DIR . 'includes/class-sm-family-discount.php';

// Include family discount tools page (admin only)
require_once SM_PLUGIN_DIR . 'includes/class-sm-family-discount-tools-page.php';

/**
 * Initialize automatic updates from GitHub
 */
function sm_init_github_updater() {
	if ( is_admin() ) {
		new SM_GitHub_Updater(
			__FILE__,
			'ahmedsebaa/school-management-plugin', // GitHub repository
			null // GitHub token (optional, set in wp-config.php: define('SM_GITHUB_TOKEN', 'your_token'))
		);
	}
}
add_action( 'admin_init', 'sm_init_github_updater' );

/**
 * Plugin activation hook
 */
register_activation_hook( __FILE__, 'sm_activate_plugin' );
function sm_activate_plugin() {
    // Start output buffering to prevent any output during activation
    ob_start();

    // Initialize roles and capabilities first
    SM_Roles::add_roles();
    SM_Roles::add_caps_to_admin();

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table names
    $students_table = $wpdb->prefix . 'sm_students';
    $levels_table = $wpdb->prefix . 'sm_levels';
    $payment_terms_table = $wpdb->prefix . 'sm_payment_terms';
    $teachers_table = $wpdb->prefix . 'sm_teachers';
    $courses_table = $wpdb->prefix . 'sm_courses';
    $enrollments_table = $wpdb->prefix . 'sm_enrollments';
    $classrooms_table = $wpdb->prefix . 'sm_classrooms';

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // --- Create Levels Table FIRST ---
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

    if ( in_array('level', $existing_columns) && !in_array('level_id', $existing_columns) ) {
        $wpdb->query("ALTER TABLE $students_table ADD level_id mediumint(9) DEFAULT NULL AFTER dob");
        
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
        
        $wpdb->query("ALTER TABLE $students_table DROP COLUMN level");
        $wpdb->query("ALTER TABLE $students_table MODIFY level_id mediumint(9) NOT NULL");
    }

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
        max_students int DEFAULT NULL,
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

        // Add missing columns to courses table
        $course_columns = $wpdb->get_results("SHOW COLUMNS FROM $courses_table", ARRAY_A);
        $existing_course_columns = array_column($course_columns, 'Field');
        
        if ( ! in_array('max_students', $existing_course_columns) ) {
            $wpdb->query("ALTER TABLE $courses_table ADD max_students int DEFAULT NULL AFTER total_price");
        }
        if ( ! in_array('certification_type', $existing_course_columns) ) {
            $wpdb->query("ALTER TABLE $courses_table ADD certification_type varchar(50) DEFAULT NULL AFTER max_students");
        }
        if ( ! in_array('certification_other', $existing_course_columns) ) {
            $wpdb->query("ALTER TABLE $courses_table ADD certification_other varchar(255) DEFAULT NULL AFTER certification_type");
        }
        // ========================================
        // PAYMENT MODEL COLUMN MIGRATION (v0.4.1)
        // ========================================
        // Fix: Rename payment_models (plural) to payment_model (singular)
        // This ensures consistency with the payment model connection system
        
        // Check if we have the wrong column name (payment_models - plural)
        if ( in_array('payment_models', $existing_course_columns) ) {
            // Rename payment_models to payment_model (preserve existing data)
            $wpdb->query("ALTER TABLE $courses_table CHANGE payment_models payment_model VARCHAR(50) DEFAULT 'monthly_installments'");
            error_log('✅ SM Migration: Renamed payment_models to payment_model in courses table');
        }
        // If neither exists, add the correct column
        elseif ( ! in_array('payment_model', $existing_course_columns) ) {
            // Add payment_model column for flexible payment options
            $wpdb->query("ALTER TABLE $courses_table ADD payment_model VARCHAR(50) DEFAULT 'monthly_installments' AFTER total_price");
            error_log('✅ SM Migration: Added payment_model column to courses table');
        }
        
        // Ensure all existing courses have a payment_model value
        $wpdb->query("UPDATE $courses_table SET payment_model = 'monthly_installments' WHERE payment_model IS NULL OR payment_model = ''");

    // --- Create Classrooms Table ---
    $sql_classrooms = "CREATE TABLE $classrooms_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        capacity int DEFAULT 0,
        location varchar(255) DEFAULT NULL,
        facilities text,
        is_active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_classroom_name (name)
    ) $charset_collate;";

dbDelta( $sql_classrooms );

        // Add classroom_id to courses table if not exists
        $course_columns = $wpdb->get_results("SHOW COLUMNS FROM $courses_table", ARRAY_A);
        $existing_course_columns = array_column($course_columns, 'Field');

        if ( ! in_array('classroom_id', $existing_course_columns) ) {
            $wpdb->query("ALTER TABLE $courses_table ADD classroom_id mediumint(9) DEFAULT NULL AFTER teacher_id");
            $wpdb->query("ALTER TABLE $courses_table ADD KEY classroom_id (classroom_id)");
        }


    // --- Create Enrollments Table ---
    $sql_enrollments = "CREATE TABLE $enrollments_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        student_id mediumint(9) NOT NULL,
        course_id mediumint(9) NOT NULL,
        enrollment_date date NOT NULL,
        start_date date NOT NULL,
        end_date date DEFAULT NULL,
        status varchar(20) DEFAULT 'active',
        payment_status varchar(20) DEFAULT 'unpaid',
        notes text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_enrollment (student_id, course_id),
        KEY student_id (student_id),
        KEY course_id (course_id),
        KEY status (status),
        KEY payment_status (payment_status)
    ) $charset_collate;";

dbDelta( $sql_enrollments );

    // Add payment_plan column to enrollments if not exists
    $enrollment_columns = $wpdb->get_results("SHOW COLUMNS FROM $enrollments_table", ARRAY_A);
    $existing_enrollment_columns = array_column($enrollment_columns, 'Field');
    
    if ( ! in_array('payment_plan', $existing_enrollment_columns) ) {
        $wpdb->query("ALTER TABLE $enrollments_table ADD payment_plan varchar(20) DEFAULT 'monthly' AFTER payment_status");
    }

    // --- Create Enrollment Fees Table ---
    $enrollment_fees_table = $wpdb->prefix . 'sm_enrollment_fees';
    $sql_enrollment_fees = "CREATE TABLE $enrollment_fees_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        enrollment_id mediumint(9) NOT NULL,
        fee_type varchar(20) NOT NULL,
        amount decimal(10,2) NOT NULL,
        status varchar(20) DEFAULT 'unpaid',
        due_date date NOT NULL,
        paid_date date DEFAULT NULL,
        notes text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY enrollment_id (enrollment_id),
        KEY fee_type (fee_type),
        KEY status (status)
    ) $charset_collate;";

    dbDelta( $sql_enrollment_fees );

    // --- Create Payment Schedules Table ---
    $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';
    $sql_payment_schedules = "CREATE TABLE $payment_schedules_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        enrollment_id mediumint(9) NOT NULL,
        installment_number int NOT NULL,
        expected_amount decimal(10,2) NOT NULL,
        due_date date NOT NULL,
        status varchar(20) DEFAULT 'pending',
        paid_amount decimal(10,2) DEFAULT 0,
        paid_date date DEFAULT NULL,
        notes text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY enrollment_id (enrollment_id),
        KEY due_date (due_date),
        KEY status (status)
    ) $charset_collate;";

    dbDelta( $sql_payment_schedules );

    // --- Create Payments Table ---
    $payments_table = $wpdb->prefix . 'sm_payments';
    $sql_payments = "CREATE TABLE $payments_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        enrollment_id mediumint(9) NOT NULL,
        payment_type varchar(20) NOT NULL,
        reference_id mediumint(9) DEFAULT NULL,
        amount decimal(10,2) NOT NULL,
        payment_date date NOT NULL,
        payment_method varchar(50) DEFAULT NULL,
        reference_number varchar(100) DEFAULT NULL,
        notes text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY enrollment_id (enrollment_id),
        KEY payment_type (payment_type),
        KEY reference_id (reference_id),
        KEY payment_date (payment_date)
    ) $charset_collate;";

    dbDelta( $sql_payments );

    // --- Create Attendance Table ---
    $attendance_table = $wpdb->prefix . 'sm_attendance';
    $sql_attendance = "CREATE TABLE $attendance_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        student_id mediumint(9) NOT NULL,
        course_id mediumint(9) NOT NULL,
        schedule_id mediumint(9) DEFAULT NULL,
        date date NOT NULL,
        status varchar(20) NOT NULL,
        notes text,
        marked_by bigint(20) UNSIGNED DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY student_id (student_id),
        KEY course_id (course_id),
        KEY schedule_id (schedule_id),
        KEY date (date),
        KEY status (status),
        KEY marked_by (marked_by)
    ) $charset_collate;";

    dbDelta( $sql_attendance );

    // --- Database Version Check & Migrations ---
    $current_db_version = get_option( 'sm_db_version', '1.0.0' );

    if ( version_compare( $current_db_version, '1.2.0', '<' ) ) {
        sm_update_to_1_2_0( $wpdb, $teachers_table, $students_table );
        update_option( 'sm_db_version', '1.2.0' );
        error_log('✅ SM Database updated to version 1.2.0');
    }

    if ( version_compare( $current_db_version, '1.3.0', '<' ) ) {
        sm_update_to_1_3_0( $wpdb, $students_table );
        update_option( 'sm_db_version', '1.3.0' );
        error_log('✅ SM Database updated to version 1.3.0');
    }

    if ( version_compare( $current_db_version, '1.4.0', '<' ) ) {
        update_option( 'sm_db_version', '1.4.0' );
        error_log('✅ SM Database updated to version 1.4.0 - Attendance table created');
    }

    if ( version_compare( $current_db_version, '1.5.0', '<' ) ) {
        sm_update_to_1_5_0( $wpdb, $students_table );
        update_option( 'sm_db_version', '1.5.0' );
        error_log('✅ SM Database updated to version 1.5.0 - Parent contact fields added');
    }

    if ( version_compare( $current_db_version, '1.6.0', '<' ) ) {
        sm_update_to_1_6_0( $wpdb, $payment_schedules_table );
        update_option( 'sm_db_version', '1.6.0' );
        error_log('✅ SM Database updated to version 1.6.0 - Family discount fields added');
    }

    error_log('✅ School Management plugin activated. All tables created or updated successfully.');

    // Clear output buffer to prevent "headers already sent" errors
    ob_end_clean();
}

/**
 * Database migration to version 1.2.0
 * Adds user_id field to teachers and students tables
 */
function sm_update_to_1_2_0( $wpdb, $teachers_table, $students_table ) {
    // Check and add user_id to teachers table
    $teachers_columns = $wpdb->get_results( "SHOW COLUMNS FROM $teachers_table", ARRAY_A );
    $existing_teachers_columns = array_column( $teachers_columns, 'Field' );

    if ( ! in_array( 'user_id', $existing_teachers_columns ) ) {
        $wpdb->query( "ALTER TABLE $teachers_table ADD user_id bigint(20) UNSIGNED DEFAULT NULL AFTER id" );
        $wpdb->query( "ALTER TABLE $teachers_table ADD UNIQUE KEY unique_user_id (user_id)" );
        error_log('✅ Added user_id field to teachers table');
    }

    // Check and add user_id to students table
    $students_columns = $wpdb->get_results( "SHOW COLUMNS FROM $students_table", ARRAY_A );
    $existing_students_columns = array_column( $students_columns, 'Field' );

    if ( ! in_array( 'user_id', $existing_students_columns ) ) {
        $wpdb->query( "ALTER TABLE $students_table ADD user_id bigint(20) UNSIGNED DEFAULT NULL AFTER id" );
        $wpdb->query( "ALTER TABLE $students_table ADD UNIQUE KEY unique_user_id (user_id)" );
        error_log('✅ Added user_id field to students table');
    }
}

/**
 * Database migration to version 1.3.0
 * Adds student_code field to students table and generates codes for existing students
 */
function sm_update_to_1_3_0( $wpdb, $students_table ) {
    // Check and add student_code to students table
    $students_columns = $wpdb->get_results( "SHOW COLUMNS FROM $students_table", ARRAY_A );
    $existing_students_columns = array_column( $students_columns, 'Field' );

    if ( ! in_array( 'student_code', $existing_students_columns ) ) {
        $wpdb->query( "ALTER TABLE $students_table ADD student_code varchar(20) UNIQUE DEFAULT NULL AFTER id" );
        error_log('✅ Added student_code field to students table');

        // Generate student codes for existing students
        $students = $wpdb->get_results( "SELECT id FROM $students_table ORDER BY id ASC" );
        $year = date('Y');
        $counter = 1;

        foreach ( $students as $student ) {
            $student_code = sprintf( 'STU%s%04d', $year, $counter );

            // Ensure uniqueness
            while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $students_table WHERE student_code = %s", $student_code ) ) ) {
                $counter++;
                $student_code = sprintf( 'STU%s%04d', $year, $counter );
            }

            $wpdb->update(
                $students_table,
                array( 'student_code' => $student_code ),
                array( 'id' => $student->id )
            );

            $counter++;
        }

        error_log('✅ Generated student codes for ' . count($students) . ' existing students');
    }
}

/**
 * Database migration to version 1.5.0
 * Adds parent contact fields to students table for family discount feature
 */
function sm_update_to_1_5_0( $wpdb, $students_table ) {
    $students_columns = $wpdb->get_results( "SHOW COLUMNS FROM $students_table", ARRAY_A );
    $existing_students_columns = array_column( $students_columns, 'Field' );

    // Add parent_name field
    if ( ! in_array( 'parent_name', $existing_students_columns ) ) {
        $wpdb->query( "ALTER TABLE $students_table ADD parent_name varchar(100) DEFAULT NULL AFTER blood_type" );
        error_log('✅ Added parent_name field to students table');
    }

    // Add parent_phone field (NO UNIQUE constraint - allows siblings)
    if ( ! in_array( 'parent_phone', $existing_students_columns ) ) {
        $wpdb->query( "ALTER TABLE $students_table ADD parent_phone varchar(50) DEFAULT NULL AFTER parent_name" );
        // Add index for faster family lookups
        $wpdb->query( "ALTER TABLE $students_table ADD INDEX idx_parent_phone (parent_phone)" );
        error_log('✅ Added parent_phone field with index to students table');
    }

    // Add parent_email field
    if ( ! in_array( 'parent_email', $existing_students_columns ) ) {
        $wpdb->query( "ALTER TABLE $students_table ADD parent_email varchar(100) DEFAULT NULL AFTER parent_phone" );
        error_log('✅ Added parent_email field to students table');
    }
}

/**
 * Database migration to version 1.6.0
 * Adds discount fields to payment_schedules table for family discount tracking
 */
function sm_update_to_1_6_0( $wpdb, $payment_schedules_table ) {
    $schedule_columns = $wpdb->get_results( "SHOW COLUMNS FROM $payment_schedules_table", ARRAY_A );
    $existing_schedule_columns = array_column( $schedule_columns, 'Field' );

    // Add discount_percentage field
    if ( ! in_array( 'discount_percentage', $existing_schedule_columns ) ) {
        $wpdb->query( "ALTER TABLE $payment_schedules_table ADD discount_percentage decimal(5,2) DEFAULT 0.00 AFTER expected_amount" );
        error_log('✅ Added discount_percentage field to payment_schedules table');
    }

    // Add discount_reason field
    if ( ! in_array( 'discount_reason', $existing_schedule_columns ) ) {
        $wpdb->query( "ALTER TABLE $payment_schedules_table ADD discount_reason varchar(255) DEFAULT NULL AFTER discount_percentage" );
        error_log('✅ Added discount_reason field to payment_schedules table');
    }
}


/**
 * Plugin deactivation hook
 */
register_deactivation_hook( __FILE__, 'sm_deactivate_plugin' );
function sm_deactivate_plugin() {
    // Remove custom roles and capabilities
    SM_Roles::remove_roles();
    SM_Roles::remove_caps_from_admin();

    error_log('ℹ️ School Management plugin deactivated. Data preserved.');
}

// Initialize roles on every page load
SM_Roles::init();
