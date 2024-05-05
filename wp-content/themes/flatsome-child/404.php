<?php
/**
 * The template for displaying 404 pages (not found).
 *
 * @package          Flatsome\Templates
 * @flatsome-version 3.16.0
 */

get_header(); ?>
	<?php do_action('flatsome_before_404') ;?>
	<div id="primary" class="content-area">
		<main id="main" class="site-main container pt" role="main">
			<section class="error-404 not-found mt mb">
				<div class="row">
					<div class="col medium-12">
						<header class="page-title">
							<h1 class="page-title">Woops, fant ingenting her!</h1>
						</header><!-- .page-title -->

					</div>
				</div><!-- .row -->


			</section><!-- .error-404 -->

		</main><!-- #main -->
	</div><!-- #primary -->
	<?php do_action('flatsome_after_404') ;?>
<?php get_footer(); ?>
