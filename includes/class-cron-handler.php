<?php

class AOC_Cron_Handler {

	// Hook to initialize the cron events
	public static function activate_cron() {
		// Get selected interval or set default
		$interval = get_option('auto_order_cancellation_interval', 'daily'); // Default to daily

		// Schedule order cancellation check
		if (!wp_next_scheduled('aoc_check_pending_orders')) {
			wp_schedule_event(time() + 30 * MINUTE_IN_SECONDS, $interval, 'aoc_check_pending_orders');
		}

		// Schedule daily log cleanup
		if (!wp_next_scheduled('aoc_log_cleanup')) {
			wp_schedule_event(time(), 'daily', 'aoc_log_cleanup');
		}
	}

	// Hook to clear the cron events upon deactivation
	public static function deactivate_cron() {
		// Clear the aoc_check_pending_orders event
		$is_orders_cron_scheduled = wp_next_scheduled('aoc_check_pending_orders') ? TRUE : FALSE;
		if ($is_orders_cron_scheduled) {
			wp_clear_scheduled_hook('aoc_check_pending_orders');
		}

		// Clear the aoc_log_cleanup event
		$is_orders_cleanup_scheduled = wp_next_scheduled('aoc_log_cleanup') ? TRUE : FALSE;
		if ($is_orders_cleanup_scheduled) {
			wp_clear_scheduled_hook('aoc_log_cleanup');
		}
	}

	// Hook to reschedule cron on settings update
	public static function update_cron_schedule() {
		// Clear all scheduled instances of the event
		while (wp_next_scheduled('aoc_check_pending_orders')) {
			$timestamp = wp_next_scheduled('aoc_check_pending_orders');
			AOC_Log_Manager::write("Cron found. ($timestamp)");
			AOC_Log_Manager::write("Trying to remove cron...");
			$result = wp_clear_scheduled_hook('aoc_check_pending_orders');
			AOC_Log_Manager::write("wp_clear_scheduled_hook: $result");
			$timestamp = wp_next_scheduled('aoc_check_pending_orders');
			AOC_Log_Manager::write("timestamp: $timestamp");
		}
	
		// Get the new interval from settings
		$interval = get_option('auto_order_cancellation_interval', 'daily'); // Default to daily
		
		AOC_Log_Manager::write("Cron tried to be rescheduled, new interval: $interval");
	
		// Schedule the cron event with the new interval
		wp_schedule_event(time(), $interval, 'aoc_check_pending_orders');
	}

	// Function to cancel old on-hold orders
	public static function cancel_old_on_hold_orders() {
		if (!class_exists('WooCommerce')) {
			return;
		}

		// Retrieve the auto-cancellation time from settings
		$time_in_minutes = get_option('auto_order_cancellation_time', 60 * 24 * 30); // Default: 30 Days
		AOC_Log_Manager::write("Auto Order Cancellation Time (minutes): $time_in_minutes");

		// Calculate the cancellation threshold
		$time_in_seconds = $time_in_minutes * 60;
		$current_time = current_time('timestamp');
		$cancel_time = $current_time - $time_in_seconds;
		$cancel_date = gmdate('Y-m-d H:i:s', $cancel_time);

		// Log details for debugging
		AOC_Log_Manager::write("Current Time (WordPress timezone): " . date('Y-m-d H:i:s', $current_time));
		AOC_Log_Manager::write("Cancel Orders Before: $cancel_time ($cancel_date UTC)");

		// Query orders on hold that should be cancelled
		$args = [
			'status'			=> 'on-hold',
			'date_created'		=> '<=' . $cancel_date,
			'limit'				=> -1,
			'payment_method'	=> 'bacs',
		];

		$orders = wc_get_orders($args);

		foreach ($orders as $order) {
			$order_date = $order->get_date_created()->date('Y-m-d H:i:s');
			AOC_Log_Manager::write("Order ID: " . $order->get_id() . " - Created On: $order_date - Cancellation Threshold: $cancel_date");

			// Cancel order if creation date is before the threshold
			if ($order_date <= $cancel_date) {
				$order->update_status('cancelled', 'Order automatically cancelled due to being on hold for too long');
				AOC_Log_Manager::write("Order ID: " . $order->get_id() . " cancelled.");
			}
		}
	}
}

// Register the cron actions
add_action('aoc_check_pending_orders', ['AOC_Cron_Handler', 'cancel_old_on_hold_orders']);
add_action('aoc_log_cleanup', ['AOC_Log_Manager', 'cleanup_logs']);
