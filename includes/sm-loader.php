<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load roles management
require_once SM_PLUGIN_DIR . 'includes/class-sm-roles.php';

// Load admin redirect and cleanup
require_once SM_PLUGIN_DIR . 'includes/class-sm-admin-redirect.php';

// Load Helper Functions
require_once SM_PLUGIN_DIR . 'includes/sm-helpers.php';


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

// Load Classrooms Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-classrooms-page.php';

// Load Enrollments Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-enrollments-page.php';

// Load Payments Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-payments-page.php';

// Load Payment Alerts Page
require_once SM_PLUGIN_DIR . 'includes/class-sm-payment-alerts-page.php';

// Load Payment Sync
require_once SM_PLUGIN_DIR . 'includes/class-sm-payment-sync.php';

// Load Enqueue scripts
require_once SM_PLUGIN_DIR . 'includes/sm-enqueue.php';
