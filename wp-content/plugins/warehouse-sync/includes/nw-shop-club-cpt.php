<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Admin page and handling of creating NW_Shop_Club
     *
     */
    class NW_Shop_Club_CPT extends NW_Shop_Vendor_CPT
    {

        /**
         * @var string Post type
         */
        const POST_TYPE = 'nw_club';

        /**
         * @var string Corresponding shop class
         */
        const SHOP_CLASS = 'NW_Shop_Club';

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            parent::init();
            // Register AJAX function for resetting of registration code
            add_action('wp_ajax_nw_reset_registration_code', array(get_called_class(), 'ajax_reset_registration_code'));

            // Set custom post thumbnail size
            add_filter('admin_post_thumbnail_size', array(get_called_class(), 'change_admin_image_size'), 99, 3);
        }

        /**
         * Register post type for admin purposes only (non-public)
         *
         */
        public static function register_post_type()
        {
            register_post_type(
                static::POST_TYPE,
                array(
                    'description' => '',
                    'public' => false,
                    'exclude_from_search' => true,
                    'publicly_queryable' => false,
                    'show_ui' => true,
                    'show_in_nav_menus' => false,
                    'show_in_menu' => 'newwave',
                    'supports' => array('title', 'thumbnail','editor'),
                    'labels' => array(
                        'name' => __('Shops', 'newwave'),
                        'singular_name' => __('Club', 'newwave'),
                        'add_new' => __('New club', 'newwave'),
                        'add_new_item' => __('Add new club', 'newwave'),
                        'edit_item' => __('Edit club', 'newwave'),
                        'search_items' => __('Search clubs', 'newwave'),
                        'featured_image' => __('Club logo', 'newwave'),
                        'set_featured_image' => __('Select club logo', 'newwave'),
                        'remove_featured_image' => __('Remove club logo', 'newwave'),
                        'use_featured_image' => __('Select as club logo', 'newwave')
                    )
                )
            );
        }

        /**
         * Edit admin standard message notices to correspond with post type
         *
         * @param string[] $messages
         * @return string[]
         */
        public static function edit_exisiting_admin_notices($messages)
        {
            $messages[static::POST_TYPE] = array(
                1 => __('Club updated.', 'newwave'),
                4 => __('Club updated.', 'newwave'),
                7 => __('Club created.', 'newwave')
            );
            return $messages;
        }

        /**
         * Get the custom columns for this post type
         *
         * @return array
         */
        protected static function get_columns()
        {
            return array(
                'shop_id' => array(
                    'label' => __('ID', 'newwave'),
                    'sortable' => true
                ),
                'name' => array(
                    'label' => __('Name', 'newwave'),
                    'sortable' => true
                ),
                'status' => array(
                    'label' => __('Status', 'newwave'),
                    'sortable' => true
                ),
                'registration_code' => array(
                    'label' => __('Registration code', 'newwave'),
                    'sortable' => false
                ),
                'shipping' => array(
                    'label' => _x('Shipping', 'Admin column', 'newwave'),
                    'sortable' => true
                ),
                'parent' => array(
                    'label' => __('Associated vendor', 'newwave'),
                    'sortable' => true,
                ),
                'product_access_link' => array(
                    'label' => __('Product Access Link', 'newwave'),
                    'sortable' => false,
                )
            );
        }

        /**
         * Output select-tag and options to filter posts based on post parent
         *
         * @param string $post_type Current post type
         */
        public static function column_parent_filter($post_type)
        {
            if ($post_type != static::POST_TYPE)
                return;

            $selected = false;
            if (isset($_REQUEST['parent_sorting']))
                $selected = $_REQUEST['parent_sorting'];
            $parents = NWP_Functions::query_vendors();

?>
            <select class="nw-select2" name="parent_sorting">
                <option value="0"><?php _e('All vendors', 'newwave'); ?></option>
                <?php foreach ($parents as $parent_id => $parent) : ?>
                    <option value="<?php echo $parent_id; ?>" <?php selected($selected, $parent_id); ?>>
                        <?php echo $parent['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select><?php
                    }

                    /**
                     * Change display size of featured image in admin panel
                     *
                     * @param array $size [x, y]
                     * @param int $thumbnail_id
                     * @param WP_POST $post
                     */
                    public static function change_admin_image_size($size, $thumbnail_id, $post)
                    {
                        if ($post->post_type == static::POST_TYPE) {
                            return 'thumbnail';
                        }
                        return $size;
                    }

                    /**
                     * Get registration code for shop
                     *
                     * @param NW_Shop_Club $shop
                     */
                    public static function column_registration_code($shop)
                    {
                        echo $shop->get_registration_code();
                    }

                    /**
                     * Get shipping setting
                     *
                     * @param NW_Shop_Club $shop
                     */
                    public static function column_shipping($shop)
                    {
                        $shipping = $shop->get_allowed_shipping();
                        switch ($shipping) {
                            case 'club':
                                _e('Club', 'newwave');
                                break;

                            case 'club-customer':
                                _e('Club & customer', 'newwave');
                                break;

                            case 'vendor':
                                _e('Vendor', 'newwave');
                                break;

                            case 'vendor-club':
                                _e('Vendor & club', 'newwave');
                                break;

                            case 'vendor-customer':
                                _e('Vendor & customer', 'newwave');
                                break;

                            case 'vendor-club-customer':
                                _e('Vendor, club & customer', 'newwave');
                                break;

                            case 'customer':
                                _e('Customer', 'newwave');
                                break;
                        }
                    }

                    /**
                     * Get product access link for shop
                     *
                     * @param NW_Shop_Club $shop
                     */
                    public static function column_product_access_link($shop)
                    {
                        echo '<a href="' . $shop->get_product_access() . '" target="_blank">View</a>';
                    }

                    /**
                     * Controller function triggering sub-functions
                     * displaying different elements for admin page
                     *
                     */
                    public static function display_meta_boxes()
                    {
                        $club = new NW_Shop_Club(get_the_ID());

                        ?><div class="wrap nw-settings">
                <?php
                        NWP_Functions::settings_section_start(__('General', 'newwave'));
                        static::display_parent($club);
                        static::display_status($club);
                        static::display_no_freight_charge($club);
                        static::display_freight_charge($club);
                        static::display_shop_id($club);
                        static::display_product_access_btn($club);
                        static::display_name($club);
                        static::display_registration_code($club);
                        static::display_registration_capping($club);
                        static::display_shipping($club);
                        static::display_campaign_ability($club);
                        static::display_club_onLogout($club);
                        static::display_open_shop_ability($club);
                        static::display_webshop_message($club);
                        NWP_Functions::settings_section_end();

                        NWP_Functions::settings_section_start(__('Profiling', 'newwave'));
                        static::display_club_logo($club);
                        static::display_sport_banners($club);
                        NWP_Functions::settings_section_end();

                        NWP_Functions::settings_section_start(__('Address', 'newwave'));
                        static::display_address($club);
                        static::display_discount_fields($club); //PLANASD -484 adding the custom discount fields
                        NWP_Functions::settings_section_end();
                        submit_button(__('Save', 'newwave'));
                ?></div>
        <?php
                    }

                    /**
                     * Display dropdown for selecting parent group
                     * @param NW_Shop_Club $shop to display
                     */
                    protected static function display_parent($shop)
                    {
                        $options = array();
                        foreach (NWP_Functions::query_vendors() as $vendor_id => $vendor) {
                            $options[$vendor_id] = sprintf('(%s) %s', get_post_meta($vendor_id, '_nw_shop_id', true), $vendor['name']);
                        }

                        NWP_Functions::settings_row(
                            'nw_parent',
                            'select',
                            $shop->is_saved() ? $shop->get_parent_id() : 0,
                            __('Associated vendor', 'newwave'),
                            array(
                                'required' => true,
                                'options' => $options,
                                'placeholder' => __('Select a vendor', 'newwave'),
                                'select_placeholder' => !$shop->is_saved()
                            )
                        );
                    }

                    /**
                     * Display shop name, which really just is the 'post_title'
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_name($shop)
                    {
                        NWP_Functions::settings_row(
                            'nw_name',
                            'text',
                            $shop->get_name(),
                            __('Club name', 'newwave'),
                            array(
                                'required' => true,
                                'input_classes' => array('wide'),
                            )
                        );
                    }

                    /**
                     * Toggle shop freight charges-applicable
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_no_freight_charge($shop)
                    {
                        NWP_Functions::settings_row(
                            'nw_no_freight_charge',
                            'checkbox',
                            $shop->is_no_freight_charge(),
                            __('Gratis Frakt', 'newwave'),
                            array(
                                'tooltip' => __('Activate for Free Shipping.', 'newwave'),
                                'input_classes' => array('nw-toggle'),
                                'attributes' => array(
                                    'data-toggle-on' => __('Activated', 'newwave'),
                                    'data-toggle-off' => __('Deactivated', 'newwave'),
                                )
                            )
                        );
                    }

                    /**
                     * Display shop freight charges
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_freight_charge($shop)
                    {
                        NWP_Functions::settings_row(
                            'nw_freight_charge',
                            'text',
                            $shop->get_freight_charge(),
                            __('Freight Charge', 'newwave'),
                            array(
                                'tooltip' => __('Club-specific Freight Charge.', 'newwave'),
                            )
                        );
                    }

                    /**
                     * Display shop customer ID
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_shop_id($shop)
                    {
                        NWP_Functions::settings_row(
                            'nw_customer_id',
                            'text',
                            $shop->get_shop_id(),
                            __('Club ID', 'newwave'),
                            array(
                                'tooltip' => __('The club ID created automatically and can not be edited.', 'newwave'),
                                'attributes' => array('disabled' => 'disabled')
                            )
                        );
                    }

                    /**
                     * View product Access button link
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_product_access_btn($shop)
                    {
                        NWP_Functions::settings_row_start(
                            __('Product Access Link', 'newwave'),
                            array(
                                'name' => 'nw-product-access-link',
                                'tooltip' => __('Link to product access page', 'newwave'),
                                'for' => 'nw-reg-code',
                            )
                        );
        ?>
            <a id="nw-product-access-link" href="<?php echo $shop->get_product_access(); ?>" class="button" target="_blank"><?php _e('View', 'newwave'); ?></a>
        <?php
                        NWP_Functions::settings_row_end();
                    }

                    /**
                     * Display shop registration code
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_registration_code($shop)
                    {
                        $reg_code = $shop->get_registration_code();
                        NWP_Functions::settings_row_start(
                            __('Registration code', 'newwave'),
                            array(
                                'name' => 'nw-reg-code',
                                'tooltip' => __('The registration code given to customers for registration.', 'newwave'),
                                'for' => 'nw-reg-code',
                            )
                        );
        ?>
            <input type="text" id="nw-reg-code" value="<?php if ($shop->is_saved()) echo $reg_code; ?>" disabled />
            <button id="nw-copy-reg-code" class="button" <?php if (!$shop->is_saved()) echo 'disabled'; ?>>
                <?php _e('Copy', 'newwave'); ?></button>
            <button id="nw-reset-reg-code" class="button" data-nonce="<?php echo wp_create_nonce('nw-reset-reg-code-nonce'); ?>" data-nw-alert="<?php _e('Resetting registration code will render the previous code invalid for new user registrations. Proceed?', 'newwave'); ?>" <?php if (!$shop->is_saved()) echo 'disabled'; ?>>
                <?php _e('Reset', 'newwave'); ?>
            </button>
        <?php
                        NWP_Functions::settings_row_end();
                    }

                    /**
                     * Reset the registration code, via AJAX
                     *
                     */
                    public static function ajax_reset_registration_code()
                    {
                        if (!current_user_can('manage_woocommerce'))
                            return;

                        check_ajax_referer('nw-reset-reg-code-nonce', 'security');

                        if (isset($_POST['post_id']) && isset($_POST['action'])) {
                            if ($_POST['post_id'] && $_POST['action'] == 'nw_reset_registration_code') {
                                $shop = new NW_Shop_Club($_POST['post_id']);
                                echo $shop->set_new_registration_code();
                                $shop->save();
                            }
                        }
                        wp_die();
                    }

                    /**
                     * Display registration capping
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_registration_capping($shop)
                    {
                        NWP_Functions::settings_row_start(
                            __('Users registered', 'newwave'),
                            array(
                                'name' => 'nw-users-registered',
                                'tooltip' => _x('Number of users registered.', 'Club admin', 'newwave'),
                                'for' => 'nw-users-registered',
                            )
                        );

                        printf(
                            '<span id="nw-users-registered" %s>%s</span>',
                            $shop->is_capping_active() ? 'style="display:none;"' : '',
                            sprintf(__('%d users registered', 'newwave'), $shop->get_no_users_registered())
                        );

                        printf(
                            '<span id="nw-max-users" %s>%s</span>',
                            !$shop->is_capping_active() ? 'style="display:none;"' : '',
                            sprintf(
                                __('%d out of %s users registered', 'newwave'),
                                $shop->get_no_users_registered(),
                                sprintf('<input name="nw_max_users" type="number" placeholder="0" min="10" max="100000" step="5" value="%s"/>', $shop->get_maximum_no_users())
                            )
                        );
                        NWP_Functions::settings_row_end();
                        NWP_Functions::settings_row(
                            'nw_registration_capping',
                            'checkbox',
                            $shop->is_capping_active(),
                            __('Registration capping', 'newwave'),
                            array(
                                'input_classes' => array('nw-toggle'),
                                'attributes' => array(
                                    'data-toggle-on' => __('On', 'newwave'),
                                    'data-toggle-off' => __('Off', 'newwave'),
                                )
                            )
                        );
                    }

                    /**
                     * Display select and checkbox for selecting shipping preferences
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_shipping($shop)
                    {
                        $options = array(
                            'club' => __('Club', 'newwave'),
                            'club-customer' => __('Club & customer', 'newwave'),
                            'vendor' => __('Vendor', 'newwave'),
                            'vendor-club' => __('Vendor & club', 'newwave'),
                            'vendor-customer' => __('Vendor & customer', 'newwave'),
                            'vendor-club-customer' => __('Vendor, club & customer', 'newwave'),
                            'customer' => __('Customer', 'newwave'),
                        );

                        NWP_Functions::settings_row(
                            'nw_shipping',
                            'select',
                            $shop->get_allowed_shipping(),
                            __('Allow shipping to', 'newwave'),
                            array(
                                'options' => $options,
                                'placeholder' => __('Select a shipping option', 'newwave'),
                                'select_placeholder' => !$shop->is_saved(),
                            )
                        );
                    }

                    /**
                     * Display campaign ability
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_campaign_ability($shop)
                    {
                        NWP_Functions::settings_row(
                            'nw_campaign_ability',
                            'checkbox',
                            $shop->has_campaign_ability(),
                            __('Campaign enabled', 'newwave'),
                            array(
                                'input_classes' => array('nw-toggle'),
                                'attributes' => array(
                                    'data-toggle-on' => __('On', 'newwave'),
                                    'data-toggle-off' => __('Off', 'newwave'),
                                )
                            )
                        );
                    }

                    /**
                     * Display club on logout view
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_club_onLogout($shop)
                    {
                        NWP_Functions::settings_row(
                            'nw_club_onLogout',
                            'checkbox',
                            $shop->has_club_onLogout(),
                            __('Access Code Aktiver (avloggede brukere)', 'newwave'),
                            array(
                                'input_classes' => array('nw-toggle'),
                                'attributes' => array(
                                    'data-toggle-on' => __('On', 'newwave'),
                                    'data-toggle-off' => __('Off', 'newwave'),
                                )
                            )
                        );
                    }

                    /**
                    * Display open shop ability
                    *
                    * @param NW_Shop_Club $shop
                    */
                    protected static function display_open_shop_ability($shop) 
                    {
                            NWP_Functions::settings_row(
                                'nw_open_shop_ability',
                                'checkbox',
                                $shop->has_open_shop_ability(),
                                __('Åpen butikk aktiver', 'newwave'),
                                array(
                                    'input_classes' => array('nw-toggle'),
                                    'attributes' => array(
                                            'data-toggle-on' => __('På', 'newwave'),
                                            'data-toggle-off' => __('Av', 'newwave'),
                                    )
                                )
                            );
                    }

                    /**
                     * Display webshop message
                     * 
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_webshop_message($shop){
                        NWP_Functions::settings_row(
                            '_nw_webshop_message',
                            'text',
                            $shop->get_nw_webshop_message(),
                            __('Butikkmelding', 'newwave'),
                            array(
                                'required' => false,
                                'input_classes' => array('wide'),
                            )
                        );

                        wp_editor( 'tesstts', 'custom_description', array(
                            'textarea_name' => '_custom_description',
                            'textarea_rows' => 5,
                            'tinymce' => true
                        ) );
                    }

                    /**
                     * Save webshop message
                     *
                     * @param NW_Shop_Club
                     */
                    protected static function save_nw_webshop_message($shop)
                    {
                        $webshop_message = (!empty($_POST['_nw_webshop_message'])) ? $_POST['_nw_webshop_message'] : '';
                        $shop->set_nw_webshop_message($webshop_message);
                    }

                    /**
                     * Displays the club logo for setting or editing (by moving the featured image box)
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_club_logo($shop)
                    {
                        NWP_Functions::settings_row_start(
                            __('Club logo', 'newwave'),
                            array(
                                'name' => 'nw_club_logo',
                                'tooltip' => sprintf(__('Recommended image size is %s', 'newwave'), '150x150'),
                            )
                        );
                        /* Javascript will add the 'featured image' div here */
                        NWP_Functions::settings_row_end();
                    }

                    /**
                     * Display selecting of sports applicable to club, for displaying of
                     * sport images at front end
                     *
                     * @param NW_Shop_Club $shop
                     */
                    protected static function display_sport_banners($shop)
                    {
                        $image_query = new WP_Query(array(
                            'post_type' => 'nw_sport_banner',
                            'posts_per_page' => -1,
                        ));

                        NWP_Functions::settings_row_start(__('Sport banners', 'newwave')); ?>
            <select class="nw-select2 wide" name="nw_sport_banners[]" multiple="multiple">
                <?php foreach ($image_query->posts as $image) {
                            $selected = '';
                            if (in_array($image->ID, $shop->get_sport_banners('id')))
                                $selected = 'selected="selected"';
                            printf('<option value="%s" %s>%s</option>', $image->ID, $selected, $image->post_title);
                        }
                ?></select><?php
                            NWP_Functions::settings_row_end();
                        }


                        /**
                         * Parent function triggering functions to save data from $_POST
                         *
                         * @param int $post_id Post ID of the shop
                         */
                        public static function save_post($post_id)
                        {
                            if (get_post_status($post_id) == 'auto-draft')
                                return;

                            $club = new NW_Shop_Club($post_id);

                            // If unable to save shop id, or parent (both required)
                            // then save club as disabled, regardless
                            if (static::save_shop_id($club) && static::save_parent($club))
                                static::save_status($club);
                            else
                                static::save_status($club, false);


                            static::save_name($club);
                            static::save_term_tax_id($club);

                            static::save_registration_capping($club);
                            static::save_shipping($club);
                            static::save_campaign_ability($club);
                            static::save_club_onLogout($club);
                            static::save_open_shop_ability($club);
                            static::save_address($club);
                            static::save_discount_fields($club); // PLANASD-484 - handle save of custom discount fields
                            static::save_sport_banners($club);
                            static::save_no_freight_charge($club);
                            static::save_freight_charge($club);
                            static::save_nw_webshop_message($club);

                            $club->save();
                            // PLANASD-484 - handle reset of the discount action
                            $club = new NW_Shop_Club($post_id);
                            static::do_reset_discount_action($club);
                        }

                        /**
                         * Call function on club to generate an ID
                         *
                         * @param NW_Shop_Club
                         * @return bool Always true, ID is auto-generated and will always be unique
                         */
                        public static function save_shop_id($shop)
                        {
                            // Generates a shop id automatically
                            $shop->set_shop_id();
                            return true;
                        }

                        /**
                         * Save parent vendor
                         *
                         * @param NW_Shop_Club
                         */
                        protected static function save_parent($shop)
                        {
                            if (!isset($_POST['nw_parent']))
                                return false;

                            $parent_id = absint($_POST['nw_parent']);
                            if (!$parent_id)
                                return false;

                            $shop->set_parent_id($parent_id, 'save_post_' . static::POST_TYPE, get_called_class() . '::save_post');
                            return true;
                        }

                        /**
                         * Save shipping preferences
                         *
                         * @param NW_Shop_Club
                         */
                        protected static function save_shipping($shop)
                        {
                            if (isset($_POST['nw_shipping']))
                                $shop->set_allowed_shipping(sanitize_text_field($_POST['nw_shipping']));
                        }

                        /**
                         * Save the number of users allowed to register
                         *
                         * @param NW_Shop_Club
                         */
                        protected static function save_registration_capping($shop)
                        {
                            if (isset($_POST['nw_max_users']))
                                $shop->set_maximum_no_users($_POST['nw_max_users']);

                            $shop->set_capping(isset($_POST['nw_registration_capping']));
                        }

                        /**
                         * Save campaign ability
                         *
                         * @param NW_Shop_Club
                         */
                        protected static function save_campaign_ability($shop)
                        {
                            $shop->set_campaign_ability(isset($_POST['nw_campaign_ability']) ? true : false);
                        }

                        /**
                         * Save club display on logout
                         *
                         * @param NW_Shop_Club
                         */
                        protected static function save_club_onLogout($shop)
                        {
                            $shop->set_club_onLogout(isset($_POST['nw_club_onLogout']) ? true : false);
                        }

                        /**
                         * Save open shop ability
                         *
                         * @param NW_Shop_Club
                         */
                        protected static function save_open_shop_ability($shop)
                        {
                            $shop->set_open_shop_ability(isset($_POST['nw_open_shop_ability']) ? true : false);
                        }

                        /**
                         * Save sports images associated with the club
                         *
                         * @param NW_Shop_Club
                         */
                        protected static function save_sport_banners($shop)
                        {
                            if (isset($_POST['nw_sport_banners']))
                                $shop->set_sport_banners($_POST['nw_sport_banners']);
                        }

                        /**
                         * Save club-specific freight charge
                         *
                         * @param NW_Shop_Club
                         */
                        protected static function save_no_freight_charge($shop)
                        {
                            $is_no_freight_charge = (!empty($_POST['nw_no_freight_charge'])) ? $_POST['nw_no_freight_charge'] : false;
                            $shop->set_no_freight_charge($is_no_freight_charge);
                        }

                        /**
                         * Save club-specific freight charge
                         *
                         * @param NW_Shop_Club
                         */
                        protected static function save_freight_charge($shop)
                        {
                            if (isset($_POST['nw_freight_charge']))
                                $shop->set_freight_charge($_POST['nw_freight_charge']);
                        }
                    }
                ?>