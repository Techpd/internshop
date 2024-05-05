<?php
/**
* Manage Shops - template for page where user can register shops and switch between them
*
* This template can be overridden by copying it to yourtheme/woocommerce/myaccount/manage-shops.php.
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

<h1><?php _e('Manage clubs', 'newwave'); ?></h1>
<p><?php _e('Here you can switch between the clubs you are registered in, or register in another club if you have a register code.', 'newwave'); ?></p>

<form method="post">
	<input type="hidden" name="action" value="nw_switch_shop">
	<?php wp_nonce_field('nw_switch_shop', '_nw_switch_shop_nonce'); ?>

	<ul class="<?php echo esc_attr(apply_filters('newwave_user_shops_list_class', 'newwave-user-shops')); ?>">
	<?php foreach ($shops as $shop) : ?>
		<li>
			<label><?php echo esc_html($shop['name']); ?></label>
			<button type="submit" class="woocommerce-button button" name="nw_switch_shop_id" value="<?php echo $shop['id']; ?>" <?php if ($shop['current'] || !$shop['activated']) echo 'disabled="disabled"'; ?>>
			<?php
				if (!$shop['activated']) {
					_ex('Deactivated', 'Deativated club button, frontend', 'newwave');
				}
				else if ($shop['current']) {
					_ex('Current', 'Current club button, frontend', 'newwave');
				}
				else {
					_ex('Switch', 'Switch club button, frontend', 'newwave');
				}
			?>
			</button>
	<?php endforeach; ?>
	</ul>
</form>

<form method="post">
	<input type="hidden" name="action" value="nw_register_shop">
	<?php wp_nonce_field('nw_register_shop', '_nw_register_shop_nonce'); ?>
	<input type="text" id="newwave-register-code" name="nw_shop_reg_code" />
	<input type="submit" id="newwave-register-new-shop" class="woocommerce-button button <?php echo esc_attr(apply_filters('newwave_register_new_shop_button_class', 'register-new-shop-button-class')); ?>" value="<?php _e('Register shop', 'newwave'); ?>" />
</form>
