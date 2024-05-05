<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Main access logic of the plugin; filter contents based on current shop for customer,
     * such as products, price, images etc.
     *
     */
    class NW_Session
    {

        /**
         * @var string Session duration - 2 hours
         */
        const SESSION_DURATION = 7200;

        /**
         * @var string Cookie Expiration - 30 days
         */
        const COOKIE_EXPIRATION = 2592000;

        /**
         * @var string Current shop for logged in user
         */
        public static $shop = null;

        /**
         * @var string Current shop ids for current shop (club, vendor and group ids)
         */
        public static $shop_ids = null;

        /**
         * @var string Cached image access
         */
        static $image_access_cache = array();

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Set current shop when user logs in
            add_action('wp_login', __CLASS__ . '::user_login', 1, 2);

            // Load the shop from DB based on the post ID stored in WC session
            add_action('woocommerce_init', __CLASS__ . '::start_session', 10);

            // Set a custom cookie expiration
            add_filter('auth_cookie_expiration', __CLASS__ . '::set_cookie_logout', 99, 3);

            // Filter products
            add_action('pre_get_posts', __CLASS__ . '::filter_posts', 99, 1);

            /* Always hide variations if for some reason filter_variations fails.
		 * Second condition of if statement at get_available_variations() in woocommerce/includes/class-wc-product-variable.php
		 */
            add_filter('woocommerce_hide_invisible_variations', '__return_true', 99, 1);

            // Decide whether a particular variation should be visible
            add_filter('woocommerce_variation_is_visible', __CLASS__ . '::filter_variations', 99, 4);

            // Filter the price of nw_stock products and its variations
            add_filter('nw_stock_price', __CLASS__ . '::filter_price', 99, 2);
            add_filter('woocommerce_product_variation_get_price', __CLASS__ . '::filter_price', 99, 2);

            // Calculate campaign price if campaign is active
            add_filter('nw_stock_campaign_price', __ClASS__ . '::calc_campaign_price', 99, 2);

            // Whether a particular nw_stock product is on sale
            add_filter('nw_product_stock_on_sale', __CLASS__ . '::on_sale', 10, 2);

            // Hide add-to-cart button if product cannot be purchased
            add_action('woocommerce_before_single_product', __CLASS__ . '::hide_add_to_cart_if_applicable', 99);

            // Redirect to shop after successful login or registration
            add_action('woocommerce_login_redirect', __CLASS__ . '::login_and_register_redirect', 99);
            add_action('woocommerce_registration_redirect', __CLASS__ . '::login_and_register_redirect', 99);

            // Redirect to front page when customer logs out
            add_action('wp_logout', __CLASS__ . '::logout_redirect', 99);

            // Replace the categories widget Walker class with a custom one
            add_filter('woocommerce_product_categories_widget_args', __CLASS__ . '::set_cat_walker_class', 1, 1);

            // Customize the 'sort by discount' query (since it depends on the current shop)
            add_filter('posts_clauses', __CLASS__ . '::sort_by_discounts', 99, 1);



            /**
             * Increase maximum number of variations a product can have before
             * they should be checked thorugh ajax and not pre-HTML-rendering.
             * If only X variations should be shown for a club, X variations is the ajax threshold,
             * not the total amount of variations regardless of club
             *
             */
            add_filter('woocommerce_ajax_variation_threshold', function ($threshold) {
                return 60;
            }, 99);

            // Add in hooks to replace nw_stock products main image if applicable
            add_action('template_redirect', __CLASS__ . '::register_image_replace_hooks', 99);

            // Customize the 'related products query' to only show products belonging to the current $shop
            add_filter('woocommerce_product_related_posts_query', __CLASS__ . '::filter_related_products_query', 99, 1);

            // Filter what term tax ids to search for when getting the available variations for a nw_stock product
            add_filter('newwave_stock_variations_shop_term_tax_ids', __CLASS__ . '::filter_variation_ids', 99, 2);

            // If an error occured, redirect to front page and display an error notice
            add_action('template_redirect', __CLASS__ . '::display_error_and_logout', 99);

            // Add a shop switcher in the admin menu bar
            add_filter('admin_bar_menu', __CLASS__ . '::admin_bar_shop_select', 99, 1);

            // Set current shop when user logs in
            add_action('template_redirect', __CLASS__ . '::club_access_code', 1, 2);
        }

        /**
         * Store id of the first shop registered to user in WooCommerce session,
         * regardless if it's activated or not. NW_Session::start_session handles that
         *
         * @param string $username
         * @param WP_User $user
         */
        public static function user_login($username, $user)
        {
            if (is_admin())
                return;
            //exit;

            if (!current_user_can('manage_woocommerce')) {
                if (!WC()->session->has_session()) {
                    WC()->session->set_customer_session_cookie(true);
                }

                //echo "==========".$shop_id = get_user_meta($user->ID, '_nw_active_shop', true);
                $logger = wc_get_logger();
                $logger->info(wc_print_r('Alert Shiop UD ', true), array('source' => 'Debug-login-logs'));
                //exit;
                if (!isset($shop_id)) {
                    $all_shop_ids = NWP_Functions::unpack_list(get_user_meta($user->ID, '_nw_shops', true));

                    if (is_array($all_shop_ids) && !empty($all_shop_ids)) {
                        $shop_id = $all_shop_ids[0];
                        update_user_meta(get_current_user_id(), '_nw_active_shop', $shop_id);
                    }
                } else {
                    $logger->info(wc_print_r($shop_id . ' guihi', true), array('source' => 'Debug-login-logs'));
                }
                //echo $shop_id.' sassa';exit;
                if (isset($shop_id)) {
                    //	echo $shop_id;
                    WC()->session->set('nw_shop', $shop_id);
                    return;
                }

                WC()->session->set('nw_error_msg', __('You are no longer registered in a club. Get in touch with the webadministrator for help.', 'newwave'));
                wp_redirect(home_url());
                exit;
            }
        }

        /**
         * Main access logic; validates that user has access to shop if
         *
         */
        public static function start_session()
        {
            //echo "dasdas";exit;
            // Is in admin area; or we're not doing an ajax call
            $logger = wc_get_logger();
            if (NWP_Functions::is_backend()) {
                return;
            }


            // print_r(WC()->session);
            // Get the user ID that was set upon login or switch
            if (WC()->session && WC()->session->has_session()) {
                //echo get_current_user_id().' user';
                $shop_id = WC()->session->get('nw_shop');
                //echo $shop_id.'asssssdasd';
                if (!$shop_id) {

                    $shop_id = get_user_meta(get_current_user_id(), '_nw_active_shop', true);
                    //	echo $shop_id.'asdasd';
                    if (!$shop_id) {
                        $all_shop_ids = NWP_Functions::unpack_list(get_user_meta(get_current_user_id(), '_nw_shops', true));
                        //print_r($all_shop_ids);
                        if (is_array($all_shop_ids) && !empty($all_shop_ids)) {
                            $shop_id = $all_shop_ids[0];
                            update_user_meta(get_current_user_id(), '_nw_active_shop', $shop_id);
                        }
                    }
                    //echo $shop_id.' dasdsad';exit;
                    if ($shop_id) {
                        WC()->session->set('nw_shop', $shop_id);
                    }
                }

                $logger->info(wc_print_r('IN SESS ', true), array('source' => 'Debug-login-logs'));
                $logger->info(wc_print_r($shop_id . ' sess', true), array('source' => 'Debug-login-logs'));
            } else {

                $shop_id = 0;

                $logger->info(wc_print_r('IN S zero', true), array('source' => 'Debug-login-logs'));
                $logger->info(wc_print_r($shop_id . ' S', true), array('source' => 'Debug-login-logs'));
            }
            //echo $shop_id.'dasdasssssssd';exit;

            $previous_shop_id = false;

            // If user is admin
            if (current_user_can('manage_woocommerce')) {
                NWP_Functions::enqueue_script('helper', array('select2'));
                NWP_Functions::enqueue_style('admin_bar_front_end.css');

                // Admin is switching between shops
                if (isset($_POST['_nw_admin_switch_shop_nonce']) && wp_verify_nonce($_POST['_nw_admin_switch_shop_nonce'], 'nw_admin_switch_shop') && isset($_POST['nw_admin_switch_shop_id'])) {
                    $previous_shop_id = $shop_id;
                    $shop_id = absint($_POST['nw_admin_switch_shop_id']);
                }
            }

            // If for some reason no shop id is set, but user has a session -> logout
            else if (!$shop_id && WC()->session && WC()->session->has_session()) {
                if (!WC()->session->get('nw_error_msg')) {
                    WC()->session->set('nw_error_msg', __('Woops, something went wrong. Try to log in again.', 'newwave'));
                }
                return;
            }

            $shop = new NW_Shop_Club($shop_id);



            if ($shop) {
                $logger->info(wc_print_r('Shoip fpimd', true), array('source' => 'Debug-login-logs'));
                $logger->info(wc_print_r($shop_id . ' found', true), array('source' => 'Debug-login-logs'));
            }

            // If shop has been activated during user session, find next valid or logout
            if (!$shop->is_activated() && !current_user_can('manage_woocommerce')) {
                $logger->info(wc_print_r('Shoip active', true), array('source' => 'Debug-login-logs'));
                $shop_ids = NWP_Functions::unpack_list(get_user_meta(get_current_user_id(), '_nw_shops', true));
                foreach ($shop_ids as $id) {
                    if ($id == $shop_id) // skip checking the shop that triggered this if statement
                        continue;

                    $switch_shop = new NW_Shop_Club($id);
                    if ($switch_shop->is_activated()) {
                        $previous_shop_id = $shop_id;
                        $shop_id = $id;
                        $shop = $switch_shop;
                        break;
                    }
                }

                if (!$shop->is_activated() && WC()->session && WC()->session->has_session() && empty(WC()->session->get('nw_error_msg'))) {
                    WC()->session->set('nw_error_msg', __("Your club shop has been deactivated. Get in touch with your club representative to find out more.", 'newwave'));
                }
            }


            // Customer is switching between shops
            if (isset($_POST['_nw_switch_shop_nonce']) && wp_verify_nonce($_POST['_nw_switch_shop_nonce'], 'nw_switch_shop') && isset($_POST['nw_switch_shop_id'])) {
                $logger->info(wc_print_r('In SWITCH', true), array('source' => 'ALongs-login-logs'));
                $shop_id = absint($_POST['nw_switch_shop_id']);
                $shop_ids = NWP_Functions::unpack_list(get_user_meta(get_current_user_id(), '_nw_shops', true));
                if (in_array($shop_id, $shop_ids)) {
                    $switch_shop = new NW_Shop_Club($shop_id);
                    if ($switch_shop->is_activated()) {
                        $previous_shop_id = $shop->get_id();
                        $shop = $switch_shop;
                    } else {
                        wc_add_notice(sprintf('%s is deactivated!', $switch_shop), 'error');
                    }
                } else {
                    wc_add_notice(sprintf('You are not registered in %s!', $switch_shop), 'error');
                }
            }

            // Done with all checks, set all variables and such
            if ($shop_id) {
                // Set statically stored ids for easy access later in runtime
                static::$shop = $shop;
                static::$shop_ids = array(
                    $shop->get_id(),
                    $shop->get_vendor_id(),
                );
                if ($shop->get_group_id())
                    static::$shop_ids[] = static::$shop->get_group_id();
            }

            // If we switched from another shop, store cart
            if ($previous_shop_id !== false) {

                // Set active shop, to remember next time customer logs in
                if (!current_user_can('manage_woocommerce')) {
                    update_user_meta(get_current_user_id(), '_nw_active_shop', $shop_id);
                }

                // Make sure we have a session
                if (!WC()->session->has_session())
                    WC()->session->set_customer_session_cookie(true);

                // Store new shop id in session
                WC()->session->set('nw_shop', $shop_id);

                // Store previous cart
                $cart_meta_key = '_woocommerce_persistent_cart_' . get_current_blog_id();

                update_user_meta(get_current_user_id(), '_nw_cart_' . $previous_shop_id, get_user_meta(get_current_user_id(), $cart_meta_key, true));

                // Get stored cart for new show
                update_user_meta(get_current_user_id(), $cart_meta_key, get_user_meta(get_current_user_id(), '_nw_cart_' . $shop_id, true));

                WC()->session->set('cart', array());
                WC()->session->set('cart_totals', array());

                // Silence warning about changes to cart
                add_action('template_redirect', function () {
                    if (WC()->session)
                        WC()->session->set('wc_notices', array());
                });
            }

            if (WC()->session) {
                WC()->session->set('chosen_payment_method', 'kco');
            }

            /* $ne = 9853;
		  WC()->session->get('nw_shop',$ne);*/

            add_action('template_redirect', __CLASS__ . '::check_product_access');
        }

        /**
         * If an error message is set, redirect to front page, log user out and display error as notice
         *
         */
        public static function display_error_and_logout()
        {
            wc_get_logger()->debug("display_error_and_logout : ". WC()->session->get('nw_shop'), ["source"=>"open_shop"]);
            // If front page and user is logged in
            if (!NWP_Functions::is_backend() && (is_home() || is_front_page() || is_shop()) && is_user_logged_in()) {
                wc_get_logger()->debug("inside if display_error_and_logout : ". WC()->session->get('nw_shop'), ["source"=>"open_shop"]);
                // If an error has occured
                if (WC()->session && WC()->session->has_session() && $msg = WC()->session->get('nw_error_msg')) {
                    WC()->session->set('nw_error_msg', '');
                    /* if (!current_user_can('manage_woocommerce')) {
					wp_logout();
				} */
                    // Display the error message
                    //wc_add_notice($msg, 'error');
                }
                // No error, redirect to WooCommerce shop
                // else if (!current_user_can('manage_woocommerce')) {
                // 	wp_redirect(get_permalink(wc_get_page_id('shop')));
                // 	exit;

                // }
            }

            // Not logged in, redirect to front page
            else if (!is_user_logged_in() && is_woocommerce()) {

                wc_get_logger()->debug("inside else display_error_and_logout : ". WC()->session->get('nw_shop'), ["source"=>"open_shop"]);
                // if no session (user logout access code) then redirect to homepage
                if (WC()->session && WC()->session->has_session() && WC()->session->get('nw_shop')) {
                    wc_get_logger()->debug("inside else display_error_and_logout inside nested if : ". WC()->session->get('nw_shop'), ["source"=>"open_shop"]);
                    $shop_id_logoutUsers = WC()->session->get('nw_shop');
                    $is_active = get_post_meta($shop_id_logoutUsers, '_nw_club_onLogout', true);

                    if ($is_active) {
                        wc_get_logger()->debug("inside else display_error_and_logout inside is active: ". WC()->session->get('nw_shop'), ["source"=>"open_shop"]);
                        //wp_redirect(get_permalink(wc_get_page_id('shop')));
                        //exit;
                    } else {
                        WC()->session->set('nw_shop', '');
                        wp_redirect(get_home_url());
                        wc_get_logger()->debug("inside else display_error_and_logout inside not active: ". WC()->session->get('nw_shop'), ["source"=>"open_shop"]);
                        exit;
                    }
                } else {
                    wp_redirect(get_home_url());
                    exit;
                }
            }
        }

        /**
         * If viewing a product, check if user should be able to see it depending on
         * the current shop
         *
         */
        public static function check_product_access()
        {
            if (is_product() && !is_null(static::$shop_ids) && !is_null(static::$shop)) {
                // Uncomment below if admins should be simulate denied access when viewing a shop
                // if (static::$shop->get_id() && current_user_can('manage_woocommerce'))
                // return;

                global $post;
                $terms_to_check = static::$shop_ids;
                if (static::$shop->has_active_campaign())
                    $terms_to_check[] = 'campaign';

                foreach (wp_get_object_terms($post->ID, '_nw_access') as $term) {
                    if (in_array(absint($term->slug), $terms_to_check))
                        return;
                }
                wp_redirect(get_permalink(wc_get_page_id('shop')));
            }
        }

        /**
         * Only show related products belonging to the current shop
         *
         * @param WP_Query $query
         * @return WP_Query
         */
        public static function filter_related_products_query($query)
        {
            if (!NWP_Functions::is_backend() && !is_null(static::$shop)) {
                $term_tax_ids = implode(', ', static::$shop->get_terms());
                $sql = sprintf(" inner JOIN (SELECT object_id as shop_term FROM wp_term_relationships as shop_terms where term_taxonomy_id IN (%s) ) as shop_terms on shop_terms.shop_term = p.ID", $term_tax_ids);
                if (isset($query['join']))
                    $query['join'] .= $sql;
            }
            return $query;
        }

        /**
         * Filter variation IDs for a product based on the term tax of club, vendor and group
         *
         * @param int[] $term_tax_ids
         * @param WC_Product_NW_Stock $product
         * @return int[]
         */
        public static function filter_variation_ids($term_tax_ids, $product)
        {
            if (!is_null(static::$shop)) {
                $campaign = count(maybe_unserialize(get_post_meta($product->get_id(), '_nw_campaign_enabled_variations', true)));

                // Product is enabled for campaign, only filter for IDs of campaign enabled variations
                if (static::$shop->has_active_campaign() && $campaign) {
                    return array(absint(get_option('nw_campaign_term_tax_id')));
                } else {
                    return static::$shop->get_terms(false);
                }
            }
            return $term_tax_ids;
        }

        /**
         * Register hooks in order to swap out a products main image if applicable
         *
         */
        public static function register_image_replace_hooks()
        {
            if (!is_null(static::$shop) && is_woocommerce()) {
                add_action('newwave_stock_product_contruct', __CLASS__ . '::cache_image_access', 99, 1);
                add_filter('get_post_metadata', __CLASS__ . '::meta_data_filter', 99, 3);
                add_action('newwave_product_stock_image_id', __CLASS__ . '::filter_stock_image_id', 99, 2);
                add_filter('newwave_product_type_stock_gallery_image_ids', __CLASS__ . '::filter_stock_gallery_image_ids', 99, 2);
            }
        }

        /**
         * Cache IDs for products main image when nw_stock product is constructed
         *
         * @param WC_Product $product
         * @return array
         */
        public static function cache_image_access($product)
        {
            // Only cache if there is a session
            if (!is_null(static::$shop) && !is_null(static::$shop_ids)) {
                $image_access = $product->get_image_access();

                // Campaign is active, get images for campaign activated variations only
                if (static::$shop->has_active_campaign()) {
                    if (isset($image_access['campaign']['image']))
                        static::$image_access_cache[$product->get_id()] = $image_access['campaign']['image'];
                    return;
                } else {
                    foreach (static::$shop_ids as $shop_id) {
                        if (isset($image_access[$shop_id]['image'])) {
                            static::$image_access_cache[$product->get_id()] = $image_access[$shop_id]['image'];
                            return; //Quit since we found an image
                        }
                    }
                }
            }
        }

        /**
         * Replace image IDs with images colors for the current shop when a
         * WC_Product_NW_Stock attributes are directly access either through
         * get_the_post_thumbnail_id or get_the_post_thumbnail_url.
         *
         * @param mixed $value Null if unaltered by other filters
         * @param int $post_id
         * @param string $meta_key
         */
        public static function meta_data_filter($value, $post_id, $meta_key)
        {
            if ('_thumbnail_id' == $meta_key || '_wp_attached_file' == $meta_key) {
                if (isset(static::$image_access_cache[$post_id])) {
                    return static::$image_access_cache[$post_id] ? static::$image_access_cache[$post_id] : $value;
                }
            }
            return $value;
        }

        /**
         * Get the filtered main image ID for a product
         *
         * @param int $image_id
         * @param WC_Product_NW_Stock $product
         * @return int $image_id
         */
        public static function filter_stock_image_id($image_id, $product)
        {
            return static::parse_image_ids($image_id, $product, 'image');
        }

        /**
         * Get the filtered gallery image IDs for a product
         *
         * @param int[] $image_ids
         * @param WC_Product_NW_Stock $product
         * @return int[] $image_ids
         */
        public static function filter_stock_gallery_image_ids($image_ids, $product)
        {
            return static::parse_image_ids($image_ids, $product, 'gallery');
        }

        /**
         * Get the cached main image or gallery image IDs for a product
         *
         * @param int|int[] $val Original value
         * @param WC_Product_NW_Stock $product
         * @param string Type of images to look for, either 'image' or 'gallery'
         * @return int[] $image_ids
         */
        private static function parse_image_ids($val, $product, $key)
        {
            if (!is_null(static::$shop) && !is_null(static::$shop_ids)) {
                $image_access = $product->get_image_access();
                if (static::$shop->has_active_campaign() && $product->is_part_of_campaign()) {
                    if (isset($image_access['campaign'][$key]))
                        return $image_access['campaign'][$key];
                } else {
                    foreach (static::$shop_ids as $shop_id) {
                        if (isset($image_access[$shop_id][$key])) {
                            return $image_access[$shop_id][$key];
                        }
                    }
                }

                // Nothing found
                if ($key == 'gallery')
                    return array();
                else
                    return 0;
            }
            return $val;
        }

        /**
         * Set a custom walker class to display category counts correctly,
         * based on current shop
         *
         * @param array $args
         * @return array
         */
        public static function set_cat_walker_class($args)
        {
            include_once(NW_PLUGIN_DIR . 'includes/nw-product-cat-list-walker-class.php');
            $args['walker'] = new NW_Product_Cat_list_Walker;
            return $args;
        }

        /**
         * Redirect user to WooCommerce shop page
         *
         * @param string $redirect_to
         */
        public static function login_and_register_redirect($redirect_to)
        {
            return wc_get_page_permalink('shop');
        }

        /**
         * Redirect to front page when user logs out
         *
         */
        public static function logout_redirect()
        {
            wp_redirect(home_url());
            exit;
        }

        /**
         * Alter the SQL-statement if sorting by price, by replacing product prices with
         * discounts from NW_ for current shop, if any
         *
         * @param string[] $sql SQL-statement pieces before execution
         * @return string[]
         */
        public static function sort_by_discounts($sql)
        {
            if (isset($_GET['orderby']) && ($_GET['orderby'] == 'price' || $_GET['orderby'] == 'price-desc')) {
                if (strpos($sql['join'], 'price_query') !== false) {
                    if (!is_null(static::$shop)) {
                        global $wpdb;

                        // Get terms without term_tax_id for campaign, we'll do another LEFT JOIN for that if applicable
                        $discount_ids = static::$shop->get_terms(false);

                        $term_tax_ids = '(' . implode(', ', $discount_ids) . ')';
                        $table_name = $wpdb->prefix . NWP_TABLE_DISCOUNTS;
                        $sql['join'] .= " LEFT JOIN (SELECT product_id, min(discount + 0) as discount FROM $table_name WHERE shop_term_tax_id IN $term_tax_ids GROUP BY product_id) as discounts ON {$wpdb->posts}.ID = discounts.product_id";

                        // Ascending or descending?
                        $asc_or_desc = $_GET['orderby'] == 'price' ? 'ASC' : 'DESC';
                        $sql['orderby'] = " COALESCE(discounts.discount, price_query.price) $asc_or_desc";

                        if (static::$shop->has_active_campaign()) {
                            $campaign_term_tax_id = absint(get_option('nw_campaign_term_tax_id'));
                            $sql['join'] .= " LEFT JOIN (SELECT product_id, min(discount + 0) as discount FROM wp_newwave_discounts where shop_term_tax_id = $campaign_term_tax_id group by product_id) as campaign on wp_posts.ID = campaign.product_id";
                            $sql['orderby'] = " COALESCE(campaign.discount, discounts.discount, price_query.price) $asc_or_desc";
                        }
                        // No need to run this function more than once
                        remove_filter('post_claues', __CLASS__ . '::post_clauses', 99);
                    }
                }
            }

            return $sql;
        }

        /**
         * Hide add-to-cart button if the nw_stock_logo or nw_special product is not purchasable
         *
         */
        public static function hide_add_to_cart_if_applicable()
        {
            global $product;

            if ($product->is_type('nw_special')) {
                if (!$product->within_sale_period())
                    remove_action('woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30);
            }
        }

        /**
         * Modify how long a user stays logged in
         *
         */
        public static function set_cookie_logout($expiration, $user_id, $remember)
        {
            if ($remember) {
                $expiration = static::SESSION_DURATION;
            }
            return $expiration;
        }

        /**
         * Filter product posts based on which products is in the set shop
         *
         * @param WP_Query $query
         */
        public static function filter_posts($query)
        {
            // Limit front end searches to products only - already done in theme
            // if (!NWP_Functions::is_backend()) {
            // if ($query->is_search)
            // $query->set('post_type', 'product');
            // }

            // Filter products based on current shop
            if (!is_null(static::$shop)) {
                if ($query->get('post_type') == 'product' || !empty($query->get('product_cat'))) { //
                    $query->query_vars['tax_query'] = array(array(
                        'taxonomy' => '_nw_access',
                        'field'    => 'term_taxonomy_id',
                        'terms'    => static::$shop->get_terms(),
                    ));
                }
            }
            return $query;
        }


        /* * Filter which variations should be selectable and available for purchase.
	 * This will be checked when products added to cart before they expired or is out of stock
	 *
	 * @param bool $visible If set to being visible or not
	 * @param int $variation_id ID of WC_Product_Variation
	 * @param int $parent_id ID of parent product
	 * @param WC_Product_Variation $variation
	 */
        public static function filter_variations($visible, $variation_id, $parent_id, $variation)
        {
            $parent = wp_cache_get($parent_id, 'nw_parent_product');
            if (false === $parent) {
                $parent = wc_get_product($parent_id);
                wp_cache_set($parent_id, $parent, 'nw_parent_product');
            }

            if (is_null(static::$shop))
                return $visible;

            if (!is_a($parent, 'WC_Product_NW_Base')) // Is a non-NW product somehow
                return $visible;

            // If non-available stock_logo or special
            if ($parent->is_type('nw_stock_logo') || $parent->is_type('nw_special')) {
                return $visible;
                if ($parent->within_sale_period()) {
                    return true;
                }
                return false;
            }

            if (static::$shop->has_active_campaign() && !empty($parent->get_campaign_enabled_variations())) {
                return in_array($variation_id, $parent->get_campaign_enabled_variations());
            }

            $access = $parent->get_color_access();
            $attributes = $variation->get_attributes();

            if (isset($attributes['pa_color']) && !is_null(static::$shop)) { // Show color variation only if set
                $color = $attributes['pa_color'];
                // get_term_by('slug', $attributes['pa_color'], 'pa_color');
                // return false;
                if (!$term = get_term_by('slug', $attributes['pa_color'], 'pa_color')) {
                    return false;
                }

                if (isset($access[static::$shop->get_id()][$term->term_id]))
                    return true;

                if (isset($access[static::$shop->get_vendor_id()][$term->term_id]))
                    return true;

                if (isset($access[static::$shop->get_group_id()][$term->term_id]))
                    return true;
                // if (isset($access[static::$shop->get_id()][$color]))
                // 	return true;
                //
                // if (isset($access[static::$shop->get_vendor_id()][$color]))
                // 	return true;
                //
                // if (isset($access[static::$shop->get_group_id()][$color]))
                // 	return true;

            }
            return false;
        }

        /**
         * Change price based on discount for product per shop if any,
         * or general shop discount if any
         *
         * @param int|float $price
         * @param WC_Product $product
         * @param int|float Price of product
         */
        public static function filter_price($price, $product)
        {
            if (is_null(static::$shop) || (!$product->is_type('nw_stock') && !$product->is_type('variation') && !$product->is_type('nw_stock_logo') && !$product->is_type('nw_special') )) { // PLANASD - 484 added here, so that there is only one filter. Removed the one in theme functions
                return $price;
            }

            $prod_type = $product->get_type();
		    $prod_reg_price = 0;

            // If variation, inherit from parent
            if ($product->is_type('variation')) {
                $parent_id = $product->get_parent_id();
                $cached_price = wp_cache_get($product->get_parent_id(), 'nw_price');
                $parent_prod = wc_get_product($parent_id);
                if (false !== $cached_price) {
                    return $cached_price;  // commented beacause PLANASD-337 was not reflecting 
                } else { // Get it manually, without loading all parent data through wc_get_product()
                    // If discounts should not be applied; store unaltered $price in cache, and return it
                    $price = get_post_meta($product->get_parent_id(), '_price', true);
                    $prod_reg_price = (float)get_post_meta($product->get_parent_id(), '_price', true);
                    // PLANASD - 484 commented as nw_price no longer used, but discounts used to get din pris
                    // if ('nw_stock' != get_post_meta($parent_id, '_nw_type', true)) {
                    // 	wp_cache_set($parent_id, $price, 'nw_price');
                    // 	return $price;
                    // }
                    $discounts = maybe_unserialize(get_post_meta($parent_id, '_nw_discounts', true));
                    // PLANASD - 484 added condition to check for nw_stock as campaigns only present for nw_stock
                    if ('nw_stock' == get_post_meta($parent_id, '_nw_type', true))
                        $part_of_campaign = count(maybe_unserialize(get_post_meta($parent_id, '_nw_campaign_enabled_variations', true)));
                    $product_id = $product->get_parent_id();
                }
                // $prod_type = get_post_meta($parent_id, '_nw_type', true);
                $prod_type = $parent_prod->get_type();
                /* change price here  for PLANASD-337 */
                // PLANASD - 484 commented as nw_stock_logo, nw_special to use discounts tab
                // $product123 = wc_get_product($parent_id);
                // if($product123->get_type() == 'nw_stock_logo' || $product123->get_type() == 'nw_special')
                // {
                // 	$price1 = get_post_meta(  $product123->get_id(), 'nw_logo_price', true ); // change price here  for PLANASD-337
                // 	if($price1 > 0)
                // 	{
                // 		$price = $price1;
                // 	} 
                // }  
                /* change price here  for PLANASD-337 */
            }
            // NW_Stock type, access attributes directly
            else {
                $cached_price = wp_cache_get($product->get_id(), 'nw_price');
                if (false !== $cached_price) {
                    // return $cached_price;
                }

                if($product->get_type() == 'nw_stock_logo' || $product->get_type() == 'nw_special')
                {
                    // PLANASD - 484 commented as nw_stock_logo, nw_special to use discounts tab
                    // $price1 = get_post_meta(  $product->get_id(), 'nw_logo_price', true ); 
                    // if($price1 > 0)
                    // {
                    // 	$price = $price1;
                    // } 
                    $discounts = $product->get_discounts();
                } else {
                    $discounts = $product->get_discounts();
                    $part_of_campaign = $product->is_part_of_campaign();
                }

                $product_id = $product->get_id();
                $prod_reg_price = (float)get_post_meta($product->get_id(), '_price', true);
            }

            // if ('nw_stock' == $product_type) {
            // If campaign enabled
            if (static::$shop->has_active_campaign() && $part_of_campaign) {
                $price = static::calc_campaign_price($price);
            } else {
                $custom_discount = 0;
                foreach (static::$shop_ids as $shop_id) {
                    if (isset($discounts[$shop_id])) {

                        $price = static::calc_tax($discounts[$shop_id]);
                        $custom_discount = 1;
                        break;
                    }
                }

                if(!$custom_discount && !empty($prod_type)) {
                    foreach (static::$shop_ids as $shop_id) {
                        $vendor_type = get_post_type($shop_id);
                        if($vendor_type == 'nw_vendor')
                            $shop_class = new NW_Shop_Vendor($shop_id);
                        else if($vendor_type == 'nw_club')
                            $shop_class = new NW_Shop_Club($shop_id);
                        $discount_percent = (float)$shop_class->{"get_discount_$prod_type"}();
                        $printing_logo_price = 0;
                        if($prod_type == 'nw_stock_logo')
                                $printing_logo_price = (float)$shop_class->get_printing_price_nw_stock_logo();
                        if($discount_percent || $printing_logo_price) {
                            $vendor_din_pris = $prod_reg_price - ( $prod_reg_price * ($discount_percent/100) );
                            $vendor_din_pris+=$printing_logo_price;
                            $price = $vendor_din_pris;
                            break;
                        }
                    }
                }
            }

            wp_cache_set($product->get_id(), $price, 'nw_price');
            return $price;
        }

        /**
         * Whether a product is on sale or not.
         * Will depend on product and if campaign is active.
         *
         * @param bool $on_sale
         * @return true
         */
        public static function on_sale($on_sale, $product)
        {
            if (
                !is_null(static::$shop) &&
                static::$shop->has_active_campaign() && $product->is_type('nw_stock') &&
                $product->is_part_of_campaign()
            ) {
                return true;
            }
            return $on_sale;
        }

        /**
         * Calculates the sale campaign price given $price, if a campaign is active,
         * and the current shop is enabled for it
         *
         * @param float|int $price
         * @param float|int
         */
        public static function calc_campaign_price($price)
        {
            if (!is_null(static::$shop) && static::$shop->has_active_campaign()) {
                $price = absint(ceil($price * (1 - (get_option('nw_campaign_discount')) / 100)));
                return $price;
            }
            return $price;
        }

        /**
         * Calculate final price with tax based on the site's first tax-rate,
         * only if tax is enabled, and prices have been entered without tax to begin with
         *
         * @param int|double|float $price
         * @return int|double|float
         */
        private static function calc_tax($price)
        {
            if (wc_tax_enabled() && !wc_prices_include_tax()) {
                $tax = WC_Tax::get_shop_base_rate();
                $tax = reset($tax);
                $price = $price * (1 + ($tax['rate'] / 100));
            }
            return $price;
        }

        /**
         * Render a shop select at the top of the admin bar when logged in as admin
         *
         * @param WP_Admin_Bar $wp_admin_bar
         */
        public static function admin_bar_shop_select($wp_admin_bar)
        {
            if (!is_admin() && current_user_can('manage_woocommerce')) {
                $current = WC()->session->get('nw_shop');
                $clubs = NWP_Functions::query_clubs();

                $html = '<form id="nw_shop_select_form" action="" method="post">';
                $html .= wp_nonce_field('nw_admin_switch_shop', '_nw_admin_switch_shop_nonce', true, false);;
                // Redirect back original page
                $html .= sprintf('<input type="hidden" name="nw_referrer" value="%s" />', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                $html .= sprintf('<select class="nw-select2" name="nw_admin_switch_shop_id"><option value="0">%s</option>', __('All shops', 'newwave'));
                foreach ($clubs as $club) {
                    $html .= sprintf('<option value="%s" %s>%s</option>', $club['id'], selected($club['id'], $current, false), $club['name']);
                }
                $html .= sprintf('</select></form>');

                $args = array(
                    'id'    => 'nw-set-shop',
                    'meta'  => array('html'    => $html)
                );
                $wp_admin_bar->add_node($args);
            }
        }


        public static function club_access_code()
        {
            if (!is_admin() && !is_user_logged_in() && isset($_GET['klubb'])) {
                $query_meta = array(
                    'post_type' => 'nw_club',
                    'meta_key' => '_nw_shop_id',
                    'meta_value' => $_GET['klubb']
                );
                $club_posts = new WP_Query($query_meta);
                if ($club_posts->found_posts) {
                    $shop_id = $club_posts->posts[0]->ID;
                    $shop = new NW_Shop_Club($shop_id);

                    //if club has open shop ability then check for activation and provide direct access to shop
                    if($shop->has_open_shop_ability()){
                        if ($shop->is_activated()) {
                            // Make sure we have a session
                            if (!WC()->session->has_session())
                                WC()->session->set_customer_session_cookie(true);

                            // Store new shop id in session for user not logged in
                            WC()->session->set('nw_shop', $shop_id);

                            $url = home_url()."/butikk/";
//                            wp_redirect($url);
                            header('Location: '.$url);
                            exit();
                        }
                    }else{
                        // Render the club access code from template
                        wc_get_template('club/product-access.php', '', '', NW_PLUGIN_DIR . 'templates/');
                    }
                } else {
                    wp_redirect(home_url());
                    exit;
                }
            }
        }
    }
