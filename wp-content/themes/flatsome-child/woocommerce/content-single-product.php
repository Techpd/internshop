<?php

/**
 * Product.
 *
 * @package          Flatsome/WooCommerce/Templates
 * @flatsome-version 3.16.0
 */

?>
<div class="container">
	<?php
	/**
	 * Hook: woocommerce_before_single_product.
	 *
	 * @hooked wc_print_notices - 10
	 */
	do_action('woocommerce_before_single_product');

	if (post_password_required()) {
		echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
	?>
</div>
<div class="product-container">

	<div class="product-main">
		<div class="row content-row mb-0">

			<div class="product-gallery col large-<?php echo flatsome_option('product_image_width'); ?>">
				<div class="slider-wrapper">
					<?php
					/**
					 * woocommerce_before_single_product_summary hook
					 *
					 * @hooked woocommerce_show_product_images - 20
					 */
					do_action('woocommerce_before_single_product_summary');
					?>
				</div>
			</div>

			<div class="product-info summary col-fit col entry-summary <?php flatsome_product_summary_classes(); ?>">
				<?php
				/**
				 * woocommerce_single_product_summary hook
				 *
				 * @hooked woocommerce_template_single_title - 5
				 * @hooked woocommerce_template_single_rating - 10
				 * @hooked woocommerce_template_single_price - 10
				 * @hooked woocommerce_template_single_excerpt - 20
				 * @hooked woocommerce_template_single_add_to_cart - 30
				 * @hooked woocommerce_template_single_meta - 40
				 * @hooked woocommerce_template_single_sharing - 50
				 */
				do_action('woocommerce_single_product_summary');
				?>

			</div>


			<div id="product-sidebar" class="col large-2 hide-for-medium product-sidebar-small">
				<?php
				do_action('flatsome_before_product_sidebar');
				/**
				 * woocommerce_sidebar hook
				 *
				 * @hooked woocommerce_get_sidebar - 10
				 */
				if (is_active_sidebar('product-sidebar')) {
					dynamic_sidebar('product-sidebar');
				}
				?>
			</div>

		</div>
	</div>

	<div class="product-footer">
		<div class="container">
			<?php
			/**
			 * woocommerce_after_single_product_summary hook
			 *
			 * @hooked woocommerce_output_product_data_tabs - 10
			 * @hooked woocommerce_upsell_display - 15
			 * @hooked woocommerce_output_related_products - 20
			 */
			do_action('woocommerce_after_single_product_summary');
			?>
		</div>
	</div>
</div>