<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Adds ability for customers to select ordered items for return,
     * creates unique return codes, displays return code confirmation page
     * and sends a notice email to customer support about the action
     *
     */
    class NW_Returns
    {
        /**
         * Cached order object
         *
         * @var WC_Order
         */
        static $order;

        /**
         * Cached returned items
         *
         * @var WC_Order_item
         */
        static $items;

        /**
         * Cached return code
         *
         * @var string
         */
        static $return_code;

        /**
         * Hooks and filters
         *
         */
        public static function init()
        {
            // Add custom customer 'my account' endpoint
            add_action('init', __CLASS__ . '::add_endpoint');

            // Handle submit of the return form
            add_action('admin_post_nw_return_products', __CLASS__ . '::handle_submit');

            // Disable 'order again'-button, regardless of order status
            add_action('woocommerce_order_item_permalink', '__return_false');
            add_action('woocommerce_valid_order_statuses_for_order_again', '__return_empty_array', 99);

            // Add form to the HTML when viewing the order
            add_action('woocommerce_view_order', __CLASS__ . '::before_order_html', 1);

            // Validate that the return code in the URL existing
            add_action('template_redirect', __CLASS__ . '::validate_return_registered');

            // Display nice name for the order item meta return code
            add_action('woocommerce_order_item_display_meta_key', __CLASS__ . '::nice_name_return_code_key');
        }

        /**
         * Change display of the hidden meta key to a nice name
         *
         * @param string $key
         * @return string
         */
        public static function nice_name_return_code_key($key)
        {
            if ('_nw_return_code' == $key)
                return __('Return code', 'newwave');
            return $key;
        }

        /**
         * Add a woocommerce endpoint for displaying result of a customer
         * requesting product to returned
         *
         */
        public static function add_endpoint()
        {
            add_rewrite_endpoint('return-registered', EP_PAGES);
        }

        /**
         * Validate that the URL-requested 'returned-registered' return code is valid.
         * Otherwise, redirect to front page
         *
         */
        public static function validate_return_registered()
        {
            global $wp_query;
            if (!$wp_query || !isset($wp_query->query['return-registered'])) // not this endpoint
                return;

            $return_code = strtoupper(strval($wp_query->query['return-registered']));
            $order_id = explode('-', $return_code);
            if (!isset($order_id[0])) {
                wp_redirect(get_home_url());
                return;
            }

            $order = wc_get_order(absint($order_id[0]));
            if (!$order || $order->get_customer_id() != get_current_user_id()) {
                wp_redirect(get_home_url());
                return;
            }

            $items = array();
            foreach ($order->get_items() as $item) {
                if ($item->get_meta('_nw_return_code') == $return_code) {
                    $items[] = $item;
                }
            }

            if (empty($items)) {
                wp_redirect(get_home_url());
                return;
            }

            // Cache the variables
            static::$order = $order;
            static::$items = $items;
            static::$return_code = $return_code;

            // Hook in function to add template
            add_action('woocommerce_account_return-registered_endpoint', __CLASS__ . '::return_registered_content');
        }

        /**
         * Add content to the 'Return Registered' page
         *
         */
        public static function return_registered_content()
        {
            wc_get_template('myaccount/return-registered.php', array(
                'order' => static::$order,
                'items' => static::$items,
                'return_code' => static::$return_code,
            ), '', NW_PLUGIN_DIR . 'templates/');
        }

        /**
         * Output return form before for order view if applicable
         *
         * @param int $order_id
         */
        public static function before_order_html($order_id)
        {
            $order = wc_get_order($order_id);
            if (!($order->has_status('completed') || $order->has_status('partially-shipped') || $order->has_status('sent-to-printing')))
                return;

            $forward_url = wp_parse_url(wc_get_endpoint_url('return-registered'))['path'];
?>
            <p><?php _e('To return products, select the ones you wish to return before submitting using the button below.', 'newwave'); ?></p>

            <form action="<?php echo admin_url('admin-post.php') ?>" method="post">
                <input type="hidden" name="action" value="nw_return_products">
                <input type="hidden" name="nw_forward_url" value="<?php echo esc_attr($forward_url); ?>" />
                <input type="hidden" name="nw_order_id" value="<?php echo esc_attr($order->get_id()); ?>" />
                <?php wp_nonce_field('nw_return_products', '_nw_return_nonce'); ?>
            <?php

            static::$order = $order;

            add_filter('woocommerce_order_item_name', __CLASS__ . '::order_item_name_html', 99, 2);
            add_filter('woocommerce_order_item_quantity_html', __CLASS__ . '::order_item_html', 99, 2);
            add_action('woocommerce_order_details_after_order_table', __CLASS__ . '::after_order_html', 99);
        }

        /**
         * Add a checkbox in front of the item in the order table
         *
         * @param string $html
         * @param WC_Order_Item $item
         * @return string
         */
        public static function order_item_name_html($html, $item)
        {
            if (!static::item_can_be_returned($item)) {
                $html = sprintf('<input type="checkbox" class="newwave-return-products-cb" disabled/> %s', esc_html($item->get_name()));
            } else {
                $html = sprintf('<input id="order-item-%1$s" class="newwave-return-products-cb" name="nw_products[]" value="%1$s" type="checkbox"/> <label for="order-item-%1$s">%2$s</label>', esc_attr($item->get_id()), esc_html($item->get_name()));
            }

            return $html;
        }

        /**
         * Add product info after product name in order table
         *
         * @param string $html
         * @param WC_Order_Item $item
         * @return string
         */
        public static function order_item_html($html, $item)
        {

            $qty = static::item_can_be_returned($item);

            if ($item->get_meta('_nw_custom_print' == 'true')) {
                $html .= sprintf('<strong class="newwave-order-item-custom-print"> - %s</strong>', sprintf(__('Custom print', 'newwave')));
            } else if ($code = $item->get_meta('_nw_return_code')) {
                $html .= sprintf('<strong class="newwave-order-item-return-code"> - %s</strong>', sprintf(__('Return code %s', 'newwave'), $code));
            } else if (static::item_can_be_returned($item) > 1) {
                $html .= sprintf(' <input type="number" name="nw_products_count[%1$s]" value="%2$s" min="1" max="%2$s" step="1" class="%3$s"/>', $item->get_id(), $qty, apply_filters('newwave_product_qty_input_class', 'newwave-qty-input'));
            } else if ($item->get_meta('_nw_product_type') == 'nw_stock_logo' || $item->get_meta('_nw_product_type') == 'nw_special') {
                $html .= sprintf(' <strong class="newwave-order-item-custom-print"> - %s</strong>', __('Custom print', 'newwave'));
            }

            if ($refunds = absint(static::$order->get_qty_refunded_for_item($item->get_id()))) {
                $html .= sprintf('<strong class="newwave-order-item-refunded"> - %s &times; %s</strong>', __('Refunded', 'newwave'), $refunds);
            }

            return $html;
        }

        /**
         * Checks whether an order item product is valid to be returned
         *
         * @param WC_Order_Item_Product $item
         * @return int Quantity of $item that can be returned
         */
        private static function item_can_be_returned($item)
        {
            if (
                $item->get_meta('_nw_product_type') == 'nw_stock_logo' ||
                $item->get_meta('_nw_product_type') == 'nw_special' ||
                $item->get_meta('_nw_unprocessed') == 'true' ||
                $item->meta_exists('_nw_return_code')
            )
                return false;

            return max($item->get_quantity() - absint(static::$order->get_qty_refunded_for_item($item->get_id())), 0);
        }

        /**
         * Output submit button to return products and close the return form
         *
         */
        public static function after_order_html()
        {
            ?>
                <input type="submit" id="newwave-return-products" class="woocommerce-button button <?php echo esc_attr(apply_filters('newwave_return_products_button_class', '')); ?>" value="<?php _e('Return products', 'newwave'); ?>" />
            </form>
<?php
        }

        /**
         * Handle the return of products.
         * Generates a unique return code as meta for items.
         * Splits items if part of the quantity is select for returning.
         *
         */
        public static function handle_submit()
        {
            $url = isset($_POST['_wp_http_referer']) ? home_url(esc_url_raw($_POST['_wp_http_referer'])) : home_url();

            if (!isset($_POST['_nw_return_nonce']) || !wp_verify_nonce($_POST['_nw_return_nonce'], 'nw_return_products')) {
                wp_redirect($url);
                return;
            }

            // If invalid order or it doesn't belong to the user submitting it
            $order = wc_get_order(absint($_POST['nw_order_id']));
            if ($order->get_customer_id() != get_current_user_id() || empty($_POST['nw_products'])) {
                wp_redirect($url);
                return;
            }

            // Count earlier return cases and create a ref.code
            $n = 1;
            foreach ($order->get_items() as $item) {
                if ($item->meta_exists('_nw_return_code'))
                    $n++;
            }
            $return_code = $order->get_id() . '-R' . $n;

            $returned_items = array();
            foreach ($_POST['nw_products'] as $item_id) {
                $item = $order->get_item(absint($item_id));

                // If already returned
                if (!$item || $item->meta_exists('_nw_return_code'))
                    continue;

                if ($item->get_quantity() > 1) {
                    if (!isset($_POST['nw_products_count'][$item_id]))
                        continue;

                    $reduce = absint($_POST['nw_products_count'][$item_id]);
                    if (!$reduce)
                        continue;

                    // If only parts of the full quantity of order items is to be returned
                    // -> split it into to two separate items
                    if ($item->get_quantity() > $reduce) {
                        $factor = $reduce / $item->get_quantity();
                        $new_item = new WC_Order_Item_Product();
                        $new_item->set_name($item->get_name());
                        $new_item->set_props(array(
                            'product_id'   => $item->get_product_id(),
                            'variation_id' => $item->get_variation_id(),
                            'quantity'     => $reduce,
                            'tax_class'         => $item->get_tax_class(),
                            'subtotal'     => $item->get_subtotal() * $factor,
                            'subtotal_tax' => $item->get_subtotal_tax() * $factor,
                            'total'        => $item->get_total() * $factor,
                            'total_tax'    => $item->get_total_tax() * $factor,
                            'taxes'         => array(
                                'subtotal' => array($item->get_subtotal_tax() * $factor),
                                'total'      => array($item->get_total_tax() * $factor),
                            ),
                        ));

                        // $order will save the item for us
                        $order->add_item($new_item);

                        $factor = 1 - $factor;
                        $item->set_props(array(
                            'quantity'     => $item->get_quantity() - $reduce,
                            'subtotal'     => $item->get_subtotal() * $factor,
                            'subtotal_tax' => $item->get_subtotal_tax() * $factor,
                            'total'        => $item->get_total() * $factor,
                            'total_tax'    => $item->get_total_tax() * $factor,
                            'taxes'         => array(
                                'subtotal' => array($item->get_subtotal_tax() * $factor),
                                'total'      => array($item->get_total_tax() * $factor),
                            ),
                        ));
                        $item->save();
                        $item = $new_item;
                    }
                }

                $returned_items[] = $item;
                $item->update_meta_data('_nw_return_code', $return_code);
                $item->save();
            }

            // If successful send email to notify site admin
            if (!empty($returned_items)) {

                // Create the email
                $club_id = absint($order->get_meta('_nw_club'));
                $vendor_id = absint($order->get_meta('_nw_vendor'));
                $msg = wc_get_template_html('emails/return-registered-notice.php', array(
                    'customer_id' => $customer_id = $order->get_customer_id(),
                    'customer_name' => get_user_meta($customer_id, 'first_name', true) . ' ' . get_user_meta($customer_id, 'last_name', true),
                    'club_name' => get_the_title($club_id),
                    'vendor_name' => get_the_title($vendor_id),
                    'returned_items' => $returned_items,
                    'return_code' => $return_code,
                ), '', NW_PLUGIN_DIR . 'templates/');

                $mailer = WC()->mailer();
                $msg = $mailer->wrap_message(_x('Return of products', 'Email header', 'newwave'), $msg);

                $customer_service = get_option('nw_settings_customer_service_email') ? get_option('nw_settings_customer_service_email') : get_option('admin_email');

                $result = $mailer->send($customer_service, sprintf(__('Return of products: %s', 'newwave'), $return_code), $msg);

                if (!$result)
                    NWP_Functions::log('Return-products-email to custom failed to send.', $msg);

                // Redirect to return-registered page for the return created
                if (isset($_POST['nw_forward_url']))
                    $url = isset($_POST['nw_forward_url']) ? home_url(esc_url_raw($_POST['nw_forward_url'])) . $return_code : $url;
            }
            wp_redirect($url);
        }
    }
?>