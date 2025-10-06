<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Enrollments_Page {

    /**
     * Render the Enrollments page
     */
    public static function render_enrollments_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_enrollments';

        // Handle delete action
        if ( isset( $_GET['delete'] ) && check_admin_referer( 'sm_delete_enrollment_' . intval( $_GET['delete'] ) ) ) {
            $deleted = $wpdb->delete( $table, [ 'id' => intval( $_GET['delete'] ) ] );
            if ( $deleted ) {
                echo '<div class="updated notice"><p>' . esc_html__( 'Enrollment deleted successfully.', 'school-management' ) . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html__( 'Error deleting enrollment.', 'school-management' ) . '</p></div>';
            }
        }

        // Handle form submission
        if ( isset( $_POST['sm_save_enrollment'] ) && check_admin_referer( 'sm_save_enrollment_action', 'sm_save_enrollment_nonce' ) ) {
    
            // TEMPORARY TEST - bypass validation
            $data = [
                'student_id' => intval( $_POST['student_id'] ?? 0 ),
                'course_id' => intval( $_POST['course_id'] ?? 0 ),
                'enrollment_date' => sanitize_text_field( $_POST['enrollment_date'] ?? '' ),
                'start_date' => sanitize_text_field( $_POST['start_date'] ?? '' ),
                'end_date' => sanitize_text_field( $_POST['end_date'] ?? '' ) ?: null,
                'status' => sanitize_text_field( $_POST['status'] ?? 'active' ),
                'payment_status' => sanitize_text_field( $_POST['payment_status'] ?? 'unpaid' ),
                'notes' => sanitize_textarea_field( $_POST['notes'] ?? '' ),
            ];
    
            $inserted = $wpdb->insert( $table, $data );
    
            if ( $inserted ) {
                echo '<div class="updated notice"><p>SUCCESS! Enrollment created. ID: ' . $wpdb->insert_id . '</p></div>';
            } else {
                echo '<div class="error notice"><p>DATABASE ERROR: ' . $wpdb->last_error . '</p></div>';
            }
        }

        // Determine view
        $action = $_GET['action'] ?? 'list';
        $enrollment = null;

        if ( $action === 'edit' && isset( $_GET['enrollment_id'] ) ) {
            $enrollment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['enrollment_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Enrollments', 'school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'add':
                    self::render_enrollment_form( null );
                    break;
                case 'edit':
                    self::render_enrollment_form( $enrollment );
                    break;
                default:
                    self::render_enrollments_list();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Validate enrollment data
     */
    private static function validate_enrollment_data( $post_data ) {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';
        $levels_table = $wpdb->prefix . 'sm_levels';
        
        $errors = [];
        
        $student_id = intval( $post_data['student_id'] ?? 0 );
        $course_id = intval( $post_data['course_id'] ?? 0 );
        $enrollment_date = sanitize_text_field( trim( $post_data['enrollment_date'] ?? '' ) );
        $start_date = sanitize_text_field( trim( $post_data['start_date'] ?? '' ) );
        $end_date = sanitize_text_field( trim( $post_data['end_date'] ?? '' ) );
        $status = sanitize_text_field( trim( $post_data['status'] ?? 'active' ) );
        $payment_status = sanitize_text_field( trim( $post_data['payment_status'] ?? 'unpaid' ) );
        $notes = sanitize_textarea_field( trim( $post_data['notes'] ?? '' ) );
        $enrollment_id = intval( $post_data['enrollment_id'] ?? 0 );

        // Required field validation
        if ( $student_id <= 0 ) {
            $errors[] = __( 'Please select a student.', 'school-management' );
        }

        if ( $course_id <= 0 ) {
            $errors[] = __( 'Please select a course.', 'school-management' );
        }

        if ( empty( $enrollment_date ) ) {
            $errors[] = __( 'Enrollment date is required.', 'school-management' );
        }

        if ( empty( $start_date ) ) {
            $errors[] = __( 'Start date is required.', 'school-management' );
        }

        // Validate dates
        if ( ! empty( $start_date ) && ! empty( $enrollment_date ) && strtotime( $start_date ) < strtotime( $enrollment_date ) ) {
            $errors[] = __( 'Start date cannot be before enrollment date.', 'school-management' );
        }

        if ( ! empty( $end_date ) && ! empty( $start_date ) && strtotime( $end_date ) < strtotime( $start_date ) ) {
            $errors[] = __( 'End date cannot be before start date.', 'school-management' );
        }

        // Check for duplicate enrollment
        if ( $student_id > 0 && $course_id > 0 ) {
            $duplicate_query = "SELECT id FROM $enrollments_table WHERE student_id = %d AND course_id = %d";
            $params = [ $student_id, $course_id ];
            
            if ( $enrollment_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $params[] = $enrollment_id;
            }
            
            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $params ) );
            if ( $duplicate ) {
                $errors[] = __( 'This student is already enrolled in this course.', 'school-management' );
            }
        }

        // Check level prerequisite (student level must match or be below course level)
        if ( $student_id > 0 && $course_id > 0 ) {
            $student = $wpdb->get_row( $wpdb->prepare( "SELECT level_id FROM $students_table WHERE id = %d", $student_id ) );
            $course = $wpdb->get_row( $wpdb->prepare( "SELECT level_id FROM $courses_table WHERE id = %d", $course_id ) );
            
            if ( $student && $course ) {
                $student_level_order = $wpdb->get_var( $wpdb->prepare( "SELECT sort_order FROM $levels_table WHERE id = %d", $student->level_id ) );
                $course_level_order = $wpdb->get_var( $wpdb->prepare( "SELECT sort_order FROM $levels_table WHERE id = %d", $course->level_id ) );
                
                if ( $student_level_order > $course_level_order ) {
                    $errors[] = __( 'Student level is too advanced for this course. Students can only enroll in courses at their level or above.', 'school-management' );
                }
            }
        }

        // Check course capacity
        if ( $course_id > 0 && $enrollment_id == 0 ) { // Only check on new enrollments
            $course = $wpdb->get_row( $wpdb->prepare( "SELECT max_students FROM $courses_table WHERE id = %d", $course_id ) );
            
            if ( $course && $course->max_students > 0 ) {
                $current_enrollments = $wpdb->get_var( $wpdb->prepare( 
                    "SELECT COUNT(*) FROM $enrollments_table WHERE course_id = %d AND status IN ('active', 'completed')", 
                    $course_id 
                ) );
                
                if ( $current_enrollments >= $course->max_students ) {
                    $errors[] = sprintf( __( 'This course is full. Maximum capacity: %d students.', 'school-management' ), $course->max_students );
                }
            }
        }

        // Validate status
        $valid_statuses = [ 'active', 'completed', 'dropped', 'suspended' ];
        if ( ! in_array( $status, $valid_statuses ) ) {
            $errors[] = __( 'Invalid enrollment status.', 'school-management' );
        }

        // Validate payment status
        $valid_payment_statuses = [ 'paid', 'unpaid', 'partial', 'overdue' ];
        if ( ! in_array( $payment_status, $valid_payment_statuses ) ) {
            $errors[] = __( 'Invalid payment status.', 'school-management' );
        }

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'student_id' => $student_id,
                    'course_id' => $course_id,
                    'enrollment_date' => $enrollment_date,
                    'start_date' => $start_date,
                    'end_date' => $end_date ?: null,
                    'status' => $status,
                    'payment_status' => $payment_status,
                    'notes' => $notes,
                ]
            ];
        }

        return [ 'success' => false, 'errors' => $errors ];
    }

    /**
     * Render enrollments list
     */
    private static function render_enrollments_list() {
        global $wpdb;
        $enrollments_table = $wpdb->prefix . 'sm_enrollments';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';

        // Pagination
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        $total_enrollments = $wpdb->get_var( "SELECT COUNT(*) FROM $enrollments_table" );
        $total_pages = ceil( $total_enrollments / $per_page );

        // Get enrollments with student and course names
        $enrollments = $wpdb->get_results( $wpdb->prepare( 
            "SELECT e.*, s.name as student_name, c.name as course_name 
             FROM $enrollments_table e 
             LEFT JOIN $students_table s ON e.student_id = s.id 
             LEFT JOIN $courses_table c ON e.course_id = c.id 
             ORDER BY e.enrollment_date DESC 
             LIMIT %d OFFSET %d", 
            $per_page, 
            $offset 
        ) );

        ?>
        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php esc_html_e( 'Enrollments List', 'school-management' ); ?></h2>
                <p class="description"><?php printf( esc_html__( 'Total: %d enrollments', 'school-management' ), $total_enrollments ); ?></p>
            </div>
            <div>
                <a href="?page=school-management-enrollments&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'New Enrollment', 'school-management' ); ?>
                </a>
            </div>
        </div>

        <?php if ( $enrollments ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Student', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Course', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Enrolled', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Start Date', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Payment', 'school-management' ); ?></th>
                        <th style="width: 150px;"><?php esc_html_e( 'Actions', 'school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $enrollments as $enrollment ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $enrollment->student_name ?: '—' ); ?></strong></td>
                            <td><?php echo esc_html( $enrollment->course_name ?: '—' ); ?></td>
                            <td><?php echo esc_html( date( 'M j, Y', strtotime( $enrollment->enrollment_date ) ) ); ?></td>
                            <td><?php echo esc_html( date( 'M j, Y', strtotime( $enrollment->start_date ) ) ); ?></td>
                            <td>
                                <?php
                                $status_colors = [
                                    'active' => '#46b450',
                                    'completed' => '#00a0d2',
                                    'dropped' => '#dc3232',
                                    'suspended' => '#f0ad4e'
                                ];
                                $color = $status_colors[ $enrollment->status ] ?? '#666';
                                $status_labels = [
                                    'active' => __( 'Active', 'school-management' ),
                                    'completed' => __( 'Completed', 'school-management' ),
                                    'dropped' => __( 'Dropped', 'school-management' ),
                                    'suspended' => __( 'Suspended', 'school-management' )
                                ];
                                $label = $status_labels[ $enrollment->status ] ?? $enrollment->status;
                                ?>
                                <span style="color: <?php echo esc_attr( $color ); ?>;">● <?php echo esc_html( $label ); ?></span>
                            </td>
                            <td>
                                <?php
                                $payment_colors = [
                                    'paid' => '#46b450',
                                    'unpaid' => '#dc3232',
                                    'partial' => '#f0ad4e',
                                    'overdue' => '#d63638'
                                ];
                                $p_color = $payment_colors[ $enrollment->payment_status ] ?? '#666';
                                $payment_labels = [
                                    'paid' => __( 'Paid', 'school-management' ),
                                    'unpaid' => __( 'Unpaid', 'school-management' ),
                                    'partial' => __( 'Partial', 'school-management' ),
                                    'overdue' => __( 'Overdue', 'school-management' )
                                ];
                                $p_label = $payment_labels[ $enrollment->payment_status ] ?? $enrollment->payment_status;
                                ?>
                                <span style="color: <?php echo esc_attr( $p_color ); ?>;">● <?php echo esc_html( $p_label ); ?></span>
                            </td>
                            <td>
                                <a href="?page=school-management-enrollments&action=edit&enrollment_id=<?php echo intval( $enrollment->id ); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                </a>
                                <?php
                                $delete_url = wp_nonce_url( 
                                    '?page=school-management-enrollments&delete=' . intval( $enrollment->id ), 
                                    'sm_delete_enrollment_' . intval( $enrollment->id ) 
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this enrollment?', 'school-management' ) ); ?>')">
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
                    'prev_text' => __( '« Previous', 'school-management' ),
                    'next_text' => __( 'Next »', 'school-management' ),
                    'total' => $total_pages,
                    'current' => $current_page,
                ];
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                echo paginate_links( $pagination_args );
                echo '</div></div>';
            }
            ?>

        <?php else : ?>
            <div class="sm-empty-state" style="text-align: center; padding: 60px 20px; background: #fafafa; border: 1px dashed #ddd; border-radius: 4px;">
                <span class="dashicons dashicons-welcome-learn-more" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></span>
                <h3><?php esc_html_e( 'No Enrollments Yet', 'school-management' ); ?></h3>
                <p><?php esc_html_e( 'Start enrolling students in courses.', 'school-management' ); ?></p>
                <a href="?page=school-management-enrollments&action=add" class="button button-primary">
                    <?php esc_html_e( 'Create First Enrollment', 'school-management' ); ?>
                </a>
            </div>
        <?php endif;
    }

    /**
     * Render enrollment form
     */
    private static function render_enrollment_form( $enrollment = null ) {
        global $wpdb;
        $is_edit = ! empty( $enrollment );
        
        // Get students and courses
        $students = $wpdb->get_results( "SELECT id, name, level_id FROM {$wpdb->prefix}sm_students ORDER BY name ASC" );
        $courses = $wpdb->get_results( "SELECT c.id, c.name, c.level_id, c.max_students, l.name as level_name 
                                        FROM {$wpdb->prefix}sm_courses c 
                                        LEFT JOIN {$wpdb->prefix}sm_levels l ON c.level_id = l.id 
                                        WHERE c.is_active = 1 
                                        ORDER BY c.name ASC" );
        
        $form_data = [];
        if ( isset( $_POST['sm_save_enrollment'] ) ) {
            $form_data = [
                'student_id' => intval( $_POST['student_id'] ?? 0 ),
                'course_id' => intval( $_POST['course_id'] ?? 0 ),
                'enrollment_date' => sanitize_text_field( $_POST['enrollment_date'] ?? '' ),
                'start_date' => sanitize_text_field( $_POST['start_date'] ?? '' ),
                'end_date' => sanitize_text_field( $_POST['end_date'] ?? '' ),
                'status' => sanitize_text_field( $_POST['status'] ?? 'active' ),
                'payment_status' => sanitize_text_field( $_POST['payment_status'] ?? 'unpaid' ),
                'notes' => sanitize_textarea_field( $_POST['notes'] ?? '' ),
            ];
        } elseif ( $enrollment ) {
            $form_data = [
                'student_id' => $enrollment->student_id,
                'course_id' => $enrollment->course_id,
                'enrollment_date' => $enrollment->enrollment_date,
                'start_date' => $enrollment->start_date,
                'end_date' => $enrollment->end_date,
                'status' => $enrollment->status,
                'payment_status' => $enrollment->payment_status,
                'notes' => $enrollment->notes,
            ];
        }
        
        ?>
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-enrollments" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Enrollments', 'school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Enrollment', 'school-management' ) : esc_html__( 'New Enrollment', 'school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_enrollment_action', 'sm_save_enrollment_nonce' ); ?>
            <input type="hidden" name="enrollment_id" value="<?php echo esc_attr( $enrollment->id ?? '' ); ?>" />

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enrollment_student"><?php esc_html_e( 'Student', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="enrollment_student" name="student_id" required <?php echo $is_edit ? 'disabled' : ''; ?>>
                            <option value=""><?php esc_html_e( 'Select Student', 'school-management' ); ?></option>
                            <?php foreach ( $students as $student ) : ?>
                                <option value="<?php echo intval( $student->id ); ?>" <?php selected( $form_data['student_id'] ?? 0, $student->id ); ?>>
                                    <?php echo esc_html( $student->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ( $is_edit ) : ?>
                            <input type="hidden" name="student_id" value="<?php echo esc_attr( $form_data['student_id'] ?? '' ); ?>" />
                            <p class="description"><?php esc_html_e( 'Student cannot be changed after enrollment.', 'school-management' ); ?></p>
                        <?php else : ?>
                            <p class="description">
                                <a href="?page=school-management-students&action=add" target="_blank"><?php esc_html_e( 'Add new student', 'school-management' ); ?></a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="enrollment_course"><?php esc_html_e( 'Course', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="enrollment_course" name="course_id" required <?php echo $is_edit ? 'disabled' : ''; ?>>
                            <option value=""><?php esc_html_e( 'Select Course', 'school-management' ); ?></option>
                            <?php foreach ( $courses as $course ) : 
                                // Get current enrollment count
                                $current_count = $wpdb->get_var( $wpdb->prepare( 
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}sm_enrollments WHERE course_id = %d AND status IN ('active', 'completed')", 
                                    $course->id 
                                ) );
                                $spots_info = '';
                                if ( $course->max_students > 0 ) {
                                    $spots_left = $course->max_students - $current_count;
                                    $spots_info = " ($spots_left spots left)";
                                }
                            ?>
                                <option value="<?php echo intval( $course->id ); ?>" <?php selected( $form_data['course_id'] ?? 0, $course->id ); ?>>
                                    <?php echo esc_html( $course->name . ' - ' . $course->level_name . $spots_info ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ( $is_edit ) : ?>
                            <input type="hidden" name="course_id" value="<?php echo esc_attr( $form_data['course_id'] ?? '' ); ?>" />
                            <p class="description"><?php esc_html_e( 'Course cannot be changed after enrollment.', 'school-management' ); ?></p>
                        <?php else : ?>
                            <p class="description">
                                <a href="?page=school-management-courses&action=add" target="_blank"><?php esc_html_e( 'Add new course', 'school-management' ); ?></a>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="enrollment_date"><?php esc_html_e( 'Enrollment Date', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="date" id="enrollment_date" name="enrollment_date" value="<?php echo esc_attr( $form_data['enrollment_date'] ?? date('Y-m-d') ); ?>" required />
                        <p class="description"><?php esc_html_e( 'Date when the student enrolled in the course.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="start_date"><?php esc_html_e( 'Start Date', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( $form_data['start_date'] ?? '' ); ?>" required />
                        <p class="description"><?php esc_html_e( 'Date when the student begins attending the course.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="end_date"><?php esc_html_e( 'End Date', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr( $form_data['end_date'] ?? '' ); ?>" />
                        <p class="description"><?php esc_html_e( 'Optional: Date when the student completes or leaves the course.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="enrollment_status"><?php esc_html_e( 'Enrollment Status', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="enrollment_status" name="status" required>
                            <option value="active" <?php selected( $form_data['status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Active', 'school-management' ); ?></option>
                            <option value="completed" <?php selected( $form_data['status'] ?? '', 'completed' ); ?>><?php esc_html_e( 'Completed', 'school-management' ); ?></option>
                            <option value="dropped" <?php selected( $form_data['status'] ?? '', 'dropped' ); ?>><?php esc_html_e( 'Dropped', 'school-management' ); ?></option>
                            <option value="suspended" <?php selected( $form_data['status'] ?? '', 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'school-management' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="payment_status"><?php esc_html_e( 'Payment Status', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="payment_status" name="payment_status" required>
                            <option value="unpaid" <?php selected( $form_data['payment_status'] ?? 'unpaid', 'unpaid' ); ?>><?php esc_html_e( 'Unpaid', 'school-management' ); ?></option>
                            <option value="partial" <?php selected( $form_data['payment_status'] ?? '', 'partial' ); ?>><?php esc_html_e( 'Partial Payment', 'school-management' ); ?></option>
                            <option value="paid" <?php selected( $form_data['payment_status'] ?? '', 'paid' ); ?>><?php esc_html_e( 'Paid', 'school-management' ); ?></option>
                            <option value="overdue" <?php selected( $form_data['payment_status'] ?? '', 'overdue' ); ?>><?php esc_html_e( 'Overdue', 'school-management' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Current payment status for this enrollment.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="enrollment_notes"><?php esc_html_e( 'Notes', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <textarea id="enrollment_notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea( $form_data['notes'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Optional notes or comments about this enrollment.', 'school-management' ); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( 
                    $is_edit ? __( 'Update Enrollment', 'school-management' ) : __( 'Create Enrollment', 'school-management' ), 
                    'primary', 
                    'sm_save_enrollment', 
                    false 
                ); ?>
                <a href="?page=school-management-enrollments" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'school-management' ); ?></a>
            </p>
            
            <p class="description">
                <span style="color: #d63638;">*</span> <?php esc_html_e( 'Required fields', 'school-management' ); ?>
            </p>
        </form>
        <?php
    }
}

// Instantiate class
new SM_Enrollments_Page();