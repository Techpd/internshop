<?php
/**
 * Header template.
 *
 * @package          Flatsome\Templates
 * @flatsome-version 3.16.0
 */

if (!WC()->session->has_session())
    WC()->session->set_customer_session_cookie(true);
if("" != WC()->session->get('nw_shop')){
    $site_url = $_SERVER['REQUEST_URI'];
    wc_get_logger()->debug("header site_url : $site_url", ["source"=>"open_shop"]);
    $site_url = explode('/', $site_url);
    $lastPart = end($site_url);
    $last2ndPart = array_slice($site_url, -2, 1)[0];
    wc_get_logger()->debug("header lastPart : $lastPart", ["source"=>"open_shop"]);
    wc_get_logger()->debug("header last2ndPart : $last2ndPart", ["source"=>"open_shop"]);
     if(('butikk' == $last2ndPart && "" == $lastPart) || isset($_GET["klubb"])) {
        wc_get_logger()->debug("header cache control", ["source"=>"open_shop"]);
        header("Cache-Control: max-age=0, public, s-maxage=0");
    }
}
?>
<!DOCTYPE html>
<!--[if IE 9 ]> <html <?php language_attributes(); ?> class="ie9 <?php flatsome_html_classes(); ?>"> <![endif]-->
<!--[if IE 8 ]> <html <?php language_attributes(); ?> class="ie8 <?php flatsome_html_classes(); ?>"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html <?php language_attributes(); ?> class="<?php flatsome_html_classes(); ?>"> <!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

	<link href="<?php echo get_stylesheet_directory_uri() ?>/favicon-32x32.png" rel="icon">
  <link href="<?php echo get_stylesheet_directory_uri() ?>/apple-touch-icon.png" rel="apple-touch-icon">
  
	<?php wp_head(); ?>
</head>

<body <?php body_class(); // Body classes is added from inc/helpers-frontend.php ?>>

<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'flatsome' ); ?></a>

<div id="wrapper">

<?php do_action('flatsome_before_header'); ?>

<header id="header" class="header <?php flatsome_header_classes();  ?>">

	<?php  
		$is_logined = "";
		if ( is_user_logged_in() or WC()->session->get('nw_shop')):
			$is_logined = "is_logged_in";?>
			<script>
				document.body.classList.add('is_logged_in')
			</script>
		<?php
		endif;
		
	?>
	<!-----banner------->
		<?php 
			$links = get_field('top_bar_links','option'); 
			if( $links ):?>			
				<div class="top-links-anim-bar">

					<div class="left-arrow">
						<svg viewBox="0 0 14 8" class="css-w68ren" xmlns="http://www.w3.org/2000/svg">
							<path fill="none" stroke="#ffffff" d="M1 1 L 7 7 L 13 1" class="css-1gp96xz"></path>
						</svg>
					</div>

					<div class="links-container">
						<div class="link-wrapper">
							<?php  //print_r($links);

								if( isset($links[0]['text_link']['target']) ){
									$firstlink = $links[0];
									unset($links[0]); ?>
	
									<div class="link active">
										<a target="<?php echo $firstlink['text_link']['target']?>" href="<?php echo $firstlink['text_link']['url']?>">
											<?php echo $firstlink['text_link']['title']?>
										</a>
									</div><?php
									
									foreach( $links as $key => $link ):
										$link = $link['text_link'];
										if( $link ):?>
											<div class="link ">
												<a target="<?php echo $link['target']?>" href="<?php echo $link['url']?>"><?php echo $link['title']?></a>
											</div><?php
										endif;
									endforeach;									
								}
							?>
							<div class="link">
								<a href="<?php echo $firstlink['text_link']['url']?>"><?php echo $firstlink['text_link']['title']?></a>
							</div>
						</div>
					</div>
					<div class="right-arrow">
						<svg viewBox="0 0 14 8" class="css-ufus15" xmlns="http://www.w3.org/2000/svg">
							<path fill="none" stroke="#ffffff" d="M1 1 L 7 7 L 13 1" class="css-1gp96xz"></path>
						</svg>
					</div>
				</div>
			<?php endif; ?>
		<!------endbanner------>
   <div class="header-wrapper <?php echo $is_logined?>">
		<?php
			get_template_part('template-parts/header/header', 'wrapper');
		?>

   </div><!-- header-wrapper-->

</header>

<?php //do_action('flatsome_after_header'); ?>

<main id="main" class="<?php flatsome_main_classes();  ?>">
    
    <div class="register-badrift-popup">
	<div class="form-container">
		<span class="close">
			<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M6.22566 4.81096C5.83514 4.42044 5.20197 4.42044 4.81145 4.81096C4.42092 5.20148 4.42092 5.83465 4.81145 6.22517L10.5862 11.9999L4.81151 17.7746C4.42098 18.1651 4.42098 18.7983 4.81151 19.1888C5.20203 19.5793 5.8352 19.5793 6.22572 19.1888L12.0004 13.4141L17.7751 19.1888C18.1656 19.5793 18.7988 19.5793 19.1893 19.1888C19.5798 18.7983 19.5798 18.1651 19.1893 17.7746L13.4146 11.9999L19.1893 6.22517C19.5799 5.83465 19.5799 5.20148 19.1893 4.81096C18.7988 4.42044 18.1657 4.42044 17.7751 4.81096L12.0004 10.5857L6.22566 4.81096Z" fill="black"/>
			</svg>	
		</span>
		<?php echo FrmFormsController::get_form_shortcode( array( 'id' => 3, 'title' => true, 'description' => true ) ); ?>
		<div class="footer-text">
			<p>
				INTERNSHOP
			</p>
		</div>
	</div>
    </div>
