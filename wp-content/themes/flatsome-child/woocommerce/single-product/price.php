<?php
/**
 * Single Product Price, including microdata for SEO
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/price.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see              https://docs.woocommerce.com/document/template-structure/
 * @author           WooThemes
 * @package          WooCommerce/Templates
 * @version          3.0.0
 * @flatsome-version 3.16.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;
$product_sale_price = '';
    if( $product->is_on_sale() ) {
        $product_sale_price =  $product->get_sale_price();
    }
   $product_regular_price = $product->get_regular_price();

   /*  $min_regular_price = $product->get_variation_regular_price( 'min' );
$max_regular_price = $product->get_variation_regular_price( 'max' );

$min_sale_price = $product->get_variation_sale_price( 'min' );
$max_sale_price = $product->get_variation_sale_price( 'max' );

$min_price = $product->get_variation_price( 'min' );
$max_price = $product->get_variation_price( 'max' );

$pricess = $product->get_variation_prices();
echo "<pre>";print_r($pricess);

echo $min_regular_price;
echo $max_regular_price;
echo $max_sale_price;
echo $min_sale_price;
echo $min_price;
echo $max_price;*/




?>
 <p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) ); ?>"><?php echo $product->get_price_html(); ?></p>
 <!-- <p class="<?php //echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) ); ?>"><?php //echo  $product_regular_price; ?></p>  -->
