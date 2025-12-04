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
        // Security check
        if ( ! current_user_can( 'manage_classrooms' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'CTADZ-school-management' ) );
        }

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
                    esc_html__( 'Cannot delete this classroom. It is being used by %d course(s).', 'CTADZ-school-management' ),
                    $courses_using
                ) . '</p></div>';
            } else {
                $deleted = $wpdb->delete( $table, [ 'id' => $classroom_id ] );
                if ( $deleted ) {
                    echo '<div class="updated notice"><p>' . esc_html__( 'Classroom deleted successfully.', 'CTADZ-school-management' ) . '</p></div>';
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
                        echo '<div class="updated notice"><p>' . esc_html__( 'Classroom updated successfully.', 'CTADZ-school-management' ) . '</p></div>';
                        echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-classrooms"; }, 1500);</script>';
                    }
                } else {
                    // Add mode
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Classroom added successfully.', 'CTADZ-school-management' ) . '</p></div>';
                        
                        if ( $save_and_new ) {
                            echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-classrooms&action=add"; }, 1500);</script>';
                        } else {
                            echo '<script>setTimeout(function(){ window.location.href = "?page=school-management-classrooms"; }, 1500);</script>';
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
        $classroom = null;

        if ( $action === 'edit' && isset( $_GET['classroom_id'] ) ) {
            $classroom = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['classroom_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Classrooms', 'CTADZ-school-management' ); ?></h1>

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
            $errors[] = __( 'Classroom name is required.', 'CTADZ-school-management' );
        } elseif ( strlen( $name ) < 2 ) {
            $errors[] = __( 'Classroom name must be at least 2 characters long.', 'CTADZ-school-management' );
        }

        // Validate capacity
        if ( $capacity < 0 ) {
            $errors[] = __( 'Capacity cannot be negative.', 'CTADZ-school-management' );
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
                $errors[] = sprintf( __( 'A classroom with the name "%s" already exists.', 'CTADZ-school-management' ), $name );
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
                "WHERE (c.name LIKE %s OR c.location LIKE %s OR c.facilities LIKE %s)", 
                $search_term, 
                $search_term,
                $search_term
            );
        }

        $total_classrooms = $wpdb->get_var( "SELECT COUNT(*) FROM $classrooms_table c $where_clause" );
        $total_pages = ceil( $total_classrooms / $per_page );

        // Validate and set ORDER BY clause
        $valid_columns = [
            'name' => 'c.name',
            'capacity' => 'c.capacity',
            'location' => 'c.location',
            'course_count' => 'course_count',
            'status' => 'c.is_active'
        ];
        $orderby_column = isset( $valid_columns[ $orderby ] ) ? $valid_columns[ $orderby ] : 'c.name';
        $order_clause = "$orderby_column $order";

        // Get classrooms with course count
        $classrooms = $wpdb->get_results( $wpdb->prepare( 
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM $courses_table WHERE classroom_id = c.id) as course_count
             FROM $classrooms_table c 
             $where_clause
             ORDER BY $order_clause
             LIMIT %d OFFSET %d", 
            $per_page, 
            $offset 
        ) );

        // Helper function to generate sortable column URL
        $get_sort_url = function( $column ) use ( $orderby, $order, $search ) {
            $new_order = ( $orderby === $column && $order === 'ASC' ) ? 'DESC' : 'ASC';
            $url = add_query_arg( [
                'page' => 'school-management-classrooms',
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
                <h2 style="margin: 0;"><?php esc_html_e( 'Classrooms List', 'CTADZ-school-management' ); ?></h2>
                <p class="description">
                    <?php 
                    if ( ! empty( $search ) ) {
                        printf( esc_html__( 'Showing %d classrooms matching "%s"', 'CTADZ-school-management' ), $total_classrooms, esc_html( $search ) );
                        echo ' <a href="?page=school-management-classrooms" style="margin-left: 10px;">' . esc_html__( '[Clear search]', 'CTADZ-school-management' ) . '</a>';
                    } else {
                        printf( esc_html__( 'Total: %d classrooms', 'CTADZ-school-management' ), $total_classrooms );
                    }
                    ?>
                </p>
            </div>
            <div>
                <a href="?page=school-management-classrooms&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Classroom', 'CTADZ-school-management' ); ?>
                </a>
            </div>
        </div>

        <!-- Search Box -->
        <div class="tablenav top" style="margin-bottom: 15px;">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="page" value="school-management-classrooms">
                <?php if ( ! empty( $orderby ) ) : ?>
                    <input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>">
                    <input type="hidden" name="order" value="<?php echo esc_attr( $order ); ?>">
                <?php endif; ?>
                <input type="search" 
                       name="s" 
                       value="<?php echo esc_attr( $search ); ?>" 
                       placeholder="<?php esc_attr_e( 'Search classrooms by name, location, or facilities...', 'CTADZ-school-management' ); ?>"
                       style="width: 350px; margin-right: 5px;">
                <button type="submit" class="button"><?php esc_html_e( 'Search', 'CTADZ-school-management' ); ?></button>
                <?php if ( ! empty( $search ) ) : ?>
                    <a href="?page=school-management-classrooms" class="button" style="margin-left: 5px;">
                        <?php esc_html_e( 'Clear', 'CTADZ-school-management' ); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ( $classrooms ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="<?php echo $orderby === 'name' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'name' ); ?>">
                                <?php esc_html_e( 'Classroom Name', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'name' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'capacity' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'capacity' ); ?>">
                                <?php esc_html_e( 'Capacity', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'capacity' ); ?>
                            </a>
                        </th>
                        <th class="<?php echo $orderby === 'location' ? 'sorted' : 'sortable'; ?>">
                            <a href="<?php echo $get_sort_url( 'location' ); ?>">
                                <?php esc_html_e( 'Location', 'CTADZ-school-management' ); ?><?php echo $get_sort_indicator( 'location' ); ?>
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
                        <th class="non-sortable" style="width: 150px;"><?php esc_html_e( 'Actions', 'CTADZ-school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $classrooms as $classroom ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $classroom->name ); ?></strong></td>
                            <td><?php echo intval( $classroom->capacity ); ?> <?php esc_html_e( 'students', 'CTADZ-school-management' ); ?></td>
                            <td><?php echo esc_html( $classroom->location ?: '—' ); ?></td>
                            <td><?php echo intval( $classroom->course_count ); ?> <?php esc_html_e( 'course(s)', 'CTADZ-school-management' ); ?></td>
                            <td>
                                <?php if ( $classroom->is_active ) : ?>
                                    <span style="color: #46b450;">● <?php esc_html_e( 'Active', 'CTADZ-school-management' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #dc3232;">● <?php esc_html_e( 'Inactive', 'CTADZ-school-management' ); ?></span>
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
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this classroom?', 'CTADZ-school-management' ) ); ?>')">
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
                <span class="dashicons dashicons-building" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 16px;"></span>
                <h3><?php esc_html_e( 'No Classrooms Yet', 'CTADZ-school-management' ); ?></h3>
                <p><?php esc_html_e( 'Add classrooms to assign courses to physical locations.', 'CTADZ-school-management' ); ?></p>
                <a href="?page=school-management-classrooms&action=add" class="button button-primary">
                    <?php esc_html_e( 'Add First Classroom', 'CTADZ-school-management' ); ?>
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
                <?php esc_html_e( 'Back to Classrooms', 'CTADZ-school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Classroom', 'CTADZ-school-management' ) : esc_html__( 'Add New Classroom', 'CTADZ-school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_classroom_action', 'sm_save_classroom_nonce' ); ?>
            <input type="hidden" name="classroom_id" value="<?php echo esc_attr( $classroom->id ?? '' ); ?>" />

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="classroom_name"><?php esc_html_e( 'Classroom Name', 'CTADZ-school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="classroom_name" name="name" value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'E.g., Room 101, Lab A, Auditorium', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="classroom_capacity"><?php esc_html_e( 'Capacity', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="classroom_capacity" name="capacity" value="<?php echo esc_attr( $form_data['capacity'] ?? 0 ); ?>" min="0" max="999" style="width: 100px;" />
                        <span><?php esc_html_e( 'students', 'CTADZ-school-management' ); ?></span>
                        <p class="description"><?php esc_html_e( 'Maximum number of students this classroom can accommodate.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="classroom_location"><?php esc_html_e( 'Location/Building', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="classroom_location" name="location" value="<?php echo esc_attr( $form_data['location'] ?? '' ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'E.g., Building A - Floor 2, Main Campus', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="classroom_facilities"><?php esc_html_e( 'Facilities/Equipment', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <textarea id="classroom_facilities" name="facilities" rows="4" class="large-text"><?php echo esc_textarea( $form_data['facilities'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'List available equipment: projector, computers, whiteboard, etc.', 'CTADZ-school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="classroom_is_active"><?php esc_html_e( 'Status', 'CTADZ-school-management' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="classroom_is_active" name="is_active" value="1" <?php checked( $form_data['is_active'] ?? true ); ?> />
                            <?php esc_html_e( 'Active (available for course assignment)', 'CTADZ-school-management' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php if ( $is_edit ) : ?>
                    <?php submit_button( 
                        __( 'Update Classroom', 'CTADZ-school-management' ), 
                        'primary', 
                        'sm_save_classroom', 
                        false 
                    ); ?>
                <?php else : ?>
                    <?php submit_button( 
                        __( 'Save & Exit', 'CTADZ-school-management' ), 
                        'primary', 
                        'sm_save_classroom', 
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
                <a href="?page=school-management-classrooms" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'CTADZ-school-management' ); ?></a>
            </p>
            
            <p class="description">
                <span style="color: #d63638;">*</span> <?php esc_html_e( 'Required fields', 'CTADZ-school-management' ); ?>
            </p>
        </form>
        <?php
    }
}

// Instantiate class
new SM_Classrooms_Page();