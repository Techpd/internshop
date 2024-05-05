<?php
if (!defined('ABSPATH')) exit;

    /**
     * Manages product editing and overview for custom product types
     */
    class NW_Product_Admin
    {
        /**
         * Add hooks and filters
         */

        public static function init()
        {
            // Add custom product types product editing admin page
            add_filter('product_type_selector', __CLASS__ . '::add_product_types_to_selector', 99);
            add_filter('woocommerce_product_data_tabs', __CLASS__ . '::add_panel_tabs', 99);
            add_filter('woocommerce_product_data_panels', __CLASS__ . '::add_panels', 99);
            // add_action('woocommerce_product_options_general_product_data', __CLASS__ . '::extend_general_panel', 99);

            // Save attributes from custom product types
            add_action('woocommerce_admin_process_product_object', __CLASS__ . '::save', 99);

            // Load specific resources
            add_action('admin_enqueue_scripts', __CLASS__ . '::enqueue_assets', 99);

            // AJAX functions for saving panels without saving the whole product

            add_action('wp_ajax_nw_discounts', __CLASS__ . '::ajax_save_discounts');

            // PLANASD-484 added ajax call for restore defaults
		    add_action('wp_ajax_nw_restore_discounts', __CLASS__.'::ajax_restore_discounts');

            add_action('wp_ajax_nw_campaign_enabled_variations', __CLASS__ . '::ajax_save_campaign_enabled_variations');

            if(get_option('_nw_shop_feature')){
            // Add custom product sorting for WP admin
                add_action('restrict_manage_posts', __CLASS__ . '::filter_by_shop', 10, 1);
                add_filter('parse_query', __CLASS__ . '::filter_by_shop_query', 99, 1);
            }

            // Display nicenames for product types in admin dropdown
            add_filter('woocommerce_product_filters', __CLASS__ . '::change_product_type_names');


            // Add custom columns to product overview
            add_filter('manage_edit-product_columns', __CLASS__ . '::add_columns', 99, 1);
            add_filter('manage_product_posts_custom_column', __CLASS__ . '::add_column_data', 99, 2);

            add_action('wp_ajax_nw_stock_control', __CLASS__ . '::ajax_save_stock_control');
            // AJAX functions for saving panels without saving the whole product
            add_action('wp_ajax_nw_color_access', __CLASS__ . '::ajax_save_color_access');
        }


        /**
         * Modify columns displayed in admin table
         *
         * @param array $columns Ignored and replaced with local array
         * @return array
         */

        public static function add_columns($columns)
        {
            $partitioned_array = array_chunk($columns, 4, true);

            $data = array(
                'nw_product_type' => __('Product type', 'newwave')
            );

            if(get_option('_nw_shop_feature')){
                $data['nw_shops'] = __('Shops', 'newwave');
                $data['nw_campaign'] = __('Campaign', 'newwave');
            }

            $inserted = array_merge($partitioned_array[0], $data, $partitioned_array[1], $partitioned_array[2]); // not necessarily an array, see manual quote

            return  $inserted;
        }

        /**
         * Controller function calling sub-functions to fill columns in admin
         * (overrides parent class function)
         *
         * @param string $column
         * @param int $post_id
         */

        public static function add_column_data($column, $post_id)
        {
            // Display product type
            if ('nw_product_type' == $column) {
                $terms = wp_get_object_terms($post_id, 'product_type');
                if (isset($terms[0])) {
                    $slug = $terms[0]->slug;

                    if ($slug == 'nw_stock')
                        _e('Stock', 'newwave');

                    else if ($slug == 'nw_stock_logo')
                        _e('Stock with logo', 'newwave');

                    else if ($slug == 'nw_special')
                        _e('Special', 'newwave');
                }
            }

            // Display what shops product is associated with
            else if ('nw_shops' == $column && get_option('_nw_shop_feature')) {
                $terms = wp_get_object_terms($post_id, '_nw_access');
                $names = array();
                foreach ($terms as $term) {
                    $club_id = absint($term->slug);
                    if ($club_id)
                        $names[] = get_the_title($club_id);
                }
                echo implode(', ', $names);
            }

            // Display whether product is activated for campaigns or not
            else if ('nw_campaign' == $column && get_option('_nw_shop_feature')) {
                if (has_term('campaign', '_nw_access', $post_id))
                    _e('Yes', 'newwave');
                else
                    _e('No', 'newwave');
            }
        }

        /**
         * Make product type names friendlier (e.g. from nw_stock to Stock)
         *
         * @param string $html
         * @return string
         */

        public static function change_product_type_names($html)
        {
            $html = str_replace('Nw_stock_logo', _x('Stock Logo', 'Sort by type', 'newwave'), $html);
            $html = str_replace('Nw_stock', _x('Stock', 'Sort by type', 'newwave'), $html);
            $html = str_replace('Nw_special', _x('Special', 'Sort by type', 'newwave'), $html);

            return $html;
        }

        /**
         * Output a 'select' for sorting products in admin by shop
         *
         * @param string $post_type
         */

        public static function filter_by_shop($post_type)
        {
            if ($post_type != 'product')
                return;

            $selected = false;
            if (isset($_REQUEST['sort_by_shop']))
                $selected = $_REQUEST['sort_by_shop'];

            printf('<select class="nw-select2" name="sort_by_shop">');
            printf('<option value="0">%s</option>', __('All shops', 'newwave'));

            foreach (NWP_Functions::query_clubs() as $club_id => $club) {
                printf('<option value="%s" %s>%s%s</option>', $club_id, selected($selected, $club_id, false), ' ', $club['name']);
            }
            printf('</select>');
        }

        /**
         * Filter products listing in admin by shop
         *
         * @param WP_Query $query
         * @return WP_Query
         */

        public static function filter_by_shop_query($query)
        {
            if (is_admin() && $query->is_main_query() && $query->query['post_type'] == 'product') {
                if (isset($_REQUEST['sort_by_shop']) && $_REQUEST['sort_by_shop']) {
                    $shop = new NW_Shop_Club(intval($_REQUEST['sort_by_shop']));
                    $query->query_vars['tax_query'] = array(array(
                        'taxonomy' => '_nw_access',
                        'field'    => 'term_taxonomy_id',
                        'terms'    => $shop->get_terms(),
                    ));
                }
            }
            return $query;
        }

        /**
         * Add custom products to dropdown in 'Product Data' panels
         * and remove WooCommerce standard ones
         *
         * @return string[] $types
         * @return string[]
         */

        public static function add_product_types_to_selector($types)
        {
            $product_types = get_option('_nw_product_types', array());

            if(count($product_types) > 1){
                unset($types['simple']);
                unset($types['grouped']);
                unset($types['external']);

                if(! in_array('variable',$product_types)){
                    unset($types['variable']);
                }

                $options = array(
                    'variable'       => __('Default(variable)','newwave'),
                    'nw_stock'       => __('Stock','newwave'),
                    'nw_stock_logo'  => __('Stock with logo','newwave'),
                    'nw_special'     => __('Special','newwave'),
                    'nw_simple'      => __('Simple','newwave'),
                );

                foreach($product_types as $ptypes){
                    $types[$ptypes] = __($options[$ptypes]);
                }
            }

            return $types;
        }

        /**
         * Enqueue custom assets
         */

        public static function enqueue_assets()
        {
            #edit-product
            $screen = get_current_screen();
            if ('product' == $screen->post_type) {
                if ('post' == $screen->base || 'edit' == $screen->base)
                    NWP_Functions::enqueue_script('admin_products.js');

                NWP_Functions::enqueue_style('admin_products.css');
            }
        }


        /**
         * Add tabs for the custom data panels
         *
         * @param array $tabs Product data tabs with label, target div, class and priority
         * @return array
         */

        public static function add_panel_tabs($tabs)
        {
            if(get_option('_nw_shop_feature')){
                // nw_stock specific
                $tabs['nw_discounts'] = array(
                    'label' => __('Discounts', 'newwave'),
                    'target' => 'nw_discount_options',
                    'class' => array('show_if_nw_stock', 'show_if_nw_stock_logo', 'show_if_nw_special'), // PLANASD - 484 add discounts tab for all
                    'priority' => 3
                );

                // nw_stock specific
                $tabs['nw_color_access'] = array(
                    'label' => __('Shops', 'newwave'),
                    'target' => 'nw_color_access_options',
                    'class' => array('show_if_nw_stock'),
                    'priority' => 2
                );

                // nw_stock specific
                $tabs['nw_campaign_enabled_variations'] = array(
                    'label' => __('Campaign', 'newwave'),
                    'target' => 'nw_campaign_enabled_variations_options',
                    'class' => array('show_if_nw_stock'),
                    'priority' => 4
                );

                // nw_stock_logo and nw_special specific
                $tabs['nw_shop'] = array(
                    'label' => __('Shop', 'newwave'),
                    'target' => 'nw_shop_options',
                    'class' => array('show_if_nw_stock_logo', 'show_if_nw_special'),
                    'priority' => 3
                );
            }

            // nw_special specific
            $tabs['nw_sale_period'] = array(
                'label' => __('Sale Period', 'newwave'),
                'target' => 'nw_sale_period_options',
                'class' => array('show_if_nw_special','hide_if_nw_stock','hide_if_nw_stock_logo'),
                'priority' => 3
            );

            $tabs['nw_printing_instruction'] = array(
                'label' => __('Trykkeri'),
                'target' => 'nw_printingInstructions_options',
                'class'         => array('show_if_nw_stock_logo'),
                'priority'      => 3
            );

            // Move 'general' tab to top
            $tabs['general']['priority'] = 1;

            // Make sure general-, attributes- and variations-tab are displayed
            $tabs_to_add = array('general', 'attribute', 'variations');
            foreach ($tabs_to_add as $tab) {
                $tabs[$tab]['class'] = array();
                $tabs[$tab]['class'] = array_push(
                    $tabs[$tab]['class'],
                    'nw_stock',
                    'nw_stock_logo',
                    'nw_special'
                );
            }

            return $tabs;
        }

        /**
         * Save color access via AJAX
         */

        public static function ajax_save_color_access()
        {
            if (!current_user_can('edit_products'))
                wp_die(-1);

            check_ajax_referer('nw-save-color-access', 'security');

            parse_str($_POST['data'], $color_access);

            if (isset($color_access['nw_color_access'])) {
                $color_access = $color_access['nw_color_access'];
            } else
                $color_access = array();

            if (isset($_POST['post_id'])) {
                $product = new WC_Product_NW_Stock($_POST['post_id']);

                if ($product->is_type('nw_stock')) {
                    $product->set_color_access($color_access);
                    $product->save();
                }
            }

            wp_die();
        }

        /**
         * Display stock control panel
         *
         * @param WC_Product_NW_Stock_Logo|WC_Product_NW_Special $product
         */

        private static function panel_stock_control($product)
        {
?>
            <div id="nw_stock_control_options" class="panel woocommerce_options_panel">
                <?php
                $stock_control = $product->get_stock_control();
                $stock = $stock_control->get_data();
                if (empty($stock)) {
                    printf('</div>');
                    return;
                }

                // Get all colors for all products associated with the SKU and int turn all sizes for each of the colors
                $colors = $sizes = array();
                foreach ($stock as $color_term_id => $nested_sizes) {
                    if ($color_term = get_term_by('id', $color_term_id, 'pa_color')) {
                        $colors[$color_term_id] = $color_term->name;

                        foreach ($nested_sizes as $size_term_id => $sku) {
                            if ($size_term = get_term_by('id', $size_term_id, 'pa_size'))
                                $sizes[$size_term_id] = $size_term->name;
                        }
                    }
                }

                // Sort colors alphabetically
                asort($colors);

                // Sort sizes from small to large
                uasort($sizes, 'NWP_Functions::sort_sizes');

                static::render_table_start('stock-control', $colors, false, __('Set stock control status per variation, to enable/disable selling of a specific product. This will affect all products sharing the same product number.', 'newwave'));

                // Output settings with available sizes per row, colors per column
                foreach ($sizes as $size_term_id => $size_name) {
                    printf('<tr><td class="nw-label">%s</td>', $size_name);
                    foreach ($colors as $color_term_id => $color_name) {
                        if (isset($stock[$color_term_id][$size_term_id])) {
                            $checked = $stock[$color_term_id][$size_term_id] ? true : false;
                            printf('<td><input type="checkbox" name=nw_stock_control[%s][%s] %s/></td>', $color_term_id, $size_term_id, checked($checked, true, false));
                        } else {
                            printf('<td></td>');
                        }
                    }
                    printf('<td></td></tr>');
                }

                static::render_table_end();
                submit_button(
                    __('Save', 'newwave'),
                    'submit',
                    'save-stock-control',
                    true,
                    array('data-nonce' => wp_create_nonce('nw-save-stock-control'))
                );
                ?></div>
            <?php // close panel
        }

        /**
         * Save stock control settings via AJAX
         */

        public static function ajax_save_stock_control()
        {
            if (!current_user_can('edit_products'))
                wp_die(-1);

            check_ajax_referer('nw-save-stock-control', 'security');

            $post_id = sanitize_text_field($_POST['post_id']);

            $product = wc_get_product($post_id);
            $stock_control = $product->get_stock_control();
            parse_str($_POST['data'], $data);
            $data = isset($data['nw_stock_control']) ? $data['nw_stock_control'] : array();
            $stock_control->update_data($data);
            $stock_control->save();

            wp_die();
        }

                /**
                 * Ouput data for the custom panels
                 */
                public static function add_panels()
                {
                    global $post;

                    // Warning if product is not yet saved
                    if ('auto-draft' == get_post_status($post->ID)) {
                        static::panels_error(__('Product must be saved first to edit these options.', 'newwave'));
                    } else {
                        // $product = new WC_Product_NW_Stock($post->ID); // PLANASD-484 -- used default woo method here
                        $product = wc_get_product($post->ID);
                        if($product->is_type('variable')){
                            $attributes = $product->get_variation_attributes();

                            if (!isset($attributes['pa_color']) || !isset($attributes['pa_size'])) {
                                if (!isset($attributes['pa_color']) && !isset($attributes['pa_size'])) {
                                    static::panels_error(__('Product must have variations with color and size attributes before editing this option.', 'newwave'));
                                } else if (!isset($attributes['pa_color'])) {
                                    static::panels_error(__('Product must have variations with color attributes before editing this option.', 'newwave'));
                                } else {
                                    static::panels_error(__('Product must have variations with size attributes before editing this option.', 'newwave'));
                                }
                            } else {
                            /*
                                static::panel_discounts($product);
                                static::panel_campaign($product);*/
                                
                                if($product->is_type('nw_stock') && get_option('_nw_shop_feature')) {
                                    static::panel_color_access($product);
                                    // static::panel_discounts($product);
                                }

                                // PLANASD - 484 - add discounts tab for all
                                if(get_option('_nw_shop_feature')){
                                    static::panel_discounts($product);
                                }

                                //static::panel_stock_control($product);
                                //static::panel_campaign($product);


                                // Output panels for the two product types nw_stock_logo and nw_special
                                // $product = new WC_Product_NW_Stock_Logo($post->ID); // PLANASD-484 -- used default woo method here
                                if(!$product->is_type('nw_stock')) {
                                    if(get_option('_nw_shop_feature')){
                                        static::panel_shop($product);
                                    }
                                    
                                    static::panel_sale_period($product);
                                    static::panel_print_instructions($product);
                                }
                            }
                        }
                    }
                }

                /**
                 * Display an error message in panel
                 *
                 * @param string $message
                 */

                private static function panels_error($message)
                {
                    $panels = array(
                        'nw_discount_options',
                        'nw_color_access_options',
                        'nw_campaign_enabled_variations_options',
                        'nw_shop_options',
                        'nw_sale_period_options',
                    );

                    foreach ($panels as $panel) {
                        ?>
                <div id="<?php echo $panel; ?>" class="panel woocommerce_options_panel">
                    <p class="nw-error"><?php echo $message; ?></p>
                </div>
            <?php
                    }
                }

                /**
                 * Display panel for setting which colors are accessible in which shops
                 *
                 * @param WC_Product_NW_Stock|WC_Product_NW_Stock_Logo|WC_Product_NW_Special $product
                 */

                private static function panel_color_access($product)
                {
            ?>
            <div id="nw_color_access_options" class="panel woocommerce_options_panel">
                <?php
                    $variations = $product->get_variation_attributes();

                    if (!isset($variations['pa_color']))
                        return;

                    $colors = array();
                    foreach ($variations['pa_color'] as $color_slug) {
                        if ($color_term = get_term_by('slug', $color_slug, 'pa_color')) {
                            $colors[$color_term->term_id] = $color_term->name;
                        }
                    }

                    asort($colors);

                    static::render_table_start('color-access', $colors, true, __('Set what colors each shop should have access to see and be able to purchase.', 'newwave'));

                    $access = $product->get_color_access();
                    $group_checked = $vendor_checked = array();

                    foreach (NWP_Functions::get_shops() as $shop) {
                        // If row should be a 'No group'
                        $no_id = !$shop['id'] ? 'nw-inactive' : '';
                        printf('<tr class="nw-%s"><td class="nw-label %s">%s</td>', $shop['type'], $no_id, $shop['name']);

                        // If a new group, reset what colors have been checked
                        if ($shop['type'] == 'group') {
                            $group_checked = array();
                        }

                        // If a new vendor, reset what colors have been checked
                        if ($shop['type'] == 'vendor')
                            $vendor_checked = array();

                        foreach (array_keys($colors) as $color) {
                            $checked = isset($access[$shop['id']][$color]) ? true : false;
                            $disabled = false;

                            // If group has a color selected, add this for reference to
                            // disable inferior checkboxes
                            if ($shop['type'] == 'group' && $checked)
                                $group_checked[$color] = true;

                            // .. same goes for vendors
                            else if ($shop['type'] == 'vendor' && $checked)
                                $vendor_checked[$color] = true;

                            // If current color have been checked in superior vendor checkbox,
                            // check and disable current checkbox
                            if ($shop['type'] == 'club') {
                                if (isset($vendor_checked[$color])) {
                                    $checked = true;
                                    $disabled = true;
                                }
                            }

                            // ... same if current color have been checked in superior group checkbox
                            if ($shop['type'] == 'club' || $shop['type'] == 'vendor') {
                                if (isset($group_checked[$color])) {
                                    $checked = true;
                                    $disabled = true;
                                }
                            }

                            // Don't display checkboxes if current $shop is 'No group'
                            if (!$shop['id']) {
                                printf('<td></td>');
                            } else { // Output checkbox with proper attributes
                                printf('<td><input type="checkbox" name="nw_color_access[%s][%s]" %s %s/></td>', $shop['id'], $color, checked($checked, true, false), disabled($disabled, true, false));
                            }
                        }
                        printf('<td></td></tr>');
                    }

                    static::render_table_end();
                    submit_button(
                        __('Save', 'newwave'),
                        'submit',
                        'save-color-access',
                        true,
                        array('data-nonce' => wp_create_nonce('nw-save-color-access'))
                    );

                ?>
            </div>
        <?php
                    // close panel
                }

                /**
                 * Display panel to set price per shop
                 *
                 * @param WC_Product_NW_Stock|WC_Product_NW_Stock_Logo|WC_Product_NW_Special $product
                 */

                public static function panel_discounts($product)
                {
                    // PLANASD-484 added restore defaults button
                    ob_start();
                    submit_button(
                        __('Gjenopprett alle pris', 'newwave'),
                        'submit',
                        'restore-discounts',
                        true,
                        array('data-nonce' => wp_create_nonce('nw-restore-discounts'), 'data-confirm' => __('Er du sikker på at du vil gjenopprette alle priser?', 'newwave'))
                    );
                    $additional_html = ob_get_clean();
        ?>
            <div id="nw_discount_options" class="panel woocommerce_options_panel">
                <?php
                    static::render_table_start('discounts', array(''), true, __('Set discounts for shops. Vendor specific discounts override groups and club specific discounts override vendor.', 'newwave'), $additional_html);

                    $discounts = $product->get_discounts();
                    $prod_type = $product->get_type(); // PLANASD-484 - product type
                    $group_price = $vendor_price = false;

                    // PLANASD - 484 - handle display only selected shop for nw_stock_logo + nw_special
                    $do_extra_chk = 0;
                    $selected_shop_id = 0;
                    if(!$product->is_type('nw_stock')) {
                        $selected_shop_id = $product->get_shop_id();
                        $do_extra_chk = 1;
                    }

                    foreach (NWP_Functions::get_shops() as $shop) {
                        // PLANASD - 484 - handle display only selected shop for nw_stock_logo + nw_special
                        if($do_extra_chk && $selected_shop_id != $shop['id'])
                        continue;

                        // PLANASD-484 added feature for hide/show vendor's clubs
                        $tr_class=$addnal_style='';
                        if(!$do_extra_chk && $shop['type'] == 'club') {
                            $tr_class = 'shop-'.$shop['vendor_id'];
                            $addnal_style = 'display:none;';
                        }else if($do_extra_chk && $shop['type'] == 'club')
                            $tr_class = ' shop-individual';

                        printf('<tr class="nw-%s %s" style="%s"><td class="nw-label %s">%s</td>',
                            $shop['type'],
                            $tr_class,
                            $addnal_style,
                            !$shop['id'] ? 'nw-inactive' : '',
                            $shop['name']
                        );

                        // Store group/vendor discount for overriding inferior shops discount inputs
                        if ($shop['type'] == 'group')
                            $group_price = isset($discounts[$shop['id']]) ? $discounts[$shop['id']] : false;

                        else if ($shop['type'] == 'vendor')
                            $vendor_price = isset($discounts[$shop['id']]) ? $discounts[$shop['id']] : false;

                        // Override input placeholder if superior shop in hierarchy has a set value
                        $prod_reg_price = (float)$product->get_regular_price('edit');
                        $placeholder = (float)$product->get_regular_price();
                       
                        // PLANASD-484 -- get the placeholder correctly; commented out old code
                        if ($shop['type'] == 'club' && $vendor_price)
                        // if (($shop['type'] == 'vendor' || $shop['type'] == 'club') && $group_price)
                            $placeholder = $vendor_price;
                        // 	$placeholder = $group_price;
                        // if ($shop['type'] == 'club' && $vendor_price)
                        // 	$placeholder = $vendor_price;
                        $vendor_din_pris = $prod_reg_price;
                        $club_din_pris=$prod_reg_price;
                        if($shop['type'] == "vendor" || $shop['type'] == 'club') {
                            if($shop['type'] == "vendor") {
                                $vend_obj = new NW_Shop_Vendor($shop['id']);
                                $discount_percent = (float)$vend_obj->{"get_discount_$prod_type"}();
                                if($discount_percent) 
                                    $vendor_din_pris = $prod_reg_price - ( $prod_reg_price * ($discount_percent/100) );
                                if($prod_type == 'nw_stock_logo')
                                    $vendor_din_pris+=(float)$vend_obj->get_printing_price_nw_stock_logo();
                                $placeholder = $vendor_din_pris;
                            }
                            else {
                                $shop_obj = new NW_Shop_Club($shop['id']);
                                if($shop['vendor_id']) {
                                    $vend_obj = new NW_Shop_Vendor($shop['vendor_id']);
                                    $discount_percent = (float)$vend_obj->{"get_discount_$prod_type"}();
                                    if($discount_percent) 
                                        $vendor_din_pris = $prod_reg_price - ( $prod_reg_price * ($discount_percent/100) );
                                    if($prod_type == 'nw_stock_logo')
                                        $vendor_din_pris+=(float)$vend_obj->get_printing_price_nw_stock_logo();
                                }
                                $discount_percent = (float)$shop_obj->{"get_discount_$prod_type"}();
                                if($discount_percent) 
                                    $club_din_pris = $prod_reg_price - ( $prod_reg_price * ($discount_percent/100) );
                                if($prod_type == 'nw_stock_logo')
                                    $club_din_pris+=(float)$shop_obj->get_printing_price_nw_stock_logo();
                                if($club_din_pris != $prod_reg_price)
                                    $placeholder = $club_din_pris;
                                else 
                                    $placeholder = $vendor_din_pris;
                            }
                            
                        }

                        // Get set discount if any, and display clear input button if so
                        $price = isset($discounts[$shop['id']]) ? $discounts[$shop['id']] : '';
                        $display_clear_btn = !empty($price) ? 'display:block;' : '';

                        $edited_class = '';
                        if($price != '')
                            $edited_class = 'nw-custom-din-pris';

                        // PLANASD-484 added feature for hide/show vendor's clubs
                        $addnal_html=$addnal_class=$vendor_id='';
                        if(!empty($shop['clubs'])) {
                            $addnal_html = '<span class="dashicons dashicons-insert"></span>';
                            $addnal_class = 'vendor-show-clubs';
                            $vendor_id = $shop['id'];
                        }

                        if (!$shop['id'])
                            printf('<td></td><td></td>');
                            else
                            printf('<td class="%s" data-ven_id="%s">%s</td><td><input type="text" class="wc_input_price nw_din_pris %s" value="%s" placeholder="%s" data-ori="%s" data-ven="%s" data-club="%s" name="nw_discounts[%s]"/></td></tr>',$addnal_class, $vendor_id, $addnal_html, $edited_class, NWP_Functions::nw_get_float_formatted_price($price), NWP_Functions::nw_get_float_formatted_price($placeholder), NWP_Functions::nw_get_float_formatted_price($prod_reg_price), NWP_Functions::nw_get_float_formatted_price($vendor_din_pris), NWP_Functions::nw_get_float_formatted_price($club_din_pris), $shop['id']); // PLANASD-484 added data attributes + removed unrequired classes
                            
                            // printf('<td><input type="text" class="wc_input_price nw_din_pris %s" value="%s" placeholder="%s" data-ori="%s" data-ven="%s" data-club="%s" name="nw_discounts[%s]"/></td><td class="%s" data-ven_id="%s">%s</td></tr>',$edited_class, NWP_Functions::nw_get_float_formatted_price($price), NWP_Functions::nw_get_float_formatted_price($placeholder), NWP_Functions::nw_get_float_formatted_price($prod_reg_price), NWP_Functions::nw_get_float_formatted_price($vendor_din_pris), NWP_Functions::nw_get_float_formatted_price($club_din_pris), $shop['id'], $addnal_class, $vendor_id, $addnal_html); // PLANASD-484 added data attributes + removed unrequired classes
            
                            // printf('<td><input type="number" step="1" value="%s" min="5" max="10000" placeholder="%s" name="nw_discounts[%s]"/><div class="nw-icon nw-reset-discount" style="%s"><span></span></div><div class="nw-icon nw-percentage"><span>x</span></div></td><td></td></tr>',
                            // 	$price, $placeholder, $shop['id'], $display_clear_btn);
                    }
                    static::render_table_end();
                    submit_button(
                        __('Save', 'newwave'),
                        'submit',
                        'save-discounts',
                        true,
                        array('data-nonce' => wp_create_nonce('nw-save-discounts'))
                    );
                ?></div>
        <?php
                }

                /**
                 * Save discounts set in discount panel via AJAX
                 *
                 */
                public static function ajax_save_discounts()
                {
                    if (!current_user_can('edit_products'))
                        wp_die(-1);

                    check_ajax_referer('nw-save-discounts', 'security');

                    // PLANASD - 484 temp - used default woo method to get product class as discounts present for all now
                    // $product = new WC_Product_NW_Stock($_POST['post_id']);
                    $product = wc_get_product($_POST['post_id']);
                    if ($product) {
                        parse_str($_POST['data'], $discounts);
                        $discounts = isset($discounts['nw_discounts']) ? $discounts['nw_discounts'] : array();
                        foreach ($discounts as $key => $value) {
                            if (empty($value))
                                unset($discounts[$key]);
                                $discounts[$key] = NWP_Functions::nw_get_float_price($value);
                        }
                        $product->set_discounts($discounts);
                        $product->save();
                    }

                    wp_die();
                }

                // PLANASD-484 added function to handle ajax call
                /**
                 * Restore discounts set for product via AJAX
                 *
                 */
                public static function ajax_restore_discounts() {
                    if (!current_user_can('edit_products'))
                        wp_die(-1);

                    check_ajax_referer('nw-restore-discounts', 'security');

                    $product = wc_get_product($_POST['post_id']);
                    if ($product) {
                        $discounts = $product->get_discounts();
                        $prod_type = $product->get_type();
                        $prod_reg_price = $product->get_regular_price();
                        $all_club_vendors = array();
                        $all_club_vend_dis = array();
                        foreach(NWP_Functions::get_shops() as $shop) {
                            if($shop["type"] != "vendor" && $shop["type"] != "club")
                                continue;

                            if(!isset($discounts[$shop['id']]))
                                continue;

                            if($shop["type"] == "vendor")
                                $shop_obj = new NW_Shop_Vendor($shop['id']);
                            else
                                $shop_obj = new NW_Shop_Club($shop['id']);

                            $din_price = $prod_reg_price;
                            $discount_percent = (float)$shop_obj->{"get_discount_$prod_type"}();
                            if($discount_percent) 
                                    $din_price = $prod_reg_price - ( $prod_reg_price * ($discount_percent/100) );

                                if($prod_type == 'nw_stock_logo')
                                    $din_price+=(float)$shop_obj->get_printing_price_nw_stock_logo();

                            // echo "<br/> ---- ".$shop["id"]." ---- ".$prod_reg_price." -- ".$discount_percent." =====> ".$din_price;

                            $discounts[$shop['id']] = $din_price;
                        }

                        $product->set_discounts($discounts);
                        $product->save();
                    }

                    wp_die();
                }

                /**
                 * Display panel to select shop that the product belongs to,
                 * which only applies to product types nw_stock_logo and nw_special
                 *
                 * @param WC_Product_NW_Stock_Logo|WC_Product_NW_Special $product
                 */
                public static function panel_shop($product)
                {
                    $selected = 0;
                    if ($product->is_type('nw_stock_logo') || $product->is_type('nw_special'))
                        $selected = $product->get_shop_id();
        ?>
            <div id="nw_shop_options" class="panel woocommerce_options_panel">
                <div class="options_group nw_shop">
                    <label><?php _e('Shop', 'newwave'); ?></label>
                    <select class="nw-select2" name="nw_shop">
                        <option <?php selected($selected, false); ?>>
                            <?php _e('No shop selected', 'newwave'); ?>
                        </option>
                        <?php foreach (NWP_Functions::query_clubs() as $club_id => $club) {
                            printf('<option value="%s" %s>%s</option>', $club_id, selected($selected, $club_id, false), $club['name']);
                        } ?>
                    </select>
                    <?php echo wc_help_tip(__('The shop that the product should appear in.', 'newwave')); ?>
                </div>
            </div>
        <?php
                }

                /**
                 * Display panel for selecting date till which product will be available for purchase
                 *
                 * @param WC_Product_NW_Stock_Logo|WC_Product_NW_Special $product
                 */

                public static function panel_sale_period($product)
                {
        ?>
            <div id="nw_sale_period_options" class="panel woocommerce_options_panel">
            <?php
                    woocommerce_form_field('nw_sale_period', array(
                        'type' => 'text',
                        'class' => array('form-field'),
                        'id' => 'nw_sale_period_date_picker',
                        'input_class' => array('short'),
                        'label' => __('Last date of purchase', 'newwave'),
                        'placeholder' => __('Select date', 'newwave')
                    ), $product->get_sale_period_date('format'));

                    wc_help_tip(__('The last date product can be purchased.', 'newwave'));

                    echo '</div>';
                }

                /**
                 * Output panel to save campaign enabled variations
                 *
                 * @param WC_Product_NW_Stock_Logo|WC_Product_NW_Special $product
                 */
                private static function panel_campaign($product)
                {
            ?>
                <div id="nw_campaign_enabled_variations_options" class="panel woocommerce_options_panel">
                    <?php

                    $attributes = $colors = $sizes = array();
                    $all_attributes = $product->get_variation_attributes();

                    // Get all colors associated with this product
                    foreach ($all_attributes['pa_size'] as $size_slug) {
                        if ($size_term = get_term_by('slug', $size_slug, 'pa_size'))
                            $sizes[$size_slug] = $size_term->name;
                    }

                    // Get all sizes associated with this product
                    foreach ($all_attributes['pa_color'] as $color_slug) {
                        if ($color_term = get_term_by('slug', $color_slug, 'pa_color'))
                            $colors[$color_slug] = $color_term->name;
                    }

                    // Map which sizes are available in which colors, e.g. $attributes['xl'] => ['sweden-blue', 'red'];
                    foreach ($product->get_children() as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        $variation_attributes = $variation->get_attributes();
                        if (isset($variation_attributes['pa_color']) && isset($variation_attributes['pa_size'])) {
                            if (!isset($attributes[$variation_attributes['pa_color']]))
                                $attributes[$variation_attributes['pa_color']] = array();

                            $attributes[$variation_attributes['pa_size']][$variation_attributes['pa_color']] = $variation_id;
                        }
                    }

                    ksort($colors);
                    uksort($sizes, 'NWP_Functions::sort_sizes');

                    $columns = array();
                    foreach ($colors as $slug => $name)
                        $columns[] = $name;

                    // Get enabled variations
                    $active_variations = $product->get_campaign_enabled_variations();

                    // Output the table
                    static::render_table_start('campaign-enabled-variations', $columns, false, __('Set variations that should be included in a campaign. These will be available for purchase for all campaign-enabled shops, once a campaign is active.', 'newwave'));

                    foreach ($sizes as $size_slug => $size_name) {
                        printf('<tr><td class="nw-label">%s</td>', $size_name);

                        foreach ($colors as $color_slug => $color_name) {
                            if (isset($attributes[$size_slug][$color_slug])) {
                                $id = $attributes[$size_slug][$color_slug];
                                printf(
                                    '<td><input type="checkbox" name="nw_campaign_enabled_variations[]" value="%s" %s/></td>',
                                    $id,
                                    checked(in_array($id, $active_variations), true, false)
                                );
                            } else
                                printf('<td></td>');
                        }
                        printf('<td></td></tr>');
                    }

                    static::render_table_end();
                    submit_button(
                        __('Save', 'newwave'),
                        'submit',
                        'save-campaign-enabled-variations',
                        true,
                        array('data-nonce' => wp_create_nonce('nw-save-campaign-enabled-variations'))
                    );

                    ?></div>
            <?php // close panel
                }

                /**
                 * Save campaign enabled variations via AJAX
                 *
                 */
                public static function ajax_save_campaign_enabled_variations()
                {
                    if (!current_user_can('edit_products'))
                        wp_die(-1);

                    check_ajax_referer('nw-save-campaign-enabled-variations', 'security');

                    $post_id = sanitize_text_field($_POST['post_id']);
                    $product = wc_get_product($post_id);
                    if (!$product->is_type('nw_stock'))
                        wp_die();

                    parse_str($_POST['data'], $post_data);
                    if (isset($post_data['nw_campaign_enabled_variations']))
                        $product->set_campaign_enabled_variations($post_data['nw_campaign_enabled_variations']);
                    else
                        $product->set_campaign_enabled_variations(array());

                    $product->save();

                    wp_die();
                }

                /**
                 * Save additional data that comes with the custom product
                 * types nw_stock, nw_stock_logo and nw_special
                 *
                 * @param WC_Product $product
                 */
                public static function save($product)
                {
                    if (!is_a($product, 'WC_Product_NW_Base') && !is_a($product, 'WC_Product_NWP_Base'))
                        return;

                    if (isset($_POST['_regular_price'])) {
                        $product->set_price(sanitize_text_field($_POST['_regular_price']));
                        if ($product->is_type('nw_stock_logo') || $product->is_type('nw_stock') || $product->is_type('nw_special')) {
                            // set_price() updates _regular_price & _price but _price gets overridens
                            // Force update _price
                            update_post_meta($product->get_id(), '_price', sanitize_text_field($_POST['_regular_price']));
                            // Update same for variations
                            foreach ($product->get_children() as $variation_id) {
                                // $variation = wc_get_product($variation_id);
                                // $variation->set_price(sanitize_text_field($_POST['_regular_price']));
                                // set_price() doesn't update variation prices. Force update.
                                update_post_meta($variation_id, '_regular_price', sanitize_text_field($_POST['_regular_price']));
                                update_post_meta($variation_id, '_price', sanitize_text_field($_POST['_regular_price']));
                            }
                        }
                    }

                    // Save attributes for custom products only
                    if ($product->is_type('nw_stock') && get_option('_nw_shop_feature')) {
                        // Save color access
                        if (isset($_POST['nw_color_access']) && !is_string($_POST['nw_color_access']))
                            $product->set_color_access($_POST['nw_color_access']);
                        else
                            $product->set_color_access(array());

                        // PLANASD - 484 temp - commented and moved out as discounts will be present for all
		                /*
                        // Save discounts
                        if (isset($_POST['nw_discounts']))
                            $product->set_discounts($_POST['nw_discounts']);
                        else
                            $product->set_discounts(array());
                        */

                        if (isset($_POST['nw_campaign_enabled_variations']))
                            $product->set_campaign_enabled_variations($_POST['nw_campaign_enabled_variations']);
                        else
                            $product->set_campaign_enabled_variations(array());
                    }

                    // PLANASD - 484 temp - code added to set discounts for the product
                    // Save discounts
                    if (isset($_POST['nw_discounts']) && get_option('_nw_shop_feature')) {
                        $discounts = isset($_POST['nw_discounts']) ? $_POST['nw_discounts'] : array();
                        foreach ($discounts as $key => $value) {
                            if (empty($value))
                                unset($discounts[$key]);
                            $discounts[$key] = NWP_Functions::nw_get_float_price($value);
                        }
                        $product->set_discounts($discounts);
                    }
                    else
                        $product->set_discounts(array());

                    // Will apply to both stock_logo and special
                    if ($product->is_type('nw_special')) {
                        if (isset($_POST['nw_sale_period']))
                            $product->set_sale_period_date($_POST['nw_sale_period'], 'format');

                        if (isset($_POST['nw_shop']))
                            $product->set_shop_id(absint($_POST['nw_shop']));
                            if (isset($_POST['nw_logo_price']))
				                update_post_meta($product->get_id(), 'nw_logo_price', sanitize_text_field($_POST['nw_logo_price']));
                    }
                    if ($product->is_type('nw_stock_logo')) {
                        // if (isset($_POST['nw_sale_period']))
                        // 	$product->set_sale_period_date($_POST['nw_sale_period'], 'format');

                        if (isset($_POST['nw_shop']))
                            $product->set_shop_id(absint($_POST['nw_shop']));

                        if (isset($_POST['print_instructions'])) {
                            update_post_meta($product->get_id(), 'print_instructions', sanitize_text_field($_POST['print_instructions']));
                        }
                        if (isset($_POST['nw_logo_price'])) {
                            update_post_meta($product->get_id(), 'nw_logo_price', sanitize_text_field($_POST['nw_logo_price']));
                        }
                    }
                }

                /**
                 * Render admin product data panel table header
                 *
                 * @param string $name Name of table
                 * @param string[] $columns Columns rendered in thead
                 * @param bool $search Whether to output search field or not
                 * @param string $tooltip Tooltip text
                 */
                private static function render_table_start($name, $columns, $search = false, $tooltip = '', $additional_html = '') { // PLANASD-484 added variable $additional_html to pass any extra html in the div
                    // Print actual table and header
            ?>
                <div class="nw-table-container nw-<?php echo $name; ?>"><?php echo $additional_html; ?><table id="nw-<?php echo $name; ?>" class="nw-table">
                        <thead>
                            <th><?php
                                if ($tooltip) {
                                    echo wc_help_tip($tooltip);
                                }
                                if ($search) : ?>
                                    <div class="nw-search">
                                        <input placeholder="<?php _e('Filter', 'newwave'); ?>" type="text" />
                                        <div class="nw-icon nw-clear-input"><span></span></div>
                                    </div>
                                <?php endif; ?>
                            </th>
                            <?php foreach ($columns as $column) {
                                printf('<th>%s</th>', $column);
                            } ?>
                            <th></th>
                        </thead>
                        </tbody>
                    <?php
                }

                /**
                 * Render admin product data panel table end, and close table container
                 *
                 */
                private static function render_table_end()
                {
                    printf('</tbody></table></div>');
                }

                /**
                 * Output panel to printing instructions for product type ' stock product with logo'
                 */

                private static function panel_print_instructions($product)
                {
                    ?>
                        <div id='nw_printingInstructions_options' class='panel woocommerce_options_panel'>
                            <div class="options_group">
                                <?php
                                woocommerce_wp_textarea_input(
                                    array(
                                        'id'        => 'print_instructions',
                                        'label'     =>  __('Trykkeri'),
                                        'type'      => 'text',
                                        'desc_tip'  => __('Instruksjoner for logostørrelse og plassering')
                                    )
                                );
                                ?>
                            </div>
                        </div>
            <?php
                }
            }
            ?>