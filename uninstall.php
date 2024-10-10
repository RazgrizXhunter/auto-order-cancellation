<?php
// Exit if accessed directly.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// List of options to delete
$options = [
    'auto_order_cancellation_interval',
    'auto_order_cancellation_time',
];

// Delete each option
foreach ($options as $option) {
    delete_option($option);
}
