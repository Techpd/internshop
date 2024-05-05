<?php
/**
 * Admin View: Email Template Preview
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
<div style="width:100%; ">
	<img src="<?php echo $banner; ?>" style="max-width:500px; margin:auto; display:block; margin-bottom: 20px;"/>
</div>
<h2><?php echo $title; ?></h2>
<p><?php echo esc_html($content); ?></p>
