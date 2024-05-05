<?php
/**
 * Return Registered Notice
 *
 * Sent as notice to site admin when a return have been registered
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/return-registered-notice.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @package NewWave/Templates
 * @version 1.0
 */

if (!defined('ABSPATH')) exit;
$text_align = is_rtl() ? 'right' : 'left';
?>

<h2><?php printf(__('Return code: %s', 'newwave'), $return_code); ?></h2>
<p><?php printf(__('<strong>%s</strong> in club <strong>%s</strong> from vendor <strong>%s</strong> have registered return of the following products:', 'newwave'), $customer_name, $club_name, $vendor_name); ?></p>
<br/>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo $text_align;?>"><?php _e('Name', 'newwave'); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo $text_align;?>"><?php _e('Quantity', 'newwave'); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo $text_align;?>"><?php _e('Product number', 'newwave'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($returned_items as $item) : ?>
			<tr>
				<td><?php echo $item->get_name(); ?></td>
				<td><?php echo $item->get_quantity(); ?></td>
				<td><?php echo $item->get_meta('_nw_sku'); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
