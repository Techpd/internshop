<?php

if (!defined('ABSPATH')) exit;

//WC_Admin_Report class was not included by woocommerce plugin, hence including here before it is inherited
include_once(WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php');
/**
 * Custom reports based on sales for shops (clubs or vendors)
 *
 */
class NW_Shop_Reports extends WC_Admin_Report
{

    /**
     * Chart colors.
     *
     * @var array
     */
    public $chart_colours = array();


    /**
     * IDs of shops to include in the report
     *
     * @var array
     */
    public $shops = array();


    /**
     * Total sales and number of orders for shops,
     * indexed by shop_id
     *
     * @var array
     */
    public $shop_sales_total = array();


    /**
     * Sales for shops over time, indexed by time and the shop_id
     * e.g. [$time_unit][$shop_id] = array('sum' => 0, 'count' => '0')
     *
     * @var array
     */
    public $shop_sales_over_time = array();


    /**
     * Type of report, 'club' or 'vendor'
     *
     * @var string
     */
    public $type  = '';


    /**
     * Constructor
     *
     * @param string $type Either 'club', 'vendor' or false
     * @param string $current_range Time range to get report for
     */
    public function __construct($type = false, $current_range = false)
    {
        // Type is specified through parameters -> just prepare report, no HTML rendering
        if (is_string($type)) {
            if ($type == 'club')
                $this->type = 'club';
            else
                $this->type = 'vendor';

            if (!in_array($current_range, array('custom', 'year', 'last_month', 'month', '7day')))
                $current_range = 'month';

            $this->check_current_range_nonce($current_range);
            $this->calculate_current_range($current_range);
            $this->get_data();
        }

        // Output report from $_GET params
        else {
            if (isset($_GET['sales_by_shops'])) {
                if (is_array($_GET['sales_by_shops']))
                    $this->shops = array_map('absint', $_GET['sales_by_shops']);
                else
                    $this->shops = array(absint($_GET['sales_by_shops']));
            }

            if (isset($_GET['tab']) && $_GET['tab'] == 'clubs') {
                $this->type = 'club';
                $this->all_shops = NWP_Functions::query_clubs();
            } else {
                $this->type = 'vendor';
                $this->all_shops = NWP_Functions::query_vendors();
            }
            $this->output_report();
        }
    }

    /**
     * Get the legend for the main chart sidebar.
     *
     * @return array
     */
    public function get_chart_legend()
    {
        if (empty($this->shops)) {
            return array();
        }

        $legend = array();
        $index  = 0;

        foreach ($this->shops as $shop_id) {
            $name = $this->all_shops[$shop_id]['name'];
            if (isset($this->shop_sales_total[$shop_id])) {
                $sum = $this->shop_sales_total[$shop_id]['sum'];
                $count = $this->shop_sales_total[$shop_id]['count'];
            } else
                $sum = $count = 0;

            $legend[] = array(
                /* translators: 1: total sum sold for 2: shop name */
                'title' => sprintf(_x('%1$s %2$d orders - %3$s', 'Admin reports', 'newwave'), '<strong>' . wc_price($sum) . '</strong>', $count, $name),
                'color'            => isset($this->chart_colours[$index]) ? $this->chart_colours[$index] : $this->chart_colours[0],
                'highlight_series' => $index,
            );

            $index++;
        }

        return $legend;
    }

    /**
     * Reads data from the DB, based on the report type
     *
     */
    public function get_data()
    {
        // Get item sales data
        $shops = $this->get_order_report_data(array(
            'data' => array(
                '_nw_' . $this->type => array(
                    'type'     => 'meta',
                    'function' => '',
                    'name'     => 'id',
                ),
                '_order_total' => array(
                    'type'     => 'meta',
                    'function' => '',
                    'name'     => 'sum',
                ),
                'post_date' => array(
                    'type'     => 'post_data',
                    'function' => '',
                    'name'     => 'post_date',
                ),
            ),
            'query_type'   => 'get_results',
            'group_by' => 'id',
            'order_by' => 'sum DESC',
            'filter_range' => true,
        ));

        $this->shop_sales_total = array();
        $this->shop_sales_over_time = array();

        if (is_array($shops)) {
            foreach ($shops as $shop) {
                switch ($this->chart_groupby) {
                    case 'day':
                        $time = strtotime(date('Ymd', strtotime($shop->post_date))) * 1000;
                        break;
                    case 'month':
                    default:
                        $time = strtotime(date('Ym', strtotime($shop->post_date)) . '01') * 1000;
                        break;
                }

                // Store sale totals for whole period
                if (!isset($this->shop_sales_total[$shop->id]))
                    $this->shop_sales_total[$shop->id] = array('count' => 0, 'sum' => 0);

                $this->shop_sales_total[$shop->id]['sum'] += $shop->sum;
                $this->shop_sales_total[$shop->id]['count']++;;

                // Store sales distributed over time units
                if (!isset($this->shop_sales_over_time[$time]))
                    $this->shop_sales_over_time[$time] = array();

                if (!isset($this->shop_sales_over_time[$time][$shop->id]))
                    $this->shop_sales_over_time[$time][$shop->id] = array('count' => 0, 'sum' => 0);
                $this->shop_sales_over_time[$time][$shop->id]['sum'] += $shop->sum;
                $this->shop_sales_over_time[$time][$shop->id]['count']++;
            }
        }

        //Sort shop sales total
        $cmp = function ($a, $b) {
            return ($a['sum'] <= $b['sum']);
        };
        uasort($this->shop_sales_total, $cmp);
    }

    /**
     * Output the report.
     */
    public function output_report()
    {
        $ranges = array(
            'year'         => __('Year', 'woocommerce'),
            'last_month'   => __('Last month', 'woocommerce'),
            'month'        => __('This month', 'woocommerce'),
            '7day'         => __('Last 7 days', 'woocommerce'),
        );

        $this->chart_colours = array('#3498db', '#34495e', '#1abc9c', '#2ecc71', '#f1c40f', '#e67e22', '#e74c3c', '#2980b9', '#8e44ad', '#2c3e50', '#16a085', '#27ae60', '#f39c12', '#d35400', '#c0392b');

        $current_range = !empty($_GET['range']) ? sanitize_text_field($_GET['range']) : '7day';

        if (!in_array($current_range, array('custom', 'year', 'last_month', 'month', '7day'))) {
            $current_range = '7day';
        }

        $this->check_current_range_nonce($current_range);
        $this->calculate_current_range($current_range);

        $this->get_data();

        include(WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
    }

    /**
     * Get chart widgets.
     *
     * @return array
     */
    public function get_chart_widgets()
    {
        return array(
            array(
                'title'    => $this->type == 'club' ? __('Clubs', 'newwave') : __('Vendors', 'newwave'),
                'callback' => array($this, 'shop_widget'),
            ),
        );
    }

    /**
     * Output category widget.
     */
    public function shop_widget()
    {
        $placeholder = ($this->type == 'club') ? __('Choose clubs', 'newwave') : __('Choose vendors', 'newwave');
?>
        <form method="GET">
            <div>
                <select multiple="multiple" data-placeholder="<?php esc_attr_e($placeholder); ?>" class="wc-enhanced-select" id="sales_by_shops" name="sales_by_shops[]" style="width: 205px;">
                    <?php
                    foreach ($this->all_shops as $shop) {
                        printf('<option value="%s">%s</option>', $shop['id'], $shop['name']);
                    }
                    ?>
                </select>
                <a href="#" class="select_none"><?php _e('None', 'woocommerce'); ?></a>
                <a href="#" class="select_all"><?php _e('All', 'woocommerce'); ?></a>
                <input type="submit" class="submit button" value="<?php esc_attr_e('Show', 'woocommerce'); ?>" />
                <input type="hidden" name="range" value="<?php echo (!empty($_GET['range'])) ? esc_attr($_GET['range']) : ''; ?>" />
                <input type="hidden" name="start_date" value="<?php echo (!empty($_GET['start_date'])) ? esc_attr($_GET['start_date']) : ''; ?>" />
                <input type="hidden" name="end_date" value="<?php echo (!empty($_GET['end_date'])) ? esc_attr($_GET['end_date']) : ''; ?>" />
                <input type="hidden" name="page" value="<?php echo (!empty($_GET['page'])) ? esc_attr($_GET['page']) : ''; ?>" />
                <input type="hidden" name="tab" value="<?php echo (!empty($_GET['tab'])) ? esc_attr($_GET['tab']) : ''; ?>" />
                <input type="hidden" name="report" value="sales_by_shops" />
            </div>
            <script type="text/javascript">
                jQuery(function() {
                    // Select all/None
                    jQuery('.chart-widget').on('click', '.select_all', function() {
                        jQuery(this).closest('div').find('select option').attr('selected', 'selected');
                        jQuery(this).closest('div').find('select').change();
                        return false;
                    });

                    jQuery('.chart-widget').on('click', '.select_none', function() {
                        jQuery(this).closest('div').find('select option').removeAttr('selected');
                        jQuery(this).closest('div').find('select').change();
                        return false;
                    });
                });
            </script>
        </form>
    <?php
    }

    /**
     * Output an export link.
     *
     */
    public function get_export_button()
    {

        $current_range = !empty($_GET['range']) ? sanitize_text_field($_GET['range']) : '7day';
    ?>
        <a href="#" download="report-<?php echo esc_attr($current_range); ?>-<?php echo date_i18n('Y-m-d', current_time('timestamp')); ?>.csv" class="export_csv" data-export="chart" data-xaxes="<?php esc_attr_e('Date', 'woocommerce'); ?>" data-groupby="<?php echo $this->chart_groupby; ?>">
            <?php _e('Export CSV', 'woocommerce'); ?>
        </a>
        <?php
    }

    /**
     * Get the main chart.
     *
     */
    public function get_main_chart()
    {
        global $wp_locale;

        if (empty($this->shops)) {
            $msg = $this->type == 'club' ? __('Choose a club to view stats', 'newwave') : __('Choose a vendor to view stats', 'newwave');
        ?>
            <div class="chart-container">
                <p class="chart-prompt"><?php echo ($msg); ?></p>
            </div>
        <?php
        } else {
            $chart_data = array();
            $index      = 0;

            // Prepare data; distribute $shop_sales_over_time over the x-axis
            foreach ($this->shops as $shop_id) {
                $shop = $this->all_shops[$shop_id];
                $shop_chart_sum_data = $shop_chart_count_data = array();

                for ($i = 0; $i <= $this->chart_interval; $i++) {
                    switch ($this->chart_groupby) {
                        case 'day':
                            $time = strtotime(date('Ymd', strtotime("+{$i} DAY", $this->start_date))) * 1000;
                            break;
                        case 'month':
                        default:
                            $time = strtotime(date('Ym', strtotime("+{$i} MONTH", $this->start_date)) . '01') * 1000;
                            break;
                    }

                    if (isset($this->shop_sales_over_time[$time][$shop_id])) {
                        $sum = $this->shop_sales_over_time[$time][$shop_id]['sum'];
                        $count = $this->shop_sales_over_time[$time][$shop_id]['count'];
                    } else
                        $sum = $count = 0;

                    $shop_chart_sum_data[] = array($time, (float) wc_format_decimal($sum, wc_get_price_decimals()));
                    $shop_chart_count_data[] = array($time, $count);
                }

                $chart_data[$shop_id]['shop_name'] = $shop['name'];
                $chart_data[$shop_id]['sums'] = $shop_chart_sum_data;
                $chart_data[$shop_id]['counts'] = $shop_chart_count_data;

                $index++;
            }
        ?>
            <div class="chart-container">
                <div class="chart-placeholder main"></div>
            </div>
            <script type="text/javascript">
                var main_chart;

                jQuery(function() {
                    var drawGraph = function(highlight) {
                        var series = [
                            <?php
                            // Output order totals
                            $index = 0;
                            foreach ($chart_data as $data) {
                                $color  = isset($this->chart_colours[$index]) ? $this->chart_colours[$index] : $this->chart_colours[0];
                                $width  = $this->barwidth / sizeof($chart_data);
                                $offset = ($width * $index);
                                $counts = $data['counts'];
                                foreach ($counts as $key => $series_data) {
                                    $counts[$key][0] = $series_data[0] + $offset;
                                }
                                echo '{
										label: "' . esc_js($data['shop_name']) . '",
										data: jQuery.parseJSON("' . json_encode($counts) . '"),
										color: "' . $color . '",
										shadowSize: 0,
										bars: {
											fillColor: "#dbe1e3",
											fill: true,
											show: true,
											lineWidth: 0,
											align: "center",
											barWidth: ' . $width * 0.75 . ',
											stack: false
										},
										prepend_tooltip: "' . esc_js(__('Orders', 'newwave')) . ': ",
										enable_tooltip: true,
										prepend_label: true
									},';
                                $index++;
                            }

                            // Output net totals
                            $index = 0;
                            foreach ($chart_data as $data) {
                                $color  = isset($this->chart_colours[$index]) ? $this->chart_colours[$index] : $this->chart_colours[0];
                                $width  = $this->barwidth / sizeof($chart_data);
                                $offset = ($width * $index);
                                $sums = $data['sums'];
                                foreach ($sums as $key => $series_data) {
                                    $sums[$key][0] = $series_data[0] + $offset;
                                }

                                echo '{
										label: "' . esc_js($data['shop_name']) . '",
										data: jQuery.parseJSON("' . json_encode($sums) . '"),
										color: "' . $color . '",
										yaxis: 2,
										points: {show: false},
										lines: { show: true, lineWidth: 5, fill: false },
										points: { show: true, radius: 6, lineWidth: 4, fillColor: "#fff", fill: true },
										' . $this->get_currency_tooltip() . ',
										enable_tooltip: true,
										prepend_label: true,
									},';
                                $index++;
                            }
                            ?>
                        ];

                        if (highlight !== 'undefined' && series[highlight]) {
                            highlight_series_count = series[highlight];
                            highlight_series_count.color = '#9c5d905e';
                            highlight_series_count.bars.fillColor = '#9c5d905e';

                            highlight_series_sum = series[(series.length / 2) + highlight];
                            highlight_series_sum.color = '#9c5d90';
                            highlight_series_sum.lines.lineWidth = 5;
                        }

                        main_chart = jQuery.plot(
                            jQuery('.chart-placeholder.main'),
                            series, {
                                legend: {
                                    show: false
                                },
                                grid: {
                                    color: '#aaa',
                                    borderColor: 'transparent',
                                    borderWidth: 0,
                                    hoverable: true
                                },
                                xaxes: [{
                                    color: '#aaa',
                                    reserveSpace: true,
                                    position: "bottom",
                                    tickColor: 'transparent',
                                    mode: "time",
                                    timeformat: "<?php echo ('day' === $this->chart_groupby) ? '%d %b' : '%b'; ?>",
                                    monthNames: <?php echo json_encode(array_values($wp_locale->month_abbrev)); ?>,
                                    tickLength: 1,
                                    minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
                                    tickSize: [1, "<?php echo $this->chart_groupby; ?>"],
                                    font: {
                                        color: "#aaa"
                                    }
                                }],
                                yaxes: [{
                                        min: 0,
                                        minTickSize: 1,
                                        tickDecimals: 0,
                                        color: '#d4d9dc',
                                        font: {
                                            color: "#aaa"
                                        }
                                    },
                                    {
                                        position: "right",
                                        min: 0,
                                        tickDecimals: 2,
                                        alignTicksWithAxis: 1,
                                        color: 'transparent',
                                        font: {
                                            color: "#aaa"
                                        }
                                    }
                                ],
                            }
                        );

                        jQuery('.chart-placeholder').resize();

                    }

                    drawGraph();

                    jQuery('.highlight_series').hover(
                        function() {
                            drawGraph(jQuery(this).data('series'));
                        },
                        function() {
                            drawGraph();
                        }
                    );
                });
            </script>
<?php
        }
    }
}
