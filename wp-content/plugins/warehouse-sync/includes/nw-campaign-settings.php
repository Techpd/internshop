<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Settings page for shop campaigns
     *
     */
    class NW_Campaign_Settings
    {

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Add page to WooCommerce menu
            add_action('admin_menu', __CLASS__ . '::add_to_menu', 99);

            // Register the settings
            add_action('admin_init', __CLASS__ . '::register_settings');

            // Enqueue custom assets
            add_action('admin_enqueue_scripts', __CLASS__ . '::enqueue_assets', 99);
        }

        /**
         * Enqueue custom assets
         *
         */
        public static function enqueue_assets()
        {
            if ('woocommerce_page_nw-campaign' == get_current_screen()->id) {
                NWP_Functions::enqueue_script('admin_campaign.js', array(
                    'date_picker',
                    'tooltip',
                    'toggle',
                    'media_upload'
                ));
            }
        }

        /**
         * Add settings page to WooCommerce menu
         *
         */
        public static function add_to_menu()
        {
            add_submenu_page(
                'woocommerce',
                'Campaign',
                _x('Campaign', 'Admin menu', 'newwave'),
                'manage_woocommerce',
                'nw-campaign',
                __CLASS__ . '::campaign_page'
            );
        }

        /**
         * Register all the custom settings
         *
         */
        public static function register_settings()
        {
            register_setting(
                'nw_campaign',
                'nw_campaign_status',
                array(
                    'type' => 'bool',
                    'default' => 0,
                )
            );
            register_setting('nw_campaign', 'nw_campaign_term_tax_id', array('type' => 'number', 'sanitize_callback' => __CLASS__ . '::set_campaign_term'));

            register_setting('nw_campaign', 'nw_campaign_discount', array(
                'type' => 'number',
                'sanitize_callback' => __CLASS__ . '::set_campaign_discount',
                'default' => 0,
            ));
            register_setting('nw_campaign', 'nw_campaign_start_date', array('default' =>  date('d-m-y')));
            register_setting('nw_campaign', 'nw_campaign_end_date', array('default' =>  date('d-m-y', strtotime('+1 day'))));
            register_setting('nw_campaign', 'nw_campaign_banner', array('type' => 'number'));
        }

        /**
         * Sanitize the discount and update the database discount table for correct sorting of
         * of prices when campaign prices are applied to products
         *
         * @param float|int $discount
         * @return float|int
         */
        public static function set_campaign_discount($discount)
        {
            $discount = absint($discount);
            $term_tax_id = get_option('nw_campaign_term_tax_id');
            if ($term_tax_id && absint(get_option('nw_campaign_discount')) != $discount) {
                global $wpdb;
                $factor = 1 - ($discount / 100);
                $discounts_table = $wpdb->prefix . NWP_TABLE_DISCOUNTS;
                $wpdb->query("UPDATE $discounts_table SET discount = (original * $factor) WHERE shop_term_tax_id = $term_tax_id");
            }
            return $discount;
        }

        /**
         * Generate a term for campaign for the taxonomy '_nw_access',
         * and store its term_taxonomy_id as a setting.
         * Since its generated we don't care what $input is, it can be whatever
         *
         * @param $input Bogus input
         * @return int The term taxonomy id of the term 'campaign'
         */
        public static function set_campaign_term($input)
        {
            if (term_exists('campaign', '_nw_access')) {
                $term = get_term_by('slug', 'campaign', '_nw_access');
            } else {
                $term = (object) wp_insert_term('campaign', '_nw_access');
            }

            return absint($term->term_taxonomy_id);
        }

        /**
         * Output the campaign settings
         *
         */
        public static function campaign_page()
        {
?>
            <div class="wrap">
                <h1><?php _e('Shop Campaign', 'newwave'); ?></h1>

                <form method="post" action="options.php">
                    <?php settings_fields('nw_campaign'); ?>
                    <?php do_settings_sections('nw_campaign'); ?>

                    <table class="form-table">
                        <?php

                        // Render a sentence describing the current campaign status
                        NWP_Functions::settings_row_start(_x('Current status', 'Campaign status', 'newwave'));
                        $start_date = strtotime(get_option('nw_campaign_start_date'));
                        $end_date = strtotime(get_option('nw_campaign_end_date'));
                        if (get_option('nw_campaign_status') == 'on') {
                            if ($start_date <= strtotime('today')) {
                                if ($end_date >= strtotime('today'))
                                    printf(__('Active and will end %s', 'newwave'), date_i18n('l j F', $end_date));
                                else
                                    printf(__('Ended %s', 'newwave'), date_i18n('l j F', $end_date));
                            } else {
                                printf(__('Will begin %s', 'newwave'), date_i18n('l j F', $start_date));
                            }
                        } else
                            _e('Not enabled', 'newwave');
                        NWP_Functions::settings_row_end();

                        // Input for campaign discount
                        NWP_Functions::settings_row(
                            'nw_campaign_discount',
                            'number',
                            get_option('nw_campaign_discount'),
                            _x('Discount in %', 'Campaign admin', 'newwave'),
                            array(
                                'attributes' => array(
                                    'min' => '0',
                                    'max' => '99',
                                    'step' => '1'
                                ),
                                'tooltip' => __('Percentage of discounts given to all products that are included in the campaign.', 'newwave'),
                            )
                        );

                        // Input for campaign status (on or off)
                        NWP_Functions::settings_row(
                            'nw_campaign_status',
                            'checkbox',
                            get_option('nw_campaign_status'),
                            _x('Set status', 'Campaign admin', 'newwave'),
                            array(
                                'input_classes' => array('nw-toggle'),
                                'attributes' => array(
                                    'data-toggle-on' => __('On', 'newwave'),
                                    'data-toggle-off' => __('Off', 'newwave'),
                                )
                            )
                        );

                        // Input for campaign start date
                        NWP_Functions::settings_row(
                            'nw_campaign_start_date',
                            'text',
                            get_option('nw_campaign_start_date'),
                            _x('Start date', 'Campaign admin', 'newwave'),
                            array('datepicker' => true)
                        );

                        // Input for campaign end date
                        NWP_Functions::settings_row(
                            'nw_campaign_end_date',
                            'text',
                            get_option('nw_campaign_end_date'),
                            _x('End date', 'Campaign admin', 'newwave'),
                            array('datepicker' => true)
                        );

                        // Input for campaign banner upload
                        NWP_Functions::settings_row_start(
                            __('Banner', 'newwave'),
                            array(
                                'tooltip' => sprintf(__('Recommended image size is %s', 'newwave'), '780x140'),
                            )
                        );

                        $img_id = get_option('nw_campaign_banner');
                        $image = $img_id ? wp_get_attachment_image_src($img_id, 'medium_large') : array('', '750', '300');
                        ?>
                        <div class="image-preview-wrapper">
                            <img id="image-preview" <?php if ($image[0]) printf('src=%s', $image[0]); ?> width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>">
                        </div>
                        <input class="nw-upload-media button" type="button" value="<?php _e('Select a banner', 'newwave'); ?>" />
                        <input type='hidden' name='nw_campaign_banner' id='image_attachment_id' value="<?php echo esc_attr($img_id); ?>">

                        <?php NWP_Functions::settings_row_end(); ?>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
<?php
        }
    }
?>