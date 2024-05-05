<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see              https://docs.woocommerce.com/document/template-structure/
 * @package          WooCommerce/Templates
 * @version          3.5.0
 * @flatsome-version 3.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

?>
<style>
.grid-container-element { 
    display: grid; 
    grid-template-columns: 1fr 1fr;  
} 
.grid-child-element { 
    margin: 10px; 
    border: 1px solid grey; 
	padding: 10px;
}
</style>

<?php 
	//$fields = get_user_meta(get_current_user_id(),'',true); print_r($fields); exit;
	$billing_first_name = get_user_meta(get_current_user_id(),'billing_first_name',true);
	$billing_last_name 	= get_user_meta(get_current_user_id(),'billing_last_name',true);
	$billing_address_1 	= get_user_meta(get_current_user_id(),'billing_address_1',true);
	$billing_postcode 	= get_user_meta(get_current_user_id(),'billing_postcode',true);
	$billing_city 		= get_user_meta(get_current_user_id(),'billing_city',true);
	$billing_phone 		= get_user_meta(get_current_user_id(),'billing_phone',true);
	
	add_action( 'woocommerce_checkout_after_order_review', 'woocommerce_checkout_payment', 30 );
?>
  <div class="grid-container-element">
    
    <div class="grid-child-element purple">
      <p class="grid-title">CART</p>
      <div class="grid-child-element-x">
        <div id="order_review" class="woocommerce-checkout-review-order">
          <?php
            remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
            do_action( 'woocommerce_checkout_order_review' );
          ?>
        </div>
      </div>      
    </div>
	
  <div class="grid-child-element green">  
    <p class="grid-title">PAYMENT</p> 
    <div class="grid-child-element-x">
      <div id="pay_by_invoice">
        <form id="payment_checkout_form" name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
          
          <?php if ( $checkout->get_checkout_fields() ) : ?>

            <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

            <div id="customer_details">
              <h3>FAKTURA</h3>
			  <p class="club-name"><?php echo nw_get_shop_name(); ?></p>
			  <br>
              <img width="100" src="<?php echo nw_get_club_logo_src(); ?>"/>
              
              <div id="show_billing_details">
                <?php echo $billing_first_name.' '.$billing_last_name; ?>,<br><br>
                Att:<br>
                <?php echo $billing_address_1; ?><br>
                <?php echo $billing_postcode.' '.$billing_city; ?><br><br>
                Tlf:<?php echo $billing_phone; ?><br>
              
                <a href="javascript:void(0);" onclick="edit_billing_checkout(); return false;">Endre</a>
              </div>
              <div id="edit_billing_details" style="display:none;">
			  <div id="msg"></div>
                <div class="col-1">
                  <?php do_action( 'woocommerce_checkout_billing' ); ?>
                </div>

                <div class="col-2">
                  <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                </div>
                
                <a href="javascript:void(0);" onclick="saveBillingInfo(); return false;">Save</a>
                
              </div>
            </div>

            <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

          <?php endif; ?>
          
          <br><br>
          <hr>
          <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

          <div id="order_review" class="woocommerce-checkout-review-order">
            <?php do_action( 'woocommerce_checkout_order_review' ); ?>
          </div>

          <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
        </form>	
      </div>
    </div>
	</div>
</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
