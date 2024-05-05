<?php

// If called directly, abort
if (!defined('ABSPATH')) exit;


if (!function_exists('nw_get_tracking')):

    /**
     * Get tracking URL for shipment
     *
     * @param WC_Order|int $order
     * @param
     */

    function nw_get_tracking($order) {
        if (is_int($order))
            $order = wc_get_order($order);

        $code = $order->get_meta('_nw_tracking_id');
        $posten_url = sanitize_url(get_option('_nw_bring_posten_url'));

        if ($code && $posten_url) {
            return array(
                'code' => $code,
                'url' => $posten_url.$code,
            );
        }
        return array();
    }
endif;

/**
 * Syncs product balance (stock) from ASW
 */

class NW_Order_Tracking {
	/**
	 * Add hooks and filters
	 */

	public static function init() {
		add_action('init', __CLASS__.'::register_taxonomy');
		add_action('init', __CLASS__.'::register_order_status');

		// Add the custom status to the status dropdown
		add_filter('wc_order_statuses', __CLASS__.'::add_status_to_dropdown');

		// Add the custom order status to reports
		add_filter('woocommerce_reports_get_order_report_data_args', __CLASS__.'::add_report_support');
		add_action('nw_order_uploaded_to_asw', __CLASS__.'::add_order_awaiting_shipment', 1, 1);

		// Hook in cron jobs
		add_action('wp_loaded', __CLASS__.'::add_cron_jobs');
		add_action('nw_check_orders_awaiting_shipment', __CLASS__.'::check_awaiting_shipment');
		add_action('nw_check_orders_awaiting_delivery', __CLASS__.'::check_awaiting_delivery');
		add_action('nw_uninstall', __CLASS__.'::remove_cron_jobs');

		// Add support for custom queries of wc_orders
		add_filter('woocommerce_order_data_store_cpt_get_orders_query', __CLASS__.'::handle_custom_query_var', 10, 2);

		// Display tracking URL in WP admin
		add_action('woocommerce_admin_order_data_after_order_details', __CLASS__.'::display_tracking_url');

		add_action('admin_head', __CLASS__.'::enqueue_assets');

		add_action('woocommerce_order_number', __CLASS__.'::change_order_prefix', 99, 2);

		if(isset($_GET['run_shipment_cron']) && $_GET['run_shipment_cron'] == 1) {
			add_action('wp_loaded', __CLASS__ . '::check_awaiting_shipment');
		}
	}

	/**
	 * Change prefix of order to AUCL
	 *
	 * @param string $id
	 * @param WC_Order $order
	 * @return string
	 */

	public static function change_order_prefix($id, $order) {
        $prefix = sanitize_text_field(get_option('_nw_bring_order_prefix'));
		return $prefix.$order->get_id();
	}

	/**
	 * Add cron jobs to run every 30 minutes
	 */

	public static function add_cron_jobs() {
		if (!wp_next_scheduled('nw_check_orders_awaiting_shipment')) {
			wp_schedule_event(strtotime('+5 min'), 'hourly', 'nw_check_orders_awaiting_shipment');
		}

		if (!wp_next_scheduled('nw_check_orders_awaiting_delivery')) {
			wp_schedule_event(strtotime('+35 min'), 'hourly', 'nw_check_orders_awaiting_delivery');
		}
	}

	/**
	 * Remove cron jobs on plugin deactivation
	 */

	public static function remove_cron_jobs() {
		wp_clear_scheduled_hook('nw_check_orders_awaiting_shipment');
		wp_clear_scheduled_hook('nw_check_orders_awaiting_delivery');
	}

	/**
	 * Register taxonomy used for searching for orders awaiting shipment and delivery
	 */

	public static function register_taxonomy() {
		register_taxonomy(
			'_nw_order_awaiting_shipment',
			'shop_order',
			array(
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => false,
			)
		);

		register_taxonomy_for_object_type('_nw_order_awaiting_shipment', 'shop_order');

		register_taxonomy(
			'_nw_order_awaiting_delivery',
			'shop_order',
			array(
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => false,
			)
		);

		register_taxonomy_for_object_type('_nw_order_awaiting_delivery', 'shop_order');
	}

	/**
	 * Extends arguments passed to wc_get_orders() to allow to search for orders
	 * based on custom taxonomies
	 *
	 * @param array $query Array of query args
	 * @param array $query Array of query vars
	 * @return array
	 */

	public static function handle_custom_query_var($query, $query_vars) {
		if (isset($query_vars['awaiting_shipment'])) {
			$query['tax_query'][] = array(
				'taxonomy' => '_nw_order_awaiting_shipment',
				'field'    => 'slug',
				'terms'    => array('true'),
			);
		}

		if (isset($query_vars['awaiting_delivery'])) {
			$query['tax_query'][] = array(
				'taxonomy' => '_nw_order_awaiting_delivery',
				'field'    => 'slug',
				'terms'    => array('true'),
			);
		}
		return $query;
	}

	/**
	 * Register 'delivered' as a custom post status for wc_orders
	 */

	public static function register_order_status() {
        register_post_status('wc-delivered', array(
        'label'                     => _x('Delivered', 'Order status', 'newwave'),
        'label_count'               => _n_noop('Delivered <span class="count">(%s)</span>', 'Delivered <span class="count">(%s)</span>', 'newwave'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        ));
	}

	/**
	 * Add 'delivered' to the dropdown of WC Order statuses
	 *
	 * @param string[] $statuses
	 * @return string[]
	 */

	public static function add_status_to_dropdown($statuses) {
		$statuses['wc-delivered'] = _x('Delivered', 'Order status', 'newwave');
		return $statuses;
	}

	/**
	 * Include the 'delivered' order status in the reports,
	 * so that the statistics reflect that
	 *
	 * @param array $args
	 * @return array
	 */

	public static function add_report_support($args) {
		if (isset($args['order_status'])) {
			if (is_array($args['order_status']) && count($args['order_status'])) {
				array_push($args['order_status'], 'delivered');
			}
		}

		return $args;
	}

	/**
	 * Enqueue styling to change color of 'delivered' status badge,
	 * and box to display tracking url
	 */

	public static function enqueue_assets() {
		$screen = get_current_screen();
		if ('shop_order' == $screen->post_type) {
			wp_enqueue_style(
				'nw_admin_order_tracking',
				NW_PLUGIN_URL .'assets/css/nw-admin-order-tracking.css'
			);
		}
	}

	public static function display_tracking_url($order) {
        $posten_url = sanitize_url(get_option('_nw_bring_posten_url'));
		$tracking_id = $order->get_meta('_nw_tracking_id', true);

		if (!$tracking_id) {
			$msg = sprintf('<span class="tracking-url">%s</span>',
				__('No tracking URL', 'newwave'));
		}
		else {
			$msg = sprintf('<a class="tracking-url" target="_blank" href="%s">%s</a>',
				$posten_url.$tracking_id,
				$tracking_id
			);
		}

		printf('<p class="form-field form-field-wide tracking">
			<label>%s</label>%s</p>',
			__('Tracking:', 'newwave'), $msg);
	}

	/**
	 * Tag orders with the _nw_order_awaiting_shipment taxonomy,
	 * so we now what orders to look for on our next
	 *
	 * @param WC_Order $order Order to lookup a tracking URL for later
	 */

	public static function add_order_awaiting_shipment($order_id) {
		if (!is_int($order_id))
			$order_id = $order_id->get_id();

		wp_set_object_terms($order_id, 'true', '_nw_order_awaiting_shipment', false);
	}

	/**
	 * Check with BRING if orders have been shipped.
	 * If so, set status to delivered and remove taxonomies
	 */

	public static function check_awaiting_shipment() {
		ini_set('display_errors', 1);

        $prefix = sanitize_text_field(get_option('_nw_bring_order_prefix'));
		
        // Get all orders waiting for a tracking url
		global $wpdb;
		$orders = $wpdb->get_results( "SELECT {$wpdb->prefix}posts.ID FROM {$wpdb->prefix}posts join {$wpdb->prefix}term_relationships ON {$wpdb->prefix}posts.ID = {$wpdb->prefix}term_relationships.object_id JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}term_relationships.term_taxonomy_id = {$wpdb->prefix}term_taxonomy.term_taxonomy_id WHERE {$wpdb->prefix}posts.post_type = 'shop_order' AND {$wpdb->prefix}term_taxonomy.taxonomy='_nw_order_awaiting_shipment' ORDER BY {$wpdb->prefix}posts.ID DESC LIMIT 500");

		wc_get_logger()->debug("Total orders: " .count($orders), ["source" => "bring_nw_logs_awaiting"]);

		foreach ($orders as $order) {
			$order = new WC_Order($order->ID); print_r($order->ID);
			$response = static::get_shipment_status($order->get_order_number()); print_r($response); //exit;
			if ($response['status']) {
				$order->update_meta_data('_nw_tracking_id', $response['tracking_id']);
				wc_get_logger()->debug("#" .$order->get_id() ." Tracking id: " . $response['tracking_id'], ["source" => "bring_nw_logs_awaiting"]);
				$order->set_status('completed');
				$order->save();

				wp_delete_object_term_relationships($order->get_id(), '_nw_order_awaiting_shipment');
				wp_set_object_terms($order->get_id(), 'true', '_nw_order_awaiting_delivery', false);
				wc_get_logger()->debug("#" .$order->get_id() ."order status changed to Completed", ["source" => "bring_nw_logs_awaiting"]);
			}
			else {
				$response = static::get_shipment_status($prefix.$order->get_order_number()); print_r($response); //exit;
				if ($response['status']) {
					$order->update_meta_data('_nw_tracking_id', $response['tracking_id']);
					wc_get_logger()->debug("#" .$order->get_id() ." Tracking id: " . $response['tracking_id'], ["source" => "bring_nw_logs_awaiting"]);
					$order->set_status('completed');
					$order->save();

					wp_delete_object_term_relationships($order->get_id(), '_nw_order_awaiting_shipment');
					wp_set_object_terms($order->get_id(), 'true', '_nw_order_awaiting_delivery', false);
					wc_get_logger()->debug("#" .$order->get_id() ."order status changed to Completed", ["source" => "bring_nw_logs_awaiting"]);
				}
			}
		}
		exit;
	}

	/**
	 * Check with BRING if orders have been delivered.
	 * If so, set status to delivered and remove taxonomies
	 */

	public static function check_awaiting_delivery() {
		wc_get_logger()->debug("check_awaiting_delivery", ["source" => "bring_nw_logs_awaiting"]);
		$orders = wc_get_orders(array(
			'posts_per_page' => -1,
			'awaiting_delivery' => true,
		));

		wc_get_logger()->debug("Total orders: " .count($orders), ["source" => "bring_nw_logs_awaiting"]);

		foreach ($orders as $order) {
			$response = static::get_shipment_status($order->get_meta('_nw_tracking_id', true));
			if ('DELIVERED' == $response['status']) {
				$order->set_status('delivered');
				$order->save();
				wp_delete_object_term_relationships($order->get_id(), '_nw_order_awaiting_delivery');

				wc_get_logger()->debug("#" .$order->get_id() ."order status changed to Delivered", ["source" => "bring_nw_logs_awaiting"]);
			}
		}
	}

	/**
	 * Get package status
	 *
	 * @param $order_ref Order reference to lookup, e.g. 'CRAFT2019'
	 * @return array
	 */

	public static function get_shipment_status($order_ref) {
		try {
			wc_get_logger()->debug("get_shipment_status", ["source" => "bring_nw_logs_awaiting"]);
			wc_get_logger()->debug("Order ref = " .$order_ref, ["source" => "bring_nw_logs_awaiting"]);
            $bring_api_url = sanitize_url(get_option('_nw_bring_api_url'));
			$data = stream_context_create(array(
				'http' => array('method' => 'GET')
			));
			$response = json_decode(file_get_contents($bring_api_url.$order_ref, false, $data), true);
			
			wc_get_logger()->debug("response: " . json_encode($response, JSON_PRETTY_PRINT), ["source" => "bring_nw_logs_awaiting"]);

			$content = $response['consignmentSet'][0];

			if (array_key_exists('error', $content)) {
				wc_get_logger()->debug("No shipment found", ["source" => "bring_nw_logs_awaiting"]);
				throw new Exception('No shipment found');
			}

			if ('PRE_NOTIFIED' == $content['packageSet'][0]['eventSet'][0]['status']) {
				wc_get_logger()->debug("Package notified, not sent", ["source" => "bring_nw_logs_awaiting"]);
				throw new Exception('Package notified, not sent');
			}

			return array(
				'status' => $content['packageSet'][0]['eventSet'][0]['status'],
				'tracking_id' => $content['consignmentId']
			);
		}
		catch (Exception $e) {}

		return array('status' => false);
	}
}

NW_Order_Tracking::init();

?>
