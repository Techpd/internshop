<?php

// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * General helper functions
     */

    class NWP_Functions
    {

        /**
         * @var array Cached hierachical shop structure
         */

        private static $structure;


        /**
         * @var array Cached queried clubs
         */

        private static $clubs;

        /**
         * @var array Cached queried vendors
         */

        private static $vendors;

        /**
         * @var array Cached queried groups
         */
        private static $groups;

        public static function init(){
            add_filter('post_class', __CLASS__ . '::nw_add_expired_class');
            add_action('woocommerce_before_shop_loop_item',  __CLASS__ . '::nw_add_sale_period_badge', 99);
            add_action('woocommerce_single_product_summary',  __CLASS__ . '::nw_add_sale_period_notice', 21);
        }

        /**
         * Custom function to check if is admin/backend, since is_admin() will be true on frontend AJAX calls
         * Adapted from https://snippets.khromov.se/determine-if-wordpress-ajax-request-is-a-backend-of-frontend-request/
         *
         * @return bool True if backend, false if frontend
         */
        public static function is_backend()
        {
            if (!is_admin())
                return false;

            $script_filename = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';

            if ((defined('DOING_AJAX') && DOING_AJAX)) {
                $ref = '';
                if (!empty($_REQUEST['_wp_http_referer']))
                    $ref = wp_unslash($_REQUEST['_wp_http_referer']);

                else if (!empty($_SERVER['HTTP_REFERER']))
                    $ref = wp_unslash($_SERVER['HTTP_REFERER']);

                //If referer does not contain admin URL and we are using the admin-ajax.php endpoint, this is likely a frontend AJAX request
                if (((strpos($ref, admin_url()) === false) && (basename($script_filename) === 'admin-ajax.php')))
                    return false;
            }
            return true;
        }

        /**
         * Enqueue plugin scripts
         *
         * @param string $script Name of script enqueue
         * @param string[] $deps Dependencies for $script
         * @param bool $helper Whether to load the general helper.js script. (default: true).
         */
        public static function enqueue_script($script = '', $deps = array(), $helper = true)
        {
            $name = 'nw_' . str_replace('.js', '', $script);
            $required = array('jquery');

            if (in_array('tooltip', $deps)) {
                wp_enqueue_script('jquery-tiptip');
                wp_enqueue_style('woocommerce_admin_styles');
                $required[] = 'jquery-tiptip';
            }

            if (in_array('media_upload', $deps)) {
                wp_enqueue_media();
            }

            if (in_array('modal', $deps)) {
                wp_enqueue_script('wc-backbone-modal');
                wp_enqueue_style('woocommerce_admin_styles');
                $required[] = 'wc-backbone-modal';
            }

            if (in_array('block', $deps)) {
                wp_enqueue_script('jquery-blockui');
                $required[] = 'jquery-blockui';
            }

            if (in_array('progressbar', $deps)) {
                wp_enqueue_script('jquery-ui-progressbar');
                $required[] = 'jquery-ui-progressbar';
            }

            if (in_array('select2', $deps)) {
                wp_enqueue_script('nw_select2', NW_PLUGIN_URL . 'assets/js/select2.full.min.js', array('jquery'));
                wp_enqueue_style('nw_select2', NW_PLUGIN_URL . 'assets/css/select2.css');
                $required[] = 'nw_select2';
            }

            if (in_array('toggle', $deps)) {
                wp_enqueue_script('nw_jquery_ui', NW_PLUGIN_URL . 'assets/js/jquery-ui.js', array('jquery'));
                wp_enqueue_script('nw_toggle', NW_PLUGIN_URL . 'assets/js/jquery.switchButton.js', array('nw_jquery_ui'));
                wp_enqueue_style('nw_toggle', NW_PLUGIN_URL . 'assets/css/jquery.switchButton.css');
                $required[] = 'nw_toggle';
            }

            if (in_array('date_picker', $deps)) {
                $required[] = 'jquery-ui-datepicker';
                wp_enqueue_style('jquery-ui-style');
            }

            // Standard assets
            if ($helper) {
                wp_enqueue_style('nw_helper', NW_PLUGIN_URL . 'assets/css/helper.css', array(), filemtime(NW_PLUGIN_DIR . 'assets/css/helper.css'));
                wp_enqueue_script('nw_helper', NW_PLUGIN_URL . 'assets/js/helper.js', $required, filemtime(NW_PLUGIN_DIR . 'assets/js/helper.js'));
            }

            // Finally, load the requested script
            if ($script && 'helper' != $script)
                wp_enqueue_script($name, NW_PLUGIN_URL . 'assets/js/' . $script, $required, filemtime(NW_PLUGIN_DIR . 'assets/js/' . $script));
        }

        /**
         * Simple wrapper function to enqueue plugin style sheets
         *
         * @param string $style Name of style sheet
         */
        public static function enqueue_style($style)
        {
            $name = 'nw_' . str_replace('.css', '', $style);
            wp_enqueue_style($name, NW_PLUGIN_URL . 'assets/css/' . $style, array(), filemtime(NW_PLUGIN_DIR . 'assets/css/' . $style));
        }

        /**
         * Unpack comma separated string
         *
         * @param string $data
         * @return array
         */

        public static function unpack_list($data)
        {
            if (!$data || !is_string($data))
                return array();

            if ($data[0] == ',')
                $data = substr($data, 1);

            if ($data[strlen($data) - 1] == ',')
                $data = substr($data, 0, strlen($data) - 1);

            if (empty($data))
                return array();

            return explode(',', $data);
        }

        /**
         * Convert array to comma separated string
         *
         * @param array $data
         * @param string $type Specified data type of array. (default: 'int').
         * @return string
         */

        public static function pack_list($data, $type = 'int')
        {
            if (is_int($data)) {
                if (absint($data))
                    return absint($data);
                else
                    return '';
            }

            if (is_string($data))
                return ',' . str_replace(',', '', $data) . ',';

            if (!is_array($data) || empty($data))
                return '';

            $sanitized = array();
            foreach ($data as $n) {
                if ($type == 'int' && absint($n))
                    $sanitized[] = absint($n);
                else if ($type == 'string')
                    $sanitized[] = str_replace(',', '', $n);
            }

            return ',' . implode(',', $sanitized) . ',';
        }

        /**
         * Function for use in usort, uasort and uksort, sorting size names/slugs,
         * in increasing order, e.g.; 3x, xs, s, m, x, xl, xxl, 4xl
         *
         * @param string $a First string to compare
         * @param string $b Second string to compare
         * @return bool True if $a is 'worth' more, false if not
         */

        public static function sort_sizes($a, $b)
        {
            $a_r = strrev($a);
            $b_r = strrev($b);

            if ($a_r[0] != $b_r[0])
                return ($a_r[0] <= $b_r[0]) ? -1 : 1;

            if (strcasecmp($a_r[0], 's') == 0)
                list($a, $b) = array($b, $a);

            if (strlen($a) != strlen($b))
                return (strlen($a) > strlen($b)) ? -1 : 1;

            return intval($a[0]) > intval($b[0]);
        }

        /**
         * Logs custom messages within plugin, writes them to local log file,
         * and sends an alert through Slack
         *
         * @param string $msg Message to send.
         * @param mixed $payload String, object or array for reference.
         * @param string $type Message type for Slack, either 'good', 'warning', 'danger'. (default: 'danger').
         */

        public static function log($msg, $payload = false, $type = 'danger')
        {
            try {
                // Generate random reference code and traceback
                $a = str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ');
                $n = str_shuffle('123456789');
                $ref_code = $n[0] . $n[1] . $a[0];
                $trace = debug_backtrace(0, 1);

                // Write to custom log file
                $log = sprintf("@%s\nRef: %s\nMessage: %s\n", date('H:i:s d-m-Y'), $ref_code, $msg);

                if ($payload !== false)
                    $log .= sprintf("Payload: %s\n", print_r($payload, true));

                $log .= sprintf(
                    "File: %s\nLine: %s\nFunction: %s\nArguments: %s",
                    $trace[0]['file'],
                    $trace[0]['line'],
                    $trace[0]['function'],
                    print_r($trace[0]['args'], true)
                );
                $log .= "\n\n";
                $f = fopen(NW_Plugin::$plugin_dir . 'includes/nw-product-synchronisation/nw-import.log', 'a+');
                fwrite($f, $log);
                fclose($f);
            } catch (Exception $e) {
            }
        }

        /**
         * Locate plugin or theme specific template (the latter overrides the former)
         *
         * @param string $name Name of the template
         * @return string|bool The path of located template, or false if not found
         */
        public static function locate_template($name)
        {
            $theme =  get_theme_file_path() . '/' . WC()->template_path() . $name;
            if (file_exists($theme))
                return $theme;

            $plugin = NW_PLUGIN_DIR . 'templates/' . $name;
            if (file_exists($plugin))
                return $plugin;

            return false;
        }

        /**
         * Output table start for settings section
         *
         * @param string $title Title of the section
         * @param string[] $classes Classes for the table. (default: array).
         */
        public static function settings_section_start($title, $classes = array())
        {
            printf('<h1>%s</h1><table class="form-table %s">', $title, implode(' ', $classes));
        }

        /**
         * Output table end for settings section
         *
         */
        public static function settings_section_end()
        {
            printf('</table>');
        }

        /**
         * Output settings row
         *
         * @param string $name Unique name for the setting.
         * @param string $type Type of HTML input.
         * @param string $value Value of the input.
         * @param string $label String to display.
         * @param array $args Additional arguments. (default: array).
         */
        public static function settings_row($name, $type, $value, $label, $args = array())
        {
            $defaults = array(
                'required' => false,
                'tooltip' => '',
                'row_classes' => array(),
                'input_classes' => array(),
                'attributes' => array(),
                'regex-pattern' => '',
                'regex-label' => '',
                'select2' => false,
                'datepicker' => false,
                'options' => array(),
                'placeholder' => '',
                'select_placeholder' => false,
                'min' => 0, // PLANASD - 484 - handled min/max
			    'max' => '' // PLANASD - 484 - handled min/max
            );
            $args = wp_parse_args($args, $defaults);

            static::settings_row_start($label, array(
                'name' => $name,
                'classes' => $args['row_classes'],
                'for' => $name,
                'tooltip' => $args['tooltip'],
            ));

            // Input is a <select>
            if ('select' == $type) {
                printf(
                    '<select id="%1$s" name="%1$s" class="%2$s %3$s">',
                    $name,
                    esc_attr(implode(' ', $args['input_classes'])),
                    $args['select2'] ? 'nw-select2' : ''
                );
                if ($args['placeholder']) {
                    printf(
                        '<option %s disabled>%s</option>',
                        $args['select_placeholder'] ? 'selected' : '',
                        $args['placeholder']
                    );
                }
                foreach ($args['options'] as $val => $name) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        $val,
                        $val == $value ? 'selected' : '',
                        $name
                    );
                }
            }

            // Input is a <textarea>
            else if ('textarea' == $type) {
                printf(
                    '<textarea id="%1$s" class="%2s$" name="%1$s">%3$s</textarea>',
                    $name,
                    esc_attr(implode(' ', $args['input_classes'])),
                    $value
                );
            }

            // Input is an actual <input>
            else {
                printf(
                    '<input type="%1$s" class="%2$s" id="%3$s" name="%3$s" ',
                    esc_attr($type),
                    esc_attr(implode(' ', $args['input_classes'])),
                    esc_attr($name)
                );
                if ('checkbox' == $type) {
                    printf($value ? 'checked="checked" data-toggle="toggle"' : '');
                } else {
                    printf('value="%s" placeholder="%s" ', esc_attr($value), $args['placeholder']);
                }

                // PLANASD-484 - handled min/max for type number
                if('number' == $type) {
                    if($args['min'] != '')
                        printf('min="%s"', $args['min']);
                    if($args['max'] != '')
                        printf('max="%s"', $args['max']);
                }

                if ($args['regex-pattern']) {
                    printf('pattern="%s" ', $args['regex-pattern']);
                    if ($args['regex-label'])
                        printf('data-tip="%s" ', $args['regex-label']);
                }

                foreach ($args['attributes'] as $key => $val) {
                    printf('%s="%s" ', esc_attr($key), esc_attr($val));
                }

                printf(
                    'aria-describedby="%s" %s/>',
                    'label-' . $name,
                    $args['required'] ? 'aria-required="true" required' : ''
                );
            }
            static::settings_row_end();
        }

        /**
         * Output the label and start of a settings row
         *
         */
        public static function settings_row_start($label, $args = array())
        {
            $defaults = array(
                'name' => '',
                'classes' => array(),
                'for' => '',
                'tooltip' => '',
            );

            $args = wp_parse_args($args, $defaults);
?>
            <tr valign="top" id="<?php if ($args['name']) echo 'row-' . $args['name']; ?>" class="<?php echo implode(' ', $args['classes']); ?>">
                <th scope="row">
                    <label id="<?php if ($args['name']) echo 'label-' . $args['name']; ?>" for="<?php echo esc_attr($args['for']); ?>">
                        <?php echo $label; ?>
                    </label>
                    <?php if ($args['tooltip']) echo wc_help_tip($args['tooltip']); ?>
                </th>
                <td>
                <?php
            }

            /**
             * Output the end of a settings row
             *
             */
            public static function settings_row_end()
            {
                ?></td>
            </tr><?php
                }

                /**
                 * Generator function for looping through the shop hierarchy
                 * (aka turning it to a flat structure)
                 * so that we don't need three, nested loops at each time
                 *
                 * @param string Type of shop that should be generated. (default: false).
                 * @return array With information about the shop
                 */
                public static function get_shops($type = false)
                {
                    if (empty(static::$structure))
                        static::build_shop_hierarchy();

                    foreach (static::$structure as $group) {
                        if (!$type || $type == 'group') {
                            yield $group;
                        }
                        foreach ($group['vendors'] as $vendor) {
                            if (!$type || $type == 'vendor') {
                                yield $vendor;
                            }
                            foreach ($vendor['clubs'] as $club) {
                                if (!$type ||  $type == 'club') {
                                    yield $club;
                                }
                            }
                        }
                    }
                }


                /**
                 * Get the shop hierarchy
                 *
                 * @return array
                 */
                public static function get_shop_hierarchy()
                {
                    if (empty(static::$structure))
                        static::build_shop_hierarchy();
                    return static::$structure;
                }

                /**
                 * Creates a hierarchical representation of the available stores,
                 * respective clubs themselves, and stores it statically
                 *
                 */
                private static function build_shop_hierarchy()
                {
                    // Fetch all posts of each post type
                    $clubs = static::query_clubs();
                    $vendors = static::query_vendors();
                    $groups = static::query_groups();

                    static::$structure = array();
                    foreach ($clubs as $club_id => $club) {
                        $vendor_id = $club['vendor_id'];
                        $vendor = $vendors[$vendor_id];
                        $group_id = $vendor['group_id'];

                        if (!isset($groups[$vendor['group_id']])) {
                            $group = array(
                                'id' => 0,
                                'name' => __('No group', 'newwave'),
                                'status' => 'nw_deactivated',
                                'type' => 'group',
                            );
                        } else {
                            $group = $groups[$vendor['group_id']];
                        }

                        // Initialize if first time we've seen this group
                        if (!isset(static::$structure[$group_id])) {
                            $group['vendors'] = array();
                            static::$structure[$group_id] = $group;
                        }

                        // Initialize if first time we've seen this vendor
                        if (!isset(static::$structure[$group_id]['vendors'][$vendor_id])) {
                            $vendor['clubs'] = array();
                            static::$structure[$group_id]['vendors'][$vendor_id] = $vendor;
                        }

                        // Finally, store the club in $structure
                        static::$structure[$group_id]['vendors'][$vendor_id]['clubs'][$club_id] = $club;
                    }
                }

                /**
                 * Query all clubs, if not already cached
                 *
                 * @param array
                 */
                public static function query_clubs($args=array())
                {
                    if (empty(static::$clubs)) {
                        static::$clubs = array();

                        // PLANASD-484 - added provision to add filter to wp query
                        $query_args = array(
                            'post_type' => 'nw_club',
                            'posts_per_page' => -1,
                        );

                        if(!empty($args['vendor_ids']) && is_array($args['vendor_ids']))
                            $query_args['post_parent__in'] = $args['vendor_ids'];

                        if(!empty($args['club_ids']) && is_array($args['club_ids']))
                            $query_args['post__in'] = $args['club_ids'];

                        $query = new WP_Query($query_args);

                        foreach ($query->posts as $post) {
                            static::$clubs[$post->ID] = array(
                                'id' => $post->ID,
                                'name' => $post->post_title,
                                'status' => $post->post_status,
                                'vendor_id' => $post->post_parent,
                                'term_tax_id' => absint($post->post_excerpt),
                                'type' => 'club',
                            );
                        }
                    }
                    return static::$clubs;
                }

                // PLANASD - 484 additional functions added for use across application ---- start
                /**
                 * Get  price value w.r.t. woo decimals
                 *
                 * @param array
                 */
                public static function nw_get_float_price($price) {
                    $decimal_separator = wc_get_price_decimal_separator();
                    return str_replace($decimal_separator, ".", $price);
                }

                /**
                 * Get formatted price value w.r.t. woo decimals
                 *
                 * @param array
                 */
                public static function nw_get_float_formatted_price($price) {
                    $decimal_separator = wc_get_price_decimal_separator();
                    return str_replace(".", $decimal_separator, $price);
                }
                // PLANASD - 484 additional functions added for use across application ---- end

                /**
                 * Query all vendor, if not already cached
                 *
                 * @param array
                 */
                public static function query_vendors($args = array())
                {
                    if (empty(static::$vendors)) {
                        static::$groups = array();
                        
                        // PLANASD-484 - added provision to add filter to wp query
                        $query_args = array(
                            'post_type' => 'nw_vendor',
                            'posts_per_page' => -1,
                        );

                        if(!empty($args['vendor_ids']) && is_array($args['vendor_ids']))
                            $query_args['post__in'] = $args['vendor_ids'];

                        $query = new WP_Query($query_args);

                        foreach ($query->posts as $post) {
                            static::$vendors[$post->ID] = array(
                                'id' => $post->ID,
                                'name' => $post->post_title,
                                'status' => $post->post_status,
                                'group_id' => $post->post_parent,
                                'term_tax_id' => absint($post->post_excerpt),
                                'type' => 'vendor',
                            );
                        }
                    }

                    return static::$vendors;
                }

                /**
                 * Query all groups, if not already cached
                 *
                 * @param array
                 */
                public static function query_groups()
                {
                    if (empty(static::$groups)) {
                        static::$groups = array();
                        $query = new WP_Query(array(
                            'post_type' => 'nw_group',
                            'posts_per_page' => -1,
                        ));
                        foreach ($query->posts as $post) {
                            static::$groups[$post->ID] = array(
                                'id' => $post->ID,
                                'name' => $post->post_title,
                                'status' => $post->post_status,
                                'term_tax_id' => absint($post->post_excerpt),
                                'type' => 'group',
                            );
                        }
                    }
                    return static::$groups;
                }

                /**
                 * Add class to products that have expired
                 *
                 */
                public static function nw_add_expired_class($classes)
                {
                    global $product;
                    if ($product && ($product->is_type('nw_special'))) {
                        if (!$product->within_sale_period())
                            $classes[] = 'expired';
                    }
                    return $classes;
                }

                /**
                 * Add sale period badge to applicable products in the products archive page
                 *
                 */
                public static function nw_add_sale_period_badge()
                {
                    global $product;
                    if ($product->is_type('nw_special')) {
                        if (!$product->within_sale_period()) {
                            $msg = sprintf(__('UTLØPT', 'newwave'));
                            $type = 'expired';
                        } else {
                            $date = date_i18n('d. M', $product->get_sale_period_date());
                            $msg = sprintf(__('FRIST %s', 'newwave'), $date);
                            $type = 'date';
                        }
                    ?>
                    <div class="newwave-sale-date <?php echo $type . ' ';
                        echo esc_attr(apply_filters('newwave_product_sale_date_class', 'newwave-product-badge')); ?>">
                        <div class="badge-inner">
                            <span>
                                <?php echo $msg; ?>
                            </span>
                        </div>
                    </div>
            <?php
                    }
                }
            

            /**
             * Add notice about sale period to product page for applicable products
             *
             */
            public static function nw_add_sale_period_notice()
            {
                global $product;
                if ($product->is_type('nw_special')) {
                    $date = date_i18n('d. M', $product->get_sale_period_date());
                    if (!$product->within_sale_period()) {
                        $msg = sprintf(__('This product expired %s. <br/> New production not yet planned. Get in touch with your club representatives.', 'newwave'), $date);
                        $type = 'expired';
                    } else {
                        $msg = sprintf(__('Bestillingsvinduet stenger %s', 'newwave'), $date);
                        $type = 'date';
                    }
                    //TODO add some hooks for classes below
        ?>
            <div class="newwave-sale-date <?php echo $type . ' '; 
            echo esc_attr(apply_filters('newwave_product_notice_class', 'newwave-product-notice')) ?>">
                <div class="notice-inner">
                    <span>
                        <?php echo $msg; ?>
                    </span>
                </div>
            </div> <?php
                }
            }


        }
        NWP_Functions::init();