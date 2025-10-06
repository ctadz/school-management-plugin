<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Courses_Page {

    /**
     * Render the Courses page
     */
    public static function render_courses_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_courses';

        // Handle delete action
        if ( isset( $_GET['delete'] ) && check_admin_referer( 'sm_delete_course_' . intval( $_GET['delete'] ) ) ) {
            $deleted = $wpdb->delete( $table, [ 'id' => intval( $_GET['delete'] ) ] );
            if ( $deleted ) {
                echo '<div class="updated notice"><p>' . esc_html__( 'Course deleted successfully.', 'school-management' ) . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html__( 'Error deleting course.', 'school-management' ) . '</p></div>';
            }
        }

        // Handle form submission
        if ( isset( $_POST['sm_save_course'] ) && check_admin_referer( 'sm_save_course_action', 'sm_save_course_nonce' ) ) {
            $validation_result = self::validate_course_data( $_POST );
            
            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];
                
                if ( ! empty( $_POST['course_id'] ) ) {
                    $updated = $wpdb->update( $table, $data, [ 'id' => intval( $_POST['course_id'] ) ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Course updated successfully.', 'school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-courses"; }, 2000);</script>';
                    }
                } else {
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Course added successfully.', 'school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-courses"; }, 2000);</script>';
                    }
                }
            } else {
                echo '<div class="error notice"><p><strong>' . esc_html__( 'Please correct the following errors:', 'school-management' ) . '</strong></p>';
                echo '<ul style="margin-left: 20px;">';
                foreach ( $validation_result['errors'] as $error ) {
                    echo '<li>' . esc_html( $error ) . '</li>';
                }
                echo '</ul></div>';
            }
        }

        // Determine view
        $action = $_GET['action'] ?? 'list';
        $course = null;

        if ( $action === 'edit' && isset( $_GET['course_id'] ) ) {
            $course = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['course_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Courses', 'school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'add':
                    self::render_course_form( null );
                    break;
                case 'edit':
                    self::render_course_form( $course );
                    break;
                default:
                    self::render_courses_list();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Validate course data
     */
    private static function validate_course_data( $post_data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_courses';
        $errors = [];
        
        // Sanitize data
        $name = sanitize_text_field( trim( $post_data['name'] ?? '' ) );
        $description = sanitize_textarea_field( trim( $post_data['description'] ?? '' ) );
        $description_file = esc_url_raw( trim( $post_data['description_file'] ?? '' ) );
        $language = sanitize_text_field( trim( $post_data['language'] ?? '' ) );
        $level_id = intval( $post_data['level_id'] ?? 0 );
        $teacher_id = intval( $post_data['teacher_id'] ?? 0 );
        $session_duration_hours = intval( $post_data['session_duration_hours'] ?? 0 );
        $session_duration_minutes = intval( $post_data['session_duration_minutes'] ?? 0 );
        $hours_per_week = floatval( $post_data['hours_per_week'] ?? 0 );
        $total_weeks = intval( $post_data['total_weeks'] ?? 0 );
        $total_months = intval( $post_data['total_months'] ?? 0 );
        $price_per_month = floatval( $post_data['price_per_month'] ?? 0 );
        $total_price = floatval( $post_data['total_price'] ?? 0 );
        $status = sanitize_text_field( trim( $post_data['status'] ?? 'upcoming' ) );
        $certification_type = sanitize_text_field( trim( $post_data['certification_type'] ?? '' ) );
        $certification_other = sanitize_text_field( trim( $post_data['certification_other'] ?? '' ) );
        $is_active = isset( $post_data['is_active'] ) ? 1 : 0;
        $course_id = intval( $post_data['course_id'] ?? 0 );

        // Required field validation
        if ( empty( $name ) ) {
            $errors[] = __( 'Course name is required.', 'school-management' );
        } elseif ( strlen( $name ) < 3 ) {
            $errors[] = __( 'Course name must be at least 3 characters long.', 'school-management' );
        }

        if ( empty( $description ) ) {
            $errors[] = __( 'Course description is required.', 'school-management' );
        }

        if ( empty( $language ) ) {
            $errors[] = __( 'Language is required.', 'school-management' );
        }

        if ( $level_id <= 0 ) {
            $errors[] = __( 'Level is required.', 'school-management' );
        }

        if ( $teacher_id <= 0 ) {
            $errors[] = __( 'Teacher is required.', 'school-management' );
        }

        if ( $session_duration_hours <= 0 && $session_duration_minutes <= 0 ) {
            $errors[] = __( 'Session duration is required.', 'school-management' );
        }

        if ( $hours_per_week <= 0 ) {
            $errors[] = __( 'Hours per week is required.', 'school-management' );
        }

        if ( $total_weeks <= 0 ) {
            $errors[] = __( 'Total weeks is required.', 'school-management' );
        }

        if ( $total_months <= 0 ) {
            $errors[] = __( 'Total months is required.', 'school-management' );
        }

        if ( $price_per_month <= 0 ) {
            $errors[] = __( 'Price per month is required.', 'school-management' );
        }

        if ( $total_price <= 0 ) {
            $errors[] = __( 'Total price is required.', 'school-management' );
        }

        // Check for duplicate course name
        if ( ! empty( $name ) ) {
            $duplicate_query = "SELECT id FROM $table WHERE LOWER(name) = LOWER(%s)";
            $params = [ $name ];
            
            if ( $course_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $params[] = $course_id;
            }
            
            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $params ) );
            if ( $duplicate ) {
                $errors[] = sprintf( __( 'A course with the name "%s" already exists.', 'school-management' ), $name );
            }
        }

        // Validate status
        $valid_statuses = [ 'upcoming', 'in_progress', 'completed', 'inactive' ];
        if ( ! in_array( $status, $valid_statuses ) ) {
            $errors[] = __( 'Invalid course status.', 'school-management' );
        }

        // Validate certification type if provided
        if ( ! empty( $certification_type ) ) {
            $valid_cert_types = [ 'school_diploma', 'state_diploma', 'other' ];
            if ( ! in_array( $certification_type, $valid_cert_types ) ) {
                $errors[] = __( 'Invalid certification type.', 'school-management' );
            }
    
        // If "other" is selected, the custom name is required
        if ( $certification_type === 'other' && empty( $certification_other ) ) {
            $errors[] = __( 'Please specify the certification name when selecting "Other".', 'school-management' );
            }
        }

        // Validate file URL if provided
        if ( ! empty( $description_file ) && ! filter_var( $description_file, FILTER_VALIDATE_URL ) ) {
            $errors[] = __( 'Please provide a valid file URL.', 'school-management' );
        }

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'name' => $name,
                    'description' => $description,
                    'description_file' => $description_file ?: null,
                    'language' => $language,
                    'level_id' => $level_id,
                    'teacher_id' => $teacher_id,
                    'session_duration_hours' => $session_duration_hours,
                    'session_duration_minutes' => $session_duration_minutes,
                    'hours_per_week' => $hours_per_week,
                    'total_weeks' => $total_weeks,
                    'total_months' => $total_months,
                    'price_per_month' => $price_per_month,
                    'total_price' => $total_price,
                    'certification_type' => $certification_type ?: null,
                    'certification_other' => $certification_other ?: null,
                    'status' => $status,
                    'is_active' => $is_active,
                ]
            ];
        }

        return [ 'success' => false, 'errors' => $errors ];
    }

    /**
     * Render courses list
     */
    private static function render_courses_list() {
        global $wpdb;
        $courses_table = $wpdb->prefix . 'sm_courses';
        $levels_table = $wpdb->prefix . 'sm_levels';
        $teachers_table = $wpdb->prefix . 'sm_teachers';

        // Pagination
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        $total_courses = $wpdb->get_var( "SELECT COUNT(*) FROM $courses_table" );
        $total_pages = ceil( $total_courses / $per_page );

        // Get courses with level and teacher names
        $courses = $wpdb->get_results( $wpdb->prepare( 
            "SELECT c.*, l.name as level_name, CONCAT(t.first_name, ' ', t.last_name) as teacher_name 
             FROM $courses_table c 
             LEFT JOIN $levels_table l ON c.level_id = l.id 
             LEFT JOIN $teachers_table t ON c.teacher_id = t.id 
             ORDER BY c.name ASC 
             LIMIT %d OFFSET %d", 
            $per_page, 
            $offset 
        ) );

        ?>
        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php esc_html_e( 'Courses List', 'school-management' ); ?></h2>
                <p class="description"><?php printf( esc_html__( 'Total: %d courses', 'school-management' ), $total_courses ); ?></p>
            </div>
            <div>
                <a href="?page=school-management-courses&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Course', 'school-management' ); ?>
                </a>
            </div>
        </div>

        <?php if ( $courses ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Course Name', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Language', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Level', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Teacher', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Duration', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Price/Month', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'school-management' ); ?></th>
                        <th style="width: 150px;"><?php esc_html_e( 'Actions', 'school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $courses as $course ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $course->name ); ?></strong></td>
                            <td><?php echo esc_html( $course->language ); ?></td>
                            <td><?php echo esc_html( $course->level_name ?: '—' ); ?></td>
                            <td><?php echo esc_html( $course->teacher_name ?: '—' ); ?></td>
                            <td><?php echo esc_html( $course->total_weeks . ' ' . __( 'weeks', 'school-management' ) ); ?></td>
                            <td><?php echo esc_html( number_format( $course->price_per_month, 2 ) ); ?></td>
                            <td>
                                <?php
                                $status_colors = [
                                    'upcoming' => '#f0ad4e',
                                    'in_progress' => '#46b450',
                                    'completed' => '#00a0d2',
                                    'inactive' => '#dc3232'
                                ];
                                $color = $status_colors[ $course->status ] ?? '#666';
                                $status_labels = [
                                    'upcoming' => __( 'Upcoming', 'school-management' ),
                                    'in_progress' => __( 'In Progress', 'school-management' ),
                                    'completed' => __( 'Completed', 'school-management' ),
                                    'inactive' => __( 'Inactive', 'school-management' )
                                ];
                                $label = $status_labels[ $course->status ] ?? $course->status;
                                ?>
                                <span style="color: <?php echo esc_attr( $color ); ?>;">● <?php echo esc_html( $label ); ?></span>
                            </td>
                            <td>
                                <a href="?page=school-management-courses&action=edit&course_id=<?php echo intval( $course->id ); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                </a>
                                <?php
                                $delete_url = wp_nonce_url( 
                                    '?page=school-management-courses&delete=' . intval( $course->id ), 
                                    'sm_delete_course_' . intval( $course->id ) 
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this course?', 'school-management' ) ); ?>')">
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
                <span class="dashicons dashicons-book" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></span>
                <h3><?php esc_html_e( 'No Courses Yet', 'school-management' ); ?></h3>
                <p><?php esc_html_e( 'Create your first course to get started.', 'school-management' ); ?></p>
                <a href="?page=school-management-courses&action=add" class="button button-primary">
                    <?php esc_html_e( 'Add First Course', 'school-management' ); ?>
                </a>
            </div>
        <?php endif;
    }

    /**
     * Render course form
     */
    private static function render_course_form( $course = null ) {
        global $wpdb;
        $is_edit = ! empty( $course );
        
        // Get levels and teachers
        $levels = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_levels WHERE is_active = 1 ORDER BY sort_order ASC, name ASC" );
        $teachers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}sm_teachers WHERE is_active = 1 ORDER BY last_name ASC, first_name ASC" );
        
        $form_data = [];
        if ( isset( $_POST['sm_save_course'] ) ) {
            $form_data = [
                'name' => sanitize_text_field( $_POST['name'] ?? '' ),
                'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
                'description_file' => esc_url_raw( $_POST['description_file'] ?? '' ),
                'language' => sanitize_text_field( $_POST['language'] ?? '' ),
                'level_id' => intval( $_POST['level_id'] ?? 0 ),
                'teacher_id' => intval( $_POST['teacher_id'] ?? 0 ),
                'session_duration_hours' => intval( $_POST['session_duration_hours'] ?? 0 ),
                'session_duration_minutes' => intval( $_POST['session_duration_minutes'] ?? 0 ),
                'hours_per_week' => floatval( $_POST['hours_per_week'] ?? 0 ),
                'total_weeks' => intval( $_POST['total_weeks'] ?? 0 ),
                'total_months' => intval( $_POST['total_months'] ?? 0 ),
                'price_per_month' => floatval( $_POST['price_per_month'] ?? 0 ),
                'total_price' => floatval( $_POST['total_price'] ?? 0 ),
                'certification_type' => sanitize_text_field( $_POST['certification_type'] ?? '' ),
                'certification_other' => sanitize_text_field( $_POST['certification_other'] ?? '' ),
                'status' => sanitize_text_field( $_POST['status'] ?? 'upcoming' ),
                'is_active' => isset( $_POST['is_active'] ),
            ];
        } elseif ( $course ) {
            $form_data = [
                'name' => $course->name,
                'description' => $course->description,
                'description_file' => $course->description_file,
                'language' => $course->language,
                'level_id' => $course->level_id,
                'teacher_id' => $course->teacher_id,
                'session_duration_hours' => $course->session_duration_hours,
                'session_duration_minutes' => $course->session_duration_minutes,
                'hours_per_week' => $course->hours_per_week,
                'total_weeks' => $course->total_weeks,
                'total_months' => $course->total_months,
                'price_per_month' => $course->price_per_month,
                'total_price' => $course->total_price,
                'certification_type' => $course->certification_type,
                'certification_other' => $course->certification_other,
                'status' => $course->status,
                'is_active' => $course->is_active,
            ];
        }
        
        ?>
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-courses" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Courses', 'school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Course', 'school-management' ) : esc_html__( 'Add New Course', 'school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_course_action', 'sm_save_course_nonce' ); ?>
            <input type="hidden" name="course_id" value="<?php echo esc_attr( $course->id ?? '' ); ?>" />

            <h3><?php esc_html_e( 'Basic Information', 'school-management' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="course_name"><?php esc_html_e( 'Course Name', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="course_name" name="name" value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'Course name must be unique.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_description"><?php esc_html_e( 'Description', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <textarea id="course_description" name="description" rows="5" class="large-text" required><?php echo esc_textarea( $form_data['description'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Brief description of the course content and objectives.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_description_file"><?php esc_html_e( 'Description File (PDF)', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="course_description_file" name="description_file" value="<?php echo esc_attr( $form_data['description_file'] ?? '' ); ?>" class="regular-text" readonly />
                        <button type="button" class="button sm-upload-file" data-target="course_description_file"><?php esc_html_e( 'Upload PDF', 'school-management' ); ?></button>
                        <?php if ( ! empty( $form_data['description_file'] ) ) : ?>
                            <br/><a href="<?php echo esc_url( $form_data['description_file'] ); ?>" target="_blank" class="button button-small" style="margin-top: 5px;">
                                <?php esc_html_e( 'View Current File', 'school-management' ); ?>
                            </a>
                        <?php endif; ?>
                        <p class="description"><?php esc_html_e( 'Optional: Upload a detailed PDF description of the course.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_language"><?php esc_html_e( 'Language', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="course_language" name="language" required>
                            <option value=""><?php esc_html_e( 'Select Language', 'school-management' ); ?></option>
                            <option value="French" <?php selected( $form_data['language'] ?? '', 'French' ); ?>><?php esc_html_e( 'French', 'school-management' ); ?></option>
                            <option value="English" <?php selected( $form_data['language'] ?? '', 'English' ); ?>><?php esc_html_e( 'English', 'school-management' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_level"><?php esc_html_e( 'Level', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="course_level" name="level_id" required>
                            <option value=""><?php esc_html_e( 'Select Level', 'school-management' ); ?></option>
                            <?php foreach ( $levels as $level ) : ?>
                                <option value="<?php echo intval( $level->id ); ?>" <?php selected( $form_data['level_id'] ?? 0, $level->id ); ?>>
                                    <?php echo esc_html( $level->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <a href="?page=school-management-levels" target="_blank"><?php esc_html_e( 'Manage levels', 'school-management' ); ?></a>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_teacher"><?php esc_html_e( 'Teacher', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="course_teacher" name="teacher_id" required>
                            <option value=""><?php esc_html_e( 'Select Teacher', 'school-management' ); ?></option>
                            <?php foreach ( $teachers as $teacher ) : ?>
                                <option value="<?php echo intval( $teacher->id ); ?>" <?php selected( $form_data['teacher_id'] ?? 0, $teacher->id ); ?>>
                                    <?php echo esc_html( $teacher->first_name . ' ' . $teacher->last_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <a href="?page=school-management-teachers" target="_blank"><?php esc_html_e( 'Manage teachers', 'school-management' ); ?></a>
                        </p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Duration & Schedule', 'school-management' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e( 'Session Duration', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" name="session_duration_hours" value="<?php echo esc_attr( $form_data['session_duration_hours'] ?? 0 ); ?>" min="0" max="24" style="width: 80px;" />
                        <span><?php esc_html_e( 'hours', 'school-management' ); ?></span>
                        
                        <input type="number" name="session_duration_minutes" value="<?php echo esc_attr( $form_data['session_duration_minutes'] ?? 0 ); ?>" min="0" max="59" style="width: 80px; margin-left: 10px;" />
                        <span><?php esc_html_e( 'minutes', 'school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Duration of each individual class session (e.g., 1h 30min).', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_hours_per_week"><?php esc_html_e( 'Hours Per Week', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_hours_per_week" name="hours_per_week" value="<?php echo esc_attr( $form_data['hours_per_week'] ?? 0 ); ?>" min="0" step="0.5" required />
                        <span><?php esc_html_e( 'hours', 'school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Total teaching hours per week (e.g., 3.5 for two 1h45min sessions).', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_total_weeks"><?php esc_html_e( 'Total Weeks', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_total_weeks" name="total_weeks" value="<?php echo esc_attr( $form_data['total_weeks'] ?? 0 ); ?>" min="1" required />
                        <span><?php esc_html_e( 'weeks', 'school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Total duration of the course in weeks.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_total_months"><?php esc_html_e( 'Total Months', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_total_months" name="total_months" value="<?php echo esc_attr( $form_data['total_months'] ?? 0 ); ?>" min="1" required />
                        <span><?php esc_html_e( 'months', 'school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Total duration of the course in months.', 'school-management' ); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Pricing', 'school-management' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="course_price_per_month"><?php esc_html_e( 'Price Per Month', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_price_per_month" name="price_per_month" value="<?php echo esc_attr( $form_data['price_per_month'] ?? 0 ); ?>" min="0" step="0.01" required />
                        <p class="description"><?php esc_html_e( 'Monthly tuition fee for this course.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_total_price"><?php esc_html_e( 'Total Course Price', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="number" id="course_total_price" name="total_price" value="<?php echo esc_attr( $form_data['total_price'] ?? 0 ); ?>" min="0" step="0.01" required />
                        <p class="description"><?php esc_html_e( 'Total price for the entire course duration.', 'school-management' ); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Certification', 'school-management' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="course_certification_type"><?php esc_html_e( 'Certification Delivered', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <select id="course_certification_type" name="certification_type">
                            <option value=""><?php esc_html_e( 'No Certification', 'school-management' ); ?></option>
                            <option value="school_diploma" <?php selected( $form_data['certification_type'] ?? '', 'school_diploma' ); ?>><?php esc_html_e( 'School Diploma', 'school-management' ); ?></option>
                            <option value="state_diploma" <?php selected( $form_data['certification_type'] ?? '', 'state_diploma' ); ?>><?php esc_html_e( 'State Diploma', 'school-management' ); ?></option>
                            <option value="other" <?php selected( $form_data['certification_type'] ?? '', 'other' ); ?>><?php esc_html_e( 'Other', 'school-management' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Type of certification or diploma awarded upon course completion.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr id="certification_other_row" style="<?php echo ( $form_data['certification_type'] ?? '' ) === 'other' ? '' : 'display:none;'; ?>">
                    <th scope="row">
                        <label for="course_certification_other"><?php esc_html_e( 'Certification Name', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="course_certification_other" name="certification_other" value="<?php echo esc_attr( $form_data['certification_other'] ?? '' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Specify the name of the certification or entity that delivers it.', 'school-management' ); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Status & Availability', 'school-management' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="course_status"><?php esc_html_e( 'Course Status', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="course_status" name="status" required>
                            <option value="upcoming" <?php selected( $form_data['status'] ?? 'upcoming', 'upcoming' ); ?>><?php esc_html_e( 'Upcoming', 'school-management' ); ?></option>
                            <option value="in_progress" <?php selected( $form_data['status'] ?? '', 'in_progress' ); ?>><?php esc_html_e( 'In Progress', 'school-management' ); ?></option>
                            <option value="completed" <?php selected( $form_data['status'] ?? '', 'completed' ); ?>><?php esc_html_e( 'Completed', 'school-management' ); ?></option>
                            <option value="inactive" <?php selected( $form_data['status'] ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'school-management' ); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Current status of the course.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="course_is_active"><?php esc_html_e( 'Active', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="course_is_active" name="is_active" value="1" <?php checked( $form_data['is_active'] ?? true ); ?> />
                            <?php esc_html_e( 'Active (visible and available for enrollment)', 'school-management' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( 
                    $is_edit ? __( 'Update Course', 'school-management' ) : __( 'Add Course', 'school-management' ), 
                    'primary', 
                    'sm_save_course', 
                    false 
                ); ?>
                <a href="?page=school-management-courses" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'school-management' ); ?></a>
            </p>
            
            <p class="description">
                <span style="color: #d63638;">*</span> <?php esc_html_e( 'Required fields', 'school-management' ); ?>
            </p>
            <script>
            jQuery(document).ready(function($) {
                $('#course_certification_type').on('change', function() {
                    if ($(this).val() === 'other') {
                        $('#certification_other_row').show();
                        $('#course_certification_other').attr('required', true);
                    } else {
                        $('#certification_other_row').hide();
                        $('#course_certification_other').attr('required', false);
                    }
                });
            });
            </script>

        </form>
        <?php
    }
}

// Instantiate class
new SM_Courses_Page();