<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Add extra columns and edit possibilities to the WP 'User' admin section
     *
     */
    class NW_User_Admin
    {

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Add custom columns and sorting options
            add_filter('manage_users_columns', __CLASS__ . '::add_club_column', 99, 1);
            add_filter('manage_users_custom_column', __CLASS__ . '::shop_column_content', 99, 3);
            add_action('restrict_manage_users', __CLASS__ . '::shop_filter');
            add_filter('pre_get_users', __CLASS__ . '::shop_filter_query', 99, 1);

            // Add inputs to edit which shops a user is registered with
            add_action('edit_user_profile', __CLASS__ . '::add_club_select', 10, 1);
            add_action('edit_user_profile', __CLASS__ . '::add_pay_byInvoice_select', 10, 1);

            // Save clubs for user
            add_action('edit_user_profile_update', __CLASS__ . '::save_club', 10, 1);
            add_action('edit_user_profile_update', __CLASS__ . '::save_pay_byInvoice', 10, 1);

            // Enqueue custom assets
            add_action('admin_enqueue_scripts', __CLASS__ . '::enqueue_assets', 99);
        }

        /**
         * Enqueue custom assets
         *
         */
        public static function enqueue_assets()
        {
            if ('users' == get_current_screen()->base || 'user-edit' == get_current_screen()->base)
                NWP_Functions::enqueue_script('', array('select2'));
        }

        /**
         * Add 'select' to set or remove clubs that the user should be registered to
         *
         * @param WP_User
         */
        public static function add_club_select($user)
        {
            $shops = NWP_Functions::unpack_list(get_user_meta($user->ID, '_nw_shops', true));
            if (!is_array($shops))
                $shops = array();

            printf('<table class="form-table"><th>%s</th>', __('Clubs', 'newwave'));
            printf('<td><select name="nw_clubs[]" class="nw-select2" multiple="multiple" style="width:25em;">');
            foreach (NWP_Functions::query_clubs() as $club) {
                $selected = in_array($club['id'], $shops) ? 'selected' : '';
                printf('<option value="%s" %s>%s</option>', $club['id'], $selected, $club['name']);
            }
            printf('</select></td></tr></table>');
        }

        /**
         * Add checkbox for pay by invoice
         *
         * @param WP_User
         */
        public static function add_pay_byInvoice_select($user)
        {
            $pay_by_invoice_val = get_user_meta($user->ID, 'must_pay_by_invoice', true);

            $checked = isset($pay_by_invoice_val) && $pay_by_invoice_val == 1 ? 'checked' : '';
            printf('<table class="form-table"><th>%s</th>', __('Betaling', 'newwave'));
            printf('<td><input type="checkbox" id="pay_by_invoice" name="pay_by_invoice" value="1" %s ><label for="pay_by_invoice"> skal betale med faktura</label></td></table>', $checked);
        }

        /**
         * Update changes made to what clubs the user is registered to
         *
         * @param int $user_id
         */
        public static function save_club($user_id)
        {
            $clubs = isset($_POST['nw_clubs']) && is_array($_POST['nw_clubs']) ? $_POST['nw_clubs'] : array();
            update_user_meta($user_id, '_nw_shops', NWP_Functions::pack_list($clubs));
        }

        /**
         * Update whether user want to pay by invoice
         *
         * @param int $user_id
         */
        public static function save_pay_byInvoice($user_id)
        {
            $pay_by_invoice_val = isset($_POST['pay_by_invoice']) ? $_POST['pay_by_invoice'] : '';
            update_user_meta($user_id, 'must_pay_by_invoice', $pay_by_invoice_val);
        }

        /**
         * Add custom colums to user admin
         *
         * @param array $columns
         */
        public static function add_club_column($columns)
        {
            $columns = array_slice($columns, 0, 4, true)
                + array('shop' => __('Club', 'newwave'))
                + array('money_spent' => __('Purchased for', 'newwave'))
                + array('orders' => __('Orders', 'newwave'))
                + array_slice($columns, 0, null, true);
            return $columns;
        }

        /**
         * Get data that goes in the custom columns 'shop', 'money_spent' and 'orders'
         *
         * @param $val Value to print.
         * @param $column_name Current column name.
         * @param $user_id ID of the user (current row)
         * @return string
         */
        public static function shop_column_content($val, $column_name, $user_id)
        {
            if ($column_name == 'shop') {
                $user_shop_ids = NWP_Functions::unpack_list(get_user_meta($user_id, '_nw_shops', true));

                if (!empty($user_shop_ids)) {
                    $names = array();
                    foreach ($user_shop_ids as $club_id)
                        array_push($names, get_the_title($club_id));

                    return implode(', ', $names);
                } else
                    return '—';
            }

            if ($column_name == 'money_spent') {
                $sum = get_user_meta($user_id, '_money_spent', true);
                if (!$sum)
                    $sum = 0;
                return wc_price($sum);
            }

            if ($column_name == 'orders') {
                $count = get_user_meta($user_id, '_order_count', true);
                if ($count)
                    return  $count;
                return 0;
            }

            return $val;
        }

        /**
         * Output select-tag and options to filter posts based on post parent
         *
         * @param string $position Current position ('top' or 'bottom' of table)
         */
        public static function shop_filter($position)
        {
            if ($position == 'top') {
                $selected = false;
                if (isset($_REQUEST['shop_sorting']))
                    $selected = $_REQUEST['shop_sorting'];

                $clubs = NWP_Functions::query_clubs(); ?>
                <div style="margin-left:8px;display:inline-block;">
                    <select class="nw-select2" name="shop_sorting">
                        <option value="0"><?php _e('All clubs', 'newwave'); ?></option>
                        <?php foreach ($clubs as $club_id => $club) : ?>
                            <option value="<?php echo $club_id; ?>" <?php selected($selected, $club_id); ?>>
                                <?php echo $club['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" style="margin-left:3px;" class="button" value="<?php _e('Filter', 'newwave'); ?>">
                </div>
<?php
            }
        }

        /**
         * Modifies the search query  to filter by user meta if a shop id is set
         *
         * @param WP_Query $query
         */
        public static function shop_filter_query($query)
        {
            if (isset($_REQUEST['shop_sorting']) && $_REQUEST['shop_sorting']) {
                $query->set('meta_query', array(
                    array(
                        'key' => '_nw_shops',
                        'value' => ',' . $_REQUEST['shop_sorting'] . ',',
                        'compare' => 'LIKE',
                    )
                ));
            }
            return $query;
        }
    }
?>