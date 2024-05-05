<?php

if (!class_exists('WC_nshift_tracking')) :

    class WC_nshift_tracking
    {

        /**
         * Construct the plugin.
         */

        public function __construct()
        {
            add_action('plugins_loaded', [$this, 'init']);
        }

        /**
         * Initialize the plugin.
         */

        public function init()
        {
            // Set the plugin slug
            if (!defined('MY_PLUGIN_SLUG')) {
                define('MY_PLUGIN_SLUG', 'wc-settings');
            }

            if (class_exists('WC_Integration')) {
                // Include our integration class.
                include_once 'class-nshift-tracking-integration.php';
                // new WC_nShift_Tracking_Integration();

                // Register the integration.
                add_filter('woocommerce_integrations', [$this, 'add_integration']);
            }
        }

        /**
         * Add a new integration to WooCommerce.
         */

        public function add_integration($integrations)
        {
            $integrations[] = 'WC_nShift_Tracking_Integration';
            return $integrations;
        }
    }

    $WC_nshift_tracking = new WC_nshift_tracking(__FILE__);

    function WC_nshift_tracking_action_links($links)
    {
        $links[] = '<a href="' . menu_page_url(MY_PLUGIN_SLUG, false) . '&tab=integration&section=nshift-tracking">Settings</a>';
        return $links;
    }

endif;
