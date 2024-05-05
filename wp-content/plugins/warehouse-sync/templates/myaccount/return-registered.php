<?php
/**
 * Return Registered
 *
 * Shows the result of the customer requested return of products
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/return-registered.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @package NewWave/Templates
 * @version 3.3.0
 */

if (!defined('ABSPATH')) exit;
?>

<h1><?php _e('Return of products', 'newwave'); ?></h1>
<p><?php _e('The return of the products have been registered.<br/> Mark the returning post package with the reference number below.', 'newwave'); ?></p>
<h3><?php printf(__('Reference number: %s', 'newwave'), $return_code); ?></h3>

<section class="woocommerce-order-details newwave-return-registered">
	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
		<thead>
			<tr>
				<th class="woocommerce-table__product-name product-name"><?php _e('Products', 'newwave' ); ?></th>
				<th class="woocommerce-table__product-table product-total"><?php _e('Quantity', 'newwave' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($items as $item) : ?>
				<tr class="woocommerce_order_item_class order-item">
					<td class="woocommerce-table__product-name product-name"><?php echo $item->get_name(); ?> <strong></strong>
					</td>
					<td>&times;<?php echo $item->get_quantity(); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<a href="<?php echo(esc_attr($order->get_view_order_url())); ?>" class="woocommerce-button button <?php echo esc_attr(apply_filters('newwave_back_to_order_class', 'return-to-order')); ?>"><?php _e('View order', 'newwave'); ?></a>
</section>
