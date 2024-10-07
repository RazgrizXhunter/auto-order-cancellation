# Auto Order Cancellation

Automatically cancels WooCommerce orders that are on hold for a specified amount of time.

## Description

The **Auto Order Cancellation** plugin checks WooCommerce orders with an "on-hold" status and automatically cancels them if they exceed a specified time limit. This is useful for e-commerce stores that want to free up pending orders or reduce unpaid orders held for too long.

## Features

- Automatically cancels orders with an "on-hold" status based on a customizable time limit.
- Allows administrators to set the cancellation delay in minutes.
- Option to control how frequently the check runs, from every minute to daily.
- Integrates with the WooCommerce menu for easy access to settings.
- Logs important events to a dedicated log file within the plugin folder.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/auto-order-cancellation` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to **WooCommerce > Auto Order Cancellation** to configure settings.

## Usage

1. After activation, navigate to **WooCommerce > Auto Order Cancellation**.
2. Set the **On-Hold Order Time** in minutes. This is the time limit for orders to remain on hold before being automatically canceled.
3. Set the **Frequency** for the cron job to specify how often the plugin checks for orders to cancel.
4. Save your settings.

## Settings

### On-Hold Order Time (minutes)

- Defines how long an order can remain in the "on-hold" status before it is automatically canceled.
- Example: Setting this to 7200 (default) means orders will be canceled if they have been on hold for more than 5 days.

### Frequency

- Sets how frequently the plugin checks for orders that meet the cancellation criteria.
- Options range from **Every 1 Minute** to **Daily**.

## Logging

The plugin logs key events to `auto_order_cancellation.log` in the plugin folder. The log file captures:

- **Current Time**: Logs the current time in the WordPress timezone.
- **Cancel Orders Before**: Shows the cancellation threshold time in Unix timestamp and human-readable UTC format.
- **Order Details**: Logs each order’s ID and creation date, and confirms whether it meets the criteria for cancellation.

To view logs, open the `auto_order_cancellation.log` file located in the plugin folder (`wp-content/plugins/auto-order-cancellation/`).

### Example Log Output

```plaintext
Auto Order Cancellation Time (minutes): 7200
Current Time (WordPress timezone): 2024-10-07 20:55:58
Cancel Orders Before (timestamp): 1728326092 (2024-10-02 20:55:58 UTC)
Order ID: 123 - Created On: 2024-09-28 15:20:10 - Cancellation Threshold: 2024-10-02 20:55:58
Order ID: 123 cancelled.
```
