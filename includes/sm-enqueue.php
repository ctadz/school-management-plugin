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
        // List of our page slugs (these don't change with translation)
        $our_pages = [
            'school-management',           // Main dashboard
            'school-management-students',
            'school-management-courses',
            'school-management-teachers',
            'school-management-levels',
            'school-management-classrooms',
            'school-management-enrollments',
            'school-management-payment-terms',
            'school-management-payments',
            'school-management-settings',
        ];
        
        // Check if current hook contains any of our page slugs
        $is_our_page = false;
        foreach ( $our_pages as $page_slug ) {
            if ( strpos( $hook, $page_slug ) !== false ) {
                $is_our_page = true;
                break;
            }
        }
        
        // Not our page, don't load assets
        if ( ! $is_our_page ) {
            return;
        }

        // WordPress Media Uploader (required for image and file uploads)
        wp_enqueue_media();

        // Custom JavaScript
        wp_enqueue_script(
            'sm-admin-js',
            SM_PLUGIN_URL . 'assets/js/sm-admin.js',
            [ 'jquery' ],
            '1.0.3', // Incremented version
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
                'selectFile'    => __( 'Select a file', 'school-management' ),
                'usePicture'    => __( 'Use this picture', 'school-management' ),
                'useFile'       => __( 'Use this file', 'school-management' ),
            ]
        );

        // Custom CSS
        wp_enqueue_style(
            'sm-admin-css',
            SM_PLUGIN_URL . 'assets/css/sm-admin.css',
            [],
            '1.0.3' // Incremented version
        );
    }
}

// Initialize
new SM_Enqueue();