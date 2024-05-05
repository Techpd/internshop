<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Imports products from a temporary ASW API server
     */

    class NWP_ASW_Importer
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

            // Include the importer
            add_action('admin_head', __CLASS__ . '::include_importer');

            // Register AJAX calls
            add_action('wp_ajax_nwp_asw_search', __CLASS__ . '::ajax_asw_search');
            add_action('wp_ajax_nwp_asw_search_reimport', __CLASS__ . '::ajax_asw_search_reimport');
            add_action('wp_ajax_nwp_create_product', __CLASS__ . '::ajax_create_product');
            add_action('wp_ajax_nwp_update_product', __CLASS__ . '::ajax_update_product');
            add_action('wp_ajax_nwp_create_variation', __CLASS__ . '::ajax_create_variation');

            // Add the button to trigger the import
            add_action('media_buttons', __CLASS__ . '::asw_importer_button');
        }

        /**
         * Add hooks and filters
         *
         * @param string $editor_id What part of the product admin is being output
         */

        public static function asw_importer_button($editor_id)
        {
            global $post;
            if ($editor_id == 'content' && $post->post_status != 'auto-draft') {
                $screen = get_current_screen();
                if ($screen->post_type == 'product' && $screen->id == 'product') {
                    printf(
                        '<button id="nwp-open-asw-import-dialog" type="button" class="button" data-nonce="%s">%s %s</button>',
                        wp_create_nonce('nwp-asw-search'),
                        '<span class="dashicons dashicons-download"></span>',
                        __('Import from ASW', 'newwave')
                    );
                }
            }
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
                    return $classes . ' nwp-new-product ';
                }, 99, 1);

            // Enqueue Select2 CSS
            wp_enqueue_style('nwselect2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0', 'all');

            // Enqueue jQuery Select2 library
            wp_enqueue_script('nwselect2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0-rc.0', true);

            // Enqueue importer script with its dependencies
            wp_enqueue_script(
                'nwp_asw_importer',
                NW_Plugin::$plugin_url . 'includes/nw-product-synchronisation/assets/js/graphql-asw-importer.js',
                array(
                    'wc-backbone-modal',
                    'jquery-blockui',
                    'jquery-ui-progressbar',
                ),
                rand(10, 100)
            );

            // Enqueue importer style with its dependencies
            wp_enqueue_style(
                'nwp_asw_importer',
                NW_Plugin::$plugin_url . 'includes/nw-product-synchronisation/assets/css/graphql-asw-importer.css',
                array('woocommerce_admin_styles'),
                rand(10, 100)
            );

            add_action('admin_footer', __CLASS__ . '::render_modal');
        }

        /**
         * Output the intial dialog HTML for import of products from ASW
         */

        public static function render_modal()
        {
            global $post;
            $saved = $post->post_status != 'auto-draft' ? true : false;
?>
            <script type="text/template" id="tmpl-nwp-modal-asw-importer">
                <div class="wc-backbone-modal">
			        <div id="nwp-asw-importer" class="nwp-modal wc-backbone-modal-content" style="width:75%;">
				        <section class="wc-backbone-modal-main" role="main">
                            <!-- Header -->
                            <header class="wc-backbone-modal-header">
                                <h1>
                                    <?php
                                    if ($saved)
                                        _e('Import from ASW', 'newwave');
                                    else
                                        _e('Import new product from ASW', 'newwave');
                                    ?>
                                </h1>
                                <button class="modal-close modal-close-link dashicons dashicons-no-alt"></button>
                            </header>

                            <!-- Article -->
                            <article>
                                <?php if (!$saved) : ?>
                                <div id="nwp-asw-search-wrapper">
                                    <select id="nwp-asw-product-type" name="nw_product_type">
                                        <option value="nw_stock"><?php _e('Stock', 'newwave'); ?></option>
                                        <option value="nw_stock_logo"><?php _e('Stock with logo', 'newwave'); ?></option>
                                        <option value="nw_special"><?php _e('Special', 'newwave'); ?></option>
                                    </select>

                                    <input id="nwp-asw-product-number" type="text" placeholder="<?php _e('Product number', 'newwave'); ?>" value="" />

                                    <button class="button button-large" id="nwp-do-asw-search" data-nonce="<?php echo wp_create_nonce('nwp-asw-search'); ?>"><?php _e('Search', 'newwave'); ?></button>
                                </div>
                                <?php endif; ?>

                                <div id="nwp-asw-importer-list"></div>

                            </article>

                            <footer>
                                <div class="inner">
                                    <button id="nwp-do-asw-import" class="button button-primary button-large" data-nonce="<?php echo wp_create_nonce('nwp-asw-import'); ?>" disabled><?php _e('Import', 'newwave'); ?></button>
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
         * Search for product variations by calling ASW server.
         */

        public static function ajax_asw_search()
        {
            if (!current_user_can('edit_products')){
                static::err(__('The current user does not have permission to edit products.', 'newwave'));
                wp_die();
            }
            check_ajax_referer('nwp-asw-search', 'security');

            if (!isset($_POST['product']) || !isset($_POST['product_type'])) {
                NWP_Functions::log('An ASW search error occured while searching for product number ' . sanitize_text_field($_POST['product']) . '.');
                printf('<span class="nwp-error">%s</span>', __('An error occured. Please contact the web administrator.', 'newwave'));
                wp_die();
            }

            $sku = isset($_POST['product']) ? sanitize_text_field($_POST['product']) : '';
            $product_type = isset($_POST['product_type']) ? sanitize_text_field($_POST['product_type']) : '';

            if (empty($sku)) {
                static::err(__('SKU is missing or empty', 'newwave'));
            }

            if (!in_array($product_type, array('nw_stock', 'nw_stock_logo', 'nw_special'))) {
                NWP_Functions::log('Someone tried to create a product of type ' . $product_type);
                static::err(__('Invalid product type.', 'newwave'));
            }

            if ($product_type == 'nw_stock' && !static::sku_is_unique($sku))
                static::err(__('A product with the same product number already and product type "Stock" already exists', 'newwave'));

            // Do the ASW search
            $product = static::asw_get_product_info($sku);
            if ($product && !$product['data'] || isset($product['error']) || isset($product['errors'])) {
                static::err(__($product['errors'][0]['message'], 'newwave'));
            }

            $product_custom_tags = '';
            $get_only_termid = [];
            $product_description = isset($product['data']['productById']['productText']) ? sanitize_text_field($product['data']['productById']['productText']) : '';
            $product_brand = '';
            if(isset($product['data']['productById']['productBrand'])){
                $product_brand = $product['data']['productById']['productBrand'];
            }
        ?>
            <div class='wrapper-div'>
                <div class='left-div' style='float:left;margin-right:10px;width:15%;'>
                    <?php
                    $args = array(
                        'post_type'         => 'nw_club',
                        'posts_per_page'     => -1
                    );

                    $query = new WP_Query($args);

                    // If the product type is stock item, show a list of cbs for clubs
                    if ($product_type == 'nw_stock') {
                        if ($query->have_posts()) {
                    ?>
                            <div class='custom_clubs_div pop_widget' style='border: 1px solid #ddd;padding: 6px;'>
                                <ul>
                                    <li><b><?= __('Velg alle butikker', 'newwave') ?></b>
                                        <input type='checkbox' id='select_all_club' name='select_all_club' value='' style='float: right;margin-top: 3px;'>
                                    </li>
                                    <?php

                                    while ($query->have_posts()) {
                                        $query->the_post();
                                    ?>
                                        <li>
                                            <?php the_title(); ?>
                                            <input style="float: right;margin-top: 3px;" type="checkbox" name="nw_club[]" value="<?php the_ID(); ?>" />
                                        </li>
                                    <?php
                                    }
                                    ?>
                                </ul>
                            </div>

                        <?php
                            wp_reset_postdata();
                        }
                    }

                    // If the product type is stock item with logo, show a dropdown with search for clubs
                    if ($product_type == 'nw_stock_logo' || $product_type == 'nw_special') {
                        if ($query->have_posts()) {
                        ?>
                            <div class='custom_clubs_div pop_widget' style='border: 1px solid #ddd;padding: 6px;'>
                                <b><?= __('Butikker', 'newwave') ?></b>
                                <select name="select_club" style="width:100%">
                                    <?php
                                    while ($query->have_posts()) {
                                        $query->the_post();
                                    ?>
                                        <option value="<?php the_ID(); ?>"><?php the_title(); ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php
                        }
                    }

                    // Add print instructions for stock item with logo
                    if ($product_type == 'nw_stock_logo') {
                        ?>
                        <!-- Print instructions -->
                        <div class='spacer_div' style='margin-top:10px;'></div>

                        <div class='custom_textarea_div ' style='border: 1px solid #ddd;padding: 6px;'>
                            <label> <?= __('Trykktekst', 'newwave') ?> </label><br>
                            <textarea id='print_instructions' name='print_instructions' rows='3' cols='5' style='height:100px; width:100%'></textarea>
                        </div>
                        <!-- Print instructions end -->
                    <?php
                    }

                    // Add product short description if the product type is nw_stock_logo or nw_special
                    if ($product_type == 'nw_stock_logo' || $product_type == 'nw_special') {
                    ?>
                        <!-- Short description -->
                        <div class='spacer_div' style='margin-top:10px;'> </div>

                        <div class='custom_textarea_div ' style='border: 1px solid #ddd;padding: 6px;'>
                            <label> <?= __('Kort beskrivelse', 'newwave') ?> </label><br>
                            <textarea id='short_description' name='short_description' rows='3' cols='5' style='height:100px; width:100%'></textarea>
                        </div>
                        <!-- Short description end -->
                    <?php
                    }
                    ?>
                </div>
                <?php
            
            require_once(NW_Plugin::$plugin_dir .'includes/nw-product-synchronisation/templates/graphql_asw_search.php');
            wp_die();
        }


        /**
         * Search ASW-function for when importing variations to an existing product,
         * and not creating one from scratch.
         */

        public static function ajax_asw_search_reimport()
        {
            if (!current_user_can('edit_products'))
            {
                static::err(__('The current user does not have permission to edit products.', 'newwave'));
                wp_die();
            }

            check_ajax_referer('nwp-asw-search', 'security');

            if (!isset($_POST['product_id'])) {
                NWP_Functions::log('Tried to re-import with an empty product_id');
                static::err(__('Invalid product', 'newwave'));
            }

            $product_id = sanitize_text_field($_POST['product_id']);
            $product = wc_get_product($product_id);

            if (empty($product->get_sku()))
                static::err(__('The product has no article number. Set one and try again.', 'newwave'));

            $product_info = static::asw_get_product_info($product->get_sku());

            if (!$product_info || isset($product_info['error']) || !isset($product_info['data']['productById']['productName']))
                static::err(__('No product found. Change the article number in the Product Data-settings and try again.', 'newwave'));

            // Build array for comparison; grey out and disable rows to re-import existing products
            $existing_skus = array();
            $existing_skus_count = 0;
            foreach ($product->get_children() as $variation_id) {
                $variation = wc_get_product($variation_id);
                $sku = $variation->get_sku();

                if ($sku) {
                    $color_code = explode('-', $sku)[1];

                    if (!isset($existing_skus[$color_code]))
                        $existing_skus[$color_code] = array();

                    $existing_skus[$color_code][] = $sku;
                    $existing_skus_count++;
                }
            }

            // Count number of variations
            $asw_skus_count = 0;
            foreach ($product_info['data']['productById']['variations'] as $color) {
                $asw_skus_count += count($color['skus']);
            }

            if ($existing_skus_count >= $asw_skus_count) {
                static::err(__('All product variations already imported.', 'newwave'), 'warning');
            }

            static::render_import_table($product_info, $product->get_sku(), $existing_skus);
            wp_die();
        }

        /**
         * Create a table, listing product variations for import from ASW.
         *
         * @param mixed[] $product The parsed JSON from the ASW server
         * @param string $sku The base product ASW SKU for the main product
         * @param string[] $compare Array of existing ASW SKUs, for disabling import of existing variations
         */

        public static function render_import_table($product_info, $sku, $compare = array())
        {
            if (!is_array($product_info) || empty($product_info))
                return;

            require_once(NW_Plugin::$plugin_dir .'includes/nw-product-synchronisation/templates/graphql_import_product_table.php');
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

        /**
         * Create a product by sanitizing REST response from ASW server,
         * and store the JSON of product variations to be created on another execution,
         * to allow javascript to control the creation of the variations (basically to
         * allow user feedback on the process, which might take a while
         * if the main product has many variations)
         */

        public static function ajax_create_product()
        {
            if (!current_user_can('edit_products'))
            {
                static::err(__('The current user does not have permission to edit products.', 'newwave'));
                wp_die();
            }

            check_ajax_referer('nwp-asw-import', 'security');

            $cdate_arr = [];

            // Collect all custom_date for the color variations selected for import
            if (isset($_POST['cdate_arr']) || !empty($_POST['cdate_arr'])) {
                $cdate_arr['var_custom_date'] = json_decode(stripslashes($_POST['cdate_arr']), true);
            }
            if (isset($_POST['custom_var_img']) || !empty($_POST['custom_var_img'])) {
                $cdate_arr['custom_var_img'] = $_POST['custom_var_img'];
            }
            if (isset($_POST['nwp_asw_product_status']) || !empty($_POST['nwp_asw_product_status'])) {
                $cdate_arr['nwp_asw_product_variant_status'] = json_decode(stripslashes($_POST['nwp_asw_product_status']), true);
            }

            // Collect all SKUs for the color variations selected for import
            if (!isset($_POST['skus']) || empty($_POST['skus'])) {
                NWP_Functions::log('Someone submitted an empty SKU list on ASW import.');
                static::err(__('No products selected for import', 'newwave'));
            }

            // Check that submitted product type is a NW Product
            if (!isset($_POST['product_type']) || empty($_POST['product_type'])) {
                NWP_Functions::log('No product type set on import');
                static::err(__('No product type set.', 'newwave'));
            }

            $product_type = sanitize_text_field($_POST['product_type']);

            if (!in_array($_POST['product_type'], array('nw_stock', 'nw_stock_logo', 'nw_special'))) {
                NWP_Functions::log('Invalid product type on ASW import.');
                static::err(__('Invalid product type set.', 'newwave'));
            }

            // If product type is to be added as Stock, make sure ASW SKU is unique
            if (!isset($_POST['product_sku']) || empty($_POST['product_sku'])) {
                NWP_Functions::log('Empty product SKU on ASW import product creation');
                static::err(__('No valid SKU set.', 'newwave'));
            }

            $sku = sanitize_text_field($_POST['product_sku']);
            if ($product_type == 'nw_stock' && !static::sku_is_unique($sku))
                static::err(__('A product with the same product number already and product type "Stock" already exists', 'newwave'));

            // Get product info and update attribute and image IDs and things
            parse_str($_POST['skus'], $variation_ids);
            $variation_ids = array_keys($variation_ids['nw_asw_import']);

            $asw_response = static::asw_get_product_info($sku);

            if (!$asw_response)
                static::err(__('The ASW server did not respond correctly.', 'newwave'));

            // Prepare REST Requst array (replace existing images with their post_id etc.)
            $product = static::prepare_product_request($asw_response, $variation_ids);
            $product['sku'] = $sku;
            $product['type'] = $product_type;
            $product['regular_price'] = static::get_regular_price($asw_response['data']['productById']['prices']);

            if ($_POST['custom_textarea'] != '') {
                $product['description'] = sanitize_textarea_field($_POST['custom_textarea']);
            }

            // Create the main product
            $request = new WP_REST_Request('POST');
            $request->set_body_params($product);
            $rest_controller = new WC_REST_Products_Controller();
            $wc_response = $rest_controller->create_item($request);

            if (is_wp_error($wc_response) || !isset($wc_response->data)) {
                NWP_Functions::log('Error while creating main product', array(
                    'payload' => $product,
                    'results' => $wc_response
                ));
                static::err(__('An error occured while trying to create the main product.', 'newwave'));
            }

            $wc_response = $wc_response->data;
            $variations = static::prepare_variations_request($asw_response, $variation_ids, $wc_response, $cdate_arr,$product['nw_product_gallery']);

            // Store the variations to be created in the database
            $update_result = update_option('_nwp_asw_import_cache_' . $wc_response['id'], maybe_serialize($variations), 'no');
            if (!$update_result) {
                NWP_Functions::log('Saving variations array as option failed.', $variations);
                static::err(__('Unable to cache data from ASW Server.', 'newwave'));
            }

            // Add categories
            if (isset($_POST['custom_cat']) && is_array($_POST['custom_cat']) && count($_POST['custom_cat'])) {
                $custom_cat_arr =  array_map('intval', $_POST['custom_cat']);
                wp_set_object_terms($wc_response['id'], $custom_cat_arr, 'product_cat');
            }

            // Add Tags
            if (isset($_POST['custom_tag']) && !empty($_POST['custom_tag'])) {
                $custom_tag = sanitize_text_field($_POST['custom_tag']);
                $cust_tag_arr = explode(",", $custom_tag);
                wp_set_object_terms($wc_response['id'], $cust_tag_arr, 'product_tag');
            }

            // Add clubs
            if (isset($_POST['custom_clubs']) && is_array($_POST['custom_clubs']) && count($_POST['custom_clubs']) ) {
                $custom_club_arr =  array_values($_POST['custom_clubs']);
                if (count($_POST['custom_color'])) {
                    $color_arr = array_values($_POST['custom_color']);
                    $data = array();
                    foreach ($custom_club_arr as $custom_club) {
                        $data[$custom_club] = array();
                        foreach ($color_arr as $color) {
                            $data[$custom_club][$color] = 'on';
                        }
                    }
                }
                update_post_meta($wc_response['id'], '_nw_color_access', $data);
            }

            // Select single club
            if (isset($_POST['custom_single_club']) && $_POST['custom_single_club'] != '') {
                update_post_meta($wc_response['id'], '_nw_shop', $_POST['custom_single_club']);
            }

            //Product Brand
            if(isset($_POST['product_brand'])){
                add_post_meta($wc_response['id'], '_brand_name', $_POST['product_brand']);
            }

            // Add featured image - START  */
            if (isset($_POST['custom_feature_img']) || !empty($_POST['custom_feature_img'])) {
                $thumbnail_id = intval(sanitize_text_field($_POST['custom_feature_img']));
                set_post_thumbnail($wc_response['id'], $thumbnail_id);
            }

            // Print instructions */
            if (isset($_POST['print_instructions']) && $_POST['print_instructions'] != '') {
                add_post_meta($wc_response['id'], 'print_instructions', sanitize_textarea_field($_POST['print_instructions']));
            }

            // Short description */
            if (isset($_POST['short_description']) && $_POST['short_description'] != '') {
                wp_update_post(array(
                    'ID'           => $wc_response['id'],
                    'post_excerpt' => sanitize_textarea_field($_POST['short_description'])
                ));
            }

            // Use slick slider for product gallery
            if (isset($_POST['show_slick_slider_gallery'])) {
                add_post_meta($wc_response['id'], '_show_slick_slider_gallery', $_POST['show_slick_slider_gallery']);
            }

            // Return number of variations to create to JS, with an edit link to created product
            $ajax_response = array(
                'edit_link' => sprintf("%spost.php?post=%d&action=edit", get_admin_url(), $wc_response['id']),
                'number_of_variations' => count($variations),
                'cache_id' => $wc_response['id'],
                'variations' => $variations
            );

            static::delete_predownloaded_images();

            wp_die(json_encode($ajax_response));
        }


        /**
         * AJAX function for re-importing product variations to an existing product
         */

        public static function ajax_update_product()
        {
            if (!current_user_can('edit_products'))
            {
                static::err(__('The current user does not have permission to edit products.', 'newwave'));
                wp_die();
            }

            check_ajax_referer('nwp-asw-import', 'security');

            $cdate_arr = [];

            // Collect all custom_date for the color variations selected for import
            if (isset($_POST['cdate_arr']) || !empty($_POST['cdate_arr'])) {
                $cdate_arr['var_custom_date'] = json_decode(stripslashes($_POST['cdate_arr']), true);
            }

            if (isset($_POST['custom_var_img']) || !empty($_POST['custom_var_img'])) {
                $cdate_arr['custom_var_img'] = $_POST['custom_var_img'];
            }

            if (isset($_POST['nwp_asw_product_status']) || !empty($_POST['nwp_asw_product_status'])) {
                $cdate_arr['nwp_asw_product_variant_status'] = json_decode(stripslashes($_POST['nwp_asw_product_status']), true);
            }

            // Missing SKUs of products to import
            if (!isset($_POST['skus'])) {
                NWP_Functions::log('Someone submitted an empty SKU list on ASW import.');
                static::err(__('No products selected for import', 'newwave'));
            }

            // Missing product id (post_id)
            if (!isset($_POST['product_id']) || !isset($_POST['product_id'])) {
                NWP_Functions::log('Someone tried to import variations to a product without a product_id');
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
            $asw_response = static::asw_get_product_info($product->get_sku());

            if (!$asw_response)
                static::err(__('The ASW server did not respond correctly.', 'newwave'));

            // Prepare REST array (replacing existing images with their post_id etc.)
            $product_new = static::prepare_product_request($asw_response, $variation_ids);
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

            // Add existing images to the REST Request
            $existing_imgs = $product->get_gallery_image_ids();
            $images = array();
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
                } else if (!in_array($img['id'], $existing_imgs)) {
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
            ));
            $rest_controller = new WC_REST_Products_Controller();
            $wc_response = $rest_controller->update_item($request);

            if (is_wp_error($wc_response) || !isset($wc_response->data)) {
                NWP_Functions::log('Error while update main product for adding of variations', array(
                    'payload' => $product,
                    'results' => $wc_response
                ));
                static::err(__('Error while updating main product.', 'newwave'));
            }

            $wc_response = $wc_response->data;

            // Prepare to create variations
            $variations = static::prepare_variations_request($asw_response, $variation_ids, $wc_response, $cdate_arr,$product_new['nw_product_gallery']);

            // Store the variations to be created in the database
            $update_result = update_option('_nwp_asw_import_cache_' . $wc_response['id'], maybe_serialize($variations), 'no');
            if (!$update_result) {
                NWP_Functions::log('Saving variations array as option failed.', $variations);
                static::err(__('Unable to cache data from ASW Server.', 'newwave'));
            }

            // Return number of variations to create to JS, with an edit link to created product
            $ajax_response = array(
                'edit_link' => sprintf("%spost.php?post=%d&action=edit", get_admin_url(), $wc_response['id']),
                'number_of_variations' => count($variations),
                'cache_id' => $wc_response['id']
            );

            wp_die(json_encode($ajax_response));
        }

        /**
         * Replaces attributes and images with their corresponding WordPress ID,
         * if it existings. Attributes are limited to 'size' and 'color',
         * and image that have been downloaded before its replaced with an ID as reference
         *
         * @param array $asw_response The 'get article info' REST response from the ASW server
         * @return array The modified product of the request ($asw_response['article'])
         */

        private static function prepare_product_request($asw_response, $variation_id)
        {
            $product = $asw_response['data'];

            $product['name'] = sanitize_text_field($product['productById']['productName']);
            $product['description'] = sanitize_textarea_field($product['productById']['productText']);

            $variation_arr = array();
            foreach ($variation_id as $variations => $value) {
                $var1 = explode('-', $value);
                $var2 = $var1[0] . '-' . $var1[1];
                if (!in_array($var2, $variation_arr)) {
                    $variation_arr[] = $var2;
                }
            }

            // Replace attributes with their correct term IDs
            $prepared_attrs = array();

            $color_id = wc_attribute_taxonomy_id_by_name('color');
            $size_id = wc_attribute_taxonomy_id_by_name('size');

            $attribute = array();
            $attribute[0]['id'] = $color_id;
            $attribute[0]['visible'] = $attribute[1]['visible'] = 1;
            $attribute[0]['variation'] = $attribute[1]['variation'] = 1;
            $attribute[1]['id'] = $size_id;
            $attribute[0]['options'] = $attribute[1]['options'] = array();
            foreach ($product['productById']['variations'] as $attribute1) {
                if (in_array($attribute1['itemNumber'], $variation_arr)) {
                    $attribute[0]['options'][] = $attribute1['itemColorName'];

                    foreach ($attribute1['skus'] as $sku_attribute) {
                        if (in_array($sku_attribute['sku'], $variation_id)) {
                            if (!in_array($sku_attribute['skuSize']['webtext'], $attribute[1]['options']))
                                $attribute[1]['options'][] = $sku_attribute['skuSize']['webtext'];
                        }
                    }
                }
            }

            // Finally set the attributes as part of the request
            $product['attributes'] = $attribute;

            /* Collect all images of each of the variations in the ASW server response, and add them to the the main product request so that the main product gets an image gallery Also replace any urls of images already existing in the media library with their attachment ID */
            $prepared_imgs = $unique_images = $existing_images = array();
            $image_search = new WP_Query(array(
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'posts_per_page' => -1,
                'post_mime_type' => 'image/jpeg,image/gif,image/jpg,image/png',
            ));

            // Create a lookup for existing images
            foreach ($image_search->posts as $image)
                $existing_images[$image->post_title] = $image->ID;

            $pos = 0;
            $nw_color_gallery = array();
            foreach ($asw_response['data']['productById']['variations'] as  $variation) {
                if (in_array($variation['itemNumber'], $variation_arr)) {

                    $nw_color_gallery_images = array();
                    foreach ($variation['pictures'] as $variation_picture) {
                        // Skip variation if no image is set
                        if (!isset($variation_picture['imageUrl']) || empty($variation_picture['imageUrl']))
                            continue;

                        $url = $variation_picture['imageUrl'];
                        $url = str_replace(basename($url), rawurlencode(basename($url)), $url);

                        // Image path
                        $img = NW_PLUGIN_DIR . 'includes/nw-product-synchronisation/temp_img/' . $variation['itemNumber'] . '_' . $variation_picture['resourceFileId'] . '_Preview.jpg';
                        $imgurl = NW_PLUGIN_URL . 'includes/nw-product-synchronisation/temp_img/' . $variation['itemNumber'] . '_' . $variation_picture['resourceFileId'] . '_Preview.jpg';

                        // Save image
                        $ch = curl_init($url);
                        $fp = fopen($img, 'wb');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_FILE, $fp);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_exec($ch);
                        curl_close($ch);
                        fclose($fp);

                        $name = $variation['itemNumber'] . '_' . $variation_picture['resourceFileId'] . '_Preview.jpg';

                        // No name could be extracted, skip variation
                        if (!$name)
                            continue;

                        // Skip adding image if it has already been listed for this product creation
                        if (in_array($name, $unique_images))
                            continue;
                        else
                            // mark as listed
                            array_push($unique_images, $name);

                        // If already uploaded, replace with attachment ID
                        if (array_key_exists($name, $existing_images)) {
                            $prepared_imgs[] = array(
                                'id' => $existing_images[$name],
                                'position' => $pos++,
                            );
                        }
                        // New image, so leave the URL as is, but give a filename
                        else {
                            $prepared_imgs[] = array(
                                'src' => $imgurl,
                                'name' => $name,
                                'position' => $pos++,
                            );
                        }

                        array_push($nw_color_gallery_images, $existing_images[$name]);
                    }
                    $nw_color_gallery[$variation['itemColorName']] =  $nw_color_gallery_images;
                }
                
            }

            // Finally set the images as part of the request
            $product['images'] = $prepared_imgs;
            $product['nw_product_gallery'] = $nw_color_gallery;
            return $product;
        }
        
        /**
         * Delete predownloaded images
         */

        private static function delete_predownloaded_images()
        {
            $images = glob(NW_PLUGIN_DIR . 'includes/nw-product-synchronisation/temp_img/*');

            foreach ($images as $image) {
                if (is_file($image)) {
                    unlink($image);
                }
            }
        }

        /**
         * Prepare variation request for creation by replacing already uploaded img urls with
         * their attachment ID, attributes names (color, size) with their attribute ID
         *
         * @param WP_REST_Response $response Response object for the main product
         * @return array Associative array the number of variations to be created,
         * and the edit link to the product
         */

        private static function prepare_variations_request($variations, $variation_id, $wc_response, $cdate_arr = array(), $nw_color_gallery = array())
        {
            $color_id = wc_attribute_taxonomy_id_by_name('color');
            $size_id = wc_attribute_taxonomy_id_by_name('size');
            $variations_data = array();
            $variation_arr = array();
            foreach ($variation_id as $variations2 => $value) {
                $var1 = explode('-', $value);
                $var2 = $var1[0] . '-' . $var1[1];
                if (!in_array($var2, $variation_arr)) {
                    $variation_arr[] = $var2;
                }
            }

            // Replace attributes with their correct term IDs
            $prepared_attrs = array();

            // Extract image IDs from the main product wc_response
            $img_ids = array();
            if (isset($wc_response['images'])) {
                foreach ($wc_response['images'] as $img)
                    $img_ids[$img['name']] = $img['id'];
            }

            $variations1 = $variation = array();

            if(get_post_meta($wc_response['id'], 'color_variants_gallery')){
                $count = intval(get_post_meta($wc_response['id'], 'color_variants_gallery',true));
            }else{
                $count = 0;
            }
            
            foreach ($variations['data']['productById']['variations'] as $attribute1) {
                if (in_array($attribute1['itemNumber'], $variation_arr)) {
                    $current_color = '';

                    $nw_color_id = term_exists( $attribute1['itemColorName'], 'pa_color');
                    if($nw_color_id['term_id'] && count($nw_color_gallery)){
                        $nw_color_gallery_map = array_values($nw_color_gallery[$attribute1['itemColorName']]);
                        $acf_product_color_key = static::get_acf_field_key('product_color');
                        $acf_color_gallery_key = static::get_acf_field_key('color_gallery');

                        update_post_meta($wc_response['id'],'color_variants_gallery_'.$count.'_product_color',$nw_color_id['term_id']);
                        update_post_meta($wc_response['id'],'_color_variants_gallery_'.$count.'_product_color',$acf_product_color_key);
                        update_post_meta($wc_response['id'],'_color_variants_gallery_'.$count.'_color_gallery',$acf_color_gallery_key);
                        update_post_meta($wc_response['id'],'color_variants_gallery_'.$count.'_color_gallery',$nw_color_gallery_map);
                        $count++;
                    }

                    foreach ($attribute1['skus'] as $sku_attribute) {

                        if (isset($attribute1['pictures'][0]['imageUrl'])) {

                            $name = $attribute1['itemNumber'] . '_' . $attribute1['pictures'][0]['resourceFileId'] . '_Preview.jpg';
                            unset($variation['image']);

                            if (isset($img_ids[$name]))
                                $variation['image'] = array('id' => $img_ids[$name]);
                        } else
                            unset($variation['image']);

                        if (in_array($sku_attribute['sku'], $variation_id)) {
                            $attribute = array();
                            $attribute[0]['id'] = $color_id;
                            $attribute[1]['id'] = $size_id;
                            $attribute[0]['option'] = $attribute1['itemColorName'];
                            $attribute[1]['option'] = $sku_attribute['skuSize']['webtext'];
                            $variation['product_id'] = $wc_response['id'];
                            $variation['regular_price'] = static::get_regular_price($variations['data']['productById']['prices']);
                            $variation['sku'] = $sku_attribute['sku'];
                            $variation['attributes'] = $attribute;
                            $variation['manage_stock'] = true;
                            $variation['stock_quantity'] = ($sku_attribute['availability'] + $sku_attribute['availabilityRegional']);

                            if (!empty($cdate_arr['var_custom_date'])) {
                                $variation['custom_date'] = $cdate_arr['var_custom_date']['cdate_' . $attribute[0]['option']];
                            }

                            $variation['custom_status'] = $cdate_arr['nwp_asw_product_variant_status']['nwp_asw_product_variant_status_' . $attribute[0]['option']];
                            $variation['current_color'] = $attribute[0]['option'];
                            $variation['cdate_arr']     = $cdate_arr['custom_var_img'];
                            

                            $current_color = $attribute[0]['option'];

                            if ($current_color != '' && $cdate_arr['custom_var_img']['customImg_' . $current_color]) //incase custom image is setup from popup
                            {
                                $variation['image'] = array('id' => $cdate_arr['custom_var_img']['customImg_' . $current_color]);
                            }

                            $variations1[] = $variation;
                        }
                    }
                }
            }

            $acf_color_variants_gallery_key = static::get_acf_field_key('color_variants_gallery');

            update_post_meta($wc_response['id'],'color_variants_gallery',($count));
            update_post_meta($wc_response['id'],'_color_variants_gallery',$acf_color_variants_gallery_key);
            
            return $variations1;
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

            check_ajax_referer('nwp-asw-import', 'security');

            // Get variation array
            $variations = maybe_unserialize(get_option('_nwp_asw_import_cache_' . $cache_id));
            if ($index >= count($variations)) {
                NWP_Functions::log('Index out of bounds on product variation creation through AJAX');
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
                $formatted_date = date("Y-m-d H:i:s", strtotime($variations[$index]['custom_date']));
                update_post_meta($response['id'], 'custom_date', $formatted_date);
            }

            if ($variations[$index]['custom_status'][0]) {
                $variation_status = $variations[$index]['custom_status'];
                wp_update_post(array('ID' => $response['id'], 'post_status' => $variation_status, 'post_type' => 'product_variation'));
            }

            // Created the last variation, delete the stored option
            if (($index + 1) == count($variations))
                delete_option('_nwp_asw_import_cache_' . $cache_id);

            wp_die(1);
        }

        /**
         * Extract and format filename from URL of a downloadable image
         * e.g. https://api.nwg.se/NWGApi/v1/pim/download/199205_1999_Active_Run_T_M.jpg
         * becomes 199205_1999_Active_Run_T_M.jpg
         *
         * @param string $filename Image URL
         * @return string|bool Extracted image name or false if no match
         */

        private static function extract_image_name($filename)
        {
            // Convert url-encoded spaces to underscores
            $filename = str_replace(array('%20', '-'), '_', $filename);

            // Extract name
            preg_match('/filename=((.*)\.(jpg|jpeg|png))&/', $filename, $matches);

            if (!$matches)
                return false;

            return sanitize_file_name($matches[2]);
        }

        /**
         * Calculate price including taxes if applicable
         *
         * @param float|int $price Without tax
         * @return float|int $price With tax
         */

        public static function calc_tax($price)
        {
            $nw_setting = get_option('_nwp_settings_asw_import_include_tax', true);
            if (wc_tax_enabled() && wc_prices_include_tax() && $nw_setting) {
                $tax = WC_Tax::get_shop_base_rate();
                $tax = reset($tax);
                $price = $price * (1 + ($tax['rate'] / 100));
            }
            return $price;
        }

        /**
         * Get product info (price, images etc.) from ASW server
         *
         * @param string $ids of products to import
         */
        public static function asw_get_product_info($ids)
        {
            try {

                $endpoint = sanitize_url(get_option('_nwp_asw_endpoint_url'));
                $authToken = sanitize_text_field(get_option('_nwp_asw_auth_token')); //this is provided by graphcms

                $qry = '{"query":"{productById(productNumber: \"' . $ids . '\", language: \"no\") { productName productBrand prices { retailPrice priceList } productText productFabrics variations {itemNumber itemColorName pictures {imageUrl resourceFileId} skus { sku skuSize { webtext } availability availabilityGlobal availabilityRegional} }} }"}';

                $headers = array();
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Authorization: Bearer ' . $authToken;

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $qry);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                return json_decode($result, true);
            } catch (Exception $e) {
                NWP_Functions::log('Some error occured while getting ASW article', array($ids, $e->getMessage()));
                return false;
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
         * Get a lookup table for existing images in WordPress attachments.
         *
         * @return array An associative array where the keys are image titles, and the values are attachment IDs.
         */

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
         * Get the regular price from an array of prices.
         *
         * @param array $prices An array containing different prices with associated details.
         * @return string The regular price if found, or an empty string if no matching regular price is found.
         */

        public static function get_regular_price($prices)
        {
            $regular_price = '';
            if (is_array($prices)) {
                foreach ($prices as $price) {
                    if ($price['priceList'] == 'INK') {
                        $regular_price = $price['retailPrice'];
                        break;
                    }
                }
            }

            return $regular_price;
        }

        /**
         * Extracts the filename from the given image URL and sanitizes it.
         *
         * @param string $image_url The URL of the image.
         * @return string|false The sanitized filename if successful, or false if the filename could not be extracted.
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
         * Generates an HTML list of categories with checkboxes recursively.
         *
         * @param int $tax_id The ID of the current category to display its child categories.
         * @param array $tax_array An array containing category information where keys are category IDs and values are arrays of category details.
         * @param array $get_only_termid An array of category IDs that should be checked by default.
         */

        public static function category_list_html($tax_id, $tax_array, $get_only_termid)
        {
            // Check if the current category has child categories.
            if (array_key_exists('child_id', $tax_array[$tax_id]) && is_array($tax_array[$tax_id]['child_id'])) {
            ?>
                <ul>
                    <?php
                    // Loop through each child category of the current category.
                    foreach ($tax_array[$tax_id]['child_id'] as $key => $val) {
                        // If the child category ID is in the list of categories to be checked, mark the checkbox as checked.
                        if (array_key_exists('child_id', $tax_array[$val]) && is_array($tax_array[$val]['child_id'])) {
                            if (in_array($val, $get_only_termid)) {
                    ?>
                                <li>
                                    <input type="checkbox" name="cust_cat[]" class="custom_cat_checkbox" value="<?= $val ?>" checked><?= $tax_array[$val]['name'] ?>
                                    <!-- Recursively call the function to display child categories of the current child category. -->
                                    <?php static::category_list_html($val, $tax_array, $get_only_termid); ?>
                                </li>
                            <?php
                            } else {
                            ?>
                                <li>
                                    <input type="checkbox" name="cust_cat[]" class="custom_cat_checkbox" value="<?= $val ?>"><?= $tax_array[$val]['name'] ?>
                                    <!-- Recursively call the function to display child categories of the current child category. -->
                                    <?php static::category_list_html($val, $tax_array, $get_only_termid); ?>
                                </li>
                            <?php
                            }
                        } else {
                            // If the child category ID is in the list of categories to be checked, mark the checkbox as checked.
                            if (in_array($val, $get_only_termid)) {
                            ?>
                                <li>
                                    <input type="checkbox" name="cust_cat[]" class="custom_cat_checkbox" value="<?= $val ?>" checked><?= $tax_array[$val]['name'] ?>
                                </li>
                            <?php
                            } else {
                            ?>
                                <li>
                                    <input type="checkbox" name="cust_cat[]" class="custom_cat_checkbox" value="<?= $val ?>"><?= $tax_array[$val]['name'] ?>
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

        /**
         * Function to get the acf field key
         */

        public static function get_acf_field_key($field_name){
            global $wpdb;
        
            $field_key = $wpdb->get_var("SELECT post_name FROM $wpdb->posts WHERE post_type='acf-field' AND post_excerpt='".$field_name ."';");
        
            return $field_name;
        }

    }

?>