<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Add Customer Endpoint for users to register and switch between registered shops
     *
     */
    class NW_User_Manage_Shops
    {

        /**
         * @var string Endpoint slug
         */
        const ENDPOINT = 'manage-shops';

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Register the endpoint
            add_action('init', __CLASS__ . '::add_endpoint');

            // Add endpoint to menu
            add_action('woocommerce_account_menu_items', __CLASS__ . '::add_endpoint_to_menu');

            // Output endpoint page content
            add_action('woocommerce_account_' . static::ENDPOINT . '_endpoint', __CLASS__ . '::endpoint_content');

            // When a user attempts registers to a new shop
            add_action('template_redirect', __CLASS__ . '::add_shop_to_user');

            // product access ajax call
            add_action('wp_ajax_nopriv_nw_register_shop',  __CLASS__ . '::add_shop_to_user');
            add_action('wp_ajax_nw_register_shop', __CLASS__ . '::add_shop_to_user');
        }

        /**
         * Add a WooCommerce endpoint for the user to manage shops
         *
         */
        public static function add_endpoint()
        {
            /*if(isset($_POST['nw_switch_shop_id'])){
			$logger->info( wc_print_r( 'In SWITCHed', true ), array( 'source' => 'ALongs-login-logs' ) );
		}*/
            add_rewrite_endpoint(static::ENDPOINT, EP_PAGES);
        }

        /**
         * Add the custom endpoint to the 'My Account' menu
         *
         * @param array
         * @return array
         */
        public static function add_endpoint_to_menu($items)
        {
            $items[static::ENDPOINT] = __('Clubs', 'newwave');
            return $items;
        }

        /**
         * Output endpoint content
         *
         */
        public static function endpoint_content()
        {
            $logger = wc_get_logger();
            $logger->info(wc_print_r('Manage shops', true), array('source' => 'ALongs-login-logs'));
            if (!nw_has_session())
                return;
            $shop_ids = NWP_Functions::unpack_list(get_user_meta(get_current_user_id(), '_nw_shops', true));

            $logger->info(wc_print_r('Data', true), array('source' => 'ALongs-login-logs'));
            $logger->info(wc_print_r($shop_ids, true), array('source' => 'ALongs-login-logs'));

            $shops = array();
            foreach ($shop_ids as $shop_id) {
                $shop = new NW_Shop_Club($shop_id);
                $logger->info(wc_print_r('Individial', true), array('source' => 'ALongs-login-logs'));
                $logger->info(wc_print_r($shop->get_name(), true), array('source' => 'ALongs-login-logs'));
                $logger->info(wc_print_r($shop->is_activated(), true), array('source' => 'ALongs-login-logs'));
                $logger->info(wc_print_r($shop->is_activated(), true), array('source' => 'ALongs-login-logs'));
                $shops[] = array(
                    'id' => $shop_id,
                    'name' => $shop->get_name(),
                    'activated' => $shop->is_activated(),
                    'current' => NW_Session::$shop->get_id() == $shop_id ? true : false,
                );
                /*$shops[] = array(
				'id' => $shop_id,
				'name' => $shop->get_name(),
				'activated' => $shop->is_activated(),
			//	'current' => NW_Session::$shop->get_id() == $shop_id ? true : false,
			);*/
            }

            $logger->info(wc_print_r('Shops', true), array('source' => 'ALongs-login-logs'));
            $logger->info(wc_print_r($shops, true), array('source' => 'ALongs-login-logs'));

            wc_get_template('myaccount/' . static::ENDPOINT . '.php', array(
                'shops' => $shops,
            ), '', NW_PLUGIN_DIR . 'templates/');
        }

        /**
         * Validate and register submitted attempt by user to register in a new shop
         *
         */
        public static function add_shop_to_user()
        {
            if (
                isset($_POST['_nw_register_shop_nonce']) && wp_verify_nonce($_POST['_nw_register_shop_nonce'], 'nw_register_shop')
                && isset($_POST['nw_shop_reg_code'])
            ) {
                $reg_code = strtoupper(sanitize_text_field($_POST['nw_shop_reg_code']));

                // Validate the registration code
                if (strlen($reg_code) >= 6) {
                    $args['post_type'] = 'nw_club';
                    $club = $_POST['club'];

                    if (get_current_user_id()) { // logged in user
                        $args['meta_query'][] = array(
                            array(
                                'key'     => '_nw_reg_code',
                                'value'   => $reg_code,
                            )
                        );

                        $search = new WP_Query($args);
                        // Found a shop with submitted registration code
                        if ($search->found_posts) {
                            $shop_id = $search->posts[0]->ID;

                            $shop_ids = NWP_Functions::unpack_list(get_user_meta(get_current_user_id(), '_nw_shops', true));

                            // Not already registered with the shop from before
                            if (!in_array($shop_id, $shop_ids)) {
                                $shop = new NW_Shop_Club($shop_id);
                                if ($shop->is_activated()) {
                                    $shop_ids[] = $shop_id;
                                    update_user_meta(get_current_user_id(), '_nw_shops', NWP_Functions::pack_list($shop_ids));
                                    wc_add_notice(sprintf(__('Successfully registered %s.', 'newwave'), $shop->get_name()));
                                } else {
                                    wc_add_notice(sprintf(__('%s is deactivated. Get in touch with your club representatives for more information.', 'newwave'), $shop->get_name()), 'error');
                                }
                            } else {
                                wc_add_notice(sprintf(_x('Already registered %s.', 'Front end, tried to register shop', 'newwave'), get_the_title($shop_id)), 'error');
                            }

                            return;
                        }
                    } else { // product access for logged out user
                        if (!empty($club) && $_POST['product_access']) {
                            $args['meta_query'][] = array(
                                'relation' => 'AND',
                                array(
                                    'key'     => '_nw_club_onLogout',
                                    'value'   => true,
                                ),
                                array(
                                    'key'     => '_nw_shop_id',
                                    'value'   => $club,
                                ),
                                array(
                                    'key'     => '_nw_reg_code',
                                    'value'   => $reg_code,
                                )
                            );

                            $search = new WP_Query($args);

                            // Found a shop with submitted registration code
                            if ($search->found_posts) {
                                $shop_id = $search->posts[0]->ID;
                                $shop = new NW_Shop_Club($shop_id);

                                if ($shop->is_activated()) {
                                    // Make sure we have a session
                                    if (!WC()->session->has_session())
                                        WC()->session->set_customer_session_cookie(true);

                                    // Store new shop id in session for user not logged in
                                    WC()->session->set('nw_shop', $shop_id);

                                    $msg = sprintf(__('Successfully registered %s.', 'newwave'), $shop->get_name());
                                    $success = 1;
                                } else {
                                    $msg = sprintf(__('%s is deactivated. Get in touch with your club representatives for more information.', 'newwave'), $shop->get_name());
                                    $success = 0;
                                    WC()->session->set('nw_shop', ''); // unset the session
                                }

                                $response['msg']  = $msg;
                                $response['success']  = $success;

                                echo json_encode($response);
                                die();
                            }
                        }
                    }
                }

                // Failed, output error
                if ($_POST['product_access']) {
                    $msg = __('Invalid registration code.', 'newwave');
                    $success = 0;
                    WC()->session->set('nw_shop', ''); // unset the session

                    $response['msg']  = $msg;
                    $response['success']  = $success;

                    echo json_encode($response);
                    die();
                } else
                    wc_add_notice(__('Invalid registration code.', 'newwave'), 'error');
            }
        }
    }
