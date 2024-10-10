<?php

class AOC_Settings_Page {    
	
	// Initialize the settings page
	public static function init_settings_page() {
		add_submenu_page(
			'woocommerce',
			'Auto Order Cancellation',
			'Auto Order Cancellation',
			'manage_options',
			'auto-order-cancellation',
			[self::class, 'render_settings_page']
		);
		
		// Register settings
		add_action('admin_init', [self::class, 'register_settings']);
	}

	// Register plugin settings
	public static function register_settings() {
		// Register the on-hold order time setting
		register_setting('auto_order_cancellation_settings', 'auto_order_cancellation_time');
	
		// Register the cron interval setting
		register_setting('auto_order_cancellation_settings', 'auto_order_cancellation_interval');
	
		// Main settings section
		add_settings_section(
			'auto_order_cancellation_main_section',
			'Auto Order Cancellation Settings',
			null,
			'auto-order-cancellation'
		);
	
		// Add input field for On-Hold Order Time
		add_settings_field(
			'auto_order_cancellation_time',
			'On-Hold Order Time (minutes)',
			[self::class, 'auto_order_cancellation_time_input'],
			'auto-order-cancellation',
			'auto_order_cancellation_main_section'
		);
	
		// Add dropdown field for Cron Interval
		add_settings_field(
			'auto_order_cancellation_interval',
			'Cron Interval',
			[self::class, 'auto_order_cancellation_interval_input'],
			'auto-order-cancellation',
			'auto_order_cancellation_main_section'
		);
	}

	// Render the input field for the On-Hold Order Time setting
	public static function auto_order_cancellation_time_input() {
		$time = get_option('auto_order_cancellation_time', 7200); // Default to 5 days
		echo '<input type="number" name="auto_order_cancellation_time" value="' . esc_attr($time) . '" min="1" />';
	}
	
	// Render the dropdown for the frequency setting
	public static function auto_order_cancellation_interval_input() {
		$intervals = [
			'hourly' => 'Hourly',
			'twicedaily' => 'Twice Daily',
			'daily' => 'Daily',
			'weekly' => 'Weekly',
		];
		$selected_interval = get_option('auto_order_cancellation_interval', 'daily'); // Default to daily
	
		echo '<select name="auto_order_cancellation_interval">';
		foreach ($intervals as $value => $label) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr($value),
				selected($selected_interval, $value, false),
				esc_html($label)
			);
		}
		echo '</select>';
	}

	// Render the settings page with tabs for Settings and Logs
	public static function render_settings_page() {
		$tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
		?>
		<div class="wrap aoc-plugin-settings">
			<h1>Auto Order Cancellation Settings</h1>
			
			<!-- Tabs -->
			<h2 class="nav-tab-wrapper">
				<a href="?page=auto-order-cancellation&tab=settings" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
				<a href="?page=auto-order-cancellation&tab=logs" class="nav-tab <?php echo $tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
			</h2>

			<!-- Settings Tab -->
			<?php if ($tab == 'settings') : ?>
				<form method="post" action="options.php">
					<?php
					settings_fields('auto_order_cancellation_settings');
					do_settings_sections('auto-order-cancellation');
					submit_button();
					?>
				</form>

			<!-- Logs Tab -->
			<?php elseif ($tab == 'logs') : ?>
				<h3>Log File Contents</h3>
				<div class="log-content">
					<?php
					$log_content = AOC_Log_Manager::read();
					if ($log_content) {
						echo nl2br(esc_html($log_content)); // Display log content with line breaks
					} else {
						echo '<p>The log file is empty.</p>';
					}
					?>
				</div>

				<!-- Clear Log Button -->
				<form method="post" action="" style="margin-top: 10px;">
					<input type="hidden" name="clear_log" value="1">
					<button type="submit" class="button">Clear Log</button>
				</form>

				<?php
				// Handle Clear Log request
				if (isset($_POST['clear_log'])) {
					AOC_Log_Manager::clear(); // Clear the log file
					echo '<div class="notice notice-success"><p>Log file cleared.</p></div>';
					echo '<script type="text/javascript">document.location.reload();</script>';
				}
				?>
			<?php endif; ?>
		</div>
		<?php
	}
}
