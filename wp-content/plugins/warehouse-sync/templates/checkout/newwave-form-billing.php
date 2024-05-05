<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-billing.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;


/** @global WC_Checkout $checkout */

?>
<div class="woocommerce-billing-fields <?php echo esc_attr(apply_filters('newwave_shipping_address_class', 'newwave-shipping-fields')); ?>">
	<h3><?php _e('Shipping address', 'newwave'); ?></h3>
	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">
		<?php
			$fields = $checkout->get_checkout_fields('billing');

			if (!empty($fields)) :

			// This shouldn't really occur as 'customer' alone is not a valid shipping setting for a shop
			if (count($fields) == 1 && isset($fields['customer'])) : ?>
				<p class="incomplete-address"><?php _e('Your address is incomplete', 'newwave'); ?><br><a href="<?php echo esc_attr(wc_customer_edit_account_url()); ?>"><?php _e('Edit it here', 'newwave'); ?></a></p>

			<?php else :

				foreach ($fields as $value => $field) :
				$customer_address_incomplete = 'customer' == $value && isset($fields['customer']['incomplete']) && $fields['customer']['incomplete'] == true;

				?><div class="<?php echo esc_attr(apply_filters('newwave_shipping_address_box_class', 'newwave-shipping-address-box')); if ($customer_address_incomplete) echo ' address-is-incomplete'; ?>">

					<input name="nw_shipping_destination" type="radio" id="newwave-<?php echo $value; ?>" value="<?php echo $value; ?>" required/>
					<div class="text-box">
						<label type="radio" for="newwave-<?php echo $value; ?>" value="<?php echo esc_attr($value); ?>">
							<h4><?php echo esc_html($field['title']); ?></h4>

							<address>
							<?php foreach ($field['address_fields'] as $address_field) : ?>
								<?php echo esc_html($address_field); ?><br/>
							<?php endforeach; ?>
						</address>
						</label>

						<?php if ($customer_address_incomplete) : ?>
							<p class="incomplete-address"><?php _ex('Your name or address is incomplete', 'Checkout', 'newwave'); ?><br><a href="<?php echo esc_attr(wc_customer_edit_account_url()); ?>"><?php _e('Click here to edit it', 'newwave'); ?></a></p>
						<?php endif;?>
					</div>
				</div>
			<?php endforeach; endif;
		endif;
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

<script>
jQuery(document).ready(function($) {
	$('#nw_shipping_destination_field input[type="radio"]:checked').next('label').css({'opacity' : 1});
	$('#nw_shipping_destination_field label').on('click', function() {
		$('#nw_shipping_destination_field label').css({'opacity' : 0.4});
		$(this).css({'opacity' : 1});
	});
});
</script>
