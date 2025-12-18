<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SM_Enqueue {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_head', [ $this, 'admin_custom_styles' ] );
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

        // Chart.js for data visualizations
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        // Custom JavaScript
        wp_enqueue_script(
            'sm-admin-js',
            SM_PLUGIN_URL . 'assets/js/sm-admin.js',
            [ 'jquery', 'chartjs' ],
            '1.0.4', // Incremented version for dashboard improvements
            true
        );

        // Localize JavaScript strings for translations
        wp_localize_script(
            'sm-admin-js',
            'sm_i18n',
            [
                'uploadSuccess' => __( 'Upload successful!', 'CTADZ-school-management' ),
                'uploadError'   => __( 'Upload failed. Please try again.', 'CTADZ-school-management' ),
                'selectLogo'    => __( 'Select a logo', 'CTADZ-school-management' ),
                'selectPicture' => __( 'Select a picture', 'CTADZ-school-management' ),
                'selectFile'    => __( 'Select a file', 'CTADZ-school-management' ),
                'usePicture'    => __( 'Use this picture', 'CTADZ-school-management' ),
                'useFile'       => __( 'Use this file', 'CTADZ-school-management' ),
            ]
        );

        // ===== NEW: AJAX Localization for Enrollment Payment Model Connection =====
        // Localize script for AJAX calls (enrollments page)
        wp_localize_script( 'jquery', 'smAjax', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'sm_enrollment_nonce' ),
            'strings' => [
                'loading' => __( 'Loading...', 'CTADZ-school-management' ),
                'error' => __( 'An error occurred. Please try again.', 'CTADZ-school-management' ),
                'selectCourse' => __( 'Please select a course first', 'CTADZ-school-management' ),
            ]
        ] );
        // ===== END NEW =====

        // Custom CSS
        wp_enqueue_style(
            'sm-admin-css',
            SM_PLUGIN_URL . 'assets/css/sm-admin.css',
            [],
            '2.0.0' // Major update: Mobile-responsive redesign
        );
    }

    /**
     * Add custom admin styles
     * NEW: Styles for course payment info display
     */
    public function admin_custom_styles() {
        // Only output on school management pages
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'CTADZ-school-management' ) === false ) {
            return;
        }
        ?>
        <style>
            /* ===== Course Payment Info Box Styles ===== */
            .sm-course-payment-info {
                background: #f0f6fc;
                border-left: 4px solid #0073aa;
                padding: 15px;
                margin: 15px 0;
                border-radius: 4px;
            }
            
            .sm-course-payment-info h4 {
                margin: 0 0 10px 0;
                color: #0073aa;
                font-size: 14px;
            }
            
            .sm-course-payment-info p {
                margin: 5px 0;
                font-size: 13px;
            }
            
            .sm-course-payment-info .sm-payment-model-badge {
                display: inline-block;
                background: #0073aa;
                color: white;
                padding: 4px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
                margin-bottom: 10px;
            }
            
            .sm-course-payment-info.subscription {
                background: #fff8e5;
                border-left-color: #f0b500;
            }
            
            .sm-course-payment-info.subscription .sm-payment-model-badge {
                background: #f0b500;
                color: #1d2327;
            }
            
            .sm-payment-plan-option[disabled] {
                opacity: 0.5;
                cursor: not-allowed;
            }
            
            /* Loading spinner for AJAX */
            .sm-loading {
                display: inline-block;
                margin-left: 10px;
            }
            
            .sm-loading::after {
                content: "";
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #0073aa;
                border-radius: 50%;
                animation: sm-spin 1s linear infinite;
            }
            
            @keyframes sm-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
        <?php
    }
}

// Initialize
new SM_Enqueue();
