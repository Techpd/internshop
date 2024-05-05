<?php

// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Custom coupons to map rebates to customer IDs
     */
    class NW_Coupons
    {
        static $coupons = array(
            'craft10percent' => array(
                'display' => '10%',
                'amount' => 10,
                'type' => 'percent',
                'customer_id' => '17201',
            ),
            'craft15percent' => array(
                'display' => '15%',
                'amount' => 15,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft20percent' => array(
                'display' => '20%',
                'amount' => 20,
                'type' => 'percent',
                'customer_id' => '17202',
            ),
            'craft25percent' => array(
                'display' => '25%',
                'amount' => 25,
                'type' => 'percent',
                'customer_id' => '17203',
            ),
            'craft30percent' => array(
                'display' => '30%',
                'amount' => 30,
                'type' => 'percent',
                'customer_id' => '17204',
            ),
            'craft35percent' => array(
                'display' => '35%',
                'amount' => 35,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft40percent' => array(
                'display' => '40%',
                'amount' => 40,
                'type' => 'percent',
                'customer_id' => '17205',
            ),
            'craft45percent' => array(
                'display' => '45%',
                'amount' => 45,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft50percent' => array(
                'display' => '50%',
                'amount' => 50,
                'type' => 'percent',
                'customer_id' => '17206',
            ),
            'craft55percent' => array(
                'display' => '55%',
                'amount' => 55,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft60percent' => array(
                'display' => '60%',
                'amount' => 60,
                'type' => 'percent',
                'customer_id' => '17208',
            ),
            'craft65percent' => array(
                'display' => '65%',
                'amount' => 65,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft70percent' => array(
                'display' => '70%',
                'amount' => 70,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft75percent' => array(
                'display' => '75%',
                'amount' => 75,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft80percent' => array(
                'display' => '80%',
                'amount' => 80,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft85percent' => array(
                'display' => '85%',
                'amount' => 85,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft90percent' => array(
                'display' => '90%',
                'amount' => 90,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft95percent' => array(
                'display' => '95%',
                'amount' => 95,
                'type' => 'percent',
                'customer_id' => '17200',
            ),
            'craft100percent' => array(
                'display' => '100%',
                'amount' => 100,
                'type' => 'percent',
                'customer_id' => '17207',
            ),
        );

        /**
         * Add hooks and filters
         *
         */

        public static function init()
        {
            //check if this feature is enabled in plugin settings
            if (!get_option('_nw_feature_coupon')) {
                return;
            }

            add_action('woocommerce_coupon_options', __CLASS__ . '::display_coupon_field', 1, 2);
            add_action('woocommerce_coupon_options_save', __CLASS__ . '::save_coupon', 1, 3);
            add_action('admin_head', __CLASS__ . '::enqueue_assets');
        }

        /**
         * Enqueue styling to change order of fields, and hide input fields that
         * are non-applicable to coupons for NewWave
         */

        public static function enqueue_assets()
        {
            $screen = get_current_screen();
            if ('shop_coupon' == $screen->post_type) {
                wp_enqueue_style(
                    'nw_admin_coupon',
                    NW_PLUGIN_URL . 'assets/css/nw-admin-coupon.css'
                );
            }
        }

        /**
         * Display custom coupon select, corresponding to the predefined
         * NewWave customer IDs
         *
         * @param string $coupon_id Coupon used (the coupon name)
         * @return string The corresponding customer_id for coupon. Defaults to '17200'
         */

        public static function get_customer_id($coupon_id)
        {
            $customer_id = '17200';
            if (array_key_exists($coupon_id, static::$coupons))
                $customer_id = static::$coupons[$coupon_id]['customer_id'];

            return $customer_id;
        }

        /**
         * Display custom coupon select, corresponding to the predefined
         * NewWave customer IDs
         *
         * @param $id $post_id Array of query args
         * @param WC_Coupon $coupon Coupon object
         */

        public static function display_coupon_field($post_id, $coupon)
        {
            $coupons = array();
            foreach (static::$coupons as $key => $data) {
                $coupons[$key] = $data['display'];
            }

            woocommerce_wp_select(array(
                'id'      => 'nw_coupon_type',
                'label'   => __('New Wave Coupon', 'newwave'),
                'options' => $coupons,
                'value'   => $coupon->get_meta('_nw_coupon_type', true),
            ));
        }

        /**
         * Save the type of NewWave coupon, and override other coupon values
         * that must be left out, or set to a specific value
         *
         * @param int $post_id Post ID of the coupon object
         * @param WC_Coupon $coupon Coupon object
         */

        public static function save_coupon($post_id, $coupon)
        {
            $type = wc_clean($_POST['nw_coupon_type']);
            $type = $type ? $type : 'craft10percent';
            $new_data = static::$coupons[$type];

            $coupon->update_meta_data('_nw_coupon_type', $type);
            $coupon->set_discount_type($new_data['type']);
            $coupon->set_amount($new_data['amount']);
            $coupon->save();
        }
    }

    NW_Coupons::init();
