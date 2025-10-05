<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Payment_Terms_Page {

    /**
     * Render the Payment Terms page
     */
    public static function render_payment_terms_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_payment_terms';

        // Handle delete action
        if ( isset( $_GET['delete'] ) && check_admin_referer( 'sm_delete_term_' . intval( $_GET['delete'] ) ) ) {
            // Check if term is being used by any teachers
            $teachers_table = $wpdb->prefix . 'sm_teachers';
            $term_id = intval( $_GET['delete'] );
            
            $teachers_using = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $teachers_table WHERE payment_term_id = %d", $term_id ) );
            
            if ( $teachers_using > 0 ) {
                echo '<div class="error notice"><p>' . sprintf( 
                    esc_html__( 'Cannot delete this payment term. It is being used by %d teacher(s).', 'school-management' ),
                    $teachers_using
                ) . '</p></div>';
            } else {
                $deleted = $wpdb->delete( $table, [ 'id' => $term_id ] );
                if ( $deleted ) {
                    echo '<div class="updated notice"><p>' . esc_html__( 'Payment term deleted successfully.', 'school-management' ) . '</p></div>';
                }
            }
        }

        // Handle form submission
        if ( isset( $_POST['sm_save_term'] ) && check_admin_referer( 'sm_save_term_action', 'sm_save_term_nonce' ) ) {
            $validation_result = self::validate_term_data( $_POST );
            
            if ( $validation_result['success'] ) {
                $data = $validation_result['data'];
                
                if ( ! empty( $_POST['term_id'] ) ) {
                    $updated = $wpdb->update( $table, $data, [ 'id' => intval( $_POST['term_id'] ) ] );
                    if ( $updated !== false ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Payment term updated successfully.', 'school-management' ) . '</p></div>';
                    }
                } else {
                    $inserted = $wpdb->insert( $table, $data );
                    if ( $inserted ) {
                        echo '<div class="updated notice"><p>' . esc_html__( 'Payment term added successfully.', 'school-management' ) . '</p></div>';
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
        $term = null;

        if ( $action === 'edit' && isset( $_GET['term_id'] ) ) {
            $term = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['term_id'] ) ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Payment Terms', 'school-management' ); ?></h1>

            <?php
            switch ( $action ) {
                case 'add':
                    self::render_term_form( null );
                    break;
                case 'edit':
                    self::render_term_form( $term );
                    break;
                default:
                    self::render_terms_list();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Validate term data
     */
    private static function validate_term_data( $post_data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_payment_terms';
        $errors = [];
        
        $name = sanitize_text_field( trim( $post_data['name'] ?? '' ) );
        $description = sanitize_textarea_field( trim( $post_data['description'] ?? '' ) );
        $percentage = floatval( $post_data['percentage'] ?? 100 );
        $sort_order = intval( $post_data['sort_order'] ?? 0 );
        $is_active = isset( $post_data['is_active'] ) ? 1 : 0;
        $term_id = intval( $post_data['term_id'] ?? 0 );

        // Validate name
        if ( empty( $name ) ) {
            $errors[] = __( 'Payment term name is required.', 'school-management' );
        }

        // Validate percentage
        if ( $percentage < 0 || $percentage > 100 ) {
            $errors[] = __( 'Percentage must be between 0 and 100.', 'school-management' );
        }

        // Check for duplicate name
        if ( ! empty( $name ) ) {
            $duplicate_query = "SELECT id FROM $table WHERE LOWER(name) = LOWER(%s)";
            $params = [ $name ];
            
            if ( $term_id > 0 ) {
                $duplicate_query .= " AND id != %d";
                $params[] = $term_id;
            }
            
            $duplicate = $wpdb->get_var( $wpdb->prepare( $duplicate_query, $params ) );
            if ( $duplicate ) {
                $errors[] = sprintf( __( 'A payment term with the name "%s" already exists.', 'school-management' ), $name );
            }
        }

        if ( empty( $errors ) ) {
            return [
                'success' => true,
                'data' => [
                    'name' => $name,
                    'description' => $description,
                    'percentage' => $percentage,
                    'sort_order' => $sort_order,
                    'is_active' => $is_active,
                ]
            ];
        }

        return [ 'success' => false, 'errors' => $errors ];
    }

    /**
     * Render terms list
     */
    private static function render_terms_list() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_payment_terms';
        $terms = $wpdb->get_results( "SELECT * FROM $table ORDER BY sort_order ASC, name ASC" );

        ?>
        <div class="sm-header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0;"><?php esc_html_e( 'Payment Terms', 'school-management' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Manage payment schedules for teachers', 'school-management' ); ?></p>
            </div>
            <div>
                <a href="?page=school-management-payment-terms&action=add" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Add New Payment Term', 'school-management' ); ?>
                </a>
            </div>
        </div>

        <?php if ( $terms ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Order', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Name', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Percentage', 'school-management' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'school-management' ); ?></th>
                        <th style="width: 150px;"><?php esc_html_e( 'Actions', 'school-management' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $terms as $term ) : ?>
                        <tr>
                            <td><?php echo intval( $term->sort_order ); ?></td>
                            <td><strong><?php echo esc_html( $term->name ); ?></strong></td>
                            <td><?php echo esc_html( $term->description ?: '—' ); ?></td>
                            <td><?php echo esc_html( number_format( $term->percentage, 2 ) . '%' ); ?></td>
                            <td>
                                <?php if ( $term->is_active ) : ?>
                                    <span style="color: #46b450;">● <?php esc_html_e( 'Active', 'school-management' ); ?></span>
                                <?php else : ?>
                                    <span style="color: #dc3232;">● <?php esc_html_e( 'Inactive', 'school-management' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?page=school-management-payment-terms&action=edit&term_id=<?php echo intval( $term->id ); ?>" class="button button-small">
                                    <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                                </a>
                                <?php
                                $delete_url = wp_nonce_url( 
                                    '?page=school-management-payment-terms&delete=' . intval( $term->id ), 
                                    'sm_delete_term_' . intval( $term->id ) 
                                );
                                ?>
                                <a href="<?php echo esc_url( $delete_url ); ?>" 
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this payment term?', 'school-management' ) ); ?>')">
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
     * Render term form
     */
    private static function render_term_form( $term = null ) {
        $is_edit = ! empty( $term );
        
        $form_data = [];
        if ( isset( $_POST['sm_save_term'] ) ) {
            $form_data = [
                'name' => sanitize_text_field( $_POST['name'] ?? '' ),
                'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
                'percentage' => floatval( $_POST['percentage'] ?? 100 ),
                'sort_order' => intval( $_POST['sort_order'] ?? 0 ),
                'is_active' => isset( $_POST['is_active'] ),
            ];
        } elseif ( $term ) {
            $form_data = [
                'name' => $term->name,
                'description' => $term->description,
                'percentage' => $term->percentage,
                'sort_order' => $term->sort_order,
                'is_active' => $term->is_active,
            ];
        }
        
        ?>
        <div class="sm-form-header" style="margin-bottom: 20px;">
            <a href="?page=school-management-payment-terms" class="button">
                <span class="dashicons dashicons-arrow-left-alt2" style="vertical-align: middle;"></span>
                <?php esc_html_e( 'Back to Payment Terms', 'school-management' ); ?>
            </a>
            <h2 style="display: inline-block; margin-left: 10px;">
                <?php echo $is_edit ? esc_html__( 'Edit Payment Term', 'school-management' ) : esc_html__( 'Add New Payment Term', 'school-management' ); ?>
            </h2>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'sm_save_term_action', 'sm_save_term_nonce' ); ?>
            <input type="hidden" name="term_id" value="<?php echo esc_attr( $term->id ?? '' ); ?>" />

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="term_name"><?php esc_html_e( 'Name', 'school-management' ); ?> <span style="color: #d63638;">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="term_name" name="name" value="<?php echo esc_attr( $form_data['name'] ?? '' ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'E.g., Monthly, Weekly, 50/50', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="term_description"><?php esc_html_e( 'Description', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <textarea id="term_description" name="description" rows="3" class="large-text"><?php echo esc_textarea( $form_data['description'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Brief description of this payment term', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="term_percentage"><?php esc_html_e( 'Percentage', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="term_percentage" name="percentage" value="<?php echo esc_attr( $form_data['percentage'] ?? 100 ); ?>" min="0" max="100" step="0.01" />
                        <span>%</span>
                        <p class="description"><?php esc_html_e( 'Payment percentage (e.g., 50 for 50/50, 33.33 for 1/3)', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="term_sort_order"><?php esc_html_e( 'Sort Order', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="term_sort_order" name="sort_order" value="<?php echo esc_attr( $form_data['sort_order'] ?? 0 ); ?>" min="0" />
                        <p class="description"><?php esc_html_e( 'Lower numbers appear first', 'school-management' ); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="term_is_active"><?php esc_html_e( 'Status', 'school-management' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="term_is_active" name="is_active" value="1" <?php checked( $form_data['is_active'] ?? true ); ?> />
                            <?php esc_html_e( 'Active (available for selection)', 'school-management' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <?php submit_button( 
                    $is_edit ? __( 'Update Payment Term', 'school-management' ) : __( 'Add Payment Term', 'school-management' ), 
                    'primary', 
                    'sm_save_term', 
                    false 
                ); ?>
                <a href="?page=school-management-payment-terms" class="button" style="margin-left: 10px;"><?php esc_html_e( 'Cancel', 'school-management' ); ?></a>
            </p>
        </form>
        <?php
    }
}

// Instantiate class
new SM_Payment_Terms_Page();