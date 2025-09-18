<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Settings_Page {

    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function register_settings() {
        // Example: register school logo option
        register_setting( 'sm_settings_group', 'sm_school_logo' );

        add_settings_section(
            'sm_general_section',
            __( 'General Settings', 'school-management' ),
            '__return_false',
            'sm-settings'
        );

        add_settings_field(
            'sm_school_logo',
            __( 'School Logo', 'school-management' ),
            [ __CLASS__, 'field_school_logo' ],
            'sm-settings',
            'sm_general_section'
        );
    }

    public static function field_school_logo() {
        $logo = get_option( 'sm_school_logo', '' );
        ?>
        <input type="text" id="sm_school_logo" name="sm_school_logo" value="<?php echo esc_attr( $logo ); ?>" style="width: 300px;" />
        <button type="button" class="button sm-upload-logo"><?php esc_html_e( 'Upload Logo', 'school-management' ); ?></button>
        <?php
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'School Management Settings', 'school-management' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'sm_settings_group' );
                do_settings_sections( 'sm-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

SM_Settings_Page::init();
