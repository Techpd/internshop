<?php

// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Exports Excel sheets of sales to New Wave FTP Server
     */

    class NW_ASW_Exporter
    {
        /**
         * @var string FTP Folder
         */
        const FTP_FOLDER = '';

        /**
         * Add hooks and filters
         */

        public static function init()
        {
            // Add custom taxonomies, used for queueing up orders to be uploaded
            add_action("init", __CLASS__ . "::register_taxonomy");

            // Add cron job, add custom schedule and trigger action when cron job triggers
            add_action("wp_loaded", __CLASS__ . "::add_cron_job");
            add_filter("cron_schedules", __CLASS__ . "::add_cron_interval", 99);

            //seperate functions are used since the process of generating reports are different when the shop feature is on
            if (get_option('_nw_shop_feature')) {
                add_action("nw_asw_export", __CLASS__ . "::export_reports");
            } else {
                add_action("nw_asw_export", __CLASS__ . "::export");
            }

            add_action("nw_uninstall", __CLASS__ . "::remove_cron_job");

            // Add support for custom queries of wc_orders
            add_filter("woocommerce_order_data_store_cpt_get_orders_query", __CLASS__ . "::handle_custom_query_var", 10, 2);

            // Add meta for processing and uploading to ASW
            //add_action('woocommerce_checkout_order_processed', __CLASS__.'::new_order_from_checkout', 99, 3);
            // add_action("woocommerce_payment_complete", __CLASS__ . "::new_order_from_checkout", 10, 1);
            add_action("woocommerce_thankyou", __CLASS__ . "::new_order_from_checkout", 10, 1);

            // Manage meta box for displaying and setting ASW upload status
            if (!get_option('_nw_shop_feature')) {
                add_action("admin_head", __CLASS__ . "::enqueue_assets");
                add_action("add_meta_boxes_shop_order", __CLASS__ . "::register_meta_box");
                add_action("save_post_shop_order", __CLASS__ . "::maybe_queue_unqueue", 99, 1);
                add_action("admin_head", __CLASS__ . "::disable_order_note_deletion", 99);

                // Add upload status/time column to order overview
                add_filter("manage_edit-shop_order_columns", __CLASS__ . "::asw_export_status_column", 99);
                add_filter("manage_shop_order_posts_custom_column", __CLASS__ . "::asw_export_status_column_content", 99);
            } else {
                // Add meta for processing and uploading to ASW
                add_action('woocommerce_checkout_order_processed', __CLASS__ . '::add_order_meta', 99, 3);
            }
        }

        /**
         * Register taxonomy used for searching for orders to upload to ASW
         */

        public static function register_taxonomy()
        {
            register_taxonomy(
                '_nw_asw_queue',
                'shop_order',
                array(
                    'public'             => false,
                    'publicly_queryable' => false,
                    'show_ui'            => false,
                    'show_in_nav_menus'  => false,
                    'show_in_rest'       => false,
                )
            );

            register_taxonomy(
                '_nw_unprocessed',
                'shop_order',
                array(
                    'public'             => false,
                    'publicly_queryable' => false,
                    'show_ui'            => false,
                    'show_in_nav_menus'  => false,
                    'show_in_rest'       => false,
                )
            );

            register_taxonomy_for_object_type('_nw_asw_queue', 'shop_order');
            register_taxonomy_for_object_type('_nw_unprocessed', 'shop_order');
        }

        /**
         * Add cron job nw_asw_export to run every 15 minutes
         */

        public static function add_cron_job()
        {
            // Schedule first export to run the next nearest quarter hour
            if (!wp_next_scheduled('nw_asw_export')) {
                $start = sprintf(
                    '%s %s %s hours + %s minutes + 10 seconds',
                    date('H:i'),
                    get_option('gmt_offset') > 0 ? '-' : '+',
                    get_option('gmt_offset'),
                    15 - ((int) date('i') % 15)
                );

                wp_schedule_event(strtotime($start), 'nw_15_min', 'nw_asw_export');
            }
        }

        /**
         * Add custom cron interval for uploading to ASW every 15 minutes
         */

        public static function add_cron_interval($events)
        {
            $events["nw_15_min"] = [
                "interval" => 900,
                "display" => __("Every 15 minutes", "newwave"),
            ];

            return $events;
        }

        /**
         * Remove cron job on plugin deactivation
         */

        public static function remove_cron_job()
        {
            wp_clear_scheduled_hook('nw_asw_export');
        }

        /**
         * Enqueue styling to change order of fields, and hide input fields that
         * are non-applicable to coupons for NewWave
         */

        public static function enqueue_assets()
        {
            $screen = get_current_screen();
            if ('shop_order' == $screen->post_type) {
                wp_enqueue_style(
                    'nw_admin_order_asw_status',
                    NW_Plugin::$plugin_url . 'assets/css/nw-admin-order-asw-status.css'
                );
            }
        }

        /**
         * Export function, upload reports of new purchases to ASW in Excel-format
         * scheduled to run every 15 minutes
         * Generates reports of sales made since last run
         */

        public static function export()
        {
            // Get all orders in ASW queue
            $orders = wc_get_orders(array(
                'posts_per_page' => -1,
                'asw_queue' => true,
            ));

            // No unprocessed orders
            if (empty($orders)) {
                return;
            }

            $orders_in_excel_report = [];

            try {
                // Require PHPSpreadsheet
                require "vendor/autoload.php";

                $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->setActiveSheetIndex(0)->setTitle("Sheet1");
                $dtype = PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;

                // Create header row
                $sheet->setCellValue("A1", "Artikelnummer");
                $sheet->setCellValue("B1", "Enhet");
                $sheet->setCellValue("C1", "Eankod");
                $sheet->setCellValue("D1", "Antal");
                $sheet->setCellValue("E1", "kundens ordernummer");
                $sheet->setCellValue("F1", "Kundnummer");
                $sheet->setCellValue("G1", "Namn");
                $sheet->setCellValue("H1", "Adress 1");
                $sheet->setCellValue("I1", "Adress 2");
                $sheet->setCellValue("J1", "Adress 3");
                $sheet->setCellValue("K1", "Adress 4");
                $sheet->setCellValue("L1", "Postkod");
                $sheet->setCellValue("M1", "Landskod");
                $sheet->setCellValue("N1", "Pris");
                $sheet->setCellValue("O1", "Leveransdatum");
                $sheet->setCellValue("P1", "Ordertyp");
                $row = 2;

                // Generate reports
                foreach ($orders as $order) {
                    if (!$order->is_paid()) { // skip cancelled or unpaid orders
                        if (($order->get_status() === "pending" || $order->get_status() === "on-hold") && !get_post_meta($order->get_id(), "_nw_pending_note_added", true)) {

                            $order->add_order_note(__("Attempted to upload to ASW, but order is not paid yet. Trying again later.", "newwave"));

                            update_post_meta($order->get_id(), "_nw_pending_note_added", true);
                        }
                        continue;
                    }

                    // skip already uploaded orders, if they for some reason are
                    if (get_post_meta($order->get_id(), "_nw_uploaded_to_asw", true)) {
                        // attempted to be uploaded again
                        static::log("Attempted to re-upload " . $order->get_id() . " (" . $order->get_order_number() . ") at" . date("Ymd-H-i"));
                        continue;
                    }

                    $customer_id = sanitize_text_field(get_option('_nw_export_csv_default_cust'));

                    if ($order->get_payment_method() === "vipps") {
                        $customer_id = sanitize_text_field(get_option('_nw_export_csv_vipps_cust'));
                    }

                    $shipping_postcodes = sanitize_text_field(get_option('_nw_export_csv_shipping_postcodes'));
                    $shipping_postcodes = explode(",", $shipping_postcodes);

                    if (in_array($order->get_shipping_postcode(), $shipping_postcodes)) {
                        $customer_id = sanitize_text_field(get_option('_nw_export_csv_shipp_cust'));
                    }

                    foreach ($order->get_items() as $item) {
                        $product = $item->get_product();
                        if ($product) {
                            $sheet->setCellValueExplicit("A" . $row, $product->get_sku(), $dtype);
                        } else {
                            $sheet->setCellValueExplicit("A" . $row, "N/A", $dtype);
                        }

                        $sheet->setCellValueExplicit("B" . $row, "PCS", $dtype);
                        // No EAN code
                        $sheet->setCellValue("D" . $row, $item->get_quantity());
                        $sheet->setCellValueExplicit("E" . $row, $order->get_order_number(), $dtype);
                        $sheet->setCellValueExplicit("F" . $row, $customer_id, $dtype);
                        $sheet->setCellValueExplicit("G" . $row, substr(sprintf("%s %s", $order->get_shipping_first_name(), $order->get_shipping_last_name()), 0, 30), $dtype);

                        $sheet->setCellValueExplicit("H" . $row, substr($order->get_shipping_address_1(), 0, 35), $dtype);
                        $sheet->setCellValueExplicit("I" . $row, substr($order->get_shipping_address_2(), 0, 35), $dtype);
                        $sheet->setCellValueExplicit("J" . $row, str_replace(" ", "", substr($order->get_billing_phone(), 0, 35)), $dtype);
                        $sheet->setCellValueExplicit("K" . $row, substr($order->get_shipping_city(), 0, 35), $dtype);
                        $sheet->setCellValueExplicit("L" . $row, substr($order->get_shipping_postcode(), 0, 35), $dtype);
                        $sheet->setCellValueExplicit("M" . $row, sanitize_text_field(get_option('_nw_export_csv_land_code')), $dtype);
                        $sheet->setCellValue("N" . $row, round($item->get_total() / $item->get_quantity(), 2));
                        $sheet->setCellValueExplicit("O" . $row, (new DateTime("now"))->format("Ymd"), $dtype);
                        $sheet->setCellValueExplicit("P" . $row, "IC", $dtype);
                        $row++;
                    }

                    array_push($orders_in_excel_report, $order);
                }
                static::log("total number of rows/items in spreadsheet: " . $row);

                // No rows were written, nothing to export
                if (2 == $row) {
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet);
                    return;
                }

                // Write excel file locally, so we have something to upload
                $file_prefix = sanitize_file_name(get_option('_nw_export_csv_filename'));
                $file_name = sprintf($file_prefix . "-%s.xlsx", date("Ymd-H-i"));
                $path = sprintf("%s/asw-uploads/", wp_upload_dir()["basedir"]);
                wp_mkdir_p($path); // Make sure folder exists
                static::log("File to be uploaded to " . $path);

                // Write Excel sheet
                try {
                    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                    $writer->save($path . $file_name);
                } catch (Exception $e) {
                    static::log("Failed creating report at " . date("Ymd-H-i"), $e->getMessage());
                    error_log("Failed creating report at " . date("Ymd-H-i"));
                    return;
                }

                // Clear spreadsheet from memory
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                // Upload reports to ASWs FTP Server
                $ftp_server = sanitize_text_field(get_option('_nw_export_csv_ftp'));
                $ftp_login = sanitize_text_field(get_option('_nw_export_csv_ftp_user'));
                $ftp_pass = sanitize_text_field(get_option('_nw_export_csv_ftp_pass'));

                $asw_ftp = ftp_connect($ftp_server);
                $login = ftp_login($asw_ftp, $ftp_login, $ftp_pass);
                ftp_pasv($asw_ftp, true); // Set FTP mode to 'passive'

                if (@$login) {
                    static::log("Uploading file " . $file_name);
                    if (!ftp_put($asw_ftp, static::FTP_FOLDER . $file_name, $path . $file_name, FTP_BINARY)) {
                        static::log("Failed to upload file " . $file_name);
                        error_log("Failed to upload file " . $file_name);
                    } else {
                        static::log("Uploaded file successfully " . $file_name);
                    }
                } else {
                    static::log("Failed to connect to ASW server");
                    error_log("Failed to connect to ASW server");
                }

                ftp_close($asw_ftp);

                // Un-queue all successfully uploaded orders
                $uploaded_timestamp = new WC_DateTime();
                $uploaded_timestamp->setTimezone(new DateTimeZone(wc_timezone_string()));

                foreach ($orders_in_excel_report as $uploaded_order) {
                    static::unqueue_order($uploaded_order, true);

                    // Mark all as uploaded, in case of manual re-upload
                    update_post_meta($uploaded_order->get_id(), "_nw_uploaded_to_asw", strval($uploaded_timestamp));
                }
            } catch (Exception $e) {

                $failed_orders = [];
                foreach ($orders_in_excel_report as $failed_order) {
                    array_push($failed_orders, $failed_order->get_id() . " (" . $failed_order->get_order_number() . ")");
                }
                static::log("Failed uploading orders to ASW at " . date("Ymd-H-i") . "\nOrders: " . implode(", ", $failed_orders), $e->getMessage());
                error_log("Failed uploading orders to ASW at " . date("Ymd-H-i"));
            }
        }

        /**
         * Extends arguments passed to wc_get_orders() to allow to search for order based on custom taxonomies
         *
         * @param array $query Array of query args
         * @param array $query Array of query vars
         * @return array
         */

        public static function handle_custom_query_var($query, $query_vars)
        {
            if (isset($query_vars["asw_queue"])) {
                $query["tax_query"][] = [
                    "taxonomy" => "_nw_asw_queue",
                    "field" => "slug",
                    "terms" => ["true"],
                ];
            }

            if (isset($query_vars['unprocessed_orders'])) {
                $query['tax_query'][] = array(
                    'taxonomy' => '_nw_unprocessed',
                    'field'    => 'slug',
                    'terms'    => array('true'),
                );
            }
            return $query;
        }

        public static function display_meta_box($post)
        {
            $order = wc_get_order($post->ID);
            $in_queue = wp_get_object_terms($order->get_id(), "_nw_asw_queue");
            $already_uploaded = get_post_meta($order->get_id(), "_nw_uploaded_to_asw", true);

            // Output status
            $status = __("In upload queue", "newwave");
            if (!$in_queue) {
                $status = __("Not in upload queue", "newwave");
            }
            if ($in_queue && !$order->is_paid()) {
                $status = __("In upload queue, but not paid", "newwave");
            }
            if ($already_uploaded) {
                $status = __("Already uploaded, manual re-upload not possible", "newwave");
            }

            printf('<p class="status %sin-queue">%s</p>', $in_queue ? "" : "not-", $status);

            // Output change buttons
            $disabled = ["disabled" => "disabled"];
            submit_button(_x("Queue", "Add to queue button", "newwave"), "primary", "nw_asw_queue", false, !$in_queue && !$already_uploaded ? [] : $disabled);
            submit_button(_x("Unqueue", "Remove from queue button", "newwave"), "delete", "nw_asw_unqueue", false, $in_queue && !$already_uploaded ? [] : $disabled);
        }

        /**
         * Add meta box for displaying ASW status
         */

        public static function register_meta_box()
        {
            add_meta_box("nw_asw_status", "ASW Status", __CLASS__ . "::display_meta_box", "shop_order", "side", "high");
        }

        /**
         * Unqueue or queue order if any of the admin buttons were clicked
         *
         * @param int $order_id
         */

        public static function maybe_queue_unqueue($order_id)
        {
            $order = wc_get_order($order_id);

            if (isset($_POST["nw_asw_queue"])) {
                $order->add_order_note(__("Manually added to ASW upload queue", "newwave"));
                static::queue_order($order);
            } elseif (isset($_POST["nw_asw_unqueue"])) {
                $order->add_order_note(__("Manually removed from the ASW upload queue", "newwave"));
                static::unqueue_order($order);
            }
        }

        /**
         * Logs custom messages within plugin, writes them to local log file
         *
         * @param string $msg Message to send.
         * @param mixed $payload String, object or array for reference.
         */

        public static function log($msg, $payload = false)
        {
            try {
                // Compose message
                $log = sprintf("@%s - %s\n", date('H:i:s d-m-Y'), $msg);
                $trace = debug_backtrace(0, 1);

                if (false !== $payload)
                    $log .= sprintf("Payload: %s\n", print_r($payload, true));

                $log .= sprintf(
                    "File: %s\nLine: %s\nFunction: %s\nArguments: %s",
                    $trace[0]['file'],
                    $trace[0]['line'],
                    $trace[0]['function'],
                    print_r(
                        $trace[0]['args'],
                        true
                    )
                );
                $log .= "\n\n";

                // Write to log file
                $f = fopen(NW_Plugin::$plugin_dir . 'exports.log', 'a+');
                fwrite($f, $log);
                fclose($f);
            } catch (Exception $e) {
            }
        }

        /**
         * Unqueue $order for ASW upload
         *
         * @param int|WC_Order $order
         * @param bool $uploaded Whether to add time & date of upload to $order log
         */

        public static function unqueue_order($order, $uploaded = false)
        {
            if (is_int($order)) {
                $order = wc_get_order($order);
            }

            // If upload was done, add to log
            if ($uploaded) {
                $order->add_order_note(__("Uploaded to ASW", "newwave"));
                do_action("nw_order_uploaded_to_asw", $order);
            }

            wp_delete_object_term_relationships($order->get_id(), "_nw_asw_queue");

            do_action("nw_order_unqueued", $order);
        }

        /**
         * Hide the link for admin users to delete order notes
         */

        public static function disable_order_note_deletion()
        {
            $screen = get_current_screen();
            if ($screen->post_type === "shop_order" && $screen->base === "post") {
                echo "<style>a.delete_note { display:none; }</style>";
            }
        }

        /**
         * Add custom order column
         *
         * @param array $columns Order columns
         */

        public static function asw_export_status_column($columns)
        {
            $columns["asw_export"] = __("ASW Export", "newwave");
            return $columns;
        }

        /**
         * Add custom content to asw export status column
         *
         * @param string $column Current column to modify content for
         */

        public static function asw_export_status_column_content($column)
        {
            if ($column != "asw_export") {
                return;
            }

            global $the_order;

            $order = $the_order;
            $raw_date = get_post_meta($order->get_id(), "_nw_uploaded_to_asw", true);

            if ($raw_date) {
                try {
                    $date = new WC_DateTime($raw_date);
                    $display_date = static::get_human_time_diff($date->getTimestamp());

                    if (!$display_date) {
                        $display_date = $date->date_i18n(__("M j, Y", "woocommerce"));
                    }
                    echo '<span style="color:#5b841b;">';
                    printf('<time datetime="%1$s" title="%2$s">%3$s</time>', esc_attr($date->date("c")), esc_html($date->date_i18n(get_option("date_format") . " " . get_option("time_format"))), esc_html($display_date));
                    echo "</span>";
                } catch (Exception $e) {
                    echo __("Uploaded at unknown time", "newwave");
                }
            } elseif (wp_next_scheduled("nw_asw_export") - $order->get_date_created()->getTimestamp() <= 900 && has_term("", "_nw_asw_queue", $order->get_id())) {
                echo '<span style="color:#94660c;">' . __("In upload queue", "newwave") . "</span>";
            } elseif ($order->get_status() == "cancelled") {
                echo '<span style="color:#777;">' . __("Order cancelled (upload skipped)", "newwave") . "</span>";
            } elseif ($order->get_status() == "failed") {
                echo '<span style="color:#777;">' . __("Order failed (upload skipped)", "newwave") . "</span>";
            } else {
                echo '<span style="color:#761919;">' . __("Not uploaded", "newwave") . "</span>";
            }
        }

        /**
         * Lifted from woocommerce/class-wc-admin-list-table-orders.php
         *
         * @param string $timestamp Unicode timestamp
         */

        public static function get_human_time_diff($timestamp)
        {
            // Check if the order was created within the last 24 hours, and not in the future.
            if ($timestamp > strtotime("-1 day", time()) && $timestamp <= time()) {
                $formatted_date = sprintf(_x("%s ago", "%s = human-readable time difference", "woocommerce"), human_time_diff($timestamp, time()));
                return $formatted_date;
            }

            return false;
        }

        /**
         * Queue $order for ASW upload
         *
         * @param int|WC_Order $order_id
         */

        public static function queue_order($order)
        {
            if (is_int($order)) {
                $order = wc_get_order($order);
            }

            $customer_id = sanitize_text_field(get_option('_nw_export_csv_default_cust'));

            // If a coupon is used, find it's corresponding customer id
            if (method_exists("NW_Coupons", "get_customer_id")) {
                foreach ($order->get_coupon_codes() as $coupon_id) {
                    $coupon = new WC_Coupon($coupon_id);
                    $nw_coupon_type = $coupon->get_meta("_nw_coupon_type");

                    if ($nw_coupon_type) {
                        $customer_id = NW_Coupons::get_customer_id($nw_coupon_type);
                        break;
                    }
                }
            }

            $order->update_meta_data("_nw_customer_id", $customer_id);
            $order->save();

            wp_set_object_terms($order->get_id(), "true", "_nw_asw_queue", false);
            do_action("nw_order_queued", $order);
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
            if (!$order_id) {
                return;
            }

            $order = wc_get_order($order_id);

            if (!get_post_meta($order_id, "_nw_uploaded_to_asw", true)) {
                static::queue_order($order_id);
                $order->add_order_note(__("Order paid. Adding to ASW upload queue", "newwave"));
            }
        }

        /**
         * Add meta data to order and order items
         *
         * @param int $order_id The id of the order being processed
         * @param array $posted_data
         * @param WC_Order $order
         */

        public static function add_order_meta($order_id, $posted_data, $order)
        {

            foreach ($order->get_items() as $item) {
                // Mark for uploading to ASW
                $item->add_meta_data('_nw_unprocessed', 'true', true);
                $product =  $item->get_product();

                // Save the SKU as order item meta, in case actual product gets deleted* before its uploaded to ASW
                if (!empty($product->get_sku()))
                    $item->add_meta_data('_nw_sku', $product->get_sku());

                if ($product->is_type('variation'))
                    $product = wc_get_product($product->get_parent_id());

                // Save the product type as order item meta too, for the same reason
                $item->add_meta_data('_nw_product_type', $product->get_type(), true);
                $item->get_product();
                $item->save();
            }

            $club_id = WC()->session->get('nw_shop');
            $order->add_meta_data('_nw_club', $club_id, true);

            $vendor_id = wp_get_post_parent_id($club_id);
            $order->add_meta_data('_nw_vendor', $vendor_id, true);

            $order->save();

            // Mark order for upload to ASW
            wp_set_object_terms($order_id, 'true', '_nw_unprocessed', false);
        }

        /**
         * Export function, upload reports of new purchases to ASW in Excel-format - This will export different reports for each product type - stock item, stock item with 
         * logo and special item.
         * This function will be used when the shop feature is on. 
         * scheduled to run every 15 minutes
         * Generates reports of sales made since last run
         */

        public static function export_reports()
        {
            $orders = wc_get_orders(array('unprocessed_orders' => true));

            // Require PHPSpreadsheet
            require "vendor/autoload.php";

            // Reports per product type
            $reports = array(
                'nw_stock' => array(
                    'sheet' => new PhpOffice\PhpSpreadsheet\Spreadsheet(),
                    'current_row' => 1,
                ),
                'nw_stock_logo' => array(
                    'sheet' => new PhpOffice\PhpSpreadsheet\Spreadsheet(),
                    'current_row' => 1,
                ),
                'nw_special' => array(
                    'sheet' => new PhpOffice\PhpSpreadsheet\Spreadsheet(),
                    'current_row' => 1,
                )
            );

            // Generate reports
            foreach ($orders as $order) {
                if (!$order->is_paid()) { // skip cancelled or unpaid orders
                    continue;
                }

                // Will be true if all products in order have been sent to ASW
                $order_is_complete = true;

                foreach ($order->get_items() as $item) {
                    // Only consider order items we haven't processed before
                    if ('true' == $item->get_meta('_nw_unprocessed', true)) {
                        $product = $item->get_product();
                        $parent_product = ($product->get_type() == 'variation') ? wc_get_product($product->get_parent_id()) : NULL;

                        // If somehow a non-NW product is being processed
                        if (is_null($parent_product) || !in_array($parent_product->get_type(), array_keys($reports))) {
                            continue;
                        }

                        // Add row to report of the product type of the current product
                        $sheet = $reports[$parent_product->get_type()]['sheet']->setActiveSheetIndex(0);
                        $row = $reports[$parent_product->get_type()]['current_row'];
                        $dtype = PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;

                        // Create header row
                        if ($row == 1) {
                            $reports[$parent_product->get_type()]['sheet']->getProperties()->setCreator('Craftshop');
                            $sheet->setCellValue('A1', 'Artikelnummer');
                            $sheet->setCellValue('B1', 'Enhet');
                            $sheet->setCellValue('C1', 'Eankod');
                            $sheet->setCellValue('D1', 'Antal');
                            $sheet->setCellValue('E1', 'kundens ordernummer');
                            $sheet->setCellValue('F1', 'Kundnummer');
                            /* added 20190124 */
                            $sheet->setCellValue('G1', 'Namn');
                            $sheet->setCellValue('H1', 'Adress 1');
                            $sheet->setCellValue('I1', 'Adress 2');
                            $sheet->setCellValue('J1', 'Adress 3');
                            $sheet->setCellValue('K1', 'Adress 4');
                            $sheet->setCellValue('L1', 'Postkod');
                            $sheet->setCellValue('M1', 'Landskod');
                            $row++;
                        }

                        $sheet->setCellValueExplicit("A" . $row, $product->get_sku(), $dtype);
                        $sheet->setCellValueExplicit("B" . $row, 'PCS', $dtype);
                        // No EAN code
                        $sheet->setCellValueExplicit("D" . $row, $item->get_quantity(), $dtype);

                        // Include custom name with order number, but length of any field can be maximum 35 characters
                        $sheet->setCellValueExplicit('E' . $row, substr(
                            sprintf('%d -%s %s', $order->get_order_number(), $order->get_billing_first_name(), $order->get_billing_last_name()),
                            0,
                            35
                        ), $dtype);

                        $vendor_post_id = absint($order->get_meta('_nw_vendor', true));
                        $sheet->setCellValueExplicit('F' . $row, get_post_meta($vendor_post_id, '_nw_shop_id', true), $dtype);
                        $sheet->setCellValueExplicit('G' . $row, substr(sprintf(
                            "%s %s",
                            $order->get_shipping_first_name(),
                            $order->get_shipping_last_name()
                        ), 0, 30), $dtype);

                        $sheet->setCellValueExplicit('H' . $row, substr($order->get_shipping_address_1(), 0, 35), $dtype);
                        $sheet->setCellValueExplicit('I' . $row, substr($order->get_shipping_address_2(), 0, 35),  $dtype);
                        $sheet->setCellValueExplicit('J' . $row, str_replace(' ', '', substr($order->get_billing_phone(), 0, 35)), $dtype);
                        $sheet->setCellValueExplicit('K' . $row, substr($order->get_shipping_city(), 0, 35), $dtype);
                        $sheet->setCellValueExplicit('L' . $row, substr($order->get_shipping_postcode(), 0, 35), $dtype);
                        $sheet->setCellValueExplicit('M' . $row, $order->get_shipping_country(), $dtype);
                        $row++;

                        // Mark item as processed
                        $item->update_meta_data('_nw_unprocessed', 'false');
                        $item->save();

                        // Update row index
                        $reports[$parent_product->get_type()]['current_row'] = $row;
                    }
                }
                if ($order_is_complete) // if all items in order has been processed, unmark order
                    wp_delete_object_term_relationships($order->get_id(), '_nw_unprocessed');
            }

            // Write reports to files locally before FTP upload
            $error_occured = false;
            $files = array();

            foreach ($reports as $type => $report) {
                if ($report['current_row'] == 1)
                    continue;

                $sheet = $report['sheet'];

                // Name the first sheet
                $sheet->setActiveSheetIndex(0)->setTitle('Sheet1');

                // Type up file names and local paths
                $file_prefix = sanitize_file_name(get_option('_nw_export_csv_filename'));
                $file_name = sprintf($file_prefix . '-%s-%s.xls', date('Ymd-H-i'), str_replace('nw_', '', $type));
                $path = sprintf('%s/newwave/', wp_upload_dir()['basedir']);

                // Make sure folder exists
                wp_mkdir_p($path);

                // Create Excel sheets
                try {
                    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet);
                    $writer->save($path . $file_name);
                } catch (Exception $e) {
                    static::log('Failed creating report for ' . $type . ' at ' . date('d-m-Y'), $e->getMessage());
                    error_log('Failed creating report for ' . $type . ' at ' . date('d-m-Y'));
                    $error_occured = true;
                }

                // Store file name, so we know what file to upload via FTP
                $files[$type] = array(
                    'name' => $file_name,
                    'path' => $path,
                );
            }

            // No need to connect to FTP if there are no files to upload
            if (empty($files) || $error_occured)
                return;

            // Upload reports to ASWs FTP Server
            $ftp_server = sanitize_text_field(get_option('_nw_export_csv_ftp'));
            $ftp_login = sanitize_text_field(get_option('_nw_export_csv_ftp_user'));
            $ftp_pass = sanitize_text_field(get_option('_nw_export_csv_ftp_pass'));

            $asw_ftp = ftp_connect($ftp_server);
            $login = ftp_login($asw_ftp, $ftp_login, $ftp_pass);
            // Set FTP mode to 'passive'
            ftp_pasv($asw_ftp, true);

            if (@$login) {
                foreach ($files as $file) {
                    static::log("Uploading file " . $file_name);
                    if (!ftp_put($asw_ftp, static::FTP_FOLDER . $file['name'], $file['path'] . $file['name'], FTP_BINARY)) {
                        static::log('Failed to upload file ' . $file['name']);
                        break;
                    }
                }
            } else {
                static::log('Failed to connect to ASW server');
                error_log('Failed to connect to ASW server');
            }
        }
    }