<?php

class AOC_Log_Manager {
	private static $log_base_name = 'auto_order_cancellation';
	private static $log_dir;

	public static function init() {
		self::$log_dir = plugin_dir_path(__DIR__) . 'logs/';
		
		// Ensure the logs directory exists
		if (!file_exists(self::$log_dir)) {
			mkdir(self::$log_dir, 0755, true);
		}
	}

	// Get the current log file path based on date
	private static function get_current_log_file() {
		$base_log_file = self::$log_dir . self::$log_base_name . '.log';
		$date_suffix = date('Ymd');
		$date_log_file = self::$log_dir . self::$log_base_name . '_' . $date_suffix . '.log';

		// Rotate log if the base log file is from a previous date
		if (file_exists($base_log_file) && filemtime($base_log_file) < strtotime('today')) {
			rename($base_log_file, $date_log_file);
		}

		return $base_log_file;
	}

	// Write a message to the log file
	public static function write($message) {
		try {
			$log_file = self::get_current_log_file();
			$timestamp = '[' . date('Y-m-d H:i:s') . '] ';
			file_put_contents($log_file, $timestamp . $message . PHP_EOL, FILE_APPEND);
		} catch (Exception $e) {
			error_log('Error trying to write to logs: ' . $message . '\n' . $e);
		}
	}

	// Read the current log file contents
	public static function read() {
		$log_file = self::get_current_log_file();
		if (file_exists($log_file)) {
			return file_get_contents($log_file);
		}
		return '';
	}

	// Clear the current log file
	public static function clear() {
		$log_file = self::get_current_log_file();
		if (file_exists($log_file)) {
			file_put_contents($log_file, ''); // Truncate the file
		}
	}

	// Delete log files older than a specified number of days (e.g., 30 days)
	public static function delete_old_logs($days = 30) {
		$files = glob(self::$log_dir . self::$log_base_name . '_*.log');
		$expiration = time() - ($days * DAY_IN_SECONDS);

		foreach ($files as $file) {
			if (filemtime($file) < $expiration) {
				unlink($file); // Delete files older than the expiration time
			}
		}
	}

	// Function to delete old logs (called daily)
	public static function cleanup_logs() {
		self::delete_old_logs(30); // Delete logs older than 30 days
		self::write("Old logs cleaned up (older than 30 days).");
	}
}

// Initialize the log manager
AOC_Log_Manager::init();
