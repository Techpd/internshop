
# Newwave order status tracking

This feature allows you to connect your WooCommerce store with nShift/Bring for tracking and managing orders.

## 1. nShift Integration

You can configure the nShift client ID, client secret, installation tags, actor tags, and order prefix through the WooCommerce settings.

## Usage 

Bulk Actions:
This feature adds custom bulk actions for changing the order status in WooCommerce. You can easily change the status of multiple orders in bulk using these actions.

Order Columns:
Additional columns for order tracking information, carrier name, and product name are added to the WooCommerce orders list.

Integration with nShift:
The integration with nShift involves fetching tracking information for orders, updating order metadata, and changing the order status based on tracking events.

## 2. Bring Integration

You can configure the posten URL, Bring API URL and order prefix in the newwave plugin settings page.

## Usage

Order Tracking: Customers can easily track their orders by clicking on the provided tracking URL, which will take them to the relevant tracking page.

Custom Order Status: Use the "Delivered" order status to mark orders as delivered when they reach their destination.

Taxonomy Integration: Custom taxonomies make it easy to filter and search for orders based on their status.

Custom Order Prefix: Configure a custom order prefix in the plugin settings to improve order organization.

Tracking URL Display: The plugin displays tracking URLs in the WordPress admin panel, providing quick access to shipment details.

## Features

- Use the custom bulk actions to change the order status.
- View the additional columns in the WooCommerce orders list for tracking information.
- Cron jobs will automatically check and update order statuses based on tracking events.

## Usage

Order Tracking: Customers can easily track their orders by clicking on the provided tracking URL, which will take them to the relevant tracking page.

Custom Order Status: Use the "Delivered" order status to mark orders as delivered when they reach their destination.

Taxonomy Integration: Custom taxonomies make it easy to filter and search for orders based on their status.

Custom Order Prefix: Configure a custom order prefix in the plugin settings to improve order organization.

Tracking URL Display: The plugin displays tracking URLs in the WordPress admin panel, providing quick access to shipment details.

## Customization

You can customize this integration further to meet your specific needs. You may want to add additional features or modify the behavior of order status updates based on your business requirements.

## Troubleshooting

If you encounter any issues or errors with this integration, you can refer to the logs( wp-content > uploads > wc-logs > bring_nw_logs_awaiting for Bring and nshift_logs and nshift_nw_logs_delivery for nShift) for more information. Logs can help diagnose and resolve any problems that may arise during order tracking and status updates.