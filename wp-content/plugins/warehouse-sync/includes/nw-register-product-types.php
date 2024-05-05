<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;
    /**
     * Register and add hooks for the New Wave custom product types
     *
     */
    class NW_Register_Product_Types
    {

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Register the classes needed for the custom product types
            add_action('woocommerce_loaded', __CLASS__ . '::register_product_types');
            add_filter('woocommerce_data_stores', __CLASS__ . '::register_data_stores');

            // Create mandatory attributes
            add_action('woocommerce_after_register_taxonomy', __CLASS__ . '::create_attributes');

            // Add 'WC_Product_Variable' specific template hooks to the custom product types
            add_action('woocommerce_nw_stock_add_to_cart', __CLASS__ . '::add_to_cart');
            add_action('woocommerce_nw_stock_logo_add_to_cart', __CLASS__ . '::add_to_cart');
            add_action('woocommerce_nw_special_add_to_cart', __CLASS__ . '::add_to_cart');
            add_filter('woocommerce_add_to_cart_handler', __CLASS__ . '::add_to_cart_handler', 99);

            /* Disable the unique SKU
            * (allow e.g. a product Stock and Stock Logo to have the same product number)
            */
            add_filter('wc_product_has_unique_sku', '__return_false');

            // Update category counts for each shop when a product changes status to or from 'publish'
            add_action('transition_post_status', __CLASS__ . '::product_status_change', 99, 3);

            // Add clearing of expired products to the plugins cron-job
            add_action('nw_nocte', __CLASS__ . '::clear_expired_products');

            // Add color associated with product image when saving
            add_action('woocommerce_save_product_variation', __CLASS__ . '::add_image_meta', 99, 1);
            add_filter('woocommerce_rest_insert_product_variation_object', __CLASS__ . '::add_image_meta', 10, 1);
        }

        /**
         * Trigger the same 'add_to_cart_handler' handler functionality for
         * custom product types that WC_Product_Variable has
         *
         */
        public static function add_to_cart_handler($type)
        {
            if (in_array($type, array('nw_stock', 'nw_stock_logo', 'nw_special')))
                $type = 'variable';
            return $type;
        }

        /**
         * Trigger the same template 'add_to_cart' buttons functionality for
         * custom product types that WC_Product_Variable has
         *
         */
        public static function add_to_cart()
        {
            do_action('woocommerce_variable_add_to_cart');
        }

        /**
         * Register product type classes for WooCommerce
         *
         */
        public static function register_product_types()
        {
            // Load pure product classes
            require_once(NW_PLUGIN_DIR . 'includes/nw-product-base-class.php');
            require_once(NW_PLUGIN_DIR . 'includes/nw-product-stock-class.php');
            require_once(NW_PLUGIN_DIR . 'includes/nw-product-stock-logo-class.php');
            require_once(NW_PLUGIN_DIR . 'includes/nw-product-special-class.php');

            // Init product admin handler
            require_once(NW_PLUGIN_DIR . 'includes/nw-product-admin.php');
            NW_Product_Admin::init();
        }


        /**
         * Specify the custom data store that the custom product types should use
         *
         * @param  array  $data_stores
         * @return array
         */
        public static function register_data_stores($data_stores = array())
        {
            $data_stores['product-nw_stock'] = 'WC_Product_NW_Data_Store';
            $data_stores['product-nw_stock_logo'] = 'WC_Product_NW_Data_Store';
            $data_stores['product-nw_special'] = 'WC_Product_NW_Data_Store';

            return $data_stores;
        }


        /**
         * Create product attributes that should always be available
         *
         */
        public static function create_attributes()
        {
            if (!wc_check_if_attribute_name_is_reserved('color')) {
                wc_create_attribute(array(
                    'name' => __('Color', 'newwave'),
                    'slug' => 'color'
                ));
            }
            if (!wc_check_if_attribute_name_is_reserved('size')) {
                wc_create_attribute(array(
                    'name' => 'Size',
                    'slug' => __('size', 'newwave')
                ));
            }
        }

        /**
         * Update product counts for all shops when a product changes status from or to 'publish'.
         * Will also trigger on 'publish' to 'publish'.
         *
         * @param string $from_status
         * @param string $to_status
         * @param WP_Post
         */
        public static function product_status_change($from_status, $to_status, $post)
        {
            if ('product' == $post->post_type && ('publish' == $to_status || 'publish' == $from_status) && get_option('_nw_shop_feature')) {
                foreach (NWP_Functions::query_clubs() as $club) {
                    $club = new NW_Shop_Club($club['id']);
                    $club->update_categories();
                    $club->save();
                }
            }
        }

        /**
         * Save the color as meta data for the thumbnail image,
         * depending on the variation 'pa_color'
         *
         * @param int $variation_id
         */
        public static function add_image_meta($variation)
        {
            if (is_numeric($variation)) {
                $variation = wc_get_product($variation);
            }

            $thumb_id = get_post_thumbnail_id($variation->get_id());
            $colors = NWP_Functions::unpack_list(get_post_meta($thumb_id, '_nw_color', true));

            $attr = $variation->get_attributes();
            if (isset($attr['pa_color'])) {
                if ($term = get_term_by('slug', $attr['pa_color'], 'pa_color')) {
                    if (!in_array($term->term_id, $colors)) { // Only ff not already associated with color
                        $colors[] = $term->term_id;
                        update_post_meta($thumb_id, '_nw_color', NWP_Functions::pack_list($colors));
                    }
                }
            }
            return $variation;
        }

        /**
         * Sets the status of expired products older than a set amount of days to 'draft',
         * so that expired products are not shown to the end user forever
         *
         */
        public static function clear_expired_products()
        {
            $search = new WP_Query(array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ));

            foreach ($search->posts as $post) {
                $product = wc_get_product($post->ID);
                if (
                    // $product->is_type('nw_stock_logo') || 
                    $product->is_type('nw_special')
                ) {
                    $days = get_option('nw_settings_days_after_expire') ? get_option('nw_settings_days_after_expire') : 7;
                    $date = strtotime("- $days days");

                    if ($product->get_sale_period_date() <= $date) {
                        wp_update_post(array(
                            'ID' => $post->ID,
                            'post_status' => 'draft',
                        ));
                    }
                }
            }
        }
    }