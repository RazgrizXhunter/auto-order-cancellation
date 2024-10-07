<?php
/*
Plugin Name: Auto Order Cancellation
Description: Automatically cancels WooCommerce orders on hold for more than a specified time and frequency.
Version: 1.0
Author: Aldo Tapia
*/

// Schedule event upon plugin activation
register_activation_hook(__FILE__, 'auto_order_cancellation_schedule');
register_deactivation_hook(__FILE__, 'auto_order_cancellation_unschedule');

// Schedule the cron job based on selected frequency
function auto_order_cancellation_schedule() {
    $frequency = get_option('auto_order_cancellation_frequency', 'daily'); // Default to 'daily'
    if (!wp_next_scheduled('check_pending_orders')) {
        wp_schedule_event(time(), $frequency, 'check_pending_orders');
    }
}

// Unschedule the event upon plugin deactivation
function auto_order_cancellation_unschedule() {
    $timestamp = wp_next_scheduled('check_pending_orders');
    wp_unschedule_event($timestamp, 'check_pending_orders');
}

// Cancel orders on hold for too long
function cancel_old_on_hold_orders() {
    if (class_exists('WooCommerce')) {
        // Define the log file path
        $log_file = plugin_dir_path(__FILE__) . 'auto_order_cancellation.log';

        // Get the custom time in minutes from settings
        $time_in_minutes = get_option('auto_order_cancellation_time', 7200); // Default to 5 days
        $log_entry = 'Auto Order Cancellation Time (minutes): ' . $time_in_minutes . PHP_EOL;

        // Convert the time to seconds
        $time_in_seconds = $time_in_minutes * 60;

        // Get the current time based on WordPress timezone
        $current_time = current_time('timestamp');

        // Calculate the cancellation threshold time in Unix timestamp format
        $cancel_time = $current_time - $time_in_seconds;

        // Convert the timestamp to a date string in UTC format
        $cancel_date = gmdate('Y-m-d H:i:s', $cancel_time);

        // Add debugging information to the log entry
        $log_entry .= 'Current Time (WordPress timezone): ' . date('Y-m-d H:i:s', $current_time) . PHP_EOL;
        $log_entry .= 'Cancel Orders Before (timestamp): ' . $cancel_time . ' (' . $cancel_date . ' UTC)' . PHP_EOL;

        // Get orders on hold with date_created before the calculated timestamp
        $args = array(
            'status' => 'on-hold',
            'date_created' => '<=' . $cancel_date, // Use UTC date format for accurate filtering
            'limit' => -1,
        );

        $orders = wc_get_orders($args);

        foreach ($orders as $order) {
            $order_date = $order->get_date_created()->date('Y-m-d H:i:s');
            $log_entry .= 'Order ID: ' . $order->get_id() . ' - Created On: ' . $order_date . ' - Cancellation Threshold: ' . $cancel_date . PHP_EOL;

            // Only cancel if the order's creation date is before or equal to the cancel threshold
            if ($order_date <= $cancel_date) {
                $order->update_status('cancelled', 'Order automatically cancelled due to being on hold for too long');
                $log_entry .= 'Order ID: ' . $order->get_id() . ' cancelled.' . PHP_EOL;
            }
        }

        // Write log entry to file
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}
add_action('check_pending_orders', 'cancel_old_on_hold_orders');


// Add a submenu under the WooCommerce menu
function auto_order_cancellation_menu_page() {
    add_submenu_page(
        'woocommerce',
        'Auto Order Cancellation',
        'Auto Order Cancellation',
        'manage_options',
        'auto-order-cancellation',
        'auto_order_cancellation_render_settings_page'
    );
}
add_action('admin_menu', 'auto_order_cancellation_menu_page');

// Render the settings page
function auto_order_cancellation_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Auto Order Cancellation Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('auto_order_cancellation_settings');
            do_settings_sections('auto-order-cancellation');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register the settings
function auto_order_cancellation_register_settings() {
    // Register settings
    register_setting('auto_order_cancellation_settings', 'auto_order_cancellation_time');
    register_setting('auto_order_cancellation_settings', 'auto_order_cancellation_frequency');

    // Add a section for the settings
    add_settings_section('auto_order_cancellation_main_section', 'Auto Order Cancellation Settings', null, 'auto-order-cancellation');

    // Time setting field
    add_settings_field('auto_order_cancellation_time', 'On-Hold Order Time (minutes)', 'auto_order_cancellation_time_input', 'auto-order-cancellation', 'auto_order_cancellation_main_section');

    // Frequency setting field
    add_settings_field('auto_order_cancellation_frequency', 'Frequency', 'auto_order_cancellation_frequency_input', 'auto-order-cancellation', 'auto_order_cancellation_main_section');
}
add_action('admin_init', 'auto_order_cancellation_register_settings');

// Input field for time setting
function auto_order_cancellation_time_input() {
    $time = get_option('auto_order_cancellation_time', 7200); // Default to 5 days
    echo '<input type="number" name="auto_order_cancellation_time" value="' . esc_attr($time) . '" min="1" />';
}

// Dropdown for frequency setting
function auto_order_cancellation_frequency_input() {
    $frequency = get_option('auto_order_cancellation_frequency', 'daily');
    $options = array(
        'every_minute' => 'Every 1 Minute',
        'five_minutes' => 'Every 5 Minutes',
        'fifteen_minutes' => 'Every 15 Minutes',
        'hourly' => 'Hourly',
        'daily' => 'Daily',
    );

    echo '<select name="auto_order_cancellation_frequency">';
    foreach ($options as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($frequency, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

// Add custom intervals
function auto_order_cancellation_custom_intervals($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display' => __('Every 1 Minute')
    );
    $schedules['five_minutes'] = array(
        'interval' => 300,
        'display' => __('Every 5 Minutes')
    );
    $schedules['fifteen_minutes'] = array(
        'interval' => 900,
        'display' => __('Every 15 Minutes')
    );
    return $schedules;
}
add_filter('cron_schedules', 'auto_order_cancellation_custom_intervals');

// Reschedule cron job if frequency changes
function auto_order_cancellation_reschedule() {
    auto_order_cancellation_unschedule();
    auto_order_cancellation_schedule();
}
add_action('update_option_auto_order_cancellation_frequency', 'auto_order_cancellation_reschedule');

