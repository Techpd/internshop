<?php

    class NWP_ASW_Update
    {

        /**
         * @var WC_Product_Variable Current product object to process
         */
        static $product;

        /**
         * @var WC_Product_Variation[] Current product variation objects to process
         */
        static $variations;
        static $variations_id;

        /**
         * Add hooks and filters
         */

        public static function init()
        {
            if (class_exists('NWP_ASW_Importer')) {
                add_action('media_buttons', __CLASS__ . '::add_update_button');
                add_action('admin_head', __CLASS__ . '::enqueue_assets');
                add_action('wp_ajax_nwp_asw_pre_update', __CLASS__ . '::ajax_pre_update');
                add_action('wp_ajax_nwp_asw_update', __CLASS__ . '::ajax_update');
            }
        }

        /**
         * Enqueue assets to assist in creating the modal and styling the select table
         */

        public static function enqueue_assets()
        {
            $screen = get_current_screen();
            if ($screen->post_type != 'product' || $screen->id != 'product')
                return;

            global $post;
            if ($post->post_status == 'auto-draft')
                return;

            // Enqueue updater script with its dependencies
            wp_enqueue_script(
                'nwp_asw_updater',
                NW_PLUGIN_URL . 'includes/nw-product-synchronisation/assets/js/graphql-asw-updater.js',
                array(
                    'wc-backbone-modal',
                    'jquery-blockui',
                    'jquery-ui-progressbar',
                ),
                rand(10, 100)
            );

            // Enqueue updater style with its dependencies
            wp_enqueue_style(
                'nwp_asw_updater',
                NW_PLUGIN_URL . 'includes/nw-product-synchronisation/assets/css/graphql-asw-updater.css',
                array('woocommerce_admin_styles'),
                rand(10, 100)
            );

            add_action('admin_footer', __CLASS__ . '::render_modal');
        }

        /**
         * Add update button to product edit screen
         *
         * @param string $editor_id What part of the product admin is being output
         */

        public static function add_update_button($editor_id)
        {
            global $post;
            if ($editor_id == 'content' && $post->post_status != 'auto-draft') {
                $screen = get_current_screen();
                if ($screen->post_type == 'product' && $screen->id == 'product') { //TODO check product type

                    printf(
                        '<button id="nwp-open-asw-updater-dialog" type="button" class="button" data-nonce="%s">%s %s</button>',
                        wp_create_nonce('nwp-asw-pre-update'),
                        '<span class="dashicons dashicons-update"></span>',
                        __('ASW Update', 'newwave')
                    );
                }
            }
        }

        /**
         * Output the dialog HTML for selecting what attribute to update from ASW
         */

        public static function render_modal()
        {
            global $post;
?>
            <script type="text/template" id="tmpl-nwp-modal-asw-updater">
                <div class="wc-backbone-modal">
                    <div id="nwp-asw-updater" class="nwp-modal wc-backbone-modal-content">
                        <section class="wc-backbone-modal-main" role="main">

                            <header class="wc-backbone-modal-header">
                                <h1><?php _e('Update from ASW', 'newwave'); ?></h1>
                                <button class="modal-close modal-close-link dashicons dashicons-no-alt"></button>
                            </header>

                            <article></article>

                            <footer>
                                <div class="inner">
                                    <button id="nwp-do-asw-update" class="button button-primary button-large" data-nonce="<?php echo wp_create_nonce('nwp-asw-update'); ?>" disabled><?php _e('Update', 'newwave'); ?></button>
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
         * Render table to select what properties to update from the ASW server
         *
         * @param array $rows
         */

        private static function render_table($rows)
        {
        ?>
            <table>
                <tbody>

                    <?php foreach ($rows as $name => $settings) {
                        $settings['name'] = $name;
                        $settings['is_parent'] = isset($settings['options']);
                        $settings['id'] = $name;
                        $settings['class'] = 'nw-update-property-row';

                        // If all child rows are disabled, disable parent too
                        if ($settings['is_parent']) {
                            $settings['disabled'] = true;
                            foreach ($settings['options'] as $option) {
                                if (!$option['disabled']) {
                                    $settings['disabled'] = false;
                                }
                            }
                        }

                        static::render_table_row($settings);

                        if ($settings['is_parent']) {
                            foreach ($settings['options'] as $option_value => $option_setting) {
                                $option_setting['name'] = $name . '[]';
                                $option_setting['class'] = 'nw-update-image-row';
                                $option_setting['id'] = $name . '_' . $option_value;
                                $option_setting['value'] = $option_value;

                                static::render_table_row($option_setting);
                            }
                        }
                    }    ?>
                </tbody>
            </table>
        <?php
        }

        /**
         * Render a table row with a label and a checkbox input
         *
         * @param array $args
         */

        private static function render_table_row($args)
        {
            $args = wp_parse_args($args, array(
                'name' => '',
                'value' => '',
                'id' => '',
                'label' => '',
                'checked' => false,
                'disabled' => false,
                'is_parent' => false,
                'class' => '',
            ));

            extract($args);
            if ($label == 'Produktbilder') {
                $disabled = false;
            }
        ?>
            <tr class="<?php if ($disabled) {echo 'nwp-disabled-row ';}echo $class; ?>">
                <td>
                    <input id="<?php echo $id; ?>" type="checkbox" name="<?php if (!$is_parent && $name) { echo $name;
                    } ?>" value="<?php if (!$is_parent && $value) {echo $value;} ?>" <?php checked($checked); echo ' '; disabled($disabled);?>>
                </td>
                <td>
                    <label for="<?php echo $id; ?>">
                        <?php echo $label; ?>
                    </label>
                </td>
                <td class="
                <?php if ($is_parent) {
                    echo 'toggle-indicator';
                } ?>">
                </td>
            </tr>
<?php
        }

        /**
         * Helper function to check permissions, validate nonce
         * and getting data the product from the ASW server
         */

        private static function initialize($nonce_name)
        {
            if (!current_user_can('edit_products'))
            {
                NWP_ASW_Importer::err(__('The current user does not have permission to edit products.', 'newwave'));
                wp_die();
            }

            check_ajax_referer($nonce_name, 'security');

            if (!isset($_POST['product_id']) || !$product_id = absint($_POST['product_id'])) {
                NWP_ASW_Importer::err(__('Invalid product', 'newwave'));
            }

            $product = wc_get_product($product_id);

            if (!is_a($product, 'WC_Product_Variable')) {
                NWP_ASW_Importer::err(__('Invalid product type', 'newwave'));
            }

            $variations = array();

            // Extract the SKUs, which will be sent to the ASW server
            $skus = array();
            foreach ($product->get_children() as $variation_id) {

                $variation = wc_get_product($variation_id);
                if ($sku = $variation->get_sku()) {

                    $variations[] = $variation;
                    $skus[] = $sku;
                }
            }

            static::$product = $product;
            static::$variations = $variations;
            static::$variations_id = $skus;

            // Fetch and return the data

            return NWP_ASW_Importer::asw_get_product_info($product->get_sku());
        }

        /**
         * Before an update can take place, the user must select what properties to
         * sync with the ASW server; render the table to select options
         */

        public static function ajax_pre_update()
        {
            $asw_data = static::initialize('nwp-asw-pre-update');

            $product = static::$product;
            $variations = static::$variations;

            $update_name = false;
            if (
                $product->get_name() != $asw_data['data']['productById']['productName']
                && strlen($asw_data['data']['productById']['productName']) > 0
            ) {
                $update_name = true;
            }

            // Check for description mismatch,
            // get the ProductCommerceText instead of 'description'
            $update_description = false;

            if (
                $product->get_description() != $asw_data['data']['productById']['productText']
                && strlen($asw_data['data']['productById']['productText']) > 0
            ) {
                $update_description = true;
            }


            // Check for short-description mismatch
            $update_short_description = false;
            if (
                $product->get_short_description() != $asw_data['data']['productById']['productText']
                && strlen($asw_data['data']['productById']['productText']) > 0
            ) {
                $update_short_description = true;
            }

            // Check for Brand Name mismatch
            $update_brand_name = false;
            if ($product->get_brand_name() != $asw_data['data']['productById']['productBrand']
                && strlen($asw_data['data']['productById']['productBrand']) > 0) {
                    $update_brand_name = true;
            }

            // Check for price mismatch
            $update_price = false;
            if ($asw_data['data']['productById']['retailPrice']['price']) {
                foreach ($variations as $variation) {
                    $price = NWP_ASW_Importer::calc_tax($asw_data['data']['productById']['retailPrice']['price']);
                    if ($variation->get_regular_price() != $price) {
                        $update_price = true;
                        break;
                    }
                }
            }

            // TODO implement support for syncing material

            // Find what colors the server has images for
            $asw_images = array();
            $variation_arr = array();
            $variations_id = static::$variations_id;
            foreach ($variations_id as $variations2 => $value) {
                $var1 = explode('-', $value);
                $var2 = $var1[0] . '-' . $var1[1];
                if (!in_array($var2, $variation_arr)) {
                    $variation_arr[] = $var2;
                }
            }

            foreach ($asw_data['data']['productById']['variations'] as $variation) {
                if (in_array($variation['itemNumber'], $variation_arr)) {
                    foreach ($variation['skus'] as $sku_attribute) {
                        if (in_array($sku_attribute['sku'], $variations_id)) {
                            // Convert to slug for comparison with attribute later
                            $color = sanitize_title($variation['itemColorName']);
                            if (!in_array($color, $asw_images) && $variation['pictures'][0]['imageUrl']) {
                                array_push($asw_images, $color);
                            }
                            break;
                        }
                    }
                }
            }

            // Check which colors in the already added product that are missing images
            $missing_colors = array();
            foreach ($variations as $variation) {
                $color = $variation->get_variation_attributes()['attribute_pa_color'];
                if (!array_key_exists($color, $missing_colors)) {
                    $missing_colors[$color] = false;
                }

                if (get_the_post_thumbnail($variation->get_id())) {
                    $missing_colors[$color] = true;
                }
            }

            // Build array of colors to display in table
            $missing_images = array();
            foreach ($missing_colors as $color => $has_image) {
                $missing_images[$color] = array(
                    'label' => get_term_by('slug', $color, 'pa_color')->name,
                    'checked' => $has_image,
                    'disabled' => $has_image || !in_array($color, $asw_images),
                );
            }

            $asw_updater_fields = array(
                'nw_update_name' => array(
                    'label' => __('Name', 'newwave'),
                    'checked' => !$update_name,
                    'disabled' => !$update_name,
                ),
                'nw_update_description' => array(
                    'label' => __('Description', 'newwave'),
                    'checked' => !$update_description,
                    'disabled' => !$update_description,
                ),
                'nw_update_short_description' => array(
                    'label' => __('Short description', 'newwave'),
                    'checked' => !$update_short_description,
                    'disabled' => !$update_short_description,
                ),
                'nw_update_price' => array(
                    'label' => __('Price', 'newwave'),
                    'checked' => !$update_price,
                    'disabled' => !$update_price,
                ),
                'nw_update_images' => array(
                    'label' => __('Product images', 'newwave'),
                    'checked' => !boolval($missing_images),
                    'disabled' => !boolval($missing_images),
                    'options' => $missing_images,
                )    
            );

            if(get_option('_nw_product_brand_name')){
                $asw_updater_fields['nw_update_brand'] = array(
                    'label' => __('Product Brand', 'newwavempi'),
                    'checked' => !boolval($update_brand_name),
                    'disabled' => !boolval($update_brand_name)
                );
            }

            // Render table
            static::render_table($asw_updater_fields);

            wp_die();
        }

        /**
         * Perform update on selected attributes
         */

        public static function ajax_update()
        {
            $asw_data = static::initialize('nwp-asw-update');

            $product = static::$product;
            $variations = static::$variations;

            if (!isset($_POST['options']) || !strlen($_POST['options'])) {
                NWP_ASW_Importer::err(__('No option selected.', 'newwave'));
            }

            // Parse the serialized array from ajax
            parse_str($_POST['options'], $options);

            // Update name if specified
            if (isset($options['nw_update_name'])) {
                $product->set_name(sanitize_text_field($asw_data['data']['productById']['productName']));
            }

            // Update description if specified, where the attribute ProductCommerceText
            // has precedence over any description field
            if (isset($options['nw_update_description'])) {
                $product->set_description(sanitize_text_field($asw_data['data']['productById']['productText']));
            }

            // Update short description if specified
            if (isset($options['nw_update_short_description'])) {
                $product->set_short_description(sanitize_text_field($asw_data['data']['productById']['productText']));
            }

            // Update price if specified
            if (isset($options['nw_update_price'])) {
                $price = NWP_ASW_Importer::calc_tax($asw_data['data']['productById']['retailPrice']['price']);
                $product->set_price($price);
                $product->set_regular_price($price);
                foreach ($variations as $variation) {
                    $variation->set_price($price);
                    $variation->set_regular_price($price);
                }
            }

            // Update images if any color is selected
            if (isset($options['nw_update_images'])) {

                // Extract URL for images to be downloaded from the ASW server
                $image_urls = array();
                $variation_arr = array();
                $variations_id = static::$variations_id;
                foreach ($variations_id as $variations2 => $value) {
                    $var1 = explode('-', $value);
                    $var2 = $var1[0] . '-' . $var1[1];
                    if (!in_array($var2, $variation_arr)) {
                        $variation_arr[] = $var2;
                    }
                }
                foreach ($asw_data['data']['productById']['variations'] as $variation) {
                    if (in_array($variation['itemNumber'], $variation_arr)) {
                        foreach ($variation['skus'] as $sku_attribute) {
                            if (in_array($sku_attribute['sku'], $variations_id)) {

                                // Convert to slug for comparison with attribute later
                                $color = sanitize_title($variation['itemColorName']);
                                if (
                                    !in_array($color, $image_urls) && $variation['pictures'][0]['imageUrl']
                                    && in_array($color, $options['nw_update_images'])
                                ) {
                                    $url = $variation['pictures'][0]['imageUrl'];

                                    // Image path
                                    $img = NW_PLUGIN_DIR . 'includes/nw-product-synchronisation/temp_img/' . $variation['pictures'][0]['resourceFileId'] . '_Preview.jpg';
                                    $imgurl = NW_PLUGIN_URL . 'includes/nw-product-synchronisation/temp_img/' . $variation['pictures'][0]['resourceFileId'] . '_Preview.jpg';

                                    // Save image
                                    $ch = curl_init($url);
                                    $fp = fopen($img, 'wb');
                                    curl_setopt($ch, CURLOPT_FILE, $fp);
                                    curl_setopt($ch, CURLOPT_HEADER, 0);
                                    curl_exec($ch);
                                    curl_close($ch);
                                    fclose($fp);
                                    $image_urls[$color]['url'] = $imgurl;
                                    $image_urls[$color]['name'] = $variation['pictures'][0]['resourceFileId'] . "_Preview.jpg";
                                }
                                break;
                            }
                        }
                    }
                }

                if ($image_urls) {
                    $existing_image_lookup = NWP_ASW_Importer::get_image_lookup();
                    $image_ids = array();

                    foreach ($image_urls as $color => $url) {
                        $filename = $url['name'];
                        // If image with same filename exists already, use that image ID
                        if (array_key_exists($filename, $existing_image_lookup))
                            $image_id = $existing_image_lookup[$filename];
                        else
                            $image_id = static::upload_image($url['url']);

                        // If image ID is valid, store reference
                        if ($image_id)
                            $image_ids[$color] = $image_id;
                    }

                    // If any images where found/uploaded
                    if ($image_ids) {
                        foreach ($variations as $variation) {
                            $color = $variation->get_variation_attributes()['attribute_pa_color'];

                            // Only set images
                            if (array_key_exists($color, $image_ids)) {
                                $variation->set_image_id($image_ids[$color]);
                            }
                        }

                        // Add to gallery of main product as well
                        $gallery = array_unique(array_merge($product->get_gallery_image_ids(), $image_ids));
                        $product->set_gallery_image_ids($gallery);
                        set_post_thumbnail($_POST['product_id'], array_values($image_ids)[0]);
                    }
                }
            }

            // Update product brand if specified
            if (isset($options['nw_update_brand'])) {
                $product->set_brand_name($asw_data['data']['productById']['productBrand']);
            }

            /* Save variations and main product, no need to check if any changes has actually been made, the WC_Product's data store handles that on it's own*/
            foreach ($variations as $variation) {
                $variation->save();
            }

            $product->save();
            wp_die();
        }

        public static function get_image_name($filename)
        {
            // Convert url-encoded spaces to underscores
            $filename = str_replace(array('%20', '-'), '_', $filename);

            $pattern = '/[\w\-]+\.(jpg|png|jpeg)/';
            $subject = $filename;
            preg_match($pattern, $subject, $matches);

            if ($matches[0] == 'noImage.jpg' || !$matches) {
                return false;
            }
            return sanitize_file_name($matches[1]);
        }

        /**
         * Upload image to WordPress from a given URL
         *
         * @param string $url
         * @param string $extension A valid extension like png, jpg etc.
         */

        public static function upload_image($url, $extension = 'jpg')
        {
            if (strpos($url, '.png') !== false) {
                $extension = 'png';
            }

            $filename = static::get_image_name($url);
            $upload_result = wp_upload_bits($filename, null, file_get_contents($url));

            if ($upload_result['error']) {
                return false;
            }

            $wp_filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            $image_id = wp_insert_attachment($attachment, $upload_result['file']);

            if (is_wp_error($image_id)) {
                return false;
            }

            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($image_id, $upload_result['file']);
            wp_update_attachment_metadata($image_id,  $attachment_data);

            return $image_id;
        }
    }
?>