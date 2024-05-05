<?php

// Prevent direct access to the file
if (!defined('ABSPATH')) exit;
    class NW_Product_Properties
    {
        /**
         * Initialization function to set up hooks and filters
         */

        public static function init()
        {
            //check if this feature is enabled in plugin settings
            if (!get_option('_nw_feature_properties')) {
                return;
            }

            // Add custom tab for product data
            add_filter('woocommerce_product_data_tabs', __CLASS__ . '::add_panel_tab', 99);

            // Add custom panel content for product data
            add_filter('woocommerce_product_data_panels', __CLASS__ . '::add_panel', 99);

            require_once(NW_Plugin::$plugin_dir . 'includes/nw-product-property-material.php');
            require_once(NW_Plugin::$plugin_dir . 'includes/nw-product-property-concept.php');
            require_once(NW_Plugin::$plugin_dir . 'includes/nw-product-property-attribute-icons.php');

            // Rest API filter to hide certain meta data from product response
            add_filter('woocommerce_rest_prepare_product_object', __CLASS__ . '::hide_meta_data', 10, 1);

             // Save attributes from custom product types
             add_action('woocommerce_admin_process_product_object', __CLASS__ . '::save', 99);
        }

        /**
         * Add tabs for the custom data panels
         * @param array $tabs Product data tabs with label, target div, class and priority
         * @return array
         */

        public static function add_panel_tab($tabs)
        {
            $tabs['nw_properties'] = array(
                'label' => __('Properties', 'newwave'),
                'target' => 'nw_properties_panel',
                'class' => array('show_if_simple', 'show_if_variable','show_if_nw_stock','show_if_nw_stock_logo'),
                'priority' => 20
            );

            $tabs['nw_printing_instruction'] = array(
                'label' => __('Trykkeri'),
                'target' => 'nw_printingInstructions_options',
                'class'         => array('show_if_nw_stock_logo'),
                'priority'      => 3
            );

            array_push($tabs['general']['class'], 'show_if_variable');

            return $tabs;
        }

        /**
         * Add custom panel content for product data
         */
        public static function add_panel()
        {
            global $post;
?>
            <div id="nw_properties_panel" class="panel woocommerce_options_panel">
                <?php
                do_action('nw_properties_panel', $post->ID);
                ?>
            </div>
            
<?php
            $product = wc_get_product($post->ID);

            if($product->is_type('nw_stock_logo')) {
                static::panel_print_instructions($product);
            }
        }

        /**
         * Hide specific meta data from product response in the Rest API
         *
         * @param WP_REST_Response $response The REST API response for the product
         * @return WP_REST_Response Updated REST API response with hidden meta data
         */

        public static function hide_meta_data($response)
        {
            if (!isset($response->data['meta_data']) || !count($response->data['meta_data'])) {
                return $response;
            }

            // Get the keys of meta data to hide using the 'nw_hide_product_meta_data' filter
            $keys_to_hide = apply_filters('nw_hide_product_meta_data', array());
            if (!$keys_to_hide) {
                return $response;
            }

            // Filter out the meta data that should not be shown
            $meta_data = array();
            foreach ($response->data['meta_data'] as $meta) {
                $data = $meta->get_data();
                if (isset($data['key']) && !in_array($data['key'], $keys_to_hide)) {
                    array_push($meta_data, $meta);
                }
            }
            if ($meta_data) {
                $response->data['meta_data'] = $meta_data;
            }

            return $response;
        }

        /**
         * Output panel to printing instructions for product type ' stock product with logo'
         */

        private static function panel_print_instructions($product)
        {
        ?>
            <div id='nw_printingInstructions_options' class='panel woocommerce_options_panel'>
                <div class="options_group">
                    <?php
                    woocommerce_wp_textarea_input(
                        array(
                            'id'        => 'print_instructions',
                            'label'     =>  __('Trykkeri'),
                            'type'      => 'text',
                            'desc_tip'  => __('Instruksjoner for logostørrelse og plassering')
                        )
                    );
                    ?>
                </div>
            </div>
        <?php
        }

        /**
         * Save additional data that comes with the custom product
         * types nw_stock, nw_stock_logo
         *
         * @param WC_Product $product
         */

        public static function save($product)
        {
            if (!is_a($product, 'WC_Product_NW_Base') && !is_a($product, 'WC_Product_NWP_Base'))
                return;

            if (isset($_POST['print_instructions'])) {
                update_post_meta($product->get_id(), 'print_instructions', sanitize_text_field($_POST['print_instructions']));
            }
        }
    }

NW_Product_Properties::init();

?>