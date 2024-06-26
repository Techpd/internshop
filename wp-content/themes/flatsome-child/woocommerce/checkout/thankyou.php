<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see              https://docs.woocommerce.com/document/template-structure/
 * @package          WooCommerce\Templates
 * @version          8.1.0
 * @flatsome-version 3.17.7
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="row xy">

	<?php if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>
		<div class="large-12 col order-failed">
			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</p>
		</div>

		<?php else : ?>
    <div class="large-7 col xxy">
			<?php
				$get_payment_method = $order->get_payment_method();
				$get_order_id       = $order->get_id();
			?>

			<p class="tyfuo">TAKK FOR DIN BESTILLING</p>

			<div class="text-block">
				<p class="customer-name">Hei <span><?php echo $order->get_billing_first_name().' '.$order->get_billing_last_name(); ?></span>, </p>
				<p class="body-text">
					Orderen din er mottatt og blir nå behandlet.
				</p>
			</div>

			<div class="row">
				<div class="col">
					<div class="order-details">
						<p class="ordrno"> Ordrenr <span>#<?php echo $order->get_order_number(); ?></span></p>
						<?php 
$var = $order->get_date_created();
    $order_date= date("d/m/Y", strtotime($var) );
						?>
					<!-- 	<p class="date"><?php //echo wc_format_datetime( $order->get_date_created() );?></p> -->
						<p class="date"><?php echo $order_date;?></p>
						<p class="type">Ordrestatus: Bekreftet</p>
					</div>
					<div class="address">
						<?php
$shipping_first_name = $order->get_shipping_first_name();
$shipping_last_name  = $order->get_shipping_last_name();	
$shipping_address_2  = $order->get_shipping_address_2();
$shipping_address_1  = $order->get_shipping_address_1();
$shipping_city       = $order->get_shipping_city();
$shipping_state      = $order->get_shipping_state();
$shipping_postcode   = $order->get_shipping_postcode();
$shipping_country    = $order->get_shipping_country();

	 ?>
						<span>Leveringsadresse</span>
						<?php echo $shipping_first_name.' '.$shipping_last_name.',<br/>'.$shipping_address_1."<br/>".$shipping_address_2." ".$shipping_postcode." ".$shipping_city." ".$shipping_state ." " ;?>
					</div>
				</div>
				<div class="col logo">
					<img src="<?php echo nw_get_club_logo_src(); ?>" alt="" class="club-logo">
				</div>
			</div>

			<?php do_action( 'woocommerce_thankyou', $get_order_id ); ?>
			
			<div class="footerx">

				<p class="link-text">
					Lurer du på noe? ta <a href="mailto:internshop@newwave.no"> kontakt med oss.</a>
				</p>
				<a href="https://staging.internshop.no/butikk/" class="cta-btn">FORTSETT Å HANDLE</a>
				<p class="desc">30 dagers returmulighet (gjelder ikke logo produkter) | Fri frakt på kjøp over 500kr</p>

			</div>

			<?php //do_action( 'woocommerce_thankyou_' . $get_payment_method, $get_order_id ); ?>

    </div>

		<div class="large-5 col xxz" style="display:none">
			<div class="is-well col-inner entry-content">
				<p class="success-color woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><strong><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong></p>

				<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

					<li class="woocommerce-order-overview__order order">
						<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>

						<li class="woocommerce-order-overview__date date">
							<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
							<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
						</li>

						<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
							<li class="woocommerce-order-overview__email email">
								<?php esc_html_e( 'Email:', 'woocommerce' ); ?>
								<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
							</li>
						<?php endif; ?>

					<li class="woocommerce-order-overview__total total">
						<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>

					<?php
					$payment_method_title = $order->get_payment_method_title();
					if ( $payment_method_title ) :
					?>
						<li class="woocommerce-order-overview__payment-method method">
							<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
							<strong><?php echo wp_kses_post( $payment_method_title ); ?></strong>
						</li>
					<?php endif; ?>

				</ul>

				<div class="clear"></div>
			</div>
		</div>

		<?php endif; ?>

	<?php else : ?>

		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

	<?php endif; ?>

</div>
