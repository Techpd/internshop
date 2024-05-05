<form class="woocommerce-form woocommerce-form-login login craft-login" method="post" id="craft-login">
	<?php do_action( 'woocommerce_login_form_start' ); ?>

	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" id="login_form_errors" style="display: none;"></p>
	
	<div class="wrap">
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="username"><?php echo 'BRUKERNAVN'; //_e( 'Username or email address', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input type="text" class="woocommerce-Input woocommerce-Input--text input-text login-field" name="username" id="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( $_POST['username'] ) : ''; ?>" />
			<span class="username-error login-errors username-error" style="display:none;"><?= __('Brukernavnet er ugyldig.');?></span>
		</p>
	</div>
	
	<div class="wrap">
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="password"><?php echo 'PASSORD';//_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
			<input class="woocommerce-Input woocommerce-Input--text input-text login-field" type="password" name="password" id="password" />
			<span class="password-error login-errors password-error" style="display:none;"><?= __('Passordet er ugyldig.');?></span>
		</p>
	</div>

	<?php do_action( 'woocommerce_login_form' ); ?>

	<div class="form-row craft-flex craft-flex-center">
		<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
		<span class="craft-width-third"></span>
		<!-- <span class="craft-width-third">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox inline">
				<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php //_e( 'Remember me', 'woocommerce' ); ?></span>
			</label>
		</span> -->

		<div class="row">
			<p class="woocommerce-register-bedrift">
				<a href="#craft-register-form" class="register-bedrift-btn" style="display:none;" >Registrer bruker</a>
			</p>
	
			<p class="woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php _e( 'Glemt Passord?', 'woocommerce' ); ?></a>
			</p>		
		</div>

	</div>

	<div class="submit-container">
		<span class="craft-width-third craft-text-center">
			<input type="submit" class="woocommerce-Button button" name="login" id="popup_login" value="<?php esc_attr_e( 'LOGG INN', 'woocommerce' ); ?>" />
		</span>
	</div>

	<?php do_action( 'woocommerce_login_form_end' ); ?>

	<div class="form-footer-content">
		<p>INTERNSHOP</p>
	</div>

</form>
