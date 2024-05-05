<?php

// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Syncs product balance (stock) from ASW
     */

    class NW_ASW_Stock_Balance
    {

        /**
         * Add hooks and filters
         */

        public static function init()
        {
            //Check if stock import feature is enabled
            if (!get_option('_nw_stock_sync_enabled')) {
                if (wp_next_scheduled('nw_update_stock_balance')) {
                    wp_clear_scheduled_hook("nw_update_stock_balance");
                }
                return;
            }

            // Add hooks for initialization
            add_action('nw_install', __CLASS__ . '::add_cron_job');
            add_filter('cron_schedules', __CLASS__ . '::add_cron_interval', 99);
            add_action('nw_uninstall', __CLASS__ . '::remove_cron_job');
            add_action('nw_update_stock_balance', __CLASS__ . '::update_stock_balance');

            add_action('wp_loaded', __CLASS__ . '::add_cron_job'); // failsafe
        }

        /**
         * Add a cron job to update stock balance
         */

        public static function add_cron_job()
        {
            if (!wp_next_scheduled('nw_update_stock_balance')) {
                $fixed_offset = 5;
                $start = sprintf(
                    '%s %s %s hours + %s minutes + 10 seconds',
                    date('H:i'),
                    get_option('gmt_offset') > 0 ? '-' : '+',
                    get_option('gmt_offset'),
                    30 - ((int)date('i') % 30) + $fixed_offset
                );

                if(!get_option('nw_stock_cron_running')){
                    wp_schedule_event(strtotime($start), 'nw_30_min', 'nw_update_stock_balance');
                }
            }
        }

        /**
         * Add custom cron interval for syncing with ASW every 30 minutes
         */

        public static function add_cron_interval($events)
        {
            $interval = intval(get_option('_nw_stock_api_interval'));
            $interval = $interval ? $interval : 30;
            $events['nw_30_min'] = array(
                'interval' => $interval * 60,
                'display' => __('Every ' . $interval . ' minutes', 'nw_craft'),
            );

            return $events;
        }

        /**
         * Remove the scheduled cron job
         */

        public static function remove_cron_job()
        {
            wp_clear_scheduled_hook('nw_update_stock_balance');
        }

        /**
         * Update stock balance function
         */

        public static function update_stock_balance()
        {
            global $wpdb;

            $logger = wc_get_logger();

            $page = 1;

            update_option('nw_stock_cron_running', 1);//this is added to identify whether the stock cron is in process;

            try {

                $logger->info('NW Stock update started at: ' . date('d-m-Y H:i:s'), array('source' => 'new_wave-stock-logs'));

                do {
                    $q = new WP_Query(array(
                        'post_type' => 'product',
                        'posts_per_page' => 50,
                        'post_status' => 'publish',
                        'paged' => $page,
                    ));

                    $logger->info(wc_print_r('Total posts count:' . $q->found_posts, true), array('source' => 'new_wave-stock-logs'));

                    $skus = array();
                    foreach ($q->posts as $post) {
                        $product = new WC_Product($post->ID);
                        if ($product) {
                            array_push($skus, $product->get_sku());
                        }
                    }

                    // $logger->info('skus:' . print_r($skus, true), array('source' => 'new_wave-stock-logs'));

                    // Get stock balance updates from ASW
                    $updates = static::get_stock_balance($skus);

                    // $logger->info('get_stock_balance:' . print_r($updates, true), array('source' => 'new_wave-stock-logs'));

                    $parent_arrays = array();

                    if (is_array($updates) && count($updates)) {
                        foreach ($updates as $main_data) {
                            if (!isset($main_data->errors) && is_object($main_data) && is_array($main_data->data->productById->variations)) {
                                foreach ($main_data->data->productById->variations as $variations) {
                                    if(is_object($variations)){
                                        foreach ($variations as $skus) {
                                            if(is_array($skus)){
                                                foreach ($skus as $single_sku) {
                                                    // Update stock information for variations
                                                    $availability = sanitize_text_field($single_sku->availability);
                                                    $availabilityRegional = sanitize_text_field($single_sku->availabilityRegional);
                                                    $stock = (int)$availabilityRegional + (int)$availability;
                                                    $stock_status = $stock > 0 ? 'instock' : 'outofstock';
                                                    $sku = isset($single_sku->sku) ? sanitize_text_field($single_sku->sku) : '';

                                                    // $logger->info('Single SKU:' . $sku, array('source' => 'new_wave-stock-logs'));

                                                    $variation_ids = $wpdb->get_results($wpdb->prepare("SELECT p.ID FROM $wpdb->postmeta AS pm JOIN $wpdb->posts AS p ON p.ID = pm.post_id WHERE p.post_status = 'publish' AND pm.meta_key='_sku' AND pm.meta_value='%s'", $sku));

                                                    if (!is_array($variation_ids) && isset($availability)) {
                                                        update_post_meta($variation_ids, 'availabilityLocal', $availability);
                                                    }

                                                    // $logger->info('Variation ids: ', array('source' => 'new_wave-stock-logs'));

                                                    foreach ($variation_ids as $variation_id) {
                                                        if ($variation_id->ID) {
                                                            update_post_meta($variation_id->ID, 'availabilityLocal', $availability);

                                                            $wpdb->query("UPDATE " . $wpdb->prefix . "postmeta SET meta_value = 'yes' WHERE post_id = " . $variation_id->ID . " AND meta_key='_manage_stock'");

                                                            $wpdb->query("UPDATE " . $wpdb->prefix . "postmeta SET meta_value = '" . $stock . "' WHERE post_id = " . $variation_id->ID . " AND meta_key='_stock'");
                                                            
                                                            $logger->info("current stock status for id ".$variation_id->ID.":". get_post_meta($variation_id->ID, "_stock_status", true), array('source' => 'new_wave-stock-logs'));
                                                            //Condition to check if the said variant is allowed to have back orders. If not then set new stock status. Otherwise ignore updating stock status.
                                                            if(get_post_meta($variation_id->ID, "_stock_status", true) != "onbackorder"){
                                                                $wpdb->query("UPDATE " . $wpdb->prefix . "postmeta SET meta_value = '" . $stock_status . "' WHERE post_id = " . $variation_id->ID . " AND meta_key='_stock_status'");
                                                            }
                                                            $logger->info("New stock status for id ".$variation_id->ID.":". get_post_meta($variation_id->ID, "_stock_status", true), array('source' => 'new_wave-stock-logs'));

                                                            $logger->info('Updated stock metas for Variation #' . $variation_id->ID . ' Stock status = ' . $stock_status . ' Availability local= ' . $availability . ' Manage stock = yes and stock = ' . $stock, array('source' => 'new_wave-stock-logs'));

                                                            $parent_id_result = $wpdb->get_results("select post_parent from " . $wpdb->prefix . "posts WHERE ID = " . $variation_id->ID);
                                                            $parent_id = $parent_id_result[0]->post_parent;

                                                            if ($stock) {
                                                                if (!in_array($parent_id, $parent_arrays)) {
                                                                    $parent_arrays[] = $parent_id;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }else{
                                foreach($main_data->errors as $err){
                                    $logger->error($err->message, array('source' => 'new_wave-stock-logs'));
                                }
                            }
                        }
                    }

                    // $logger->info('Parent #:' . print_r($parent_arrays), array('source' => 'new_wave-stock-logs'));

                    // Update parent products' stock status
                    foreach ($parent_arrays as $parent_array) {
                        $wpdb->query("UPDATE " . $wpdb->prefix . "postmeta SET meta_value = 'instock' WHERE post_id = " . $parent_array . " AND meta_key='_stock_status'");
                        wp_remove_object_terms($parent_array, 'outofstock', 'product_visibility'); // update term relationship
                    }

                    $logger->info('Stock update successful for page: ' . $page, array('source' => 'new_wave-stock-logs'));

                    $page++;
                } while ($q->found_posts);
            } catch (Exception $e) {
                // Handle exceptions if necessary
                $logger->info('Exception:' . $e->getMessage(), array('source' => 'new_wave-stock-logs'));
                $logger->info('Exception line:' . $e->getLine(), array('source' => 'new_wave-stock-logs'));
                $logger->info('Exception trace:' . $e->getTrace(), array('source' => 'new_wave-stock-logs'));
            }

            update_option('nw_stock_cron_running', 0); //mark the stock cron as completed

        }

        /**
         * Get stock balance from ASW API
         */

        public static function get_stock_balance($skus)
        {
            if (!$skus || empty($skus)) {
                return array();
            }

            $logger = wc_get_logger();

            try {
                $graphBatchQuery = '[';
                foreach ($skus as $sku) {
                    $graphBatchQuery .= '{"query":"{\\nproductById(productNumber: \\"' . $sku . '\\", language: \\"no\\") {\\nvariations {\\nskus {\\nsku\\navailability\\navailabilityRegional\\n}\\n}\\n}\\n}","variables":{}},';
                }
                $graphBatchQuery .= ']';
                $graphBatchQuery = substr($graphBatchQuery, 0, -2) . substr($graphBatchQuery, -1);

                return static::api_request($graphBatchQuery);
            } catch (Exception $e) {
                $logger->info('Exception:' . $e->getMessage(), array('source' => 'new_wave-stock-logs'));
                $logger->info('Exception line:' . $e->getLine(), array('source' => 'new_wave-stock-logs'));
                $logger->info('Exception trace:' . $e->getTrace(), array('source' => 'new_wave-stock-logs'));
            }

            return array();
        }

        /**
         * Make an API request
         */

        public static function api_request($query)
        {
            $api_url = sanitize_url(get_option('_nw_stock_api_url'));
            $api_token = sanitize_text_field(get_option('_nw_stock_api_token'));

            $logger = wc_get_logger();
            $logger->info('$query:' . print_r($query, true), array('source' => 'new_wave-stock-logs'));

            try {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $api_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => $query,
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer ' . $api_token,
                        'Content-Type: application/json'
                    ),
                ));
                $response = curl_exec($curl);

                // Get HTTP status code and content type from the cURL request
                $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

                // Check for non-200 HTTP status codes and log errors
                if ($httpcode !== 200) {
                    wc_get_logger()->debug("api_request failed with HTTP status code: " . $httpcode, ["source" => "new_wave-stock-logs"]);
                }

                return json_decode($response);
            } catch (Exception $e) {
                $logger->info('Exception:' . $e->getMessage(), array('source' => 'new_wave-stock-logs'));
                $logger->info('Exception line:' . $e->getLine(), array('source' => 'new_wave-stock-logs'));
                $logger->info('Exception trace:' . $e->getTrace(), array('source' => 'new_wave-stock-logs'));
            }
        }
    }
