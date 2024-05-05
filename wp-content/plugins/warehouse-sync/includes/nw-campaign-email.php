<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Settings page for sending campaigns emails to customers
     *
     */
    class NW_Campaign_Email
    {

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Add page to the WooCommerce menu
            add_action('admin_menu', __CLASS__ . '::add_to_menu', 99);

            // Register WP settings
            add_action('admin_init', __CLASS__ . '::register_settings');

            // Handle HTML preview of email
            add_action('admin_init', __CLASS__ . '::preview_emails');

            // Add resources to this page
            add_action('admin_enqueue_scripts', __CLASS__ . '::enqueue_assets', 99);

            // Register AJAX calls
            add_action('wp_ajax_nw_send_campaign_email', __CLASS__ . '::send_email');
            add_action('wp_ajax_nw_clear_email_cache', __CLASS__ . '::clear_email_cache');
        }

        /**
         * Enqueue custom assets
         *
         */
        public static function enqueue_assets()
        {
            if ('woocommerce_page_nw-campaign-email' == get_current_screen()->base) {
                NWP_Functions::enqueue_script('admin_campaign_email.js', array(
                    'modal',
                    'block',
                    'progressbar',
                    'date_picker',
                    'media_upload',
                    'tooltip',
                ));
                NWP_Functions::enqueue_style('admin_campaign_email.css');
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
                'Campaign Email',
                _x('Campaign Email', 'Admin menu', 'newwave'),
                'manage_woocommerce',
                'nw-campaign-email',
                __CLASS__ . '::campaign_email_page'
            );
        }

        /**
         * Register all the custom settings
         *
         */
        public static function register_settings()
        {
            register_setting('nw_campaign_email', 'nw_campaign_email_subject');
            register_setting('nw_campaign_email', 'nw_campaign_email_title');
            register_setting('nw_campaign_email', 'nw_campaign_email_content');
            register_setting('nw_campaign_email', 'nw_campaign_email_banner', array('type' => 'number'));
        }


        /**
         * Output settings for email content
         *
         */
        public static function campaign_email_page()
        {
?>
            <div class="wrap">
                <h1><?php _e('Campaign Email', 'newwave'); ?></h1>
                <p class="nw-notice"><?php _e('Rememeber to save your changes before sending', 'newwave'); ?></p>
                <form method="post" action="options.php">
                    <?php settings_fields('nw_campaign_email'); ?>
                    <?php do_settings_sections('nw_campaign_email'); ?>

                    <table class="form-table">
                        <?php

                        // Input for email subject
                        NWP_Functions::settings_row(
                            'nw_campaign_email_subject',
                            'text',
                            get_option('nw_campaign_email_subject'),
                            __('Subject line', 'newwave')
                        );

                        // Input for email title
                        NWP_Functions::settings_row(
                            'nw_campaign_email_title',
                            'text',
                            get_option('nw_campaign_email_title'),
                            __('Title', 'newwave')
                        );

                        // Input for email content
                        NWP_Functions::settings_row(
                            'nw_campaign_email_content',
                            'textarea',
                            get_option('nw_campaign_email_content'),
                            __('Content', 'newwave')
                        );

                        // Input for selecting a banner
                        NWP_Functions::settings_row_start(
                            __('Banner', 'newwave'),
                            array(
                                'tooltip' => sprintf(__('Recommended image size is %s', 'newwave'), '500x120'),
                            )
                        );
                        $img_id = get_option('nw_campaign_email_banner');
                        $image = $img_id ? wp_get_attachment_image_src($img_id, 'medium_large') : array('', '750', '300');
                        ?>
                        <div class="image-preview-wrapper">
                            <img id="image-preview" <?php if ($image[0]) printf('src=%s', $image[0]); ?> width="<?php echo $image[1]; ?>" height="<?php echo $image[2]; ?>">
                        </div>
                        <input type="button" class="button nw-upload-media" value="<?php _e('Select a banner', 'newwave'); ?>" />
                        <input type='hidden' name='nw_campaign_email_banner' id='image_attachment_id' value="<?php echo esc_attr($img_id); ?>">

                    </table>
                    <?php NWP_Functions::settings_row_end(); ?>
                    </table>

                    <div class="buttons-wrapper">
                        <?php submit_button(); ?>
                        <a class="button" target="_blank" href="<?php echo wp_nonce_url(admin_url('?preview_nw_campaign_email=true'), 'preview-email'); ?>"><?php _e('Preview', 'newwave'); ?></a>
                        <a class="button" id="nw-open-campaign-email-modal"><?php _e('Send Emails', 'newwave'); ?></a>
                    </div>
                </form>
            </div>
        <?php

            // Hook in action to render modal at page footer
            add_action('admin_footer', __CLASS__ . '::render_modal');
        }

        /**
         * Render the 'Send emails'-modal at in footer of the settings page
         *
         */
        public static function render_modal()
        {
            $users = array();

            // Check each user, if applicable for receviing email
            foreach (get_users() as $user) {
                $shop_ids = NWP_Functions::unpack_list(get_user_meta($user->ID, '_nw_shops', true));

                // Each shop user is registered in
                foreach ($shop_ids as $shop_id) {

                    // Cache loaded club
                    if (!$shop = wp_cache_get($shop_id, 'nw_shops')) {
                        $shop = new NW_Shop_Club($shop_id);
                        wp_cache_set($shop_id, new NW_Shop_Club($shop_id), 'nw_shops');
                    }

                    // If shop user is registered in is campaign enabled
                    if ($shop->has_campaign_ability()) {
                        if (!array_key_exists($user->ID, $users)) {
                            $users[$user->ID] = array(
                                'name' => sprintf('%s %s', $user->first_name, $user->last_name),
                                'email' => $user->user_email,
                                'shop' => array($shop->get_name()),
                            );
                        }
                        // Already added user, append this shops name to list for the user
                        else if (array_key_exists($user->ID, $users)) {
                            $users[$user->ID]['shop'][] = $shop->get_name();
                        }
                    }
                }
            }
        ?>
            <script type="text/template" id="tmpl-nw-modal-campaign-email">
                <div class="wc-backbone-modal">
			<div id="nw-modal-campaign-email" class="nw-modal wc-backbone-modal-content">
				<section class="wc-backbone-modal-main" role="main">
					<header class="wc-backbone-modal-header">
						<h1><?php _e('Send emails', 'newwave'); ?></h1>
						<button class="modal-close modal-close-link dashicons dashicons-no-alt"></button>
					</header>
					<article>
						<table id="nw-campaign-email" class="nw-table">
							<thead>
								<th><input type="checkbox" class="nw-check-all-vertical" /></th>
								<th><?php _e('Name', 'newwave'); ?></th>
								<th><?php _e('Email', 'newwave'); ?></th>
								<th><?php _e('Club', 'newwave'); ?></th>
							</thead>
							<tbody>
								<?php foreach ($users as $user) :
                                    $id = esc_attr('user-' . $user['name']);
                                ?>
									<tr>
										<td><input id="<?php echo $id; ?>" type="checkbox" data-send-to="<?php echo $user['email']; ?>"/></td>
										<td class="name"><label for="<?php echo $id; ?>"><?php echo $user['name']; ?></label></td>
										<td><label for="<?php echo $id; ?>"><?php echo $user['email']; ?></label></td>
										<td><label for="<?php echo $id; ?>"><?php echo implode(', ', $user['shop']); ?></label></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</article>
					<footer>
						<div class="inner">
							<button id="nw-send-emails" data-nonce="<?php echo wp_create_nonce('nw-send-emails-nonce'); ?>" class="button button-primary button-large"  disabled><?php _e('Send emails', 'newwave'); ?></button>
						</div>
					</footer>
				</section>
			</div>
		</div>
		<div class="wc-backbone-modal-backdrop modal-close"></div>
		</script>
<?php
        }

        /**
         * Send email to the address $_POST['data'] through AJAX
         *
         */
        public static function send_email()
        {
            if (!current_user_can('manage_woocommerce') || !isset($_POST['address']))
                wp_die(0);

            check_ajax_referer('nw-send-emails-nonce', 'security');

            $customer_email = sanitize_email($_POST['address']);
            if (!is_email($customer_email))
                wp_die(0);


            $mailer = WC()->mailer();
            $subject_line = get_option('nw_campaign_email_subject');

            // Cache the rendered email, no need to create it anew for each email address
            if (!$message = get_option('_nw_campaign_email_cache')) {
                $message = static::compose_email($mailer, $subject_line);
                update_option('_nw_campaign_email_cache', $message, 'no');
            }

            $result = $mailer->send($customer_email, $subject_line, $message);

            if (!$result) {
                NWP_Functions::log('Failed to send campaign email to ' . $customer_email, $message);
                wp_die(0);
            }

            // Tell client-side send was successful
            wp_die(1);
        }

        /**
         * Clear the cache of the sent email
         *
         */
        public static function clear_email_cache()
        {
            if (!current_user_can('manage_woocommerce'))
                wp_die(0);

            check_ajax_referer('nw-send-emails-nonce', 'security');

            delete_option('_nw_campaign_email_cache');
            wp_die(1);
        }

        /**
         * Preview email template
         *
         */
        public static function preview_emails()
        {
            if (isset($_GET['preview_nw_campaign_email'])) {
                if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'preview-email')) {
                    die('Security check');
                }

                $mailer = WC()->mailer();
                $subject_line = get_option('nw_campaign_email_subject');
                echo static::compose_email($mailer, $subject_line);
                exit;
            }
        }

        /**
         * Create the email
         *
         * @param WC_Email $mailer
         * @param string $subject_line
         * @return string HTML of the email
         */
        private static function compose_email($mailer, $subject_line)
        {
            $title = get_option('nw_campaign_email_title');
            $content = get_option('nw_campaign_email_content');
            $banner_id = get_option('nw_campaign_email_banner');
            $banner = $banner_id ? wp_get_attachment_image_src($banner_id, 'nw_sport_banner')[0] : false;

            // Render the email from template
            $template = NWP_Functions::locate_template('emails/campaign.php');
            if (!$template) {
                NWP_Functions::log('Campaign email template not located.');
                return '';
            }

            ob_start();
            include($template);
            $message       = ob_get_clean();
            $email         = new WC_Email();

            // Wrap the content with the email template and then add styles
            return apply_filters('woocommerce_mail_content', $email->style_inline($mailer->wrap_message($subject_line, $message)));
        }
    }
?>