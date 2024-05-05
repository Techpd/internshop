<?php
// If called directly, abort
if (!defined("ABSPATH")) {
    exit();
}

    class NW_Order_Export
    {
        public static function init()
        {
            //Check if order export feature is enabled
            if (!get_option('_nw_order_export_enabled')) {
                if (wp_next_scheduled('nw_order_export')) {
                    wp_clear_scheduled_hook("nw_order_export");
                }
                return;
            }

            // Add cron job on plugin activation
            add_action('nw_install', __CLASS__ . '::add_cron_job');

            // Add custom cron interval
            add_filter('cron_schedules', __CLASS__ . '::add_cron_interval', 99);

            // Remove cron job on plugin deactivation
            add_action('nw_uninstall', __CLASS__ . '::remove_cron_job');

            // Export order data to New Wave API
            add_action("nw_order_export", __CLASS__ . "::export");

            // Add metadata for processing and uploading to ASW
            // add_action("woocommerce_checkout_order_processed", __CLASS__ . "::new_order_from_checkout", 99, 3);
            // add_action("woocommerce_order_status_processing", __CLASS__ . "::new_order_from_checkout", 99, 3);
            add_action("woocommerce_payment_complete", __CLASS__ . "::new_order_from_checkout", 99, 3);
            add_action( 'woocommerce_checkout_create_order', __CLASS__ .'::add_nw_order_meta',98,2);

            // Add cron job on WordPress loaded
            add_action("wp_loaded", __CLASS__ . "::add_cron_job"); //failsafe

            // Add custom order resend button to admin order actions
            add_filter("woocommerce_admin_order_actions", __CLASS__ . "::add_custom_order_resend_button", 100, 2);

            // Add custom order resend button CSS
            add_action("admin_head", __CLASS__ . "::add_custom_order_resend_button_css");

            // Resend order data to New Wave on AJAX request
            add_action("wp_ajax_woocommerce_mark_order_status", __CLASS__ . "::resend_to_nw");

            // Add custom column for export status in order list
            add_filter("manage_edit-shop_order_columns", __CLASS__ . "::ibs_export_status_column", 99);

            // Populate content for custom export status column
            add_filter("manage_shop_order_posts_custom_column", __CLASS__ . "::ibs_export_status_column_content", 99);

            // Change order prefix using WooCommerce filter
            add_action('woocommerce_order_number', __CLASS__ . '::change_order_prefix', 99, 2);
        }

        /**
         * Add cron job nw_asw_export to run every 15 minutes
         */

        public static function add_cron_job()
        {
            // Schedule first export to run the next nearest quarter hour
            if (!wp_next_scheduled("nw_order_export")) {
                $start = sprintf(
                    "%s %s %s hours + %s minutes + 10 seconds",
                    date("H:i"),
                    get_option("gmt_offset") > 0 ? "-" : "+",
                    get_option("gmt_offset"),
                    15 - ((int) date("i") % 15)
                );

                wp_schedule_event(strtotime($start), "nw_15_min", "nw_order_export");
            }
        }

        /**
         * Remove cron job on plugin deactivation
         */

        public static function remove_cron_job()
        {
            wp_clear_scheduled_hook("nw_order_export");
        }

        /**
         * Add custom cron interval for uploading to ASW every 15 minutes
         */

        public static function add_cron_interval($events)
        {
            $interval = intval(get_option("_nw_order_api_interval"));
            $interval =  $interval ? $interval : 15;
            $events["nw_15_min"] = [
                "interval" => $interval * 60,
                "display" => __("Every " . $interval . " minutes", "nw_craft"),
            ];

            return $events;
        }

        /**
         * Change prefix of order to CRAFT
         *
         * @param string $id
         * @param WC_Order $order
         * @return string
         */

        public static function change_order_prefix($id, $order)
        {
            $order_prefix = sanitize_text_field(get_option('_nw_order_export_api_prefix'));
            return $order_prefix . $order->get_id();
        }

        /**
         * Cron export function: Upload orders of purchase orders to ASW through API call
         */

        public static function export()
        {
            wc_get_logger()->debug("export: starting export cron at " . date("d-m-Y H:i:s"), ["source" => "nw_order_export_logs"]);

            $order_limit = get_option("_nw_order_threshold") ? get_option("_nw_order_threshold") : 15;

            // Get all orders in ASW queue
            $args = [
                "limit" => $order_limit,
                "orderby" => "date",
                "order" => "DESC",
                "meta_key" => "_nw_order_state",
                "meta_value" => "processing",
            ];

            $query = new WC_Order_Query($args);
            $orders = $query->get_orders();

            wc_get_logger()->debug("export: total orders available for export: " . count($orders), ["source" => "nw_order_export_logs"]);

            foreach ($orders as $order) {
                // Calculate the time difference in minutes between the order's date and the current time.
                $orderDate = (new \DateTime($order->get_date_created()));
                $now = (new \DateTime('now'));
                $diff = $orderDate->diff($now);
                $minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

                //If the time difference is less than or equal to 2 minute, skip the order.
                if ($minutes <= 2) {
                    wc_get_logger()->debug("export: skip order #" . $order->get_id() . ' . Order placed ' . $minutes . ' minutes ago.', ["source" => "nw_order_export_logs"]);
                    continue;
                }

                if (!($order->is_paid()) || $order->get_status() == "failed") {
                    $paid = $order->is_paid() ? "True" : "False";

                    wc_get_logger()->debug("export: skip order #" . $order->get_id() . ". order paid: " .  $paid . " order status: " . $order->get_status(), ["source" => "nw_order_export_logs"]);

                    // Skip cancelled or unpaid orders
                    continue;
                }

                wc_get_logger()->debug("export: export order #" . $order->get_id(), ["source" => "nw_order_export_logs"]);

                static::export_order($order);
            }
        }

        /**
         * Wrapper function to mark order created on checkout for ASW upload
         *
         * @param int $order_id The id of the order being processed
         * @param array $posted_data
         * @param WC_Order $order
         */

        public static function new_order_from_checkout($order_id)
        {
            wc_get_logger()->debug("new_order_from_checkout: Order #" . $order_id . " captured at: " . date("d-m-Y H:i:s"), ["source" => "nw_order_export_logs"]);

            if (is_int($order_id)) {
                $order = wc_get_order($order_id);
                $order_id = $order->get_id();

                $orderState = get_post_meta($order_id,"_nw_order_state", true); 

                //New order from checkout - set order state to "initial"
                if(!$orderState){
                    update_post_meta($order_id, '_nw_order_state', "initial");
                    add_post_meta($order->get_id(),'_nw_order_state_log',"initial");//maintained for log purporse
                }

                $orderReadyForProcessing = static::is_order_ready_for_processing($order_id);

                if($orderReadyForProcessing){
                    // Order is validated - set order state to "processing".
                    update_post_meta($order_id, '_nw_order_state', "processing");
                    add_post_meta($order->get_id(),'_nw_order_state_log',"processing");//maintained for log purporse

                    //add newwave order meta
                    // static::add_nw_order_meta($order);

                    //send order for export.
                    static::export_order($order);
                }
            }else{
                wc_get_logger()->debug("new_order_from_checkout: Invalid order id", ["source" => "nw_order_export_logs"]);
            }
        }

        /**
         * Add a custom "Resend Order" button to the order actions.
         *
         * @param array    $actions The existing order actions.
         * @param WC_Order $order   The WooCommerce order object.
         * @return array Modified array of order actions.
         */

        public static function add_custom_order_resend_button($actions, $order)
        {
            $order_id = method_exists($order, "get_id") ? $order->get_id() : $order->id;

            // Define the action to resend the order and add it to the actions array
            $actions["resend_order_to_nw"] = [
                "url" => wp_nonce_url(admin_url("admin-ajax.php?action=woocommerce_mark_order_status&status=resend_to_nw&order_id=" . $order_id), "woocommerce-mark-order-status"),
                "name" => __("Resend Order", "woocommerce"),
                "action" => "custom",
            ];

            return $actions;
        }

        /**
         * Add custom CSS for the order resend button.
         * This function is used to style the button with a specific icon.
         */

        public static function add_custom_order_resend_button_css()
        {
            // Output custom CSS styles for the order resend button
            echo '<style>.wc-action-button-custom::after { font-family: woocommerce !important; content: "\e029" !important; }</style>';
        }

        /**
         * Add custom order column
         *
         * @param array $columns Order columns
         */

        public static function ibs_export_status_column($columns)
        {
            $columns["ibs_export"] = __("Sent to IBS", "woocommerce");
            return $columns;
        }

        /**
         * Add custom content to ibs export status column
         *
         * @param string $column Current column to modify content for
         */

        public static function ibs_export_status_column_content($column)
        {
            if ($column != "ibs_export") {
                return;
            }

            global $the_order;

            $order = $the_order;
            $order_state =  get_post_meta($order->get_id(),"_nw_order_state", true);
            $raw_date = $order->get_meta("_nw_order_placed_date", true);

            if ($order_state == 'complete') {
                try {
                    if ($raw_date == "") {
                        throw new Exception("No valid date");
                    }

                    $date = new WC_DateTime($raw_date);
                    $timestamp = $date->getTimestamp();
                    $display_date = "";

                    if ($timestamp > strtotime("-1 day", time()) && $timestamp <= time()) {
                        $display_date = sprintf(_x("%s ago", "%s = human-readable time difference", "woocommerce"), human_time_diff($timestamp, time()));
                    } else {
                        $display_date = $date->date_i18n(__("M j, Y", "woocommerce"));
                    }

                    echo '<span style="color:#5b841b;">';
                    printf('<time datetime="%1$s" title="%2$s">%3$s</time>', esc_attr($date->date("c")), esc_html($date->date_i18n(get_option("date_format") . " " . get_option("time_format"))), esc_html($display_date));
                    echo "</span>";
                } catch (Exception $e) {
                    echo __("Uploaded at unknown time", "newwave");
                }
            } elseif ((wp_next_scheduled("nw_order_export") - $order->get_date_created()->getTimestamp() <= 900 && $order->get_meta("_nw_order_placed_date", true)) || $order_state== "processing") {
                echo '<span style="color:#5b841b;">' . __("In upload queue", "newwave") . "</span>";
            } elseif ($order_state == "failed") {
                echo '<span style="color:#ff0000;">' . __("Order failed (upload skipped)", "newwave") . "</span>";
            } elseif ($order_state== "sending") {
                echo '<span style="color:#5b841b;">' . __("Sending to IBS", "newwave") . "</span>";
            }elseif ($order_state== "initial"){
                echo '<span style="color:#5b841b;">' . __("Order created in Woo", "newwave") . "</span>";
            }else{
                echo '<span style="color:#777;">' . __("Pending", "newwave") . "</span>";
            }
        }

        /**
         * Resend an order to the API.
         */

        public static function resend_to_nw()
        {
            check_ajax_referer('woocommerce-mark-order-status', 'security');
            $order_id = $_GET["order_id"];

            wc_get_logger()->debug("resend_to_nw: Resending order #" . $order_id . date("dmY H:i:s"), ["source" => "nw_order_export_logs"]);

            // Check if the "status" parameter is set and indicates a resend request
            if (isset($_GET["status"]) && $_GET["status"] == "resend_to_nw") {
                wc_get_logger()->debug("resend_to_nw: status = " . $_GET["status"], ["source" => "nw_order_export_logs"]);

                $order = wc_get_order($order_id);

                //mark the order state as processing as we manually resend the order to IBS
                update_post_meta($order->get_id(),'_nw_order_state',"processing");
                add_post_meta($order->get_id(),'_nw_order_state_log',"processing");//maintained for log purporse

                // Call the export_order function to resend the order to the API
                static::export_order($order);
            }

            // Redirect back to the previous page or the WooCommerce order listing
            wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url("edit.php?post_type=shop_order"));
            exit();
        }

        /**
         * Export an order to the API.
         *
         * @param WC_Order $order The WooCommerce order to be exported.
         *  @param $manual_resend If set to true, export the order to NW API irrespective of the order status. Otherwise, export only if the order status is "processing".
         */

        public static function export_order($order, $manual_resend = false)
        {
            $order_state = get_post_meta($order->get_id(),'_nw_order_state',true);

            // Check if the order has status other than "processing"
            if ($order_state !== "processing") {
                wc_get_logger()->debug("export_order: Cannot export order #" .$order->get_id() .". Order state = " .$order_state, ["source" => "nw_order_export_logs"]);
            } else if($order_state == "processing" || $manual_resend ){
                wc_get_logger()->debug("export_order: exporting order #" . $order->get_id(), ["source" => "nw_order_export_logs"]);

                $all_skus = "[";
                $ct = 0;
                $stock_with_logo = false;
                $added = [];

                // Check if a certain shop feature is enabled in options
                if (get_option('_nw_shop_feature')) {
                    $vendor_post_id = absint($order->get_meta("_nw_vendor", true));
                    $nw_shop_id = get_post_meta($vendor_post_id, "_nw_shop_id", true);
                    $nw_shop_id_invoice = $order->get_meta("_nw_shop_id_invoice", true);
                    $customerNumber = isset($nw_shop_id_invoice) && !empty($nw_shop_id_invoice) ? $nw_shop_id_invoice : $nw_shop_id;
                    wc_get_logger()->debug("export_order: vendor_post_id = " . $vendor_post_id ." nw_shop_id = " .$nw_shop_id ." nw_shop_id_invoice =" .$nw_shop_id_invoice , ["source" => "nw_order_export_logs"]);

                    if($order->get_shipping_address_1()){
                        wc_get_logger()->debug("export_order: shipping address", ["source" => "nw_order_export_logs"]);
                        $name = $order->get_shipping_company() ? $order->get_shipping_company() : $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
                        $address1 = $order->get_shipping_address_1();
                        $address2 = $order->get_shipping_address_2();
                        $postcode = $order->get_shipping_postcode();
                        $city = $order->get_shipping_city();
                        $phone = $order->get_shipping_phone();
                        $country = $order->get_shipping_country();
                    }else{
                        wc_get_logger()->debug("export_order: billing address", ["source" => "nw_order_export_logs"]);
                        $name = $order->get_billing_company() ? $order->get_billing_company() : $order->get_billing_first_name() . " " . $order->get_billing_last_name();
                        $address1 = $order->get_billing_address_1();
                        $address2 = $order->get_billing_address_2();
                        $postcode = $order->get_billing_postcode();
                        $city =  $order->get_billing_city();
                        $phone = $order->get_billing_phone();
                        $country = $order->get_billing_country();
                    }
                    $order_type = 'IO';
                } else {

                    $customerNumber =  sanitize_text_field(get_option('_nw_export_default_cust'));
                    if ($order->get_payment_method() === "vipps") {
                        $customerNumber = sanitize_text_field(get_option('_nw_export_vipps_cust'));
                    }

                    $shipping_postcodes = sanitize_text_field(get_option('_nw_export_csv_shipping_postcodes'));
                    $shipping_postcodes = explode(",", $shipping_postcodes);

                    if (!empty($order->get_shipping_postcode()) && in_array($order->get_shipping_postcode(), $shipping_postcodes)) {
                        wc_get_logger()->debug("export_order: shipping postcode = " .$order->get_shipping_postcode(), ["source" => "nw_order_export_logs"]);
                        $customerNumber = sanitize_text_field(get_option('_nw_export_shipp_cust'));
                    }

                    $order_type = 'IC';
                    if($order->get_shipping_address_1()){
                        wc_get_logger()->debug("export_order: shipping address", ["source" => "nw_order_export_logs"]);
                        $name = $order->get_shipping_company() ? $order->get_shipping_company() : $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
                        $address1 = $order->get_shipping_address_1();
                        $address2 = $order->get_shipping_address_2();
                        $postcode = $order->get_shipping_postcode();
                        $city =  $order->get_shipping_city();
                        $phone = $order->get_shipping_phone();
                        $country = $order->get_shipping_country();
                    }else{
                        wc_get_logger()->debug("export_order: billing address", ["source" => "nw_order_export_logs"]);
                        $name = $order->get_billing_company() ? $order->get_billing_company() : $order->get_billing_first_name() . " " . $order->get_billing_last_name();
                        $address1 = $order->get_billing_address_1();
                        $address2 = $order->get_billing_address_2();
                        $postcode = $order->get_billing_postcode();
                        $city =  $order->get_billing_city();
                        $phone = $order->get_billing_phone();
                        $country = $order->get_billing_country();
                    }

                    if(strlen($name) > 30){
                        $name = substr($name, 0, 30);
                        $order->add_order_note("Delivery name/company is longer than the allowed maxlenght of 30 characters. Additional characters will be stripped off. To prevent this please increase the limit at IBS.");
                    }
                }

                // Loop through order items to gather SKU and other information
                foreach ($order->get_items() as $item) {
                    wc_get_logger()->debug("Item:" . $item, ["source" => "nw_order_export_logs"]);
                    wc_get_logger()->debug("Item class:" . get_class($item), ["source" => "nw_order_export_logs"]);

                    $variation_id = $item["variation_id"];
                    $product = $variation_id && $variation_id != "0" ? wc_get_product($item["variation_id"]) : wc_get_product($item["product_id"]);
                    $sku = $product ? $product->get_sku() : $item->get_meta("_nw_sku");
                    $comment = "";

                    if (get_option('_nw_shop_feature')) {
                        $prod_type = $item->get_meta("_nw_product_type");
    
                        if(!$prod_type){
                            $parent_product = wc_get_product($item["product_id"]);
                            $prod_type = $parent_product->get_type();
                        }
    
                        wc_get_logger()->debug("Item product type:" . $prod_type, ["source" => "nw_order_export_logs"]);
    
                        if ($prod_type == "nw_stock_logo") {
                            $stock_with_logo = true;
                            $comment = htmlspecialchars(get_post_meta($item["product_id"], "print_instructions", true));
                        }else if(!$prod_type){
                            wc_get_logger()->debug("Item product type is not defined:" . $prod_type, ["source" => "nw_order_export_logs"]);
                            exit();
                        }
                    }

                    if ($item->get_meta("_nw_product_type") == "nw_stock_logo") {
                        $stock_with_logo = true;
                        $comment = htmlspecialchars(get_post_meta($item["product_id"], "print_instructions", true));
                    }

                    $sales_price = round($item->get_total() / $item->get_quantity(), 2);
                    $addon = "";
                    if (isset($added[$sku])) {
                        $addon = ", addOnArticle: true";
                    }

                    if ($comment == "") {
                        $all_skus .= '{sku:\\"' . $sku . '\\", quantity:' . $item->get_quantity() . ", salesPrice:" . $sales_price . $addon . "}";
                    } else {
                        $all_skus .= '{sku:\\"' . $sku . '\\", quantity:' . $item->get_quantity() . ", salesPrice:" . $sales_price . ', comment:\\"' . $comment . '\\" ' . $addon . "}";
                    }

                    if ($ct > 0) {
                        $all_skus .= ",";
                    }

                    $ct++;
                    $added[$sku] = true;
                }

                $all_skus .= "]";

                // Calculate shipping fees and other relevant details
                $shipping = $order->get_shipping_total() + $order->get_shipping_tax();
                // $shippingFee = $shipping ? $shipping : 0; commented out as per instrution from end client for PLANASD - 590

                $order_type = $stock_with_logo ? "IM" : $order_type;
                $customer_ref = strlen($order->get_billing_email()) > 20 ? substr($order->get_billing_email(), 0, 20) : $order->get_billing_email();

                // Construct the GraphQL query for placing the order
                $order_details =
                    '{"query":"{\\r\\n\\torderPlace(\\r\\n\\t\\tskus: ' .
                    $all_skus .
                    ' header: {\\r\\n\\t\\t\\tcustomerReference: \\"' .
                    $customer_ref .
                    '\\"\\r\\n\\t\\t\\tcompleteDelivery: true\\r\\n\\t\\t\\tcustomerOrderReference: \\"' .
                    $order->get_order_number() .
                    '\\"\\r\\n\\t\\t\\torderType: \\"' .
                    $order_type .
                    '\\"\\r\\n\\t\\t}\\r\\n\\t\\tdeliveryAddress: {\\r\\n\\t\\t\\tname: \\"' .
                    $name .
                    '\\"\\r\\n\\t\\t\\tstreet: \\"' .
                    $address1 .
                    '\\"\\r\\n\\t\\t\\tstreet2: \\"' .
                    $address2 .
                    '\\"\\r\\n\\t\\t\\tzipCode: \\"' .
                    $postcode .
                    '\\"\\r\\n\\t\\t\\tcity: \\"' .
                    $city .
                    '\\"\\r\\n\\t\\t\\temail: \\"' .
                    $order->get_billing_email() .
                    '\\"\\r\\n\\t\\t\\tstreet3: \\"' .
                    $phone .
                    '\\"\\r\\n\\t\\t\\tcountry: \\"' .
                    $country .
                    '\\"\\r\\n\\t\\t}\\r\\n\\t\\tcustomerNumber: \\"' .
                    $customerNumber .'\\"'.
                    // '\\r\\n\\t\\tshippingFee: ' .
                    // $shippingFee .
                    '\\r\\n\\t) {\\r\\n\\t\\tcreated\\r\\n\\t\\tmessage\\r\\n\\t\\terrors\\r\\n\\t\\tclientOrderId\\r\\n\\t}\\r\\n}","variables":{}}';

                wc_get_logger()->debug("export_order: order details = " . $order_details, ["source" => "nw_order_export_logs"]);

                // Set order state to "sending".
                update_post_meta($order->get_id(),'_nw_order_state',"sending");
                add_post_meta($order->get_id(),'_nw_order_state_log',"sending");

                // send the API request
                $result = static::api_request($order_details, $order);

                // Add an order note indicating the order type
                $order->add_order_note("Order is set to type " . $order_type);

                $order->add_order_note("Club: " .$order->get_meta("_nw_order_note", true));

                // Process the API response and handle success/failure
                if (isset($result->data->orderPlace->created) && $result->data->orderPlace->created == 1) {
                    $note = "Order successfully placed with Order ID: " .$result->data->orderPlace->clientOrderId ;

                    //Set the order state to "complete".
                    update_post_meta($order->get_id(),'_nw_order_state',"complete");
                    add_post_meta($order->get_id(),'_nw_order_state_log',"complete");//maintained for log purporse
                    wc_get_logger()->debug("export_order: Order state changed to 'complete' for order #" . $order->get_id(), ["source" => "nw_order_export_logs"]);

                    //set the order placed date and time
                    $uploaded_timestamp = new WC_DateTime();
                    $uploaded_timestamp->setTimezone(new DateTimeZone(wc_timezone_string()));

                    $order->update_meta_data("_nw_order_placed_date", strval($uploaded_timestamp));
                    wc_get_logger()->debug("export_order: order uploaded to IBS at:" . strval($uploaded_timestamp), ["source" => "nw_order_export_logs"]);

                    $order->update_status('wc-completed');
                    
                    $note = __($note);
                    $order->add_order_note($note);
                    $order->save();
                } else {
                    $error_message = "";
                    foreach ($result->data->orderPlace->errors as $error) {
                        $error_message .= $error . " ";
                    }

                    wc_get_logger()->debug("export_order: #" . $order->get_id() ." order upload to ASW failed. "  . "Error: " . $error_message, ["source" => "nw_order_export_logs"]);
                    
                    //Set order state to "processing".
                    // update_post_meta($order->get_id(),'_nw_order_state',"processing");
                    // add_post_meta($order->get_id(),'_nw_order_state_log',"processing");//maintained for log purporse 
                    // wc_get_logger()->debug("Adding order #" . $order->get_id() ." back to queue.", ["source" => "nw_order_export_logs"]);
                    if($error_message){
                        $order->add_order_note($error_message);
                        $order->save();
                    }
                    
                }
            }
        }

        /**
         * Make API request to ASW using cURL.
         *
         * @param array $order_data The data to be sent in the API request.
         * @return mixed|null Returns the JSON-decoded response or null on failure.
         */

        public static function api_request($order_data, $order)
        {
            try{
                // The API endpoint URL and token
                $api_url = sanitize_url(get_option('_nw_order_export_api_url'));
                $api_token = sanitize_text_field(get_option("_nw_order_api_token"));

                // Initialize a cURL session
                $curl = curl_init();

                // Set cURL options
                curl_setopt_array($curl, [
                    CURLOPT_URL => $api_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $order_data,
                    CURLOPT_HTTPHEADER => ["Authorization: Bearer " . $api_token, "Content-Type: application/json"],
                ]);

                // Execute the cURL request
                $response = curl_exec($curl);
                if ($response === false) {
                    $response = curl_error($curl);
                }

                // Get HTTP status code and content type from the cURL request
                $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

                // Check for non-200 HTTP status codes and log errors
                if ($httpcode !== 200) {
                    wc_get_logger()->debug("api_request failed with HTTP status code: " . $httpcode, ["source" => "nw_order_export_logs"]);

                    // PLANASD-633
                    if($httpcode == 502){
                        //Set order state to "processing" if incase of network error/server overloads.
                        update_post_meta($order->get_id(),'_nw_order_state',"processing");
                        add_post_meta($order->get_id(),'_nw_order_state_log',"processing");//maintained for log purporse    

                        $note = "Order state changed to processing. Order is added to the queue. HTTP Error: " .$httpcode;
                        $order->add_order_note($note);
                    }else{
                        //Set order state to "failed".
                        update_post_meta($order->get_id(),'_nw_order_state',"failed");
                        add_post_meta($order->get_id(),'_nw_order_state_log',"failed");//maintained for log purporse
                        $order->update_status("failed");
                        $note = "Order state changed to failed. Order is added to the queue. HTTP Error: " .$httpcode;
                        $order->add_order_note($note);
                    }
                }

                $order->save();

                // Log content type and response for debugging
                wc_get_logger()->debug("api_request: content type = " . $content_type, ["source" => "nw_order_export_logs"]);
                wc_get_logger()->debug("api_request: response = " . $response, ["source" => "nw_order_export_logs"]);

                // Return the JSON-decoded response
                return json_decode($response);
            }catch (Exception $e) {
                $order->add_order_note($e->getMessage());
                $order->save();
                wc_get_logger()->debug("api_request error: " . $e->getMessage(), ["source" => "nw_order_export_logs"]);
            }
        }

        /**
         * Checks if the order is ready for ASW upload
         * 
         * @param string $order_id
         * @return boolean
         */

        public static function is_order_ready_for_processing($order_id){
            // Move the order in "processing" state only when the previous state is "initial"
            if (get_post_meta($order_id, "_nw_order_state", true) == "initial") {

                $order = wc_get_order($order_id);

                // Check if the order is paid and not failed
                if($order->is_paid() && !($order->get_status() == "failed")){ 
                    return true;
                }else{
                    $order_paid = $order->is_paid() ? 'paid' : 'not paid';
                    wc_get_logger()->debug("is_order_ready_for_processing: skip order #" . $order->get_id() . ". order paid: " .  $order_paid . " order status: " . $order->get_status(), ["source" => "nw_order_export_logs"]);
                    return false;
                }
            }else{
                wc_get_logger()->debug("is_order_ready_for_processing: Order #" . $order_id . " is not in initial state and cannot be processed.", ["source" => "nw_order_export_logs"]);
                return false;
            }
        }

        /**
         * Add newwave order meta
         * 
         * @param object|$order
         */

        public static function add_nw_order_meta($order){
            //If shop feature is enabled
            if (get_option('_nw_shop_feature')) {
                $club_id = WC()->session->get("nw_shop");
                $order->add_meta_data("_nw_club", $club_id, true);
                wc_get_logger()->debug("add_nw_order_meta: club id = " . $club_id , ["source" => "nw_order_export_logs"]);

                $vendor_id = wp_get_post_parent_id($club_id);
                $order->add_meta_data("_nw_vendor", $vendor_id, true);
                wc_get_logger()->debug("add_nw_order_meta: vendor id = " . $vendor_id , ["source" => "nw_order_export_logs"]);

                $shop = new NW_Shop_Club($club_id);
                $order->add_meta_data('_nw_allowed_shipping', $shop->get_allowed_shipping(), true);
                wc_get_logger()->debug("add_nw_order_meta: allowed shipping = " .$shop->get_allowed_shipping() , ["source" => "nw_order_export_logs"]);

                if($club_id){
                    $order->add_meta_data('_nw_order_note', get_the_title($club_id));
                }
            }

            // Add order item meta
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();

                if (!$product) {
                    continue;
                }

                if ($product->is_type("variation")) {
                    $product = wc_get_product($product->get_parent_id());
                }

                if (!empty($product->get_sku())) {
                    $item->add_meta_data("_nw_sku", $product->get_sku());
                }

                $item->add_meta_data("_nw_product_type", $product->get_type(), true);

                $item->save();
            }
            $order->save();
        }
    }
