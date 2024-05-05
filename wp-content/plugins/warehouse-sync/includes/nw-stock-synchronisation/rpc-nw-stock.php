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
                return;
            }

            // Add hooks for initialization
            add_action('nw_install', __CLASS__ . '::add_cron_job');
            add_filter('cron_schedules', __CLASS__ . '::add_cron_interval', 99);
            add_action('nw_update_stock_balance', __CLASS__ . '::update_stock_balance');
            add_action('nw_uninstall', __CLASS__ . '::remove_cron_job');

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
                    30 - ((int) date('i') % 30) + $fixed_offset
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
            $interval =  $interval ? $interval : 30;
            $events['nw_30_min'] = array(
                'interval' => $interval * 60,
                'display' => __('Every ' . $interval . ' minutes', 'newwave'),
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
            $page = 1;
            global $wpdb;
            update_option('nw_stock_cron_running', 1);//this is added to identify whether the stock cron is in process;

            $logger = wc_get_logger();

            $logger->info('NW Stock update started at: ' . date('d-m-Y H:i:s'), array('source' => 'new_wave-stock-logs'));

            do {
                $q = new WP_Query(array(
                    'post_type' => 'product_variation',
                    'posts_per_page' => 500,
                    'paged' => $page,
                ));

                $logger->info(wc_print_r('Total posts count:' . $q->found_posts, true), array('source' => 'new_wave-stock-logs'));

                $variations = array();
                $skus = array();

                foreach ($q->posts as $post) {
                    $var = new WC_Product_Variation($post->ID);
                    if ($var) {
                        $variations[$var->get_sku()] = $var;
                        array_push($skus, $var->get_sku());
                    }
                }

                // $logger->info('skus:' . print_r($skus, true), array('source' => 'new_wave-stock-logs'));

                $updates = static::get_stock_balance($skus);

                $parent_arrays = array();

                if (is_array($updates) && count($updates)) {
                    foreach ($updates as $update) {
                        $stock_status = "";
                        $update['sku'] = sanitize_text_field($update['sku']);
                        $update['quantity'] = sanitize_text_field($update['quantity']);
                        // Make sure all are set to manage stock
                        if (isset($variations[$update['sku']]) && is_object($variations[$update['sku']])) {
                            $stock_status = $update['quantity'] > 0 ? 'instock' : 'outofstock';
                            $variations[$update['sku']]->set_manage_stock(true);
                            $variations[$update['sku']]->set_stock_quantity(intval($update['quantity']));

                            //Condition to check if the said variant is allowed to have back orders. If not then set new stock status. Otherwise ignore updating stock status.
                            if(get_post_meta($variations[$update['sku']]->get_id(), "_stock_status", true) != "onbackorder"){
                                $variations[$update['sku']]->set_stock_status($stock_status);
                            }
                            $variations[$update['sku']]->save();

                            $logger->info('Updated stock metas for SKU #' . $update['sku']   . ' Manage stock = yes and stock quantity = ' . $update['quantity'], array('source' => 'new_wave-stock-logs'));
                        }

                        //If any of the product variations has quantity greater than 0, the parent stock status will be changed to instock
                        if ($update['quantity'] && isset($variations[$update['sku']])) {
                            $parent_id = $variations[$update['sku']]->get_parent_id();
                            if (!in_array($parent_id, $parent_arrays)) {
                                $parent_arrays[] = $parent_id;
                            }
                        }
                    }
                }

                $logger->info('Parent #:' . print_r($parent_arrays), array('source' => 'new_wave-stock-logs'));

                foreach ($parent_arrays as $parent_array) {
                    $wpdb->query("UPDATE " . $wpdb->prefix . "postmeta SET meta_value = 'instock' WHERE post_id = " . $parent_array . " AND meta_key='_stock_status'");
                    wp_remove_object_terms($parent_array, 'outofstock', 'product_visibility'); // update term relationship
                }

                $logger->info('Stock update successful for page: ' . $page, array('source' => 'new_wave-stock-logs'));

                $page++;
            } while ($q->found_posts);

            update_option('nw_stock_cron_running', 0); //mark the stock cron as completed
        }

        /**
         * Get stock balance from ASW
         *
         * @param string[] $skus List of SKUs to check for balance
         * @return array List of associative arrays with balance per SKU
         */

        private static function get_stock_balance($skus)
        {
            $logger = wc_get_logger();

            if (!$skus) {
                return array();
            }

            $api_key = sanitize_text_field(get_option('_nw_stock_api_token'));
            $api_url = sanitize_url(get_option('_nw_stock_api_url')) . 'warehousebalance/';

            try {
                $data = stream_context_create(array(
                    'http' => array(
                        'method' => 'POST',
                        'header' => "Content-Type: application/json\r\n" . 'X-Custom-API-Key: ' . $api_key,
                        'content' => json_encode($skus)
                    )
                ));
                $response = file_get_contents($api_url, false, $data);

                if ($response === false) {
                    // Handle request failure
                    $logger->error('Failed to fetch data from the API.', array('source' => 'new_wave-stock-logs'));
                    return array(); // or throw an exception or take other actions as needed
                }

                return json_decode($response, true);
            } catch (Exception $e) {
                $logger->info('Exception:' . $e->getMessage(), array('source' => 'new_wave-stock-logs'));
                $logger->info('Exception line:' . $e->getLine(), array('source' => 'new_wave-stock-logs'));
                $logger->info('Exception trace:' . $e->getTrace(), array('source' => 'new_wave-stock-logs'));
            }

            return array();
        }

        /*
        public static function update_stock_balance_fast() 
        {
            $page = 1;
            start_timer();
            do {
                $q = new WP_Query(array(
                    'post_type' => 'product_variation',
                    'posts_per_page' => 500,
                    'paged' => $page,
                ));
                $page++;

                $variations = array();
                $sku_map_id = array();
                foreach ($q->posts as $p) {
                    $sku = get_post_meta($p->ID, '_sku', true);
                    if ($sku) {
                        $sku_map_id[$sku] = $p->ID;
                    }
                }

                $balances = static::get_stock_balance(array_keys($sku_map_id));

                foreach ($balances as $balance) {
                    $id = $sku_map_id[$balance['sku']];
                    $set = 'instock';
                    if ($balance['quantity'])
                        $set = 'outofstock';
                    update_post_meta($id, '_stock_status', $set);
                    update_post_meta($id, '_stock', $balance['quantity']);
                }

            } while ($q->found_posts == 500);
            stop_timer();
        }
	    */
    }

