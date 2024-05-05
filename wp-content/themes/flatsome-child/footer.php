<?php
/**
 * The template for displaying the footer.
 *
 * @package          Flatsome\Templates
 * @flatsome-version 3.16.0
 */

global $flatsome_opt;
?>

</main>

<div class="lightbox-forms">
	<?php echo do_shortcode('[lightbox id="craft-login-form" width="600px" padding="20px"][craft_login_form][/lightbox]');?>
</div>

<div class="lightbox-forms">
	<?php //echo do_shortcode('[lightbox id="craft-register-form" width="600px" padding="20px" auto_timer="50" auto_show="always/once"][craft_register_form][/lightbox]');?>
</div>

<footer id="footer" class="footer-wrapper">

	<?php do_action('flatsome_footer'); ?>

</footer>

</div>

<div id="store-modal" class="white-popup-block mfp-hide">
	<div class="tab_wrapper">
		<button type="button" class="close-modal" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h1 class="store_title"><?php the_field('storrelsesguide_title', 'option'); ?></h1>
		

		<ul class="tabs">
			<li class="tab-link current" data-tab="tab-1"><?php the_field('tab_one_title', 'option'); ?></li>
			<li class="tab-link" data-tab="tab-5"><?php the_field('tab_five_title', 'option'); ?></li>
			<li class="tab-link" data-tab="tab-2"><?php the_field('tab_two_title', 'option'); ?></li>
			<li class="tab-link" data-tab="tab-3"><?php the_field('tab_three_title', 'option'); ?></li>
			<li class="tab-link" data-tab="tab-4"><?php the_field('tab_four_title', 'option'); ?></li>
			
		</ul>

		<div class="content-wrapper">
			<div id="tab-1" class="tab-content current">
				<img src="<?php the_field('tab_one_image', 'option'); ?>"/>
					<div class="row flex-row">
						<?php if( have_rows('tab_one_content', 'option') ): ?>
							<?php while( have_rows('tab_one_content', 'option') ): the_row(); ?>
							<div class="col-sm-4 flex-col">
								<div class="img_wrap">
									<h5><?php the_sub_field('sub_title', 'option'); ?></h5>
									<img src="<?php the_sub_field('sub_image', 'option'); ?>"/>
								</div>
								<div class="tab_content">
									<?php the_sub_field('sub_content', 'option'); ?>
								</div>
							</div>
							<?php endwhile; ?>
						<?php endif; ?>
					</div>
			</div>
			<div id="tab-5" class="tab-content">
				<img src="<?php the_field('tab_five_image', 'option'); ?>"/>
			</div>
			<div id="tab-2" class="tab-content">
				<img src="<?php the_field('tab_two_image', 'option'); ?>"/>
			</div>
			<div id="tab-3" class="tab-content">
				<img src="<?php the_field('tab_three_image', 'option'); ?>"/>
			</div>
			<div id="tab-4" class="tab-content">
				<img src="<?php the_field('tab_four_image', 'option'); ?>"/>
			</div>
			
		</div>

	</div>
</div>

<div id="footer-store-modal">
	<div class="tab_wrapper">
		<button type="button" class="close-modal" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h1 class="store_title"><?php the_field('storrelsesguide_title', 'option'); ?></h1>
		

		<div class="tabs-nav">
			<ul>
			<li class="active"><a href="#tab1"><?php the_field('tab_one_title', 'option'); ?> </a></li>
			<li><a href="#tab2"><?php the_field('tab_five_title', 'option'); ?></a></li>
			<li><a href="#tab3"><?php the_field('tab_two_title', 'option'); ?></a></li>
			<li><a href="#tab4"><?php the_field('tab_three_title', 'option'); ?></a></li>
			<li><a href="#tab5"><?php the_field('tab_four_title', 'option'); ?></a></li>
			</ul>
		</div>

		<section class="tabs-content">
			<div id="tab1">
				<img src="<?php the_field('tab_one_image', 'option'); ?>"/>
					<section class="row flex-row">
						<?php if( have_rows('tab_one_content', 'option') ): ?>
							<?php while( have_rows('tab_one_content', 'option') ): the_row(); ?>
							<aside class="col-sm-4 flex-col">
								<aside class="img_wrap">
									<h5><?php the_sub_field('sub_title', 'option'); ?></h5>
									<img src="<?php the_sub_field('sub_image', 'option'); ?>"/>
								</aside>
								<aside class="tab_content">
									<?php the_sub_field('sub_content', 'option'); ?>
								</aside>
							</aside>
							<?php endwhile; ?>
						<?php endif; ?>
					</section>
			</div>
			<div id="tab2">
				<img src="<?php the_field('tab_five_image', 'option'); ?>"/>
			</div>

			<div id="tab3">
				<img src="<?php the_field('tab_two_image', 'option'); ?>"/>
			</div>

			<div id="tab4">
				<img src="<?php the_field('tab_three_image', 'option'); ?>"/>
			</div>

			<div id="tab5">
				<img src="<?php the_field('tab_four_image', 'option'); ?>"/>
			</div>
		</section>
		
	</div>
</div>


	

<script type="text/javascript" src="https://npmcdn.com/flickity@2/dist/flickity.pkgd.js"></script>
<?php wp_footer(); ?>
</body>
</html>
