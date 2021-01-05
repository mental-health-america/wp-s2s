<?php
/**
 * Plugin Name: Mythril Shard
 * Plugin URI: http://mhascreening.org
 * Version: 1.0.0
 * Author: MHA Screening Web Team
 * Author URI: http://mhascreening.org
 * Description: A companion plugin for the MHA theme.
 */

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

// Custom Admin Widgets
include_once 'inc/widgets.php';

// Geo Search for WP Query
include_once 'inc/geo-search.php';

?>