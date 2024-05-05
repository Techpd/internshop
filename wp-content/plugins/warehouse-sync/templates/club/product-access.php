<?php
/**
 * Product access template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/campaign.php.
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<style>

/* The Modal (background) */
.modal {
  position: fixed; /* Stay in place */
  z-index: 9; /* Sit on top */
  padding-top: 100px; /* Location of the box */
  left: 0;
  top: 0;
  width: 100%; /* Full width */
  height: 100%; /* Full height */
  overflow: auto; /* Enable scroll if needed */
  background-color: rgb(0,0,0); /* Fallback color */
  background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

/* Modal Content */
.modal-content {
  background-color: #fefefe;
  margin: auto;
  padding: 20px;
  border: 1px solid #888;
  width: 80%;
}

/* The Close Button */
.close {
  color: #aaaaaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: #000;
  text-decoration: none;
  cursor: pointer;
}
</style>

<!-- The Modal -->
<div id="myModal" class="modal">

  <!-- Modal content -->
  <div class="modal-content">
    <span class="close">&times;</span>
    
    <form id="product_access_form" method="post">
      
      <div class="form-title">
        <p class="">LOGG INN</p>
      </div>
    
      <input type="hidden" name="action" value="nw_register_shop">
      <?php wp_nonce_field('nw_register_shop', '_nw_register_shop_nonce'); ?>
      <label>PASSORD</label>
      <input type="text" id="newwave-register-code" name="nw_shop_reg_code" />
      
      <div class="row" style="display:none">
        <p> <a href="#">Registrer bedrift</a></p>
        <p> <a href="#">Glemt Passord?</a></p>        
      </div>
      
      <input type="button" onClick="productAccess();" id="newwave-register-new-shop" class="woocommerce-button button <?php echo esc_attr(apply_filters('newwave_register_new_shop_button_class', 'register-new-shop-button-class')); ?>" value="<?php _e('LOGG INN', 'newwave'); ?>" />
      <div id="msg"></div>
      
      <div class="form-footer-content">
        <p>INTERNSHOP</p>
      </div>

      
    </form>

    
    
  </div>

</div>