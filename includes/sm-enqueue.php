<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Enqueue {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    public function enqueue_admin_scripts( $hook ) {
        // Load only on our plugin’s pages
        if ( strpos( $hook, 'school-management' ) === false ) {
            return;
        }

        // Enqueue WordPress Media Uploader
        wp_enqueue_media();

        // Enqueue custom JS
        wp_enqueue_script(
            'sm-admin-js',
            SM_PLUGIN_URL . 'assets/js/sm-admin.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );

        // Localize strings for translation
        wp_localize_script(
            'sm-admin-js',
            'sm_i18n',
            [
                'uploadSuccess' => __( 'Upload successful!', 'school-management' ),
                'uploadError'   => __( 'Upload failed. Please try again.', 'school-management' ),
                'selectLogo'    => __( 'Select a logo', 'school-management' ),
            ]
        );

        // Optional: enqueue custom CSS
        wp_enqueue_style(
            'sm-admin-css',
            SM_PLUGIN_URL . 'assets/css/sm-admin.css',
            [],
            '1.0.0'
        );
    }
}

// Initialize
new SM_Enqueue();
