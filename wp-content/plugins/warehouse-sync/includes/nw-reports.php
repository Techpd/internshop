<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Register reports with WooCommerce and
     * output admin dashboard widgets
     *
     */
    class NW_Reports
    {

        /**
         * Hook in widgets and reports
         *
         */
        public static function init()
        {
            add_action('wp_dashboard_setup', __CLASS__ . '::add_widget');
            add_filter('woocommerce_admin_reports', __CLASS__ . '::add_reports', 99, 1);
        }

        /**
         * Add widgets for top selling clubs and vendors respectively
         *
         */
        public static function add_widget()
        {
            global $wp_locale;
            wp_enqueue_style('nw_dashboard_widgets', NW_PLUGIN_URL . 'assets/css/dashboard_widgets.css');
            wp_add_dashboard_widget(
                'nw_vendor_sales',
                sprintf(__('Top selling vendors for %s', 'newwave'), $wp_locale->get_month(date('m'))),
                function () {
                    NW_Reports::render_widget('vendor');
                }
            );

            wp_add_dashboard_widget(
                'nw_club_sales',
                sprintf(__('Top selling clubs for %s', 'newwave'), $wp_locale->get_month(date('m'))),
                function () {
                    NW_Reports::render_widget('club');
                }
            );
        }

        /**
         * Render dashboard widget for custom reports
         *
         * @param string $type Either 'club' or 'vendor' for the type of widget to render
         */
        public static function render_widget($type)
        {
            if ($type == 'club')
                $all_shops = NWP_Functions::query_clubs();
            else
                $all_shops = NWP_Functions::query_vendors();

            require_once(NW_PLUGIN_DIR . 'includes/nw-reports-class.php');
            $report = new NW_Shop_Reports($type, 'month');

            echo '<ul>';
            $index = 0;
            if (empty($report->shop_sales_total)) {
                printf('<li><span class="no-sales">%s</span></li>', __('No sales this month', 'newwave'));
            } else {
                foreach ($report->shop_sales_total as $shop_id => $shop_data) {
                    $index++;

                    // Prepare sparkline
                    $sparkline = array();
                    for ($i = 0; $i <= $report->chart_interval; $i++) {
                        $time = strtotime(date('Ymd', strtotime("+{$i} DAY", $report->start_date))) * 1000;
                        $sparkline[] = array($time, isset($report->shop_sales_over_time[$time][$shop_id]) ? $report->shop_sales_over_time[$time][$shop_id]['sum'] : 0);
                    }

                    // Render widget
?>
                    <li>
                        <a href="<?php echo admin_url('admin.php?page=wc-reports&tab=' . $type . 's&report=sales_by_shops&range=month&sales_by_shops=' . $shop_id); ?>">
                            <strong>
                                <span class="number">
                                    <?php echo $index; ?>
                                </span>
                                <?php
                                echo isset($all_shops[$shop_id]['name']) ?  esc_html($all_shops[$shop_id]['name']) : '';
                                ?>
                                <br />
                                <span class="underline">
                                    <?php printf(__('%s orders in total', 'newwave'), $shop_data['count']); ?></span>
                            </strong>
                            <div class="price">
                                <?php echo wc_price($shop_data['sum']); ?>
                                <span class="wc_sparkline lines" data-color="#777" data-sparkline="<?php echo esc_attr(json_encode($sparkline)); ?>"></span>
                            </div>
                        </a>
                    </li>
<?php

                    if ($index == 3)
                        break;
                }
            }
            echo '</ul>';
        }

        /**
         * Add reports to the WooCommerce admin report page
         *
         * @param array $reports
         */
        public static function add_reports($reports)
        {
            $reports['vendors'] = array(
                'title' => __('Vendors', 'newwave'),
                'reports' => array(
                    'sales_by_shops' => array(
                        'title' => 'Sale per vendor',
                        'description' => '',
                        'hide_title' => 1,
                        'callback' => 'NW_Reports::get_reports',
                    )
                )
            );
            $reports['clubs'] = array(
                'title' => __('Clubs', 'newwave'),
                'reports' => array(
                    'sales_by_shops' => array(
                        'title' => 'Sale per club',
                        'description' => '',
                        'hide_title' => 1,
                        'callback' => 'NW_Reports::get_reports',
                    )
                )
            );
            return $reports;
        }

        /**
         * Callback function to trigger rendering of reports
         *
         */
        public static function get_reports()
        {
            require_once(NW_PLUGIN_DIR . 'includes/nw-reports-class.php');
            new NW_Shop_Reports();
        }
    }
?>