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
                echo '<div class="updated notice"><p>' . esc_html__( 'Student deleted successfully.', 'school-management' ) . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html__( 'Error deleting student.', 'school-management' ) . '</p></div>';
            }
        }

        // Handle form submission with validation
        if ( isset( $_POST['sm_save_student'] ) && check_admin_referer( 'sm_save_student_action', 'sm_save_student_nonce' ) ) {
            
            // Validate and sanitize input data
            $validation_result = self::validate_student_data( $_POST );
            
            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];
                
                if ( ! empty( $_POST['student_id'] ) ) {
                    // Update existing student
                    $updated = $wpdb->update( $table, $data, [ 'id' => intval( $_POST['student_id'] ) ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Student updated successfully.', 'school-management' ) . '</p></div>';
                        // Redirect to list after successful update
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-students"; }, 2000);</script>';
                    } else {
                        echo '<div class="error notice"><p>' . esc_html__( 'Error updating student. Please try again.', 'school-management' ) . '</p></div>';
                    }
                } else {
                    // Add new student
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Student added successfully.', 'school-management' ) . '</p></div>';
                        // Redirect to list after successful insert
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-students"; }, 2000);</script>';
                    } else {
                        echo '<div class="error notice"><p>' . esc_html__( 'Error adding student. Please try again.', 'school-management' ) . '</p></div>';
                    }
                }
            } else {
                // Display validation errors
                echo '<div class="error notice"><p><strong>' . esc_html__( 'Please correct the following errors:', 'school-management' ) . '</strong></p>';
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
            <h1><?php esc_html_e( 'Manage Students', 'school-management' ); ?></h1>

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
        
        // Sanitize data first
        $name = sanitize_text_field( trim( $post_data['name'] ?? '' ) );
        $email = sanitize_email( trim( $post_data['email'] ?? '' ) );
        $phone = sanitize_text_field( trim( $post_data['phone'] ?? '' ) );
        $dob = sanitize_text_field( trim( $post_data['dob'] ?? '' ) );
        $level = sanitize_text_field( trim( $post_data['level'] ?? '' ) );
        $picture = esc_url_raw( trim( $post_data['picture'] ?? '' ) );
        $blood_type = sanitize_text_field( trim( $post_data['blood_type'] ?? '' ) );
        $student_id = intval( $post_data['student_id'] ?? 0 );

        // Required field validation
        if ( empty( $name ) ) {
            $errors[] = __( 'Student name is required.', 'school-management' );
        } elseif ( strlen( $name ) < 2 ) {
            $errors[] = __( 'Student name must be at least 2 characters long.', 'school-management' );
        } elseif ( strlen( $name ) > 100 ) {
            $errors[] = __( 'Student name cannot exceed 100 characters.', 'school-management' );
        }

        if ( empty( $email ) ) {
            $errors[] = __( 'Email address is required.', 'school-management' );
        } elseif ( ! is_email( $email ) ) {
            $errors[] = __( 'Please enter a valid email address.', 'school-management' );
        }

        if ( empty( $phone ) ) {
            $errors[] = __( 'Phone number is required.', 'school-management' );
        } elseif ( strlen( $phone ) < 8 ) {
            $errors[] = __( 'Please enter a valid phone number (minimum 8 digits).', 'school-management' );
        }

        if ( empty( $dob ) ) {
            $errors[] = __( 'Date of birth is required.', 'school-management' );
        } elseif ( ! self::is_valid_date( $dob ) ) {
            $errors[] = __( 'Please enter a valid date of birth.', 'school-management' );
        } elseif ( self::is_future_date( $dob ) ) {
            $errors[] = __( 'Date of birth cannot be in the future.', 'school-management' );
        }

        if ( empty( $level ) ) {
            $errors[] = __( 'Level is required.', 'school-management' );
        }

        // Check for duplicate name (case-insensitive)
        if ( ! empty( $name ) ) {
            $duplicate_query = "SELECT id FROM $table WHERE LOWER(name) = LOWER(%s)";
            $query_params = [ $name ];
            
            // If editing, exclude current student from duplicate check
            if ( $student_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $query_params[] = $student_id;
            }
            
            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $query_params ) );
            
            if ( $duplicate ) {
                $errors[] = sprintf( 
                    __( 'A student with the name "%s" already exists. Please use a different name.', 'school-management' ), 
                    $name 
                );
            }
        }

        // Check for duplicate email (case-insensitive)
        if ( ! empty( $email ) && is_email( $email ) ) {
            $duplicate_email_query = "SELECT id FROM $table WHERE LOWER(email) = LOWER(%s)";
            $email_params = [ $email ];
            
            // If editing, exclude current student from duplicate check
            if ( $student_id > 0 ) {
                $duplicate_email_query .= " AND id != %d";
                $email_params[] = $student_id;
            }
            
            $duplicate_email = $wpdb->get_var( $wpdb->prepare( $duplicate_email_query, $email_params ) );
            
            if ( $duplicate_email ) {
                $errors[] = sprintf( 
                    __( 'The email address "%s" is already registered. Please use a different email.', 'school-management' ), 
                    $email 
                );
            }
        }

        // Validate blood type if provided
        if ( ! empty( $blood_type ) ) {
            $valid_blood_types = [ 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-' ];
            if ( ! in_array( $blood_type, $valid_blood_types ) ) {
                $errors[] = __( 'Please select a valid blood type.', 'school-management' );
            }
        }

        // Validate picture URL if provided
        if ( ! empty( $picture ) && ! filter_var( $picture, FILTER_VALIDATE_URL ) ) {
            $errors[] = __( 'Please provide a valid picture URL.', 'school-management' );
        }

        // Return validation result
        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'name'       => $name,
                    'email'      => $email,
                    'phone'      => $phone,
                    'dob'        => $dob,
                    'level'      => $level,
                    'picture'    => $picture,
                    'blood_type' => $blood_type ?: null, // Store as NULL if empty
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
        $table = $wpdb->prefix . 'sm_students';

        // Pagination setup
        $per_page = 20; // Number of students per page
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        // Get total count for pagination
        $total_students = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        $total_pages = ceil( $total_students / $per_page );

        // Get students for current page
        $students = $wpdb->get_results( $wpdb->prepare( 
            "SELECT * FROM $table ORDER BY name ASC LIMIT %d OFFSET %d", 
            $per_page, 
            $offset 
        ) );

        ?>
        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php esc_html_e( 'Students List', 'school-management' ); ?></h2>
                <p class="description"><?php printf( esc_html__( 'Total: %d students', 'school-management' ), $total_students ); ?></p>
            </div>
            <div>
                <a href="?page=school-management-students&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Student', 'school-management' ); ?>
                </a>
            </div>
        </div>

        <?php if ( $students ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;"><?php esc_html_e( 'Picture', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Phone', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Level', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Blood Type', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Registered', 'school-management' ); ?></th>
                        <th style="width: 150px;"><?php esc_html_e( 'Actions', 'school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $students as $student ) : ?>
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
                            <td><span class="sm-level-badge"><?php echo esc_html( $student->level ); ?></span></td>
                            <td><?php echo esc_html( $student->blood_type ?: '—' ); ?></td>
                            <td><?php echo esc_html( date( 'M j, Y', strtotime( $student->created_at ) ) ); ?></td>
                            <td>
                                <a href="?page=school-management-students&action=edit&student_id=<?php echo intval( $student->id ); ?>" 
                                   class="button button-small" title="<?php esc_attr_e( 'Edit Student', 'school-management' ); ?>">
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
                                   title="<?php esc_attr_e( 'Delete Student', 'school-management' ); ?>"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this student? This action cannot be undone.', 'school-management' ) ); ?>')">
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
                    'prev_text' => __( '« Previous', 'school-management' ),
                    'next_text' => __( 'Next »', 'school-management' ),
                    'total'     => $total_pages,
                    'current'   => $current_page,
                ];

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
                <h3><?php esc_html_e( 'No Students Yet', 'school-management' ); ?></h3>
                <p><?php esc_html_e( 'Start building your student database by adding your first student.', 'school-management' ); ?></p>
                <a href="?page=school-management-students&action=add" class="button button-primary">
                    <?php esc_html_e( 'Add First Student', 'school-management' ); ?>
                </a>
            </div>
        <?php endif;
    }

    /**
     * Render student form (add/edit)
     */
    private static function render_student_form( $student = null ) {
        $is_edit = ! empty( $student );
        
        // Pre-fill form with POST data if validation failed
        $form_data = [];
        if ( isset( $_POST['sm_save_student'] ) ) {
            $form_data = [
                'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
                'email'      => sanitize_email( $_POST['email'] ?? '' ),
                'phone'      => sanitize_text_field( $_POST['phone'] ?? '' ),
                'dob'        => sanitize_text_field( $_POST['dob'] ?? '' ),
                'level'      => sanitize_text_field( $_POST['level'] ?? '' ),
                'picture'    => esc_url_raw( $_POST['picture'] ?? '' ),
                'blood_type' => sanitize_text_field( $_POST['blood_type'] ?? '' ),
            ];
        } elseif ( $student ) {
            $form_data = [
                'name'       => $student->name,
                'email'      => $student->email,
                'phone'      => $student->phone,
                'dob'        => $student->dob,
                'level'      => $student->level,
                'picture'    => $student->picture,
                'blood_type' => $student->blood_type,
            ];
        }
        
        ?>
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-students" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Students', 'school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Student', 'school-management' ) : esc_html__( 'Add New Student', 'school-management' ); ?>
            </h2>
        </div>

        <form method="post" novalidate>
            <?php wp_nonce_field( 'sm_save_student_action', 'sm_save_student_nonce' ); ?>
            <input type="hidden" name="student_id" value="<?php echo esc_attr( $student->id ?? '' ); ?>" />

            <table class="form-table">
                <tr>
                    <td colspan="2" style="position: relative;">
                        <!-- Picture upload box -->
                        <div id="sm_student_picture_box">
                            <?php if ( ! empty( $form_data['picture'] ) ) : ?>
                                <img id="sm_student_picture_preview" src="<?php echo esc_url( $form_data['picture'] ); ?>" alt="<?php esc_attr_e( 'Student Picture', 'school-management' ); ?>" />
                            <?php else : ?>
                                <span><?php esc_html_e( 'Click to upload', 'school-management' ); ?></span>
                                <img id="sm_student_picture_preview" src="" style="display:none;" alt="<?php esc_attr_e( 'Student Picture', 'school-management' ); ?>" />
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="picture" id="sm_student_picture" value="<?php echo esc_attr( $form_data['picture'] ?? '' ); ?>" />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_name"><?php esc_html_e( 'Full Name', 'school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="student_name" name="name" value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>" class="regular-text" required maxlength="100" />
                        <p class="description"><?php esc_html_e( 'Each student must have a unique name.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_email"><?php esc_html_e( 'Email Address', 'school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="email" id="student_email" name="email" value="<?php echo esc_attr( $form_data['email'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'Used for communications and must be unique.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_phone"><?php esc_html_e( 'Phone Number', 'school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="student_phone" name="phone" value="<?php echo esc_attr( $form_data['phone'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'Contact number for emergencies and notifications.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_dob"><?php esc_html_e( 'Date of Birth', 'school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="date" id="student_dob" name="dob" value="<?php echo esc_attr( $form_data['dob'] ?? '' ); ?>" required max="<?php echo date( 'Y-m-d' ); ?>" />
                        <p class="description"><?php esc_html_e( 'Required for age verification and records.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_level"><?php esc_html_e( 'Level', 'school-management' ); ?> <span class="description" style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <select id="student_level" name="level" required>
                            <option value=""><?php esc_html_e( 'Select Level', 'school-management' ); ?></option>
                            <?php
                            $levels = [ 'Beginner', 'Intermediate', 'Advanced', 'Expert' ];
                            $selected_level = $form_data['level'] ?? '';
                            foreach ( $levels as $level ) {
                                echo '<option value="' . esc_attr( $level ) . '" ' . selected( $selected_level, $level, false ) . '>' . esc_html( $level ) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Choose the appropriate skill level for course assignment.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="student_blood_type"><?php esc_html_e( 'Blood Type', 'school-management' ); ?> <span class="description">(optional)</span></label>
                    </th>
                    <td>
                        <select name="blood_type" id="student_blood_type">
                            <option value=""><?php esc_html_e( 'Select Blood Type', 'school-management' ); ?></option>
                            <?php
                            $types = [ 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-' ];
                            $selected_blood = $form_data['blood_type'] ?? '';
                            foreach ( $types as $type ) {
                                echo '<option value="' . esc_attr( $type ) . '" ' . selected( $selected_blood, $type, false ) . '>' . esc_html( $type ) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php esc_html_e( 'Blood type information for emergency situations.', 'school-management' ); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( 
                    $is_edit ? __( 'Update Student', 'school-management' ) : __( 'Add Student', 'school-management' ), 
                    'primary', 
                    'sm_save_student', 
                    false 
                ); ?>
                <a href="?page=school-management-students" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'school-management' ); ?></a>
            </p>
            
            <p class="description">
                <span style="color: #d63638;">*</span> <?php esc_html_e( 'Required fields', 'school-management' ); ?>
            </p>
        </form>
        <?php
    }
}

// Instantiate class
new SM_Students_Page();