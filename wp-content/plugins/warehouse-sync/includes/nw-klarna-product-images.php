<?php

// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Replaces standard woocommerce thumbnails sent to Klarna with
     * full-size product images
     */

    class NW_Klarna_Product_Images
    {
        /**
         * Add hooks and filters to initialize the class
         */

        public static function init()
        {
            //check if this feature is enabled in plugin settings
            if (!get_option('_nw_klarna_product_imgs')) {
                return;
            }

            // Hook into the Klarna API request arguments to replace image URLs
            add_filter('kco_wc_api_request_args', __CLASS__ . '::replace_image_urls', 99, 1);
        }

        /**
         * Replace image URLs of standard Klarna checkout request with
         * product images of size 'woocommerce_single'
         *
         * @param array $request The Klarna API request arguments
         * @return array Modified Klarna API request arguments with replaced image URLs
         */

        public static function replace_image_urls($request)
        {
            // Check if there are any products in the request
            if (!isset($request['order_lines']))
                return $request;

            // Get larger images for products in cart
            $items = WC()->cart->get_cart();
            $images = array();
            foreach ($items as $item => $values) {
                $sku = $values['data']->get_sku();
                if (!$sku)
                    continue;

                $url = get_the_post_thumbnail_url($values['product_id'], 'woocommerce_single');
                if ($url) {
                    $images[$sku] = $url;
                }
            }

            // Replace image URLs in the Klarna API request with the larger product images
            foreach ($request['order_lines'] as &$order_line) {
                if (isset($order_line['reference']) && isset($images[$order_line['reference']])) {

                    // If the product in the request matches a product in the cart with an image,
                    // update the image URL in the API request
                    $order_line['image_url'] = $images[$order_line['reference']];
                }
            }

            // Return the modified Klarna API request arguments
            return $request;
        }
    }

    // Initialize the NW_Klarna_Product_Images class and hooks/filters
    NW_Klarna_Product_Images::init();
