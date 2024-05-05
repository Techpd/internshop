
<?php
/*
* Template Name: Home Page 2.0
*/
get_header(); ?>

<div class="internshop-wrapper"><?php 
	if( have_rows('page_builder') ):
		while ( have_rows('page_builder') ): the_row();
			switch(get_row_layout()){
				case 'top_banner' 			: get_template_part( 'templates/home/is_top_banner' ); break;
				case 'showcase' 				: get_template_part( 'templates/home/is_showcase_pannel' ); break;
				case 'poster' 					: get_template_part( 'templates/home/is_poster' ); break;
				case 'list_with_cta' 		: get_template_part( 'templates/home/is_list_with_cta' ); break;
				case 'sequence_images' 	: get_template_part( 'templates/home/is_sequence_images' ); break;
				case 'double_content' 	: get_template_part( 'templates/home/is_double_content' ); break;
				case 'testimonials' 		: get_template_part( 'templates/home/is_testimonials' ); break;
				default: break;
			}
		endwhile;
	endif; ?>
</div>


<!--<div class="register-badrift-popup">
	<div class="form-container">
		<span class="close">
			<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M6.22566 4.81096C5.83514 4.42044 5.20197 4.42044 4.81145 4.81096C4.42092 5.20148 4.42092 5.83465 4.81145 6.22517L10.5862 11.9999L4.81151 17.7746C4.42098 18.1651 4.42098 18.7983 4.81151 19.1888C5.20203 19.5793 5.8352 19.5793 6.22572 19.1888L12.0004 13.4141L17.7751 19.1888C18.1656 19.5793 18.7988 19.5793 19.1893 19.1888C19.5798 18.7983 19.5798 18.1651 19.1893 17.7746L13.4146 11.9999L19.1893 6.22517C19.5799 5.83465 19.5799 5.20148 19.1893 4.81096C18.7988 4.42044 18.1657 4.42044 17.7751 4.81096L12.0004 10.5857L6.22566 4.81096Z" fill="black"/>
			</svg>	
		</span>
		<?php // echo FrmFormsController::get_form_shortcode( array( 'id' => 3, 'title' => true, 'description' => true ) ); ?>
		<div class="footer-text">
			<p>
				INTERNSHOP
			</p>
		</div>
	</div>
</div>-->
 

<?php get_footer(); ?>