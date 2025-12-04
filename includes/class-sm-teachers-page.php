<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Teachers_Page {

    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        add_action( 'wp_ajax_sm_validate_teacher_email', array( __CLASS__, 'ajax_validate_email' ) );
        add_action( 'wp_ajax_sm_validate_teacher_phone', array( __CLASS__, 'ajax_validate_phone' ) );
    }

    /**
     * AJAX handler for email validation
     */
    public static function ajax_validate_email() {
        check_ajax_referer( 'sm_validate_email', 'nonce' );

        $email = sanitize_email( $_POST['email'] ?? '' );
        $teacher_id = intval( $_POST['teacher_id'] ?? 0 );

        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email format', 'CTADZ-school-management' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sm_teachers';

        // Check teachers table
        $query = "SELECT id FROM $table WHERE LOWER(email) = LOWER(%s)";
        $params = array( $email );

        if ( $teacher_id > 0 ) {
            $query .= " AND id != %d";
            $params[] = $teacher_id;
        }

        $duplicate = $wpdb->get_var( $wpdb->prepare( $query, $params ) );
        if ( $duplicate ) {
            wp_send_json_error( array( 'message' => __( 'Email already used by another teacher', 'CTADZ-school-management' ) ) );
        }

        // Check WordPress users
        $existing_user = email_exists( $email );
        if ( $existing_user ) {
            $teacher_record = $teacher_id > 0 ? $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM $table WHERE id = %d", $teacher_id ) ) : null;
            if ( ! $teacher_record || $teacher_record->user_id != $existing_user ) {
                wp_send_json_error( array( 'message' => __( 'Email already registered in WordPress', 'CTADZ-school-management' ) ) );
            }
        }

        wp_send_json_success( array( 'message' => __( 'Email is available', 'CTADZ-school-management' ) ) );
    }

    /**
     * AJAX handler for phone validation
     */
    public static function ajax_validate_phone() {
        check_ajax_referer( 'sm_validate_phone', 'nonce' );

        $phone = sanitize_text_field( $_POST['phone'] ?? '' );
        $teacher_id = intval( $_POST['teacher_id'] ?? 0 );

        if ( empty( $phone ) ) {
            wp_send_json_error( array( 'message' => __( 'Phone number is required', 'CTADZ-school-management' ) ) );
        }

        // Validate phone format
        $phone_regex = '/^[\d\s\+\-\(\)]{7,20}$/';
        if ( ! preg_match( $phone_regex, $phone ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid phone format', 'CTADZ-school-management' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'sm_teachers';

        // Check teachers table for duplicate
        $query = "SELECT id FROM $table WHERE phone = %s";
        $params = array( $phone );

        if ( $teacher_id > 0 ) {
            $query .= " AND id != %d";
            $params[] = $teacher_id;
        }

        $duplicate = $wpdb->get_var( $wpdb->prepare( $query, $params ) );
        if ( $duplicate ) {
            wp_send_json_error( array( 'message' => __( 'Phone number already used by another teacher', 'CTADZ-school-management' ) ) );
        }

        wp_send_json_success( array( 'message' => __( 'Phone number is available', 'CTADZ-school-management' ) ) );
    }

    /**
     * Render the Teachers page
     */
    public static function render_teachers_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_teachers';

        // Handle bulk account creation
        if ( isset( $_POST['sm_create_teacher_accounts'] ) && check_admin_referer( 'sm_create_accounts_action', 'sm_create_accounts_nonce' ) ) {
            $results = SM_User_Management::bulk_create_teacher_accounts();

            // Show success messages and download CSV
            if ( ! empty( $results['success'] ) ) {
                $csv_file = SM_User_Management::export_credentials_to_csv( $results['success'], 'teacher' );
                $upload_dir = wp_upload_dir();
                $csv_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $csv_file );

                echo '<div class="updated notice"><p>' . sprintf(
                    esc_html__( '%d teacher account(s) created successfully! Download starting...', 'CTADZ-school-management' ),
                    count( $results['success'] )
                ) . '</p></div>';

                // Auto-download using JavaScript
                echo '<script type="text/javascript">
                    (function() {
                        var link = document.createElement("a");
                        link.href = "' . esc_js( $csv_url ) . '";
                        link.download = "' . esc_js( basename( $csv_file ) ) . '";
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    })();
                </script>';
            }

            // Show error messages
            if ( ! empty( $results['errors'] ) ) {
                echo '<div class="error notice"><p><strong>' . esc_html__( 'Some accounts could not be created:', 'CTADZ-school-management' ) . '</strong></p><ul style="margin-left: 20px;">';
                foreach ( $results['errors'] as $error ) {
                    echo '<li>';
                    if ( isset( $error['teacher_name'] ) ) {
                        echo '<strong>' . esc_html( $error['teacher_name'] ) . ':</strong> ';
                    }
                    echo esc_html( $error['message'] ) . '</li>';
                }
                echo '</ul></div>';
            }

            // Show info if no accounts to create
            if ( empty( $results['success'] ) && empty( $results['errors'] ) ) {
                echo '<div class="notice notice-info"><p>' . esc_html__( 'No new accounts to create. All active teachers already have accounts.', 'CTADZ-school-management' ) . '</p></div>';
            }
        }

        // Handle single account creation
        if ( isset( $_GET['create_account'] ) && check_admin_referer( 'sm_create_account_' . intval( $_GET['create_account'] ) ) ) {
            $teacher_id = intval( $_GET['create_account'] );
            $result = SM_User_Management::create_teacher_account( $teacher_id );

            if ( $result['success'] ) {
                echo '<div class="updated notice"><p>' . esc_html( $result['message'] ) . '<br><strong>' . esc_html__( 'Username:', 'CTADZ-school-management' ) . '</strong> ' . esc_html( $result['username'] ) . '<br><strong>' . esc_html__( 'Password:', 'CTADZ-school-management' ) . '</strong> ' . esc_html( $result['password'] ) . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html( $result['message'] ) . '</p></div>';
            }
        }

        // Handle delete action
        if ( isset( $_GET['delete'] ) && check_admin_referer( 'sm_delete_teacher_' . intval( $_GET['delete'] ) ) ) {
            // Check if teacher is assigned to any courses
            $courses_table = $wpdb->prefix . 'sm_courses';
            $teacher_id = intval( $_GET['delete'] );

            $courses_using = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $courses_table WHERE teacher_id = %d", $teacher_id ) );

            if ( $courses_using > 0 ) {
                echo '<div class="error notice"><p>' . sprintf(
                    esc_html__( 'Cannot delete this teacher. They are assigned to %d course(s).', 'CTADZ-school-management' ),
                    $courses_using
                ) . '</p></div>';
            } else {
                // Get teacher's WordPress user_id before deleting
                $teacher = $wpdb->get_row( $wpdb->prepare( "SELECT user_id, first_name, last_name FROM $table WHERE id = %d", $teacher_id ) );

                // Delete the teacher record
                $deleted = $wpdb->delete( $table, [ 'id' => $teacher_id ] );

                if ( $deleted ) {
                    // Also delete the WordPress user account if it exists
                    if ( $teacher && $teacher->user_id ) {
                        require_once( ABSPATH . 'wp-admin/includes/user.php' );
                        $user_deleted = wp_delete_user( $teacher->user_id );

                        if ( $user_deleted ) {
                            echo '<div class="updated notice"><p>' . sprintf(
                                esc_html__( 'Teacher "%s" and their WordPress account deleted successfully.', 'CTADZ-school-management' ),
                                $teacher->first_name . ' ' . $teacher->last_name
                            ) . '</p></div>';
                        } else {
                            echo '<div class="updated notice"><p>' . sprintf(
                                esc_html__( 'Teacher "%s" deleted, but WordPress account could not be removed (may need manual deletion).', 'CTADZ-school-management' ),
                                $teacher->first_name . ' ' . $teacher->last_name
                            ) . '</p></div>';
                        }
                    } else {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Teacher deleted successfully.', 'CTADZ-school-management' ) . '</p></div>';
                    }
                }
            }
        }

        // Handle form submission
        if ( isset( $_POST['sm_save_teacher'] ) && check_admin_referer( 'sm_save_teacher_action', 'sm_save_teacher_nonce' ) ) {
            $validation_result = self::validate_teacher_data( $_POST );

            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];
                $teacher_id = null;
                $is_new = empty( $_POST['teacher_id'] );

                if ( ! $is_new ) {
                    // Update existing teacher
                    $teacher_id = intval( $_POST['teacher_id'] );
                    $updated = $wpdb->update( $table, $data, [ 'id' => $teacher_id ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Teacher updated successfully.', 'CTADZ-school-management' ) . '</p></div>';
                    }
                } else {
                    // Insert new teacher
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        $teacher_id = $wpdb->insert_id;
                        echo '<div class="updated notice"><p>' . esc_html__( 'Teacher added successfully.', 'CTADZ-school-management' ) . '</p></div>';
                    }
                }

                // Auto-create WordPress account if teacher is active and doesn't have one
                if ( $teacher_id && $data['is_active'] ) {
                    $teacher_record = $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM $table WHERE id = %d", $teacher_id ) );

                    if ( ! $teacher_record->user_id ) {
                        $account_result = SM_User_Management::create_teacher_account( $teacher_id );

                        if ( $account_result['success'] ) {
                            // Create single-account CSV for download
                            $csv_file = SM_User_Management::export_credentials_to_csv( array( $account_result ), 'teacher' );
                            $upload_dir = wp_upload_dir();
                            $csv_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $csv_file );

                            echo '<div class="updated notice" style="border-left: 4px solid #46b450; padding: 20px; background: #fff; margin: 20px 0;">';
                            echo '<h2 style="margin-top: 0; color: #46b450;">✓ ' . esc_html__( 'WordPress Account Created Successfully!', 'CTADZ-school-management' ) . '</h2>';
                            echo '<table style="margin: 15px 0; border-collapse: collapse;">';
                            echo '<tr><td style="padding: 8px 15px 8px 0; font-weight: 600;">' . esc_html__( 'Name:', 'CTADZ-school-management' ) . '</td><td style="padding: 8px;">' . esc_html( $account_result['name'] ) . '</td></tr>';
                            echo '<tr><td style="padding: 8px 15px 8px 0; font-weight: 600;">' . esc_html__( 'Username:', 'CTADZ-school-management' ) . '</td><td style="padding: 8px;"><code id="sm-username" style="background: #f0f0f1; padding: 5px 10px; font-size: 14px; user-select: all;">' . esc_html( $account_result['username'] ) . '</code> <button type="button" class="button button-small" onclick="smCopyToClipboard(\'sm-username\')" style="margin-left: 10px;">📋 ' . esc_html__( 'Copy', 'CTADZ-school-management' ) . '</button></td></tr>';
                            echo '<tr><td style="padding: 8px 15px 8px 0; font-weight: 600;">' . esc_html__( 'Password:', 'CTADZ-school-management' ) . '</td><td style="padding: 8px;"><code id="sm-password" style="background: #f0f0f1; padding: 5px 10px; font-size: 14px; user-select: all;">' . esc_html( $account_result['password'] ) . '</code> <button type="button" class="button button-small" onclick="smCopyToClipboard(\'sm-password\')" style="margin-left: 10px;">📋 ' . esc_html__( 'Copy', 'CTADZ-school-management' ) . '</button></td></tr>';
                            echo '<tr><td style="padding: 8px 15px 8px 0; font-weight: 600;">' . esc_html__( 'Email:', 'CTADZ-school-management' ) . '</td><td style="padding: 8px;">' . esc_html( $account_result['email'] ) . '</td></tr>';
                            echo '<tr><td style="padding: 8px 15px 8px 0; font-weight: 600;">' . esc_html__( 'Role:', 'CTADZ-school-management' ) . '</td><td style="padding: 8px;">' . esc_html__( 'School Teacher', 'CTADZ-school-management' ) . '</td></tr>';
                            echo '</table>';
                            echo '<p style="margin: 15px 0;"><a href="' . esc_url( $csv_url ) . '" class="button button-primary" download>⬇️ ' . esc_html__( 'Download Credentials (CSV)', 'CTADZ-school-management' ) . '</a></p>';
                            echo '<p style="color: #d63638; font-weight: 600; margin: 15px 0;"><span class="dashicons dashicons-warning" style="vertical-align: middle;"></span> ' . esc_html__( 'Important: Please save these credentials now. They will not be shown again.', 'CTADZ-school-management' ) . '</p>';
                            echo '<p style="margin: 15px 0;"><a href="?page=school-management-teachers" class="button button-large">' . esc_html__( 'Continue to Teachers List', 'CTADZ-school-management' ) . '</a></p>';
                            echo '</div>';

                            // Add copy to clipboard script
                            echo '<script>
                            function smCopyToClipboard(elementId) {
                                var element = document.getElementById(elementId);
                                var text = element.textContent;
                                navigator.clipboard.writeText(text).then(function() {
                                    var btn = element.nextElementSibling;
                                    var originalText = btn.innerHTML;
                                    btn.innerHTML = "✓ ' . esc_js( __( 'Copied!', 'CTADZ-school-management' ) ) . '";
                                    btn.style.background = "#46b450";
                                    btn.style.color = "#fff";
                                    setTimeout(function() {
                                        btn.innerHTML = originalText;
                                        btn.style.background = "";
                                        btn.style.color = "";
                                    }, 2000);
                                });
                            }
                            </script>';
                        }
                    } else {
                        // Teacher already has account, just redirect
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-teachers"; }, 2000);</script>';
                    }
                } else {
                    // Teacher not active or no account created, redirect
                    echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-teachers"; }, 2000);</script>';
                }
            } else {
                echo '<div class="error notice"><p><strong>' . esc_html__( 'Please correct the following errors:', 'CTADZ-school-management' ) . '</strong></p>';
                echo '<ul style="margin-left: 20px;">';
                foreach ( $validation_result['errors'] as $error ) {
                    echo '<li>' . esc_html( $error ) . '</li>';
                }
                echo '</ul></div>';
            }
        }

        // Determine view
        $action = $_GET['action'] ?? 'list';
        $teacher = null;

        if ( $action === 'edit' && isset( $_GET['teacher_id'] ) ) {
            $teacher = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['teacher_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Teachers', 'CTADZ-school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'add':
                    self::render_teacher_form( null );
                    break;
                case 'edit':
                    self::render_teacher_form( $teacher );
                    break;
                default:
                    self::render_teachers_list();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Validate teacher data
     */
    private static function validate_teacher_data( $post_data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_teachers';
        $errors = [];
        
        $first_name = sanitize_text_field( trim( $post_data['first_name'] ?? '' ) );
        $last_name = sanitize_text_field( trim( $post_data['last_name'] ?? '' ) );
        $email = sanitize_email( trim( $post_data['email'] ?? '' ) );
        $phone = sanitize_text_field( trim( $post_data['phone'] ?? '' ) );
        $picture = esc_url_raw( trim( $post_data['picture'] ?? '' ) );
        $payment_term_id = intval( $post_data['payment_term_id'] ?? 0 );
        $hourly_rate = floatval( $post_data['hourly_rate'] ?? 0 );
        $is_active = isset( $post_data['is_active'] ) ? 1 : 0;
        $teacher_id = intval( $post_data['teacher_id'] ?? 0 );

        // Required field validation
        if ( empty( $first_name ) ) {
            $errors[] = __( 'First name is required.', 'CTADZ-school-management' );
        } elseif ( strlen( $first_name ) < 2 ) {
            $errors[] = __( 'First name must be at least 2 characters long.', 'CTADZ-school-management' );
        }

        if ( empty( $last_name ) ) {
            $errors[] = __( 'Last name is required.', 'CTADZ-school-management' );
        } elseif ( strlen( $last_name ) < 2 ) {
            $errors[] = __( 'Last name must be at least 2 characters long.', 'CTADZ-school-management' );
        }

        if ( empty( $email ) ) {
            $errors[] = __( 'Email address is required.', 'CTADZ-school-management' );
        } elseif ( ! is_email( $email ) ) {
            $errors[] = __( 'Please enter a valid email address.', 'CTADZ-school-management' );
        }

        if ( empty( $phone ) ) {
            $errors[] = __( 'Phone number is required.', 'CTADZ-school-management' );
        }

        // Check for duplicate phone number
        if ( ! empty( $phone ) ) {
            $duplicate_phone_query = "SELECT id FROM $table WHERE phone = %s";
            $phone_params = [ $phone ];

            if ( $teacher_id > 0 ) {
                $duplicate_phone_query .= " AND id != %d";
                $phone_params[] = $teacher_id;
            }

            $duplicate_phone = $wpdb->get_var( $wpdb->prepare( $duplicate_phone_query, $phone_params ) );
            if ( $duplicate_phone ) {
                $errors[] = sprintf( __( 'The phone number "%s" is already used by another teacher.', 'CTADZ-school-management' ), $phone );
            }
        }

        if ( $payment_term_id <= 0 ) {
            $errors[] = __( 'Payment term is required.', 'CTADZ-school-management' );
        }

        // Check for duplicate email in teachers table
        if ( ! empty( $email ) && is_email( $email ) ) {
            $duplicate_query = "SELECT id FROM $table WHERE LOWER(email) = LOWER(%s)";
            $params = [ $email ];

            if ( $teacher_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $params[] = $teacher_id;
            }

            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $params ) );
            if ( $duplicate ) {
                $errors[] = sprintf( __( 'The email address "%s" is already used by another teacher.', 'CTADZ-school-management' ), $email );
            }

            // Check if email is already registered in WordPress
            // Only check if this teacher doesn't have a user_id yet, or if email changed
            $teacher_record = $teacher_id > 0 ? $wpdb->get_row( $wpdb->prepare( "SELECT user_id, email FROM $table WHERE id = %d", $teacher_id ) ) : null;
            $email_changed = $teacher_record ? ( strtolower( $teacher_record->email ) !== strtolower( $email ) ) : true;

            if ( $email_changed ) {
                $existing_user_id = email_exists( $email );
                if ( $existing_user_id ) {
                    // Check if this WordPress user belongs to this teacher
                    if ( ! $teacher_record || $teacher_record->user_id != $existing_user_id ) {
                        $errors[] = sprintf(
                            __( 'The email address "%s" is already registered in WordPress. Please use a different email or contact the administrator.', 'CTADZ-school-management' ),
                            $email
                        );
                    }
                }
            }
        }

        // Validate picture URL if provided
        if ( ! empty( $picture ) && ! filter_var( $picture, FILTER_VALIDATE_URL ) ) {
            $errors[] = __( 'Please provide a valid picture URL.', 'CTADZ-school-management' );
        }

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'picture' => $picture ?: null,
                    'payment_term_id' => $payment_term_id,
                    'hourly_rate' => $hourly_rate,
                    'is_active' => $is_active,
                ]
            ];
        }

        return [ 'success' => false, 'errors' => $errors ];
    }

    /**
     * Render teachers list
     */
    private static function render_teachers_list() {
        global $wpdb;
        $teachers_table = $wpdb->prefix . 'sm_teachers';
        $terms_table = $wpdb->prefix . 'sm_payment_terms';
        $courses_table = $wpdb->prefix . 'sm_courses';

        // Get search parameter
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // Get sorting parameters
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'name';
        $order = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), [ 'ASC', 'DESC' ] ) ? strtoupper( $_GET['order'] ) : 'ASC';

        // Pagination
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        // Build WHERE clause for search
        $where_clause = '';
        if ( ! empty( $search ) ) {
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $where_clause = $wpdb->prepare( 
                "WHERE (t.first_name LIKE %s OR t.last_name LIKE %s OR t.email LIKE %s OR t.phone LIKE %s OR pt.name LIKE %s)", 
                $search_term, 
                $search_term,
                $search_term,
                $search_term,
                $search_term
            );
        }

        $total_teachers = $wpdb->get_var( "SELECT COUNT(*) FROM $teachers_table t $where_clause" );
        $total_pages = ceil( $total_teachers / $per_page );

        // Validate and set ORDER BY clause
        $valid_columns = [
            'name' => 'CONCAT(t.first_name, " ", t.last_name)',
            'email' => 't.email',
            'phone' => 't.phone',
            'payment_term' => 'pt.name',
            'hourly_rate' => 't.hourly_rate',
            'course_count' => 'course_count',
            'status' => 't.is_active'
        ];
        $orderby_column = isset( $valid_columns[ $orderby ] ) ? $valid_columns[ $orderby ] : 'CONCAT(t.first_name, " ", t.last_name)';
        $order_clause = "$orderby_column $order";

        // Get teachers with payment term names, course count, and user account status
        $teachers = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*,
                    pt.name as payment_term_name,
                    COUNT(DISTINCT c.id) as course_count,
                    CASE WHEN t.user_id IS NOT NULL THEN 1 ELSE 0 END as has_account
             FROM $teachers_table t
             LEFT JOIN $terms_table pt ON t.payment_term_id = pt.id
             LEFT JOIN $courses_table c ON t.id = c.teacher_id
             $where_clause
             GROUP BY t.id
             ORDER BY $order_clause
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ) );

        // Helper function to generate sortable column URL
        $get_sort_url = function( $column ) use ( $orderby, $order, $search ) {
            $new_order = ( $orderby === $column && $order === 'ASC' ) ? 'DESC' : 'ASC';
            $url = add_query_arg( [
                'page' => 'school-management-teachers',
                'orderby' => $column,
                'order' => $new_order,
            ] );
            
            if ( ! empty( $search ) ) {
                $url = add_query_arg( 's', urlencode( $search ), $url );
            }
            
            return esc_url( $url );
        };

        // Helper function to get sort indicator
        $get_sort_indicator = function( $column ) use ( $orderby, $order ) {
            if ( $orderby === $column ) {
                return $order === 'ASC' ? ' ▲' : ' ▼';
            }
            return '';
        };

        ?>
        <style>
        /* Sortable column styles */
        .wp-list-table thead th.sortable a,
        .wp-list-table thead th.sorted a {
            text-decoration: none;
            color: inherit;
            display: block;
            cursor: pointer;
            position: relative;
            padding-right: 20px;
        }
        
        .wp-list-table thead th.sortable a::after {
            content: "⇅";
            position: absolute;
            right: 0;
            opacity: 0.3;
            font-size: 14px;
            transition: opacity 0.2s;
        }
        
        .wp-list-table thead th.sortable a:hover {
            color: #0073aa;
        }
        
        .wp-list-table thead th.sortable a:hover::after {
            opacity: 0.7;
        }
        
        .wp-list-table thead th.sorted {
            background-color: #f0f0f1;
        }
        
        .wp-list-table thead th.sorted a {
            font-weight: 600;
            color: #0073aa;
        }
        
        .wp-list-table thead th.sorted a::after {
            display: none;
        }
        
        .wp-list-table thead th.non-sortable {
            color: #646970;
            cursor: default;
        }
        </style>
        
        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php esc_html_e( 'Teachers List', 'CTADZ-school-management' ); ?></h2>
                <p class="description">
                    <?php
                    if ( ! empty( $search ) ) {
                        printf( esc_html__( 'Showing %d teachers matching "%s"', 'CTADZ-school-management' ), $total_teachers, esc_html( $search ) );
                        echo ' <a href="?page=school-management-teachers" style="margin-left: 10px;">' . esc_html__( '[Clear search]', 'CTADZ-school-management' ) . '</a>';
                    } else {
                        printf( esc_html__( 'Total: %d teachers', 'CTADZ-school-management' ), $total_teachers );
                    }
                    ?>
                </p>
            </div>
            <div>
                <form method="post" style="display: inline-block; margin-right: 10px;">
                    <?php wp_nonce_field( 'sm_create_accounts_action', 'sm_create_accounts_nonce' ); ?>
                    <button type="submit" name="sm_create_teacher_accounts" class="button"
                            onclick="return confirm('<?php echo esc_js( __( 'This will create WordPress accounts for all teachers who don\'t have one yet. Continue?', 'CTADZ-school-management' ) ); ?>')">
                        <span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span>
                        <?php esc_html_e( 'Create Teacher Accounts', 'CTADZ-school-management' ); ?>
                    </button>
                </form>
                <a href="?page=school-management-teachers&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Teacher', 'CTADZ-school-management' ); ?>
                </a>
            </div>
        </div>

        <!-- Search Box -->
        <div class="tablenav top" style="margin-bottom: 15px;">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="school-management-teachers">
                <?php if ( ! empty( $orderby ) ) : ?>
                    <input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>">
                    <input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>">
                <?php endif; ?>
                <input type="search" 
                       name="s" 
                       value="<?php echo esc_attr( $search ); ?>" 
                       placeholder="<?php esc_attr_e( 'Search teachers by name, email, phone, or payment term...', 'CTADZ-school-management' ); ?>"
                       style="width: 350px; margin-right: 5px;">
                <button type="submit" class="button"><?php esc_html_e( 'Search', 'CTADZ-school-management' ); ?></button>
                <?php if ( ! empty( $search ) ) : ?>
                    <a href="?page=school-management-teachers" class="button" style="margin-left: 5px;">
                        <?php esc_html_e( 'Clear', 'CTADZ-school-management' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ( $teachers ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="non-sortable" style="width: 60px;"><?php esc_html_e( 'Picture', 'CTADZ-school-management' ); ?></th>
                        <th class="<?php echo $orderby === 'name' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'name' ); ?>">
                                <?php esc_html_e( 'Name', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'name' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'email' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'email' ); ?>">
                                <?php esc_html_e( 'Email', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'email' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'phone' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'phone' ); ?>">
                                <?php esc_html_e( 'Phone', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'phone' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'payment_term' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'payment_term' ); ?>">
                                <?php esc_html_e( 'Payment Term', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'payment_term' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'course_count' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'course_count' ); ?>">
                                <?php esc_html_e( 'Courses', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'course_count' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'hourly_rate' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'hourly_rate' ); ?>">
                                <?php esc_html_e( 'Hourly Rate', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'hourly_rate' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'status' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'status' ); ?>">
                                <?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'status' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable"><?php esc_html_e( 'Account', 'CTADZ-school-management' ); ?></th>
                        <th class="non-sortable" style="width: 180px;"><?php esc_html_e( 'Actions', 'CTADZ-school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $teachers as $teacher ) : ?>
                        <tr>
                            <td>
                                <?php if ( $teacher->picture ) : ?>
                                    <img src="<?php echo esc_url( $teacher->picture ); ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" alt="<?php echo esc_attr( $teacher->first_name . ' ' . $teacher->last_name ); ?>" />
                                <?php else : ?>
                                    <div style="width:40px;height:40px;border-radius:50%;background:#ddd;display:flex;align-items:center;justify-content:center;font-size:10px;color:#666;">No Photo</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html( $teacher->first_name . ' ' . $teacher->last_name ); ?></strong></td>
                            <td><?php echo esc_html( $teacher->email ); ?></td>
                            <td><?php echo esc_html( $teacher->phone ); ?></td>
                            <td><?php echo esc_html( $teacher->payment_term_name ?: '—' ); ?></td>
                            <td>
                                <?php
                                $count = intval( $teacher->course_count );
                                if ( $count > 0 ) {
                                    echo '<span style="color: #2271b1;"><strong>' . esc_html( $count ) . '</strong> ' . esc_html( _n( 'course', 'courses', $count, 'CTADZ-school-management' ) ) . '</span>';
                                } else {
                                    echo '<span style="color: #999;">' . esc_html__( 'No courses', 'CTADZ-school-management' ) . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html( number_format( $teacher->hourly_rate, 2 ) ); ?></td>
                            <td>
                                <?php if ( $teacher->is_active ) : ?>
                                    <span style="color: #46b450;">● <?php esc_html_e( 'Active', 'CTADZ-school-management' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #dc3232;">● <?php esc_html_e( 'Inactive', 'CTADZ-school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $teacher->has_account ) : ?>
                                    <span style="color: #46b450;">✓ <?php esc_html_e( 'Created', 'CTADZ-school-management' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #999;">— <?php esc_html_e( 'None', 'CTADZ-school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=school-management-teachers&action=edit&teacher_id=<?php echo intval( $teacher->id ); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                </a>
                                <?php if ( ! $teacher->has_account && $teacher->is_active ) : ?>
                                    <?php
                                    $create_account_url = wp_nonce_url(
                                        '?page=school-management-teachers&create_account=' . intval( $teacher->id ),
                                        'sm_create_account_' . intval( $teacher->id )
                                    );
                                    ?>
                                    <a href="<?php echo esc_url( $create_account_url ); ?>"
                                       class="button button-small"
                                       title="<?php esc_attr_e( 'Create Account', 'CTADZ-school-management' ); ?>">
                                        <span class="dashicons dashicons-admin-users" style="vertical-align: middle;"></span>
                                    </a>
                                <?php endif; ?>
                                <?php
                                $delete_url = wp_nonce_url(
                                    '?page=school-management-teachers&delete=' . intval( $teacher->id ),
                                    'sm_delete_teacher_' . intval( $teacher->id )
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this teacher?', 'CTADZ-school-management' ) ); ?>')">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle; color: #d63638;"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            // Pagination
            if ( $total_pages > 1 ) {
                $pagination_args = [
                    'base' => add_query_arg( 'paged', '%#%' ),
                    'format' => '',
                    'prev_text' => __( '« Previous', 'CTADZ-school-management' ),
                    'next_text' => __( 'Next »', 'CTADZ-school-management' ),
                    'total' => $total_pages,
                    'current' => $current_page,
                ];
                
                // Preserve search and sorting in pagination
                if ( ! empty( $search ) ) {
                    $pagination_args['add_args'] = [ 's' => urlencode( $search ) ];
                }
                if ( ! empty( $orderby ) ) {
                    $pagination_args['add_args']['orderby'] = $orderby;
                    $pagination_args['add_args']['order'] = $order;
                }
                
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                echo paginate_links( $pagination_args );
                echo '</div></div>';
            }
            ?>

        <?php else : ?>
            <div class="sm-empty-state" style="text-align: center; padding: 60px 20px; background: #fafafa; border: 1px dashed #ddd; border-radius: 4px;">
                <span class="dashicons dashicons-businessperson" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></span>
                <h3><?php esc_html_e( 'No Teachers Yet', 'CTADZ-school-management' ); ?></h3>
                <p><?php esc_html_e( 'Add your first teacher to get started.', 'CTADZ-school-management' ); ?></p>
                <a href="?page=school-management-teachers&action=add" class="button button-primary">
                    <?php esc_html_e( 'Add First Teacher', 'CTADZ-school-management' ); ?>
                </a>
            </div>
        <?php endif;
    }

    /**
     * Render teacher form
     */
    private static function render_teacher_form( $teacher = null ) {
        global $wpdb;
        $is_edit = ! empty( $teacher );
        
        // Get payment terms
        $payment_terms = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_payment_terms WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );
        
        $form_data = [];
        if ( isset( $_POST['sm_save_teacher'] ) ) {
            $form_data = [
                'first_name' => sanitize_text_field( $_POST['first_name'] ?? '' ),
                'last_name' => sanitize_text_field( $_POST['last_name'] ?? '' ),
                'email' => sanitize_email( $_POST['email'] ?? '' ),
                'phone' => sanitize_text_field( $_POST['phone'] ?? '' ),
                'picture' => esc_url_raw( $_POST['picture'] ?? '' ),
                'payment_term_id' => intval( $_POST['payment_term_id'] ?? 0 ),
                'hourly_rate' => floatval( $_POST['hourly_rate'] ?? 0 ),
                'is_active' => isset( $_POST['is_active'] ),
            ];
        } elseif ( $teacher ) {
            $form_data = [
                'first_name' => $teacher->first_name,
                'last_name' => $teacher->last_name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'picture' => $teacher->picture,
                'payment_term_id' => $teacher->payment_term_id,
                'hourly_rate' => $teacher->hourly_rate,
                'is_active' => $teacher->is_active,
            ];
        }
        
        ?>
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-teachers" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Teachers', 'CTADZ-school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Teacher', 'CTADZ-school-management' ) : esc_html__( 'Add New Teacher', 'CTADZ-school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_teacher_action', 'sm_save_teacher_nonce' ); ?>
            <input type="hidden" name="teacher_id" value="<?php echo esc_attr( $teacher->id ?? '' ); ?>" />

            <table class="form-table">
                <tr>
                    <td colspan="2" style="position: relative;">
                        <div id="sm_student_picture_box">
                            <?php if ( ! empty( $form_data['picture'] ) ) : ?>
                                <img id="sm_student_picture_preview" src="<?php echo esc_url( $form_data['picture'] ); ?>" alt="<?php esc_attr_e( 'Teacher Picture', 'CTADZ-school-management' ); ?>" />
                            <?php else : ?>
                                <span><?php esc_html_e( 'Click to upload', 'CTADZ-school-management' ); ?></span>
                                <img id="sm_student_picture_preview" src="" style="display:none;" alt="<?php esc_attr_e( 'Teacher Picture', 'CTADZ-school-management' ); ?>" />
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="picture" id="sm_student_picture" value="<?php echo esc_attr( $form_data['picture'] ?? '' ); ?>" />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="teacher_first_name"><?php esc_html_e( 'First Name', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="teacher_first_name" name="first_name" value="<?php echo esc_attr( $form_data['first_name'] ?? '' ); ?>" class="regular-text" required />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="teacher_last_name"><?php esc_html_e( 'Last Name', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="teacher_last_name" name="last_name" value="<?php echo esc_attr( $form_data['last_name'] ?? '' ); ?>" class="regular-text" required />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="teacher_email"><?php esc_html_e( 'Email Address', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="email" id="teacher_email" name="email" value="<?php echo esc_attr( $form_data['email'] ?? '' ); ?>" class="regular-text" required />
                        <span id="email-validation-msg" style="display: inline-block; margin-left: 10px; font-size: 13px;"></span>
                        <p class="description"><?php esc_html_e( 'Email must be unique and will be used as username.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="teacher_phone"><?php esc_html_e( 'Phone Number', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="teacher_phone" name="phone" value="<?php echo esc_attr( $form_data['phone'] ?? '' ); ?>" class="regular-text" required />
                        <span id="phone-validation-msg" style="display: inline-block; margin-left: 10px; font-size: 13px;"></span>
                        <p class="description"><?php esc_html_e( 'Enter a valid phone number (numbers, spaces, +, -, ( ) allowed).', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="teacher_payment_term"><?php esc_html_e( 'Payment Term', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="teacher_payment_term" name="payment_term_id" required>
                            <option value=""><?php esc_html_e( 'Select Payment Term', 'CTADZ-school-management' ); ?></option>
                            <?php foreach ( $payment_terms as $term ) : ?>
                                <option value="<?php echo intval( $term->id ); ?>" <?php selected( $form_data['payment_term_id'] ?? 0, $term->id ); ?>>
                                    <?php echo esc_html( $term->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'How this teacher will be paid.', 'CTADZ-school-management' ); ?>
                            <a href="?page=school-management-payment-terms" target="_blank"><?php esc_html_e( 'Manage payment terms', 'CTADZ-school-management' ); ?></a>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="teacher_hourly_rate"><?php esc_html_e( 'Hourly Rate', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="teacher_hourly_rate" name="hourly_rate" value="<?php echo esc_attr( $form_data['hourly_rate'] ?? 0 ); ?>" min="0" step="0.01" />
                        <p class="description"><?php esc_html_e( 'Payment rate per hour (optional).', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="teacher_is_active"><?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="teacher_is_active" name="is_active" value="1" <?php checked( $form_data['is_active'] ?? true ); ?> />
                            <?php esc_html_e( 'Active (available for course assignment)', 'CTADZ-school-management' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( 
                    $is_edit ? __( 'Update Teacher', 'CTADZ-school-management' ) : __( 'Add Teacher', 'CTADZ-school-management' ), 
                    'primary', 
                    'sm_save_teacher', 
                    false 
                ); ?>
                <a href="?page=school-management-teachers" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'CTADZ-school-management' ); ?></a>
            </p>
            
            <p class="description">
                <span style="color: #d63638;">*</span> <?php esc_html_e( 'Required fields', 'CTADZ-school-management' ); ?>
            </p>
        </form>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var emailTimeout;
            var emailValid = <?php echo $is_edit ? 'true' : 'false'; ?>;
            var phoneValid = <?php echo ! empty( $form_data['phone'] ) ? 'true' : 'false'; ?>;
            var originalEmail = '<?php echo esc_js( $form_data['email'] ?? '' ); ?>';

            // Email validation
            $('#teacher_email').on('input', function() {
                clearTimeout(emailTimeout);
                var email = $(this).val().trim();
                var msgSpan = $('#email-validation-msg');

                if (!email) {
                    msgSpan.html('').css('color', '');
                    emailValid = false;
                    return;
                }

                // Check email format
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    msgSpan.html('✗ <?php esc_html_e( 'Invalid email format', 'CTADZ-school-management' ); ?>').css('color', '#d63638');
                    emailValid = false;
                    return;
                }

                // Skip AJAX check if email hasn't changed
                if (email === originalEmail) {
                    msgSpan.html('✓ <?php esc_html_e( 'Email unchanged', 'CTADZ-school-management' ); ?>').css('color', '#46b450');
                    emailValid = true;
                    return;
                }

                msgSpan.html('⏳ <?php esc_html_e( 'Checking...', 'CTADZ-school-management' ); ?>').css('color', '#999');

                emailTimeout = setTimeout(function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sm_validate_teacher_email',
                            nonce: '<?php echo wp_create_nonce( 'sm_validate_email' ); ?>',
                            email: email,
                            teacher_id: $('input[name="teacher_id"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                msgSpan.html('✓ ' + response.data.message).css('color', '#46b450');
                                emailValid = true;
                            } else {
                                msgSpan.html('✗ ' + response.data.message).css('color', '#d63638');
                                emailValid = false;
                            }
                        },
                        error: function() {
                            msgSpan.html('✗ <?php esc_html_e( 'Validation error', 'CTADZ-school-management' ); ?>').css('color', '#d63638');
                            emailValid = false;
                        }
                    });
                }, 500); // Wait 500ms after user stops typing
            });

            // Phone validation
            var phoneTimeout;
            var originalPhone = '<?php echo esc_js( $form_data['phone'] ?? '' ); ?>';

            $('#teacher_phone').on('input', function() {
                clearTimeout(phoneTimeout);
                var phone = $(this).val().trim();
                var msgSpan = $('#phone-validation-msg');

                if (!phone) {
                    msgSpan.html('').css('color', '');
                    phoneValid = false;
                    return;
                }

                // Phone regex: allows digits, spaces, +, -, (, )
                var phoneRegex = /^[\d\s\+\-\(\)]{7,20}$/;
                if (!phoneRegex.test(phone)) {
                    msgSpan.html('✗ <?php esc_html_e( 'Invalid phone format', 'CTADZ-school-management' ); ?>').css('color', '#d63638');
                    phoneValid = false;
                    return;
                }

                // Skip AJAX check if phone hasn't changed
                if (phone === originalPhone) {
                    msgSpan.html('✓ <?php esc_html_e( 'Phone unchanged', 'CTADZ-school-management' ); ?>').css('color', '#46b450');
                    phoneValid = true;
                    return;
                }

                msgSpan.html('⏳ <?php esc_html_e( 'Checking...', 'CTADZ-school-management' ); ?>').css('color', '#999');

                phoneTimeout = setTimeout(function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'sm_validate_teacher_phone',
                            nonce: '<?php echo wp_create_nonce( 'sm_validate_phone' ); ?>',
                            phone: phone,
                            teacher_id: $('input[name="teacher_id"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                msgSpan.html('✓ ' + response.data.message).css('color', '#46b450');
                                phoneValid = true;
                            } else {
                                msgSpan.html('✗ ' + response.data.message).css('color', '#d63638');
                                phoneValid = false;
                            }
                        },
                        error: function() {
                            msgSpan.html('✗ <?php esc_html_e( 'Validation error', 'CTADZ-school-management' ); ?>').css('color', '#d63638');
                            phoneValid = false;
                        }
                    });
                }, 500); // Wait 500ms after user stops typing
            });

            // Form submission validation
            $('form').on('submit', function(e) {
                if (!emailValid || !phoneValid) {
                    e.preventDefault();
                    alert('<?php esc_html_e( 'Please fix the validation errors before submitting.', 'CTADZ-school-management' ); ?>');
                    return false;
                }
            });

            // Trigger validation on page load if fields have values
            if ($('#teacher_email').val()) {
                $('#teacher_email').trigger('input');
            }
            if ($('#teacher_phone').val()) {
                $('#teacher_phone').trigger('input');
            }
        });
        </script>
        <?php
    }
}

// Instantiate class
new SM_Teachers_Page();