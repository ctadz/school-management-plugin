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
                    esc_html__( 'Cannot delete this level. It is being used by %d student(s) and %d course(s).', 'CTADZ-school-management' ),
                    $students_using,
                    $courses_using
                ) . '</p></div>';
            } else {
                $deleted = $wpdb->delete( $table, [ 'id' => $level_id ] );
                if ( $deleted ) {
                    echo '<div class="updated notice"><p>' . esc_html__( 'Level deleted successfully.', 'CTADZ-school-management' ) . '</p></div>';
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
                        echo '<div class="updated notice"><p>' . esc_html__( 'Level updated successfully.', 'CTADZ-school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-levels"; }, 1500);</script>';
                    }
                } else {
                    // Add mode
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Level added successfully.', 'CTADZ-school-management' ) . '</p></div>';
                
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
        $level = null;

        if ( $action === 'edit' && isset( $_GET['level_id'] ) ) {
            $level = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['level_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Levels', 'CTADZ-school-management' ); ?></h1>

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
            $errors[] = __( 'Level name is required.', 'CTADZ-school-management' );
        } elseif ( strlen( $name ) < 2 ) {
            $errors[] = __( 'Level name must be at least 2 characters long.', 'CTADZ-school-management' );
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
                $errors[] = sprintf( __( 'A level with the name "%s" already exists.', 'CTADZ-school-management' ), $name );
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
        $levels_table = $wpdb->prefix . 'sm_levels';
        $students_table = $wpdb->prefix . 'sm_students';
        $courses_table = $wpdb->prefix . 'sm_courses';

        // Get search parameter
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // Get sorting parameters
        $orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'sort_order';
        $order = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), [ 'ASC', 'DESC' ] ) ? strtoupper( $_GET['order'] ) : 'ASC';

        // Build WHERE clause for search
        $where_clause = '';
        if ( ! empty( $search ) ) {
            $search_term = '%' . $wpdb->esc_like( $search ) . '%';
            $where_clause = $wpdb->prepare( 
                "WHERE (l.name LIKE %s OR l.description LIKE %s)", 
                $search_term, 
                $search_term
            );
        }

        // Validate and set ORDER BY clause
        $valid_columns = [
            'name' => 'l.name',
            'sort_order' => 'l.sort_order',
            'student_count' => 'student_count',
            'course_count' => 'course_count',
            'status' => 'l.is_active'
        ];
        $orderby_column = isset( $valid_columns[ $orderby ] ) ? $valid_columns[ $orderby ] : 'l.sort_order';
        $order_clause = "$orderby_column $order";

        // Get levels with student and course counts
        $levels = $wpdb->get_results( 
            "SELECT l.*, 
                    COUNT(DISTINCT s.id) as student_count,
                    COUNT(DISTINCT c.id) as course_count
             FROM $levels_table l 
             LEFT JOIN $students_table s ON l.id = s.level_id
             LEFT JOIN $courses_table c ON l.id = c.level_id
             $where_clause
             GROUP BY l.id
             ORDER BY $order_clause" 
        );

        $total_levels = count( $levels );

        // Helper function to generate sortable column URL
        $get_sort_url = function( $column ) use ( $orderby, $order, $search ) {
            $new_order = ( $orderby === $column && $order === 'ASC' ) ? 'DESC' : 'ASC';
            $url = add_query_arg( [
                'page' => 'school-management-levels',
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
                <h2 style="margin: 0;"><?php esc_html_e( 'Course Levels', 'CTADZ-school-management' ); ?></h2>
                <p class="description">
                    <?php 
                    if ( ! empty( $search ) ) {
                        printf( esc_html__( 'Showing %d levels matching "%s"', 'CTADZ-school-management' ), $total_levels, esc_html( $search ) );
                        echo ' <a href="?page=school-management-levels" style="margin-left: 10px;">' . esc_html__( '[Clear search]', 'CTADZ-school-management' ) . '</a>';
                    } else {
                        esc_html_e( 'Manage skill levels for courses and students', 'CTADZ-school-management' );
                    }
                    ?>
                </p>
            </div>
            <div>
                <a href="?page=school-management-levels&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Level', 'CTADZ-school-management' ); ?>
                </a>
            </div>
        </div>

        <!-- Search Box -->
        <div class="tablenav top" style="margin-bottom: 15px;">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="school-management-levels">
                <?php if ( ! empty( $orderby ) ) : ?>
                    <input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>">
                    <input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>">
                <?php endif; ?>
                <input type="search" 
                       name="s" 
                       value="<?php echo esc_attr( $search ); ?>" 
                       placeholder="<?php esc_attr_e( 'Search levels by name or description...', 'CTADZ-school-management' ); ?>"
                       style="width: 300px; margin-right: 5px;">
                <button type="submit" class="button"><?php esc_html_e( 'Search', 'CTADZ-school-management' ); ?></button>
                <?php if ( ! empty( $search ) ) : ?>
                    <a href="?page=school-management-levels" class="button" style="margin-left: 5px;">
                        <?php esc_html_e( 'Clear', 'CTADZ-school-management' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ( $levels ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="<?php echo $orderby === 'name' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'name' ); ?>">
                                <?php esc_html_e( 'Level Name', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'name' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable"><?php esc_html_e( 'Description', 'CTADZ-school-management' ); ?></th>
                        <th class="<?php echo $orderby === 'student_count' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'student_count' ); ?>">
                                <?php esc_html_e( 'Students', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'student_count' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'course_count' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'course_count' ); ?>">
                                <?php esc_html_e( 'Courses', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'course_count' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'status' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'status' ); ?>">
                                <?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'status' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'sort_order' ? 'sorted' : 'sortable'; ?>" style="width: 80px;">
                            <a href="<?php echo $get_sort_url( 'sort_order' ); ?>">
                                <?php esc_html_e( 'Order', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'sort_order' ); ?>
                            </a>
                        </th>
                        <th class="non-sortable" style="width: 150px;"><?php esc_html_e( 'Actions', 'CTADZ-school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $levels as $level ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $level->name ); ?></strong></td>
                            <td><?php echo esc_html( $level->description ?: '—' ); ?></td>
                            <td>
                                <?php
                                $student_count = intval( $level->student_count );
                                if ( $student_count > 0 ) {
                                    echo '<span style="color: #2271b1;"><strong>' . esc_html( $student_count ) . '</strong> ' . esc_html( _n( 'student', 'students', $student_count, 'CTADZ-school-management' ) ) . '</span>';
                                } else {
                                    echo '<span style="color: #999;">' . esc_html__( 'None', 'CTADZ-school-management' ) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $course_count = intval( $level->course_count );
                                if ( $course_count > 0 ) {
                                    echo '<span style="color: #2271b1;"><strong>' . esc_html( $course_count ) . '</strong> ' . esc_html( _n( 'course', 'courses', $course_count, 'CTADZ-school-management' ) ) . '</span>';
                                } else {
                                    echo '<span style="color: #999;">' . esc_html__( 'None', 'CTADZ-school-management' ) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ( $level->is_active ) : ?>
                                    <span style="color: #46b450;">● <?php esc_html_e( 'Active', 'CTADZ-school-management' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #dc3232;">● <?php esc_html_e( 'Inactive', 'CTADZ-school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo intval( $level->sort_order ); ?></td>
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
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this level?', 'CTADZ-school-management' ) ); ?>')">
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
                <?php esc_html_e( 'Back to Levels', 'CTADZ-school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Level', 'CTADZ-school-management' ) : esc_html__( 'Add New Level', 'CTADZ-school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_level_action', 'sm_save_level_nonce' ); ?>
            <input type="hidden" name="level_id" value="<?php echo esc_attr( $level->id ?? '' ); ?>" />

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="level_name"><?php esc_html_e( 'Level Name', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="level_name" name="name" value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'E.g., Beginner, Intermediate, Advanced', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="level_description"><?php esc_html_e( 'Description', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <textarea id="level_description" name="description" rows="4" class="large-text"><?php echo esc_textarea( $form_data['description'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Brief description of this level', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="level_sort_order"><?php esc_html_e( 'Sort Order', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="level_sort_order" name="sort_order" value="<?php echo esc_attr( $form_data['sort_order'] ?? 0 ); ?>" min="0" max="999" />
                        <p class="description"><?php esc_html_e( 'Lower numbers appear first in lists', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="level_is_active"><?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="level_is_active" name="is_active" value="1" <?php checked( $form_data['is_active'] ?? true ); ?> />
                            <?php esc_html_e( 'Active (available for courses and students)', 'CTADZ-school-management' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php if ( $is_edit ) : ?>
                    <?php submit_button( 
                        __( 'Update Level', 'CTADZ-school-management' ), 
                        'primary', 
                        'sm_save_level', 
                        false 
                    ); ?>
                <?php else : ?>
                    <?php submit_button( 
                        __( 'Save & Exit', 'CTADZ-school-management' ), 
                        'primary', 
                        'sm_save_level', 
                        false 
                    ); ?>
                    <?php submit_button( 
                        __( 'Save & Add New', 'CTADZ-school-management' ), 
                        'secondary', 
                        'sm_save_and_new', 
                        false,
                        [ 'style' => 'margin-left: 5px;' ]
                    ); ?>
                <?php endif; ?>
                <a href="?page=school-management-levels" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'CTADZ-school-management' ); ?></a>
            </p>
        </form>
        <?php
    }
}

// Instantiate class
new SM_Levels_Page();