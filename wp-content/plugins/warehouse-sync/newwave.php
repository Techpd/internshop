<?php
/*
* @wordpress-plugin
* Plugin Name:       NewWave WooCommerce plugin
* Description:       The NewWave plugin combines functionalities from Craft, Auclair, and Internshop.
* Version:           2.2.6
* Author:            Plan A Kommunikasjon
* Author URI:        http://planakommunikasjon.no/
* Requires at least: 4.9.4
* Tested up to: 4.9.4
* WC requires at least: 3.3.3
* WC tested up to: 3.3.3
* Text Domain: newwave
* Domain Path: /i18n
*/

// If called directly, abort
if (!defined('ABSPATH')) exit;

if (!defined('NW_PLUGIN_VERSION')) {
    define('NW_PLUGIN_VERSION', 0.8);
}

if (!defined('NW_PLUGIN_DIR')) {
    define('NW_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('NW_PLUGIN_URL')) {
    define('NW_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('NW_PLUGIN_FILE')) {
    define('NW_PLUGIN_FILE', __FILE__);
}

// PLANASD-484 added for use in the product classes for discount saving purposes as feature should work on its own as well
if (!defined('NWP_TABLE_DISCOUNTS')) {
    define('NWP_TABLE_DISCOUNTS', 'newwave_discounts');
}

if (!defined('NWP_TABLE_VARIATIONS')) {
    define('NWP_TABLE_VARIATIONS', 'newwave_variations');
}

if (!defined('MY_PLUGIN_SLUG')) {
    define('MY_PLUGIN_SLUG', 'wc-settings');
}

if (!class_exists('NW_Plugin')) :

    /**
     * Main plugin class, check dependencies and includes other classes
     *
     */

    class NW_Plugin
    {
        /**
         * Add hooks and filters
         */
        const PLUGIN_OPTS = array(
            '_nw_stock_sync_enabled',
            '_nw_stock_api_url',
            '_nw_stock_api_interval',
            '_nw_stock_api_token',
            '_nw_stock_api_type',
            '_nw_order_export_enabled',
            '_nw_order_export_api_url',
            '_nw_order_export_api_prefix',
            '_nw_order_api_interval',
            '_nw_order_api_token',
            '_nw_order_threshold',
            '_nw_export_default_cust',
            '_nw_export_vipps_cust',
            '_nw_export_shipping_postcodes',
            '_nw_export_shipp_cust',
            '_nw_order_tracking_enabled',
            '_nw_order_tracking_type',
            '_nw_bring_posten_url',
            '_nw_bring_api_url',
            '_nw_bring_order_prefix',
            '_nw_product_import_enabled',
            '_nwp_asw_endpoint_url',
            '_nwp_asw_auth_token',
            '_nw_feature_coupon',
            '_nw_feature_properties',
            '_nw_feature_color_attr',
            '_nw_feature_cat_imgs',
            '_nw_feature_purechat',
            '_nw_klarna_product_imgs',
            '_nw_remove_product_type',
            '_nw_remove_product_reviews',
            '_nw_login_redirect',
            '_nw_reg_redirect',
            '_nw_api_type',
            '_nw_shop_feature',
            '_nw_export_sales_csv',
            '_nw_order_export_type',
            '_nw_export_csv_ftp',
            '_nw_export_csv_ftp_user',
            '_nw_export_csv_ftp_pass',
            '_nw_export_csv_default_cust',
            '_nw_export_csv_land_code',
            '_nw_export_csv_vipps_cust',
            '_nw_export_csv_shipping_postcodes',
            '_nw_export_csv_shipp_cust',
            '_nw_export_csv_filename',
            '_nw_product_types',
            '_nw_product_brand_name',
            '_nw_oos_feature',
        );

        /**
         * @var string|null The plugin directory path
         */
        static $plugin_ver = NW_PLUGIN_VERSION;

        /**
         * @var string|null The plugin directory path
         */
        static $plugin_dir = NW_PLUGIN_DIR;

        /**
         * @var string|null The plugin URL path
         */
        static $plugin_url = NW_PLUGIN_URL;

        /**
         * @var string|null The plugin file path
         */
        static $plugin_file = NW_PLUGIN_FILE;

        public static function init()
        {
            // Add activation and deactivation hooks
            register_activation_hook(static::$plugin_file, __CLASS__ . '::install');
            register_deactivation_hook(static::$plugin_file, __CLASS__ . '::uninstall');

            // Load text domain and add admin menu
            add_action('plugins_loaded', __CLASS__ . '::check_dependencies_and_load_textdomain');
            add_filter('plugin_action_links', __CLASS__ . '::nw_plugin_settings_link', 10, 2);
            add_action('admin_enqueue_scripts', __CLASS__ . '::enqueue_admin_scripts');
            add_action('admin_menu', __CLASS__ . '::add_admin_menu');
            //Extend general tab to add SKU and show out of stock variations togge
            add_action('woocommerce_product_options_general_product_data', __CLASS__ . '::extend_general_panel', 99);
            // Save show out of stock toggle value
            add_action('woocommerce_admin_process_product_object', __CLASS__ . '::save', 100);
            // Update existing product types to variable/nw_stock
            add_action('init', __CLASS__ . '::nw_update_product_type');

            if (get_option('_nw_shop_feature')) {
                // Include all custom non-static classes
                require_once(static::$plugin_dir . 'includes/nw-shop-group-class.php');
                require_once(static::$plugin_dir . 'includes/nw-shop-vendor-class.php');
                require_once(static::$plugin_dir . 'includes/nw-shop-club-class.php');
                require_once(static::$plugin_dir . 'includes/nw-stock-control-class.php');

                // Register the taxonomies for sorting products based on stores
                require_once(static::$plugin_dir . 'includes/nw-register-taxonomies.php');
                NWP_Register_Taxonomies::init();

                // Register the custom post types for managing shops
                require_once(static::$plugin_dir . 'includes/nw-shop-group-cpt.php');
                require_once(static::$plugin_dir . 'includes/nw-shop-vendor-cpt.php');
                require_once(static::$plugin_dir . 'includes/nw-shop-club-cpt.php');
                NW_Shop_Group_CPT::init();
                NW_Shop_Vendor_CPT::init();
                NW_Shop_Club_CPT::init();

                // Register the custom post types for sport banners
                require_once(NW_PLUGIN_DIR . 'includes/nw-sport-banner-cpt.php');
                NW_Sport_Banner_CPT::init();

                // Add campaign settings page
                require_once(NW_PLUGIN_DIR . 'includes/nw-campaign-settings.php');
                NW_Campaign_Settings::init();

                // Add campaign email settings page
                require_once(NW_PLUGIN_DIR . 'includes/nw-campaign-email.php');
                NW_Campaign_Email::init();

                // Add support for customer return
                require_once(NW_PLUGIN_DIR . 'includes/nw-returns.php');
                NW_Returns::init();
            }

            $product_types = get_option('_nw_product_types', array());

            if(is_array($product_types) && count($product_types) > 1){
                // Extend WooCommerce with custom product types
                require_once(NW_PLUGIN_DIR . 'includes/nw-register-product-types.php');
                NW_Register_Product_Types::init();
            }

            // Import customizations
            require_once(static::$plugin_dir . 'includes/nw-coupons.php');
            require_once(static::$plugin_dir . 'includes/nw-product-properties.php');
            require_once(static::$plugin_dir . 'includes/nw-color-attribute-images.php');
            require_once(static::$plugin_dir . 'includes/nw-category-images.php');
            require_once(static::$plugin_dir . 'includes/nw-purechat.php');
            require_once(static::$plugin_dir . 'includes/nw-klarna-product-images.php');

            //Include backend helper functions
            require_once(NW_Plugin::$plugin_dir . 'includes/nw-functions.php');

            //check if this feature is enabled in plugin settings
            //seperate files have been included because craft/auclar and internshop uses different api type and the response structure is different
            if (get_option('_nw_api_type') == 'graphql') {
                require_once(static::$plugin_dir . 'includes/nw-product-synchronisation/graphql-asw-importer.php');
                require_once(static::$plugin_dir . 'includes/nw-product-synchronisation/graphql-asw-updater.php');
                NWP_ASW_Importer::init();
                NWP_ASW_Update::init();
            }

            if (get_option('_nw_api_type') == 'rpc') {
                require_once(static::$plugin_dir . 'includes/nw-product-synchronisation/rpc-asw-importer.php');
                require_once(static::$plugin_dir . 'includes/nw-product-synchronisation/rpc-asw-updater.php');
                NW_ASW_Importer::init();
                NW_ASW_Update::init();
            }

            if (get_option('_nw_shop_feature')) {
                // Handle user registration (additional fields and such)
                require_once(static::$plugin_dir . 'includes/nw-user-registration.php');
                NW_User_Registration::init();

                // Add ability to edit which shops user is registered with in the backend
                require_once(NW_PLUGIN_DIR . 'includes/nw-user-admin.php');
                NW_User_Admin::init();

                // Main control logic for product, price and logins
                require_once(NW_PLUGIN_DIR . 'includes/nw-session.php');
                NW_Session::init();

                // Customize checkout field and content for both standard and Klarna checkout
                require_once(NW_PLUGIN_DIR . 'includes/nw-checkout.php');
                NW_Checkout::init();

                require_once(NW_PLUGIN_DIR . 'includes/nw-checkout-klarna.php');
                NW_Checkout_Klarna::init();

                // Custom reports based on clubs or vendors
                require_once(NW_PLUGIN_DIR . 'includes/nw-reports.php');
                NW_Reports::init();

                // Add functionality to editing of WooCommerce orders
                require_once(NW_PLUGIN_DIR . 'includes/nw-order-admin.php');
                NW_Order_Admin::init();

                // Add custom endpoint for user to register in additional shops and switch between them
                require_once(NW_PLUGIN_DIR . 'includes/nw-user-manage-shops.php');
                NW_User_Manage_Shops::init();

                // Add a custom order status 'Partially Shipped'
                require_once(NW_PLUGIN_DIR . 'includes/nw-order-status.php');
                NW_Order_Status::init();

                // Add plugin to admin menu
                add_action('admin_menu', __CLASS__ . '::add_admin_page', 99);

                // Include front end content customizations and helper functions
                require_once(NW_PLUGIN_DIR . 'includes/nw-functions-template.php');
            }

            // Add a general settings page for this plugin
            require_once(NW_PLUGIN_DIR . 'includes/nw-settings.php');
            NW_Settings::init();

            // Remove non-applicable product types from dropdown when creating a product
            if (get_option('_nw_remove_product_type')) {
                add_filter('product_type_selector', function ($types) {
                    unset($types['grouped']);
                    unset($types['external']);
                    return $types;
                }, 99);
            }

            // Remove review tab from product page
            if (get_option('_nw_remove_product_reviews')) {
                add_filter('woocommerce_product_tabs', function ($tabs) {
                    unset($tabs['reviews']);
                    return $tabs;
                }, 99);
            }

            add_filter('woocommerce_login_redirect', __CLASS__ . '::login_redirect', 20, 1);
            add_filter('woocommerce_registration_redirect', __CLASS__ . '::registration_redirect', 20, 1);

            // Include front end assets
            add_action('wp_enqueue_scripts', __CLASS__ . '::enqueue_front_end_assets');

            // Hide unused backend menu items
            add_action('admin_menu', __CLASS__ . '::remove_menu_items');

            //Stock synchronization feature
            if (get_option('_nw_stock_api_type') == 'rpc') {
                require_once(static::$plugin_dir . 'includes/nw-stock-synchronisation/rpc-nw-stock.php');
                NW_ASW_Stock_Balance::init();
            }

            if (get_option('_nw_stock_api_type') == 'graphql') {
                require_once(static::$plugin_dir . 'includes/nw-stock-synchronisation/graphql-nw-stock.php');
                NW_ASW_Stock_Balance::init();
            }

            if(!get_option('_nw_stock_sync_enabled')){
                if (wp_next_scheduled('nw_update_stock_balance')) {
                    wp_clear_scheduled_hook('nw_update_stock_balance');
                }
            }

            //Order synchronization feature
            require_once(static::$plugin_dir . 'includes/nw-order-synchronisation/nw-order-export.php');
            NW_Order_Export::init();

            //Order status tracking feature - Nshift
            if (get_option('_nw_order_tracking_enabled')) {
                if(get_option('_nw_order_tracking_type') == 'nshift'){
                    require_once(static::$plugin_dir . 'includes/nw-order-status-tracking/woocommerce-nshift-tracking.php');
                }
                
                if(get_option('_nw_order_tracking_type') == 'bring'){
                    require_once(static::$plugin_dir . 'includes/nw-order-status-tracking/class-bring-order-tracking.php');
                }

                //Commenting this because this class is not initialized/not in use anywhere.
                // require_once(static::$plugin_dir . 'includes/nw-order-status-tracking/class-nshift-tracking-emails.php');
                // new WC_nShift_Tracking_Emails();
            } else {
                if (wp_next_scheduled('nshift_check_orders_awaiting_shipment')) {
                    wp_clear_scheduled_hook('nshift_check_orders_awaiting_shipment');
                }
                if (wp_next_scheduled('nshift_check_orders_awaiting_delivery')) {
                    wp_clear_scheduled_hook('nshift_check_orders_awaiting_delivery');
                }
                if (wp_next_scheduled('nw_check_orders_awaiting_delivery')) {
                    wp_clear_scheduled_hook('nw_check_orders_awaiting_delivery');
                }
                if (wp_next_scheduled('nw_check_orders_awaiting_shipment')) {
                    wp_clear_scheduled_hook('nw_check_orders_awaiting_shipment');
                }
            }

            //Export orders excel sheets to newwave feature
            if (get_option('_nw_export_sales_csv')) {
                require_once(static::$plugin_dir . 'includes/nw-asw-exporter.php');
                NW_ASW_Exporter::init();
            }else{
                if (wp_next_scheduled('nw_asw_export')) {
                    wp_clear_scheduled_hook('nw_asw_export');
                }
            }

            if (get_option('_nw_shop_feature') && !get_option('nw_variations_discounts_tbl_created') ) {
                global $wpdb;
                $discounts_table = $wpdb->prefix . NWP_TABLE_DISCOUNTS;
                $variations_table = $wpdb->prefix . NWP_TABLE_VARIATIONS;
                $charset_collate = $wpdb->get_charset_collate();

                // Create a discount table, for sorting by price depending on current shop
                $discounts_sql = "CREATE TABLE IF NOT EXISTS $discounts_table (
                `product_id` bigint(20) unsigned NOT NULL,
                `shop_term_tax_id` bigint(20) unsigned NOT NULL,
                `discount` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                PRIMARY KEY (`product_id`, `shop_term_tax_id`))
                $charset_collate";

                /* Create a table with which variations should be available depending on
                * current shop and parent product
                */
                $variations_sql = "CREATE TABLE IF NOT EXISTS $variations_table (
                `shop_term_tax_id` bigint(20) unsigned NOT NULL,
                `product_id` bigint(20) unsigned NOT NULL,
                `variation_id` bigint(20) unsigned NOT NULL,
                PRIMARY KEY (`shop_term_tax_id`, `variation_id`))
                $charset_collate";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($discounts_sql);
                dbDelta($variations_sql);

                update_option('nw_variations_discounts_tbl_created', 1);
            }
        }

        /**
         * Triggers install action that all components of NW_Craft should use
         */

        public static function install()
        {
            do_action('nw_install');

            // The job runs at midnight, doing the action 'nw_nocte', (only present on internshop)
            if (!wp_next_scheduled('nw_nocte')) {
                $operator = get_option('gmt_offset') > 0 ? '-' : '+';
                wp_schedule_event(strtotime('tomorrow ' . $operator . absint(get_option('gmt_offset')) . ' HOURS'), 'daily', 'nw_nocte');
            }
        }

        /**
         * Triggers uninstall action that all components of NW_Craft should use
         */

        public static function uninstall()
        {
            do_action('nw_uninstall');

            wp_clear_scheduled_hook('nw_nocte');

            //Delete feature options
            foreach (self::PLUGIN_OPTS as $option) {
                delete_option($option);
            }

            delete_option('_nw_admin_notice');
            delete_option('_nw_campaign_email_cache');

            // Delete all cached ASW imports
            global $wpdb;

            $plugin_options = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_nw_asw_import_cache_%'");
            if ($plugin_options) {
                foreach ($plugin_options as $option) {
                    delete_option($option->option_name);
                }
            }

            if (get_option('_nw_shop_feature')) {
                // Delete all carts stored for different shops
                global $wpdb;
                $carts = $wpdb->get_results("SELECT user_id, meta_key FROM $wpdb->usermeta WHERE meta_key LIKE '_nw_cart_%'");
                if ($carts) {
                    foreach ($carts as $cart) {
                        delete_user_meta($cart->user_id, $cart->meta_key);
                    }
                }

                // Delete all stored active shop meta data
                $active_shops = $wpdb->get_results("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_nw_active_shop'");
                if ($active_shops) {
                    foreach ($active_shops as $result) {
                        delete_user_meta($result->user_id, '_nw_active_shop');
                    }
                }
            }
        }

        /**
         * Check plugin dependencies and load text domain
         */

        public static function check_dependencies_and_load_textdomain()
        {
            // Check if WooCommerce is activated
            if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                add_action('admin_notices', function () {
                    $e = __('<b>Newwave plugin</b> requires <b>WooCommerce</b> to work!', 'newwave');
                    printf('<div class="error"><p>%s</p></div>', $e);
                    deactivate_plugins(plugin_basename(__FILE__));
                });
            }

            // Check PHP version
            if (phpversion() < 5.6) {
                add_action('admin_notices', function () {
                    $e = __('<b>Newwave plugin</b> requires at least PHP version 5.6 to work!', 'newwave');
                    printf('<div class="error"><p>%s</p></div>', $e);
                    deactivate_plugins(plugin_basename(__FILE__));
                });
            }

            //Load text domain
            $plugin_path = plugin_basename(dirname(__FILE__) . '/i18n');
            load_plugin_textdomain('newwave', '', $plugin_path);
        }

        public static function nw_plugin_settings_link($links, $file) {
            if ($file === plugin_basename(__FILE__)) {
                $settings_link = '<a href="admin.php?page=nwp_option">Settings</a>';
                array_push($links, $settings_link);
            }
            return $links;
        }

        /**
         * Add plugin settings page
         */

        public static function add_admin_menu()
        {
            add_menu_page(__('Newwave plugin settings','newwave'), __('New Wave settings','newwave'), 'manage_options', 'nwp_option', __CLASS__ . '::plugin_settings_page', 'dashicons-download');
        }

        public static function plugin_settings_page()
        {
            if (!empty($_POST['nw_plugin_nonce']) && wp_verify_nonce($_POST['nw_plugin_nonce'], basename(__FILE__))) {
                foreach (self::PLUGIN_OPTS as $option) {
                    if (isset($_POST[$option])) {
                        $sanitized_opt_val = sanitize_option($option, $_POST[$option]);
                        update_option($option, $sanitized_opt_val);
                    } else {
                        update_option($option, '');
                    }
                }
?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings Saved!', 'newwave'); ?></p>
                </div>
            <?php
            }
            ?>
            <div class="nw-settings wrap">
                <h1 class="nw-settings-heading"><?= __('Newwave plugin settings', 'newwave'); ?></h1>
                <hr>

                <form method="post" action="">
                    <?php wp_nonce_field(basename(__FILE__), 'nw_plugin_nonce'); ?>
                    <nav class="nav-tab-wrapper">
                        <a href="#" class="nav-tab nav-tab-active" data-tab="general">
                            <?= __('General','newwave'); ?>
                        </a>
                        <a href="#" class="nav-tab" data-tab="product">
                            <?= __('Product synchronization','newwave'); ?>
                        </a>
                        <a href="#" class="nav-tab" data-tab="stock">
                            <?= __('Stock synchronization','newwave'); ?>
                        </a>
                        <a href="#" class="nav-tab" data-tab="order">
                            <?= __('Order synchronization','newwave'); ?>
                        </a>
                        <a href="#" class="nav-tab" data-tab="order-status">
                            <?= __('Order status tracking (Nshift/BRING)','newwave'); ?>
                        </a>
                        <a href="#" class="nav-tab" data-tab="order-reports">
                            <?= __('Export order reports','newwave'); ?>
                        </a>
                    </nav>

                    <table class="form-table">

                        <!--Nshift tracking API -->
                        <tr class="nw-tab-content" data-panel-content="order-status">
                            <th scope="row" class="nw-main-feature">
                                <?= __('Enable Order Status Tracking', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="checkbox" name="_nw_order_tracking_enabled" value="1" <?php checked(get_option('_nw_order_tracking_enabled'), 1); ?> />
                            </td>
                        </tr>

                        <tr class="info nw-tab-content" data-panel-content="order-status">
                            <td>
                                <?= __('Enables processesing of tracking information and updating order status.','newwave');?>
                                <br><br>
                                <?php 
                                    printf(__('Configurations for order status tracking can be added <a href="%s">here</a>.','newwave'), menu_page_url(MY_PLUGIN_SLUG, false) .'&tab=integration&section=nshift-tracking');
                                ?>
                                <br>
                                <?= __('Please enable this feature before adding configurations.','newwave');?>
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-status">
                            <th scope="row" class="nw-main-feature">
                                <?= __('Order tracking type', 'newwave'); ?>
                            </th>
                            <td>
                                <?php
                                $order_tracking_type = get_option('_nw_order_tracking_type');
                                ?>
                                <select name="_nw_order_tracking_type" data-parent="_nw_order_tracking_enabled">
                                    <option value="nshift" <?= $order_tracking_type == 'nshift' ? 'selected' : ''; ?>>Nshift</option>
                                    <option value="bring" <?= $order_tracking_type == 'bring' ? 'selected' : ''; ?>>Bring</option>
                                </select>
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-status">
                            <th scope="row">
                                <?= __('POSTEN URL', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="Bring display status URL"></span>
                            </th>
                            <td><input type="text" name="_nw_bring_posten_url" value="<?php echo get_option('_nw_bring_posten_url'); ?>" data-parent="_nw_order_tracking_enabled" <?php echo get_option('_nw_order_tracking_enabled') ? '' : 'disabled'; ?> /></td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-status">
                            <th scope="row">
                                <?= __('BRING API URL', 'newwave'); ?>
                            </th>
                            <td><input type="text" name="_nw_bring_api_url" value="<?php echo get_option('_nw_bring_api_url'); ?>" data-parent="_nw_order_tracking_enabled" <?php echo get_option('_nw_order_tracking_enabled') ? '' : 'disabled'; ?> /></td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-status">
                            <th scope="row">
                                <?= __('BRING order prefix', 'newwave'); ?>
                            </th>
                            <td><input type="text" name="_nw_bring_order_prefix" value="<?php echo get_option('_nw_bring_order_prefix'); ?>" data-parent="_nw_order_tracking_enabled" <?php echo get_option('_nw_order_tracking_enabled') ? '' : 'disabled'; ?> /></td>
                        </tr>

                        <!-- Nshift tracking API end -->

                        <!-- Order export API -->
                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row" class="nw-main-feature">
                                <?= __('Enable Order Synchronization', 'newwave'); ?>
                            </th>
                            <td><input type="checkbox" name="_nw_order_export_enabled" value="1" <?php checked(get_option('_nw_order_export_enabled'), 1); ?> /></td>
                        </tr>

                        <!-- Help text for order export feature -->
                        <tr class="info nw-tab-content" data-panel-content="order">
                            <td colspan="12">
                                <i>
                                    <ul>
                                        <li><?= __('Enables the upload of new purchase orders to ASW', 'newwave'); ?></li>
                                    </ul>
                                </i>
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row">
                                <?= __('Order export API url', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enter API endpoint URL', 'newwave'); ?>"></span>
                            </th>
                            <td><input type="url" name="_nw_order_export_api_url" value="<?php echo esc_attr(get_option('_nw_order_export_api_url')); ?>" data-parent="_nw_order_export_enabled" <?php echo get_option('_nw_order_export_enabled') ? '' : 'disabled'; ?> /></td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row">
                                <?= __('Order prefix', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enter the prefix to be used for order export', 'newwave'); ?>"></span>
                            </th>
                            <td><input type="text" name="_nw_order_export_api_prefix" value="<?php echo esc_attr(get_option('_nw_order_export_api_prefix')); ?>" data-parent="_nw_order_export_enabled" <?php echo get_option('_nw_order_export_enabled') ? '' : 'disabled'; ?> /></td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row">
                                <?= __('Order export API interval', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enter the interval (in time units) for order API synchronization', 'newwave'); ?>"></span>
                            </th>
                            <td><input type="number" name="_nw_order_api_interval" value="<?php echo esc_attr(get_option('_nw_order_api_interval')); ?>" data-parent="_nw_order_export_enabled" <?php echo get_option('_nw_order_export_enabled') ? '' : 'disabled'; ?> min="1"/></td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row">
                                <?= __('Order export API token', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enter the authentication token for the order export API', 'newwave'); ?>"></span>
                            </th>
                            <td><input type="text" name="_nw_order_api_token" value="<?php echo esc_attr(get_option('_nw_order_api_token')); ?>" data-parent="_nw_order_export_enabled" <?php echo get_option('_nw_order_export_enabled') ? '' : 'disabled'; ?> /></td>
                        </tr>

                        <?php 
                            if(!get_option('_nw_shop_feature')){
                        ?>
                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row">
                                <?= __('Maximum number of orders to export per cron cycle', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Set the number of orders to be exported per cron cycle', 'newwave'); ?>"></span>
                            </th>
                            <td><input type="number" min="1" name="_nw_order_threshold" value="<?php echo esc_attr(get_option('_nw_order_threshold')); ?>" data-parent="_nw_order_export_enabled" <?php echo get_option('_nw_order_export_enabled') ? '' : 'disabled'; ?> /></td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row">
                                <?= __('Default ASW customer code for this site', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="text" name="_nw_export_default_cust" value="<?php echo esc_attr(get_option('_nw_export_default_cust')); ?>" data-parent="_nw_order_export_enabled" <?php echo get_option('_nw_order_export_enabled') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row">
                                <?= __('Customer code if the payment type is vipps', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="text" name="_nw_export_vipps_cust" value="<?php echo esc_attr(get_option('_nw_export_vipps_cust')); ?>" data-parent="_nw_order_export_enabled" <?php echo get_option('_nw_order_export_enabled') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row">
                                <?= __('Shipping post codes (comma seperated list)', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="eg. 9170, 9171, 9172, 9173">
                            </th>
                            <td>
                                <input type="text" name="_nw_export_shipping_postcodes" value="<?php echo esc_attr(get_option('_nw_export_shipping_postcodes')); ?>" data-parent="_nw_order_export_enabled" <?php echo get_option('_nw_order_export_enabled') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order">
                            <th scope="row">
                                <?= __('Customer code for above shipping postcodes', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="text" name="_nw_export_shipp_cust" value="<?php echo esc_attr(get_option('_nw_export_shipp_cust')); ?>" data-parent="_nw_order_export_enabled" <?php echo get_option('_nw_order_export_enabled') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>
                        <?php 
                            }
                        ?>
                        <!-- Order export API end -->

                        <!-- Stock API-->
                        <tr class="nw-tab-content" data-panel-content="stock">
                            <th scope="row" class="nw-main-feature">
                                <?= __('Enable Stock Synchronization', 'newwave'); ?>
                            </th>
                            <td><input type="checkbox" name="_nw_stock_sync_enabled" value="1" <?php checked(get_option('_nw_stock_sync_enabled')); ?> /></td>
                        </tr>

                         <!-- Help text for stock import feature -->
                         <tr class="info nw-tab-content" data-panel-content="stock">
                            <td colspan="12">
                                <i>
                                    <ul>
                                        <li>
                                            <?php printf(__('Synchronizes stock status and stock quantity from warehouse to with %s','newwave'), get_bloginfo('name')); ?>
                                        </li>
                                    </ul>
                                </i>
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="stock">
                            <th scope="row">
                                <?= __('Stock API type', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Choose the API type for stock synchronization (RPC or GraphQL)', 'newwave'); ?>">
                            </th>
                            <td>
                                <?php
                                $api_type = get_option('_nw_stock_api_type');
                                ?>
                                <select name="_nw_stock_api_type" data-parent="_nw_stock_sync_enabled">
                                    <option value="rpc" <?= $api_type == 'rpc' ? 'selected' : ''; ?>>RPC</option>
                                    <option value="graphql" <?= $api_type == 'graphql' ? 'selected' : ''; ?>>GraphQL</option>
                                </select>
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="stock">
                            <th scope="row">
                                <?= __('Stock API url', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enter the URL for the stock synchronization API', 'newwave'); ?>">
                            </th>
                            <td><input type="url" name="_nw_stock_api_url" value="<?php echo esc_attr(get_option('_nw_stock_api_url')); ?>" data-parent="_nw_stock_sync_enabled" <?php echo get_option('_nw_stock_sync_enabled') ? '' : 'disabled'; ?> /></td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="stock">
                            <th scope="row">
                                <?= __('Stock API interval', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enter the interval (in time units) for stock API synchronization', 'newwave'); ?>">
                            </th>
                            <td><input type="number" name="_nw_stock_api_interval" value="<?php echo esc_attr(get_option('_nw_stock_api_interval')); ?>" data-parent="_nw_stock_sync_enabled" <?php echo get_option('_nw_stock_sync_enabled') ? '' : 'disabled'; ?> min="1"/></td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="stock">
                            <th scope="row">
                                <?= __('Stock API token', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enter the authentication token for the stock synchronization API', 'newwave'); ?>">
                            </th>
                            <td><input type="text" name="_nw_stock_api_token" value="<?php echo esc_attr(get_option('_nw_stock_api_token')); ?>" data-parent="_nw_stock_sync_enabled" <?php echo get_option('_nw_stock_sync_enabled') ? '' : 'disabled'; ?> /></td>
                        </tr>
                        <!-- Stock API end-->

                        <!-- Product import API -->
                        <tr class="nw-tab-content" data-panel-content="product">
                            <th scope="row" class="nw-main-feature">
                                <?= __('Enable Product Synchronization', 'newwave') ?>
                            </th>
                            <td>
                                <input type="checkbox" name="_nw_product_import_enabled" value="1" <?php checked(get_option('_nw_product_import_enabled'), 1); ?> />
                            </td>
                        </tr>

                        <!-- Help text for product import feature -->
                        <tr class="info nw-tab-content" data-panel-content="product">
                            <td colspan="12">
                                <i>
                                    <ul>
                                        <li>
                                            <?= __('1. Imports product from warehouse using a products article number.', 'newwave'); ?>
                                        </li>
                                        <li>
                                        <?= __('2. Enables the ability to change product variants status, set featured image for main prouduct and it\'s variants, set published date, selectively import product variants, set product description, map product to categories, adds tags for the product etc.', 'newwave'); ?>
                                        </li>
                                        <li>
                                            <?= __(' 3. Add product material, concept and attribute icons for the product. (<b>Please note:</b> Product properties feature should be enabled to activate this.)', 'newwave'); ?>
                                        </li>
                                        <li><?= __('4. Enables mapping of all product types(Stock, stock item with logo and special item) to club(s). (<b>Please note:</b> Shop feature must be enabled to activate this.)', 'newwave'); ?>
                                        </li>
                                    </ul>
                                </i>
                                <br>
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="product">
                            <th scope="row">
                                <?= __('API type', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Choose the API type for product synchronization (RPC or GraphQL)', 'newwave'); ?>">
                            </th>
                            <td>
                                <?php
                                $api_type = get_option('_nw_api_type');
                                ?>
                                <select name="_nw_api_type" data-parent="_nw_product_import_enabled">
                                    <option value="rpc" <?= $api_type == 'rpc' ? 'selected' : ''; ?>>RPC</option>
                                    <option value="graphql" <?= $api_type == 'graphql' ? 'selected' : ''; ?>>GraphQL</option>
                                </select>
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="product">
                            <th scope="row">
                                <?= __('ASW Endpoint', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enter the endpoint URL for ASW', 'newwave'); ?>">
                            </th>
                            <td>
                                <input type="url" name="_nwp_asw_endpoint_url" value="<?php echo esc_attr(get_option('_nwp_asw_endpoint_url')); ?>" data-parent="_nw_product_import_enabled" <?php echo get_option('_nw_product_import_enabled') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="product">
                            <th scope="row">
                                <?= __('ASW Auth Token', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enter the authentication token for ASW', 'newwave'); ?>">
                            </th>
                            <td>
                                <input type="text" name="_nwp_asw_auth_token" value="<?php echo esc_attr(get_option('_nwp_asw_auth_token')); ?>" data-parent="_nw_product_import_enabled" <?php echo get_option('_nw_product_import_enabled') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <!-- Export order reports -->
                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row" class="nw-main-feature">
                                <?= __('Enable CSV export of order reports', 'newwave') ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('Enable export of excel sheets of sales to Newwave FTP server', 'newwave'); ?>">
                                </span>
                            </th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_export_sales_csv" <?php checked(get_option('_nw_export_sales_csv'), 1); ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row">
                                <?= __('FTP server', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="text" name="_nw_export_csv_ftp" value="<?php echo esc_attr(get_option('_nw_export_csv_ftp')); ?>" data-parent="_nw_export_sales_csv" <?php echo get_option('_nw_export_sales_csv') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row">
                                <?= __('FTP login username', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="text" name="_nw_export_csv_ftp_user" value="<?php echo esc_attr(get_option('_nw_export_csv_ftp_user')); ?>" data-parent="_nw_export_sales_csv" <?php echo get_option('_nw_export_sales_csv') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row">
                                <?= __('FTP login password', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="password" name="_nw_export_csv_ftp_pass" value="<?php echo esc_attr(get_option('_nw_export_csv_ftp_pass')); ?>" data-parent="_nw_export_sales_csv" <?php echo get_option('_nw_export_sales_csv') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row">
                                <?= __('CSV file name', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="<?= __('The export date will be automatically appeneded to the file name provided here.', 'newwave'); ?>">
                                </span>
                            </th>
                            <td>
                                <input type="text" name="_nw_export_csv_filename" value="<?php echo esc_attr(get_option('_nw_export_csv_filename')); ?>" data-parent="_nw_export_sales_csv" <?php echo get_option('_nw_export_sales_csv') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <?php 
                            if(get_option('_nw_shop_feature')){
                        ?>
                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row">
                                <?= __('Default ASW customer code for this site', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="number" name="_nw_export_csv_default_cust" value="<?php echo esc_attr(get_option('_nw_export_csv_default_cust')); ?>" data-parent="_nw_export_sales_csv" <?php echo get_option('_nw_export_sales_csv') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row">
                                <?= __('Customer code if the payment type is vipps', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="number" name="_nw_export_csv_vipps_cust" value="<?php echo esc_attr(get_option('_nw_export_csv_vipps_cust')); ?>" data-parent="_nw_export_sales_csv" <?php echo get_option('_nw_export_sales_csv') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row">
                                <?= __('Shipping post codes (comma seperated list)', 'newwave'); ?>
                                <span class="dashicons dashicons-editor-help" data-toggle="tooltip" data-placement="top" title="eg. 9170, 9171, 9172, 9173">
                            </th>
                            <td>
                                <input type="text" name="_nw_export_csv_shipping_postcodes" value="<?php echo esc_attr(get_option('_nw_export_csv_shipping_postcodes')); ?>" data-parent="_nw_export_sales_csv" <?php echo get_option('_nw_export_sales_csv') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row">
                                <?= __('Customer code for above shipping postcodes', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="number" name="_nw_export_csv_shipp_cust" value="<?php echo esc_attr(get_option('_nw_export_csv_shipp_cust')); ?>" data-parent="_nw_export_sales_csv" <?php echo get_option('_nw_export_sales_csv') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>
                        <?php 
                            }
                        ?>

                        <tr class="nw-tab-content" data-panel-content="order-reports">
                            <th scope="row">
                                <?= __('Land code', 'newwave'); ?>
                            </th>
                            <td>
                                <input type="text" name="_nw_export_csv_land_code" value="<?php echo esc_attr(get_option('_nw_export_csv_land_code')); ?>" data-parent="_nw_export_sales_csv" <?php echo get_option('_nw_export_sales_csv') ? '' : 'disabled'; ?> />
                            </td>
                        </tr>
                        <!-- Export order reports end -->

                        <!-- General features -->
                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row" class="nw-main-feature"><?= __('General features', 'newwave') ?></th>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row">
                                <?= __('Enable Group, vendor and club management', 'newwave') ?>
                            </th>
                            <td>
                                <input class="feature-cb" type="checkbox" value="1" name="_nw_shop_feature" <?php checked(get_option('_nw_shop_feature'), 1); ?> />
                            </td>
                        </tr>

                        <!-- Help text for shop feature -->
                        <tr class="info nw-tab-content active" data-panel-content="general">
                            <td colspan="12">
                                <i>
                                    <ul>
                                        <li><?= __('1. Register taxonomies for product variations and shop orders.', 'newwave'); ?></li>
                                        <li><?= __('2. Register a custom post type (CPT) for sport banners.', 'newwave'); ?></li>
                                        <li><?= __('3. Register product types for stock items, stock items with logos, and special items.', 'newwave'); ?></li>
                                        <li><?= __('4. Adds campaign feature.', 'newwave'); ?></li>
                                        <li><?= __('5. Provide support for customer returns.', 'newwave'); ?></li>
                                        <li><?= __('6. Handle user registration.', 'newwave'); ?></li>
                                        <li><?= __('7. Enables the ability to add discounted price on dealer + shop level and add price for printing.', 'newwave'); ?></li>
                                    </ul>
                                </i>
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable Product Properties: Material, concept and attribute icons', 'newwave') ?></th>
                            <td>
                                <input class="feature-cb" type="checkbox" value="1" name="_nw_feature_properties" <?php checked(get_option('_nw_feature_properties'), 1); ?> />
                            </td>
                        </tr>

                        <!-- Help text for product properties feature -->
                        <tr class="info nw-tab-content active" data-panel-content="general">
                            <td colspan="12">
                                <i>
                                    <ul>
                                        <li><?= __('1. Introduces product concepts (elite/performance/active/ctm).', 'newwave') ?></li>
                                        <li><?= __('2. Manages the "Material" custom product attribute for WooCommerce products.', 'newwave') ?></li>
                                        <li><?= __('3. Introduces a custom post type "nw_attribute_icon" for setting and displaying product properties on the product page, to visually represent certain attributes the product possesses.', 'newwave') ?></li>
                                    </ul>
                                </i>
                            </td>
                        </tr>

                        <!-- Product types -->
                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Product types', 'newwave') ?></th>
                            <td>
                                <?php
                                    $product_types = get_option('_nw_product_types', array());

                                    $options = array(
                                        'variable'       => 'Default(variable)',
                                        'nw_stock'       => 'Stock',
                                        'nw_stock_logo'  => 'Stock with logo',
                                        'nw_special'     => 'Special',
                                    );

                                    foreach ($options as $key => $label) {
                                        $checked = is_array($product_types) && in_array($key, $product_types) ? 'checked' : '';
                                        echo '<label><input type="checkbox" name="_nw_product_types[]" value="' . esc_attr($key) . '" ' . $checked . '>' . esc_html($label) . '</label><br>';
                                    }
                                ?>
                            </td>
                        </tr>

                        <tr class="info nw-tab-content active" data-panel-content="general">
                            <td colspan="12">
                                <i>
                                    <b>
                                    <?= __('Note: Before changing the product types, please take a database backup. This action would update the existing product types to "variable" if Default (variable) is selected or "nw_stock" if Stock/Stock with logo/Special is selected.', 'newwave') ?>
                                    </b>
                                </i>
                                <br>
                                <?php 
                                    if(is_array($product_types)){
                                ?>
                                <i>Run custom script to update existing product types to 
                                    <b>
                                    <?php 
                                    if(in_array('variable', $product_types)){
                                        $update_ptype = 'variable';
                                    }else if(array_intersect(array("nw_stock","nw_stock_logo","nw_special"), array_keys($options))){
                                        $update_ptype = 'nw_stock';
                                    }
                                    echo $update_ptype;
                                    ?>
                                    </b> >>> 
                                    <a href="<?= admin_url('admin.php?page=nwp_option&update_product_type='.$update_ptype)?>">Run</a>
                                </i>
                                <?php } ?>
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable Coupons', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_feature_coupon" <?php checked(get_option('_nw_feature_coupon'), 1); ?> />
                            </td>
                        </tr>

                        <!-- Help text for coupons feature -->
                        <tr class="info nw-tab-content active" data-panel-content="general">
                            <td colspan="12">
                                <i><?= __('Adds newwave coupons.', 'newwave') ?></i>
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable color attribute images', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_feature_color_attr" <?php checked(get_option('_nw_feature_color_attr'), 1); ?> />
                            </td>
                        </tr>

                        <!-- Help text for color attribute images feature -->
                        <tr class="info nw-tab-content active" data-panel-content="general">
                            <td colspan="12">
                                <i><?= __('Manages the updating of image-color associations for a product and represents product colors using thumbnail versions of their respective product images.', 'newwave') ?>
                                </i>
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable category images', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_feature_cat_imgs" <?php checked(get_option('_nw_feature_cat_imgs'), 1); ?> />
                            </td>
                        </tr>

                        <!-- Help text for category images feature -->
                        <tr class="info nw-tab-content active" data-panel-content="general">
                            <td colspan="12">
                                <i><?= __('Register category images as a custom post type.', 'newwave') ?>
                                </i>
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable purechat', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_feature_purechat" <?php checked(get_option('_nw_feature_purechat'), 1); ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable klarna product images', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_klarna_product_imgs" <?php checked(get_option('_nw_klarna_product_imgs'), 1); ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Remove grouped and external product types', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_remove_product_type" <?php checked(get_option('_nw_remove_product_type'), 1); ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Remove reviews from product', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_remove_product_reviews" <?php checked(get_option('_nw_remove_product_reviews'), 1); ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable login redirect', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_login_redirect" <?php checked(get_option('_nw_login_redirect'), 1); ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable registration redirect', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_reg_redirect" <?php checked(get_option('_nw_reg_redirect'), 1); ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable product brand', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_product_brand_name" <?php checked(get_option('_nw_product_brand_name'), 1); ?> />
                            </td>
                        </tr>

                        <tr class="nw-tab-content active" data-panel-content="general">
                            <th scope="row"><?= __('Enable "show out of stock variants to users" feature on product edit page > Product data> General section', 'newwave') ?></th>
                            <td>
                                <input type="checkbox" value="1" name="_nw_oos_feature" <?php checked(get_option('_nw_oos_feature'), 1); ?> />
                            </td>
                        </tr>
                        <!--General features end -->

                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
<?php
        }

        public static function enqueue_admin_scripts()
        {
            wp_enqueue_script('nw_admin', NW_PLUGIN_URL . 'assets/js/nw_admin.js', array('jquery'), filemtime(NW_PLUGIN_DIR . '/assets/js/nw_admin.js'));
            NWP_Functions::enqueue_style('helper.css');
        }

        /**
         * Redirect customer to home page after login
         *
         * @param string $url
         * @return string
         */

        public static function login_redirect($url)
        {
            $redirect_page_id = url_to_postid($url);
            $checkout_page_id = wc_get_page_id('checkout');

            // If registered during checkout, do not change redirect
            if ($redirect_page_id == $checkout_page_id) {
                return $url;
            }
            return get_home_url();
        }

        /**
         * Redirect customer to home page after registration
         *
         * @param string $url
         * @return string
         */

        public static function registration_redirect($url)
        {
            return get_home_url();
        }

        /**
         * Enqueue custom front end assets
         */
        public static function enqueue_front_end_assets()
        {
            NWP_Functions::enqueue_style('nw_frontend.css');
            NWP_Functions::enqueue_script('nw_frontend.js', array(), false);
            wp_localize_script('nw_nw_frontend', 'adminajax', array('ajax_url' => admin_url('admin-ajax.php')));
        }

        /**
         * Remove unused menu items in the WP Admin menu
         */
        public static function remove_menu_items()
        {
            // remove_menu_page('edit.php');
            // remove_menu_page('edit.php?post_type=featured_item');
            // remove_menu_page('edit-comments.php');
        }

        /**
         * Register admin page for use for the custom post types
         *
         */
        public static function add_admin_page()
        {
            add_menu_page(
                __('Shops', 'newwave'),
                __('Shops', 'newwave'),
                'manage_options',
                'newwave',
                '', // No callback function, use standard WP admin page
                'dashicons-store',
                55 // Position priority in admin
            );
        }

        /**
         * Add a SKU and show out of stock varition toggle field to the 'general' panel
         */
        public static function extend_general_panel(){
            global $product_object;
            $checked = (get_post_meta($product_object->get_id(), "_nw_show_oos_variants", true) == 1) ? "checked" : "";
            ?>
            <div class="options_group ">
                <?php 
                if (is_a($product_object, 'WC_Product_NW_Base') || is_a($product_object, 'WC_Product_NWP_Base')) {
                ?>
                <p class="form-field _regular_price_field">
                    <label for="nw_sku"><?php _e('Product article number', 'newwave'); ?></label>
                    <input type="text" id="nw_sku" value="<?php echo $product_object->get_sku(); ?>" disabled />
                    <?php echo wc_help_tip(__('ASW Article Number - all products in WooCommerce with the same number will share the same Stock Control settings.', 'newwave')); ?>
                </p>
                <?php
                }
                ?>
                <?php if(get_option('_nw_oos_feature')) { ?>
                <p class="form-field _show_out_of_stock_variant">
                    <label for="nw_oos_variant"><?php _e('Show Out of stock variants to users', 'newwave'); ?></label>
                    <input type="checkbox" id="nw_oos_variant" name="nw_oos_variant" value="1" <?php echo $checked;?>/>
                    <?php echo wc_help_tip(__('Check this box if this product need to show out of stock variants to users.', 'newwave' )); ?>
                </p>
                <?php } ?>
            </div> 
            <?php
        }

        /**
         * Save show out of stock toggle option
         * @param WC_Product $product
         */
        public static function save($product)
        {
            $nw_oos_variant_value = "0";
            if(isset($_POST['nw_oos_variant'])){
                $nw_oos_variant_value = "1";
            }
            if(get_post_meta($product->get_id(), '_nw_show_oos_variants', true) == ""){
                add_post_meta($product->get_id(), '_nw_show_oos_variants', $nw_oos_variant_value);
            }else{
                update_post_meta($product->get_id(), '_nw_show_oos_variants', $nw_oos_variant_value);
            }
        }

        /**
         * Update the product type of existing products to variable/nw_stock
         */
        public static function nw_update_product_type(){
            if(isset($_GET['update_product_type'])){
                $update_ptype = sanitize_text_field($_GET['update_product_type']);

                if(in_array($update_ptype,array('variable','nw_stock'))){
                    $q = new WP_Query(array(
                        'post_type' => 'product',
                        'posts_per_page' => -1,
                        'post_status' => 'publish'
                    ));

                    echo "Total product count: " . $q->post_count . '<br>';
                    
                    foreach ($q->posts as $post) {
                        // echo ' Post ID = ' . $post->ID;
                        $product = wc_get_product($post->ID);
                        // echo ' Product type = ' . $product->get_type();
                        wp_set_object_terms($post->ID, $update_ptype, 'product_type');
                    }

                    echo '<div class="notice notice-success is-dismissible">
                            <p>Existing product types have been updated</p>
                        </div>';

                    wp_safe_redirect(admin_url('admin.php?page=nwp_option&&update_product_type=complete'));
                    exit;
                }

                if($update_ptype === 'complete'){
                    echo '<div class="notice notice-success is-dismissible">
                            <p>Existing product types have been updated!</p>
                        </div>';
                }
            }
        }
    }

    NW_Plugin::init();

endif;
