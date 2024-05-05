<?php
/**
 * Checkout billing information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/newwave-klarna-shipping-fields.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package NewWave/Templates
 * @version 3.0.9
 */

if (!defined('ABSPATH')) exit;

/** @global array $nw_shipping_fields */

?>
<h3 id="newwave-klarna-shipping-destination-title">
	<?php _e('Delivery', 'newwave'); ?>
</h3>

<?php do_action('newwave_before_shipping_destination'); ?>
<div class="newwave-klarna-shipping-destination">
	<?php foreach ($nw_shipping_fields as $name => $field) : ?>

	<div class="newwave-shipping-option">
		<input type="radio" name="nw_shipping_destination" id="nw_shipping_destination_<?php echo $name; ?>" value="<?php echo $name; ?>" <?php checked($field['checked']); ?>/>
		<span class="craft-radio-button" class="<?php if ($field['checked']) echo 'checked'; ?>"></span>
		<div>
			<label for="nw_shipping_destination_<?php echo $name; ?>">
				<?php echo $field['title']; ?>
			</label>
			<p><?php echo $field['address']; ?></p>
		</div>
	</div>

	<?php endforeach; ?>
</div>

<?php do_action('newwave_after_shipping_fields'); ?>
