<?php
/**
 * User Management Class
 * Handles WordPress user account creation for teachers and students
 *
 * @package SchoolManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_User_Management {

    /**
     * Create WordPress account for a teacher
     *
     * @param int $teacher_id The teacher ID
     * @return array Success status, user_id, username, password, and message
     */
    public static function create_teacher_account( $teacher_id ) {
        global $wpdb;
        $teachers_table = $wpdb->prefix . 'sm_teachers';

        // Get teacher data
        $teacher = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $teachers_table WHERE id = %d",
            $teacher_id
        ) );

        if ( ! $teacher ) {
            return array(
                'success' => false,
                'message' => __( 'Teacher not found', 'CTADZ-school-management' )
            );
        }

        // Check if user_id already exists
        if ( $teacher->user_id ) {
            return array(
                'success' => false,
                'message' => __( 'Teacher already has a WordPress account', 'CTADZ-school-management' )
            );
        }

        // Generate username from email
        $username = sanitize_user( $teacher->email );

        // Check if email already exists in WordPress
        if ( email_exists( $teacher->email ) ) {
            return array(
                'success' => false,
                'teacher_name' => $teacher->first_name . ' ' . $teacher->last_name,
                'message' => sprintf(
                    __( 'Email %s is already registered in WordPress', 'CTADZ-school-management' ),
                    $teacher->email
                )
            );
        }

        // Check if username already exists
        if ( username_exists( $username ) ) {
            return array(
                'success' => false,
                'teacher_name' => $teacher->first_name . ' ' . $teacher->last_name,
                'message' => sprintf(
                    __( 'Username %s already exists', 'CTADZ-school-management' ),
                    $username
                )
            );
        }

        // Generate secure password
        $password = wp_generate_password( 12, true, true );

        // Create WordPress user
        $user_id = wp_create_user( $username, $password, $teacher->email );

        if ( is_wp_error( $user_id ) ) {
            return array(
                'success' => false,
                'message' => $user_id->get_error_message()
            );
        }

        // Set user role
        $user = new WP_User( $user_id );
        $user->set_role( 'school_teacher' );

        // Update user meta with teacher info
        update_user_meta( $user_id, 'first_name', $teacher->first_name );
        update_user_meta( $user_id, 'last_name', $teacher->last_name );
        update_user_meta( $user_id, 'sm_teacher_id', $teacher_id );

        // Update teacher record with user_id
        $wpdb->update(
            $teachers_table,
            array( 'user_id' => $user_id ),
            array( 'id' => $teacher_id ),
            array( '%d' ),
            array( '%d' )
        );

        return array(
            'success'  => true,
            'user_id'  => $user_id,
            'username' => $username,
            'password' => $password,
            'email'    => $teacher->email,
            'name'     => $teacher->first_name . ' ' . $teacher->last_name,
            'message'  => __( 'Teacher account created successfully', 'CTADZ-school-management' )
        );
    }

    /**
     * Create WordPress account for a student
     *
     * @param int $student_id The student ID
     * @return array Success status, user_id, username, password, and message
     */
    public static function create_student_account( $student_id ) {
        global $wpdb;
        $students_table = $wpdb->prefix . 'sm_students';

        // Get student data
        $student = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $students_table WHERE id = %d",
            $student_id
        ) );

        if ( ! $student ) {
            return array(
                'success' => false,
                'message' => __( 'Student not found', 'CTADZ-school-management' )
            );
        }

        // Check if user_id already exists
        if ( $student->user_id ) {
            return array(
                'success' => false,
                'message' => __( 'Student already has a WordPress account', 'CTADZ-school-management' )
            );
        }

        // Generate username from name (first.last format)
        $username = self::generate_student_username( $student->name );

        // Generate secure password
        $password = wp_generate_password( 12, true, true );

        // Create WordPress user
        $user_id = wp_create_user( $username, $password, $student->email );

        if ( is_wp_error( $user_id ) ) {
            return array(
                'success' => false,
                'message' => $user_id->get_error_message()
            );
        }

        // Set user role
        $user = new WP_User( $user_id );
        $user->set_role( 'school_student' );

        // Update user meta with student info
        update_user_meta( $user_id, 'nickname', $student->name );
        update_user_meta( $user_id, 'display_name', $student->name );
        update_user_meta( $user_id, 'sm_student_id', $student_id );

        // Update student record with user_id
        $wpdb->update(
            $students_table,
            array( 'user_id' => $user_id ),
            array( 'id' => $student_id ),
            array( '%d' ),
            array( '%d' )
        );

        return array(
            'success'  => true,
            'user_id'  => $user_id,
            'username' => $username,
            'password' => $password,
            'email'    => $student->email,
            'name'     => $student->name,
            'message'  => __( 'Student account created successfully', 'CTADZ-school-management' )
        );
    }

    /**
     * Generate username from student name (first.last format)
     *
     * @param string $name Student full name
     * @return string Generated username
     */
    private static function generate_student_username( $name ) {
        // Split name into parts
        $parts = explode( ' ', trim( $name ) );

        if ( count( $parts ) >= 2 ) {
            // Use first and last name
            $username = strtolower( $parts[0] . '.' . end( $parts ) );
        } else {
            // Use single name with random number
            $username = strtolower( $parts[0] ) . rand( 100, 999 );
        }

        // Sanitize and transliterate
        $username = remove_accents( $username );
        $username = sanitize_user( $username, true );

        // Check if username exists, add number if needed
        $original_username = $username;
        $counter = 1;
        while ( username_exists( $username ) ) {
            $username = $original_username . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Bulk create accounts for all teachers without accounts
     *
     * @return array Results array with created accounts and errors
     */
    public static function bulk_create_teacher_accounts() {
        global $wpdb;
        $teachers_table = $wpdb->prefix . 'sm_teachers';

        // Get all teachers without user accounts
        $teachers = $wpdb->get_results(
            "SELECT id FROM $teachers_table WHERE user_id IS NULL AND is_active = 1"
        );

        $results = array(
            'success' => array(),
            'errors' => array()
        );

        foreach ( $teachers as $teacher ) {
            $result = self::create_teacher_account( $teacher->id );
            if ( $result['success'] ) {
                $results['success'][] = $result;
            } else {
                $results['errors'][] = $result;
            }
        }

        return $results;
    }

    /**
     * Bulk create accounts for all students without accounts
     *
     * @return array Results array with created accounts
     */
    public static function bulk_create_student_accounts() {
        global $wpdb;
        $students_table = $wpdb->prefix . 'sm_students';

        // Get all students without user accounts
        $students = $wpdb->get_results(
            "SELECT id FROM $students_table WHERE user_id IS NULL"
        );

        $results = array();
        foreach ( $students as $student ) {
            $result = self::create_student_account( $student->id );
            if ( $result['success'] ) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Sanitize CSV cell to prevent formula injection
     *
     * Prevents CSV injection by prefixing cells that start with dangerous
     * characters (=, +, -, @, tab, carriage return) with a single quote.
     *
     * @param string $value The cell value to sanitize
     * @return string Sanitized value safe for CSV export
     */
    private static function sanitize_csv_cell( $value ) {
        // Convert to string and trim
        $value = (string) $value;
        $value = trim( $value );

        // Check if value starts with dangerous characters
        if ( ! empty( $value ) ) {
            $first_char = substr( $value, 0, 1 );
            $dangerous_chars = array( '=', '+', '-', '@', "\t", "\r" );

            if ( in_array( $first_char, $dangerous_chars, true ) ) {
                // Prefix with single quote to prevent formula execution
                $value = "'" . $value;
            }
        }

        return $value;
    }

    /**
     * Export credentials to CSV file
     *
     * @param array $accounts Array of account data
     * @param string $type 'teacher' or 'student'
     * @return string File path of generated CSV
     */
    public static function export_credentials_to_csv( $accounts, $type = 'teacher' ) {
        // Create uploads directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $csv_dir = $upload_dir['basedir'] . '/school-credentials/';

        if ( ! file_exists( $csv_dir ) ) {
            wp_mkdir_p( $csv_dir );
        }

        // Generate filename
        $filename = $type . '-credentials-' . date( 'Y-m-d-His' ) . '.csv';
        $filepath = $csv_dir . $filename;

        // Open file for writing
        $file = fopen( $filepath, 'w' );

        // Write CSV header
        fputcsv( $file, array( 'Name', 'Username', 'Password', 'Email', 'Role' ), ',', '"', '\\' );

        // Write account data with CSV injection prevention
        foreach ( $accounts as $account ) {
            fputcsv( $file, array(
                self::sanitize_csv_cell( $account['name'] ),
                self::sanitize_csv_cell( $account['username'] ),
                self::sanitize_csv_cell( $account['password'] ),
                self::sanitize_csv_cell( $account['email'] ),
                self::sanitize_csv_cell( ucfirst( $type ) )
            ), ',', '"', '\\' );
        }

        fclose( $file );

        return $filepath;
    }

    /**
     * Get teacher ID from WordPress user ID
     *
     * @param int $user_id WordPress user ID
     * @return int|false Teacher ID or false
     */
    public static function get_teacher_id_by_user_id( $user_id ) {
        global $wpdb;
        $teachers_table = $wpdb->prefix . 'sm_teachers';

        $teacher_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $teachers_table WHERE user_id = %d",
            $user_id
        ) );

        return $teacher_id ? (int) $teacher_id : false;
    }

    /**
     * Get student ID from WordPress user ID
     *
     * @param int $user_id WordPress user ID
     * @return int|false Student ID or false
     */
    public static function get_student_id_by_user_id( $user_id ) {
        global $wpdb;
        $students_table = $wpdb->prefix . 'sm_students';

        $student_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $students_table WHERE user_id = %d",
            $user_id
        ) );

        return $student_id ? (int) $student_id : false;
    }
}
