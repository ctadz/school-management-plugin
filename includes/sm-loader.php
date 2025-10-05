<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load Admin Menu
require_once SM_PLUGIN_DIR . 'includes/class-sm-admin-menu.php';

// Load Settings Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-settings-page.php';

// Load Students Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-students-page.php';

// Load Levels Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-levels-page.php';

// Load Payment Terms Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-payment-terms-page.php';

// Load Teachers Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-teachers-page.php';

// Load Courses Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-courses-page.php';

// Load Enqueue scripts
require_once SM_PLUGIN_DIR . 'includes/sm-enqueue.php';