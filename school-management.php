<?php
/**
 * Plugin Name: School Management
 * Description: A modular school management system for courses, students, payments, and planning.
 * Version: 1.0.0
 * Author: Ahmed Sebaa
 * Text Domain: school-management
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'SM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the loader file.
require_once SM_PLUGIN_DIR . 'includes/sm-loader.php';

// Activation hook.
register_activation_hook( __FILE__, 'sm_activate_plugin' );
function sm_activate_plugin() {
    error_log("✅ School Management plugin activated");
}
