<?php

if (!defined('ABSPATH')) exit;
wp_enqueue_script('wc-password-strength-meter');

?>
<form class="craft-register" method="post" class="register" id="craft-register">

	<?php do_action( 'woocommerce_register_form_start' ); ?>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" id="register_form_errors" style="display: none;"></p>

	<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide craft-wide">
			<label for="reg_username"><?php _e( 'Username', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( $_POST['username'] ) : ''; ?>" />
		</p>

	<?php endif; ?>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide craft-wide">
		<label for="reg_email"><?php _e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
		<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( $_POST['email'] ) : ''; ?>" />
	</p>

	<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide craft-wide">
			<label for="reg_password"><?php _e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" />
		</p>

	<?php endif; ?>

	<?php do_action( 'woocommerce_register_form' ); ?>

	<p class="woocommerce-FormRow form-row craft-wide craft-flex craft-flex-center submit-btn-row">
		<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
		<input type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>" />
	</p>

	<?php do_action( 'woocommerce_register_form_end' ); ?>

</form>
