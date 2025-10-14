<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Levels_Page {

    /**
     * Render the Levels page
     */
    public static function render_levels_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_levels';

        // Handle delete action
        if ( isset( $_GET['delete'] ) && check_admin_referer( 'sm_delete_level_' . intval( $_GET['delete'] ) ) ) {
            // Check if level is being used by any students or courses
            $students_table = $wpdb->prefix . 'sm_students';
            $courses_table = $wpdb->prefix . 'sm_courses';
            $level_id = intval( $_GET['delete'] );
            
            $level_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $table WHERE id = %d", $level_id ) );
            $students_using = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $students_table WHERE level = %s", $level_name ) );
            $courses_using = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $courses_table WHERE level_id = %d", $level_id ) );
            
            if ( $students_using > 0 || $courses_using > 0 ) {
                echo '<div class="error notice"><p>' . sprintf( 
                    esc_html__( 'Cannot delete this level. It is being used by %d student(s) and %d course(s).', 'school-management' ),
                    $students_using,
                    $courses_using
                ) . '</p></div>';
            } else {
                $deleted = $wpdb->delete( $table, [ 'id' => $level_id ] );
                if ( $deleted ) {
                    echo '<div class="updated notice"><p>' . esc_html__( 'Level deleted successfully.', 'school-management' ) . '</p></div>';
                }
            }
        }

        // Handle form submission
        if ( ( isset( $_POST['sm_save_level'] ) || isset( $_POST['sm_save_and_new'] ) ) && check_admin_referer( 'sm_save_level_action', 'sm_save_level_nonce' ) ) {
            $validation_result = self::validate_level_data( $_POST );
    
            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];
                $save_and_new = isset( $_POST['sm_save_and_new'] );
        
                if ( ! empty( $_POST['level_id'] ) ) {
                    // Edit mode
                    $updated = $wpdb->update( $table, $data, [ 'id' => intval( $_POST['level_id'] ) ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Level updated successfully.', 'school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-levels"; }, 1500);</script>';
                    }
                } else {
                    // Add mode
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Level added successfully.', 'school-management' ) . '</p></div>';
                
                        if ( $save_and_new ) {
                            // Redirect to add new level page
                            echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-levels&action=add"; }, 1500);</script>';
                        } else {
                            // Redirect to levels list
                            echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-levels"; }, 1500);</script>';
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
        $level = null;

        if ( $action === 'edit' && isset( $_GET['level_id'] ) ) {
            $level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['level_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Levels', 'school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'add':
                    self::render_level_form( null );
                    break;
                case 'edit':
                    self::render_level_form( $level );
                    break;
                default:
                    self::render_levels_list();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Validate level data
     */
    private static function validate_level_data( $post_data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_levels';
        $errors = [];
        
        $name = sanitize_text_field( trim( $post_data['name'] ?? '' ) );
        $description = sanitize_textarea_field( trim( $post_data['description'] ?? '' ) );
        $sort_order = intval( $post_data['sort_order'] ?? 0 );
        $is_active = isset( $post_data['is_active'] ) ? 1 : 0;
        $level_id = intval( $post_data['level_id'] ?? 0 );

        // Validate name
        if ( empty( $name ) ) {
            $errors[] = __( 'Level name is required.', 'school-management' );
        } elseif ( strlen( $name ) < 2 ) {
            $errors[] = __( 'Level name must be at least 2 characters long.', 'school-management' );
        }

        // Check for duplicate name
        if ( ! empty( $name ) ) {
            $duplicate_query = "SELECT id FROM $table WHERE LOWER(name) = LOWER(%s)";
            $params = [ $name ];
            
            if ( $level_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $params[] = $level_id;
            }
            
            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $params ) );
            if ( $duplicate ) {
                $errors[] = sprintf( __( 'A level with the name "%s" already exists.', 'school-management' ), $name );
            }
        }

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => $sort_order,
                    'is_active' => $is_active,
                ]
            ];
        }

        return [ 'success' => false, 'errors' => $errors ];
    }

    /**
     * Render levels list
     */
    private static function render_levels_list() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_levels';
        $levels = $wpdb->get_results( "SELECT * FROM $table ORDER BY sort_order ASC, name ASC" );

        ?>
        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php esc_html_e( 'Course Levels', 'school-management' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Manage skill levels for courses and students', 'school-management' ); ?></p>
            </div>
            <div>
                <a href="?page=school-management-levels&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Level', 'school-management' ); ?>
                </a>
            </div>
        </div>

        <?php if ( $levels ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Order', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Level Name', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'school-management' ); ?></th>
                        <th style="width: 150px;"><?php esc_html_e( 'Actions', 'school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $levels as $level ) : ?>
                        <tr>
                            <td><?php echo intval( $level->sort_order ); ?></td>
                            <td><strong><?php echo esc_html( $level->name ); ?></strong></td>
                            <td><?php echo esc_html( $level->description ?: '—' ); ?></td>
                            <td>
                                <?php if ( $level->is_active ) : ?>
                                    <span style="color: #46b450;">● <?php esc_html_e( 'Active', 'school-management' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #dc3232;">● <?php esc_html_e( 'Inactive', 'school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=school-management-levels&action=edit&level_id=<?php echo intval( $level->id ); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                </a>
                                <?php
                                $delete_url = wp_nonce_url( 
                                    '?page=school-management-levels&delete=' . intval( $level->id ), 
                                    'sm_delete_level_' . intval( $level->id ) 
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this level?', 'school-management' ) ); ?>')">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle; color: #d63638;"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif;
    }

    /**
     * Render level form
     */
    private static function render_level_form( $level = null ) {
        $is_edit = ! empty( $level );
        
        $form_data = [];
        if ( isset( $_POST['sm_save_level'] ) ) {
            $form_data = [
                'name' => sanitize_text_field( $_POST['name'] ?? '' ),
                'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
                'sort_order' => intval( $_POST['sort_order'] ?? 0 ),
                'is_active' => isset( $_POST['is_active'] ),
            ];
        } elseif ( $level ) {
            $form_data = [
                'name' => $level->name,
                'description' => $level->description,
                'sort_order' => $level->sort_order,
                'is_active' => $level->is_active,
            ];
        }
        
        ?>
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-levels" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Levels', 'school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Level', 'school-management' ) : esc_html__( 'Add New Level', 'school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_level_action', 'sm_save_level_nonce' ); ?>
            <input type="hidden" name="level_id" value="<?php echo esc_attr( $level->id ?? '' ); ?>" />

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="level_name"><?php esc_html_e( 'Level Name', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="level_name" name="name" value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'E.g., Beginner, Intermediate, Advanced', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="level_description"><?php esc_html_e( 'Description', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <textarea id="level_description" name="description" rows="4" class="large-text"><?php echo esc_textarea( $form_data['description'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Brief description of this level', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="level_sort_order"><?php esc_html_e( 'Sort Order', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="level_sort_order" name="sort_order" value="<?php echo esc_attr( $form_data['sort_order'] ?? 0 ); ?>" min="0" max="999" />
                        <p class="description"><?php esc_html_e( 'Lower numbers appear first in lists', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="level_is_active"><?php esc_html_e( 'Status', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="level_is_active" name="is_active" value="1" <?php checked( $form_data['is_active'] ?? true ); ?> />
                            <?php esc_html_e( 'Active (available for courses and students)', 'school-management' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php if ( $is_edit ) : ?>
                    <?php submit_button( 
                        __( 'Update Level', 'school-management' ), 
                        'primary', 
                        'sm_save_level', 
                        false 
                    ); ?>
                <?php else : ?>
                    <?php submit_button( 
                        __( 'Save & Exit', 'school-management' ), 
                        'primary', 
                        'sm_save_level', 
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
                <a href="?page=school-management-levels" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'school-management' ); ?></a>
            </p>
        </form>
        <?php
    }
}

// Instantiate class
new SM_Levels_Page();