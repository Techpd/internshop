<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

    /**
     * Imports products from a temporary ASW API server
     *
     */
    class NW_User_Registration
    {

        /**
         * @var NW_Shop_Club Cached shop
         */
        public static $shop = NULL;

        /**
         * @var string Cached newly registered users first name
         */
        public static $first_name = '';

        /**
         * @var string Cached newly registered users last name
         */
        public static $last_name = '';

        /**
         * @var string Cached newly registered users phone number
         */
        public static $phone = '';

        /**
         * @var string Cached newly registered users gateadresse
         */
        public static $gateadresse = '';

        /**
         * @var string Cached newly registered users gateadresse
         */
        public static $apartmenttype = '';

        /**
         * @var string Cached newly registered users gateadresse
         */
        public static $postnummer = '';

        /**
         * @var string Cached newly registered users gateadresse
         */
        public static $sted = '';

        /**
         * Add hooks and filters
         *
         */
        public static function init()
        {
            // Add fields to the registration form
            add_action('woocommerce_register_form', __CLASS__ . '::add_registration_fields');

            // Process the custom registration fields
            add_action('woocommerce_process_registration_errors', __CLASS__ . '::validate_fields', 99);

            // Associate the new user with shop, specified by the submitted registration code
            add_action('user_register', __CLASS__ . '::do_user_registration', 99, 1);

            // Customize the password requirements
            add_filter('woocommerce_min_password_strength', __CLASS__ . '::change_password_requirement');
        }


        /**
         * Change password requirement
         *
         * @param $strength level of password 'strictness'
         */
        public static function change_password_requirement($strength)
        {
            $strength = get_option('nw_settings_password_strength') ? get_option('nw_settings_password_strength') : 2;
            return $strength;
        }

        /**
         * Output registration code field to front end
         *
         */
        public static function add_registration_fields()
        { ?>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="nw-first-name"><?php _e('First name', 'woocommerce'); ?><span class="required">*</span></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="nw_first_name" id="nw-first-name" />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="nw-last-name"><?php _e('Last name', 'woocommerce'); ?> <span class="required">*</span></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="nw_last_name" id="nw-last-name" />
            </p>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="nw_billing_gateadresse"><?php _e('Gateadresse', 'woocommerce'); ?> <span class="required">*</span></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="nw_billing_gateadresse" id="nw_billing_gateadresse" placeholder="Gatenavn og husnummer" />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="nw_apartment_type"><?php _e('Leilighet, suite, osv. (valgfritt)', 'woocommerce'); ?></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="nw_apartment_type" id="nw_apartment_type" />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="nw_postnummer"><?php _e('Postnummer', 'woocommerce'); ?> <span class="required">*</span></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="nw_postnummer" id="nw_postnummer" />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="nw_sted"><?php _e('Sted', 'woocommerce'); ?> <span class="required">*</span></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="nw_sted" id="nw_sted" />
            </p>

            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="nw-phone"><?php _e('Phone', 'woocommerce'); ?> <span class="required">*</span></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="nw_phone" id="nw-phone" />
            </p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <label for="nw-registration-code"><?php _e('Registration code', 'newwave'); ?> <span class="required">*</span></label>
                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="nw_registration_code" id="nw-registration-code" />
            </p>
<?php
        }

        /**
         * Validate that registration code exists, registration capping is not active
         * or that there are registrations left and that the club itself is active
         *
         * @param WP_Error $validation_error
         */
        public static function validate_fields($validation_error)
        {

            // Validate first name
            if (!isset($_POST['nw_first_name']) || empty($_POST['nw_first_name'])) {
                $validation_error->add('registration-code-error', __('Please enter your first name to register.', 'newwave'));
                return $validation_error;
            }
            static::$first_name = sanitize_text_field($_POST['nw_first_name']);

            // Validate last name
            if (!isset($_POST['nw_last_name']) || empty($_POST['nw_last_name'])) {
                $validation_error->add('registration-code-error', __('Please enter your last name to register.', 'newwave'));
                return $validation_error;
            }
            static::$last_name = sanitize_text_field($_POST['nw_last_name']);

            // Validate norwegian phone number
            $phone_err = __('Please enter a valid phone number to register.', 'newwave');
            if (!isset($_POST['nw_phone']) || empty($_POST['nw_phone'])) {
                $validation_error->add('registration-code-error', $phone_err);
                return $validation_error;
            }

            $phone = sanitize_text_field($_POST['nw_phone']);
            preg_match('/^(\+47)?((4|9)(\d{7}))/', $phone, $matches);
            if (!$matches) {
                $validation_error->add('registration-code-error', $phone_err);
                return $validation_error;
            }
            static::$phone = $matches[2];

            // Validate the registration code
            if (!isset($_POST['nw_registration_code'])) {
                $validation_error->add('registration-code-error', __('You need a registration code to register.', 'newwave'));
                return $validation_error;
            }

            $reg_code = strtoupper(sanitize_text_field($_POST['nw_registration_code']));
            $search = new WP_Query(array(
                'post_type' => 'nw_club',
                'meta_key' => '_nw_reg_code',
                'meta_value' => $reg_code
            ));

            if (!$search->found_posts) {
                $validation_error->add('registration-code-error', sprintf(__('Invalid registration code: %s', 'newwave'), $reg_code));
                return $validation_error;
            }

            $shop = new NW_Shop_Club($search->posts[0]->ID);

            if (!$shop->is_activated()) {
                $validation_error->add('club-inactive-error', __('The club you tried to register with is deactivated.', 'newwave'));
                return $validation_error;
            } else if (!$shop->is_capping_active() && $shop->get_no_users_registered() >= $shop->get_maximum_no_users()) {
                $validation_error->add('registration-code-error', sprintf(__('No more registration allowed using code %s.', 'newwave'), $reg_code));
                return $validation_error;
            }

            if (isset($_POST['nw_billing_gateadresse']) && empty($_POST['nw_billing_gateadresse'])) {
                $validation_error->add('registration-code-error', __('Vennligst skriv inn gateadresse Ã¥ registrere.', 'newwave'));
                return $validation_error;
            }
            static::$gateadresse = sanitize_text_field($_POST['nw_billing_gateadresse']);

            static::$apartmenttype = sanitize_text_field($_POST['nw_apartment_type']);

            if (isset($_POST['nw_postnummer']) && empty($_POST['nw_postnummer'])) {
                $validation_error->add('registration-code-error', __('Vennligst skriv inn postnummer Ã¥ registrere.', 'newwave'));
                return $validation_error;
            }
            static::$postnummer = sanitize_text_field($_POST['nw_postnummer']);

            if (isset($_POST['nw_sted']) && empty($_POST['nw_sted'])) {
                $validation_error->add('registration-code-error', __('Vennligst skriv inn sted Ã¥ registrere', 'newwave'));
                return $validation_error;
            }
            static::$sted = sanitize_text_field($_POST['nw_sted']);

            // Valid registration code, return with no set errors
            static::$shop = $shop;
            return $validation_error;
        }


        /**
         * Associate user with club id and increase number of users
         * that have been registered to that club
         *
         * @param int $user_id
         */
        public static function do_user_registration($user_id)
        {
            if (!empty(static::$first_name)) {
                update_user_meta($user_id, 'first_name', static::$first_name);
                update_user_meta($user_id, 'billing_first_name', static::$first_name);
                update_user_meta($user_id, 'shipping_first_name', static::$first_name);
            }

            if (!empty(static::$last_name)) {
                update_user_meta($user_id, 'last_name', static::$last_name);
                update_user_meta($user_id, 'billing_last_name', static::$last_name);
                update_user_meta($user_id, 'shipping_last_name', static::$last_name);
            }

            if (!empty(static::$phone))
                update_user_meta($user_id, 'billing_phone', static::$phone);

            if (!is_null(static::$shop)) {
                $shop = static::$shop;
                update_user_meta($user_id, '_nw_shops', NWP_Functions::pack_list($shop->get_id()));
                $shop->increment_users_registered();
                $shop->save();

                // Make sure to set user shop session, so that user is not instantly logged upon registration
                if (!WC()->session->has_session())
                    WC()->session->set_customer_session_cookie(true);
                WC()->session->set('nw_shop', $shop->get_id());
            }

            if (!empty(static::$gateadresse)) {
                update_user_meta($user_id, 'user_shipping_gateadresse', static::$gateadresse);
                update_user_meta($user_id, 'shipping_address_1', static::$gateadresse);
                update_user_meta($user_id, 'billing_address_1', static::$gateadresse);
            }
            if (!empty(static::$apartmenttype)) {
                update_user_meta($user_id, 'shipping_addtional_options', static::$apartmenttype);
                update_user_meta($user_id, 'shipping_address_2', static::$apartmenttype);
            }
            if (!empty(static::$postnummer)) {
                update_user_meta($user_id, 'user_postnummer', static::$postnummer);
                update_user_meta($user_id, 'shipping_postcode', static::$postnummer);
                update_user_meta($user_id, 'billing_postcode', static::$postnummer);
            }
            if (!empty(static::$sted)) {
                update_user_meta($user_id, 'user_sted', static::$sted);
                update_user_meta($user_id, 'shipping_city', static::$sted);
                update_user_meta($user_id, 'billing_city', static::$sted);
            }

            update_user_meta($user_id, 'shipping_country', 'NO');
            update_user_meta($user_id, 'billing_country', 'NO');
        }
    }
?>