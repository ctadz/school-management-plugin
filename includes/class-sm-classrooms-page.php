<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Classrooms_Page {

    /**
     * Render the Classrooms page
     */
    public static function render_classrooms_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_classrooms';

        // Handle delete action
        if ( isset( $_GET['delete'] ) && check_admin_referer( 'sm_delete_classroom_' . intval( $_GET['delete'] ) ) ) {
            // Check if classroom is being used by any courses
            $courses_table = $wpdb->prefix . 'sm_courses';
            $classroom_id = intval( $_GET['delete'] );
            
            $classroom_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $table WHERE id = %d", $classroom_id ) );
            $courses_using = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $courses_table WHERE classroom_id = %d", $classroom_id ) );
            
            if ( $courses_using > 0 ) {
                echo '<div class="error notice"><p>' . sprintf( 
                    esc_html__( 'Cannot delete this classroom. It is being used by %d course(s).', 'school-management' ),
                    $courses_using
                ) . '</p></div>';
            } else {
                $deleted = $wpdb->delete( $table, [ 'id' => $classroom_id ] );
                if ( $deleted ) {
                    echo '<div class="updated notice"><p>' . esc_html__( 'Classroom deleted successfully.', 'school-management' ) . '</p></div>';
                }
            }
        }

        // Handle form submission
        if ( ( isset( $_POST['sm_save_classroom'] ) || isset( $_POST['sm_save_and_new'] ) ) && check_admin_referer( 'sm_save_classroom_action', 'sm_save_classroom_nonce' ) ) {
            $validation_result = self::validate_classroom_data( $_POST );
            
            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];
                $save_and_new = isset( $_POST['sm_save_and_new'] );
                
                if ( ! empty( $_POST['classroom_id'] ) ) {
                    // Edit mode
                    $updated = $wpdb->update( $table, $data, [ 'id' => intval( $_POST['classroom_id'] ) ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Classroom updated successfully.', 'school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-classrooms"; }, 1500);</script>';
                    }
                } else {
                    // Add mode
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Classroom added successfully.', 'school-management' ) . '</p></div>';
                        
                        if ( $save_and_new ) {
                            echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-classrooms&action=add"; }, 1500);</script>';
                        } else {
                            echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-classrooms"; }, 1500);</script>';
                        }
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
        $classroom = null;

        if ( $action === 'edit' && isset( $_GET['classroom_id'] ) ) {
            $classroom = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['classroom_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Classrooms', 'school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'add':
                    self::render_classroom_form( null );
                    break;
                case 'edit':
                    self::render_classroom_form( $classroom );
                    break;
                default:
                    self::render_classrooms_list();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Validate classroom data
     */
    private static function validate_classroom_data( $post_data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_classrooms';
        $errors = [];
        
        $name = sanitize_text_field( trim( $post_data['name'] ?? '' ) );
        $capacity = intval( $post_data['capacity'] ?? 0 );
        $location = sanitize_text_field( trim( $post_data['location'] ?? '' ) );
        $facilities = sanitize_textarea_field( trim( $post_data['facilities'] ?? '' ) );
        $is_active = isset( $post_data['is_active'] ) ? 1 : 0;
        $classroom_id = intval( $post_data['classroom_id'] ?? 0 );

        // Validate name
        if ( empty( $name ) ) {
            $errors[] = __( 'Classroom name is required.', 'school-management' );
        } elseif ( strlen( $name ) < 2 ) {
            $errors[] = __( 'Classroom name must be at least 2 characters long.', 'school-management' );
        }

        // Validate capacity
        if ( $capacity < 0 ) {
            $errors[] = __( 'Capacity cannot be negative.', 'school-management' );
        }

        // Check for duplicate name
        if ( ! empty( $name ) ) {
            $duplicate_query = "SELECT id FROM $table WHERE LOWER(name) = LOWER(%s)";
            $params = [ $name ];
            
            if ( $classroom_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $params[] = $classroom_id;
            }
            
            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $params ) );
            if ( $duplicate ) {
                $errors[] = sprintf( __( 'A classroom with the name "%s" already exists.', 'school-management' ), $name );
            }
        }

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'name' => $name,
                    'capacity' => $capacity,
                    'location' => $location,
                    'facilities' => $facilities,
                    'is_active' => $is_active,
                ]
            ];
        }

        return [ 'success' => false, 'errors' => $errors ];
    }

    /**
     * Render classrooms list
     */
    private static function render_classrooms_list() {
        global $wpdb;
        $classrooms_table = $wpdb->prefix . 'sm_classrooms';
        $courses_table = $wpdb->prefix . 'sm_courses';

        // Pagination
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        $total_classrooms = $wpdb->get_var( "SELECT COUNT(*) FROM $classrooms_table" );
        $total_pages = ceil( $total_classrooms / $per_page );

        // Get classrooms with course count
        $classrooms = $wpdb->get_results( $wpdb->prepare( 
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM $courses_table WHERE classroom_id = c.id) as course_count
             FROM $classrooms_table c 
             ORDER BY c.name ASC 
             LIMIT %d OFFSET %d", 
            $per_page, 
            $offset 
        ) );

        ?>
        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php esc_html_e( 'Classrooms List', 'school-management' ); ?></h2>
                <p class="description"><?php printf( esc_html__( 'Total: %d classrooms', 'school-management' ), $total_classrooms ); ?></p>
            </div>
            <div>
                <a href="?page=school-management-classrooms&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Classroom', 'school-management' ); ?>
                </a>
            </div>
        </div>

        <?php if ( $classrooms ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Classroom Name', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Capacity', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Location', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Courses', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'school-management' ); ?></th>
                        <th style="width: 150px;"><?php esc_html_e( 'Actions', 'school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $classrooms as $classroom ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $classroom->name ); ?></strong></td>
                            <td><?php echo intval( $classroom->capacity ); ?> <?php esc_html_e( 'students', 'school-management' ); ?></td>
                            <td><?php echo esc_html( $classroom->location ?: '—' ); ?></td>
                            <td><?php echo intval( $classroom->course_count ); ?> <?php esc_html_e( 'course(s)', 'school-management' ); ?></td>
                            <td>
                                <?php if ( $classroom->is_active ) : ?>
                                    <span style="color: #46b450;">● <?php esc_html_e( 'Active', 'school-management' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #dc3232;">● <?php esc_html_e( 'Inactive', 'school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=school-management-classrooms&action=edit&classroom_id=<?php echo intval( $classroom->id ); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                </a>
                                <?php
                                $delete_url = wp_nonce_url( 
                                    '?page=school-management-classrooms&delete=' . intval( $classroom->id ), 
                                    'sm_delete_classroom_' . intval( $classroom->id ) 
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this classroom?', 'school-management' ) ); ?>')">
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
                <span class="dashicons dashicons-building" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></span>
                <h3><?php esc_html_e( 'No Classrooms Yet', 'school-management' ); ?></h3>
                <p><?php esc_html_e( 'Add classrooms to assign courses to physical locations.', 'school-management' ); ?></p>
                <a href="?page=school-management-classrooms&action=add" class="button button-primary">
                    <?php esc_html_e( 'Add First Classroom', 'school-management' ); ?>
                </a>
            </div>
        <?php endif;
    }

    /**
     * Render classroom form
     */
    private static function render_classroom_form( $classroom = null ) {
        $is_edit = ! empty( $classroom );
        
        $form_data = [];
        if ( isset( $_POST['sm_save_classroom'] ) || isset( $_POST['sm_save_and_new'] ) ) {
            $form_data = [
                'name' => sanitize_text_field( $_POST['name'] ?? '' ),
                'capacity' => intval( $_POST['capacity'] ?? 0 ),
                'location' => sanitize_text_field( $_POST['location'] ?? '' ),
                'facilities' => sanitize_textarea_field( $_POST['facilities'] ?? '' ),
                'is_active' => isset( $_POST['is_active'] ),
            ];
        } elseif ( $classroom ) {
            $form_data = [
                'name' => $classroom->name,
                'capacity' => $classroom->capacity,
                'location' => $classroom->location,
                'facilities' => $classroom->facilities,
                'is_active' => $classroom->is_active,
            ];
        }
        
        ?>
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-classrooms" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Classrooms', 'school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Classroom', 'school-management' ) : esc_html__( 'Add New Classroom', 'school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_classroom_action', 'sm_save_classroom_nonce' ); ?>
            <input type="hidden" name="classroom_id" value="<?php echo esc_attr( $classroom->id ?? '' ); ?>" />

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="classroom_name"><?php esc_html_e( 'Classroom Name', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="classroom_name" name="name" value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'E.g., Room 101, Lab A, Auditorium', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="classroom_capacity"><?php esc_html_e( 'Capacity', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="classroom_capacity" name="capacity" value="<?php echo esc_attr( $form_data['capacity'] ?? 0 ); ?>" min="0" max="999" style="width: 100px;" />
                        <span><?php esc_html_e( 'students', 'school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Maximum number of students this classroom can accommodate.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="classroom_location"><?php esc_html_e( 'Location/Building', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="classroom_location" name="location" value="<?php echo esc_attr( $form_data['location'] ?? '' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'E.g., Building A - Floor 2, Main Campus', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="classroom_facilities"><?php esc_html_e( 'Facilities/Equipment', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <textarea id="classroom_facilities" name="facilities" rows="4" class="large-text"><?php echo esc_textarea( $form_data['facilities'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'List available equipment: projector, computers, whiteboard, etc.', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="classroom_is_active"><?php esc_html_e( 'Status', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="classroom_is_active" name="is_active" value="1" <?php checked( $form_data['is_active'] ?? true ); ?> />
                            <?php esc_html_e( 'Active (available for course assignment)', 'school-management' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php if ( $is_edit ) : ?>
                    <?php submit_button( 
                        __( 'Update Classroom', 'school-management' ), 
                        'primary', 
                        'sm_save_classroom', 
                        false 
                    ); ?>
                <?php else : ?>
                    <?php submit_button( 
                        __( 'Save & Exit', 'school-management' ), 
                        'primary', 
                        'sm_save_classroom', 
                        false 
                    ); ?>
                    <?php submit_button( 
                        __( 'Save & Add New', 'school-management' ), 
                        'secondary', 
                        'sm_save_and_new', 
                        false,
                        [ 'style' => 'margin-left: 5px;' ]
                    ); ?>
                <?php endif; ?>
                <a href="?page=school-management-classrooms" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'school-management' ); ?></a>
            </p>
            
            <p class="description">
                <span style="color: #d63638;">*</span> <?php esc_html_e( 'Required fields', 'school-management' ); ?>
            </p>
        </form>
        <?php
    }
}

// Instantiate class
new SM_Classrooms_Page();