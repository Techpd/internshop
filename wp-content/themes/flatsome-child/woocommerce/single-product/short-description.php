<?php
/**
 * Single product short description
 *
 * @author           Automattic
 * @package          WooCommerce/Templates
 * @version          3.3.0
 * @flatsome-version 3.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

global $post;

wc_get_template( 'single-product/meta.php' );

$short_description  = apply_filters( 'woocommerce_short_description', $post->post_excerpt );

$productId = $post->ID;
$print_instructions = get_post_meta($productId, 'print_instructions', true );

$product = wc_get_product( $productId );

// if ( ! $short_description ) {
  // return;
// }

?>
<style type="text/css">
   .show-read-more .more-text{
        display: none;
    }
    .display-read{
      display: none;
    }
</style>
<div class="product-short-description show-read-more">

  <?php echo $short_description; // WPCS: XSS ok. ?>
</div>
<div class="read-more-div display-read">
  <a href="javascript:void(0);" class="read-more">Les mer</a>
</div>
<div class="read-less-div display-read">
  <a href="javascript:void(0);" class="read-less">Les mindre</a>
</div>

<?php  if( $product->is_type('nw_stock_logo') && !empty($print_instructions)){ ?>
<div class="product-print-instruction">
  <span class="title">Logo</span>
  <span class="inst">
    <?php echo $print_instructions;  ?>
  </span>
  
  <br>
</div>
<?php } ?>
