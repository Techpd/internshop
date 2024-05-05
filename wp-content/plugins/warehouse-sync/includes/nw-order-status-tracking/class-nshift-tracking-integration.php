<?php

require __DIR__ . '/nshift-php-client/vendor/autoload.php';

use Crakter\nShift\Entity\Connect;
use Crakter\nShift\Entity\Tracking;
use Crakter\nShift\Clients\Authorization;
use Crakter\nShift\Clients\Tracking\TrackingByOrderNumber;
use Crakter\nShift\Clients\Tracking\TrackingByBarcode;
use Crakter\nShift\Clients\Tracking\TrackingByUuid;

class WC_nShift_Tracking_Integration extends WC_Integration
{
    /**
     * Init and hook in the integration.
     */

    public function __construct()
    {
        global $woocommerce;
        $this->id                 = 'nshift-tracking';
        $this->method_title       = __('nShift tracking plugin');
        $this->method_description = __('nShift Plugin Integration to show you how easy it is to extend WooCommerce.');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Actions.
        add_action('woocommerce_update_options_integration_' .  $this->id, [&$this, 'process_admin_options']);
        add_filter('wc_order_statuses', __CLASS__ . '::add_status_to_dropdown');
        add_action('init', __CLASS__ . '::register_order_status');
        add_filter('woocommerce_order_is_paid_statuses', __CLASS__ . '::register_custom_statuses_as_paid', 99, 1);

        add_action('wp_loaded', __CLASS__ . '::add_cron_jobs');
        add_action('nshift_check_orders_awaiting_shipment', [&$this, 'check_awaiting_shipment']);
        add_action('nshift_check_orders_awaiting_delivery', [&$this, 'check_awaiting_delivery']);
        add_action('nw_uninstall', __CLASS__ . '::remove_cron_jobs');

        add_action('woocommerce_admin_order_data_after_order_details', __CLASS__ . '::display_tracking_url');
        add_filter('manage_edit-shop_order_columns', [$this, 'addColumns']);
        add_filter('manage_edit-shop_order_sortable_columns', [$this, 'sortNewColumns']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'addColumnsValue'], 2);
        add_filter('bulk_actions-edit-shop_order', [&$this, 'register_my_bulk_actions']);
        add_filter('handle_bulk_actions-edit-shop_order', [&$this, 'my_bulk_action_handler'], 10, 3);
        add_action('admin_notices', [&$this, 'my_bulk_action_admin_notice']);

        add_filter('cron_schedules', [&$this, 'my_add_everysixthhour']);
    }

    function my_add_everysixthhour( $schedules ) {
        // add a 'sixthhour' schedule to the existing set
        $schedules['sixthhour'] = array(
            'interval' => 21600,
            'display' => __('Every sixth hour')
        );
        return $schedules;
    }

    public function register_my_bulk_actions($bulk_actions)
    {
        $bulk_actions['wc-senttoprintshop'] = _x('Endre status til Sendt til trykkeri', 'Order status', 'nshift');
        $bulk_actions['wc-printshopto'] = _x('Endre status til Levert til trykkeri', 'Order status', 'nshift');
        $bulk_actions['wc-printshopfrom'] = _x('Endre status til Levert ut fra trykkeri', 'Order status', 'nshift');
        $bulk_actions['wc-shipped'] = _x('Endre status til Sendt til kunde', 'Order status', 'nshift');
        $bulk_actions['wc-atpostaloffice'] = _x('Endre status til Levert til postkontor', 'Order status', 'nshift');
        $bulk_actions['wc-delivered'] = _x('Endre status til Levert til kunde', 'Order status', 'nshift');
        $bulk_actions['wc-returned'] = _x('Endre status til Returnert til avsender', 'Order status', 'nshift');
        $bulk_actions['wc-senttothirdparty'] = _x('Endre status til Sendt til tredjepart', 'Order status', 'nshift');

        return $bulk_actions;
    }

    public function my_bulk_action_admin_notice()
    {
        if (!empty($_REQUEST['bulk_draft_posts'])) {
            $drafts_count = intval($_REQUEST['bulk_draft_posts']);

            printf(
                '<div id="message" class="updated fade">' .
                    _n('%s post moved to drafts.', '%s posts moved to drafts.', $drafts_count, 'domain')
                    . '</div>',
                $drafts_count
            );
        }
    }

    public function my_bulk_action_handler($redirect_to, $action, $post_ids)
    {
        if (!in_array($action, [
            'wc-senttoprintshop',
            'wc-printshopto',
            'wc-printshopfrom',
            'wc-shipped',
            'wc-atpostaloffice',
            'wc-delivered',
            'wc-returned',
            'wc-senttothirdparty',
        ])) {
            return $redirect_to;
        }

        foreach ($post_ids as $post_id) {
            //commenting this because i am not sure why this is done for internshop
            // wp_update_post([
            //     'ID'          => $post_id,
            //     'post_status' => $action,
            // ]);

            $order = new WC_Order($post_id);
            $order->update_status($action);

            //commenting this because i am not sure why this is done for internshop
            // $order->update_status('building', __('Order status changed by bulk edit:', 'woocommerce'), true);
        }

        $redirect_to = add_query_arg('bulk_draft_posts', count($post_ids), $redirect_to);

        return $redirect_to;
    }

    public function addColumnsValue($column)
    {
        global $post;
        $data = get_post_meta($post->ID);

        if ($column == 'nshift-tracking') {
            $msg = self::getTrackingHtml(
                isset($data['_nshift_tracking'][0]) ? $data['_nshift_tracking'][0] : '',
                isset($data['_nshift_tracking_id'][0]) ? $data['_nshift_tracking_id'][0] : ''
            );
            echo $msg;
        }
        if ($column == 'nshift-carrier-name') {
            echo isset($data['_nshift_carrier_name'][0]) ? $data['_nshift_carrier_name'][0] : 'No carrier name';
        }
        if ($column == 'nshift-product-name') {
            echo isset($data['_nshift_product_name'][0]) ? $data['_nshift_product_name'][0] : 'No product name';
        }
    }

    public function addColumns($columns)
    {
        $new_columns = (is_array($columns) ? $columns : []);
        unset($new_columns['order_actions']);
        $new_columns['nshift-tracking'] = _x('Tracking info', 'nShift', 'nshift');
        $new_columns['nshift-carrier-name'] = _x('Carrier Name', 'nShift', 'nshift');
        $new_columns['nshift-product-name'] = _x('Product Name', 'nShift', 'nshift');
        // $new_columns['order_actions'] = $columns['order_actions'];
        return $new_columns;
    }

    public function sortNewColumns($columns)
    {
        $custom = [
            'nshift-tracking' => '_nshift_tracking_id',
            'nshift-carrier-name' => '_nshift_carrier_name',
            'nshift-product-name' => '_nshift_product_name',
        ];

        return wp_parse_args($custom, $columns);
    }

    public function get_access_token()
    {
        $connect = (new Connect())
            ->setClient_id($this->settings['nshift_client_id'])
            ->setClient_secret(str_replace("&amp;", "&", $this->settings['nshift_client_secret']));

        // Try to send the booking - or catch the error.
        try {
            $authorization = (new Authorization())->setApiEntity($connect)->send();
        } catch (\Exception $e) {
            wc_get_logger()->debug("Error message from nShift integration: " . $e->getMessage(), ['source' => 'nshift_logs']);
        }

        return $authorization->getAccessToken();
    }

    public static function add_cron_jobs()
    {
        if (!wp_next_scheduled('nshift_check_orders_awaiting_shipment')) {
            wp_schedule_event(strtotime('+5 min'), 'hourly', 'nshift_check_orders_awaiting_shipment');
        }

        if (!wp_next_scheduled('nshift_check_orders_awaiting_delivery')) {
            wp_schedule_event(strtotime('+30 min'), 'sixthhour', 'nshift_check_orders_awaiting_delivery');
        }
    }

    /**
     * Remove cron jobs on plugin deactivation
     */

    public static function remove_cron_jobs()
    {
        wp_clear_scheduled_hook('nshift_check_orders_awaiting_shipment');
        wp_clear_scheduled_hook('nshift_check_orders_awaiting_delivery');
    }

    public static function register_order_status()
    {
        register_post_status('wc-delivered', [
            'label'                     => _x('Levert til kunde', 'Order status', 'nshift'),
            'label_count'               => _n_noop('Levert <span class="count">(%s)</span>', 'Levert <span class="count">(%s)</span>', 'nshift'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ]);
        register_post_status('wc-returned', [
            'label'                     => _x('Returnert til avsender', 'Order status', 'nshift'),
            'label_count'               => _n_noop('Returnert <span class="count">(%s)</span>', 'Returnert <span class="count">(%s)</span>', 'nshift'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
          ]);
        register_post_status('wc-printshopto', [
            'label'                     => _x('Levert til trykkeri', 'Order status', 'nshift'),
            'label_count'               => _n_noop('Levert til trykkeri <span class="count">(%s)</span>', 'Levert til trykkeri <span class="count">(%s)</span>', 'nshift'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ]);

        register_post_status('wc-printshopfrom', [
            'label'                     => _x('Levert ut fra trykkeri', 'Order status', 'nshift'),
            'label_count'               => _n_noop('Levert ut fra trykkeri <span class="count">(%s)</span>', 'Levert ut fra trykkeri <span class="count">(%s)</span>', 'nshift'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ]);

        register_post_status('wc-shipped', [
            'label'                     => _x('Sendt til kunde', 'Order status', 'nshift'),
            'label_count'               => _n_noop('Sendt <span class="count">(%s)</span>', 'Sendt <span class="count">(%s)</span>', 'nshift'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ]);

        register_post_status('wc-senttoprintshop', [
            'label'                     => _x('Sendt til trykkeri', 'Order status', 'nshift'),
            'label_count'               => _n_noop('Sendt til trykkeri <span class="count">(%s)</span>', 'Sendt til trykkeri <span class="count">(%s)</span>', 'nshift'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ]);

        register_post_status('wc-atpostaloffice', [
            'label'                     => _x('Levert til postkontor', 'Order status', 'nshift'),
            'label_count'               => _n_noop('Levert til postkontor <span class="count">(%s)</span>', 'Levert til postkontor <span class="count">(%s)</span>', 'nshift'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ]);

        register_post_status('wc-senttothirdparty', [
            'label'                     => _x('Sendt til tredjepart', 'Order status', 'nshift'),
            'label_count'               => _n_noop('Sendt til tredjepart <span class="count">(%s)</span>', 'Sendt til tredjepart <span class="count">(%s)</span>', 'nshift'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ]);
    }

    public static function register_custom_statuses_as_paid($statuses)
    {
        $statuses[] = 'delivered';
        $statuses[] = 'returned';
        $statuses[] = 'printshopto';
        $statuses[] = 'printshopfrom';
        $statuses[] = 'shipped';
        $statuses[] = 'senttoprintshop';
        $statuses[] = 'atpostaloffice';
        $statuses[] = 'senttothirdparty';

        return $statuses;
    }

    public static function add_status_to_dropdown($statuses)
    {
        $statuses['wc-senttoprintshop'] = _x('Sendt til trykkeri', 'Order status', 'nshift');
        $statuses['wc-printshopto'] = _x('Levert til trykkeri', 'Order status', 'nshift');
        $statuses['wc-printshopfrom'] = _x('Levert ut fra trykkeri', 'Order status', 'nshift');
        $statuses['wc-shipped'] = _x('Sendt til kunde', 'Order status', 'nshift');
        $statuses['wc-atpostaloffice'] = _x('Levert til postkontor', 'Order status', 'nshift');
        $statuses['wc-delivered'] = _x('Levert til kunde', 'Order status', 'nshift');
        $statuses['wc-returned'] = _x('Returnert til avsender', 'Order status', 'nshift');
        $statuses['wc-senttothirdparty'] = _x('Sendt til tredjepart', 'Order status', 'nshift');

        return $statuses;
    }

    public static function getTrackingHtml($tracking_url, $tracking_id)
    {
        if (!$tracking_id) {
            $msg = sprintf(
                '<span class="tracking-url">%s</span>',
                __('No tracking URL', 'nshift')
            );
        } else {
            $msg = sprintf(
                '<a class="tracking-url" target="_blank" href="%s">%s</a>',
                $tracking_url,
                $tracking_id
            );
        }

        return $msg;
    }

    public static function display_tracking_url($order)
    {
        printf(
            '<div style="display: none">%s</div>
            <p class="form-field form-field-wide tracking">
                <label>%s</label>%s
            </p>',
            $order->get_meta('_nshift_uuid', true),
            __('Tracking:', 'nshift'),
            self::getTrackingHtml(
                $order->get_meta('_nshift_tracking', true),
                $order->get_meta('_nshift_tracking_id', true)
            )
        );
    }

    /**
     * Check with BRING if orders have been shipped.
     * If so, set status to delivered and remove taxonomies
     */

    public function check_awaiting_shipment()
    {
        wc_get_logger()->debug("check_awaiting_shipment", ["source" => "nshift_nw_logs_awaiting"]);

        $token = $this->get_access_token();
        $paged = 1;
        $args = [
            'status' => ['wc-completed', 'wc-returned'],
            'limit' => 100,
        ];

        while ($paged > 0) {
            $args['paged'] = $paged;
            wc_get_logger()->debug("check_awaiting_shipment > checking args:" . json_encode($args, JSON_PRETTY_PRINT), ["source" => "nshift_nw_logs_awaiting"]);

            $orders = wc_get_orders($args);
            $paged++;

            wc_get_logger()->debug("check_awaiting_shipment > orders count:" . count($orders), ["source" => "nshift_nw_logs_awaiting"]);

            if (count($orders) === 0) {
                $paged = 0;
                break;
            }

            foreach ($orders as $order) {
                wc_get_logger()->debug("check_awaiting_shipment > order id:" . $order->get_id(), ["source" => "nshift_nw_logs_awaiting"]);
                $dates = self::getStartEndDates($order->get_date_created());
                wc_get_logger()->debug("check_awaiting_shipment > order date:" . date("d-m-Y H:i:s", strtotime($order->get_date_created())) . "Start: " . $dates['start'] . "End: " . $dates['end'], ["source" => "nshift_nw_logs_awaiting"]);
                $startDate = new \DateTime($dates['start']);
                $endDate = (new \DateTime('now'))->modify('-2 months');
                if ($startDate < $endDate) {
                    continue;
                }
                $query = (new Tracking())
                    ->setQuery($this->getOrderNumber($order))
                    ->setStartDate($dates['start'])
                    ->setEndDate($dates['end'])
                    ->setPageSize(20)
                    ->setPageIndex(0)
                    ->setInstallationTags(explode(';', $this->settings['nshift_installation_tags']))
                    ->setActorTags(explode(';', $this->settings['nshift_actor_tags']))
                    ->setCarrierTags([]);

                try {
                    $tracking = (new TrackingByOrderNumber())->setToken($token)->setApiEntity($query)->send();
                    $trackingResult = $tracking->toArray();
                } catch (\Exception $e) {
                    wc_get_logger()->debug("Error message from nShift integration: " . $e->getMessage(), ["source" => "nshift_logs"]);
                }

                wc_get_logger()->debug("\$trackingResult :" . print_r($trackingResult, true), ["source" => "nshift_nw_logs_awaiting"]);

                if (empty($trackingResult)) {
                    wc_get_logger()->debug("\$trackingResult empty continue:", ["source" => "nshift_nw_logs_awaiting"]);
                    continue;
                }

                switch ($trackingResult[0]['carrierName']) {
                    case 'Bring':
                        wc_get_logger()->debug("switch \$trackingResult[0]['carrierName']:" . $trackingResult[0]['carrierName'], ["source" => "nshift_nw_logs_awaiting"]);
                        $url = 'https://sporing.posten.no/sporing/%s';
                        break;
                }

                if (isset($trackingResult[0]['number'])) {
                    $number = $trackingResult[0]['number'];
                } else {
                    $number = $trackingResult[0]['lines'][0]['packages'][0]['number'];
                }

                wc_get_logger()->debug("\$number:" . $number, ["source" => "nshift_nw_logs_awaiting"]);

                $printedDate = $trackingResult[0]['events'][0]['date'];
                $trackingUrl = sprintf($url, $number);
                $order->update_meta_data('_nshift_tracking', $trackingUrl);
                $order->update_meta_data('_nshift_tracking_id', $number);
                $order->update_meta_data('_nshift_carrier_name', $trackingResult[0]['carrierName']);
                $order->update_meta_data('_nshift_product_name', $trackingResult[0]['productName']);
                $order->update_meta_data('_nshift_uuid', $trackingResult[0]['uuid']);
                $order->update_meta_data('_nshift_printed_date', $printedDate);
                $order->update_meta_data('_kss_tracking_url', $trackingUrl);
                $order->update_meta_data('_kss_tracking_id', $number);
                $order->update_status('wc-shipped');

                wc_get_logger()->debug("\$order meta updated, status:wc-shipped", ["source" => "nshift_nw_logs_awaiting"]);
            }
            unset($orders);
        }
    }

    public static function getStartEndDates($date)
    {
        return [
            'start' => (new \DateTime($date))->modify('-1 week')->format('Y-m-d\Th:i:s+00'),
            'end' => (new \DateTime($date))->modify('+3 weeks')->format('Y-m-d\Th:i:s+00'),
        ];
    }

    public function getOrderNumber($order)
    {
        // Make sure it returns INTSH with the order id after.
        if ($this->settings['nshift_prefix_checkbox'] !== 'yes') {
            return $order->get_id();
        }

        return $this->settings['nshift_prefix'] . str_replace($this->settings['nshift_prefix'], '', $order->get_id());
    }

    /**
     * Check with BRING if orders have been delivered.
     * If so, set status to delivered and remove taxonomies
     */

    public function check_awaiting_delivery()
    {
        $token = $this->get_access_token();

        $paged = 1;
        $args = [
            'status' => [
                'wc-completed', 'wc-printshopto', 'wc-printshopfrom', 'wc-delivered', 'wc-returned', 'wc-shipped', 'wc-senttoprintshop', 'wc-atpostaloffice'
            ],
            'limit' => 100,
            // 'date_created'  => strtotime('today') . '...' . strtotime('-2 months'), //this is specifically done for craft
        ];

        while ($paged > 0) {
            $args['paged'] = $paged;
            $orders = wc_get_orders($args);
            $paged++;

            if (count($orders) === 0) {
                $paged = 0;
                break;
            }

            wc_get_logger()->debug("check_awaiting_delivery > orders count:" . count($orders), ["source" => "nshift_nw_logs_delivery"]);

            foreach ($orders as $order) {
                wc_get_logger()->debug("check_awaiting_delivery > orders ID:" . $order->get_id() . ". Status: " . $order->get_status(), ["source" => "nshift_nw_logs_delivery"]);
                $orderDate = (new \DateTime($order->get_date_created()));
                $now = (new \DateTime('now'));
                if ($orderDate->diff($now)->days > 90) {
                    continue;
                }
                $query = $order->get_meta('_nshift_uuid', true);
                $order_allowed_shipping = $order->get_meta('_nw_allowed_shipping', true);

                if (!empty($order_allowed_shipping)) {
                    $allowed_shipping = ['club', 'club-customer', 'vendor', 'vendor-club', 'vendor-customer', 'vendor-club-customer'];
                    if (in_array($order_allowed_shipping, $allowed_shipping)) {
                        $order->update_status('wc-completed');
                        $order->save();
                        $order->update_status('wc-senttothirdparty');
                        $order->save();
                        continue;
                    }
                }

                if (!isset($query) || $query === '') {
                    continue;
                }

                wc_get_logger()->debug('uuid: ' . $query, ["source" => "nshift_logs"]);

                try {
                    $tracking = (new TrackingByUuid())->setToken($token)->setUuid($query)->send();
                    $trackingResult = $tracking->toArray();
                    wc_get_logger()->debug("\$trackingResult :" . print_r($trackingResult, true), ["source" => "nshift_nw_logs_delivery"]);
                } catch (\Exception $e) {
                    wc_get_logger()->debug("Error message from nShift integration: " . $e->getMessage(), ["source" => "nshift_logs"]);
                    wc_get_logger()->debug("Error message from nShift integration:" . $e->getMessage(), ["source" => "nshift_nw_logs_delivery"]);
                }

                wc_get_logger()->debug("Order ID: " . $order->get_id() . " Order number: " . $order->get_order_number() . "  Status: " . $order->get_status(), ["source" => "nshift_nw_logs_delivery"]);

                if (empty($trackingResult)) {
                    wc_get_logger()->debug("trackingResult: empty", ["source" => "nshift_nw_logs_delivery"]);
                    continue;
                }

                if ($trackingResult['isDeleted']) {
                    wc_get_logger()->debug("uuid is deleted: empty", ["source" => "nshift_nw_logs_delivery"]);
                    wc_get_logger()->debug('uuid is deleted: ' . $query, ["source" => "nshift_logs_delivery"]);
                    $order->update_meta_data('_nshift_tracking', '');
                    $order->update_meta_data('_nshift_tracking_id', '');
                    $order->update_meta_data('_nshift_carrier_name', '');
                    $order->update_meta_data('_nshift_product_name', '');
                    $order->update_meta_data('_nshift_uuid', '');
                    $order->update_meta_data('_nshift_printed_date', '');
                    $order->update_meta_data('_kss_tracking_url', '');
                    $order->update_meta_data('_kss_tracking_id', '');
                    $order->update_status('wc-completed');
                    continue;
                }

                if (isset($trackingResult['moneyAmounts'][0]['value'])) {
                    wc_get_logger()->debug("_nshift_money_net: " . $trackingResult['moneyAmounts'][0]['value'], ["source" => "nshift_nw_logs_delivery"]);
                    $order->update_meta_data('_nshift_money_net', $trackingResult['moneyAmounts'][0]['value']);
                }

                if (isset($trackingResult['moneyAmounts'][1]['value'])) {
                    wc_get_logger()->debug("_nshift_money_gross: " . $trackingResult['moneyAmounts'][1]['value'], ["source" => "nshift_nw_logs_delivery"]);
                    $order->update_meta_data('_nshift_money_gross', $trackingResult['moneyAmounts'][1]['value']);
                }

                $status = '';

                foreach ($trackingResult['lines'][0]['packages'] as $package) {
                    usort($package['events'], function ($a, $b) {
                        return strtotime($a['date']) - strtotime($b['date']);
                    });

                    foreach ($package['events'] as $event) {
                        wc_get_logger()->debug("\$event['configurationName']: " . $event['configurationName'], ["source" => "nshift_nw_logs_delivery"]);
                        wc_get_logger()->debug("\$event['normalizedStatusName']: " . $event['normalizedStatusName'], ["source" => "nshift_nw_logs_delivery"]);
                        switch ($event['configurationName']) {
                            case 'HANDED IN AT POSTAL TERMINAL':
                                $status = 'wc-shipped';
                                break;
                            case 'Ready for pickup':
                                $status = 'wc-atpostaloffice';
                                break;
                            case 'Levert trykkeri':
                                $status = 'wc-printshopto';
                                break;
                            case 'Sendt fra trykkeri':
                                $status = 'wc-printshopfrom';
                                break;
                            case 'Sendt til trykkeri':
                                $status = 'wc-senttoprintshop';
                                break;
                        }

                        switch ($event['normalizedStatusName']) {
                            /*case 'In transit':
                            $status = 'wc-completed';
                            break;*/
                            case 'Departed from terminal/hub':
                                $status = 'wc-shipped';
                                break;
                            case 'Arrived at terminal/hub':
                                $status = 'wc-shipped';
                                break;
                            case 'Arrived at terminal/hub':
                                $status = 'wc-shipped';
                                break;
                            case 'Delivered':
                                $status = 'wc-delivered';
                                break;
                            case 'Returned to sender':
                                $status = 'wc-returned';
                                break;
                        }
                    }
                }

                wc_get_logger()->debug("order status after for each: " . $status, ["source" => "nshift_nw_logs_delivery"]);

                if ($status !== '' && !is_null($status)) {
                    wc_get_logger()->debug('order status \$status type: ' . gettype($status), ["source" => "nshift_nw_logs_delivery"]);
                    wc_get_logger()->debug('order status \$status !== \'\': ' . print_r($status, true), ["source" => "nshift_nw_logs_delivery"]);
                    $order->update_status($status);
                }

                $order->save();
            }
            unset($orders);
        }
    }

    /**
     * Initialize integration settings form fields.
     */

    public function init_form_fields()
    {
        $this->form_fields = [
            'nshift_client_id' => [
                'title'             => __('Client Id'),
                'type'              => 'text',
                'description'       => __('Enter client id'),
                'desc_tip'          => true,
                'default'           => '',
                'css'               => 'width:300px;',
            ],
            'nshift_client_secret' => [
                'title'             => __('Client secret'),
                'type'              => 'text',
                'description'       => __('Enter client secret'),
                'desc_tip'          => true,
                'default'           => '',
                'css'               => 'width:300px;',
            ],
            'nshift_installation_tags' => [
                'title'             => __('Installation tags'),
                'type'              => 'text',
                'description'       => __('Seperate by ;'),
                'desc_tip'          => true,
                'default'           => '',
                'css'               => 'width:300px;',
            ],
            'nshift_actor_tags' => [
                'title'             => __('Actor tags'),
                'type'              => 'text',
                'description'       => __('Seperate by ;'),
                'desc_tip'          => true,
                'default'           => '',
                'css'               => 'width:300px;',
            ],
            'nshift_prefix_checkbox' => [
                'title'             => __('Any prefix for orders?'),
                'type'              => 'checkbox',
                'description'       => __('Orderprefix'),
                'desc_tip'          => true,
                'default'           => '',
            ],
            'nshift_prefix' => [
                'title'             => __('Prefix for orders'),
                'type'              => 'text',
                'description'       => __('Orderprefix'),
                'desc_tip'          => true,
                'default'           => '',
                'css'               => 'width:300px;',
            ]
        ];
    }
}
