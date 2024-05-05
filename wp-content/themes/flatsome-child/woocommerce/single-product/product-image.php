<?php
/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.8.0
 */

defined( 'ABSPATH' ) || exit;

// FL: Disable check, Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
//if ( ! function_exists( 'wc_get_gallery_image_html' ) ) {
//	return;
//}

if(get_theme_mod('product_gallery_woocommerce')) {
  wc_get_template_part( 'single-product/product-image', 'default' );
  return;
}

if(get_theme_mod('product_layout') == 'gallery-wide'){
  wc_get_template_part( 'single-product/product-image', 'wide' );
  return;
}

if(get_theme_mod('product_layout') == 'stacked-right'){
  wc_get_template_part( 'single-product/product-image', 'stacked' );
  return;
}

if(get_theme_mod('product_image_style') == 'vertical'){
  wc_get_template_part( 'single-product/product-image', 'vertical' );
  return;
}

global $product;

$show_slick_slider_gallery = get_post_meta($product->get_id(), '_show_slick_slider_gallery',true);

if($show_slick_slider_gallery){
	// Get all variations for the current product
	$variations = $product->get_available_variations();

	// Sort the variations by the order they appear in the attribute 'pa_color'
	usort($variations, function($a, $b) {
		return strcmp($a['attributes']['attribute_pa_color'], $b['attributes']['attribute_pa_color']);
	});

	// Filter out variations that are out of stock
	$variations = array_filter($variations, function($variation) {
		return $variation['is_in_stock'];
	});

	$unique_colors = [];
	// Output the variations
	foreach ($variations as $variation) {
		$color = $variation['attributes']['attribute_pa_color'];
		if (!in_array($color, $unique_colors)) {
			$unique_colors[] = $color;
		}
	}

	$default_selected_color = $unique_colors[0];
	// echo $default_selected_color;

	$term = get_term_by('slug', $default_selected_color, 'pa_color');
	$color_id = $term->term_id;

	global $wpdb;
	$meta_key = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT meta_key FROM $wpdb->postmeta WHERE meta_value = %d AND post_id = %d",
			$color_id,
			$product->get_id()
		)
	);

	if ($meta_key) {
		preg_match('/(\d+)_product_color/', $meta_key, $matches);
		if ($matches && isset($matches[1])) {
			$number = $matches[1];

			$attachment_ids =  get_post_meta($product->get_id(), 'color_variants_gallery_'.$number.'_color_gallery',true);
			// print_r($attachment_ids);
		}
	}

}else{
	$attachment_ids = $product->get_gallery_image_ids();
}

$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$wrapper_classes   = apply_filters( 'woocommerce_single_product_image_gallery_classes', array(
	'woocommerce-product-gallery',
	'woocommerce-product-gallery--' . ( $product->get_image_id() ? 'with-images' : 'without-images' ),
	'woocommerce-product-gallery--columns-' . absint( $columns ),
	'images',
) );

$slider_classes = array('product-gallery-slider','slider','slider-nav-small','mb-half');

// Image Zoom
if(get_theme_mod('product_zoom', 0)){
  $slider_classes[] = 'has-image-zoom';
}

$rtl = 'false';
if(is_rtl()) $rtl = 'true';

if(get_theme_mod('product_lightbox','default') == 'disabled'){
  $slider_classes[] = 'disable-lightbox';
}

?>
<?php do_action('flatsome_before_product_images'); ?>

<div class="product-images relative mb-half has-hover <?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">

  <?php do_action('flatsome_sale_flash'); ?>

  <div class="image-tools absolute top show-on-hover right z-3">
    <?php do_action('flatsome_product_image_tools_top'); ?>
  </div>

  <figure class="woocommerce-product-gallery__wrapper <?php echo implode(' ', $slider_classes); ?>"
        data-flickity-options='{
                "cellAlign": "center",
                "wrapAround": true,
                "autoPlay": false,
                "prevNextButtons":true,
                "adaptiveHeight": true,
                "imagesLoaded": true,
                "lazyLoad": 1,
                "dragThreshold" : 15,
                "pageDots": false,
                "rightToLeft": <?php echo $rtl; ?>
       }'>
    <?php
    if ( $product->get_image_id() ) {
      $html  = flatsome_wc_get_gallery_image_html( $post_thumbnail_id, true );
    } else {
      $html  = '<div class="woocommerce-product-gallery__image--placeholder">';
      $html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
      $html .= '</div>';
    }

		echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped

    do_action( 'woocommerce_product_thumbnails' );
    ?>
  </figure>

  <div class="image-tools absolute bottom left z-3">
    <?php do_action('flatsome_product_image_tools_bottom'); ?>
  </div>
</div>
<?php do_action('flatsome_after_product_images'); ?>

<?php wc_get_template( 'woocommerce/single-product/product-gallery-thumbnails.php' ); ?>


