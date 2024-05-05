<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Create page for the miscellaneous settings adjustable for this plugin
     *
     */
    class NW_Settings
    {

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Add settings tab to WooCommerce Settings
            add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 99);

            // Add tab content
            add_action('woocommerce_settings_tabs_newwave', function () {
                woocommerce_admin_fields(static::get_settings());
            });

            // Save tab content
            add_action('woocommerce_update_options_newwave', function () {
                woocommerce_update_options(static::get_settings());
            });
        }

        /**
         * Add settings tab
         *
         * @param string[] $sections
         * @return string[]
         */
        public static function add_settings_tab($sections)
        {
            $sections['newwave'] = 'Newwave';
            return $sections;
        }

        /**
         * Add settings tab
         *
         * @param string[] $sections
         * @return string[]
         */
        public static function get_settings()
        {
            $settings = array(
                'general_section' => array(
                    'name'     => '',
                    'type'     => 'title',
                    'id'       => 'nw_settings_general'
                ),

                'hide_expired' => array(
                    'id'   => 'nw_settings_days_after_expire',
                    'name' => __('Hide expired products after', 'newwave'),
                    'type' => 'select',
                    'options' => array(3 => 3, 7 => 7, 14 => 14, 21 => 21, 30 => 30),
                    'default' => 7,
                    'css'     => 'max-width: 60px;',
                    'desc_tip' => __('Number of days before expired products (stock logo and special) are hidden.', 'newwave'),
                    'desc' => __('days', 'newwave'),
                ),

                'password_strength' => array(
                    'id'   => 'nw_settings_password_strength',
                    'name' => __('User password strength', 'newwave'),
                    'type' => 'select',
                    'options' => array(
                        1 => __('Weak', 'newwave'),
                        2 => __('Medium', 'newwave'),
                        3 => __('Strong', 'newwave'),
                    ),
                    'default' => 2,
                    'css'     => 'max-width: 150px;',
                    'desc_tip' => __('Level of complexity required of user password.', 'newwave'),
                ),

                'customer_service_email' => array(
                    'id' => 'nw_settings_customer_service_email',
                    'name' => __('Product return notification', 'newwave'),
                    'type' => 'email',
                    'desc_tip' => __('Email address used for notifying when a customer register a return of products', 'newwave'),
                ),

                /* 'partly_shipped_notice' => array(
				'id' => 'nw_settings_partly_shipped_notice',
				'name' => __('Partly shipped notification', 'newwave'),
				'type' => 'checkbox',
				'desc_tip' => __("Send email notice to customer when order changes status to 'Partly Shipped'", 'newwave'),
			), */
                'sent_to_printing_notice' => array(
                    'id' => 'nw_settings_sent_to_printing_notice',
                    'name' => __('Sent to Printing notification', 'newwave'),
                    'type' => 'checkbox',
                    'desc_tip' => __("Send email notice to customer when order changes status to 'Sent to Printing'", 'newwave'),
                ),

                'import_tax' => array(
                    'id' => 'nw_settings_asw_import_include_tax',
                    'name' => __('Add taxes to ASW prices', 'newwave'),
                    'type' => 'radio',
                    'options' => array(1 => __('Yes', 'newwave'), 0 => __('No', 'newwave')),
                    'default' => 1,
                    'desc_tip' => __('Whether to add taxes to prices imported from ASW.', 'newwave'),
                ),

                'shipping_estimates' => array(
                    'id' => 'nw_settings_shipping_estimates',
                    'name' => __('Shipping estimates', 'newwave'),
                    'type' => 'textarea',
                    'desc_tip' => __('Text describing estimated times for shipping.', 'newwave'),
                ),

                'general_section_end' => array('type' => 'sectionend', 'id' => 'nw_settings_general'),

                // DEPRECATED - set FTP details
                // 'asw_exporter_section' => array(
                // 	'name'     => _x('ASW Exporter', 'newwave'),
                // 	'type'     => 'title',
                // 	'id'       => 'nw_settings_asw_exporter'
                // ),
                //
                // 'ftp_server' => array(
                // 	'id' => 'nw_settings_ftp_server',
                // 	'name' => __('FTP Server', 'newwave'),
                // 	'type' => 'text',
                // 	'desc_tip' => __('URL for the server to upload reports to', 'newwave'),
                // ),
                //
                // 'ftp_login' => array(
                // 	'id' => 'nw_settings_ftp_login',
                // 	'name' => __('FTP Login', 'newwave'),
                // 	'type' => 'text',
                // 	'desc_tip' => __('Login name/account to use for uploading reports', 'newwave'),
                // ),
                //
                // 'ftp_pw' => array(
                // 	'id' => 'nw_settings_password',
                // 	'name' => __('FTP Password', 'newwave'),
                // 	'type' => 'text',
                // 	'desc_tip' => __('Passord for the FTP Login account', 'newwave'),
                // ),
                //
                // 'asw_exporter_section_end' => array('type' => 'sectionend', 'id' => 'nw_settings_asw_exporter')
            );

            if(!get_option('_nw_shop_feature')){
                foreach ($settings as $setting => $args) {
                    if($setting !== 'hide_expired'){
                        unset($settings[$setting]);
                    }
                }
            }

            return $settings;
        }
    }
