<?php
/*
Plugin Name: Auto Order Cancellation
Description: Automatically cancels WooCommerce orders on hold for more than a specified time.
Version: 1.2
Author: Aldo Tapia
Text Domain: auto-order-cancellation
Domain Path: /languages
*/

// Define constants for paths and URLs
define('AOC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AOC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once AOC_PLUGIN_DIR . 'includes/class-log-manager.php';
require_once AOC_PLUGIN_DIR . 'includes/class-cron-handler.php';
require_once AOC_PLUGIN_DIR . 'includes/class-settings-page.php';

// Initialize the settings page within the admin_menu hook
add_action('admin_menu', ['AOC_Settings_Page', 'init_settings_page']);

// Initialize cron handler
register_activation_hook(__FILE__, ['AOC_Cron_Handler', 'activate_cron']);
register_deactivation_hook(__FILE__, ['AOC_Cron_Handler', 'deactivate_cron']);

// Add the action to update the cron schedule when the interval option is changed
add_action('update_option_auto_order_cancellation_interval', ['AOC_Cron_Handler', 'update_cron_schedule']);

// Load text domain for translations
function aoc_load_textdomain() {
	load_plugin_textdomain('auto-order-cancellation', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'aoc_load_textdomain');

// Enqueue styles and scripts for the admin area
function aoc_enqueue_admin_assets() {
	wp_enqueue_style('aoc-admin-styles', AOC_PLUGIN_URL . 'assets/css/admin-styles.css');
	wp_enqueue_script('aoc-admin-scripts', AOC_PLUGIN_URL . 'assets/js/admin-scripts.js', ['jquery'], false, true);
}
add_action('admin_enqueue_scripts', 'aoc_enqueue_admin_assets');
