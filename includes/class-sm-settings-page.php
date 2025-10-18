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
        // Register a single array option
        register_setting( 'sm_settings_group', 'sm_school_settings' );

        // Settings section
        add_settings_section(
            'sm_general_section',
            __( 'General Settings', 'school-management' ),
            '__return_false',
            'sm-settings'
        );

        // Fields
        add_settings_field(
            'school_name',
            __( 'School Name', 'school-management' ),
            [ __CLASS__, 'field_school_name' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'address',
            __( 'Address', 'school-management' ),
            [ __CLASS__, 'field_school_address' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'phone',
            __( 'Phone', 'school-management' ),
            [ __CLASS__, 'field_school_phone' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'email',
            __( 'Email', 'school-management' ),
            [ __CLASS__, 'field_school_email' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'logo',
            __( 'School Logo', 'school-management' ),
            [ __CLASS__, 'field_school_logo' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'date_format',
            __( 'Date Format', 'school-management' ),
            [ __CLASS__, 'field_date_format' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'time_format',
            __( 'Time Format', 'school-management' ),
            [ __CLASS__, 'field_time_format' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'first_day_of_week',
            __( 'First Day of the Week', 'school-management' ),
            [ __CLASS__, 'field_first_day_of_week' ],
            'sm-settings',
            'sm_general_section'
);
    }

    // Field callbacks
    public static function field_school_name() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['school_name'] ?? '';
        echo '<input type="text" name="sm_school_settings[school_name]" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    public static function field_school_address() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['address'] ?? '';
        echo '<textarea name="sm_school_settings[address]" rows="3" cols="50">' . esc_textarea( $value ) . '</textarea>';
    }

    public static function field_school_phone() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['phone'] ?? '';
        echo '<input type="text" name="sm_school_settings[phone]" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    public static function field_school_email() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['email'] ?? '';
        echo '<input type="email" name="sm_school_settings[email]" value="' . esc_attr( $value ) . '" class="regular-text" />';
    }

    public static function field_school_logo() {
        $settings = get_option( 'sm_school_settings', [] );
        $logo = $settings['logo'] ?? '';
        ?>
        <input type="text" id="sm_school_logo" name="sm_school_settings[logo]" value="<?php echo esc_attr( $logo ); ?>" style="width: 300px;" />
        <button type="button" class="button sm-upload-logo"><?php esc_html_e( 'Upload Logo', 'school-management' ); ?></button>
        <?php if ( $logo ) : ?>
            <div class="sm-logo-preview" style="margin-top:10px;">
                <img id="sm_school_logo_preview" src="<?php echo esc_url( $logo ); ?>" style="max-height:80px;" />
            </div>
        <?php endif;
    }

    public static function field_date_format() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['date_format'] ?? get_option('date_format');
        echo '<input type="text" name="sm_school_settings[date_format]" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Use PHP date format, e.g., Y-m-d, d/m/Y, m/d/Y', 'school-management' ) . '</p>';
    }

    public static function field_time_format() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['time_format'] ?? get_option('time_format');
        echo '<input type="text" name="sm_school_settings[time_format]" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Use PHP time format, e.g., H:i, g:i a', 'school-management' ) . '</p>';
    }

    public static function field_first_day_of_week() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['first_day_of_week'] ?? 'Sunday'; // default Sunday
        ?>
        <select name="sm_school_settings[first_day_of_week]">
        <option value="Sunday" <?php selected( $value, 'Sunday' ); ?>><?php esc_html_e( 'Sunday', 'school-management' ); ?></option>
        <option value="Monday" <?php selected( $value, 'Monday' ); ?>><?php esc_html_e( 'Monday', 'school-management' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Choose the first day of the week for calendars.', 'school-management' ); ?></p>
        <?php
    }

    // Render the settings page
    public static function render_settings_page() {
        // Security check - Settings ONLY for Site Administrators
        if ( ! current_user_can( 'manage_school_settings' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'school-management' ) );
        }

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
