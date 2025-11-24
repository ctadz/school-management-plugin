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

        // Handle form submission
        if ( isset( $_POST['sm_save_student'] ) && check_admin_referer( 'sm_save_student_action', 'sm_save_student_nonce' ) ) {
            $validation_result = self::validate_student_data( $_POST );
            
            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];
                
                if ( ! empty( $_POST['student_id'] ) ) {
                    $updated = $wpdb->update( $table, $data, [ 'id' => intval( $_POST['student_id'] ) ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Student updated successfully.', 'CTADZ-school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-students"; }, 2000);</script>';
                    }
                } else {
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Student added successfully.', 'CTADZ-school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-students"; }, 2000);</script>';
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
        
        $name = sanitize_text_field( trim( $post_data['name'] ?? '' ) );
        $email = sanitize_email( trim( $post_data['email'] ?? '' ) );
        $phone = sanitize_text_field( trim( $post_data['phone'] ?? '' ) );
        $dob = sanitize_text_field( trim( $post_data['dob'] ?? '' ) );
        $level_id = intval( $post_data['level_id'] ?? 0 );
        $picture = esc_url_raw( trim( $post_data['picture'] ?? '' ) );
        $blood_type = sanitize_text_field( trim( $post_data['blood_type'] ?? '' ) );
        $student_id = intval( $post_data['student_id'] ?? 0 );

        // Required field validation
        if ( empty( $name ) ) {
            $errors[] = __( 'Student name is required.', 'CTADZ-school-management' );
        } elseif ( strlen( $name ) < 2 ) {
            $errors[] = __( 'Student name must be at least 2 characters long.', 'CTADZ-school-management' );
        } elseif ( strlen( $name ) > 100 ) {
            $errors[] = __( 'Student name cannot exceed 100 characters.', 'CTADZ-school-management' );
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

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'name'       => $name,
                    'email'      => $email,
                    'phone'      => $phone,
                    'dob'        => $dob,
                    'level_id'   => $level_id,
                    'picture'    => $picture,
                    'blood_type' => $blood_type ?: null,
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
        
        // Get sorting parameters
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'name';
        $order = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), [ 'ASC', 'DESC' ] ) ? strtoupper( $_GET['order'] ) : 'ASC';

        // Pagination setup
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        // Build WHERE clause for search
        $where_clause = '';
        if ( ! empty( $search ) ) {
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $where_clause = $wpdb->prepare( 
                "WHERE (s.name LIKE %s OR s.email LIKE %s OR s.phone LIKE %s)", 
                $search_term, 
                $search_term, 
                $search_term 
            );
        }

        $total_students = $wpdb->get_var( "SELECT COUNT(*) FROM $students_table s $where_clause" );
        $total_pages = ceil( $total_students / $per_page );

        // Validate and set ORDER BY clause
        $valid_columns = [
            'name' => 's.name',
            'email' => 's.email',
            'phone' => 's.phone',
            'level' => 'l.name',
            'enrollment_date' => 's.enrollment_date',
            'active_enrollments' => 'active_enrollments'
        ];
        $orderby_column = isset( $valid_columns[ $orderby ] ) ? $valid_columns[ $orderby ] : 's.name';
        $order_clause = "$orderby_column $order";

        // Get students with level names, enrollment count, and payment info
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
                   END) as total_outstanding
            FROM $students_table s
            LEFT JOIN $levels_table l ON s.level_id = l.id
            LEFT JOIN $enrollments_table e ON s.id = e.student_id
            LEFT JOIN $payment_schedules_table ps ON e.id = ps.enrollment_id
            $where_clause
            GROUP BY s.id
            ORDER BY $order_clause
            LIMIT %d OFFSET %d
        ";
        
        $students = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );

        // Helper function to generate sortable column URL
        $get_sort_url = function( $column ) use ( $orderby, $order, $search ) {
            $new_order = ( $orderby === $column && $order === 'ASC' ) ? 'DESC' : 'ASC';
            $url = add_query_arg( [
                'page' => 'school-management-students',
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
        </style>
        
        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php esc_html_e( 'Students List', 'CTADZ-school-management' ); ?></h2>
                <p class="description">
                    <?php 
                    if ( ! empty( $search ) ) {
                        printf( esc_html__( 'Showing %d students matching "%s"', 'CTADZ-school-management' ), $total_students, esc_html( $search ) );
                        echo ' <a href="?page=school-management-students" style="margin-left: 10px;">' . esc_html__( '[Clear search]', 'CTADZ-school-management' ) . '</a>';
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
                <input type="search" 
                       name="s" 
                       value="<?php echo esc_attr( $search ); ?>" 
                       placeholder="<?php esc_attr_e( 'Search students by name, email, or phone...', 'CTADZ-school-management' ); ?>"
                       style="width: 300px; margin-right: 5px;">
                <button type="submit" class="button"><?php esc_html_e( 'Search', 'CTADZ-school-management' ); ?></button>
                <?php if ( ! empty( $search ) ) : ?>
                    <a href="?page=school-management-students" class="button" style="margin-left: 5px;">
                        <?php esc_html_e( 'Clear', 'CTADZ-school-management' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ( $students ) : ?>
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
                        <th class="<?php echo $orderby === 'level' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'level' ); ?>">
                                <?php esc_html_e( 'Level', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'level' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'active_enrollments' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'active_enrollments' ); ?>">
                                <?php esc_html_e( 'Enrollments', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'active_enrollments' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable"><?php esc_html_e( 'Payment Status', 'CTADZ-school-management' ); ?></th>
                        <th class="non-sortable"><?php esc_html_e( 'Balance', 'CTADZ-school-management' ); ?></th>
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
                            <td>
                                <?php if ( $student->picture ) : ?>
                                    <img src="<?php echo esc_url( $student->picture ); ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" alt="<?php echo esc_attr( $student->name ); ?>" />
                                <?php else : ?>
                                    <div style="width:40px;height:40px;border-radius:50%;background:#ddd;display:flex;align-items:center;justify-content:center;font-size:10px;color:#666;">No Photo</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html( $student->name ); ?></strong></td>
                            <td><?php echo esc_html( $student->email ); ?></td>
                            <td><?php echo esc_html( $student->phone ); ?></td>
                            <td><span class="sm-level-badge"><?php echo esc_html( $student->level_name ?: '—' ); ?></span></td>
                            <td>
                                <?php if ( $active_enrollments > 0 ) : ?>
                                    <span style="color: #2271b1;">
                                        <strong><?php echo esc_html( $active_enrollments ); ?></strong>
                                        <?php echo esc_html( _n( 'course', 'courses', $active_enrollments, 'CTADZ-school-management' ) ); ?>
                                    </span>
                                <?php else : ?>
                                    <span style="color: #999;"><?php esc_html_e( 'None', 'CTADZ-school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="display: inline-flex; align-items: center; padding: 3px 8px; background: <?php echo esc_attr( $status_bg ); ?>; border-radius: 3px; font-size: 11px;">
                                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: <?php echo esc_attr( $status_color ); ?>; margin-right: 5px;"></span>
                                    <strong style="color: <?php echo esc_attr( $status_color ); ?>;"><?php echo esc_html( $status_label ); ?></strong>
                                </span>
                            </td>
                            <td>
                                <?php if ( $total_outstanding > 0 ) : ?>
                                    <strong style="color: <?php echo $overdue_amount > 0 ? '#d63638' : '#f0ad4e'; ?>;">
                                        <?php echo esc_html( number_format( $total_outstanding, 2 ) ); ?> DZD
                                    </strong>
                                <?php else : ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=school-management-students&action=edit&student_id=<?php echo intval( $student->id ); ?>" 
                                   class="button button-small" title="<?php esc_attr_e( 'Edit Student', 'CTADZ-school-management' ); ?>">
                                    <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                </a>
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
        <?php endif;
    }

    /**
     * Render student form (add/edit)
     */
    private static function render_student_form( $student = null ) {
        global $wpdb;
        $is_edit = ! empty( $student );
        
        // Get active levels
        $levels = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_levels WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );
        
        // Pre-fill form with POST data if validation failed
        $form_data = [];
        if ( isset( $_POST['sm_save_student'] ) ) {
            $form_data = [
                'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
                'email'      => sanitize_email( $_POST['email'] ?? '' ),
                'phone'      => sanitize_text_field( $_POST['phone'] ?? '' ),
                'dob'        => sanitize_text_field( $_POST['dob'] ?? '' ),
                'level_id'   => intval( $_POST['level_id'] ?? 0 ),
                'picture'    => esc_url_raw( $_POST['picture'] ?? '' ),
                'blood_type' => sanitize_text_field( $_POST['blood_type'] ?? '' ),
            ];
        } elseif ( $student ) {
            $form_data = [
                'name'       => $student->name,
                'email'      => $student->email,
                'phone'      => $student->phone,
                'dob'        => $student->dob,
                'level_id'   => $student->level_id,
                'picture'    => $student->picture,
                'blood_type' => $student->blood_type,
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

        <form method="post" novalidate>
            <?php wp_nonce_field( 'sm_save_student_action', 'sm_save_student_nonce' ); ?>
            <input type="hidden" name="student_id" value="<?php echo esc_attr( $student->id ?? '' ); ?>" />

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
                        <label for="student_name"><?php esc_html_e( 'Full Name', 'CTADZ-school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="student_name" name="name" value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>" class="regular-text" required maxlength="100" />
                        <p class="description"><?php esc_html_e( 'Each student must have a unique name.', 'CTADZ-school-management' ); ?></p>
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
                        <input type="date" id="student_dob" name="dob" value="<?php echo esc_attr( $form_data['dob'] ?? '' ); ?>" required max="<?php echo date( 'Y-m-d' ); ?>" />
                        <p class="description"><?php esc_html_e( 'Required for age verification and records.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_level"><?php esc_html_e( 'Level', 'CTADZ-school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="student_level" name="level_id" required>
                            <option value=""><?php esc_html_e( 'Select Level', 'CTADZ-school-management' ); ?></option>
                            <?php foreach ( $levels as $level ) : ?>
                                <option value="<?php echo intval( $level->id ); ?>" <?php selected( $form_data['level_id'] ?? 0, $level->id ); ?>>
                                    <?php echo esc_html( $level->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Choose the appropriate skill level for course assignment.', 'CTADZ-school-management' ); ?>
                            <a href="?page=school-management-levels" target="_blank"><?php esc_html_e( 'Manage levels', 'CTADZ-school-management' ); ?></a>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_blood_type"><?php esc_html_e( 'Blood Type', 'CTADZ-school-management' ); ?> <span class="description">(optional)</span></label>
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
}

// Instantiate class
new SM_Students_Page();
