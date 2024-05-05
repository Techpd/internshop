<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Modifies checkout fields and content
     *
     */
    class NW_Checkout_Klarna
    {

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Add shipping destination inputs to the Klarna checkout page
            add_action('kco_wc_api_request_args', __CLASS__ . '::klarna_pre_request', 99, 1);
            add_action('kco_wc_before_snippet', __CLASS__ . '::output_shipping_destination_fields');

            // Update selected shipping destination via AJAX
            add_action('wp_ajax_nw_set_shipping_destination', __CLASS__ . '::ajax_set_shipping_destination');
            add_action('wp_footer', __CLASS__ . '::shipping_destination_js');

            // Validate checkout before submitting order to WooCommerce
            add_action('kco_validate_checkout', __CLASS__ . '::validate_checkout', 1, 3);

            // Processed submitted order
            add_action('woocommerce_checkout_order_processed', __CLASS__ . '::order_completed_klarna', 99, 3);

            // Print notice upon failure to select a shipping destination
            add_action('kco_wc_before_checkout_form', __CLASS__ . '::maybe_print_notice');

            // Miscellaneous template alterations
            add_action('kco_wc_before_order_review', __CLASS__ . '::add_cart_title');
            add_action('kco_wc_after_order_review', __CLASS__ . '::close_div');
            add_action('kco_wc_before_snippet', __CLASS__ . '::add_payment_title');
            add_action('kco_wc_after_snippet', __CLASS__ . '::close_div');

            // Deprecated: is handled by static::order_completed_klarna
            // add_action('kco_wc_klarna_order_pre_submit', __CLASS__.'::klarna_pre_submit', 99, 1);
        }

        /**
         * Add title to and wrap cart
         *
         */
        public static function add_cart_title()
        { ?>
            <h3 id="newwave-klarna-cart-title">
                <?php _e('Cart', 'newwave'); ?>
            </h3>
            <div class="newwave-klarna-cart">
            <?php
        }

        /**
         * Add title to and wrap Klarna component
         *
         */
        public static function add_payment_title()
        { ?>
                <h3 id="newwave-klarna-payment-title">
                    <?php _e('Payment', 'newwave'); ?>
                </h3>
                <div class="newwave-klarna-payment">
                <?php
            }

            /**
             * Output closing div tag
             *
             */
            public static function close_div()
            { ?>
                </div>
                <p class="junk-mail-text"><?php echo _e('Vi opplever at ordrebekreftelser, gavekort og sendingsinformasjon ofte havner i søppelposten. Sjekk derfor søppelpost om du ikke har mottatt epost fra oss.','newwave'); ?> </p>
            <?php
            }

            /**
             * Output fields for selecting shipping destinations current shop allows
             *
             */
            public static function output_shipping_destination_fields()
            {
                if (NW_Session::$shop) {
                    $selected = WC()->session->get('nw_shipping_destination');
                    $shop = NW_Session::$shop;

                    // Store the different shipping destinations, to be used by template
                    $options = array();

                    // If shop allows shipping to the club
                    if (NW_Checkout::check_allowed_shipping_option('club')) {
                        $address = array(
                            '<b>' . $shop->get_name() . '</b>',
                            $shop->get_address_1(),
                            $shop->get_address_2(),
                            $shop->get_postcode(),
                            $shop->get_city()
                        );

                        // Remove any empty fields
                        foreach ($address as $key => $field) {
                            if (!$field)
                                unset($address[$key]);
                        }

                        // Add as option
                        $options['club'] = array(
                            'title' => _x('Your club', 'checkout', 'newwave'),
                            'address' => implode('<br>', $address),
                            'checked' => 'club' == $selected ? true : false,
                        );
                    }

                    // If shop allows shipping to the vendor
                    if (NW_Checkout::check_allowed_shipping_option('vendor')) {
                        $address = array(
                            '<b>' . $shop->get_vendor_name() . '</b>',
                            $shop->get_vendor_address_1(),
                            $shop->get_vendor_address_2(),
                            $shop->get_vendor_postcode(),
                            $shop->get_vendor_city()
                        );

                        // Remove any empty fields
                        foreach ($address as $key => $field) {
                            if (!$field)
                                unset($address[$key]);
                        }

                        // Add as option
                        $options['vendor'] = array(
                            'title' => _x('Your club vendor', 'checkout', 'newwave'),
                            'address' => implode('<br>', $address),
                            'checked' => 'vendor' == $selected ? true : false,
                        );
                    }

                    // If shop allows shipping to home address
                    if (NW_Checkout::check_allowed_shipping_option('customer')) {
                        $userid = get_current_user_id();
                        $address = array(
                            get_user_meta($userid, 'shipping_address_1', true),
                            get_user_meta($userid, 'shipping_postcode', true),
                            get_user_meta($userid, 'shipping_city', true),
                        );
                        $errors = array_filter($address);

                        if (!empty($errors)) {
                            $options['customer'] = array(
                                'title' => _x('Pakke i postkasse/Utleveringssted', 'checkout', 'newwave'),
                                'address' => implode('<br>', $address),
                                'checked' => 'customer' == $selected ? true : false,
                            );
                        } else {
                            $options['customer'] = array(
                                'title' => _x('Pakke i postkasse/Utleveringssted', 'checkout', 'newwave'),
                                'address' => _x('Forsendelsen sendes hjem til din adresse.', 'checkout', 'newwave'),
                                'checked' => 'customer' == $selected ? true : false,
                            );
                        }
                    }

                    // Select first option by default, if no choice already made
                    if (!$selected) {
                        reset($options);
                        $options[key($options)]['checked'] = true;
                        WC()->session->set('nw_shipping_destination', key($options));
                    }

                    // Include template, with only passed in parameter being available to said template
                    $include_template = function ($nw_shipping_fields) {
                        include NWP_Functions::locate_template('checkout/newwave-klarna-shipping.php');
                    };
                    $include_template($options);
                }
            }

            /**
             * Add JS to trigger updates when changes to shipping destination occur
             *
             */
            public static function shipping_destination_js()
            {
                if (!is_checkout() || !NW_Checkout::is_klarna())
                    return;
            ?>

                <script type="text/javascript">
                    jQuery(document).ready(function($) {

                        // Save changes to shipping destination, and trigger an update
                        $('input[name="nw_shipping_destination"]').on('change', function() {
                            var data = {
                                action: 'nw_set_shipping_destination',
                                nw_shipping_destination: $(this).val(),
                                nw_shipping_nonce: '<?php echo wp_create_nonce('nw_shipping_nonce'); ?>',
                            };

                            // Submit data, trigger update of Klarna component
                            $.post('<?php echo get_site_url() . '/wp-admin/admin-ajax.php'; ?>', data, function(response) {
                                $('body').trigger('update_checkout');
                            });
                        });
                        $('div.newwave-shipping-option').on('click', function() {
                            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
                        });
                        $('.craft-radio-button').on('click', function() {
                            $(this).siblings('input[type="radio"]').prop('checked', true).trigger('change');
                        });
                    });
                </script>
    <?php
            }

            /**
             * Set shipping destination via AJAX, save selected to WC()->session
             *
             */
            public static function ajax_set_shipping_destination()
            {
                if (wp_verify_nonce('nw_shipping_nonce', $_POST['nw_shipping_nonce']))
                    wp_die(0);

                $destination = isset($_POST['nw_shipping_destination']) ? $_POST['nw_shipping_destination'] : '';

                // Validate that user is allowed to ship to selected destination, set to blank if not
                if (!NW_Checkout::check_allowed_shipping_option($destination))
                    $destination = '';

                // Store it
                WC()->session->set('nw_shipping_destination', $destination);

                wp_die(1);
            }

            /**
             * Modify klarna order creation request
             *
             * @param array $request
             * @return array
             */
            public static function klarna_pre_request($request)
            {
                // Disallow separate shipping address
                $request['options']['allow_separate_shipping_address'] = false;

                // If any valid shipping option is selected, store it as merchant_data;
                // this is used to validate order as a whole when submitted
                $destination = WC()->session->get('nw_shipping_destination');
                if (NW_Checkout::check_allowed_shipping_option($destination)) {
                    $request['merchant_data'] = 'nw_shipping_destination_validated';
                } else {
                    unset($request['merchant_data']);
                }

                return $request;
            }

            /**
             * Validate that the Klarna order has a set shipping destination
             *
             */
            public static function validate_checkout($data, $all_in_stock, $shipping_chosen)
            {
                $val = isset($data['merchant_data']) ? $data['merchant_data'] : '';

                // If not set to valid keyword, redirect and kill the processing of the order
                if ('nw_shipping_destination_validated' !== $val) {
                    header('HTTP/1.0 303 See Other');

                    // Display notice on page redirect
                    header('Location: ' . wc_get_checkout_url() . '?no_shipping_destination');
                    die();
                }
            }

            /**
             * Print notice if Klarna order did not have a shipping destination
             *
             */
            public static function maybe_print_notice()
            {
                if (isset($_GET['no_shipping_destination'])) {
                    wc_add_notice(__('No shipping destination was selected.', 'newwave'), 'error');
                }
            }

            /**
             * Validate selected address option and set it as shipping address for order
             *
             * @param int $order_id The id of the order being processed
             * @param int $posted_data The $_POST array
             * @param WC_Order $order The order being processed
             */
            public static function order_completed_klarna($order_id, $posted_data, $order)
            {
                if (!NW_Checkout::is_klarna()) {
                    return;
                }

                $destination = WC()->session->get('nw_shipping_destination');
                if (!$destination)
                    return;

                NW_Checkout::set_order_shipping_address($destination, $order);
            }

            /* Deprecated, order edit is handled by order_completed_klarna
	 * Sets order shipping address according
	 *
	 *
	 *
	public static function klarna_pre_submit($request) {
		$destination = WC()->session->get('nw_shipping_destination');

		$shop = NW_Session::$shop;
		if ('club' == $destination) {
			$request->shipping_address->street_address = $shop->get_address_1();
			$request->shipping_address->street_address2 = $shop->get_address_2();
			$request->shipping_address->postal_code = $shop->get_postcode();
			$request->shipping_address->city = $shop->get_city();
		}
		else if ('vendor' == $destination) {
			$request->shipping_address->street_address = $shop->get_vendor_address_1();
			$request->shipping_address->street_address2 = $shop->get_vendor_address_1();
			$request->shipping_address->postal_code = $shop->get_vendor_postcode();
			$request->shipping_address->city = $shop->get_vendor_city();
		}
		return $request;
	}*/
        }
?>