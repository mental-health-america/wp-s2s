<?php
/**
 * Plugin Name: MHA - Shard
 * Plugin URI: http://mhascreening.org
 * Version: 1.0.0
 * Author: MHA Screening Web Team
 * Author URI: http://mhascreening.org
 * Description: A companion plugin for the MHA theme.
 */

//General Keys
include_once 'keys.php';

// General Wordpress Options
include_once 'inc/wp-cleanup.php';

// General Helper Functions
include_once 'inc/general.php';

// User Specific Functions
include_once 'inc/users.php';

// Plugin Specific Functions
include_once 'inc/plugin-overrides.php';

// Content Specific Functions
include_once 'inc/content.php';

// Custom Shortcodes
include_once 'inc/shortcodes.php';

// Article Specific Functions
include_once 'inc/articles.php';

// Submit Resources
include_once 'inc/submit-resources.php';

// Custom Admin Widgets
include_once 'inc/widgets.php';

// Geo Search for WP Query
include_once 'inc/geo-search.php';

// Newsletter signups
include_once 'inc/newsletter.php';

// Admin Specific Scripts
include_once 'inc/admin.php';

// Admin Specific Scripts
include_once 'inc/ab_testing.php';