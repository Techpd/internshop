<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Adds custom columns to the admin order overview, and functionality to edit
     * order items meta regarding uploading to ASW
     *
     */
    class NW_Order_Admin
    {

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Add buttons for marking items for ASW upload
            add_action('woocommerce_admin_order_item_bulk_actions', __CLASS__ . '::output_buttons');

            // Enqueue custom assets
            add_action('admin_head', __CLASS__ . '::enqueue_assets');

            // Register AJAX function to edit item meta
            add_action('wp_ajax_nw_update_order_items', __CLASS__ . '::ajax_update_order_items');

            // Display nice name for order meta
            add_action('woocommerce_order_item_display_meta_key', __CLASS__ . '::nice_name_item_meta_key');
            add_action('woocommerce_order_item_display_meta_value', __CLASS__ . '::nice_name_item_meta_value');

            // Add custom columns to admin order archive page - no need after the WooCommerce 3.3.1
            // add_filter('manage_edit-shop_order_columns', __CLASS__.'::add_columns', 99, 1);
            // add_filter('manage_shop_order_posts_custom_column', __CLASS__.'::add_column_data', 99, 2);

        }

        /**
         * Enqueue custom assets
         *
         */
        public static function enqueue_assets()
        {
            if (get_current_screen()->post_type == 'shop_order') {
                NWP_Functions::enqueue_script('admin_orders.js');
                NWP_Functions::enqueue_style('admin_order.css');
            }
        }
        /**
         * Modify columns displayed in admin table. Superfluous after ther WooCommerce 3.3.1 update.
         *
         * @param array $columns Ignored and replaced with local array
         */
        public static function add_columns($columns)
        {
            $columns = array_slice($columns, 0, 4, true)
                + array('nw_name' => __('Name', 'newwave'))
                + array_slice($columns, 0, null, true);
            return $columns;
        }

        /**
         * Add column data to the column nw_name. Superfluous after ther WooCommerce 3.3.1 update.
         *
         * @param string $column
         * @param int $post_id
         */
        public static function add_column_data($column, $post_id)
        {
            if ('nw_name' == $column) {
                $order = wc_get_order($post_id);
                $name = sprintf("%s %s", $order->get_billing_first_name(), $order->get_billing_last_name());
                if ($name)
                    echo $name;
                else
                    echo '–';
            }
        }

        /**
         * Change display of meta keys to a nice name
         *
         * @param string $key
         * @return string
         */
        public static function nice_name_item_meta_key($key)
        {
            if ('_nw_product_type' == $key)
                return __('Product type', 'newwave');

            if ('_nw_sku' == $key)
                return __('Product number', 'newwave');

            if ($key == '_nw_unprocessed')
                return __('To be uploaded to ASW', 'newwave');

            return $key;
        }

        /**
         * Change display of meta values to a nice name
         *
         * @param string $key
         * @return string
         */
        public static function nice_name_item_meta_value($value)
        {
            if ($value == 'nw_stock')
                return __('Stock', 'newwave');

            else if ($value == 'nw_stock_logo')
                return __('Stock with logo', 'newwave');

            else if ($value == 'nw_special')
                return __('Special', 'newwave');

            else if ($value == 'true')
                return __('Yes', 'newwave');

            else if ($value == 'false')
                return __('No', 'newwave');

            return $value;
        }


        /**
         * Update order and its item meta data whether to upload to ASW or not, via AJAX
         *
         */
        public static function ajax_update_order_items()
        {
            if (!current_user_can('manage_woocommerce'))
                return;

            if (!isset($_POST['action']) || $_POST['action'] != 'nw_update_order_items')
                return;

            check_ajax_referer('nw-update-order-items', 'security');

            $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : false;
            if (!isset($_POST['order_item_ids']) || !count($_POST['order_item_ids']) || !$order_id)
                return;

            // Are we enabling the items for upload or no?
            $value = 'true' == $_POST['value'] ? 'true' : 'false';

            foreach ($_POST['order_item_ids'] as $item_id) {
                $item = new WC_Order_Item_Product($item_id);
                $item->update_meta_data('_nw_unprocessed', $value);
                $item->save();
            }

            $order = wc_get_order($order_id);
            $all_items_processed = true;
            foreach ($order->get_items() as $item) {
                if ('true' == $item->get_meta('_nw_unprocessed')) {
                    $all_items_processed = false;
                    break;
                }
            }

            // Add term to WC_Order so that NW_ASW_Exporter can find it
            if (!$all_items_processed) {
                wp_set_object_terms($order_id, 'true', '_nw_unprocessed', false);
            } else {
                wp_delete_object_term_relationships($order_id, '_nw_unprocessed');
            }
        }

        /**
         * Output buttons for enabling or disabling items for ASW upload
         *
         * @param WC_Order $order
         */
        public static function output_buttons($order)
        {
?>
            <input type="button" class="button nw-change-asw-status nw-set-to-true" data-nonce="<?php echo wp_create_nonce('nw-update-order-items'); ?>" data-value="false" value="<?php _e('Cancel ASW upload', 'newwave'); ?>" />
            <input type="button" class="button nw-change-asw-status nw-set-to-false" data-nonce="<?php echo wp_create_nonce('nw-update-order-items'); ?>" data-value="true" value="<?php _e('Enable ASW upload', 'newwave'); ?>" />
<?php
        }
    }
?>