<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Admin page and handling of creating NW_Shop_Vendor
     *
     */
    class NW_Shop_Vendor_CPT extends NW_Shop_Group_CPT
    {

        /**
         * @var string Post type
         */
        const POST_TYPE = 'nw_vendor';

        /**
         * @var string Corresponding shop class
         */
        const SHOP_CLASS = 'NW_Shop_Vendor';

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            parent::init();
            // Add custom filtering of posts in admin
            add_action('restrict_manage_posts', array(get_called_class(), 'column_parent_filter'), 10, 1);
            add_filter('parse_query', array(get_called_class(), 'column_parent_filter_parse'), 10, 1);
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
                    'supports' => array('title'),
                    'labels' => array(
                        'name' => __('Vendors', 'newwave'),
                        'singular_name' => __('Vendor', 'newwave'),
                        'add_new' => __('New vendor', 'newwave'),
                        'add_new_item' => __('Add new vendor', 'newwave'),
                        'edit_item' => __('Edit vendor', 'newwave'),
                        'search_items' => __('Search vendors', 'newwave'),
                    )
                )
            );
        }

        /**
         * Edit admin standard message notices to correspond with post type
         *
         */
        public static function edit_exisiting_admin_notices($messages)
        {
            $messages[static::POST_TYPE] = array(
                1 => __('Vendor updated.', 'newwave'),
                4 => __('Vendor updated.', 'newwave'),
                7 => __('Vendor created.', 'newwave')
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
                'parent' => array(
                    'label' => __('Associated group', 'newwave'),
                    'sortable' => true,
                )
            );
        }

        /**
         * Get address fields
         *
         * @return array
         */
        protected static function get_address_fields()
        {
            return array(
                'poc' => array(
                    'label' => __('Point of contact', 'newwave'),
                    'required' => true,
                    'regex-pattern' => '^\D{2,}$',
                    'regex-label' => __('Must have at least 2 letters', 'newwave'),
                ),
                'phone' => array(
                    'label' => __('Phone number', 'newwave'),
                    'required' => false,
                    // 'regex-pattern' => '^((00|\+)47)?(\d{8})$',
                    // 'regex-label' => __('Must be a valid phone number (8 digits)', 'newwave'),
                ),
                'address_1' => array(
                    'label' => __('Address line 1', 'newwave'),
                    'required' => true,
                ),
                'address_2' => array(
                    'label' => __('Address line 2', 'newwave'),
                    'required' => false,
                ),
                'postcode' => array(
                    'label' => __('Postcode', 'newwave'),
                    'required' => true,
                    'regex-pattern' => '^\d{4}$',
                    'regex-label' => __('Must be a valid postcode (4 digits)', 'newwave')
                ),
                'city' => array(
                    'label' => __('City', 'newwave'),
                    'required' => true,
                ),
                'club_email' => array(
                    'label' => __('Club_email', 'newwave'),
                    'required' => true,
                    'input_type' => "email",
                )
            );
        }

        /**
         * Add column data for the post status (either activated or deactivated)
         *
         * @param NW_Shop_Vendor $shop
         */
        protected static function column_status($shop)
        {
            if ($shop->is_activated())
                _e('Activated', 'newwave');
            else {
                if ($shop->deactivated_by() == 'self')
                    _e('Deactivated', 'newwave');
                else
                    _e(sprintf('Deactivated (by %s)', $shop->deactivated_by()), 'newwave');
            }
        }

        /**
         * Add column data for the parent (group) of vendor
         *
         * @param NW_Shop_Vendor $shop
         */
        public static function column_parent($shop)
        {
            $parent_id = $shop->get_parent_id();
            if ($parent_id) {
                $parent_name = get_the_title($parent_id);
                printf('<a href="%s">%s</a>', get_edit_post_link($parent_id), $parent_name);
            } else {
                _e('No group', 'newwave');
            }
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

            $parents = NWP_Functions::query_groups();


?>
            <select class="nw-select2" name="parent_sorting">
                <option value="0"><?php _e('All groups', 'newwave'); ?></option>
                <?php foreach ($parents as $parent_id => $parent) : ?>
                    <option value="<?php echo $parent_id; ?>" <?php selected($selected, $parent_id); ?>>
                        <?php echo $parent['name']; ?>
                    </option>
                <?php endforeach; ?>
            </select><?php
                    }

                    /**
                     * Parse and specify query to filter based on post_parent
                     *
                     * @param WP_Query $query that will be altered based on $_REQUEST
                     */
                    public static function column_parent_filter_parse($query)
                    {
                        if (is_admin() && $query->is_main_query() && $query->query['post_type'] == static::POST_TYPE) {
                            if (isset($_REQUEST['parent_sorting']) && $_REQUEST['parent_sorting']) {
                                $query->query_vars['post_parent'] = $_REQUEST['parent_sorting'];
                            }
                        }
                        return $query;
                    }

                    /**
                     * Controller function triggering sub-functions
                     * displaying different elements for admin page
                     *
                     */
                    public static function display_meta_boxes()
                    {
                        $vendor = new NW_Shop_Vendor(get_the_ID());
                        ?>
            <div class="wrap nw-settings">
                <?php
                        NWP_Functions::settings_section_start(__('General', 'newwave'));
                        static::display_status($vendor);
                        static::display_parent($vendor);
                        static::display_shop_id($vendor);
                        static::display_shop_id_invoice($vendor);
                        static::display_name($vendor);
                        NWP_Functions::settings_section_end();

                        NWP_Functions::settings_section_start(__('Address', 'newwave'));
                        static::display_address($vendor);
                        static::display_discount_fields($vendor); //PLANASD -484 adding the custom discount fields
                        NWP_Functions::settings_section_end();
                        submit_button(__('Save', 'newwave'));
                ?>
            </div>
<?php
                    }

                    /**
                     * Display shop status (either activated or deactivated)
                     *
                     * @param NW_Shop_Vendor $shop
                     */
                    protected static function display_status($shop)
                    {
                        $msg = '';
                        $deactivated_by = $shop->deactivated_by();
                        $disable_cb = false;
                        if ($deactivated_by && 'self' != $deactivated_by) {
                            $disable_cb = true;
                            if ('group' == $deactivated_by)
                                $msg = __('Deactivated by group', 'newwave');
                            else
                                $msg = __('Deactivated by vendor', 'newwave');
                        }
                        NWP_Functions::settings_row_start(
                            __('Activated', 'newwave'),
                            array(
                                'name' => 'nw_status',
                                'tooltip' => __('Set status for shop. If not activated, no users are able to log in.', 'newwave'),
                                'for' => 'nw_status',
                                'classes' => $disable_cb ? array('disabled') : array(),
                            )
                        );
                        printf(
                            '<input type="checkbox" id="nw_status" class="nw-toggle" name="nw_status" %s %s />',
                            $disable_cb ? 'disabled' : '',
                            checked($shop->is_activated(), true, false)
                        );
                        if ($disable_cb)
                            printf('<span class="deactivated">- %s</span>', $msg);
                        NWP_Functions::settings_row_end();
                    }

                    /**
                     * Display dropdown for selecting parent group
                     *
                     * @param NW_Shop_Vendor $shop to display
                     */
                    protected static function display_parent($shop)
                    {
                        $options =  array(0 => __('No group', 'newwave'));
                        foreach (NWP_Functions::query_groups() as $group_id => $group) {
                            $options[$group_id] = sprintf('(%s) %s', get_post_meta($group_id, '_nw_shop_id', true), $group['name']);
                        }

                        NWP_Functions::settings_row(
                            'nw_parent',
                            'select',
                            $shop->is_saved() ? $shop->get_parent_id() : 0,
                            __('Associated group', 'newwave'),
                            array(
                                'required' => true,
                                'select2' => false,
                                'options' => $options,
                                'placeholder' => __('Select a group', 'newwave'),
                                'select_placeholder' => !$shop->is_saved()
                            )
                        );
                    }

                    /**
                     * Display shop name, which really just is the 'post_title'
                     *
                     * @param NW_Shop_Vendor $shop
                     */
                    protected static function display_name($shop)
                    {
                        NWP_Functions::settings_row(
                            'nw_name',
                            'text',
                            $shop->get_name(),
                            __('Vendor name', 'newwave'),
                            array(
                                'required' => true,
                                'input_classes' => array('wide'),
                            )
                        );
                    }

                    /**
                     * Display customer id for Klarna, a unique value which corresponds to New Waves
                     * internal ASW system
                     *
                     * @param NW_Shop_Vendor $shop
                     */
                    protected static function display_shop_id($shop)
                    {
                        NWP_Functions::settings_row(
                            'nw_customer_id',
                            'text',
                            $shop->get_shop_id(),
                            __('Forhandler-ID: Klarna', 'newwave'),
                            array(
                                'required' => true,
                                'regex-pattern' => '^\d{1,6}$', /* changed from '^\d{6}' */
                                'regex-label' => __('Must be a number between 0 and 999999', 'newwave'),
                            )
                        );
                    }

                    /**
                     * Display customer id for invoice, a unique value which corresponds to New Waves
                     * internal ASW system
                     *
                     * @param NW_Shop_Vendor $shop
                     */
                    protected static function display_shop_id_invoice($shop)
                    {
                        NWP_Functions::settings_row(
                            '_nw_shop_id_invoice',
                            'text',
                            $shop->get_shop_id_invoice(),
                            __('Forhandler-ID: Faktura', 'newwave'),
                            array(
                                'required' => true,
                                'regex-pattern' => '^\d{1,6}$', /* changed from '^\d{6}' */
                                'regex-label' => __('Must be a number between 0 and 999999', 'newwave'),
                            )
                        );
                    }

                    /**
                     * Display all address fields
                     *
                     * @param NW_Shop_Vendor|NW_Shop_Club $shop to display
                     */
                    protected static function display_address($shop)
                    {
                        foreach (static::get_address_fields() as $field_id => $field) {
                            NWP_Functions::settings_row(
                                $field_id,
                                (isset($field['input_type']) && ($field['input_type']) == 'email' ? 'email' : 'text'),
                                $shop->{"get_$field_id"}(),
                                $field['label'],
                                array(
                                    'required' => $field['required'],
                                    'regex-pattern' => isset($field['regex-pattern']) ? $field['regex-pattern'] : '',
                                    'regex-label' => isset($field['regex-label']) ? $field['regex-label'] : '',
                                    'input_classes' => array('wide'),
                                )
                            );
                        }
                    }

                    // PLANASD-484 --- added new discount fields for vendor ---- start
                    /**
                     * Get discount fields
                     *
                     * @return array
                     */
                    protected static function get_discount_fields() {
                        $all_prod_types = wc_get_product_types();
                        $all_fields = array(
                            'discount_nw_stock' => array(
                                'label' => sprintf(__('Rabatt %% for %s', 'newwave'), $all_prod_types['nw_stock']),
                                'input_type' => 'number',
                                'required' => false,
                                'regex-label' => __('Må være et gyldig nummer', 'newwave'),
                                'min' => 0,
                                'max' => 100
                            ),
                            'discount_nw_stock_logo' => array(
                                'label' => sprintf(__('Rabatt %% for %s', 'newwave'), $all_prod_types['nw_stock_logo']),
                                'input_type' => 'number',
                                'required' => false,
                                'regex-label' => __('Må være et gyldig nummer', 'newwave'),
                                'min' => 0,
                                'max' => 100
                            ),
                            'printing_price_nw_stock_logo' => array(
                                'label' => sprintf(__('Logo trykk pris for %s', 'newwave'), $all_prod_types['nw_stock_logo']),
                                'input_type' => 'number',
                                'required' => false,
                                'regex-label' => __('Må være et gyldig nummer', 'newwave'),
                            ),
                            'discount_nw_special' => array(
                                'label' => sprintf(__('Rabatt %% for %s', 'newwave'), $all_prod_types['nw_special']),
                                'input_type' => 'number',
                                'required' => false,
                                'regex-label' => __('Må være et gyldig nummer', 'newwave'),
                                'min' => 0,
                                'max' => 100
                            )
                        );
                        if(static::POST_TYPE == 'nw_vendor') {
                            $all_fields['reset_all_clubs'] = array(
                                'label' => __('Tilbakestill prosenter på tvers av alle klubber', 'newwave'),
                                'required' => false,
                                'input_type' => 'checkbox',
                                'input_classes' => array('reset_all_clubs'),
                                'tooltip' => __('Hvis du krysser av for dette, vil alle prosenter og logotrykkpris for klubbene med denne dealeren tilbakestilles.', 'newwave'),
                            );
                        }else if(static::POST_TYPE == 'nw_club') {
                            $all_fields['reset_to_default_vendor'] = array(
                                'label' => __('Gjenopprett til forhandlerens standardverdier', 'newwave'),
                                'required' => false,
                                'input_type' => 'checkbox',
                                'input_classes' => array('reset_all_clubs'),
                                'tooltip' => __('Ved å krysse av for dette vil rabatt %-verdiene tilbakestilles til forhandlerens standardverdier.', 'newwave'),
                            );
                        }
                        $all_fields['reset_all_products'] = array(
                            'label' => __('Tilbakestill din pris på tvers av alle produkter', 'newwave'),
                            'required' => false,
                            'input_type' => 'checkbox',
                            'input_classes' => array('reset_all_products'),
                            'tooltip' => __('Hvis du krysser av for dette, tilbakestilles det tilpassede settet Din pris for alle produkter som har verdi for denne forhandler eller klubbene til forhandler.', 'newwave'),
                        );
                        if(static::POST_TYPE == 'nw_club')
                            $all_fields['reset_all_products']['tooltip'] = __('Hvis du krysser av for dette, tilbakestilles det tilpassede settet Din pris for et produkt som har verdi for denne klubben.', 'newwave');
                        return $all_fields;
                    }
                    /**
                    * Display all discount related fields fields
                    *
                    * @param NW_Shop_Vendor|NW_Shop_Club $shop to display
                    */
                    protected static function display_discount_fields($shop) {
                        foreach (static::get_discount_fields() as $field_id => $field) {
                            NWP_Functions::settings_row(
                                $field_id,
                                ($field['input_type']), // PLANASD-484
                                ($shop->{"get_$field_id"}()),
                                $field['label'],
                                array(
                                    'required' => $field['required'],
                                    'regex-pattern' => isset($field['regex-pattern']) ? $field['regex-pattern'] : '',
                                    'regex-label' => isset($field['regex-label']) ? $field['regex-label'] : '',
                                    'input_classes' => isset($field['input_classes']) ? $field['input_classes']: array('wide'),
                                    'tooltip' => isset($field['tooltip']) ? $field['tooltip'] : '',
                                    'min' => isset($field['min']) ? $field['min'] : 0,
                                    'max' => isset($field['max']) ? $field['max'] : ''
                            ));
                        }
                    }
                    /**
                    * General purpose function saving the discount fields to shop
                    *
                    * @param NW_Shop_Vendor|NW_Shop_Club $shop to change
                    */
                    protected static function save_discount_fields($shop) {
                        $fields = static::get_discount_fields();
                        foreach ($fields as $field_id => $field) {
                            if (isset($_POST[$field_id])) {
                                $value = sanitize_text_field($_POST[$field_id]);
                                if (isset($field['pattern'])) {
                                    preg_match('/'.$field['pattern'].'/', $value, $matches);
                                    if (!$matches)
                                        continue;
                                }
                                $shop->{"set_$field_id"}($value);
                            }
                        }
                    }
                    /**
                    * General purpose function saving the discount fields to shop
                    *
                    * @param NW_Shop_Vendor|NW_Shop_Club $shop to change
                    */
                    protected static function do_reset_discount_action($shop) {
                        global $wpdb;
                        $shop_post_type = get_post_type($shop->get_id());
                        $reset_all_clubs = '';
                        if($shop_post_type == 'nw_vendor')
                            $reset_all_clubs = $shop->get_reset_all_clubs();

                        $reset_all_products = $shop->get_reset_all_products();
                        if($reset_all_clubs == 'on' || $reset_all_products == 'on') {
                            $vendors=$all_clubs=array();
                            if($shop_post_type == 'nw_vendor') {
                                $custom_args = array(
                                    'vendor_ids' => array($shop->get_id())
                                );
                                $vendors = NWP_Functions::query_vendors($custom_args);
                                $all_clubs = NWP_Functions::query_clubs($custom_args);
                            } else if($shop_post_type == 'nw_club') {
                                $custom_args = array(
                                    'club_ids' => array($shop->get_id())
                                );
                                $all_clubs = NWP_Functions::query_clubs($custom_args);
                            }
                            // echo "<pre>";
                            // print_r($vendors);
                            // echo "<br/> ---------------------------- <br/>";
                            // print_r($all_clubs);
                            // echo "</pre>";
                            if(!empty($all_clubs)) {
                                if($reset_all_clubs == "on" && $shop_post_type == 'nw_vendor' && !empty($vendors)) {
                                    // reset discount for all clubs
                                    foreach($all_clubs as $club_post_id => $club_det) {
                                        $loop_club = new NW_Shop_Club($club_post_id);
                                        $loop_club->set_discount_nw_stock($shop->get_discount_nw_stock());
                                        $loop_club->set_discount_nw_stock_logo($shop->get_discount_nw_stock_logo());
                                        $loop_club->set_printing_price_nw_stock_logo($shop->get_printing_price_nw_stock_logo());
                                        $loop_club->set_discount_nw_special($shop->get_discount_nw_special());
                                        $loop_club->save();
                                    }
                                }
                                if($reset_all_products == "on") {
                                    // reset discount for all products
                                    $shop_term_tax_ids = array();
                                    if($shop_post_type == 'nw_vendor' && !empty($vendors)) {
                                        foreach($vendors as $vendor_post_id => $vendor_det) {
                                            $shop_term_tax_ids[$vendor_det['term_tax_id']] = $vendor_post_id;
                                        }
                                    }
                                    foreach($all_clubs as $club_post_id => $club_det) {
                                        $shop_term_tax_ids[$club_det['term_tax_id']] = $club_post_id;
                                    }
                                    if(!empty($shop_term_tax_ids)) {
                                        $discounts_table = $wpdb->prefix.NWP_TABLE_DISCOUNTS;
                                        $all_products_res = $wpdb->get_results("SELECT product_id, shop_term_tax_id FROM ".$discounts_table." WHERE shop_term_tax_id IN (".implode(",", array_keys($shop_term_tax_ids)).")", ARRAY_A);
                                        // echo "<pre>";
                                        // print_r($shop_term_tax_ids);
                                        // echo "<br/> ----- all_products_res ------ <br/>";
                                        // print_r($all_products_res);
                                        // echo "</pre>";
                                        if(!empty($all_products_res)) {
                                            $all_products = array();
                                            foreach($all_products_res as $pdet) {
                                                if(!isset($all_products[$pdet["product_id"]]))
                                                    $all_products[$pdet["product_id"]] = array();
                                                array_push($all_products[$pdet["product_id"]], $pdet["shop_term_tax_id"]);
                                            }
                                            // echo "<br/> ----- all_products ------ <br/>";
                                            // echo "<pre>";
                                            // print_r($all_products);
                                            // echo "</pre>";
                                            foreach($all_products as $prod_id => $prod_shop_term) {
                                                $product_obj = wc_get_product($prod_id);
                                                if($product_obj) {
                                                    $prod_discounts = $product_obj->get_discounts();
                                                    $prod_reg_price = $product_obj->get_regular_price('edit');
                                                    $prod_type = $product_obj->get_type();
                                                    // echo "<br/> ------ processing --- id=".$prod_id." --- type=".$prod_type." ---- price=".$prod_reg_price."<br/>";
                                                    // echo "<pre>";print_r($prod_discounts);echo "</pre><br/>";
                                                    $din_price = $prod_reg_price;
                                                    $discount_percent = (float)$shop->{"get_discount_$prod_type"}();
                                                    if($discount_percent) 
                                                        $din_price = $prod_reg_price - ( $prod_reg_price * ($discount_percent/100) );
                                                    if($prod_type == 'nw_stock_logo')
                                                        $din_price+= (float)$shop->get_printing_price_nw_stock_logo();
                                                    if($din_price == $prod_reg_price) // incase value is the same, then unset the discount
                                                        $din_price = '';
                                                    foreach($prod_shop_term as $ind => $shop_term_tax_id) {
                                                        $shop_post_id = $shop_term_tax_ids[$shop_term_tax_id];
                                                        $prod_discounts[$shop_post_id] = $din_price;
                                                    }
                                                    // echo "<br/> ---- after ---- <br/>";
                                                    // echo "<pre>";print_r($prod_discounts);echo "</pre><br/>";
                                                    $product_obj->set_discounts($prod_discounts);
                                                    $product_obj->save();
                                                }
                                            }
                                        }
                                    }
                                } 
                            }
                            if($shop_post_type == 'nw_vendor') {
                                $vendor = new NW_Shop_Vendor($shop->get_id());
                                $vendor->set_reset_all_clubs('');
                                $vendor->set_reset_all_products('');
                                $vendor->save();
                            } else if($shop_post_type == 'nw_club') {
                                $club = new NW_Shop_Club($shop->get_id());
                                $club->set_reset_all_products('');
                                $club->save();
                            }
                        }

                        // reset to vendor defaults
                        if($shop_post_type == 'nw_club' && $shop->get_reset_to_default_vendor() == 'on') {
                            $vendor_id = $shop->get_parent_id();
                            if($vendor_id) {
                                $vendor = new NW_Shop_Vendor($vendor_id);
                                $loop_club = new NW_Shop_Club($shop->get_id());
                                $loop_club->set_discount_nw_stock($vendor->get_discount_nw_stock());
                                $loop_club->set_discount_nw_stock_logo($vendor->get_discount_nw_stock_logo());
                                $loop_club->set_printing_price_nw_stock_logo($vendor->get_printing_price_nw_stock_logo());
                                $loop_club->set_discount_nw_special($vendor->get_discount_nw_special());
                                $loop_club->set_reset_to_default_vendor('');
                                $loop_club->save();
                            }
                        }
                    }
                    // PLANASD-484 --- added new discount fields for vendor ---- end

                    /**
                     * Display save and activation/deactivation buttons
                     *
                     * @param NW_Shop_Vendor|NW_Shop_Club $shop
                     */
                    protected static function display_buttons($shop)
                    {
                        printf('<div class="nw-button-wrapper"><div class="nw-button left">');
                        if ($shop->is_saved()) {
                            $attribute = '';
                            if ($shop->deactivated_by() == 'vendor' || $shop->deactivated_by() == 'group') {
                                $attribute = 'disabled';
                                $deactivated_by = $shop->deactivated_by();
                            }

                            if ($shop->is_activated())
                                submit_button(__('Deactivate', 'newwave'), 'nw_deactivate', 'nw_deactivate', false, $attribute);
                            else
                                submit_button(__('Activate', 'newwave'), 'nw_activate', 'nw_activate', false, $attribute);

                            if (isset($deactivated_by)) {
                                if ($deactivated_by == 'group')
                                    printf('<span>%s</span>', __('Deactivated by group', 'newwave'));
                                else
                                    printf('<span>%s</span>', __('Deactivated by vendor', 'newwave'));
                            }
                        }
                        printf('</div>');

                        printf('<div class="nw-button right">');
                        if ($shop->is_activated() || !$shop->is_saved())
                            submit_button(__('Save', 'newwave'), 'primary nw_save', 'nw_activate', false);
                        else
                            submit_button(__('Save', 'newwave'), 'primary nw_save', 'nw_deactivate', false);
                        printf('</div></div>');
                    }

                    /**
                     * Parent function triggering functions to save data from $_POST
                     *
                     * @param NW_Shop_Vendor|NW_Shop_Club $shop to change
                     */
                    public static function save_post($post_id)
                    {
                        if (get_post_status($post_id) == 'auto-draft')
                            return;

                        $vendor = new NW_Shop_Vendor($post_id);

                        // If unable to save shop id, save post status as deactivated regardless
                        if (static::save_shop_id($vendor))
                            static::save_status($vendor);
                        else
                            static::save_status($vendor, false);

                        // If unable to save shop id invoice, save post status as deactivated regardless
                        if (static::save_shop_id_invoice($vendor))
                            static::save_status($vendor);
                        else
                            static::save_status($vendor, false);

                        static::save_name($vendor);
                        static::save_term_tax_id($vendor);
                        static::save_parent($vendor);
                        static::save_address($vendor);
                        static::save_discount_fields($vendor); // PLANASD-484 - handle save of custom discount fields

                        $vendor->save();

                        // PLANASD-484 - handle reset of the discount action
                        $vendor = new NW_Shop_Vendor($post_id);
                        static::do_reset_discount_action($vendor);
                    }

                    /**
                     * Save parent group, throw exceptions if invalid or missing
                     *
                     * @param NW_Shop_Vendor $shop to change
                     */
                    protected static function save_parent($shop)
                    {
                        if (isset($_POST['nw_parent'])) {
                            $parent_id = intval($_POST['nw_parent']);
                            $shop->set_parent_id($parent_id, 'save_post_' . static::POST_TYPE, get_called_class() . '::save_post');
                            if ($parent_id == 0)
                                return false;
                            return true;
                        }
                    }

                    /**
                     * General purpose function saving the address fields to shop
                     *
                     * @param NW_Shop_Vendor|NW_Shop_Club $shop to change
                     */
                    protected static function save_address($shop)
                    {
                        $fields = static::get_address_fields();

                        foreach ($fields as $field_id => $field) {
                            if (isset($_POST[$field_id])) {
                                $value = sanitize_text_field($_POST[$field_id]);

                                if (empty($value))
                                    continue;

                                if (isset($field['pattern'])) {
                                    preg_match('/' . $field['pattern'] . '/', $value, $matches);
                                    if (!$matches)
                                        continue;
                                }
                                $shop->{"set_$field_id"}($value);
                            }
                        }
                    }
                }
?>