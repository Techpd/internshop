<?php

// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Imports products from a temporary ASW API server
     */

    class NW_ASW_Importer
    {
        /**
         * Add hooks and filters
         */

        public static function init()
        {
            //check if this feature is enabled in plugin settings
            if (!get_option('_nw_product_import_enabled')) {
                return;
            }

            //Include backend helper functions
            require_once(NW_Plugin::$plugin_dir . 'includes/nw-functions.php');

            // Include the importer
            add_action('admin_head', __CLASS__ . '::include_importer');

            // Add the button to trigger the import
            add_action('media_buttons', __CLASS__ . '::asw_importer_button');

            // Make sure attributes used for variations exists
            add_action('woocommerce_after_register_taxonomy', __CLASS__ . '::register_product_attributes');

            // Register AJAX calls
            add_action('wp_ajax_nw_asw_search', __CLASS__ . '::ajax_asw_search');
            add_action('wp_ajax_nw_asw_search_reimport', __CLASS__ . '::ajax_asw_search_reimport');
            add_action('wp_ajax_nw_create_product', __CLASS__ . '::ajax_create_product');
            add_action('wp_ajax_nw_create_variation', __CLASS__ . '::ajax_create_variation');
            add_action('wp_ajax_nw_update_product', __CLASS__ . '::ajax_update_product');

            // Allow ASPX as MIME
            // add_filter('woocommerce_rest_allowed_image_mime_types', __CLASS__.'::allow_aspx', 99, 1);
        }

        /**
         * *NB* Not in use, due to predownloading instead
         * Set filtype .aspx as valid MIME to enable download from nwgmedia.com/Image.aspx
         *
         * @param  string[] $mimes Allowed MIME types
         * @return string[]        Modified MIME-types array
         */

        public static function allow_aspx($mimes) {
            $mimes['aspx'] = 'image/aspx';
            return $mimes;
        }

        /**
         * Include prompt to import from ASW when creating a new product
         */

        public static function include_importer()
        {
            $screen = get_current_screen();
            if ($screen->post_type != 'product' || $screen->id != 'product')
                return;

            global $post;

            if ($post->post_status == 'auto-draft')
                add_action('admin_body_class', function ($classes) {
                    return $classes . ' nw-new-product ';
                }, 99, 1);

            // Enqueue script with its dependencies
            wp_enqueue_script(
                'asw_importer',
                NW_Plugin::$plugin_url . 'includes/nw-product-synchronisation/assets/js/rpc-asw-importer.js',
                array(
                    'wc-backbone-modal',
                    'jquery-blockui',
                    'jquery-ui-progressbar',
                ),
                rand(10, 100)
            );

            // Enqueue style with its dependencies
            wp_enqueue_style(
                'asw_importer',
                NW_Plugin::$plugin_url . 'includes/nw-product-synchronisation/assets/css/rpc-asw-importer.css',
                array('woocommerce_admin_styles'),
                rand(10, 100)
            );

            add_action('admin_footer', __CLASS__ . '::render_modal');
        }

        /**
         * Add importer button to product edit screen
         *
         * @param string $editor_id What part of the product admin is being output
         */

        public static function asw_importer_button($editor_id)
        {
            global $post;

            if ($editor_id == 'content' && $post->post_status != 'auto-draft') {
                $screen = get_current_screen();
                if ($screen->post_type == 'product' && $screen->id == 'product') { //TODO check product type
                    printf(
                        '<button id="nw-open-asw-import-dialog" type="button" class="button" data-nonce="%s">%s %s</button>',
                        wp_create_nonce('nw-asw-search'),
                        '<span class="dashicons dashicons-download"></span>',
                        __('ASW Import', 'newwave')
                    );
                }
            }
        }

        /**
         * Output the intial dialog HTML for import of products from ASW
         */

        public static function render_modal()
        {
            global $post;
            $saved = $post->post_status != 'auto-draft' ? true : false;
?>
            <script type="text/template" id="tmpl-nw-modal-asw-importer">
                <div class="wc-backbone-modal">
                    <div id="nw-asw-importer" class="nw-modal wc-backbone-modal-content">
                        <section class="wc-backbone-modal-main" role="main">

                            <header class="wc-backbone-modal-header">
                                <h1><?php
                                    if ($saved)
                                        _e('Import from ASW', 'newwave');
                                    else
                                        _e('Import new products from ASW', 'newwave');
                                    ?></h1>
                                <button class="modal-close modal-close-link dashicons dashicons-no-alt"></button>
                            </header>

                            <article>
                                <?php if (!$saved) : ?>
                                <div id="nw-asw-search-wrapper">
                                    <?php 
                                        $product_types = get_option('_nw_product_types', array());

                                        $labels = array(
                                            'variable'       => 'Default(variable)',
                                            'nw_stock'       => 'Stock',
                                            'nw_stock_logo'  => 'Stock with logo',
                                            'nw_special'     => 'Special',
                                            'nw_simple'      => 'Simple',
                                        );

                                        if(count($product_types) > 1){
                                            echo '<select id="nw-asw-product-type" name="nw_product_type">';
                                            foreach($product_types as $type){
                                                echo '<option value="' .$type .'">' .__($labels[$type], 'newwave') .'</option>';
                                            }
                                            echo '</select>';
                                        }
                                    ?>

                                     <input id="nw-asw-product-number" type="text" placeholder="<?php _e('Product number', 'newwave'); ?>" value="" />
                                    <button class="button button-large" id="nw-do-asw-search" data-nonce="<?php echo wp_create_nonce('nw-asw-search'); ?>"><?php _e('Search', 'newwave'); ?></button>
                                </div>
                                <?php endif; ?>

                                <div id="nw-asw-importer-list"></div>

                            </article>
                            <footer>
                                <div class="inner">
                                    <button id="nw-do-asw-import" class="button button-primary button-large" data-nonce="<?php echo wp_create_nonce('nw-asw-import'); ?>" disabled><?php _e('Import', 'newwave'); ?></button>
                                </div>
                            </footer>
                        </section>
                    </div>
		        </div>
		        <div class="wc-backbone-modal-backdrop modal-close"></div>
		    </script>
        <?php
        }

        /**
         * Search for product by calling ASW API
         */

        public static function ajax_asw_search()
        {
            if (!current_user_can('edit_products'))
            {
                static::err(__('The current user does not have permission to edit products.', 'newwave'));
                wp_die();
            }

            check_ajax_referer('nw-asw-search', 'security');

            if (!isset($_POST['product'])) {
                static::err(__('No product number was set.', 'newwave'));
            }

            $sku = isset($_POST['product']) ? sanitize_text_field($_POST['product']) : '';
            $get_only_termid = [];
            $get_only_tagname = [];
            $product_meterial = '';
            $product_description = '';
            $product_brand = 'Craft';
            $product_image_id = '';

            //If the modal is not open by ASW Import button Display warning that a product with the same SKU has already been added
            if (!isset($_POST['current_product_id'])) {
                $product_type = sanitize_text_field($_POST['product_type']);
                if ($product_type == 'nw_stock' && !static::sku_is_unique($sku)){
                    static::err(__('A product with the same product number already and product type "Stock" already exists', 'newwave'));
                }

                //new product importer condition
                // $search = new WP_Query(array(
                //     'post_type' => 'product',
                //     'meta_query' => array(array(
                //         'key'      => '_sku',
                //         'value'    => $sku,
                //     )),
                // ));

                // if ($search->found_posts) {
                //     $warning = sprintf(
                //         '<a href="%s">%s</a>',
                //         get_edit_post_link($search->posts[0]->ID),
                //         __('The product has already been imported!', 'newwave')
                //     );
                //     static::err($warning, 'warning', apply_filters('wc_product_has_unique_sku', false));
                // }
            } else {

                //existing product importer value
                $current_product_id  = sanitize_text_field($_POST['current_product_id']);
                $product_meterial    = get_post_meta($current_product_id, 'nw_product_material', true);
                $product_description = get_post($current_product_id)->post_content;
                $product_brand = get_post_meta($current_product_id, '_brand_name', true);

                $product_categories  = get_the_terms($current_product_id, 'product_cat');

                foreach ($product_categories as $product_category) {
                    $get_only_termid[] = $product_category->term_id;
                }

                $all_product_custom_tags = get_the_terms($current_product_id, 'product_tag');

                foreach ($all_product_custom_tags as $product_tag) {
                    $get_only_tagname[] = $product_tag->name;
                }

                $product_image_id = get_post_thumbnail_id($current_product_id);
                $product_image    = wp_get_attachment_image_src($product_image_id, 'single-post-thumbnail');

                $product_variation = wc_get_product($current_product_id);

                // Build array for comparison; checked rows to re-import existing products
                $existing_skus = array();
                foreach ($product_variation->get_children() as $variation_id) {
                    $variation_sku = get_post_meta($variation_id, '_sku', true);
                    if (!empty($sku)) {
                        $color_code = explode('-', $variation_sku)[1];

                        if (!isset($existing_skus[$color_code]))
                            $existing_skus[$color_code] = array();

                        $existing_skus[$color_code][] = $variation_sku;
                    }
                }
            }

            $image_container_class = '';
            if (empty($product_image_id) || $product_image_id == 0) {
                $image_container_class = 'hidden';
            }

            if (is_array($get_only_tagname)) {
                $product_custom_tags = implode(",", $get_only_tagname);
            } else {
                $product_custom_tags = '';
            }

            if (!empty($sku)) {
                // Do the ASW search
                $product = static::asw_get_product_variations($sku);
            } else {
                $warning = __('SKU is missing or empty.', 'newwave');
                static::err($warning, 'error', true);
            }

            if (!$product || (isset($product['error']) && !isset($product['Description']))) {
                static::err(__('No product found.', 'newwave'));
            }

            require_once(NW_Plugin::$plugin_dir .'includes/nw-product-synchronisation/templates/rpc_asw_search.php');
            wp_die();
        }

        /**
         * Search ASW-function for when importing variations to an existing product,
         * and not creating one from scratch.
         */

        public static function ajax_asw_search_reimport() {
            if (!current_user_can('edit_products')){
                static::err(__('The current user does not have permission to edit products.', 'newwave'));
                wp_die();
            }

            check_ajax_referer('nw-asw-search', 'security');

            if (!isset($_POST['product_id'])) {
                static::err(__('Invalid product', 'newwave'));
            }

            $product_id = sanitize_text_field($_POST['product_id']);
            $product = wc_get_product($product_id);

            if (empty($product->get_sku()))
                static::err(__('The product has no article number. Set one and try again.', 'newwave'));

            $product_info = static::asw_get_product_variations($product->get_sku());
            if (!$product_info || isset($product_info['error']) || !isset($product_info['Description']))
                static::err(__('No product found. Change the article number in the Product Data-settings and try again.', 'newwave'));

            // Build array for comparison; grey out and disable rows to re-import existing products
            $existing_skus = array();
            $existing_skus_count = 0;
            foreach ($product->get_children() as $variation_id) {
                $sku = get_post_meta($variation_id, '_sku', true);
                if (!empty($sku)) {
                    $color_code = explode('-', $sku)[1];

                    if (!isset($existing_skus[$color_code]))
                        $existing_skus[$color_code] = array();

                    $existing_skus[$color_code][] = $sku;
                    $existing_skus_count++;
                }
            }

            // Count number of variations
            $asw_skus_count = 0;
            foreach ($product_info['Colors'] as $color) {
                $asw_skus_count += count($color['Sizes']);
            }

            if ($existing_skus_count == $asw_skus_count) {
                static::err(__('All product variations already imported.', 'newwave'), 'warning');
            }

            static::render_import_table($product_info, $product->get_sku(), $existing_skus);
            wp_die();
        }

        /**
         * Wrapper function for searching for a product through an API call to the ASW server
         *
         * @param int $id of product to check
         * @return mixed[] json decoded array with colors, sizes and their respective SKUs
         */

        public static function asw_get_product_variations($id)
        {
            $id = sanitize_text_field($id);
            $api_url = sanitize_url(get_option('_nwp_asw_endpoint_url'));
            $api_token = sanitize_text_field(get_option('_nwp_asw_auth_token'));
            $url = $api_url . 'checkarticle/' . $id;

            if (empty($api_url) || empty($api_token)) {
                static::err(__('Please add API url and token in settings.', 'newwave'));
                return false;
            }

            try {
                return static::do_api_call(
                    $url,
                    array(
                        'http' => array(
                            'method' => 'GET',
                            'header' => 'X-Custom-API-Key:' . $api_token,
                        )
                    )
                );
            } catch (Exception $e) {
                return false;
            }
        }

        /**
         * Wrapper function to do a HTTP request to a url with options
         *
         * @param string $url of product to check
         * @param array $options for stream_context_create
         * @return mixed json decoded response
         */

        public static function do_api_call($url, $options)
        {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            return json_decode($response, true);
        }

        /**
         * Create a table, listing product variations for import from ASW and pre populated existing product variation.
         *
         * @param mixed[] $product The parsed JSON from the ASW server
         * @param string $sku The base product ASW SKU for the main product
         * @param string[] $compare Array of existing ASW SKUs, for pre populating existing variations
         * @param int $add_new Integar static value
         * @param int $product_id Integar current product ID. for getting product status
         */

        public static function render_import_table_with_existing_data($product_info, $sku, $compare = array(), $add_new = 0, $product_id = 0)
        {
            if (!is_array($product_info) || empty($product_info))
                return;
            $color_img = array();

            foreach ($product_info['Colors'] as $key => $colorArr) {
                $color_img['sku'][$key] = array_column($colorArr['Sizes'], 'SKU')[0];
            }

            $asw_response = static::asw_get_product_info(array_values($color_img['sku']));

            if (isset($asw_response['variations']) && !empty($asw_response['variations'])) {
                foreach ($asw_response['variations'] as $key => $val) {
                    $color_code = 0;
                    $color_code = array_search($val['sku'], $color_img['sku']);
                    if ($color_code) {
                        $color_img['img'][$color_code] = $val['image'][0]['src'];
                    }
                }
            }

            $colspan = ($add_new == 1) ? 5 : 4;
            $class_hidden = ($add_new == 1) ? "" : 'hidden';

            // Render table with existing data
            require_once(NW_Plugin::$plugin_dir .'includes/nw-product-synchronisation/templates/rpc_update_product_table.php');
        }

        /**
         * Create a table, listing product variations for import from ASW.
         *
         * @param mixed[] $product The parsed JSON from the ASW server
         * @param string $sku The base product ASW SKU for the main product
         * @param string[] $compare Array of existing ASW SKUs, for disabling import of existing variations
         */

        public static function render_import_table($product_info, $sku, $compare = array(), $add_new = 0)
        {
            if (!is_array($product_info) || empty($product_info))
                return;
            $color_img = array();

            foreach ($product_info['Colors'] as $key => $colorArr) {
                $color_img['sku'][$key] = array_column($colorArr['Sizes'], 'SKU')[0];
            }

            $asw_response = static::asw_get_product_info(array_values($color_img['sku']));

            if (isset($asw_response['variations']) && !empty($asw_response['variations'])) {
                foreach ($asw_response['variations'] as $key => $val) {
                    $color_code = 0;
                    $color_code = array_search($val['sku'], $color_img['sku']);
                    if ($color_code) {
                        $color_img['img'][$color_code] = $val['image'][0]['src'];
                    }
                }
            }

            $colspan = ($add_new == 1) ? 5 : 4;
            $class_hidden = ($add_new == 1) ? "" : 'hidden';
            
            // Render product import table
            require_once(NW_Plugin::$plugin_dir .'includes/nw-product-synchronisation/templates/rpc_import_product_table.php');
        }

        /**
         * Create a product by sanitizing REST response from ASW server,
         * and store the JSON of product variations to be created on another execution,
         * to allow javascript to control the creation of the variations (basically to
         * allow user feedback on the process, which might take a while
         * if the main product has many variations)
         */

        public static function ajax_create_product()
        {
            if (!current_user_can('edit_products')){
                static::err(__('The current user does not have permission to edit products.', 'newwave'));
                wp_die();
            }

            check_ajax_referer('nw-asw-import', 'security');
            $cdate_arr = [];

            // Collect all custom_date for the color variations selected for import
            if (isset($_POST['cdate_arr']) || !empty($_POST['cdate_arr'])) {
                $cdate_arr['var_custom_date'] = json_decode(stripslashes($_POST['cdate_arr']), true);
                parse_str($_POST['cdate_arr'], $cdate_arr123);
            }
            if (isset($_POST['custom_var_img']) || !empty($_POST['custom_var_img'])) {
                $cdate_arr['custom_var_img'] = $_POST['custom_var_img'];
            }
            if (array_key_exists('nw_asw_product_variant_status', $_POST) && isset($_POST['nw_asw_product_status']) || !empty($_POST['nw_asw_product_status'])) {
                $cdate_arr['nw_asw_product_variant_status'] = json_decode(stripslashes($_POST['nw_asw_product_status']), true);
                parse_str($_POST['nw_asw_product_variant_status'], $cdate_arr123);
            }

            // Collect all SKUs for the color variations selected for import
            if (!isset($_POST['skus']) || empty($_POST['skus'])) {
                static::err(__('No products selected for import', 'newwave'));
            }

            // If product type is to be added as Stock, make sure ASW SKU is unique
            if (!isset($_POST['product_sku']) || empty($_POST['product_sku'])) {
                static::err(__('No valid SKU set.', 'newwave'));
            }

            // Check that submitted product type is a NW Product
            if (!isset($_POST['product_type']) || empty($_POST['product_type'])) {
                NWP_Functions::log('No product type set on import');
                static::err(__('No product type set.', 'newwave'));
            }

            if (!in_array($_POST['product_type'], array('variable', 'nw_stock', 'nw_stock_logo', 'nw_special', 'nw_simple'))) {
                NWP_Functions::log('Invalid product type on ASW import.');
                static::err(__('Invalid product type set.', 'newwave'));
            }

            // Get product info and update attribute and image IDs and things
            parse_str($_POST['skus'], $variation_ids);
            $variation_ids = array_keys($variation_ids['nw_asw_import']);
            $asw_response = static::asw_get_product_info($variation_ids);

            if (!$asw_response)
                static::err(__('The ASW server did not respond correctly.', 'newwave'));

            // Prepare REST Requst array (replace existing images with their attachment id etc.)
            $product = static::prepare_product_request($asw_response);
            $product_type = sanitize_text_field($_POST['product_type']);
            $product['type'] = $product_type;
            $product['regular_price'] = static::calc_tax($product['regular_price']);

            if ($_POST['custom_textarea'] != '') {
                $product['description'] = ($_POST['custom_textarea']);
            }

            // Create the main product
            $request = new WP_REST_Request('POST');
            $request->set_body_params($product);
            $rest_controller = new WC_REST_Products_Controller();
            $wc_response = $rest_controller->create_item($request);

            if (is_wp_error($wc_response) || !isset($wc_response->data)) {
                static::err(__('An error occured while trying to create the main product. ' . print_r($wc_response->errors, true), 'newwave'));
            }

            // Rename images from "Image" to name of product
            $wc_response = $wc_response->data;
            $variations = static::prepare_variations_request($asw_response['variations'], $wc_response, $cdate_arr);

            // Store the variations to be created in the database
            $update_result = update_option('_nw_asw_import_cache_' . $wc_response['id'], maybe_serialize($variations), 'no');
            if (!$update_result) {
                static::err(__('Unable to cache data from ASW Server.', 'newwave'));
            }

            /* Add concept - START */
            if (isset($_POST['concept']) || !empty($_POST['concept'])) {
                $_POST['concept'] = sanitize_text_field($_POST['concept']);
                NW_Product_Property_Concept::set_concept($_POST['concept'], $wc_response['id']);
            }
            /* Add concept - END */

            /* Add Tags - START */
            if (isset($_POST['custom_tag']) || !empty($_POST['custom_tag'])) {
                $custom_tag = sanitize_text_field($_POST['custom_tag']);
                $cust_tag_arr = explode(",", $custom_tag);
                wp_set_object_terms($wc_response['id'], $cust_tag_arr, 'product_tag');
            }
            /* Add Tags - END */

            /* Add categories - START  */
            if (isset($_POST['custom_cat']) || is_array($_POST['custom_cat']) && count($_POST['custom_cat']) ) {
                $custom_cat_arr =  array_map('intval', $_POST['custom_cat']);
                wp_set_object_terms($wc_response['id'], $custom_cat_arr, 'product_cat');
            }
            /* Add categories - END */

            /* Add featured image - START  */
            if (isset($_POST['custom_feature_img']) || !empty($_POST['custom_feature_img'])) {
                $thumbnail_id = intval(sanitize_text_field($_POST['custom_feature_img']));
                set_post_thumbnail($wc_response['id'], $thumbnail_id);
            }
            /* Add featured image - END */

            /** Add product brand - START */
            if (isset($_POST['product_brand']) && !empty($_POST['product_brand'])) {
                add_post_meta($wc_response['id'], '_brand_name', sanitize_text_field($_POST['product_brand']));
            }
            /** Add product brand - END */

            // Print instructions */
            if (isset($_POST['print_instructions']) && $_POST['print_instructions'] != '') {
                add_post_meta($wc_response['id'], 'print_instructions', $_POST['print_instructions']);
            }

            // Short description */
            if (isset($_POST['short_description']) && $_POST['short_description'] != '') {
                wp_update_post(array(
                    'ID'           => $wc_response['id'],
                    'post_excerpt' => $_POST['short_description']
                ));
            }

            //store product material value
            if (isset($_POST['nw_product_material']) && $_POST['nw_product_material'] != '') {
                update_post_meta($wc_response['id'], 'nw_product_material', esc_attr(sanitize_text_field($_POST['nw_product_material'])));
            }

            //store product attribute icons 
            $product_attribute_icon_slugs = array();

            if (isset($_POST['nw_attribute_icons']) && is_array($_POST['nw_attribute_icons'])) {
                update_post_meta($wc_response['id'], 'nw_attribute_icons', '');
                $product_attribute_icon_slugs = $_POST['nw_attribute_icons'];
                $product_attribute_icon_validated = array();
                foreach ($product_attribute_icon_slugs as $product_attribute_icon_slug) {
                    if (array_key_exists($product_attribute_icon_slug, NW_Product_Property_Attribute_Icons::$icons)) {
                        array_push($product_attribute_icon_validated, $product_attribute_icon_slug);
                    }
                }
                update_post_meta($wc_response['id'], 'nw_attribute_icons', maybe_serialize($product_attribute_icon_validated));
            }

            // Return number of variations to create to JS, with an edit link to created product
            $ajax_response = array(
                'edit_link' => sprintf("%spost.php?post=%d&action=edit", get_admin_url(), $wc_response['id']),
                'number_of_variations' => count($variations),
                'cache_id' => $wc_response['id']
            );

            static::delete_predownloaded_images();
            wp_die(json_encode($ajax_response));
        }


        /**
         * AJAX function for re-importing product variations to an existing product
         */

        public static function ajax_update_product()
        {
            if (!current_user_can('edit_products')){
                static::err(__('The current user does not have permission to edit products.', 'newwave'));
                wp_die();
            }

            check_ajax_referer('nw-asw-import', 'security');
            $cdate_arr = [];

            // Collect all custom_date for the color variations selected for import
            if (isset($_POST['cdate_arr']) || !empty($_POST['cdate_arr'])) {
                $cdate_arr['var_custom_date'] = json_decode(stripslashes($_POST['cdate_arr']), true);
                parse_str($_POST['cdate_arr'], $cdate_arr123);
            }
            if (isset($_POST['custom_var_img']) || !empty($_POST['custom_var_img'])) {
                $cdate_arr['custom_var_img'] = $_POST['custom_var_img'];
            }

            if (array_key_exists('nw_asw_product_variant_status', $_POST) && isset($_POST['nw_asw_product_status']) || !empty($_POST['nw_asw_product_status'])) {
                $cdate_arr['nw_asw_product_variant_status'] = json_decode(stripslashes($_POST['nw_asw_product_status']), true);
                parse_str($_POST['nw_asw_product_variant_status'], $cdate_arr123);
            }

            // Collect all SKUs for the color variations selected for import
            if (!isset($_POST['skus']) || empty($_POST['skus'])) {
                static::err(__('No products selected for import', 'newwave'));
            }

            // Missing product id (post_id)
            if (!isset($_POST['product_id']) || !isset($_POST['product_id'])) {
                static::err(__('Product id missing.', 'newwave'));
            }

            // Make sure parent product is of correct type and has an SKU
            $product_id = sanitize_text_field($_POST['product_id']);
            $product = wc_get_product($product_id);

            if (empty($product->get_sku()))
                static::err(__('The product has no article number. Set one and try again.', 'newwave'));

            // Do the ASW search
            parse_str($_POST['skus'], $variation_ids);

            $variation_ids = array_keys($variation_ids['nw_asw_import']);
            $asw_response = static::asw_get_product_info($variation_ids);
            if (!$asw_response)
                static::err(__('The ASW server did not respond correctly.', 'newwave'));

            // Prepare REST array (replacing existing images with their post_id etc.)
            $product_new = static::prepare_product_request($asw_response);
            $product = wc_get_product($product_id);

            // Get all existing attributes
            $attributes = array();
            foreach ($product->get_attributes() as $attribute_slug => $attribute) {
                $options = array();
                foreach ($attribute['options'] as $option) {
                    $options[] = get_term_by('id', $option, $attribute_slug)->name;
                }
                $attributes[] = array(
                    'id' => $attribute['id'],
                    'options' => $options,
                    'visible' => $attribute['visible'],
                    'variation' => $attribute['variation'],
                );
            }

            // Get all new attributes
            foreach ($product_new['attributes'] as $new_attribute) {
                $found = false;
                // Extend the options for existing attributes, make sure they're unique
                foreach ($attributes as &$attribute) {
                    if ($new_attribute['id'] == $attribute['id']) {
                        $attribute['options'] = array_unique(array_merge($attribute['options'], $new_attribute['options']));
                        $found = true;
                    }
                }
                // If a completely new attribute
                if (!$found) {
                    $attributes[] = $new_attribute;
                }
            }

            $images = array();

            // Add existing images to the REST Request
            $existing_imgs = $product->get_gallery_image_ids();

            $pos = 0;
            foreach ($existing_imgs as $img) {
            	$images[] = array(
            		'id' => $img,
            		'pos' => $pos++,
            	);
            }

            // Add new images to the REST Request
            foreach ($product_new['images'] as $img) {
            	if (isset($img['src'])) {
            		$images[] = array(
            			'src' => $img['src'],
            			'name' => $img['name'],
            			'pos' => $pos++,
            		);
            	}
            	else if (!in_array($img['id'], $existing_imgs)) {
            		$images[] = array(
            			'id' => $img['id'],
            			'pos' => $pos++,
            		);
            	}
            }

            // Submit the update of the main product
            $request = new WP_REST_Request('POST');
            $request->set_body_params(array(
                'id' => $product_id,
                'attributes' => $attributes,
                'images' => $images,
                // 'description' => ($_POST['custom_textarea'])
            ));


            $rest_controller = new WC_REST_Products_Controller();
            $wc_response = $rest_controller->update_item($request);

            if (is_wp_error($wc_response) || !isset($wc_response->data)) {
                static::err(__('Error while updating main product.', 'newwave'));
            }

            $wc_response = $wc_response->data;

            // Prepare to create variations
            // $variations = static::prepare_variations_request($asw_response['variations'], $wc_response);

            //Store the variations to be created in the database
            // $update_result = update_option('_nw_asw_import_cache_'.$wc_response['id'], maybe_serialize($variations), 'no');
            // if (!$update_result) {
            // 	static::err(__('Unable to cache data from ASW Server.', 'newwave'));
            // }

            // Prepare to create variations
            $variations = static::prepare_variations_request($asw_response['variations'], $wc_response, $cdate_arr);

            // Store the variations to be created in the database
            $update_result = update_option('_nw_asw_import_cache_' . $wc_response['id'], maybe_serialize($variations), 'no');

            if (!$update_result) {
                static::err(__('Unable to cache data from ASW Server.', 'newwave'));
            }

            /* Add concept - START */
            if (isset($_POST['concept']) && !empty($_POST['concept'])) {
                NW_Product_Property_Concept::set_concept(sanitize_text_field($_POST['concept']), $wc_response['id']);
            }
            /* Add concept - END */

            /* Add Tags - START */
            if (isset($_POST['custom_tag'])) {
                $custom_tag = sanitize_text_field($_POST['custom_tag']);
                $cust_tag_arr = explode(",", $custom_tag);
                wp_set_object_terms($wc_response['id'], $cust_tag_arr, 'product_tag');
            }
            /* Add Tags - END */

            /* Add categories - START  */
            if (isset($_POST['custom_cat']) || is_array($_POST['custom_cat']) && count($_POST['custom_cat'])) {
                $custom_cat_arr =  array_map('intval', $_POST['custom_cat']);
                wp_set_object_terms($wc_response['id'], $custom_cat_arr, 'product_cat');
            }
            /* Add categories - END */

            /* Add featured image - START  */
            if (isset($_POST['custom_feature_img'])) {
                $thumbnail_id = intval(sanitize_text_field($_POST['custom_feature_img']));
                set_post_thumbnail($wc_response['id'], $thumbnail_id);
            }
            /* Add featured image - END */

            /** Add product brand - START */
            if (isset($_POST['product_brand']) && !empty($_POST['product_brand'])) {
                update_post_meta($wc_response['id'], '_brand_name', sanitize_text_field($_POST['product_brand']));
            }
            /** Add product brand - END */

            //store product material value
            if (isset($_POST['nw_product_material']) && $_POST['nw_product_material'] != '') {
                update_post_meta($wc_response['id'], 'nw_product_material', esc_attr(sanitize_text_field($_POST['nw_product_material'])));
            }

            //store product attribute icons 
            $product_attribute_icon_slugs = array();

            if (isset($_POST['nw_attribute_icons']) && is_array($_POST['nw_attribute_icons'])) {
                update_post_meta($wc_response['id'], 'nw_attribute_icons', '');
                $product_attribute_icon_slugs = $_POST['nw_attribute_icons'];
                $product_attribute_icon_validated = array();
                foreach ($product_attribute_icon_slugs as $product_attribute_icon_slug) {
                    if (array_key_exists($product_attribute_icon_slug, NW_Product_Property_Attribute_Icons::$icons)) {
                        array_push($product_attribute_icon_validated, $product_attribute_icon_slug);
                    }
                }
                update_post_meta($wc_response['id'], 'nw_attribute_icons', maybe_serialize($product_attribute_icon_validated));
            }

            // Return number of variations to create to JS, with an edit link to created product
            $ajax_response = array(
                'edit_link' => sprintf("%spost.php?post=%d&action=edit", get_admin_url(), $wc_response['id']),
                'number_of_variations' => count($variations),
                'cache_id' => $wc_response['id']
            );

            static::delete_predownloaded_images();
            wp_die(json_encode($ajax_response));
        }

        /**
         * Get product info (price, images etc.) from ASW server
         *
         * @param string $ids of products to import
         */

        public static function asw_get_product_info($ids)
        {
            $api_url = sanitize_url(get_option('_nwp_asw_endpoint_url'));
            $api_token = sanitize_text_field(get_option('_nwp_asw_auth_token'));
            $url = $api_url . 'articleinfo';

            if (empty($api_url) || empty($api_token)) {
                static::err(__('Please add API url and token in settings.', 'newwave'));
                return false;
            }

            try {
                return static::do_api_call(
                    $url,
                    array(
                        'http' => array(
                            'method' => 'POST',
                            'header' => "Content-Type: application/json\r\n" . 'X-Custom-API-Key:' . $api_token,
                            'content' => json_encode($ids)
                        )
                    )
                );
            } catch (Exception $e) {
                error_log('ASW Importer - some error occured while getting ASW article: ' . $e->getMessage());
                return false;
            }
        }

        /**
         * Create a product variation through an AJAX call,
         * based on the cached API call, and clear the cache on the last call
         */

        public static function ajax_create_variation()
        {
            if (!isset($_POST['variation_index']))
                static::err(__('No variations index was set.', 'newwave'));
            else
                $index = absint($_POST['variation_index']);

            if (!isset($_POST['cache_id']))
                static::err(__('No cache ID was set.', 'newwave'));
            else
                $cache_id = absint($_POST['cache_id']);

            check_ajax_referer('nw-asw-import', 'security');

            // Get variation array
            $variations = maybe_unserialize(get_option('_nw_asw_import_cache_' . $cache_id));
            if ($index >= count($variations)) {
                static::err(__('Variation index was out of bounds.', 'newwave'));
            }

            // Create the variation
            $request = new WP_REST_Request('POST');
            $request->set_body_params($variations[$index]);
            $rest_controller = new WC_REST_Product_Variations_Controller();
            $response = $rest_controller->create_item($request);
            $response = $response->data;
            $formatted_date = date("Y-m-d H:i:s");
            if ($variations[$index]['custom_date'][0]) {
                $formatted_date = date("Y-m-d H:i:s", strtotime($variations[$index]['custom_date'][0]));
            }

            if ($variations[$index]['custom_status'][0]) {
                $variation_status = $variations[$index]['custom_status'][0];
            }

            update_post_meta($response['id'], 'custom_date', $formatted_date);

            wp_update_post(array('ID' => $response['id'], 'post_status' => $variation_status, 'post_type' => 'product_variation'));

            // Created the last variation, delete the stored option
            if (($index + 1) == count($variations))
                delete_option('_nw_asw_import_cache_' . $cache_id);

            wp_die(1); // Tell client side that import was successful
        }


        /**
         * Replaces attributes and images with their corresponding WordPress ID,
         * if it existings. Attributes are limited to 'size' and 'color',
         * and image that have been downloaded before its replaced with an ID as reference
         *
         * @param array $asw_response The 'get article info' REST response from the ASW server
         * @return array The modified product of the request ($asw_response['article'])
         */

        private static function prepare_product_request($asw_response)
        {
            $product = $asw_response['article'];

            // Replace attributes with their correct term IDs
            $prepared_attrs = array();

            $color_id = wc_attribute_taxonomy_id_by_name('color');
            $size_id = wc_attribute_taxonomy_id_by_name('size');

            foreach ($product['attributes'] as $attribute) {
                if (isset($attribute['name'])) {
                    if ($attribute['name'] == 'color') {
                        $attribute['id'] = $color_id;
                        unset($attribute['name']);
                        $prepared_attrs[] = $attribute;
                    } else if ($attribute['name'] == 'size') {
                        $attribute['id'] = $size_id;
                        unset($attribute['name']);
                        $prepared_attrs[] = $attribute;
                    }

                    // Replace description text with ProductCommerceText
                    //TODO use a separate function for this maybe?
                    else if ($attribute['name'] == 'ProductCommerceText' && strlen($attribute['options'][0])) {
                        $product['description'] = $attribute['options'][0];
                    }
                }
            }

            // Finally set the attributes as part of the request
            $product['attributes'] = $prepared_attrs;

            /* Collect all images of each of the variations in the ASW server response,
            and add them to the the main product request so that the main product gets an image gallery.
            Also replace any urls of images already existing in the media library with their attachment ID
            */
            $prepared_imgs = $unique_images = array();

            // Get lookup for existing images
            $existing_images = static::get_image_lookup();

            $pos = 0;
            foreach ($asw_response['variations'] as $key => $variation) {
                // Skip variation if no image is set
                if (!isset($variation['image'][0]['src']) || empty($variation['image'][0]['src']))
                    continue;

                $img_url = $variation['image'][0]['src'];
                $name = static::extract_filename($img_url);
                if (!$name) // No name could be extracted, skip variation
                    continue;

                // Skip adding image if it has already been listed for this product creation
                if (in_array($name, $unique_images))
                    continue;
                else // mark as listed
                    array_push($unique_images, $name);

                // If already uploaded, replace with attachment ID
                if (array_key_exists($name, $existing_images)) {
                    $prepared_imgs[] = array(
                        'id' => $existing_images[$name],
                        'position' => $pos++,
                    );
                }

                // New image, so leave the URL as is, but give a filename
                // if not placeholder image containg 'noimage' in URL
                else if (strpos(strtolower($img_url), 'noimage') === false) {
                    // Maybe predownload image to avoid 'Image.aspx' problem
                    $img_url = static::maybe_predownload_image($img_url, $name);

                    $prepared_imgs[] = array(
                        'src' => $img_url,
                        'name' => $name,
                        'position' => $pos++,
                    );
                }
            } // Finally set the images as part of the request
            $product['images'] = $prepared_imgs;

            return $product;
        }

        /**
         * Prepare variation request for creation by replacing already uploaded img urls with
         * their attachment ID and attributes names (color, size) with their attribute ID
         *
         * @param WP_REST_Response $response Response object for the main product
         * @return array Associative array the number of variations to be created,
         * and the edit link to the product
         */

        private static function prepare_variations_request($variations, $wc_response, $cdate_arr = array())
        {
            $color_id = wc_attribute_taxonomy_id_by_name('color');
            $size_id = wc_attribute_taxonomy_id_by_name('size');

            // Extract image IDs from the main product wc_response
            $img_ids = array();
            if (isset($wc_response['images'])) {
                foreach ($wc_response['images'] as $img)
                    $img_ids[$img['name']] = $img['id'];
            }

            foreach ($variations as &$variation) {
                $variation['product_id'] = $wc_response['id'];
                $variation['regular_price'] = static::calc_tax($variation['regular_price']);

                // Replace valid attribute names the correct IDs
                $attributes = array();
                $current_color = '';
                foreach ($variation['attributes'] as $attribute) {
                    if ($attribute['name'] == 'color') {
                        $attribute['id'] = $color_id;
                        unset($attribute['name']);
                        $attributes[] = $attribute;
                        if (!empty($cdate_arr['var_custom_date'])) {
                            $op = $attribute['option'];
                            $variation['custom_date'][] = $cdate_arr['var_custom_date']['cdate_' . $attribute['option']];
                        }
                        if (!empty($cdate_arr['nw_asw_product_variant_status'])) {
                            $variation['custom_status'][] = $cdate_arr['nw_asw_product_variant_status']['nw_asw_product_variant_status_' . $attribute['option']];
                        }

                        $current_color = $attribute['option'];
                    } else if ($attribute['name'] == 'size') {
                        $attribute['id'] = $size_id;
                        unset($attribute['name']);
                        $attributes[] = $attribute;
                    }
                }
                $variation['attributes'] = $attributes;

                if ($current_color != '' && $cdate_arr['custom_var_img']['customImg_' . $current_color]) //incase custom image is setup from popup
                {
                    $variation['image'] = array('id' => $cdate_arr['custom_var_img']['customImg_' . $current_color]);
                } else {
                    // Replace images with their WP attachment ID
                    if (isset($variation['image'][0]['src'])) {
                        $name = static::extract_filename($variation['image'][0]['src']);
                        unset($variation['image']);

                        if (isset($img_ids[$name]))
                            $variation['image'] = array('id' => $img_ids[$name]);
                    } else
                        unset($variation['image']);
                }
            }

            return $variations;
        }

        /**
         * Outputs categories and subcategory list
         */

        public static function category_list_html($tax_id, $tax_array, $get_only_termid)
        {
            if (array_key_exists('child_id', $tax_array[$tax_id]) && is_array($tax_array[$tax_id]['child_id'])) {
            ?>
                <ul>
                    <?php
                    foreach ($tax_array[$tax_id]['child_id'] as $key => $val) {
                        if (array_key_exists('child_id', $tax_array[$val]) && is_array($tax_array[$val]['child_id'])) {
                            if (in_array($val, $get_only_termid)) {
                    ?>
                                <li>
                                    <input type="checkbox" name="cust_cat[]" value="<?= $val ?>" class="custom_cat_checkbox" checked><?= $tax_array[$val]['name'] ?>
                                    <?php
                                    static::category_list_html($val, $tax_array, $get_only_termid);
                                    ?>
                                </li>
                            <?php
                            } else {
                            ?>
                                <li>
                                    <input type="checkbox" name="cust_cat[]" value="<?= $val ?>" class="custom_cat_checkbox"><?= $tax_array[$val]['name'] ?>
                                    <?php
                                    static::category_list_html($val, $tax_array, $get_only_termid);
                                    ?>
                                </li>
                            <?php
                            }
                        } else {
                            if (in_array($val, $get_only_termid)) {
                            ?>
                                <li>
                                    <input type="checkbox" name="cust_cat[]" value="<?= $val ?>" class="custom_cat_checkbox" checked><?= $tax_array[$val]['name'] ?>
                                </li>
                            <?php
                            } else {
                            ?>
                                <li>
                                    <input type="checkbox" name="cust_cat[]" value="<?= $val ?>" class="custom_cat_checkbox"><?= $tax_array[$val]['name'] ?>
                                </li>
                    <?php
                            }
                        }
                    }
                    ?>
                </ul>
<?php
            }
        }

        private static function delete_predownloaded_images()
        {
            $images = glob(NW_PLUGIN_DIR . 'tmp/*');

            foreach ($images as $image) {
                if (is_file($image)) {
                    unlink($image);
                }
            }
        }

        /**
         * Extract and format filename from URL of a downloadable image
         * e.g. https://api.nwg.se/NWGApi/v1/pim/download/199205_1999_Active_Run_T_M.jpg
         * becomes ['name' => 199205_1999_Active_Run_T_M, 'extension' => 'jpg']
         *
         * @param string $image_url Image URL
         * @return string|bool Extracted image name or false if no match
         */

        public static function extract_filename($image_url)
        {
            // Convert url-encoded spaces to underscores
            $image_url = str_replace(array('%20', '-'), '_', $image_url);

            // Extract name
            preg_match('/([^\/&=:\/]*)\.(jpg|jpeg|png)/', $image_url, $matches);

            if (!$matches)
                return false;

            return sanitize_file_name($matches[1]);
        }

        /**
         * Manually predownload images, to avoid all images being named after download URL,
         * aka Image.jpg, rather than their corresponding product name,
         * and to avoid Wordpress interpreting Image.aspx as filetype and failing
         * the upload due to a "invalid MIME-type"-error
         *
         * @param  [type] $url  Image url
         * @param  [type] $name Image name
         * @return [type]       Local url
         */

        private static function maybe_predownload_image($url, $filename)
        {
            preg_match('/\.\w{3,4}($|\?)/', $url, $extension);
            $valid_extensions = ['.jpg', '.jpeg', '.png'];

            // URL directly links to an image file
            if (!$extension || in_array($extension[0], $valid_extensions)) {
                return $url;
            }

            if (!function_exists('download_url')) {
                include_once ABSPATH . 'wp-admin/includes/file.php';
            }

            if (!file_exists(NW_PLUGIN_DIR . 'tmp')) {
                mkdir(NW_PLUGIN_DIR . 'tmp', 0777, true);
            }

            $tmp = download_url($url);
            $dst = 'tmp/' . $filename . $extension[0];
            rename($tmp, NW_PLUGIN_DIR . $dst);

            return NW_PLUGIN_URL . $dst;
        }


        /**
         * Make sure the attributes (taxonomies) color & size exists
         */

        public static function register_product_attributes()
        {
            if (!wc_check_if_attribute_name_is_reserved('color')) {
                wc_create_attribute(array(
                    'name' => __('Color', 'newwave'),
                    'slug' => 'color'
                ));
            }
            if (!wc_check_if_attribute_name_is_reserved('size')) {
                wc_create_attribute(array(
                    'name' => __('Size', 'newwave'),
                    'slug' => 'size'
                ));
            }
        }


        /**
         * Wrapper function to output error message on AJAX call
         *
         * @param string $msg Message to output
         * @param string $type Type of message, either 'error' or 'warning'
         * @param bool $die Whether to stop execution or not
         */

        public static function err($msg = '', $type = 'error', $die = true)
        {
            if ('' === $msg)
                $msg = __('Something went wrong. Try again. If the problem keeps occuring, contact the web-administrator', 'newwave');

            printf('<span class="nw-%s">%s</span>', $type, $msg);

            if ($die)
                wp_die();
        }

        /**
         * Calculate price including taxes if applicable
         *
         * @param float|int $price Without tax
         * @return float|int $price With tax
         */

        public static function calc_tax($price)
        {
            if (wc_tax_enabled() && wc_prices_include_tax() && $nw_setting) {
                $tax = WC_Tax::get_shop_base_rate();
                $tax = reset($tax);
                $price = $price * (1 + ($tax['rate'] / 100));
            }
            return $price;    
        }

        public static function get_image_lookup()
        {
            $image_search = new WP_Query(array(
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'posts_per_page' => -1,
                'post_mime_type' => 'image/jpeg,image/gif,image/jpg,image/png',
            ));

            // Create a lookup for existing images
            $existing_images = array();
            foreach ($image_search->posts as $image)
                $existing_images[$image->post_title] = $image->ID;

            return $existing_images;
        }

        /**
         * Check that $product_number has a unique ASW SKU; terminates WP if not.
         * Only used when adding a product of type NW_Stock through an AJAX call
         *
         * @param string $product_number The ASW SKU
         * @return bool True if unique
         * @throws Exception with error message if not unique
         */

         private static function sku_is_unique($product_number)
         {
             $search = new WP_Query(array(
                 'post_type' => 'product',
                 'meta_query' => array(array(
                     'key'      => '_sku',
                     'value'    => $product_number,
                 )),
                 'tax_query' => array(array(
                     'taxonomy' => 'product_type',
                     'field'         => 'slug',
                     'terms'         => 'nw_stock',
                 ))
             ));
 
             if (!$search->found_posts)
                 return true;
 
             return false;
         }
    }

