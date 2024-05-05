<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Register taxonomy handling sports images for clubs
     *
     */
    class NW_Sport_Banner_CPT
    {

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Register the sport banner post type
            add_action('init', __CLASS__ . '::register_post_type');

            // Hide screen options
            add_action('screen_options_show_screen', __CLASS__ . '::remove_screen_options');

            // Set layout
            add_action('get_user_option_screen_layout_nw_sport_banner', __CLASS__ . '::set_columns');

            // Enqueue custom assets
            add_action('admin_head', __CLASS__ . '::enqueue_assets');

            // Deactivate autosave
            add_action('admin_enqueue_scripts', __CLASS__ . '::deactive_auto_save');

            // Add custom image size
            add_filter('admin_post_thumbnail_size', __CLASS__ . '::change_admin_image_size', 99, 3);

            // Register custom save and delete buttons
            add_action('add_meta_boxes_nw_sport_banner', __CLASS__ . '::register_buttons');

            // Action when deleting a sport banner
            add_action('save_post_nw_sport_banner', __CLASS__ . '::delete');

            // Add custom columns the sport banner overview
            add_filter('manage_edit-nw_sport_banner_columns', __CLASS__ . '::add_columns', 99, 1);
            add_filter('manage_nw_sport_banner_posts_custom_column', __CLASS__ . '::add_column_data', 99, 2);

            // Disable bulk edit post type
            add_filter('bulk_actions-edit-nw_sport_banner', '__return_empty_array');

            // Customize general interaction messages (admin notices) for this post type
            add_filter('post_updated_messages', array(get_called_class(), 'edit_exisiting_admin_notices'), 99);
        }

        /**
         * Add thumbnail column
         *
         * @param string[] $columns
         * @param string[]
         */
        public static function add_columns($columns)
        {
            $columns = array(
                'sport-banner' => '',
                'name' => __('Sport', 'newwave'),
            );
            return $columns;
        }

        /**
         * Add column data; name and thumbnail image
         *
         * @param string $column
         * @param int $post_id
         */
        public static function add_column_data($column, $post_id)
        {
            if ($column == 'sport-banner') {
                printf(
                    '<a href="%s">%s</a>',
                    get_edit_post_link($post_id),
                    get_the_post_thumbnail($post_id, 'nw_sport_banner_thumbnail')
                );
            } else if ($column == 'name') {
                $name = get_the_title($post_id);
                if (empty($name))
                    $name = __('No name', 'newwave');

                printf('<a href="%s">%s</a>', get_edit_post_link($post_id), $name);
            }
        }

        /**
         * De-register script that enables autosave
         *
         */
        public static function deactive_auto_save()
        {
            wp_deregister_script('autosave');
        }

        /**
         * Change the size of the featured image displayed in admin
         *
         * @param string $size
         * @param int $thumbnail_id
         * @param WP_Post $post
         * @return string
         */
        public static function change_admin_image_size($size, $thumbnail_id, $post)
        {
            if ($post->post_type == 'nw_sport_banner')
                return 'nw_sport_banner';
            return $size;
        }

        /**
         * Register sport banner post type and add image size
         *
         */
        public static function register_post_type()
        {
            register_post_type(
                'nw_sport_banner',
                array(
                    'description' => '',
                    'public' => false,
                    'exclude_from_search' => true,
                    'publicly_queryable' => false,
                    'show_ui' => true,
                    'show_in_nav_menus' => false,
                    'show_in_menu' => 'upload.php',
                    'supports' => array('title', 'thumbnail'),
                    'labels' => array(
                        'name' => __('Sport Banners', 'newwave'),
                        'singular_name' => __('Sport banners', 'newwave'),
                        'add_new' => __('New image', 'newwave'),
                        'add_new_item' => __('Add new image', 'newwave'),
                        'edit_item' => __('Edit image', 'newwave'),
                        'search_items' => __('Search images', 'newwave'),
                        'featured_image' => __('Image', 'newwave'),
                        'set_featured_image' => __('Select image', 'newwave'),
                        'remove_featured_image' => '',
                        'use_featured_image' => __('Select as image', 'newwave')
                    )
                )
            );
            add_image_size('nw_sport_banner', 1500, 600, true);
            add_image_size('nw_sport_banner_thumbnail', 500, 200, true);
        }


        /**
         * Enqueue custom CSS for restyling of admin pages
         * (coloring buttons, removing white wrappers around meta boxes and such)
         *
         */
        public static function enqueue_assets()
        {
            $screen = get_current_screen();
            if ($screen && $screen->post_type == 'nw_sport_banner') {
                wp_enqueue_style('nw_sport_banner_css', NW_PLUGIN_URL . 'assets/css/admin_cpt_sport_banner.css');
            }
        }


        /**
         * Allow only 1 column for the layout when creating/editing the post type
         *
         */
        public static function set_columns()
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
            $messages['nw_sport_banner'] = array(
                1 => __('Image updated.', 'newwave'),
                4 => __('Image updated.', 'newwave'),
                7 => __('Image created.', 'newwave')
            );
            return $messages;
        }

        /**
         * Remove screen options button (all fields displayed are required)
         *
         * @param bool $keep
         * @return bool True if to keep button, false if not
         */
        public static function remove_screen_options($keep)
        {
            if ('nw_sport_banner' == get_current_screen()->post_type)
                return false;
            return $keep;
        }

        /**
         * Register a meta box for custom save and delete buttons
         *
         */
        public static function register_buttons()
        {
            add_meta_box(
                'nw_sport_banner',
                'nw_sport_banner meta data',
                __CLASS__ . '::display_buttons',
                'nw_sport_banner',
                'normal',
                'high'
            );
        }

        /**
         * Output custom save and delete buttons
         *
         * @param int $post_id
         */
        public static function display_buttons($post_id)
        {
            submit_button(__('Save', 'newwave'), 'primary', 'nw_save');
            submit_button(__('Delete', 'newwave'), 'nw-delete nw-alert', 'nw_delete', true, array(
                'data-nw-alert' => __('Delete image?', 'newwave')
            ));
        }

        /**
         * Delete the sport banner
         *
         * @param int $post_id
         */
        public static function delete($post_id)
        {
            if (isset($_POST['nw_delete'])) {
                wp_delete_post($post_id);
                wp_redirect('edit.php?post_type=nw_sport_banner');
                exit;
            }
        }
    }
