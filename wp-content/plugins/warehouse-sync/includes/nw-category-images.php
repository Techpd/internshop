<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

if (!function_exists('nw_category_image')) :

    /**
     * Get the category image based on term ID and index.
     *
     * @param int $term_id The term ID.
     * @param int $idx The index of the image to retrieve.
     * @return array The category image data.
     */

    function nw_category_image($term_id, $idx)
    {
        $images = maybe_unserialize(get_term_meta($term_id, 'nw_category_images', true));
        if (!$images)
            $images = array();

        $i = 0;
        foreach ($images as $key => $image) {
            $images[$key]['type'] = $i++ % 2 ? 'wide' : 'normal';
        }

        $images = array_values($images);
        return isset($images[$idx]) ? $images[$idx] : array();
    }

endif;


    /**
     * Register category images as a custom post type.
     */

    class NW_Category_Images
    {

        const CAT_IMGS = 8;

        /**
         * Initialize the class and add hooks and filters.
         */

        public static function init()
        {
            //check if this feature is enabled in plugin settings
            if (!get_option('_nw_feature_cat_imgs')) {
                return;
            }

            add_action('product_cat_edit_form_fields', __CLASS__ . '::add_image_fields', 10, 1);
            add_action('edit_term', __CLASS__ . '::save_image_fields', 10, 3);
            add_action('admin_head', __CLASS__ . '::enqueue_assets');
        }

        /**
         * Enqueue necessary assets for the admin screen.
         */

        public static function enqueue_assets()
        {
            if ('edit-product_cat' == get_current_screen()->id) {
                wp_enqueue_script(
                    'nw_category_images_upload',
                    NW_Plugin::$plugin_url . 'assets/js/nw-category-images.js',
                    array('jquery')
                );

                wp_enqueue_style(
                    'nw_category_image',
                    NW_Plugin::$plugin_url . 'assets/css/nw-category-images.css'
                );
            }
        }

        /**
         * Add image fields to the category edit form.
         *
         * @param object $term The term object being edited.
         */

        public static function add_image_fields($term)
        {
            // Only allow setting images for categories on level 0 and 1
            if ($term->parent && get_term($term->parent)->parent) {
                error_log(print_r($term, true));
                error_log(print_r(get_term($term->parent), true));
                return;
            }

            // Retrieve saved images for the category
            $saved = maybe_unserialize(get_term_meta($term->term_id, 'nw_category_images', true));
            if (!$saved)
                $saved = array();

            // Output image fields for each image slot
            for ($i = 1; $i <= static::CAT_IMGS; $i++) {
                static::output_image_field(
                    $i,
                    isset($saved[$i]['image']) ? $saved[$i]['image'] : 0,
                    isset($saved[$i]['product']) ? $saved[$i]['product'] : 0,
                    $i % 2 ? sprintf(__('%s: Narrow category image', 'newwave'), $i) : sprintf(__('%s: Wide category image', 'newwave'), $i),
                    $i % 2 ? __('Recommended image size 290x450', 'newwave') : __('Recommended image size 600x450', 'newwave')
                );
            }
        }

        /**
         * Output an image field on the category edit form.
         *
         * @param int $i The index of the image field.
         * @param int $img_id The ID of the selected image.
         * @param int $product_id The ID of the linked product.
         * @param string $label The label for the image field.
         * @param string $description The description for the image field.
         */

        public static function output_image_field($i, $img_id, $product_id, $label, $description)
        {
            $add_tip = __('Select image', 'newwave');
            $rm_tip = __('Remove image', 'newwave');
            $tip = $img_id ? $rm_tip : $add_tip;

            $placeholder = esc_url(wc_placeholder_img_src());
            $thumbnail = $img_id ? wp_get_attachment_image_url($img_id, 'woocommerce_gallery_thumbnail') : $placeholder;

?>
            <tr class="form-field nw-category-image">
                <th scope="row" valign="top"><label><?php echo $label; ?></label></th>
                <td>

                    <a href="#" class="nw-category-image-upload tips" data-tip="<?php echo $tip; ?>" data-add-tip="<?php echo $add_tip; ?>" data-rm-tip="<?php echo $rm_tip; ?>">
                        <img class="nw-category-image-thumbnail" data-placeholder="<?php echo $placeholder; ?>" src="<?php echo $thumbnail; ?>" width="60px" height="60px" />
                        <input type="hidden" id="category_image_<?php echo $i; ?>" name="nw_category_image_<?php echo $i; ?>" value="<?php echo $img_id; ?>" />
                    </a>

                    <select class="wc-product-search" style="width: 50%;" id="nw_category_image_product_<?php echo $i; ?>" name="nw_category_image_product_<?php echo $i; ?>" data-placeholder="<?php esc_attr_e('Search for a product&hellip;', 'woocommerce'); ?>" data-action="woocommerce_json_search_products_and_variations">
                        <?php if ($product_id) : ?>
                            <option value="<?php echo $product_id; ?>"><?php echo get_the_title($product_id); ?></option>
                        <?php endif; ?>
                    </select>

                    <p class="description"><?php echo $description; ?></p>
                </td>
            </tr>
<?php
        }

        /**
         * Save the image fields when the category is updated.
         *
         * @param int $term_id The category term ID.
         * @param int $tt_id The term taxonomy ID.
         * @param string $taxonomy The taxonomy of the term.
         */

        public static function save_image_fields($term_id, $tt_id = '', $taxonomy = '')
        {
            $save = array();
            for ($i = 1; $i <= static::CAT_IMGS; $i++) {
                $category_img = array();

                if (isset($_POST['nw_category_image_' . $i]) && $img_id = absint($_POST['nw_category_image_' . $i])) {
                    $category_img['image'] = $img_id;
                }
                if (isset($_POST['nw_category_image_product_' . $i]) && $p_id = absint($_POST['nw_category_image_product_' . $i])) {
                    $category_img['product'] = $p_id;
                }
                if ($category_img) {
                    $save[$i] = $category_img;
                }
            }
            if ($save) {
                update_term_meta($term_id, 'nw_category_images', maybe_serialize($save));
            }
        }
    }

    // Initialize the class when the file is included
    NW_Category_Images::init();

?>