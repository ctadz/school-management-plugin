<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Students_Page {

    /**
     * Render the Students page
     */
    public static function render_students_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_students';

        // Handle delete action
        if ( isset( $_GET['delete'] ) && check_admin_referer( 'sm_delete_student_' . intval( $_GET['delete'] ) ) ) {
            $deleted = $wpdb->delete( $table, [ 'id' => intval( $_GET['delete'] ) ] );
            if ( $deleted ) {
                echo '<div class="updated notice"><p>' . esc_html__( 'Student deleted successfully.', 'CTADZ-school-management' ) . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html__( 'Error deleting student.', 'CTADZ-school-management' ) . '</p></div>';
            }
        }

        // Handle manual discount recalculation
        if ( isset( $_GET['recalculate_discount'] ) && check_admin_referer( 'sm_recalculate_discount_' . intval( $_GET['recalculate_discount'] ) ) ) {
            $student_id = intval( $_GET['recalculate_discount'] );
            $student = $wpdb->get_row( $wpdb->prepare( "SELECT parent_phone FROM $table WHERE id = %d", $student_id ) );

            if ( $student && ! empty( $student->parent_phone ) ) {
                $recalc_count = SM_Family_Discount::recalculate_family_discounts( $student->parent_phone );
                if ( $recalc_count > 0 ) {
                    echo '<div class="updated notice"><p>' . sprintf( esc_html__( 'Successfully recalculated %d payment schedules for this family!', 'CTADZ-school-management' ), $recalc_count ) . '</p></div>';
                } else {
                    echo '<div class="notice notice-info"><p>' . esc_html__( 'No payment schedules needed recalculation.', 'CTADZ-school-management' ) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-warning"><p>' . esc_html__( 'This student has no parent phone number set.', 'CTADZ-school-management' ) . '</p></div>';
            }
        }

        // Handle form submission
        if ( isset( $_POST['sm_save_student'] ) && check_admin_referer( 'sm_save_student_action', 'sm_save_student_nonce' ) ) {
            $validation_result = self::validate_student_data( $_POST );

            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];

                if ( ! empty( $_POST['student_id'] ) ) {
                    $student_id = intval( $_POST['student_id'] );

                    // Get old parent phone before update
                    $old_student = $wpdb->get_row( $wpdb->prepare( "SELECT parent_phone FROM $table WHERE id = %d", $student_id ) );
                    $old_parent_phone = $old_student ? $old_student->parent_phone : '';

                    $updated = $wpdb->update( $table, $data, [ 'id' => $student_id ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Student updated successfully.', 'CTADZ-school-management' ) . '</p></div>';

                        // Check if parent phone changed
                        $new_parent_phone = $data['parent_phone'];
                        if ( $old_parent_phone !== $new_parent_phone ) {
                            // Recalculate discounts for old family (if any)
                            if ( ! empty( $old_parent_phone ) ) {
                                $recalc_count = SM_Family_Discount::recalculate_family_discounts( $old_parent_phone );
                                if ( $recalc_count > 0 ) {
                                    echo '<div class="updated notice"><p>' . sprintf( esc_html__( 'Recalculated %d payment schedules for old family.', 'CTADZ-school-management' ), $recalc_count ) . '</p></div>';
                                }
                            }

                            // Recalculate discounts for new family (if any)
                            if ( ! empty( $new_parent_phone ) ) {
                                $recalc_count = SM_Family_Discount::recalculate_family_discounts( $new_parent_phone );
                                if ( $recalc_count > 0 ) {
                                    echo '<div class="updated notice"><p>' . sprintf( esc_html__( 'Recalculated %d payment schedules for new family.', 'CTADZ-school-management' ), $recalc_count ) . '</p></div>';
                                }
                            }
                        }

                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-students"; }, 2000);</script>';
                    }
                } else {
                    // New student - insert into database
                    // Note: If family discount modal was shown, form was prevented on first submit,
                    // so this is still the first actual insert regardless of family_discount_confirmed flag
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        $new_student_id = $wpdb->insert_id;

                        // Generate student code in format STUYYYYxxxx
                        $student_code = self::generate_student_code( $new_student_id );
                        if ( $student_code ) {
                            $wpdb->update( $table, [ 'student_code' => $student_code ], [ 'id' => $new_student_id ] );
                        }

                        // Recalculate discounts for the family if parent phone was provided
                        if ( ! empty( $data['parent_phone'] ) ) {
                            $recalc_count = SM_Family_Discount::recalculate_family_discounts( $data['parent_phone'] );
                        }
                    }

                    // Show success messages and enrollment prompt
                    if ( $inserted ) {
                        // Show success message
                        echo '<div class="updated notice"><p>' . esc_html__( 'Student added successfully.', 'CTADZ-school-management' ) . '</p></div>';

                        // Show family discount message if applicable
                        if ( ! empty( $data['parent_phone'] ) && isset( $recalc_count ) && $recalc_count > 0 ) {
                            echo '<div class="updated notice"><p>' . sprintf( esc_html__( 'Applied family discounts to %d existing payment schedules.', 'CTADZ-school-management' ), $recalc_count ) . '</p></div>';
                        }

                        // Get student name for enrollment prompt
                        $student_name = sanitize_text_field( $data['name'] );

                        // Show enrollment prompt modal
                        ?>
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            setTimeout(function() {
                                showEnrollmentPrompt('<?php echo esc_js( $student_name ); ?>', <?php echo intval( $new_student_id ); ?>);
                            }, 500);
                        });
                        </script>
                        <?php
                    }
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

        // Determine which view to show
        $action = $_GET['action'] ?? 'list';
        $student = null;

        if ( $action === 'edit' && isset( $_GET['student_id'] ) ) {
            $student = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['student_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Students', 'CTADZ-school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'add':
                    self::render_student_form( null );
                    break;
                case 'edit':
                    self::render_student_form( $student );
                    break;
                default:
                    self::render_students_list();
                    break;
            }

            // Output family discount modal (once per page)
            self::render_family_discount_modal();

            // Output enrollment prompt modal (once per page)
            self::render_enrollment_prompt_modal();
            ?>
        </div>
        <?php
    }

    /**
     * Validate student data
     */
    private static function validate_student_data( $post_data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_students';
        $errors = [];

        // Get first name and last name, then combine into full name (Last Name First Name format)
        $first_name = sanitize_text_field( trim( $post_data['first_name'] ?? '' ) );
        $last_name = sanitize_text_field( trim( $post_data['last_name'] ?? '' ) );
        $name = trim( $last_name . ' ' . $first_name );

        $email = sanitize_email( trim( $post_data['email'] ?? '' ) );
        $phone = sanitize_text_field( trim( $post_data['phone'] ?? '' ) );

        // Convert date from dd-mm-yyyy display format to Y-m-d database format
        $dob_input = sanitize_text_field( trim( $post_data['dob'] ?? '' ) );
        $dob = '';
        if ( ! empty( $dob_input ) ) {
            // Check if it's in dd-mm-yyyy format
            if ( preg_match( '/^(\d{2})-(\d{2})-(\d{4})$/', $dob_input, $matches ) ) {
                $dob = $matches[3] . '-' . $matches[2] . '-' . $matches[1]; // Convert to Y-m-d
            } else {
                $dob = $dob_input; // Keep as-is if already in Y-m-d format
            }
        }

        $level_id = intval( $post_data['level_id'] ?? 0 );
        $picture = esc_url_raw( trim( $post_data['picture'] ?? '' ) );
        $blood_type = sanitize_text_field( trim( $post_data['blood_type'] ?? '' ) );
        $student_id = intval( $post_data['student_id'] ?? 0 );

        // Parent/Guardian fields
        $parent_name = sanitize_text_field( trim( $post_data['parent_name'] ?? '' ) );
        $parent_phone = sanitize_text_field( trim( $post_data['parent_phone'] ?? '' ) );
        $parent_email = sanitize_email( trim( $post_data['parent_email'] ?? '' ) );

        // Required field validation for first name and last name
        if ( empty( $first_name ) ) {
            $errors[] = __( 'First name is required.', 'CTADZ-school-management' );
        } elseif ( strlen( $first_name ) < 2 ) {
            $errors[] = __( 'First name must be at least 2 characters long.', 'CTADZ-school-management' );
        } elseif ( strlen( $first_name ) > 50 ) {
            $errors[] = __( 'First name cannot exceed 50 characters.', 'CTADZ-school-management' );
        }

        if ( empty( $last_name ) ) {
            $errors[] = __( 'Last name is required.', 'CTADZ-school-management' );
        } elseif ( strlen( $last_name ) < 2 ) {
            $errors[] = __( 'Last name must be at least 2 characters long.', 'CTADZ-school-management' );
        } elseif ( strlen( $last_name ) > 50 ) {
            $errors[] = __( 'Last name cannot exceed 50 characters.', 'CTADZ-school-management' );
        }

        // Validate combined full name
        if ( strlen( $name ) > 100 ) {
            $errors[] = __( 'Full name cannot exceed 100 characters.', 'CTADZ-school-management' );
        }

        if ( empty( $email ) ) {
            $errors[] = __( 'Email address is required.', 'CTADZ-school-management' );
        } elseif ( ! is_email( $email ) ) {
            $errors[] = __( 'Please enter a valid email address.', 'CTADZ-school-management' );
        }

        if ( empty( $phone ) ) {
            $errors[] = __( 'Phone number is required.', 'CTADZ-school-management' );
        } elseif ( strlen( $phone ) < 8 ) {
            $errors[] = __( 'Please enter a valid phone number (minimum 8 digits).', 'CTADZ-school-management' );
        }

        if ( empty( $dob ) ) {
            $errors[] = __( 'Date of birth is required.', 'CTADZ-school-management' );
        } elseif ( ! self::is_valid_date( $dob ) ) {
            $errors[] = __( 'Please enter a valid date of birth.', 'CTADZ-school-management' );
        } elseif ( self::is_future_date( $dob ) ) {
            $errors[] = __( 'Date of birth cannot be in the future.', 'CTADZ-school-management' );
        }

        if ( $level_id <= 0 ) {
            $errors[] = __( 'Level is required.', 'CTADZ-school-management' );
        }

        // Check for duplicate name
        if ( ! empty( $name ) ) {
            $duplicate_query = "SELECT id FROM $table WHERE LOWER(name) = LOWER(%s)";
            $query_params = [ $name ];
            
            if ( $student_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $query_params[] = $student_id;
            }
            
            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $query_params ) );
            
            if ( $duplicate ) {
                $errors[] = sprintf( 
                    __( 'A student with the name "%s" already exists. Please use a different name.', 'CTADZ-school-management' ), 
                    $name 
                );
            }
        }

        // Check for duplicate email
        if ( ! empty( $email ) && is_email( $email ) ) {
            $duplicate_email_query = "SELECT id FROM $table WHERE LOWER(email) = LOWER(%s)";
            $email_params = [ $email ];
            
            if ( $student_id > 0 ) {
                $duplicate_email_query .= " AND id != %d";
                $email_params[] = $student_id;
            }
            
            $duplicate_email = $wpdb->get_var( $wpdb->prepare( $duplicate_email_query, $email_params ) );
            
            if ( $duplicate_email ) {
                $errors[] = sprintf( 
                    __( 'The email address "%s" is already registered. Please use a different email.', 'CTADZ-school-management' ), 
                    $email 
                );
            }
        }

        // Validate blood type if provided
        if ( ! empty( $blood_type ) ) {
            $valid_blood_types = [ 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-' ];
            if ( ! in_array( $blood_type, $valid_blood_types ) ) {
                $errors[] = __( 'Please select a valid blood type.', 'CTADZ-school-management' );
            }
        }

        // Validate picture URL if provided
        if ( ! empty( $picture ) && ! filter_var( $picture, FILTER_VALIDATE_URL ) ) {
            $errors[] = __( 'Please provide a valid picture URL.', 'CTADZ-school-management' );
        }

        // Validate parent email if provided
        if ( ! empty( $parent_email ) && ! is_email( $parent_email ) ) {
            $errors[] = __( 'Please enter a valid parent/guardian email address.', 'CTADZ-school-management' );
        }

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'name'         => $name,
                    'email'        => $email,
                    'phone'        => $phone,
                    'dob'          => $dob,
                    'level_id'     => $level_id,
                    'picture'      => $picture,
                    'blood_type'   => $blood_type ?: null,
                    'parent_name'  => $parent_name ?: null,
                    'parent_phone' => $parent_phone ?: null,
                    'parent_email' => $parent_email ?: null,
                ]
            ];
        } else {
            return [
                'success' => false,
                'errors'  => $errors
            ];
        }
    }

    /**
     * Check if date is valid
     */
    private static function is_valid_date( $date ) {
        $d = DateTime::createFromFormat( 'Y-m-d', $date );
        return $d && $d->format( 'Y-m-d' ) === $date;
    }

    /**
     * Check if date is in the future
     */
    private static function is_future_date( $date ) {
        return strtotime( $date ) > time();
    }

    /**
     * Render students list with pagination
     */
    private static function render_students_list() {
        global $wpdb;
        $students_table = $wpdb->prefix . 'sm_students';
        $levels_table = $wpdb->prefix . 'sm_levels';
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $payment_schedules_table = $wpdb->prefix . 'sm_payment_schedules';

        // Get search parameter
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

        // Get filter parameters
        $level_id = isset( $_GET['level_id'] ) ? intval( $_GET['level_id'] ) : 0;
        $level_name = isset( $_GET['level_name'] ) ? sanitize_text_field( $_GET['level_name'] ) : '';
        $filter_payment_status = isset( $_GET['filter_payment_status'] ) ? sanitize_text_field( $_GET['filter_payment_status'] ) : '';
        $filter_enrollment = isset( $_GET['filter_enrollment'] ) ? sanitize_text_field( $_GET['filter_enrollment'] ) : '';
        $filter_discount = isset( $_GET['filter_discount'] ) ? sanitize_text_field( $_GET['filter_discount'] ) : '';
        $filter_portal_access = isset( $_GET['filter_portal_access'] ) ? sanitize_text_field( $_GET['filter_portal_access'] ) : '';

        // Get sorting parameters
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'name';
        $order = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), [ 'ASC', 'DESC' ] ) ? strtoupper( $_GET['order'] ) : 'ASC';

        // Pagination setup
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        // Get all levels for filter dropdown
        $all_levels = $wpdb->get_results( "SELECT id, name FROM $levels_table WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );

        // Build WHERE clause for search and filters
        $where_sql = '';
        $where_params = array();
        $where_conditions = array();

        // Enhanced search - includes name, email, phone, student_code, and level name
        if ( ! empty( $search ) ) {
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $where_conditions[] = "(s.name LIKE %s OR s.email LIKE %s OR s.phone LIKE %s OR s.student_code LIKE %s OR l.name LIKE %s)";
            $where_params = array_merge( $where_params, array( $search_term, $search_term, $search_term, $search_term, $search_term ) );
        }

        // Level filter
        if ( $level_id > 0 ) {
            $where_conditions[] = "s.level_id = %d";
            $where_params[] = $level_id;
        }

        if ( ! empty( $where_conditions ) ) {
            $where_sql = "WHERE " . implode( " AND ", $where_conditions );
        }

        // Build HAVING clause for aggregate-based filters (payment status, enrollment)
        $having_conditions = array();
        $having_params = array();

        // Payment status filter (based on aggregated overdue_amount)
        if ( ! empty( $filter_payment_status ) ) {
            if ( $filter_payment_status === 'overdue' ) {
                $having_conditions[] = "SUM(CASE WHEN ps.status IN ('pending', 'partial') AND ps.due_date < CURDATE() THEN (ps.expected_amount - ps.paid_amount) ELSE 0 END) > 0";
            } elseif ( $filter_payment_status === 'pending' ) {
                $having_conditions[] = "SUM(CASE WHEN ps.status IN ('pending', 'partial') THEN (ps.expected_amount - ps.paid_amount) ELSE 0 END) > 0";
                $having_conditions[] = "SUM(CASE WHEN ps.status IN ('pending', 'partial') AND ps.due_date < CURDATE() THEN (ps.expected_amount - ps.paid_amount) ELSE 0 END) = 0";
            } elseif ( $filter_payment_status === 'clear' ) {
                $having_conditions[] = "SUM(CASE WHEN ps.status IN ('pending', 'partial') THEN (ps.expected_amount - ps.paid_amount) ELSE 0 END) = 0";
            }
        }

        // Enrollment filter
        if ( ! empty( $filter_enrollment ) ) {
            if ( $filter_enrollment === 'enrolled' ) {
                $having_conditions[] = "COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.id END) > 0";
            } elseif ( $filter_enrollment === 'not_enrolled' ) {
                $having_conditions[] = "COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.id END) = 0";
            }
        }

        // Discount filter (based on family enrollment count - 2+ family members with active enrollments = has discount)
        if ( ! empty( $filter_discount ) ) {
            if ( $filter_discount === 'has_discount' ) {
                // Student has active enrollments AND family has 2+ members with active enrollments
                $having_conditions[] = "COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.id END) > 0";
                $having_conditions[] = "family_enrollment_count >= 2";
            } elseif ( $filter_discount === 'no_discount' ) {
                // No active enrollments OR family has less than 2 members
                $having_conditions[] = "(COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.id END) = 0 OR family_enrollment_count < 2)";
            }
        }

        // Validate and set ORDER BY clause - simple columns only
        $valid_columns = array(
            'name' => 's.name',
            'email' => 's.email',
            'phone' => 's.phone',
            'level' => 'l.name',
            'enrollment_date' => 's.enrollment_date',
            'active_enrollments' => 'active_enrollments'
        );
        $orderby_column = isset( $valid_columns[ $orderby ] ) ? $valid_columns[ $orderby ] : 's.name';
        $order_clause = "$orderby_column $order";

        // Get students with level names, enrollment count, payment info, and portal access (if plugin is active)
        $has_portal_plugin = class_exists( 'SMSP_Auth' );

        // Build portal access SELECT and JOIN conditionally
        $portal_select = '';
        $portal_join = '';
        if ( $has_portal_plugin ) {
            $portal_credentials_table = $wpdb->prefix . 'smsp_student_credentials';
            $portal_select = ', spc.student_id as has_portal_access';
            $portal_join = "LEFT JOIN $portal_credentials_table spc ON s.id = spc.student_id";

            // Portal access filter (only when portal plugin is active)
            if ( ! empty( $filter_portal_access ) ) {
                if ( $filter_portal_access === 'has_access' ) {
                    $having_conditions[] = "MAX(CASE WHEN spc.student_id IS NOT NULL THEN 1 ELSE 0 END) = 1";
                } elseif ( $filter_portal_access === 'no_access' ) {
                    $having_conditions[] = "MAX(CASE WHEN spc.student_id IS NOT NULL THEN 1 ELSE 0 END) = 0";
                }
            }
        }

        // Build HAVING SQL after all conditions are added
        $having_sql = ! empty( $having_conditions ) ? "HAVING " . implode( " AND ", $having_conditions ) : '';

        $query = "
            SELECT s.*,
                   l.name as level_name,
                   COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.id END) as active_enrollments,
                   SUM(CASE
                       WHEN ps.status = 'pending' AND ps.due_date < CURDATE()
                       THEN (ps.expected_amount - ps.paid_amount)
                       WHEN ps.status = 'partial' AND ps.due_date < CURDATE()
                       THEN (ps.expected_amount - ps.paid_amount)
                       ELSE 0
                   END) as overdue_amount,
                   SUM(CASE
                       WHEN ps.status IN ('pending', 'partial')
                       THEN (ps.expected_amount - ps.paid_amount)
                       ELSE 0
                   END) as total_outstanding,
                   (SELECT COUNT(DISTINCT s2.id)
                    FROM $students_table s2
                    INNER JOIN $enrollments_table e2 ON s2.id = e2.student_id AND e2.status = 'active'
                    WHERE s2.parent_phone = s.parent_phone
                    AND s2.parent_phone != ''
                    AND s2.parent_phone IS NOT NULL) as family_enrollment_count
                   $portal_select
            FROM $students_table s
            LEFT JOIN $levels_table l ON s.level_id = l.id
            LEFT JOIN $enrollments_table e ON s.id = e.student_id
            LEFT JOIN $payment_schedules_table ps ON e.id = ps.enrollment_id
            $portal_join
            $where_sql
            GROUP BY s.id
            $having_sql
            ORDER BY $order_clause
            LIMIT %d OFFSET %d
        ";

        // Merge all parameters
        $all_params = array_merge( $where_params, array( $per_page, $offset ) );

        if ( ! empty( $all_params ) ) {
            $students = $wpdb->get_results( $wpdb->prepare( $query, $all_params ) );
        } else {
            $students = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );
        }

        // Count total with filters (need to use subquery for HAVING)
        $count_query = "
            SELECT COUNT(*) FROM (
                SELECT s.id,
                       (SELECT COUNT(DISTINCT s2.id)
                        FROM $students_table s2
                        INNER JOIN $enrollments_table e2 ON s2.id = e2.student_id AND e2.status = 'active'
                        WHERE s2.parent_phone = s.parent_phone
                        AND s2.parent_phone != ''
                        AND s2.parent_phone IS NOT NULL) as family_enrollment_count
                FROM $students_table s
                LEFT JOIN $levels_table l ON s.level_id = l.id
                LEFT JOIN $enrollments_table e ON s.id = e.student_id
                LEFT JOIN $payment_schedules_table ps ON e.id = ps.enrollment_id
                $portal_join
                $where_sql
                GROUP BY s.id
                $having_sql
            ) as filtered_students
        ";
        if ( ! empty( $where_params ) ) {
            $total_students = $wpdb->get_var( $wpdb->prepare( $count_query, $where_params ) );
        } else {
            $total_students = $wpdb->get_var( $count_query );
        }
        $total_pages = ceil( $total_students / $per_page );

        // Helper function to generate sortable column URL
        $get_sort_url = function( $column ) use ( $orderby, $order, $search, $level_id, $level_name, $filter_payment_status, $filter_enrollment, $filter_discount, $filter_portal_access ) {
            $new_order = ( $orderby === $column && $order === 'ASC' ) ? 'DESC' : 'ASC';
            $url = add_query_arg( [
                'page' => 'school-management-students',
                'orderby' => $column,
                'order' => $new_order,
            ] );

            if ( ! empty( $search ) ) {
                $url = add_query_arg( 's', urlencode( $search ), $url );
            }

            if ( $level_id > 0 ) {
                $url = add_query_arg( 'level_id', $level_id, $url );
                if ( ! empty( $level_name ) ) {
                    $url = add_query_arg( 'level_name', urlencode( $level_name ), $url );
                }
            }

            if ( ! empty( $filter_payment_status ) ) {
                $url = add_query_arg( 'filter_payment_status', $filter_payment_status, $url );
            }

            if ( ! empty( $filter_enrollment ) ) {
                $url = add_query_arg( 'filter_enrollment', $filter_enrollment, $url );
            }

            if ( ! empty( $filter_discount ) ) {
                $url = add_query_arg( 'filter_discount', $filter_discount, $url );
            }

            if ( ! empty( $filter_portal_access ) ) {
                $url = add_query_arg( 'filter_portal_access', $filter_portal_access, $url );
            }

            return esc_url( $url );
        };

        // Helper function to generate filter URL
        $get_filter_url = function( $filter_name, $filter_value ) use ( $search, $level_id, $level_name, $filter_payment_status, $filter_enrollment, $filter_discount, $filter_portal_access, $orderby, $order ) {
            // Start with a clean base URL (not using current URL parameters)
            $base_url = admin_url( 'admin.php' );
            $url = add_query_arg( 'page', 'school-management-students', $base_url );

            if ( ! empty( $search ) && $filter_name !== 's' ) {
                $url = add_query_arg( 's', $search, $url );
            }

            if ( ! empty( $orderby ) ) {
                $url = add_query_arg( 'orderby', $orderby, $url );
                $url = add_query_arg( 'order', $order, $url );
            }

            // Keep existing filters except the one being changed
            if ( $filter_name !== 'level_id' && $level_id > 0 ) {
                $url = add_query_arg( 'level_id', $level_id, $url );
                if ( ! empty( $level_name ) ) {
                    $url = add_query_arg( 'level_name', $level_name, $url );
                }
            }

            if ( $filter_name !== 'filter_payment_status' && ! empty( $filter_payment_status ) ) {
                $url = add_query_arg( 'filter_payment_status', $filter_payment_status, $url );
            }

            if ( $filter_name !== 'filter_enrollment' && ! empty( $filter_enrollment ) ) {
                $url = add_query_arg( 'filter_enrollment', $filter_enrollment, $url );
            }

            if ( $filter_name !== 'filter_discount' && ! empty( $filter_discount ) ) {
                $url = add_query_arg( 'filter_discount', $filter_discount, $url );
            }

            if ( $filter_name !== 'filter_portal_access' && ! empty( $filter_portal_access ) ) {
                $url = add_query_arg( 'filter_portal_access', $filter_portal_access, $url );
            }

            // Add the new filter value if not empty
            if ( ! empty( $filter_value ) ) {
                $url = add_query_arg( $filter_name, $filter_value, $url );
                if ( $filter_name === 'level_id' ) {
                    // Get level name for the URL
                    foreach ( $GLOBALS['sm_all_levels'] ?? [] as $lvl ) {
                        if ( $lvl->id == $filter_value ) {
                            $url = add_query_arg( 'level_name', $lvl->name, $url );
                            break;
                        }
                    }
                }
            }

            return esc_url( $url );
        };

        // Store levels globally for the filter URL helper
        $GLOBALS['sm_all_levels'] = $all_levels;

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
        
        /* Add sort icon to show column is sortable */
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
        
        /* Hide the double arrow when actively sorted */
        .wp-list-table thead th.sorted a::after {
            display: none;
        }
        
        /* Non-sortable columns styling */
        .wp-list-table thead th.non-sortable {
            color: #646970;
            cursor: default;
        }

        /* Discount badge styling */
        .sm-discount-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            background: #e7f7ed;
            border-radius: 3px;
            color: #2c5f2d;
            font-size: 13px;
        }
        .sm-discount-badge .dashicons {
            color: #46b450;
        }
        .sm-discount-badge-clickable {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .sm-discount-badge-clickable:hover {
            background: #d4f0dd;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(70, 180, 80, 0.3);
        }

        /* Dark mode support for discount badge */
        @media (prefers-color-scheme: dark) {
            .sm-discount-badge {
                background: rgba(70, 180, 80, 0.15);
                color: #a7f3b3;
            }
            .sm-discount-badge .dashicons {
                color: #68de7c;
            }
            .sm-discount-badge-clickable:hover {
                background: rgba(70, 180, 80, 0.25);
                box-shadow: 0 2px 4px rgba(104, 222, 124, 0.3);
            }
        }
        </style>

        <?php if ( $level_id > 0 && ! empty( $level_name ) ) : ?>
        <!-- Breadcrumb Navigation -->
        <div class="sm-breadcrumb" style="margin-bottom: 15px; padding: 10px 0; border-bottom: 1px solid #ddd;">
            <a href="?page=school-management-levels" style="text-decoration: none; color: #2271b1;">
                <span class="dashicons dashicons-arrow-left-alt2" style="font-size: 16px; vertical-align: middle;"></span>
                <?php esc_html_e( 'Levels', 'CTADZ-school-management' ); ?>
            </a>
            <span style="margin: 0 8px; color: #999;">›</span>
            <span style="font-weight: 500;"><?php echo esc_html( $level_name ); ?></span>
            <span style="margin: 0 8px; color: #999;">›</span>
            <span style="color: #666;"><?php esc_html_e( 'Students', 'CTADZ-school-management' ); ?></span>
        </div>
        <?php endif; ?>

        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;">
                    <?php
                    if ( $level_id > 0 && ! empty( $level_name ) ) {
                        printf( esc_html__( 'Students in %s', 'CTADZ-school-management' ), esc_html( $level_name ) );
                    } else {
                        esc_html_e( 'Students List', 'CTADZ-school-management' );
                    }
                    ?>
                </h2>
                <p class="description">
                    <?php
                    $has_filters = ! empty( $search ) || $level_id > 0 || ! empty( $filter_payment_status ) || ! empty( $filter_enrollment ) || ! empty( $filter_discount ) || ! empty( $filter_portal_access );
                    if ( $has_filters ) {
                        printf( esc_html__( 'Showing %d filtered students', 'CTADZ-school-management' ), $total_students );
                        echo ' <a href="?page=school-management-students" style="margin-left: 10px;">' . esc_html__( '[Clear all filters]', 'CTADZ-school-management' ) . '</a>';
                    } else {
                        printf( esc_html__( 'Total: %d students', 'CTADZ-school-management' ), $total_students );
                    }
                    ?>
                </p>
            </div>
            <div>
                <a href="?page=school-management-students&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Student', 'CTADZ-school-management' ); ?>
                </a>
            </div>
        </div>

        <!-- Search Box -->
        <div class="tablenav top" style="margin-bottom: 15px;">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="school-management-students">
                <?php if ( ! empty( $orderby ) ) : ?>
                    <input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>">
                    <input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>">
                <?php endif; ?>
                <?php if ( $level_id > 0 ) : ?>
                    <input type="hidden" name="level_id" value="<?php echo esc_attr( $level_id ); ?>">
                    <?php if ( ! empty( $level_name ) ) : ?>
                        <input type="hidden" name="level_name" value="<?php echo esc_attr( $level_name ); ?>">
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ( ! empty( $filter_payment_status ) ) : ?>
                    <input type="hidden" name="filter_payment_status" value="<?php echo esc_attr( $filter_payment_status ); ?>">
                <?php endif; ?>
                <?php if ( ! empty( $filter_enrollment ) ) : ?>
                    <input type="hidden" name="filter_enrollment" value="<?php echo esc_attr( $filter_enrollment ); ?>">
                <?php endif; ?>
                <input type="search"
                       name="s"
                       value="<?php echo esc_attr( $search ); ?>"
                       placeholder="<?php esc_attr_e( 'Search name, email, phone, code, or level...', 'CTADZ-school-management' ); ?>"
                       style="margin-right: 5px; width: 280px;">
                <button type="submit" class="button"><?php esc_html_e( 'Search', 'CTADZ-school-management' ); ?></button>
            </form>
        </div>

        <!-- Active Filters Display -->
        <?php if ( $has_filters ) : ?>
        <div class="sm-active-filters" style="margin-bottom: 15px; padding: 10px 15px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
            <strong style="margin-right: 10px;"><?php esc_html_e( 'Active Filters:', 'CTADZ-school-management' ); ?></strong>
            <?php if ( ! empty( $search ) ) : ?>
                <span class="sm-filter-tag" style="display: inline-block; background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                    <?php printf( esc_html__( 'Search: %s', 'CTADZ-school-management' ), esc_html( $search ) ); ?>
                    <a href="<?php echo esc_url( $get_filter_url( 's', '' ) ); ?>" style="color: white; margin-left: 5px; text-decoration: none;">&times;</a>
                </span>
            <?php endif; ?>
            <?php if ( $level_id > 0 ) : ?>
                <span class="sm-filter-tag" style="display: inline-block; background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                    <?php printf( esc_html__( 'Level: %s', 'CTADZ-school-management' ), esc_html( $level_name ) ); ?>
                    <a href="<?php echo esc_url( $get_filter_url( 'level_id', '' ) ); ?>" style="color: white; margin-left: 5px; text-decoration: none;">&times;</a>
                </span>
            <?php endif; ?>
            <?php if ( ! empty( $filter_payment_status ) ) : ?>
                <span class="sm-filter-tag" style="display: inline-block; background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                    <?php
                    $status_labels = [
                        'overdue' => __( 'Overdue', 'CTADZ-school-management' ),
                        'pending' => __( 'Pending', 'CTADZ-school-management' ),
                        'clear' => __( 'Clear', 'CTADZ-school-management' ),
                    ];
                    printf( esc_html__( 'Payment: %s', 'CTADZ-school-management' ), esc_html( $status_labels[ $filter_payment_status ] ?? $filter_payment_status ) );
                    ?>
                    <a href="<?php echo esc_url( $get_filter_url( 'filter_payment_status', '' ) ); ?>" style="color: white; margin-left: 5px; text-decoration: none;">&times;</a>
                </span>
            <?php endif; ?>
            <?php if ( ! empty( $filter_enrollment ) ) : ?>
                <span class="sm-filter-tag" style="display: inline-block; background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                    <?php
                    $enroll_labels = [
                        'enrolled' => __( 'Enrolled', 'CTADZ-school-management' ),
                        'not_enrolled' => __( 'Not Enrolled', 'CTADZ-school-management' ),
                    ];
                    printf( esc_html__( 'Enrollment: %s', 'CTADZ-school-management' ), esc_html( $enroll_labels[ $filter_enrollment ] ?? $filter_enrollment ) );
                    ?>
                    <a href="<?php echo esc_url( $get_filter_url( 'filter_enrollment', '' ) ); ?>" style="color: white; margin-left: 5px; text-decoration: none;">&times;</a>
                </span>
            <?php endif; ?>
            <?php if ( ! empty( $filter_discount ) ) : ?>
                <span class="sm-filter-tag" style="display: inline-block; background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                    <?php
                    $discount_labels = [
                        'has_discount' => __( 'Has Discount', 'CTADZ-school-management' ),
                        'no_discount' => __( 'No Discount', 'CTADZ-school-management' ),
                    ];
                    printf( esc_html__( 'Discount: %s', 'CTADZ-school-management' ), esc_html( $discount_labels[ $filter_discount ] ?? $filter_discount ) );
                    ?>
                    <a href="<?php echo esc_url( $get_filter_url( 'filter_discount', '' ) ); ?>" style="color: white; margin-left: 5px; text-decoration: none;">&times;</a>
                </span>
            <?php endif; ?>
            <?php if ( ! empty( $filter_portal_access ) ) : ?>
                <span class="sm-filter-tag" style="display: inline-block; background: #2271b1; color: white; padding: 3px 8px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                    <?php
                    $portal_labels = [
                        'has_access' => __( 'Has Access', 'CTADZ-school-management' ),
                        'no_access' => __( 'No Access', 'CTADZ-school-management' ),
                    ];
                    printf( esc_html__( 'Portal: %s', 'CTADZ-school-management' ), esc_html( $portal_labels[ $filter_portal_access ] ?? $filter_portal_access ) );
                    ?>
                    <a href="<?php echo esc_url( $get_filter_url( 'filter_portal_access', '' ) ); ?>" style="color: white; margin-left: 5px; text-decoration: none;">&times;</a>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ( $students ) : ?>
            <table class="wp-list-table widefat fixed striped mobile-card-layout sm-filterable-table">
                <thead>
                    <tr>
                        <th class="non-sortable" style="width: 60px;"><?php esc_html_e( 'Picture', 'CTADZ-school-management' ); ?></th>
                        <th class="non-sortable" style="width: 110px;"><?php esc_html_e( 'Student Code', 'CTADZ-school-management' ); ?></th>
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
                        <!-- Level Column with Filter -->
                        <th class="<?php echo $orderby === 'level' ? 'sorted' : 'sortable'; ?> sm-filterable-column">
                            <div class="sm-column-header">
                                <a href="<?php echo $get_sort_url( 'level' ); ?>">
                                    <?php esc_html_e( 'Level', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'level' ); ?>
                                </a>
                                <button type="button" class="sm-filter-trigger <?php echo $level_id > 0 ? 'active' : ''; ?>" data-filter="level">
                                    <span class="dashicons dashicons-filter"></span>
                                </button>
                            </div>
                            <div class="sm-filter-dropdown" id="filter-dropdown-level" style="display: none;">
                                <div class="sm-filter-search">
                                    <input type="text" placeholder="<?php esc_attr_e( 'Search levels...', 'CTADZ-school-management' ); ?>" class="sm-filter-search-input" data-filter="level">
                                </div>
                                <ul class="sm-filter-options">
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'level_id', '' ) ); ?>" class="<?php echo $level_id === 0 ? 'active' : ''; ?>"><?php esc_html_e( '(All Levels)', 'CTADZ-school-management' ); ?></a></li>
                                    <?php foreach ( $all_levels as $lvl ) : ?>
                                        <li><a href="<?php echo esc_url( $get_filter_url( 'level_id', $lvl->id ) ); ?>" class="<?php echo $level_id == $lvl->id ? 'active' : ''; ?>" data-search="<?php echo esc_attr( strtolower( $lvl->name ) ); ?>"><?php echo esc_html( $lvl->name ); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </th>
                        <!-- Enrollments Column with Filter -->
                        <th class="<?php echo $orderby === 'active_enrollments' ? 'sorted' : 'sortable'; ?> sm-filterable-column">
                            <div class="sm-column-header">
                                <a href="<?php echo $get_sort_url( 'active_enrollments' ); ?>">
                                    <?php esc_html_e( 'Enrollments', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'active_enrollments' ); ?>
                                </a>
                                <button type="button" class="sm-filter-trigger <?php echo ! empty( $filter_enrollment ) ? 'active' : ''; ?>" data-filter="enrollment">
                                    <span class="dashicons dashicons-filter"></span>
                                </button>
                            </div>
                            <div class="sm-filter-dropdown" id="filter-dropdown-enrollment" style="display: none;">
                                <ul class="sm-filter-options">
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_enrollment', '' ) ); ?>" class="<?php echo empty( $filter_enrollment ) ? 'active' : ''; ?>"><?php esc_html_e( '(All)', 'CTADZ-school-management' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_enrollment', 'enrolled' ) ); ?>" class="<?php echo $filter_enrollment === 'enrolled' ? 'active' : ''; ?>"><?php esc_html_e( 'Has Enrollments', 'CTADZ-school-management' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_enrollment', 'not_enrolled' ) ); ?>" class="<?php echo $filter_enrollment === 'not_enrolled' ? 'active' : ''; ?>"><?php esc_html_e( 'No Enrollments', 'CTADZ-school-management' ); ?></a></li>
                                </ul>
                            </div>
                        </th>
                        <!-- Discount Column with Filter -->
                        <th class="non-sortable sm-filterable-column">
                            <div class="sm-column-header">
                                <span><?php esc_html_e( 'Discount', 'CTADZ-school-management' ); ?></span>
                                <button type="button" class="sm-filter-trigger <?php echo ! empty( $filter_discount ) ? 'active' : ''; ?>" data-filter="discount">
                                    <span class="dashicons dashicons-filter"></span>
                                </button>
                            </div>
                            <div class="sm-filter-dropdown" id="filter-dropdown-discount" style="display: none;">
                                <ul class="sm-filter-options">
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_discount', '' ) ); ?>" class="<?php echo empty( $filter_discount ) ? 'active' : ''; ?>"><?php esc_html_e( '(All)', 'CTADZ-school-management' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_discount', 'has_discount' ) ); ?>" class="<?php echo $filter_discount === 'has_discount' ? 'active' : ''; ?>"><span style="color: #46b450;">●</span> <?php esc_html_e( 'Has Discount', 'CTADZ-school-management' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_discount', 'no_discount' ) ); ?>" class="<?php echo $filter_discount === 'no_discount' ? 'active' : ''; ?>"><span style="color: #999;">●</span> <?php esc_html_e( 'No Discount', 'CTADZ-school-management' ); ?></a></li>
                                </ul>
                            </div>
                        </th>
                        <!-- Payment Status Column with Filter -->
                        <th class="non-sortable sm-filterable-column">
                            <div class="sm-column-header">
                                <span><?php esc_html_e( 'Payment Status', 'CTADZ-school-management' ); ?></span>
                                <button type="button" class="sm-filter-trigger <?php echo ! empty( $filter_payment_status ) ? 'active' : ''; ?>" data-filter="payment">
                                    <span class="dashicons dashicons-filter"></span>
                                </button>
                            </div>
                            <div class="sm-filter-dropdown" id="filter-dropdown-payment" style="display: none;">
                                <ul class="sm-filter-options">
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_payment_status', '' ) ); ?>" class="<?php echo empty( $filter_payment_status ) ? 'active' : ''; ?>"><?php esc_html_e( '(All)', 'CTADZ-school-management' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_payment_status', 'overdue' ) ); ?>" class="<?php echo $filter_payment_status === 'overdue' ? 'active' : ''; ?>"><span style="color: #d63638;">●</span> <?php esc_html_e( 'Overdue', 'CTADZ-school-management' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_payment_status', 'pending' ) ); ?>" class="<?php echo $filter_payment_status === 'pending' ? 'active' : ''; ?>"><span style="color: #f0ad4e;">●</span> <?php esc_html_e( 'Pending', 'CTADZ-school-management' ); ?></a></li>
                                    <li><a href="<?php echo esc_url( $get_filter_url( 'filter_payment_status', 'clear' ) ); ?>" class="<?php echo $filter_payment_status === 'clear' ? 'active' : ''; ?>"><span style="color: #46b450;">●</span> <?php esc_html_e( 'Clear', 'CTADZ-school-management' ); ?></a></li>
                                </ul>
                            </div>
                        </th>
                        <th class="non-sortable"><?php esc_html_e( 'Balance', 'CTADZ-school-management' ); ?></th>
                        <?php if ( class_exists( 'SMSP_Auth' ) ) : ?>
                            <!-- Portal Access Column with Filter -->
                            <th class="non-sortable sm-filterable-column">
                                <div class="sm-column-header">
                                    <span><?php esc_html_e( 'Portal Access', 'CTADZ-school-management' ); ?></span>
                                    <button type="button" class="sm-filter-trigger <?php echo ! empty( $filter_portal_access ) ? 'active' : ''; ?>" data-filter="portal">
                                        <span class="dashicons dashicons-filter"></span>
                                    </button>
                                </div>
                                <div class="sm-filter-dropdown" id="filter-dropdown-portal" style="display: none;">
                                    <ul class="sm-filter-options">
                                        <li><a href="<?php echo esc_url( $get_filter_url( 'filter_portal_access', '' ) ); ?>" class="<?php echo empty( $filter_portal_access ) ? 'active' : ''; ?>"><?php esc_html_e( '(All)', 'CTADZ-school-management' ); ?></a></li>
                                        <li><a href="<?php echo esc_url( $get_filter_url( 'filter_portal_access', 'has_access' ) ); ?>" class="<?php echo $filter_portal_access === 'has_access' ? 'active' : ''; ?>"><span style="color: #46b450;">●</span> <?php esc_html_e( 'Has Access', 'CTADZ-school-management' ); ?></a></li>
                                        <li><a href="<?php echo esc_url( $get_filter_url( 'filter_portal_access', 'no_access' ) ); ?>" class="<?php echo $filter_portal_access === 'no_access' ? 'active' : ''; ?>"><span style="color: #999;">●</span> <?php esc_html_e( 'No Access', 'CTADZ-school-management' ); ?></a></li>
                                    </ul>
                                </div>
                            </th>
                        <?php endif; ?>
                        <th class="non-sortable" style="width: 150px;"><?php esc_html_e( 'Actions', 'CTADZ-school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $students as $student ) : ?>
                        <?php
                        $active_enrollments = intval( $student->active_enrollments );
                        $overdue_amount = floatval( $student->overdue_amount );
                        $total_outstanding = floatval( $student->total_outstanding );
                        
                        // Determine payment status
                        if ( $overdue_amount > 0 ) {
                            $payment_status = 'overdue';
                            $status_label = __( 'Overdue', 'CTADZ-school-management' );
                            $status_color = '#d63638';
                            $status_bg = '#fef2f2';
                        } elseif ( $total_outstanding > 0 ) {
                            $payment_status = 'partial';
                            $status_label = __( 'Pending', 'CTADZ-school-management' );
                            $status_color = '#f0ad4e';
                            $status_bg = '#fef8e7';
                        } elseif ( $active_enrollments > 0 ) {
                            $payment_status = 'paid';
                            $status_label = __( 'Paid Up', 'CTADZ-school-management' );
                            $status_color = '#46b450';
                            $status_bg = '#ecf7ed';
                        } else {
                            $payment_status = 'none';
                            $status_label = __( 'No Enrollments', 'CTADZ-school-management' );
                            $status_color = '#999';
                            $status_bg = '#f5f5f5';
                        }
                        ?>
                        <tr>
                            <td data-label="<?php echo esc_attr__( 'Picture', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Picture', 'CTADZ-school-management' ); ?>:</span>
                                <?php if ( $student->picture ) : ?>
                                    <img src="<?php echo esc_url( $student->picture ); ?>" class="student-avatar" alt="<?php echo esc_attr( $student->name ); ?>" />
                                <?php else : ?>
                                    <div class="student-avatar-placeholder"><?php esc_html_e( 'No Photo', 'CTADZ-school-management' ); ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Student Code', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Student Code', 'CTADZ-school-management' ); ?>:</span>
                                <strong class="student-code">
                                    <?php echo esc_html( $student->student_code ?: 'ID-' . $student->id ); ?>
                                </strong>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Name', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Name', 'CTADZ-school-management' ); ?>:</span>
                                <strong><?php echo esc_html( $student->name ); ?></strong>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Email', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Email', 'CTADZ-school-management' ); ?>:</span>
                                <?php echo esc_html( $student->email ); ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Phone', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Phone', 'CTADZ-school-management' ); ?>:</span>
                                <?php echo esc_html( $student->phone ); ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Level', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Level', 'CTADZ-school-management' ); ?>:</span>
                                <span class="sm-level-badge"><?php echo esc_html( $student->level_name ?: '—' ); ?></span>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Enrollments', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Enrollments', 'CTADZ-school-management' ); ?>:</span>
                                <?php if ( $active_enrollments > 0 ) : ?>
                                    <span class="text-primary">
                                        <strong><?php echo esc_html( $active_enrollments ); ?></strong>
                                        <?php echo esc_html( _n( 'course', 'courses', $active_enrollments, 'CTADZ-school-management' ) ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="text-muted"><?php esc_html_e( 'None', 'CTADZ-school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Discount', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Discount', 'CTADZ-school-management' ); ?>:</span>
                                <?php
                                // Calculate discount for this student
                                if ( SM_Family_Discount::is_enabled() && $active_enrollments > 0 ) {
                                    $discount_info = SM_Family_Discount::calculate_discount_for_student( $student->id );
                                    if ( $discount_info['percentage'] > 0 ) :
                                ?>
                                    <span class="sm-discount-badge sm-discount-badge-clickable"
                                          data-student-id="<?php echo esc_attr( $student->id ); ?>"
                                          data-parent-phone="<?php echo esc_attr( $student->parent_phone ); ?>"
                                          title="<?php echo esc_attr( $discount_info['reason'] . ' - Click to view family' ); ?>">
                                        <span class="dashicons dashicons-tag" style="font-size: 14px; vertical-align: middle;"></span>
                                        <strong><?php echo esc_html( number_format( $discount_info['percentage'], 1 ) ); ?>%</strong>
                                    </span>
                                <?php
                                    else :
                                        echo '<span class="text-muted">—</span>';
                                    endif;
                                } else {
                                    echo '<span class="text-muted">—</span>';
                                }
                                ?>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Payment Status', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Payment Status', 'CTADZ-school-management' ); ?>:</span>
                                <span class="sm-status-badge">
                                    <span class="sm-status-dot" style="background: <?php echo esc_attr( $status_color ); ?>;"></span>
                                    <strong style="color: <?php echo esc_attr( $status_color ); ?>;"><?php echo esc_html( $status_label ); ?></strong>
                                </span>
                            </td>
                            <td data-label="<?php echo esc_attr__( 'Balance', 'CTADZ-school-management' ); ?>">
                                <span class="mobile-label"><?php esc_html_e( 'Balance', 'CTADZ-school-management' ); ?>:</span>
                                <?php if ( $total_outstanding > 0 ) : ?>
                                    <strong style="color: <?php echo $overdue_amount > 0 ? '#d63638' : '#f0ad4e'; ?>;">
                                        <?php echo esc_html( number_format( $total_outstanding, 2 ) ); ?> DZD
                                    </strong>
                                <?php else : ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <?php if ( class_exists( 'SMSP_Auth' ) ) : ?>
                                <td data-label="<?php echo esc_attr__( 'Portal Access', 'CTADZ-school-management' ); ?>">
                                    <span class="mobile-label"><?php esc_html_e( 'Portal Access', 'CTADZ-school-management' ); ?>:</span>
                                    <?php if ( $student->has_portal_access ) : ?>
                                        <span class="sm-status-badge" style="background: #ecf7ed; margin-bottom: 3px;">
                                            <span class="sm-status-dot" style="background: #46b450;"></span>
                                            <strong style="color: #46b450;"><?php esc_html_e( 'Active', 'CTADZ-school-management' ); ?></strong>
                                        </span>
                                        <button class="button button-small smsp-reset-password"
                                                data-student-id="<?php echo intval( $student->id ); ?>"
                                                title="<?php esc_attr_e( 'Reset Portal Password', 'CTADZ-school-management' ); ?>"
                                                style="display: block; margin-top: 3px;">
                                            <?php esc_html_e( 'Reset Password', 'CTADZ-school-management' ); ?>
                                        </button>
                                    <?php else : ?>
                                        <button class="button button-small smsp-create-password"
                                                data-student-id="<?php echo intval( $student->id ); ?>"
                                                title="<?php esc_attr_e( 'Create Portal Access', 'CTADZ-school-management' ); ?>">
                                            <?php esc_html_e( 'Create Portal Access', 'CTADZ-school-management' ); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td class="actions">
                                <a href="?page=school-management-students&action=edit&student_id=<?php echo intval( $student->id ); ?>"
                                   class="button button-small" title="<?php esc_attr_e( 'Edit Student', 'CTADZ-school-management' ); ?>">
                                    <span class="dashicons dashicons-edit align-middle"></span>
                                    <span class="button-text"><?php esc_html_e( 'Edit', 'CTADZ-school-management' ); ?></span>
                                </a>
                                <?php
                                // Show recalculate discount button only if student is part of a family (2+ students)
                                if ( SM_Family_Discount::is_enabled() && ! empty( $student->parent_phone ) ) :
                                    $family_count = SM_Family_Discount::count_family_enrollments( $student->parent_phone );
                                    if ( $family_count >= 2 ) :
                                        $recalc_url = wp_nonce_url(
                                            '?page=school-management-students&recalculate_discount=' . intval( $student->id ),
                                            'sm_recalculate_discount_' . intval( $student->id )
                                        );
                                ?>
                                    <a href="<?php echo esc_url( $recalc_url ); ?>"
                                       class="button button-small"
                                       title="<?php esc_attr_e( 'Recalculate Family Discount', 'CTADZ-school-management' ); ?>">
                                        <span class="dashicons dashicons-update align-middle"></span>
                                        <span class="button-text"><?php esc_html_e( 'Recalc Discount', 'CTADZ-school-management' ); ?></span>
                                    </a>
                                <?php
                                    endif;
                                endif;
                                ?>
                                <?php
                                $delete_url = wp_nonce_url(
                                    '?page=school-management-students&delete=' . intval( $student->id ),
                                    'sm_delete_student_' . intval( $student->id )
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>"
                                   class="button button-small button-link-delete"
                                   title="<?php esc_attr_e( 'Delete Student', 'CTADZ-school-management' ); ?>"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this student? This action cannot be undone.', 'CTADZ-school-management' ) ); ?>')">
                                    <span class="dashicons dashicons-trash align-middle text-danger"></span>
                                    <span class="button-text"><?php esc_html_e( 'Delete', 'CTADZ-school-management' ); ?></span>
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
                    'base'      => add_query_arg( 'paged', '%#%' ),
                    'format'    => '',
                    'prev_text' => __( '« Previous', 'CTADZ-school-management' ),
                    'next_text' => __( 'Next »', 'CTADZ-school-management' ),
                    'total'     => $total_pages,
                    'current'   => $current_page,
                ];
                
                // Preserve search and sorting in pagination
                if ( ! empty( $search ) ) {
                    $pagination_args['add_args'] = [ 's' => urlencode( $search ) ];
                }
                if ( ! empty( $orderby ) ) {
                    $pagination_args['add_args']['orderby'] = $orderby;
                    $pagination_args['add_args']['order'] = $order;
                }

                echo '<div class="tablenav bottom">';
                echo '<div class="tablenav-pages">';
                echo paginate_links( $pagination_args );
                echo '</div>';
                echo '</div>';
            }
            ?>

        <?php else : ?>
            <div class="sm-empty-state" style="text-align: center; padding: 60px 20px; background: #fafafa; border: 1px dashed #ddd; border-radius: 4px;">
                <span class="dashicons dashicons-groups" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></span>
                <h3><?php esc_html_e( 'No Students Yet', 'CTADZ-school-management' ); ?></h3>
                <p><?php esc_html_e( 'Start building your student database by adding your first student.', 'CTADZ-school-management' ); ?></p>
                <a href="?page=school-management-students&action=add" class="button button-primary">
                    <?php esc_html_e( 'Add First Student', 'CTADZ-school-management' ); ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Family Members Modal -->
        <div id="sm-family-members-modal" class="sm-modal" style="display: none;">
            <div class="sm-modal-content">
                <div class="sm-modal-header">
                    <h2><?php esc_html_e( 'Family Members', 'CTADZ-school-management' ); ?></h2>
                    <button class="sm-modal-close" id="sm-family-modal-close">&times;</button>
                </div>
                <div class="sm-modal-body" id="sm-family-members-list">
                    <div class="sm-modal-loading">
                        <span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite; font-size: 32px;"></span>
                        <p><?php esc_html_e( 'Loading family members...', 'CTADZ-school-management' ); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .sm-modal {
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
            }
            .sm-modal-content {
                position: fixed;
                top: 80px;
                left: 50%;
                margin-left: -300px;
                background: #fff;
                padding: 0;
                width: 600px;
                border-radius: 4px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                -webkit-font-smoothing: subpixel-antialiased;
                -moz-osx-font-smoothing: auto;
                font-smooth: never;
                text-rendering: geometricPrecision;
                transform: translateZ(0);
                backface-visibility: hidden;
                perspective: 1000px;
                filter: none;
                will-change: auto;
                image-rendering: -webkit-optimize-contrast;
            }
            .sm-modal-content * {
                -webkit-font-smoothing: subpixel-antialiased;
                -moz-osx-font-smoothing: auto;
                transform: translateZ(0);
            }
            .sm-modal-header {
                padding: 20px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .sm-modal-header h2 {
                margin: 0;
                font-size: 20px;
            }
            .sm-modal-close {
                background: none;
                border: none;
                font-size: 32px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 32px;
                height: 32px;
                line-height: 1;
            }
            .sm-modal-close:hover {
                color: #d63638;
            }
            .sm-modal-body {
                padding: 20px;
                max-height: 60vh;
                overflow-y: auto;
            }
            .sm-modal-loading {
                text-align: center;
                padding: 40px 20px;
                color: #666;
            }
            .sm-family-member {
                padding: 15px;
                margin-bottom: 10px;
                background: #f9f9f9;
                border-left: 4px solid #0073aa;
                border-radius: 3px;
            }
            .sm-family-member h3 {
                margin: 0 0 8px 0;
                font-size: 16px;
            }
            .sm-family-member-info {
                font-size: 13px;
                color: #666;
                margin: 4px 0;
            }
            .sm-family-member-actions {
                margin-top: 10px;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }

            /* Dark mode support */
            @media (prefers-color-scheme: dark) {
                .sm-modal-content {
                    background: #1d2327;
                    color: #c3c4c7;
                }
                .sm-modal-header {
                    border-bottom-color: #3c434a;
                }
                .sm-modal-close {
                    color: #c3c4c7;
                }
                .sm-modal-close:hover {
                    color: #d63638;
                }
                .sm-modal-loading {
                    color: #c3c4c7;
                }
                .sm-family-member {
                    background: #2c3338;
                    border-left-color: #72aee6;
                }
                .sm-family-member-info {
                    color: #a7aaad;
                }
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Localized strings
            var smFamilyStrings = {
                editStudent: '<?php echo esc_js( __( 'Edit Student', 'CTADZ-school-management' ) ); ?>'
            };

            // Handle discount badge click
            $(document).on('click', '.sm-discount-badge-clickable', function(e) {
                e.preventDefault();
                const studentId = $(this).data('student-id');
                const parentPhone = $(this).data('parent-phone');

                // Show modal with clean rendering
                $('#sm-family-members-modal').css('display', 'block');

                // Load family members via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'sm_get_family_members',
                        parent_phone: parentPhone,
                        current_student_id: studentId,
                        nonce: '<?php echo wp_create_nonce( 'sm_family_members_nonce' ); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.members) {
                            let html = '';
                            response.data.members.forEach(function(member) {
                                html += '<div class="sm-family-member">';
                                html += '<h3>' + member.name + '</h3>';
                                html += '<div class="sm-family-member-info">✉️ ' + member.email + '</div>';
                                html += '<div class="sm-family-member-info">📞 ' + member.phone + '</div>';
                                html += '<div class="sm-family-member-info">📚 ' + member.level_name + '</div>';
                                html += '<div class="sm-family-member-actions">';
                                html += '<a href="?page=school-management-students&action=edit&student_id=' + member.id + '" class="button button-small">' + smFamilyStrings.editStudent + '</a>';
                                html += '</div>';
                                html += '</div>';
                            });
                            $('#sm-family-members-list').html(html);
                        } else {
                            $('#sm-family-members-list').html('<p><?php esc_html_e( 'No family members found.', 'CTADZ-school-management' ); ?></p>');
                        }
                    },
                    error: function() {
                        $('#sm-family-members-list').html('<p style="color: #d63638;"><?php esc_html_e( 'Error loading family members.', 'CTADZ-school-management' ); ?></p>');
                    }
                });
            });

            // Close modal - click on close button or outside the content
            $('#sm-family-modal-close').on('click', function() {
                $('#sm-family-members-modal').css('display', 'none');
            });

            // Close when clicking outside the modal content
            $('#sm-family-members-modal').on('click', function(e) {
                if ($(e.target).is('#sm-family-members-modal')) {
                    $('#sm-family-members-modal').css('display', 'none');
                }
            });

            // Close on ESC key
            $(document).on('keyup', function(e) {
                if (e.key === 'Escape') {
                    $('#sm-family-members-modal').css('display', 'none');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render student form (add/edit)
     */
    private static function render_student_form( $student = null ) {
        global $wpdb;
        $is_edit = ! empty( $student );
        
        // Get active levels
        $levels = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_levels WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );
        
        // Pre-fill form with POST data if validation failed or after family confirmation
        $form_data = [];
        if ( isset( $_POST['sm_save_student'] ) ) {
            $form_data = [
                'first_name'   => sanitize_text_field( $_POST['first_name'] ?? '' ),
                'last_name'    => sanitize_text_field( $_POST['last_name'] ?? '' ),
                'email'        => sanitize_email( $_POST['email'] ?? '' ),
                'phone'        => sanitize_text_field( $_POST['phone'] ?? '' ),
                'dob'          => sanitize_text_field( $_POST['dob'] ?? '' ),
                'level_id'     => intval( $_POST['level_id'] ?? 0 ),
                'picture'      => esc_url_raw( $_POST['picture'] ?? '' ),
                'blood_type'   => sanitize_text_field( $_POST['blood_type'] ?? '' ),
                'parent_name'  => sanitize_text_field( $_POST['parent_name'] ?? '' ),
                'parent_phone' => sanitize_text_field( $_POST['parent_phone'] ?? '' ),
                'parent_email' => sanitize_email( $_POST['parent_email'] ?? '' ),
            ];
        } elseif ( $student ) {
            // Split existing name into last_name and first_name (stored as "LastName FirstName")
            $name_parts = explode( ' ', $student->name, 2 );
            $last_name = $name_parts[0] ?? '';
            $first_name = $name_parts[1] ?? '';

            $form_data = [
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'email'        => $student->email,
                'phone'        => $student->phone,
                'dob'          => $student->dob,
                'level_id'     => $student->level_id,
                'picture'      => $student->picture,
                'blood_type'   => $student->blood_type,
                'parent_name'  => $student->parent_name ?? '',
                'parent_phone' => $student->parent_phone ?? '',
                'parent_email' => $student->parent_email ?? '',
            ];
        }
        
        ?>
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-students" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Students', 'CTADZ-school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Student', 'CTADZ-school-management' ) : esc_html__( 'Add New Student', 'CTADZ-school-management' ); ?>
            </h2>
        </div>

        <form method="post" novalidate id="sm-student-form">
            <?php wp_nonce_field( 'sm_save_student_action', 'sm_save_student_nonce' ); ?>
            <input type="hidden" name="student_id" value="<?php echo esc_attr( $student->id ?? '' ); ?>" />
            <input type="hidden" name="family_discount_confirmed" id="family_discount_confirmed" value="" />

            <table class="form-table">
                <tr>
                    <td colspan="2" style="position: relative;">
                        <!-- Picture top-right -->
                        <div id="sm_student_picture_box">
                            <?php if ( ! empty( $form_data['picture'] ) ) : ?>
                                <img id="sm_student_picture_preview" src="<?php echo esc_url( $form_data['picture'] ); ?>" alt="<?php esc_attr_e( 'Student Picture', 'CTADZ-school-management' ); ?>" />
                            <?php else : ?>
                                <span><?php esc_html_e( 'Click to upload', 'CTADZ-school-management' ); ?></span>
                                <img id="sm_student_picture_preview" src="" style="display:none;" alt="<?php esc_attr_e( 'Student Picture', 'CTADZ-school-management' ); ?>" />
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="picture" id="sm_student_picture" value="<?php echo esc_attr( $form_data['picture'] ?? '' ); ?>" />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_last_name"><?php esc_html_e( 'Last Name', 'CTADZ-school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="student_last_name" name="last_name" value="<?php echo esc_attr( $form_data['last_name'] ?? '' ); ?>" class="regular-text" required maxlength="50" />
                        <p class="description"><?php esc_html_e( 'Student\'s last name (family name).', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_first_name"><?php esc_html_e( 'First Name', 'CTADZ-school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="student_first_name" name="first_name" value="<?php echo esc_attr( $form_data['first_name'] ?? '' ); ?>" class="regular-text" required maxlength="50" />
                        <p class="description"><?php esc_html_e( 'Student\'s first name (given name). Each student must have a unique full name.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_email"><?php esc_html_e( 'Email Address', 'CTADZ-school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="email" id="student_email" name="email" value="<?php echo esc_attr( $form_data['email'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'Used for communications and must be unique.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_phone"><?php esc_html_e( 'Phone Number', 'CTADZ-school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="student_phone" name="phone" value="<?php echo esc_attr( $form_data['phone'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'Contact number for emergencies and notifications.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_dob"><?php esc_html_e( 'Date of Birth', 'CTADZ-school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <?php
                        // Convert stored Y-m-d format to display format dd-mm-yyyy
                        $dob_value = $form_data['dob'] ?? '';
                        if ( ! empty( $dob_value ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dob_value ) ) {
                            $dob_parts = explode( '-', $dob_value );
                            $dob_value = $dob_parts[2] . '-' . $dob_parts[1] . '-' . $dob_parts[0]; // dd-mm-yyyy
                        }
                        ?>
                        <input type="text" id="student_dob" name="dob" value="<?php echo esc_attr( $dob_value ); ?>" class="sm-datepicker" required placeholder="<?php esc_attr_e( 'dd-mm-yyyy', 'CTADZ-school-management' ); ?>" autocomplete="off" />
                        <p class="description"><?php esc_html_e( 'Required for age verification and records.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_level"><?php esc_html_e( 'Level', 'CTADZ-school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <div class="sm-dropdown-with-refresh">
                            <select id="student_level" name="level_id" required>
                                <option value=""><?php esc_html_e( 'Select Level', 'CTADZ-school-management' ); ?></option>
                                <?php foreach ( $levels as $level ) : ?>
                                    <option value="<?php echo intval( $level->id ); ?>" <?php selected( $form_data['level_id'] ?? 0, $level->id ); ?>>
                                        <?php echo esc_html( $level->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="button button-small sm-refresh-dropdown"
                                    data-entity="levels"
                                    data-target="student_level"
                                    title="<?php esc_attr_e( 'Refresh list', 'CTADZ-school-management' ); ?>">
                                <span class="dashicons dashicons-update"></span>
                            </button>
                        </div>
                        <p class="description">
                            <?php esc_html_e( 'Choose the appropriate skill level for course assignment.', 'CTADZ-school-management' ); ?>
                            <a href="?page=school-management-levels" target="_blank"><?php esc_html_e( 'Manage levels', 'CTADZ-school-management' ); ?></a>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_blood_type"><?php esc_html_e( 'Blood Type', 'CTADZ-school-management' ); ?> <span class="description">(<?php esc_html_e( 'optional', 'CTADZ-school-management' ); ?>)</span></label>
                    </th>
                    <td>
                        <select name="blood_type" id="student_blood_type">
                            <option value=""><?php esc_html_e( 'Select Blood Type', 'CTADZ-school-management' ); ?></option>
                            <?php
                            $types = [ 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-' ];
                            $selected_blood = $form_data['blood_type'] ?? '';
                            foreach ( $types as $type ) {
                                echo '<option value="' . esc_attr( $type ) . '" ' . selected( $selected_blood, $type, false ) . '>' . esc_html( $type ) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Blood type information for emergency situations.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <td colspan="2"><hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;"></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <h3 style="margin: 0; font-size: 14px; font-weight: 600;"><?php esc_html_e( 'Parent/Guardian Information', 'CTADZ-school-management' ); ?></h3>
                        <p class="description"><?php esc_html_e( 'Used for emergency contact and family discount calculation.', 'CTADZ-school-management' ); ?></p>

                        <?php
                        // Show current discount status for existing students
                        if ( $is_edit && ! empty( $student->parent_phone ) ) {
                            $discount_info = SM_Family_Discount::calculate_discount_for_student( $student->id );
                            if ( $discount_info['percentage'] > 0 ) :
                                // Get family members
                                $family_members = SM_Family_Discount::get_family_members( $student->parent_phone );
                                // Filter out current student
                                $siblings = array_filter( $family_members, function( $member ) use ( $student ) {
                                    return $member->id != $student->id;
                                } );
                        ?>
                            <div class="sm-discount-info-box">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                    <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                                        <span class="dashicons dashicons-yes-alt sm-discount-icon"></span>
                                        <div style="flex: 1;">
                                            <strong class="sm-discount-title"><?php esc_html_e( 'Family Discount Active', 'CTADZ-school-management' ); ?></strong>
                                            <p class="sm-discount-details">
                                                <?php
                                                printf(
                                                    esc_html__( '%s%% discount applied (%d students in family with active enrollments)', 'CTADZ-school-management' ),
                                                    number_format( $discount_info['percentage'], 1 ),
                                                    $discount_info['family_count']
                                                );
                                                ?>
                                            </p>
                                            <?php if ( ! empty( $siblings ) ) : ?>
                                                <p class="sm-discount-details" style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(70, 180, 80, 0.2);">
                                                    <strong><?php esc_html_e( 'Siblings:', 'CTADZ-school-management' ); ?></strong><br>
                                                    <?php
                                                    $sibling_links = array();
                                                    foreach ( $siblings as $sibling ) {
                                                        $sibling_links[] = sprintf(
                                                            '<a href="?page=school-management-students&action=edit&student_id=%d" style="text-decoration: underline;">%s</a>',
                                                            intval( $sibling->id ),
                                                            esc_html( $sibling->name )
                                                        );
                                                    }
                                                    echo implode( ', ', $sibling_links );
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="<?php echo wp_nonce_url( '?page=school-management-students&action=edit&student_id=' . intval( $student->id ) . '&recalculate_discount=' . intval( $student->id ), 'sm_recalculate_discount_' . intval( $student->id ) ); ?>"
                                       class="button button-small"
                                       style="white-space: nowrap;">
                                        <span class="dashicons dashicons-update" style="vertical-align: middle; font-size: 14px;"></span>
                                        <?php esc_html_e( 'Recalculate', 'CTADZ-school-management' ); ?>
                                    </a>
                                </div>
                            </div>
                            <style>
                                /* Light mode styles */
                                .sm-discount-info-box {
                                    background: #e7f7ed;
                                    border-left: 4px solid #46b450;
                                    padding: 12px 15px;
                                    margin-top: 10px;
                                }
                                .sm-discount-icon {
                                    color: #46b450;
                                    font-size: 20px;
                                }
                                .sm-discount-title {
                                    color: #2c5f2d;
                                }
                                .sm-discount-details {
                                    margin: 5px 0 0 0;
                                    color: #555;
                                    font-size: 13px;
                                }

                                /* Dark mode styles */
                                @media (prefers-color-scheme: dark) {
                                    .sm-discount-info-box {
                                        background: rgba(70, 180, 80, 0.15);
                                        border-left: 4px solid #68de7c;
                                    }
                                    .sm-discount-icon {
                                        color: #68de7c;
                                    }
                                    .sm-discount-title {
                                        color: #a7f3b3;
                                    }
                                    .sm-discount-details {
                                        color: #c3c4c7;
                                    }
                                }
                            </style>
                        <?php
                            endif;
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="parent_name"><?php esc_html_e( 'Parent/Guardian Name', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="parent_name" name="parent_name" value="<?php echo esc_attr( $form_data['parent_name'] ?? '' ); ?>" class="regular-text" maxlength="100" />
                        <p class="description"><?php esc_html_e( 'Full name of parent or guardian.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="parent_phone"><?php esc_html_e( 'Parent/Guardian Phone', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="parent_phone" name="parent_phone" value="<?php echo esc_attr( $form_data['parent_phone'] ?? '' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Used to identify family members for family discounts. Students with the same parent phone number will be grouped as siblings.', 'CTADZ-school-management' ); ?></p>
                        <div id="sm-family-warning" class="sm-family-warning-box" style="display:none;">
                            <p class="sm-family-warning-title">⚠️ <?php esc_html_e( 'Family Members Detected', 'CTADZ-school-management' ); ?></p>
                            <div id="sm-family-list" class="sm-family-warning-content"></div>
                        </div>
                        <?php
                        // Show parent name validation warning for existing students
                        if ( $is_edit && ! empty( $student->parent_phone ) && ! empty( $student->parent_name ) ) {
                            $family_members = SM_Family_Discount::get_family_members( $student->parent_phone );
                            $name_mismatches = array();

                            foreach ( $family_members as $member ) {
                                if ( $member->id != $student->id && ! empty( $member->parent_name ) ) {
                                    $normalized_current = strtolower( trim( $student->parent_name ) );
                                    $normalized_other = strtolower( trim( $member->parent_name ) );

                                    if ( $normalized_current !== $normalized_other ) {
                                        $name_mismatches[] = sprintf(
                                            '%s (%s)',
                                            esc_html( $member->name ),
                                            esc_html( $member->parent_name )
                                        );
                                    }
                                }
                            }

                            if ( ! empty( $name_mismatches ) ) :
                        ?>
                            <div class="sm-parent-name-warning">
                                <div style="display: flex; align-items: flex-start; gap: 10px;">
                                    <span class="dashicons dashicons-warning sm-warning-icon"></span>
                                    <div style="flex: 1;">
                                        <strong class="sm-warning-title"><?php esc_html_e( 'Parent Name Mismatch Detected', 'CTADZ-school-management' ); ?></strong>
                                        <p class="sm-warning-text">
                                            <?php
                                            printf(
                                                esc_html__( 'This student\'s parent name (%s) differs from siblings with the same phone number:', 'CTADZ-school-management' ),
                                                '<strong>' . esc_html( $student->parent_name ) . '</strong>'
                                            );
                                            ?>
                                        </p>
                                        <ul class="sm-warning-list">
                                            <?php foreach ( $name_mismatches as $mismatch ) : ?>
                                                <li><?php echo $mismatch; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <p class="sm-warning-note">
                                            <?php esc_html_e( 'Please verify and correct if this is a data entry error.', 'CTADZ-school-management' ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <style>
                                /* Parent name warning box - Light mode */
                                .sm-parent-name-warning {
                                    background: #fff3cd;
                                    border-left: 4px solid #ffc107;
                                    padding: 12px 15px;
                                    margin-top: 10px;
                                }
                                .sm-warning-icon {
                                    color: #856404;
                                    font-size: 20px;
                                    margin-top: 2px;
                                }
                                .sm-warning-title {
                                    color: #856404;
                                }
                                .sm-warning-text {
                                    margin: 5px 0 0 0;
                                    color: #856404;
                                    font-size: 13px;
                                }
                                .sm-warning-list {
                                    margin: 5px 0 0 20px;
                                    color: #856404;
                                    font-size: 13px;
                                }
                                .sm-warning-note {
                                    margin: 5px 0 0 0;
                                    color: #856404;
                                    font-size: 12px;
                                    font-style: italic;
                                }

                                /* Dark mode support for parent name warning */
                                @media (prefers-color-scheme: dark) {
                                    .sm-parent-name-warning {
                                        background: rgba(255, 193, 7, 0.15);
                                        border-left: 4px solid #ffc107;
                                    }
                                    .sm-warning-icon,
                                    .sm-warning-title,
                                    .sm-warning-text,
                                    .sm-warning-list,
                                    .sm-warning-note {
                                        color: #ffc107;
                                    }
                                }
                            </style>
                        <?php
                            endif;
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="parent_email"><?php esc_html_e( 'Parent/Guardian Email', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="email" id="parent_email" name="parent_email" value="<?php echo esc_attr( $form_data['parent_email'] ?? '' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Email address for parent communications.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <?php if ( ! $is_edit ) : // Only show enrollment option for new students ?>
                <tr>
                    <td colspan="2"><hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;"></td>
                </tr>
                <?php endif; ?>
            </table>

            <p class="submit">
                <?php submit_button( 
                    $is_edit ? __( 'Update Student', 'CTADZ-school-management' ) : __( 'Add Student', 'CTADZ-school-management' ), 
                    'primary', 
                    'sm_save_student', 
                    false 
                ); ?>
                <a href="?page=school-management-students" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'CTADZ-school-management' ); ?></a>
            </p>
            
            <p class="description">
                <span style="color: #d63638;">*</span> <?php esc_html_e( 'Required fields', 'CTADZ-school-management' ); ?>
            </p>
        </form>
        <?php
    }

    /**
     * AJAX handler to check for family members with same parent phone
     */
    public static function ajax_check_family_phone() {
        global $wpdb;

        $phone = sanitize_text_field( $_POST['phone'] ?? '' );
        $current_student_id = intval( $_POST['student_id'] ?? 0 );

        if ( empty( $phone ) ) {
            wp_send_json_error( [ 'message' => __( 'Phone number required', 'CTADZ-school-management' ) ] );
        }

        $students_table = $wpdb->prefix . 'sm_students';
        $normalized = SM_Family_Discount::normalize_phone( $phone );

        if ( empty( $normalized ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid phone number', 'CTADZ-school-management' ) ] );
        }

        // Find students with the same parent phone (using normalized comparison)
        $query = "SELECT id, name, student_code FROM $students_table
                  WHERE REGEXP_REPLACE(parent_phone, '[^0-9]', '') = %s";
        $params = [ $normalized ];

        // Exclude current student if editing
        if ( $current_student_id > 0 ) {
            $query .= " AND id != %d";
            $params[] = $current_student_id;
        }

        $family_members = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

        // Calculate what discount would apply
        $discount_info = null;
        if ( SM_Family_Discount::is_enabled() && count( $family_members ) > 0 ) {
            // Total family members will be existing + 1 (current student)
            $total_family_count = count( $family_members ) + 1;
            $tiers = SM_Family_Discount::get_discount_tiers();

            // Find applicable tier
            foreach ( $tiers as $tier ) {
                if ( $total_family_count >= intval( $tier['students'] ) ) {
                    $discount_info = sprintf(
                        __( '%s%% discount for %d students', 'CTADZ-school-management' ),
                        number_format( floatval( $tier['discount'] ), 1 ),
                        $total_family_count
                    );
                    break;
                }
            }
        }

        wp_send_json_success( [
            'family_members' => $family_members,
            'count' => count( $family_members ),
            'discount_info' => $discount_info
        ] );
    }

    /**
     * Render family discount confirmation modal (output once per page)
     */
    private static function render_family_discount_modal() {
        ?>
        <!-- Custom Modal for Family Discount Confirmation -->
        <div id="sm-family-confirm-modal" style="display: none;">
            <div class="sm-modal-overlay"></div>
            <div class="sm-modal-container">
                <div class="sm-modal-header">
                    <h2>⚠️ <?php esc_html_e( 'Family Discount Confirmation', 'CTADZ-school-management' ); ?></h2>
                </div>
                <div class="sm-modal-content" id="sm-modal-message"></div>
                <div class="sm-modal-footer">
                    <button type="button" class="button button-primary button-large" id="sm-confirm-yes">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e( 'Yes, Apply Discount', 'CTADZ-school-management' ); ?>
                    </button>
                    <button type="button" class="button button-large" id="sm-confirm-no">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php esc_html_e( 'No, Change Phone Number', 'CTADZ-school-management' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <style>
        .sm-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999999;
            backdrop-filter: blur(2px);
        }

        .sm-modal-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 1000000;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow: auto;
        }

        .sm-modal-header {
            background: #f0ad4e;
            color: #fff;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }

        .sm-modal-header h2 {
            margin: 0;
            color: #fff !important;
            font-size: 18px;
        }

        .sm-modal-content {
            padding: 25px;
            line-height: 1.6;
            font-size: 15px;
        }

        .sm-modal-content p {
            margin: 0 0 15px 0;
        }

        .sm-modal-footer {
            padding: 20px;
            background: #f5f5f5;
            border-radius: 0 0 8px 8px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .sm-family-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #8e44ad;
        }

        .sm-family-list ul {
            margin: 10px 0;
            padding-left: 20px;
            list-style: none;
        }

        .sm-family-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .sm-family-list li:last-child {
            border-bottom: none;
        }

        .sm-discount-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #155724;
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .sm-modal-container {
                background: #1a1a1a;
                color: #e0e0e0;
            }

            .sm-modal-content {
                color: #e0e0e0;
            }

            .sm-modal-footer {
                background: #2a2a2a;
            }

            .sm-family-list {
                background: #2a2a2a;
                border-left-color: #8e44ad;
            }

            .sm-family-list li {
                border-bottom-color: #3a3a3a;
            }

            .sm-discount-box {
                background: #1a3d1a;
                border-left-color: #4caf50;
                color: #a5d6a7;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var familyConfirmed = false;
            var detectedFamilyInfo = null;

            // AJAX check for family members when phone number changes
            var ajaxTimer = null;
            $('#parent_phone').on('input', function() {
                clearTimeout(ajaxTimer);
                var phone = $(this).val().trim();
                var currentStudentId = $('input[name="student_id"]').val() || 0;

                if (phone.length >= 8) {
                    ajaxTimer = setTimeout(function() {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'sm_check_family_phone',
                                phone: phone,
                                student_id: currentStudentId
                            },
                            success: function(response) {
                                if (response.success && response.data.family_members && response.data.family_members.length > 0) {
                                    detectedFamilyInfo = response.data;
                                    let familyList = '<strong><?php esc_html_e( 'This phone number is associated with:', 'CTADZ-school-management' ); ?></strong><br>';
                                    response.data.family_members.forEach(function(member) {
                                        familyList += '• ' + member.name + ' (' + member.student_code + ')<br>';
                                    });
                                    familyList += '<br><em><?php esc_html_e( 'These students will be considered family members for discount purposes.', 'CTADZ-school-management' ); ?></em>';

                                    // Add discount info if available
                                    if (response.data.discount_info) {
                                        familyList += '<br><br><strong style="color: #28a745;"><?php esc_html_e( 'Applicable Discount:', 'CTADZ-school-management' ); ?> ' + response.data.discount_info + '</strong>';
                                    }

                                    $('#sm-family-list').html(familyList);
                                    $('#sm-family-warning').slideDown();
                                } else {
                                    detectedFamilyInfo = null;
                                    $('#sm-family-warning').slideUp();
                                }
                            }
                        });
                    }, 500);
                } else {
                    detectedFamilyInfo = null;
                    $('#sm-family-warning').slideUp();
                }
            });

            // Intercept form submission for family discount confirmation
            var studentFormSubmitted = false;
            var $studentForm = $('#sm-student-form');

            // Form only exists on add/edit pages, not on list page - silently exit if not found
            if ($studentForm.length === 0) {
                return;
            }

            $studentForm.on('submit', function(e) {
                // Check if family discount confirmation was already done
                var confirmValue = $('#family_discount_confirmed').val();

                if (confirmValue === 'yes') {
                    // Already confirmed, allow submission
                    return true;
                }

                if (!studentFormSubmitted && detectedFamilyInfo && detectedFamilyInfo.family_members && detectedFamilyInfo.family_members.length > 0 && !familyConfirmed) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Build modal content
                    var modalContent = '<p><strong><?php esc_html_e( 'This parent phone number matches existing students:', 'CTADZ-school-management' ); ?></strong></p>';

                    modalContent += '<div class="sm-family-list">';
                    modalContent += '<ul>';
                    detectedFamilyInfo.family_members.forEach(function(member) {
                        modalContent += '<li>👤 <strong>' + member.name + '</strong> (' + member.student_code + ')</li>';
                    });
                    modalContent += '</ul>';
                    modalContent += '<p><strong><?php esc_html_e( 'Parent Phone:', 'CTADZ-school-management' ); ?></strong> ' + $('#parent_phone').val() + '</p>';
                    modalContent += '</div>';

                    if (detectedFamilyInfo.discount_info) {
                        modalContent += '<div class="sm-discount-box">';
                        modalContent += '<strong>✓ <?php esc_html_e( 'Discount to Apply:', 'CTADZ-school-management' ); ?></strong><br>';
                        modalContent += detectedFamilyInfo.discount_info;
                        modalContent += '</div>';
                        modalContent += '<p><?php echo esc_js( __( 'This discount will be automatically applied to all family members current and future enrollments.', 'CTADZ-school-management' ) ); ?></p>';
                    }

                    modalContent += '<p style="margin-top: 20px;"><strong><?php esc_html_e( 'Are these students from the same family?', 'CTADZ-school-management' ); ?></strong></p>';

                    // Show modal
                    $('#sm-modal-message').html(modalContent);
                    $('#sm-family-confirm-modal').fadeIn(200);

                    return false;
                }
            });

            // Handle modal button clicks
            $(document).on('click', '#sm-confirm-yes', function(e) {
                e.preventDefault();
                console.log('=== DISCOUNT CONFIRMED ===');

                // Close modal
                $('#sm-family-confirm-modal').fadeOut(200);

                // Mark as confirmed so submit handler allows it
                familyConfirmed = true;
                studentFormSubmitted = true;

                // Submit form after modal closes
                setTimeout(function() {
                    // Set the hidden field value
                    var confirmField = document.getElementById('family_discount_confirmed');
                    if (confirmField) {
                        confirmField.value = 'yes';
                        console.log('Field value set to:', confirmField.value);
                    }

                    // Find and click the submit button instead of calling form.submit()
                    var submitButton = document.querySelector('#sm-student-form input[type="submit"][name="sm_save_student"]');
                    if (submitButton) {
                        console.log('Clicking submit button...');
                        // Remove jQuery event handlers to prevent modal from showing again
                        $('#sm-student-form').off('submit');
                        // Click the actual submit button
                        submitButton.click();
                    } else {
                        console.error('Submit button not found!');
                    }
                }, 300);
            });

            $('#sm-confirm-no').on('click', function() {
                $('#sm-family-confirm-modal').fadeOut(200);
                $('#parent_phone').focus();
            });

            // Close modal when clicking overlay
            $('.sm-modal-overlay').on('click', function() {
                $('#sm-family-confirm-modal').fadeOut(200);
                $('#parent_phone').focus();
            });
        });
        </script>
        <?php
    }

    /**
     * Render enrollment prompt modal
     */
    private static function render_enrollment_prompt_modal() {
        ?>
        <!-- Enrollment Prompt Modal -->
        <div id="sm-enrollment-prompt-modal" style="display: none;">
            <div class="sm-modal-overlay"></div>
            <div class="sm-modal-container">
                <div class="sm-modal-header" style="background: #2563eb;">
                    <h2>📚 <?php esc_html_e( 'Enroll Student in Course?', 'CTADZ-school-management' ); ?></h2>
                </div>
                <div class="sm-modal-content">
                    <p id="sm-enrollment-message"></p>
                    <p><?php esc_html_e( 'You can enroll the student in a course now, or do it later from the Financial Management menu.', 'CTADZ-school-management' ); ?></p>
                </div>
                <div class="sm-modal-footer">
                    <button type="button" class="button button-primary button-large" id="sm-enroll-yes">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e( 'Yes, Enroll Now', 'CTADZ-school-management' ); ?>
                    </button>
                    <button type="button" class="button button-large" id="sm-enroll-no">
                        <span class="dashicons dashicons-no-alt"></span>
                        <?php esc_html_e( 'No, Maybe Later', 'CTADZ-school-management' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <script>
        function showEnrollmentPrompt(studentName, studentId) {
            var message = '<?php echo esc_js( __( 'Do you want to enroll', 'CTADZ-school-management' ) ); ?> <strong>' + studentName + '</strong> <?php echo esc_js( __( 'in a course now?', 'CTADZ-school-management' ) ); ?>';
            document.getElementById('sm-enrollment-message').innerHTML = message;
            document.getElementById('sm-enrollment-prompt-modal').style.display = 'block';

            // Store student ID for later use
            window.enrollmentStudentId = studentId;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Yes button - redirect to enrollment form
            document.getElementById('sm-enroll-yes').addEventListener('click', function() {
                var studentId = window.enrollmentStudentId;
                var url = 'admin.php?page=school-management-enrollments';
                url += '&action=add';
                url += '&student_id=' + studentId;
                window.location.href = url;
            });

            // No button - redirect to students list
            document.getElementById('sm-enroll-no').addEventListener('click', function() {
                window.location.href = '?page=school-management-students';
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler to get family members
     */
    public static function ajax_get_family_members() {
        // Security check
        check_ajax_referer( 'sm_family_members_nonce', 'nonce' );

        $parent_phone = sanitize_text_field( $_POST['parent_phone'] ?? '' );
        $current_student_id = intval( $_POST['current_student_id'] ?? 0 );

        if ( empty( $parent_phone ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid parent phone.', 'CTADZ-school-management' ) ] );
        }

        // Get family members
        $family_members = SM_Family_Discount::get_family_members( $parent_phone );

        // Format the response
        $members_data = [];
        foreach ( $family_members as $member ) {
            // Skip current student
            if ( $member->id == $current_student_id ) {
                continue;
            }

            global $wpdb;
            $levels_table = $wpdb->prefix . 'sm_levels';
            $level = $wpdb->get_row( $wpdb->prepare(
                "SELECT name FROM $levels_table WHERE id = %d",
                $member->level_id
            ) );

            $members_data[] = [
                'id' => intval( $member->id ),
                'name' => esc_html( $member->name ),
                'email' => esc_html( $member->email ),
                'phone' => esc_html( $member->phone ),
                'level_name' => $level ? esc_html( $level->name ) : __( 'No Level', 'CTADZ-school-management' )
            ];
        }

        wp_send_json_success( [ 'members' => $members_data ] );
    }

    /**
     * Generate a unique student code in format STUYYYYxxxx
     *
     * @param int $student_id The student's database ID (used as fallback for uniqueness)
     * @return string The generated student code
     */
    private static function generate_student_code( $student_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_students';
        $year = date( 'Y' );

        // Find the highest existing code number for this year
        $last_code = $wpdb->get_var( $wpdb->prepare(
            "SELECT student_code FROM $table
             WHERE student_code LIKE %s
             ORDER BY student_code DESC
             LIMIT 1",
            'STU' . $year . '%'
        ) );

        if ( $last_code ) {
            // Extract the number part and increment
            $last_number = intval( substr( $last_code, 7 ) ); // After "STU" + 4-digit year
            $new_number = $last_number + 1;
        } else {
            // First student of this year
            $new_number = 1;
        }

        // Generate the code
        $student_code = sprintf( 'STU%s%04d', $year, $new_number );

        // Ensure uniqueness (in case of race condition)
        $attempts = 0;
        while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE student_code = %s", $student_code ) ) && $attempts < 100 ) {
            $new_number++;
            $student_code = sprintf( 'STU%s%04d', $year, $new_number );
            $attempts++;
        }

        return $student_code;
    }
}

// Instantiate class
new SM_Students_Page();

// Register AJAX handlers
add_action( 'wp_ajax_sm_check_family_phone', [ 'SM_Students_Page', 'ajax_check_family_phone' ] );
add_action( 'wp_ajax_sm_get_family_members', [ 'SM_Students_Page', 'ajax_get_family_members' ] );
