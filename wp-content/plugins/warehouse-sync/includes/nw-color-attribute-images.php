<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

if (!function_exists('nw_get_color_attribute_images')) :

    /**
     * Get the associative array with color => image url,
     * to represent colors with thumbnail versions of their respective product image
     *
     * @param int $product_id
     * @return array
     */

    function nw_get_color_attribute_images($product_id)
    {
        if (!$product_id)
            return array();

        $product_id = sanitize_text_field($product_id);

        $images = maybe_unserialize(get_post_meta($product_id, 'nw_color_attribute_images', true));

        if (!is_array($images) || empty($images)) {
            return array();
        }

        return $images;
    }

endif;

    /**
     * Handles updating of which images belong to which colors for a product
     */

    class NW_Color_Attribute_Images
    {

        /**
         * Initialization function to set up hooks and filters
         */

        public static function init()
        {
            //check if this feature is enabled in plugin settings
            if (!get_option('_nw_feature_color_attr')) {
                return;
            }

            // Update color-attribute-image array when a variation is saved or updated
            add_action('woocommerce_save_product_variation', __CLASS__ . '::update_color_attribute_images', 99, 1);
            add_filter('woocommerce_rest_insert_product_variation_object', __CLASS__ . '::update_color_attribute_images', 10, 1);

            add_filter('nw_hide_product_meta_data', function ($keys) {
                array_push($keys, 'nw_color_attribute_images');
                return $keys;
            });
        }

        /**
         * Update the parent meta with new image for a color if applicable
         *
         * @param WC_Product_Variation|int $variation
         * @return WC_Product_Variation
         */

        public static function update_color_attribute_images($variation)
        {
            if (is_numeric($variation))
                $variation = wc_get_product($variation);

            // Get image for this variation
            $image_id = $variation->get_image_id();
            if (!$image_id)
                return $variation;

            $images = maybe_unserialize(get_post_meta($variation->get_parent_id(), 'nw_color_attribute_images', true));
            if (!is_array($images))
                $images = array();

            // Get color for variation, if any
            $attributes = $variation->get_attributes();
            $color = isset($attributes['pa_color']) ? $attributes['pa_color'] : false;

            if (!$color)
                return $variation;

            $image = wp_get_attachment_image_src($image_id, 'nw_attribute_icon');
            if (!$image || !is_array($image) || empty($image) || empty($image[0]))
                return $variation;

            //Associate image url with the color attribute of $variation,and update the meta data for the parent product.
            $images[$color] = $image[0];
            update_post_meta($variation->get_parent_id(), 'nw_color_attribute_images', maybe_serialize($images));

            return $variation;
        }
    }

NW_Color_Attribute_Images::init();
