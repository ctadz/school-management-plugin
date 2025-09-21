<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Students_Page {

    public function __construct() {
        // Enqueue only on plugin admin pages
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue JS and CSS for the Students page
     */
    public function enqueue_assets( $hook ) {
        // Load assets on any admin page that belongs to our plugin.
        // This is robust to submenu hook naming.
        if ( strpos( $hook, 'school-management' ) === false ) {
            return;
        }

        // Ensure the WP media scripts are available
        wp_enqueue_media();

        // Use the plugin URL constant to build asset paths (safer than relative '../')
        if ( defined( 'SM_PLUGIN_URL' ) ) {
            $base = rtrim( SM_PLUGIN_URL, '/' ) . '/';
        } else {
            $base = plugin_dir_url( dirname( __FILE__ ) ) . '/';
        }

        // Enqueue the main admin JS (contains uploader logic for logo & student picture)
        wp_enqueue_script(
            'sm-admin-js',
            $base . 'assets/js/sm-admin.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );

        // Localize strings if not already localized elsewhere (safe duplicate)
        wp_localize_script(
            'sm-admin-js',
            'sm_i18n',
            [
                'uploadSuccess' => __( 'Upload successful!', 'school-management' ),
                'uploadError'   => __( 'Upload failed. Please try again.', 'school-management' ),
                'selectLogo'    => __( 'Select a logo', 'school-management' ),
                'selectPicture' => __( 'Select a picture', 'school-management' ),
                'usePicture'    => __( 'Use this picture', 'school-management' ),
            ]
        );

        // Enqueue single admin CSS (we decided to keep one CSS file)
        wp_enqueue_style(
            'sm-admin-css',
            $base . 'assets/css/sm-admin.css',
            [],
            '1.0.0'
        );
    }

    /**
     * Render the Students page
     */
    public static function render_students_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sm_students';

        // Handle form submission
        if ( isset( $_POST['sm_save_student'] ) && check_admin_referer( 'sm_save_student_action', 'sm_save_student_nonce' ) ) {
            $data = [
                'name'       => sanitize_text_field( $_POST['name'] ),
                'email'      => sanitize_email( $_POST['email'] ),
                'phone'      => sanitize_text_field( $_POST['phone'] ),
                'dob'        => sanitize_text_field( $_POST['dob'] ),
                'level'      => sanitize_text_field( $_POST['level'] ),
                'picture'    => esc_url_raw( $_POST['picture'] ?? '' ),
                'blood_type' => sanitize_text_field( $_POST['blood_type'] ?? '' ),
            ];

            if ( ! empty( $_POST['student_id'] ) ) {
                $wpdb->update( $table, $data, [ 'id' => intval( $_POST['student_id'] ) ] );
            } else {
                $wpdb->insert( $table, $data );
            }

            echo '<div class="updated notice"><p>' . esc_html__( 'Student saved successfully.', 'school-management' ) . '</p></div>';
        }

        // Edit mode
        $student = null;
        if ( isset( $_GET['edit'] ) ) {
            $student = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_GET['edit'] ) ) );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Students', 'school-management' ); ?></h1>

            <form method="post">
                <?php wp_nonce_field( 'sm_save_student_action', 'sm_save_student_nonce' ); ?>
                <input type="hidden" name="student_id" value="<?php echo esc_attr( $student->id ?? '' ); ?>" />

                <table class="form-table">
                    <tr>
                        <td colspan="2" style="position: relative;">
                            <!-- Picture top-right -->
                            <div id="sm_student_picture_box">
                                <?php if ( ! empty( $student->picture ) ) : ?>
                                    <img id="sm_student_picture_preview" src="<?php echo esc_url( $student->picture ); ?>" alt="<?php esc_attr_e( 'Student Picture', 'school-management' ); ?>" />
                                <?php else : ?>
                                    <span><?php esc_html_e( 'Click to upload', 'school-management' ); ?></span>
                                    <img id="sm_student_picture_preview" src="" style="display:none;" alt="<?php esc_attr_e( 'Student Picture', 'school-management' ); ?>" />
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="picture" id="sm_student_picture" value="<?php echo esc_attr( $student->picture ?? '' ); ?>" />
                        </td>
                    </tr>

                    <tr>
                        <th><label for="student_name"><?php esc_html_e( 'Name', 'school-management' ); ?></label></th>
                        <td><input type="text" id="student_name" name="name" value="<?php echo esc_attr( $student->name ?? '' ); ?>" required /></td>
                    </tr>

                    <tr>
                        <th><label for="student_email"><?php esc_html_e( 'Email', 'school-management' ); ?></label></th>
                        <td><input type="email" id="student_email" name="email" value="<?php echo esc_attr( $student->email ?? '' ); ?>" required /></td>
                    </tr>

                    <tr>
                        <th><label for="student_phone"><?php esc_html_e( 'Phone', 'school-management' ); ?></label></th>
                        <td><input type="text" id="student_phone" name="phone" value="<?php echo esc_attr( $student->phone ?? '' ); ?>" required /></td>
                    </tr>

                    <tr>
                        <th><label for="student_dob"><?php esc_html_e( 'Date of Birth', 'school-management' ); ?></label></th>
                        <td><input type="date" id="student_dob" name="dob" value="<?php echo esc_attr( $student->dob ?? '' ); ?>" required /></td>
                    </tr>

                    <tr>
                        <th><label for="student_level"><?php esc_html_e( 'Level', 'school-management' ); ?></label></th>
                        <td><input type="text" id="student_level" name="level" value="<?php echo esc_attr( $student->level ?? '' ); ?>" required /></td>
                    </tr>

                    <tr>
                        <th><label for="student_blood_type"><?php esc_html_e( 'Blood Type', 'school-management' ); ?></label></th>
                        <td>
                            <select name="blood_type" id="student_blood_type">
                                <?php
                                $types = [ 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-' ];
                                $selected = $student->blood_type ?? '';
                                foreach ( $types as $type ) {
                                    echo '<option value="' . esc_attr( $type ) . '" ' . selected( $selected, $type, false ) . '>' . esc_html( $type ) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save Student', 'school-management' ), 'primary', 'sm_save_student' ); ?>
            </form>
        </div>
        <?php
    }
}

// Instantiate class
new SM_Students_Page();
