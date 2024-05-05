<?php 
/**
 * Category layout with left sidebar.
 *
 * @package          Flatsome/WooCommerce/Templates
 * @flatsome-version 3.16.0
 */
?>
<div class="bedrift-name">
                <h3><?php echo nw_get_shop_name(); ?></h3>
        </div>
	<div class="category-banner-image">
		<?php $club_id =  nw_get_current_shop_id(); 
			$banner_img = get_field('club_banner',$club_id);
			echo "<img src='".$banner_img."'>";
		?>	
	</div>
	<div class="webshop-message">
		<h4><?php echo get_field('webshop_message'); ?></h4>
	</div>
	<div class="mobile-sidebar">
		<?php
			if(is_active_sidebar('shop-sidebar')) {
				dynamic_sidebar('shop-sidebar');
			} else{ echo '<p>You need to assign Widgets to <strong>"Shop Sidebar"</strong> in <a href="'.get_site_url().'/wp-admin/widgets.php">Appearance > Widgets</a> to show anything here</p>';
			}
		?>
	</div>
	<div class="row category-page-row">
		<div class="col large-3 hide-for-medium <?php flatsome_sidebar_classes(); ?>">
			<?php flatsome_sticky_column_open( 'category_sticky_sidebar' ); ?>
			<div id="shop-sidebar" class="sidebar-inner col-inner">
				<?php
				  if(is_active_sidebar('shop-sidebar')) {
				  		dynamic_sidebar('shop-sidebar');
				  	} else{ echo '<p>You need to assign Widgets to <strong>"Shop Sidebar"</strong> in <a href="'.get_site_url().'/wp-admin/widgets.php">Appearance > Widgets</a> to show anything here</p>';
				  }
				?>
			</div>
			<?php flatsome_sticky_column_close( 'category_sticky_sidebar' ); ?>

			<div class="dealer-name-phone">
				<?php
				$vendor_id = wp_get_post_parent_id($club_id);
				?>
				<div class="dealer-name">
					<?php
					echo "".get_the_title($vendor_id);
					?>
				</div>
				<div class="dealer-phone">
					<?php
					echo "".get_post_meta($vendor_id, '_nw_phone',true);
					?>
				</div>
				<div class="dealer-email">
					<a href="mailto:<?php
					echo "".get_post_meta($vendor_id, '_nw_club_email',true);
					?>"><?php
					echo "".get_post_meta($vendor_id, '_nw_club_email',true);
					?></a>
				</div>
				
			</div>
		</div>

		<div class="col large-9">
		<?php
		/**
		 * Hook: woocommerce_before_main_content.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20 (FL removed)
		 * @hooked WC_Structured_Data::generate_website_data() - 30
		 */
		do_action( 'woocommerce_before_main_content' );

		?>

		<?php
		/**
		 * Hook: woocommerce_archive_description.
		 *
		 * @hooked woocommerce_taxonomy_archive_description - 10
		 * @hooked woocommerce_product_archive_description - 10
		 */
		do_action( 'woocommerce_archive_description' );
		?>

		<?php

		if ( woocommerce_product_loop() ) {

			/**
			 * Hook: woocommerce_before_shop_loop.
			 *
			 * @hooked wc_print_notices - 10
			 * @hooked woocommerce_result_count - 20 (FL removed)
			 * @hooked woocommerce_catalog_ordering - 30 (FL removed)
			 */
			do_action( 'woocommerce_before_shop_loop' );

			woocommerce_product_loop_start();

			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();
					// echo 111;
					/**
					 * Hook: woocommerce_shop_loop.
					 *
					 * @hooked WC_Structured_Data::generate_product_data() - 10
					 */
					do_action( 'woocommerce_shop_loop' );

					wc_get_template_part( 'content', 'product' );
				}
			}

			woocommerce_product_loop_end();?>
			<input type="hidden" id="cat" value="<?php echo get_queried_object_id(); ?>"/>
			<?php
			/**
			 * Hook: woocommerce_after_shop_loop.
			 *
			 * @hooked woocommerce_pagination - 10
			 */
			//do_action( 'woocommerce_after_shop_loop' ); 
			
			$ppp = wc_get_default_products_per_row() * wc_get_default_product_rows_per_page();
			
			if(wc_get_loop_prop( 'total' ) > $ppp){
			?>
			<button id="load_more" class="load_more" onclick='load_more_products();'>VIS FLERE PRODUKTER</button>
		<?php
			}
		} else {
			/**
			 * Hook: woocommerce_no_products_found.
			 *
			 * @hooked wc_no_products_found - 10
			 */
			 
			 
			do_action( 'woocommerce_no_products_found' );
		}
		?>

		<?php
			/**
			 * Hook: flatsome_products_after.
			 *
			 * @hooked flatsome_products_footer_content - 10
			 */
			do_action( 'flatsome_products_after' );
			/**
			 * Hook: woocommerce_after_main_content.
			 *
			 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
			 */
			do_action( 'woocommerce_after_main_content' );
		?>
		</div>
	</div>

