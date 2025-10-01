<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Enqueue {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function enqueue_admin_assets( $hook ) {
        // Define our plugin pages - more precise hook checking
        $plugin_pages = [
            'toplevel_page_school-management',                    // Dashboard
            'school-management_page_school-management-students',  // Students
            'school-management_page_school-management-settings',  // Settings
        ];

        // Load only on our plugin pages
        if ( ! in_array( $hook, $plugin_pages ) ) {
            return;
        }

        // WordPress Media Uploader (required for image uploads)
        wp_enqueue_media();

        // Custom JavaScript
        wp_enqueue_script(
            'sm-admin-js',
            SM_PLUGIN_URL . 'assets/js/sm-admin.js',
            [ 'jquery' ],
            '1.0.1', // Increment version to force refresh
            true
        );

        // Localize JavaScript strings for translations
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

        // Custom CSS
        wp_enqueue_style(
            'sm-admin-css',
            SM_PLUGIN_URL . 'assets/css/sm-admin.css',
            [],
            '1.0.1' // Increment version to force refresh
        );
    }
}

// Initialize
new SM_Enqueue();