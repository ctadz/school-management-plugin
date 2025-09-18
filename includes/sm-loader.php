<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load Admin Menu.
require_once SM_PLUGIN_DIR . 'includes/class-sm-admin-menu.php';

// Load Settings Page.
require_once SM_PLUGIN_DIR . 'includes/class-sm-settings-page.php';

// Load Enqueue scripts.
require_once SM_PLUGIN_DIR . 'includes/sm-enqueue.php';