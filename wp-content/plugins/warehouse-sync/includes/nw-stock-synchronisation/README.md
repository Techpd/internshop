
# Newwave stock import

This feature ensures that only published products have their stock status updated by ASW. This process is automated using a cron job, which runs every 30 minutes by default. However, the interval can be customized on the newwave plugin settings page.

## Configuration

- Enable stock import: Go to the newwave plugin settings page in the admin panel and enable the stock import feature.
- Configure API Settings: Set API type - RPC or GraphQL, stock API endpoint url and stock API token.
- Define Cron Interval: Set the interval at which stock should be synchronized with the product stock available on the site.

## Functionality

- If the product is a variable product, the "Manage Stock" setting for each variant is set to 1. The stock status and stock quantity of each variant are updated accordingly. 

- If the stock value of a variable product or its variants is greater than 0, the stock status of the parent product is updated to "in stock". This ensures an accurate stock status representation for variable products.

- The stock quantity value is calculated based on the sum of regional availability (availabilityRegional) and product availability from the New Wave API.

- The cron job retrieves all published posts and passes their SKUs to ASW in batches of 50 products. 

## Customization

You can customize this feature further to meet your specific needs. You may want to add additional features or modify the behavior of order export based on your business requirements.

## Troubleshooting

If you encounter any issues or errors with this feature, you can refer to the logs( wp-content > uploads > wc-logs > new_wave_stock-logs) for more information. Logs can help diagnose and resolve any problems that may arise during order export.