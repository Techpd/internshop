<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Adds 'Partially shipped' as a custom order status
     * Adds 'Sent to printing' as a custom order status (earlier it was 'Partially shipped')
     *
     */
    class NW_Order_Status
    {

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {

            // Register the custom post status for WC_Orders
            add_action('init', __CLASS__ . '::register_sent_to_printing_status');

            // Add the custom status to the status dropdown
            add_filter('wc_order_statuses', __CLASS__ . '::add_to_dropdown');

            // Fire function when an order changes status to 'Partially Shipped'
            add_action('woocommerce_order_status_sent-to-printing', __CLASS__ . '::status_changed', 99, 2);

            // Add custom order statuses as "paid" statuses
            add_filter('woocommerce_order_is_paid_statuses', __CLASS__ . '::register_custom_statuses_as_paid', 99, 1);
        }



        /**
         * Register 'Sent to printing' as a custom post status for wc_orders
         *
         */
        public static function register_sent_to_printing_status()
        {
            /*register_post_status('wc-sent-to-printing', array(
			'label'                     => _x('Sendt til trykkeri', 'Order status', 'newwave'),
			'label_count'               => _n_noop('Sendt til trykkeri <span class="count">(%s)</span>', 'Sendt til trykkeri <span class="count">(%s)</span>', 'newwave'),
      'public'                    => true,
      'exclude_from_search'       => false,
      'show_in_admin_all_list'    => true,
      'show_in_admin_status_list' => true,
    ));*/
        }

        /**
         * Register 'wc-sent-to-printing' and 'wc-partially-shipped' as paid order statuses
         *
         */
        public static function register_custom_statuses_as_paid($statuses)
        {
            //$statuses[] = 'sent-to-printing';
            //$statuses[] = 'partially-shipped';
            return $statuses;
        }

        /**
         * Add 'Partially Shipped' to the dropdown of WC Order statuses
         *
         * @param string[] $statuses
         * @return string[]
         */
        public static function add_to_dropdown($statuses)
        {
            //$statuses['wc-sent-to-printing'] = _x('Sendt til trykkeri', 'Order status', 'newwave');
            return $statuses;
        }

        /**
         * Send email notice to customer of changed order status
         *
         * @param int $order_id
         * @param WC_Order $order
         */

        public static function status_changed($order_id, $order)
        {
            // If settings enable sending of notice
            if (get_option('nw_settings_sent_to_printing_notice')) {
                $items = array();
                foreach ($order->get_items() as $item) {

                    if ($item->get_meta('_nw_product_type') == 'nw_stock')
                        $items[] = $item;
                }

                $template = NWP_Functions::locate_template('emails/sent-to-printing-notice.php');
                if (!$template) {
                    NWP_Functions::log('Sent to printing notice template not located. Order #' . $order_id);
                    return;
                }

                $mailer = WC()->mailer();
                ob_start();
                include($template);
                $message = ob_get_clean();
                $message = $mailer->wrap_message(_x('Sent to printing', 'Email header', 'newwave'), $message);

                $customer = new WC_Customer($order->get_customer_id());
                //$result = $mailer->send($customer->get_email(), sprintf(__('Sent to printing: #%s', 'newwave'), $order->get_id()), $message);
            }
        }
    }
