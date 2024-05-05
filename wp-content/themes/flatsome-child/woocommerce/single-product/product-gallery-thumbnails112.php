<?php

global $post, $product;

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
	}
}

// $attachment_ids = $product->get_gallery_image_ids();
$post_thumbnail = has_post_thumbnail();
$thumb_count    = count( $attachment_ids );

if ( $post_thumbnail ) $thumb_count++;

// Disable thumbnails if there is only one extra image.
if ( $post_thumbnail && $thumb_count == 1 ) {
	return;
}

$rtl              = 'false';
$thumb_cell_align = 'left';

if ( is_rtl() ) {
	$rtl              = 'true';
	$thumb_cell_align = 'right';
}

if ( $attachment_ids ) {
	$loop          = 0;
	$image_size    = 'thumbnail';
	$gallery_class = array( 'product-thumbnails', 'thumbnails' );

	// Check if custom gallery thumbnail size is set and use that.
	$image_check = wc_get_image_size( 'gallery_thumbnail' );
	if ( $image_check['width'] !== 100 ) {
		$image_size = 'gallery_thumbnail';
	}

	$gallery_thumbnail = wc_get_image_size( apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_' . $image_size ) );

	if ( $thumb_count < 5 ) {
		$gallery_class[] = 'slider-no-arrows';
	}

	$gallery_class[] = 'slider row row-small row-slider slider-nav-small small-columns-4';
	?>
	<div class="<?php echo implode( ' ', $gallery_class ); ?>"
		data-flickity-options='{
			"cellAlign": "<?php echo $thumb_cell_align; ?>",
			"wrapAround": false,
			"autoPlay": false,
			"prevNextButtons": true,
			"asNavFor": ".product-gallery-slider",
			"percentPosition": true,
			"imagesLoaded": true,
			"pageDots": false,
			"rightToLeft": <?php echo $rtl; ?>,
			"contain": true
		}'>
		<?php


		if ( $post_thumbnail ) :
			?>
			<div class="col is-nav-selected first slider-items">
				<a>
					<?php
					$image_id  = get_post_thumbnail_id( $post->ID );
					$image     = wp_get_attachment_image_src( $image_id, apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_' . $image_size ) );
					$image_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
					$image     = '<img src="' . $image[0] . '" alt="' . $image_alt . '" width="' . $gallery_thumbnail['width'] . '" height="' . $gallery_thumbnail['height'] . '" class="attachment-woocommerce_thumbnail" />';

					echo $image;
					?>
				</a>
			</div><?php
		endif;

		foreach ( $attachment_ids as $attachment_id ) {

			$classes     = array( '' );
			$image_class = esc_attr( implode( ' ', $classes ) );
			$image       = wp_get_attachment_image_src( $attachment_id, apply_filters( 'woocommerce_gallery_thumbnail_size', 'woocommerce_' . $image_size ) );

			if ( empty( $image ) ) {
				continue;
			}

			$image_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$image     = '<img src="' . $image[0] . '" alt="' . $image_alt . '" width="' . $gallery_thumbnail['width'] . '" height="' . $gallery_thumbnail['height'] . '"  class="attachment-woocommerce_thumbnail" />';

			echo apply_filters( 'woocommerce_single_product_image_thumbnail_html', sprintf( '<div class="col"><a>%s</a></div>', $image ), $attachment_id, $post->ID, $image_class );

			$loop ++;
		}
		?>
	</div>
	<?php
} ?>
