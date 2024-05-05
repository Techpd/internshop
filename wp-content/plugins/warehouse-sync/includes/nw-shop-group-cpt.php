<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Custom Post Type for 'Group', the top most object class in the
     * New Wave store hierarchy. Manage all sub-classes states
     * (activated or deactivated) and discount and the discount state.
     * A post instance of this type is required create a 'Vendor'.
     *
     */
    class NW_Shop_Group_CPT
    {

        /**
         * @var string Post type
         */
        const POST_TYPE = 'nw_group';

        /**
         * @var string Corresponding shop class
         */
        const SHOP_CLASS = 'NW_Shop_Group';

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Register post type and custom post statuses
            add_action('init', array(get_called_class(), 'register_post_type'));
            add_action('init', __CLASS__ . '::register_post_status');

            // Save and update hooks
            add_action('add_meta_boxes_' . static::POST_TYPE, array(get_called_class(), 'register_metabox'));
            add_action('save_post_' . static::POST_TYPE, array(get_called_class(), 'save_post'), 10, 1);

            // Customize post type table columns
            add_filter('manage_edit-' . static::POST_TYPE . '_columns', array(get_called_class(), 'add_columns'), 99, 1);
            add_filter('manage_' . static::POST_TYPE . '_posts_custom_column', array(get_called_class(), 'add_column_data'), 99, 2);
            add_filter('manage_edit-' . static::POST_TYPE . '_sortable_columns', array(get_called_class(), 'set_sortable_columns'), 99, 1);
            add_filter('pre_get_posts', array(get_called_class(), 'sort_columns'), 99, 1);

            // Disable ability to bulk edit post type
            add_filter('bulk_actions-edit-' . static::POST_TYPE, '__return_empty_array');

            // Customize admin page
            add_action('screen_options_show_screen', array(get_called_class(), 'remove_screen_options'));
            add_filter('get_user_option_screen_layout_' . static::POST_TYPE, array(get_called_class(), 'set_screen_layout'));
            add_action('admin_notices', array(get_called_class(), 'admin_notice'));
            add_action('admin_enqueue_scripts', array(get_called_class(), 'enqueue_assets'), 99);

            // Disable autosave
            add_action('admin_enqueue_scripts', array(get_called_class(), 'deactive_auto_save'), 99);

            // Remove 'sort by month' dropdown at post type admin page
            add_filter('months_dropdown_results', '__return_empty_array');

            // Customize general interaction messages (admin notices) for this post type
            add_filter('post_updated_messages', array(get_called_class(), 'edit_exisiting_admin_notices'), 99);
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
                        'name' => __('Groups', 'newwave'),
                        'singular_name' => __('Group', 'newwave'),
                        'add_new' => __('New group', 'newwave'),
                        'add_new_item' => __('Add new group', 'newwave'),
                        'edit_item' => __('Edit group', 'newwave'),
                        'search_items' => __('Search groups', 'newwave'),
                    )
                )
            );
        }

        /**
         * Register custom post statuses for all shop post typess
         *
         */
        public static function register_post_status()
        {
            register_post_status('nw_activated', array(
                'label' => __('Active', 'newwave'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'newwave')
            ));

            register_post_status('nw_deactivated', array(
                'label' => __('Deactivated', 'newwave'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Deactivated <span class="count">(%s)</span>', 'Deactivated <span class="count">(%s)</span>', 'newwave')
            ));
        }

        /**
         * Register the meta box for the post types custom data
         *
         */
        public static function register_metabox()
        {
            remove_meta_box('slugdiv', static::POST_TYPE, 'normal');
            add_meta_box(
                static::POST_TYPE,
                static::POST_TYPE . ' meta data',
                array(get_called_class(), 'display_meta_boxes'),
                static::POST_TYPE,
                'normal',
                'high'
            );
        }

        /**
         * De-register script that enables autosave
         *
         */
        public static function deactive_auto_save()
        {
            if (static::POST_TYPE == get_current_screen()->post_type) {
                wp_deregister_script('autosave');
            }
        }

        /**
         * Enqueue custom assets
         *
         */
        public static function enqueue_assets()
        {
            if (static::POST_TYPE == get_current_screen()->post_type) {
                NWP_Functions::enqueue_script('admin_cpt_stores.js', array(
                    'select2',
                    'tooltip',
                    'toggle'
                ));
                NWP_Functions::enqueue_style('admin_cpt_stores.css');
            }
        }

        /**
         * Remove screen options button (all fields displayed are required)
         *
         */
        public static function remove_screen_options($bool)
        {
            // If a different post type, do not remove button
            if (static::POST_TYPE == get_current_screen()->post_type) {
                return false;
            }

            return $bool;
        }

        /**
         * Allow only 1 column for the layout when creating/editing the post type
         *
         * @return int Always returns 1
         */
        public static function set_screen_layout()
        {
            return 1;
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
                1 => __('Group updated.', 'newwave'),
                4 => __('Group updated.', 'newwave'),
                7 => __('Group created.', 'newwave')
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
            );
        }

        /**
         * Display error as admin notice, if GET value is set
         *
         */
        public static function admin_notice()
        {
            // Should we display message?
            if (isset($_GET['nw_error'])) {

                // Is there any message to display?
                $notice = get_option('_nw_admin_notice');
                if (!empty($notice)) {
                    update_option('_nw_admin_notice', ''); ?>
                    <div class="notice notice-error is-dismissable">
                        <p><?php echo $notice; ?></p>
                    </div>
            <?php
                }
            }
        }

        /**
         * Modify columns displayed in admin table
         *
         * @param array $columns Ignored and replaced with local array
         */
        public static function add_columns($columns)
        {
            $columns = array();
            foreach (static::get_columns() as $key => $value) {
                $columns[$key] = $value['label'];
            }
            return $columns;
        }

        /**
         * Set which columns should be sortable by adding them to the
         *
         * @param array $columns associative array of columns
         */
        public static function set_sortable_columns($columns)
        {
            $sortable = array();
            foreach (static::get_columns() as $key => $value) {
                if ($value['sortable'])
                    $sortable[$key] = $key;
            }
            return $sortable;
        }


        /**
         * Custom sort function for some column data
         *
         * @param WP_Query $query
         */
        public static function sort_columns($query)
        {
            // If sorting column discount, sort by numbers instead of letters
            $orderby = $query->get('orderby');
            if ($orderby == 'discount') {
                $query->set('meta_key', 'nw_discount');
                $query->set('orderby', 'meta_value_num');
            }
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
            $columns = array_keys(static::get_columns());
            $class = static::SHOP_CLASS;
            $shop = new $class($post_id);

            if (in_array($column, $columns)) {
                call_user_func(array(get_called_class(), 'column_' . $column), $shop);
            }
        }

        /**
         * Add column data for customer id
         *
         * @param NW_Shop_Group $shop
         */
        protected static function column_shop_id($shop)
        {
            $id = $shop->get_shop_id();
            if ($id === false)
                echo '---';
            else
                echo $id;
        }

        /**
         * Add column data for 'name', which is just the post 'title'
         *
         * @param NW_Shop_Group $shop
         */
        protected static function column_name($shop)
        {
            $name = $shop->get_name();

            if (empty($name))
                $name = __('No name', 'newwave');

            printf('<a href="%s">%s</a>', get_edit_post_link($shop->get_id()), $name);
        }

        /**
         * Add column data for discount
         *
         * @param NW_Shop_Group $shop
         */
        protected static function column_discount($shop)
        {
            $discount = $shop->get_discount();

            if ($discount === false)
                echo '0%';
            else
                printf('%.1f%%', $discount);
        }

        /**
         * Add column data for the post status (either activated or deactivated)
         *
         * @param NW_Shop_Group $shop
         */
        protected static function column_status($shop)
        {
            if ($shop->is_activated())
                _e('Activated', 'newwave');

            else
                _e('Deactivated', 'newwave');
        }

        /**
         * Controller function triggering sub-functions
         * displaying different elements for admin page
         *
         * @param NW_Shop_Group $shop
         */
        public static function display_meta_boxes()
        {
            $group = new NW_Shop_Group(get_the_ID());

            ?><div class="wrap nw-settings">
                <?php
                NWP_Functions::settings_section_start(__('General', 'newwave'));
                static::display_status($group);
                static::display_shop_id($group);
                static::display_name($group);
                NWP_Functions::settings_section_end();
                submit_button(__('Save', 'newwave'));
                ?></div>
<?php
        }

        /**
         * Display status
         *
         * @param NW_Shop_Group $shop
         */
        protected static function display_status($shop)
        {
            NWP_Functions::settings_row(
                'nw_status',
                'checkbox',
                $shop->is_activated(),
                __('Status', 'newwave'),
                array(
                    'tooltip' => __('Set status for shop. If not activated, no users are able to log in.', 'newwave'),
                    'input_classes' => array('nw-toggle'),
                    'attributes' => array(
                        'data-toggle-on' => __('Activated', 'newwave'),
                        'data-toggle-off' => __('Deactivated', 'newwave'),
                    )
                )
            );
        }

        /**
         * Display customer id, a unique value which corresponds to New Waves
         * internal ASW system
         *
         * @param NW_Shop_Group $shop
         */
        protected static function display_shop_id($shop)
        {
            NWP_Functions::settings_row(
                'nw_customer_id',
                'text',
                $shop->get_shop_id(),
                __('Group ID', 'newwave'),
                array(
                    'required' => true,
                    'regex-pattern' => '^[1-6](\d{3,})(\D|$).*',
                    'regex-label' => __('Must be a number between 1000 and 6000', 'newwave'),
                )
            );
        }

        /**
         * Display shop name, which really just is the 'post_title'
         *
         * @param NW_Shop_Group $shop
         */
        protected static function display_name($shop)
        {
            NWP_Functions::settings_row(
                'nw_name',
                'text',
                $shop->get_name(),
                __('Group name', 'newwave'),
                array(
                    'required' => true,
                    'input_classes' => array('wide'),
                )
            );
        }

        /**
         * Parent function triggering functions to save data from $_POST
         *
         * @param int $post_id
         */
        public static function save_post($post_id)
        {
            if (get_post_status($post_id) == 'auto-draft')
                return;

            $group = new NW_Shop_Group($post_id);

            // If unable to save shop id, save post status as deactivated regardless
            if (static::save_shop_id($group))
                static::save_status($group);
            else
                static::save_status($group, false);

            static::save_name($group);
            static::save_term_tax_id($group);
            static::save_discount($group);

            $group->save();
        }

        /**
         * Save unique shop ID
         *
         * @param NW_Shop_Group $shop
         */
        protected static function save_shop_id($shop)
        {
            try {
                if (!isset($_POST['nw_customer_id']) || empty($_POST['nw_customer_id']))
                    throw new Exception(__('Must have a specified ID', 'newwave'));

                // Sanitize and try to set, will throw eror if not unique, or valid
                $shop->set_shop_id(sanitize_text_field($_POST['nw_customer_id']));
            } catch (Exception $e) {
                update_option('_nw_admin_notice', $e->getMessage());
                add_filter('redirect_post_location', array(get_called_class(), 'redirect'), 99, 2);
                return false;
            }
            return true;
        }

        /**
         * Save unique shop ID
         *
         * @param NW_Shop_Group $shop
         */
        protected static function save_shop_id_invoice($shop)
        {
            try {
                if (!isset($_POST['_nw_shop_id_invoice']) || empty($_POST['_nw_shop_id_invoice']))
                    throw new Exception(__('Must have a specified ID', 'newwave'));

                // Sanitize and try to set, will throw eror if not unique, or valid
                $shop->set_shop_id_invoice(sanitize_text_field($_POST['_nw_shop_id_invoice']));
            } catch (Exception $e) {
                update_option('_nw_admin_notice', $e->getMessage());
                add_filter('redirect_post_location', array(get_called_class(), 'redirect'), 99, 2);
                return false;
            }
            return true;
        }

        /**
         * Save value from the field 'name' (and store it as 'title')
         *
         * @param NW_Shop_Group $shop
         */
        protected static function save_name($shop)
        {
            if (isset($_POST['nw_name']) && strlen(trim($_POST['nw_name'])) > 0) {
                $name = sanitize_text_field($_POST['nw_name']);
                $shop->save_name($name, 'save_post_' . static::POST_TYPE, get_called_class() . '::save_post');
            }
        }

        /**
         * Save post status
         *
         * @param NW_Shop_Group|NW_Shop_Vendor|NW_Shop_Club $shop
         * @param bool $status Could be set if we want to ignore values in $_POST
         */
        protected static function save_status($shop, $status = null)
        {
            if ($status === null)
                $status = isset($_POST['nw_status']);

            $shop->save_status($status, 'save_post_' . static::POST_TYPE, get_called_class() . '::save_post');
        }

        /**
         * Create a term and store the taxonomy term id as the post excerpt
         *
         * @param NW_Shop_Group $shop
         */
        protected static function save_term_tax_id($shop)
        {
            $slug = (string) $shop->get_id();
            if (term_exists($slug, '_nw_access'))
                $term = get_term_by('slug', $slug, '_nw_access');
            else
                $term = (object) wp_insert_term($slug, '_nw_access');

            $shop->save_term_tax_id($term->term_taxonomy_id, 'save_post_' . static::POST_TYPE, get_called_class() . '::save_post');
        }

        /**
         * Save discount and its state from checkbox
         *
         * @param NW_Shop_Group $shop
         */
        protected static function save_discount($shop)
        {
            if (isset($_POST['nw_discount'])) {
                $shop->set_discount(sanitize_text_field($_POST['nw_discount']));
            }
        }

        /**
         * Called to add an admin notice error, when an exising post tries to
         * claim an exising shop id or an invalid one
         *
         * @param string $location
         * @param int $post_id
         */
        public static function redirect($location, $post_id)
        {
            return add_query_arg(array(
                'post' => $post_id,
                'action' => 'edit',
                'nw_error' => 'true'
            ), 'post.php');
        }
    }
?>