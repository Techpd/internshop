
# Newwave order export

This feature provides functionality for exporting orders to the New Wave API. It allows you to automate the process of sending order data to New Wave for further processing.

## Configuration

- Enable Order Export: Go to the newwave plugin settings page in the admin panel and enable the order export feature.
- Configure API Settings: Enter your New Wave API URL and API token in the plugin settings.
- Define Cron Interval: Set the interval at which orders should be exported to New Wave.
- Customize Order Prefix: You can customize the order prefix used in WooCommerce.
- Configure Default Customer: Define the default customer number for orders (used only when the shop feature is enabled).
- Customizing Shipping Behavior: Depending on your needs, you can configure shipping-related settings (used only when the shop feature is enabled).

## Features

- Exporting order data to the New Wave API.
- Customizing order export settings.
- Adding custom order resend functionality.
- Managing cron jobs for automated exports.
- Adding custom order columns and status information in the admin order list.

## Customization

You can customize this feature further to meet your specific needs. You may want to add additional features or modify the behavior of order export based on your business requirements.

## Troubleshooting

If you encounter any issues or errors with this feature, you can refer to the logs( wp-content > uploads > wc-logs > nw_order_export_logs) for more information. Logs can help diagnose and resolve any problems that may arise during order export.