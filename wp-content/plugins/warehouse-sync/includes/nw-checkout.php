<?php
// If called directly, abort
if (!defined("ABSPATH")) {
    exit();
}

    /**
     * Modifies checkout fields and content
     *
     */
    class NW_Checkout
    {
        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Modify checkout fields
            add_action("woocommerce_checkout_fields", __CLASS__ . "::edit_checkout_fields", 99, 1);

            // Modify shipping address for new orders
            add_action("woocommerce_checkout_order_processed", __CLASS__ . "::order_completed", 99, 3);

            // Modify confirmation email
            add_action("woocommerce_email_before_order_table", __CLASS__ . "::email_display_club_name", 99, 2);

            // Replace the standard checkout templates
            add_action("wc_get_template", __CLASS__ . "::replace_standard_checkout_templates", 99, 5);

            // Make sure WooCommerce knows all orders needs a shipping address
            add_action("woocommerce_order_needs_shipping_address", "__return_true", 99);

            // Remove order notes input from the checkout form
            add_action("woocommerce_checkout_fields", __CLASS__ . "::remove_order_notes", 99, 1);

            // Make available only one payment gateway at a time
            add_filter("woocommerce_available_payment_gateways", __CLASS__ . "::either_one_payment_available", 99, 1);

            // Update order with shop id with invoice (pay by invoice)
            add_action("woocommerce_checkout_order_processed", __CLASS__ . "::order_update_shop_id_invoice", 99, 1);

            // Change order status to sent-to-printing for product type => 'Stock products with Logo'
            add_action("woocommerce_thankyou", __CLASS__ . "::change_order_status_sent_toPrinting", 99, 1);

            // remove some required fields
            add_action("woocommerce_checkout_fields", __CLASS__ . "::checkout_not_required_fields", 99, 1);

            // change order button text
            add_action("woocommerce_order_button_text", __CLASS__ . "::checkout_order_button_text", 99, 1);
        }

        /**
         * Remove order notes from checkout form
         *
         */
        public static function remove_order_notes($fields)
        {
            if (isset($fields["order"]["order_comments"])) {
                unset($fields["order"]["order_comments"]);
            }
            return $fields;
        }

        /**
         * Check whether or not Klarna checkout is activated
         *
         * @return bool
         */
        public static function is_klarna()
        {
            return "kco" == (WC()->session && WC()->session->get("chosen_payment_method")) ? true : false;
        }

        /**
         * Check if given option is an allowed shipping destination for the current shop
         *
         * @param string $option
         * @return bool
         */
        public static function check_allowed_shipping_option($option)
        {
            if (!$option) {
                return false;
            }

            if (nw_has_session()) {
                if (false !== strpos(NW_Session::$shop->get_allowed_shipping(), $option)) {
                    return true;
                } else {
                    return false;
                }
            } elseif (current_user_can("manage_woocommerce")) {
                return true;
            }

            return false;
        }

        /**
         * Replace the standard checkout templates
         *
         * @param string $located Path to the located template
         * @param string $template_name Name of the template we're looking for
         * @param mixed[] $args
         * @param string $template_path
         * @param string $default_path
         * @return string Path to the template
         */
        public static function replace_standard_checkout_templates($located, $template_name, $args, $template_path, $default_path)
        {
            // Custom checkout form, if not Klarna
            if (!static::is_klarna() && "checkout/form-billing.php" == $template_name) {
                $replace = NWP_Functions::locate_template("checkout/newwave-form-billing.php");
                if ($replace) {
                    return $replace;
                }
            }

            // Template displaying shipping address only (disregarding billing)
            if ("order/order-details-customer.php" == $template_name) {
                $replace = NWP_Functions::locate_template($template_name);
                if ($replace) {
                    return $replace;
                }
            }

            if ("emails/email-addresses.php" == $template_name) {
                $replace = NWP_Functions::locate_template($template_name);
                if ($replace) {
                    return $replace;
                }
            }

            return $located;
        }

        /**
         * Display the club name of the current shop in the confirmation email
         *
         * @param WC_Order $order
         * @param bool $sent_to_admin
         */
        public static function email_display_club_name($order, $sent_to_admin)
        {
            // If we don't want to display it on the customer email
            if (!$sent_to_admin && !apply_filters("nw_display_shop_name_on_email_for_customer", true)) {
                return;
            }

            if ($title = get_the_title(absint($order->get_meta("_nw_club")))) {
                printf("<h2>%s</h2>", $title);
            }
        }

        /**
         * Remove checkout fields
         *
         * @param array $fields Checkout fields.
         * @return array
         */
        public static function edit_checkout_fields($fields)
        {
            if (static::is_klarna()) {
                return $fields;
            }

            if (NW_Session::$shop) {
                $shop = NW_Session::$shop;
                $address_types = explode("-", $shop->get_allowed_shipping());
                $options = $fields["billing"];

                // If sending to customer is a valid option for the current $shop
                if (static::check_allowed_shipping_option("customer")) {
                    $customer = new WC_Customer(get_current_user_id());
                    $options["customer"] = [
                        "title" => _x("Your address", "Checkout", "newwave"),
                        "address_fields" => [sprintf("%s %s", $customer->get_first_name(), $customer->get_last_name()), $customer->get_billing_address_1(), $customer->get_billing_address_2(), $customer->get_billing_postcode(), $customer->get_billing_city(), $customer->get_shipping_address_1(), $customer->get_shipping_address_2(), $customer->get_shipping_postcode(), $customer->get_shipping_city()],
                        "incomplete" => false,
                    ];

                    $validate = [$customer->get_billing_address_1(), $customer->get_billing_postcode(), $customer->get_billing_city()];

                    // If any of the fields in $validate[] is empty, customer must
                    // edit address before selecting this a shipping option
                    foreach ($validate as $val) {
                        if (empty($val)) {
                            $options["customer"]["incomplete"] = true;
                        }
                    }
                }

                // If sending to club is a valid option for the current $shop
                if (static::check_allowed_shipping_option("club")) {
                    $options["club"] = [
                        "title" => _x("Your club", "checkout", "newwave"),
                        "address_fields" => [$shop->get_name(), $shop->get_address_1(), $shop->get_address_2(), $shop->get_postcode(), $shop->get_city()],
                    ];
                }

                // If sending to vendor is a valid option for the current $shop
                if (static::check_allowed_shipping_option("vendor")) {
                    $options["vendor"] = [
                        "title" => _x("Your club vendor", "checkout", "newwave"),
                        "address_fields" => [$shop->get_vendor_name(), $shop->get_vendor_address_1(), $shop->get_vendor_address_2(), $shop->get_vendor_postcode(), $shop->get_vendor_city()],
                    ];
                }
                $fields["billing"] = $options;
                if (isset($options2)) {
                    $fields["shipping"] = $options2;
                }
            } else {
                $fields["billing"] = [];
            }

            return $fields;
        }

        /**
         * Validate submitted nw_shipping_destination and trigger processing of the order if so
         *
         * @param int $order_id The id of the order being processed
         * @param int $posted_data The $_POST array (contains selected shipping address)
         * @param WC_Order $order The order being processed
         */
        public static function order_completed($order_id, $posted_data, $order)
        {
            if (static::is_klarna()) {
                return $order_id;
            }

            if (!isset($_POST["nw_shipping_destination"])) {
                throw new Exception(__("You must select a shipping destination!", "newwave"));
            }

            if (!static::check_allowed_shipping_option($_POST["nw_shipping_destination"])) {
                throw new Exception(__("You must select a valid shipping option!", "newwave"));
            }

            // Submitted shipping option is valid, update order shipping fields
            static::set_order_shipping_address($_POST["nw_shipping_destination"], $order);
        }

        /**
         * Set the provided shipping destination as the shipping address for order
         *
         * @param string $destination
         * @param WC_Order $order The order being processed
         */
        public static function set_order_shipping_address($destination, $order)
        {
            // If there's no session for some reason
            if (!nw_has_session()) {
                if (current_user_can("manage_woocommerce")) {
                    return;
                } else {
                    throw new Exception(__("Woops, something went (kinda terribly) wrong!", "newwave"));
                }
            }

            $shop = NW_Session::$shop;
            $customer = new WC_Customer(get_current_user_id());

            // Set club address as destination
            if ($destination == "club") {
                $order->set_shipping_address_1($shop->get_address_1());
                $order->set_shipping_address_2($shop->get_address_2());
                $order->set_shipping_postcode($shop->get_postcode());
                $order->set_shipping_city($shop->get_city());
                $order->set_billing_address_1($shop->get_address_1());
                $order->set_billing_address_2($shop->get_address_2());
                $order->set_billing_postcode($shop->get_postcode());
                $order->set_billing_city($shop->get_city());
            }
            // Set vendor address as destination
            if ($destination == "vendor") {
                $order->set_shipping_address_1($shop->get_vendor_address_1());
                $order->set_shipping_address_2($shop->get_vendor_address_2());
                $order->set_shipping_postcode($shop->get_vendor_postcode());
                $order->set_shipping_city($shop->get_vendor_city());
                $order->set_billing_address_1($shop->get_vendor_address_1());
                $order->set_billing_address_2($shop->get_vendor_address_2());
                $order->set_billing_postcode($shop->get_vendor_postcode());
                $order->set_billing_city($shop->get_vendor_city());
            }

            $order->save();
        }

        public static function either_one_payment_available($methods)
        {
            $is_cod_allowed = false;
            //check if cod enabled for site and user loggin
            if (is_user_logged_in()) {
                $nw_shop_id_invoice = get_user_meta(get_current_user_id(), "must_pay_by_invoice", true);
                if ($nw_shop_id_invoice) {
                    $is_cod_allowed = true;
                }
            }

            if ($is_cod_allowed) {
                foreach ($methods as $method_name => $values) {
                    if ($method_name != "cod") {
                        unset($methods[$method_name]);
                    }
                }
            } else {
                unset($methods["cod"]);
            }

            return $methods;
        }

        /**
         * Update metadata - _nw_shop_id_invoice in order   for users checked in for must_pay_by_invoice
         *
         */
        public static function order_update_shop_id_invoice($order_id)
        {
            if (is_user_logged_in()) {
                $nw_shop_id_invoice = get_user_meta(get_current_user_id(), "must_pay_by_invoice", true);

                if ($nw_shop_id_invoice) {
                    $club_id = WC()->session->get("nw_shop");
                    $vendor_id = wp_get_post_parent_id($club_id);
                    $_nw_shop_id_invoice = get_post_meta($vendor_id, "_nw_shop_id_invoice", true);

                    update_post_meta($order_id, "_nw_shop_id_invoice", $_nw_shop_id_invoice);
                }
            }
        }

        public static function change_order_status_sent_toPrinting($order_id)
        {
            if (!$order_id) {
                return;
            }
            $order = wc_get_order($order_id);

            $items = [];
            foreach ($order->get_items() as $item) {
                if ($item->get_meta("_nw_product_type") == "nw_stock_logo") {
                    $items[] = $item;
                }
            }

            if (is_array($items) && !empty($items)) {
                if ("processing" == $order->get_status()) {
                    //$order->update_status("wc-sent-to-printing");
                }
            }
        }

        public static function checkout_not_required_fields($f)
        {
            if (is_user_logged_in()) {
                $nw_shop_id_invoice = get_user_meta(get_current_user_id(), "must_pay_by_invoice", true);

                if ($nw_shop_id_invoice) {
                    unset($f["shipping"]["shipping_first_name"]["required"]); // that's it
                    unset($f["shipping"]["shipping_last_name"]["required"]);
                    unset($f["shipping"]["shipping_company"]["required"]);
                    unset($f["shipping"]["shipping_country"]["required"]);
                    unset($f["shipping"]["shipping_address_1"]["required"]);
                    unset($f["shipping"]["shipping_address_2"]["required"]);
                    unset($f["shipping"]["shipping_postcode"]["required"]);
                }
            }

            return $f;
        }

        public static function checkout_order_button_text($button_text)
        {
            if (is_user_logged_in()) {
                $nw_shop_id_invoice = get_user_meta(get_current_user_id(), "must_pay_by_invoice", true);
                return "BESTILL"; // new text is here
            }
        }
    }
