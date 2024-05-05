<?php

/**
 * Get the material text for a product
 *
 * @param int $product_id The ID of the product
 * @return string Material text for the product, or empty string if not available
 */

if (!function_exists('nw_get_material')) :

    function nw_get_material($product_id)
    {
        if (!class_exists('NW_Product_Property_Material'))
            return '';

        return NW_Product_Property_Material::get_material($product_id);
    }

endif;

    /**
     * Class NW_Product_Property_Material
     * Handles the 'Material' custom product property for WooCommerce products.
     */

    class NW_Product_Property_Material
    {

        /**
         * Initialize the class by adding hooks and filters.
         */

        public static function init()
        {
            // Save the material when the product is saved or updated
            add_action('save_post', __CLASS__ . '::save_from_post');

            // Render the material panel content in the WooCommerce product data metabox
            add_action('nw_properties_panel', __CLASS__ . '::render_panel', 1);

            // Enable REST API access to get and set material text for products
            add_action('rest_api_init', __CLASS__ . '::enable_REST');

            // Add 'nw_product_material' key to the list of meta data keys to hide in the REST API response
            add_filter('nw_hide_product_meta_data', function ($keys) {
                array_push($keys, 'nw_product_material');
                return $keys;
            });
        }

        /**
         * Render the material panel content in the WooCommerce product data metabox.
         *
         * @param int $post_id The ID of the current product
         */

        public static function render_panel($post_id)
        {
            // Get the material text for the current product
            $material = static::get_material($post_id);
?>
            <div class="options_group">
                <p class="form-field">
                    <label for="nw-material"><?php _e('Material', 'newwave'); ?></label>
                    <input id="nw-material" type="text" class="short" name="nw_product_material" value="<?php echo $material ?>" />
                    <?php echo wc_help_tip(__('Materials the product consists of.', 'newwave')); ?>
                </p>
            </div>
<?php
        }

        /**
         * Save the material text when the product is saved or updated.
         *
         * @param int $post_id The ID of the current product
         */

        public static function save_from_post($post_id)
        {
            // Check if the material data is present in the POST request
            if (!isset($_POST['nw_product_material']))
                return; // If not, exit and do nothing

            // Set the material text for the product
            static::set_material($_POST['nw_product_material'], $post_id);
        }

        /**
         * Enable REST API access to get and set material text for products.
         */

        public static function enable_REST()
        {
            // Register a REST field for 'product' to handle material data
            register_rest_field('product', 'nw_product_material', array(
                'get_callback' => __CLASS__ . '::get_material',
                'update_callback' => __CLASS__ . '::set_material',
            ));
        }

        /**
         * Get the material text for a product.
         *
         * @param int|array|WP_Post $post_id The ID, post object, or array of arguments of the product
         * @return string The material text for the product
         */

        public static function get_material($post_id)
        {
            if (is_array($post_id))
                $post_id = $post_id['id'];

            $material = get_post_meta($post_id, 'nw_product_material', true);
            return is_string($material) ? $material : '';
        }

        /**
         * Set the material text for a product.
         *
         * @param string $material The material text to set for the product
         * @param int|WP_Post $post_id The ID or post object of the product
         */

        public static function set_material($material, $post_id)
        {
            if (is_a($post_id, 'WC_Product'))
                $post_id = $post_id->get_id();

            else if (is_object($post_id))
                return;

            update_post_meta(
                $post_id,
                'nw_product_material',
                esc_attr(sanitize_text_field($material))
            );
        }
    }

    // Initialize the class when the file is included
    NW_Product_Property_Material::init();
?>