<?php
/*
Plugin Name: Site Report
Plugin URI:  https://github.com/UjjwalShres/Site-Report
Description: Audits your WordPress site for database, performance, security, and file system stats, with one-click export to HTML, PDF, JSON, CSV, or TXT.
Version:     1.0.0
Author:      Ujjwal Shrestha
Author URI:  https://ujjwal-shrestha.com.np/
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: site-report
*/

if (!defined('ABSPATH')) exit;

define('SITE_REPORT_PATH', plugin_dir_path(__FILE__));
define('SITE_REPORT_URL', plugin_dir_url(__FILE__));

// Include classes
require_once SITE_REPORT_PATH . 'admin/class-admin.php';
require_once SITE_REPORT_PATH . 'includes/class-db.php';
require_once SITE_REPORT_PATH . 'includes/class-performance.php';
require_once SITE_REPORT_PATH . 'includes/class-health-evaluator.php';
require_once SITE_REPORT_PATH . 'includes/class-data.php';
require_once SITE_REPORT_PATH . 'includes/class-report.php';


// initialize menu only
add_action('admin_menu', ['Site_Report_Admin', 'init']);

// register export EARLY
add_action('admin_post_sr_export', ['Site_Report_Admin', 'handle_export']);