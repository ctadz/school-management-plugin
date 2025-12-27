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
            __( 'General Settings', 'CTADZ-school-management' ),
            '__return_false',
            'sm-settings'
        );

        // Fields
        add_settings_field(
            'school_name',
            __( 'School Name', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_school_name' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'address',
            __( 'Address', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_school_address' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'phone',
            __( 'Phone', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_school_phone' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'email',
            __( 'Email', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_school_email' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'logo',
            __( 'School Logo', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_school_logo' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'date_format',
            __( 'Date Format', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_date_format' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'time_format',
            __( 'Time Format', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_time_format' ],
            'sm-settings',
            'sm_general_section'
        );

        add_settings_field(
            'first_day_of_week',
            __( 'First Day of the Week', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_first_day_of_week' ],
            'sm-settings',
            'sm_general_section'
        );

        // Family Discount section
        add_settings_section(
            'sm_family_discount_section',
            __( 'Family Discount Settings', 'CTADZ-school-management' ),
            [ __CLASS__, 'family_discount_section_callback' ],
            'sm-settings'
        );

        add_settings_field(
            'family_discount_enabled',
            __( 'Enable Family Discounts', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_family_discount_enabled' ],
            'sm-settings',
            'sm_family_discount_section'
        );

        add_settings_field(
            'family_discount_tiers',
            __( 'Discount Tiers', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_family_discount_tiers' ],
            'sm-settings',
            'sm_family_discount_section'
        );

        add_settings_field(
            'family_discount_max_cap',
            __( 'Maximum Discount Cap', 'CTADZ-school-management' ),
            [ __CLASS__, 'field_family_discount_max_cap' ],
            'sm-settings',
            'sm_family_discount_section'
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
        <input type="text" id="sm_school_logo" name="sm_school_settings[logo]" value="<?php echo esc_attr( $logo ); ?>" class="regular-text" />
        <button type="button" class="button sm-upload-logo"><?php esc_html_e( 'Upload Logo', 'CTADZ-school-management' ); ?></button>
        <?php if ( $logo ) : ?>
            <div class="sm-logo-preview mt-10">
                <img id="sm_school_logo_preview" src="<?php echo esc_url( $logo ); ?>" alt="<?php esc_attr_e( 'School Logo', 'CTADZ-school-management' ); ?>" style="max-height:80px;" />
            </div>
        <?php endif;
    }

    public static function field_date_format() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['date_format'] ?? get_option('date_format');
        echo '<input type="text" name="sm_school_settings[date_format]" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Use PHP date format, e.g., Y-m-d, d/m/Y, m/d/Y', 'CTADZ-school-management' ) . '</p>';
    }

    public static function field_time_format() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['time_format'] ?? get_option('time_format');
        echo '<input type="text" name="sm_school_settings[time_format]" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Use PHP time format, e.g., H:i, g:i a', 'CTADZ-school-management' ) . '</p>';
    }

    public static function field_first_day_of_week() {
        $settings = get_option( 'sm_school_settings', [] );
        $value = $settings['first_day_of_week'] ?? 'Sunday'; // default Sunday
        ?>
        <select name="sm_school_settings[first_day_of_week]">
        <option value="Sunday" <?php selected( $value, 'Sunday' ); ?>><?php esc_html_e( 'Sunday', 'CTADZ-school-management' ); ?></option>
        <option value="Monday" <?php selected( $value, 'Monday' ); ?>><?php esc_html_e( 'Monday', 'CTADZ-school-management' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Choose the first day of the week for calendars.', 'CTADZ-school-management' ); ?></p>
        <?php
    }

    // Family Discount section callback
    public static function family_discount_section_callback() {
        echo '<p>' . esc_html__( 'Configure automatic family discounts based on the number of enrolled siblings (identified by parent phone number).', 'CTADZ-school-management' ) . '</p>';
    }

    public static function field_family_discount_enabled() {
        $settings = get_option( 'sm_school_settings', [] );
        $enabled = isset( $settings['family_discount_enabled'] ) && $settings['family_discount_enabled'] === 'yes';
        ?>
        <label>
            <input type="checkbox" name="sm_school_settings[family_discount_enabled]" value="yes" <?php checked( $enabled ); ?> />
            <?php esc_html_e( 'Enable automatic family discounts', 'CTADZ-school-management' ); ?>
        </label>
        <p class="description"><?php esc_html_e( 'When enabled, students with the same parent phone number will automatically receive family discounts based on the tiers below.', 'CTADZ-school-management' ); ?></p>
        <?php
    }

    public static function field_family_discount_tiers() {
        $settings = get_option( 'sm_school_settings', [] );
        $tiers = $settings['family_discount_tiers'] ?? [
            [ 'students' => 2, 'discount' => 5 ],
            [ 'students' => 3, 'discount' => 10 ]
        ];
        ?>
        <table class="wp-list-table widefat fixed striped" id="sm-discount-tiers-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Number of Students', 'CTADZ-school-management' ); ?></th>
                    <th><?php esc_html_e( 'Discount %', 'CTADZ-school-management' ); ?></th>
                    <th style="width: 100px;"><?php esc_html_e( 'Actions', 'CTADZ-school-management' ); ?></th>
                </tr>
            </thead>
            <tbody id="sm-discount-tiers-body">
                <?php foreach ( $tiers as $index => $tier ) : ?>
                    <tr data-index="<?php echo esc_attr( $index ); ?>">
                        <td>
                            <input type="number"
                                   name="sm_school_settings[family_discount_tiers][<?php echo esc_attr( $index ); ?>][students]"
                                   value="<?php echo esc_attr( $tier['students'] ); ?>"
                                   min="2"
                                   class="small-text"
                                   required />
                            <?php esc_html_e( 'or more students', 'CTADZ-school-management' ); ?>
                        </td>
                        <td>
                            <input type="number"
                                   name="sm_school_settings[family_discount_tiers][<?php echo esc_attr( $index ); ?>][discount]"
                                   value="<?php echo esc_attr( $tier['discount'] ); ?>"
                                   min="0"
                                   max="100"
                                   step="0.01"
                                   class="small-text"
                                   required /> %
                        </td>
                        <td>
                            <button type="button" class="button sm-remove-tier"><?php esc_html_e( 'Remove', 'CTADZ-school-management' ); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <button type="button" class="button" id="sm-add-tier"><?php esc_html_e( 'Add Tier', 'CTADZ-school-management' ); ?></button>
        </p>
        <p class="description"><?php esc_html_e( 'Define discount percentages based on the number of family members enrolled. Tiers are applied automatically based on the highest matching tier.', 'CTADZ-school-management' ); ?></p>

        <script>
        jQuery(document).ready(function($) {
            let tierIndex = <?php echo count( $tiers ); ?>;

            // Add new tier
            $('#sm-add-tier').on('click', function() {
                const newRow = `
                    <tr data-index="${tierIndex}">
                        <td>
                            <input type="number"
                                   name="sm_school_settings[family_discount_tiers][${tierIndex}][students]"
                                   value="2"
                                   min="2"
                                   class="small-text"
                                   required />
                            <?php esc_html_e( 'or more students', 'CTADZ-school-management' ); ?>
                        </td>
                        <td>
                            <input type="number"
                                   name="sm_school_settings[family_discount_tiers][${tierIndex}][discount]"
                                   value="5"
                                   min="0"
                                   max="100"
                                   step="0.01"
                                   class="small-text"
                                   required /> %
                        </td>
                        <td>
                            <button type="button" class="button sm-remove-tier"><?php esc_html_e( 'Remove', 'CTADZ-school-management' ); ?></button>
                        </td>
                    </tr>
                `;
                $('#sm-discount-tiers-body').append(newRow);
                tierIndex++;
            });

            // Remove tier
            $(document).on('click', '.sm-remove-tier', function() {
                if ($('#sm-discount-tiers-body tr').length > 1) {
                    $(this).closest('tr').remove();
                } else {
                    alert('<?php esc_html_e( 'At least one discount tier is required.', 'CTADZ-school-management' ); ?>');
                }
            });
        });
        </script>
        <?php
    }

    public static function field_family_discount_max_cap() {
        $settings = get_option( 'sm_school_settings', [] );
        $max_cap = $settings['family_discount_max_cap'] ?? 10;
        ?>
        <input type="number"
               name="sm_school_settings[family_discount_max_cap]"
               value="<?php echo esc_attr( $max_cap ); ?>"
               min="0"
               max="100"
               step="0.01"
               class="small-text"
               required /> %
        <p class="description">
            <?php esc_html_e( 'Maximum discount percentage that can be applied to any family, regardless of tier configuration. The highest configured tier will apply to families exceeding the highest tier threshold.', 'CTADZ-school-management' ); ?>
        </p>
        <?php
    }

    // Render the settings page
    public static function render_settings_page() {
        // Security check - Settings ONLY for Site Administrators
        if ( ! current_user_can( 'manage_school_settings' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'CTADZ-school-management' ) );
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'School Management Settings', 'CTADZ-school-management' ); ?></h1>
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
