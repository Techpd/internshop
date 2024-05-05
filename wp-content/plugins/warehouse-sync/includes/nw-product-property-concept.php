<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

if (!function_exists('nw_get_product_concept')) :

    /**
     * Get the product concept for a product
     *
     * @param int $product_id The ID of the product
     * @return string The concept for the product (elite|performance|active|ctm) or an empty string if not available
     */

    function nw_get_product_concept($product_id)
    {
        if (!$product_id)
            return ''; // If no product ID is provided, return an empty string

        $concept = get_post_meta($product_id, 'nw_product_concept', true);

        // If the concept is not one of the allowed values, return an empty string
        if (!in_array($concept, array('elite', 'performance', 'active', 'ctm')))
            return '';

        return $concept;
    }

endif;

    /**
     * Class NW_Product_Property_Concept
     * Adds functionality to associate products with a product concept.
     */

    class NW_Product_Property_Concept
    {

        /**
         * Initialize the class by adding hooks and filters.
         */

        public static function init()
        {

            // Register product concept as a taxonomy
            add_action('init', __CLASS__ . '::register_taxonomy');

            // Output and save from field when editing in backend
            add_action('nw_properties_panel', __CLASS__ . '::render_panel', 1);
            add_action('save_post', __CLASS__ . '::save_from_post');

            // Enable REST API support
            add_action('rest_api_init', __CLASS__ . '::enable_REST');
            add_filter('nw_hide_product_meta_data', function ($keys) {
                array_push($keys, 'nw_product_concept');
                return $keys;
            });
        }

        // public static function enable($response) {
	// 	if (!isset($response->data['meta_data']) || !count($response->data['meta_data'])) {
	// 		return $response;
	// 	}
	//
	// 	$meta_data = array();
	// 	foreach ($response->data['meta_data'] as $meta) {
	// 		$data = $meta->get_data();
	// 		if (isset($data['key']) && 'nw_product_material' != $data['key'])
	// 			array_push($meta_data, $meta);
	// 	}
	// 	if ($meta_data) {
	// 		$response->data['meta_data'] = $meta_data;
	// 	}
	//
	// 	return $response;
	// }

        /**
         * Register product concept as a taxonomy.
         * For use if sorting by concept should happen sometime in the future.
         */

        public static function register_taxonomy()
        {
            // Register the 'nw_product_concept' taxonomy with specific settings
            register_taxonomy(
                'nw_product_concept',
                'product',
                array(
                    'public'             => false,
                    'publicly_queryable' => false,
                    'show_ui'            => false,
                    'show_in_nav_menus'  => false,
                    'show_in_rest'       => false,
                )
            );

            register_taxonomy_for_object_type('nw_product_concept', 'product');
        }

        /**
         * Render product concept panel section.
         *
         * @param int $post_id The ID of the current product
         */

        public static function render_panel($post_id)
        {
            $concept = '';
            if ($post_id) {
                $concept = get_post_meta($post_id, 'nw_product_concept', true);
            }

            $all_concepts = array('elite' => 'pro', 'performance' => 'advanced', 'active' => 'core', 'ctm' => 'ctm'); ?>

            <div class="options_group">
                <p class="form-field">
                    <?php $title = ($post_id == 0) ? __('Egenskaper', 'newwave') :  __('Concept', 'newwave'); ?>
                    <label><?php echo $title; ?></label><br>

                    <span id="nw-product-concept">
                        <?php foreach ($all_concepts as $c => $cv) { ?>
                            <input name="nw_product_concept" value="<?php echo $c; ?>" type="radio" id="nw_product_concept_<?php echo $c; ?>" <?php checked($concept, $c); ?> />
                            <label for="nw_product_concept_<?php echo $c; ?>">
                                <?php echo ucfirst($cv); ?>
                            </label>
                            <br />
                        <?php } ?>

                        <input id="nw_product_concept_no_concept" name="nw_product_concept" value="" type="radio" <?php checked($concept, false); ?> />
                        <label for="nw_product_concept_no_concept">
                            <?php _e('No concept', 'newwave'); ?>
                        </label>
                    </span>
                </p>
            </div>
<?php
        }

        /**
         * Save product concept when product is saved.
         *
         * @param int $post_id The ID of the current product
         */

        public static function save_from_post($post_id)
        {
            if (!isset($_POST['nw_product_concept']))
                return;

            static::set_concept($_POST['nw_product_concept'], $post_id);
        }

        /**
         * Enable REST API access for getting and setting material text for product.
         */

        public static function enable_REST()
        {
            // Register a REST field for 'product' to handle product concept data
            register_rest_field('product', 'nw_product_concept', array(
                'get_callback' => __CLASS__ . '::get_concept',
                'update_callback' => __CLASS__ . '::set_concept',
            ));
        }

        /**
         * Get concept for product.
         *
         * @param int|array|WP_Post $post_id The ID, post object, or array of arguments of the product
         * @return string The concept for the product (elite|performance|active|ctm)
         */

        public static function get_concept($post_id)
        {
            if (is_array($post_id))
                $post_id = $post_id['id'];

            $concept = get_post_meta($post_id, 'nw_product_concept', true);
            return is_string($concept) ? $concept : '';
        }

        /**
         * Set product concept for a product.
         *
         * @param string $concept The concept to set for the product (elite|performance|active|ctm|'')
         * @param int|WP_Post $post_id The ID or post object of the product
         */

        public static function set_concept($concept, $post_id)
        {
            if (is_a($post_id, 'WC_Product'))
                $post_id = $post_id->get_id();

            else if (is_object($post_id))
                return;

            $concept = sanitize_text_field($concept);

            // If the concept is one of the allowed values, update the post meta and the taxonomy term
            if (in_array($concept, array('elite', 'performance', 'active', 'ctm', ''))) {
                update_post_meta($post_id, 'nw_product_concept', $concept);
                wp_set_object_terms($post_id, $concept, 'nw_product_concept');
            }
        }
    }

    // Initialize the class when the file is included
    NW_Product_Property_Concept::init();
?>