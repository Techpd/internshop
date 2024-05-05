<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;
/**
 * New Wave Club Shop class
 *
 */
class NW_Shop_Club extends NW_Shop_Vendor {

	/**
	 * @var string Post type
	 */
	const POST_TYPE = 'nw_club';

	/**
	 * @var string Type of this class
	 */
	const TYPE = 'club';

	/**
	 * @var array All properties of $this club
	 */
	protected $data = array(
		'id' => 0,
		'shop_id' => false,
		'status' => 'auto-draft',
		'term_tax_id' => 0,
		'categories_count' => array(),
		'categories_campaign_count' => array(),
		'deactivated_by' => 'self',
		'name' => '',
		'registration_code' => false,
		'users_registered' => 0,
		'maximum_no_users' => 500,
		'capping_active' => false,
		'campaign_ability' => false,
                'open_shop_ability' => false,
		'club_onLogout' => false,
		'allowed_shipping' => 'vendor',
		'sport_banners' => array(),
		'vendor_id' => 0,
		'vendor_term_tax_id' => 0,
		'group_id' => 0,
		'group_term_tax_id' => 0,
		'poc' => '',
		'phone' => '',
		'address_1' => '',
		'address_2' => '',
		'postcode' => '',
		'city' => '',
		'freight_charge' => '',
		'no_freight_charge' => '',
		'club_email' => '',	
		// PLANASD - 484 added the custom fields added
		'discount_nw_stock' => '',
		'discount_nw_stock_logo' => '',
		'printing_price_nw_stock_logo' => '',
		'discount_nw_special' => '',
		'reset_all_products' => '',	
		'reset_to_default_vendor' => '',
		'webshop_message' => '',
	);

	/**
	 * @var array Mapping of properties to meta keys
	 */
	protected $meta_keys = array(
		'shop_id' => '_nw_shop_id',
		'categories_count' => '_nw_categories_count',
		'categories_campaign_count' => '_nw_categories_campaign_count',
		'registration_code' => '_nw_reg_code',
		'users_registered' => '_nw_users_registered',
		'maximum_no_users' => '_nw_maximum_no_users',
		'capping_active' => '_nw_capping_active',
		'campaign_ability' => '_nw_registered_for_campaigns',
                'open_shop_ability' => '_nw_open_shop_active',
		'club_onLogout' => '_nw_club_onLogout',
		'allowed_shipping' => '_nw_shipping_pref',
		'sport_banners' => '_nw_sport_banners',
		'poc' => '_nw_poc',
		'phone' => '_nw_phone',
		'address_1' => '_nw_address_1',
		'address_2' => '_nw_address_2',
		'postcode' => '_nw_postcode',
		'city' => '_nw_city',
		'freight_charge' => '_nw_freight_charge',
		'no_freight_charge' => '_nw_no_freight_charge',
		'club_email' => '_nw_club_email',
		// PLANASD - 484 added the custom fields added
		'discount_nw_stock' => '_nw_discount_nw_stock',
		'discount_nw_stock_logo' => '_nw_discount_nw_stock_logo',
		'printing_price_nw_stock_logo' => '_nw_printing_price_nw_stock_logo',
		'discount_nw_special' => '_nw_discount_nw_special',
		'reset_all_products' => '_nw_reset_all_products',
		'reset_to_default_vendor' => '_reset_to_default_vendor',
		'webshop_message' => '_nw_webshop_message'
	);

	/**
	 * @var array Inherited meta data (from post parents)
	 */
	protected $inherited_meta_data = array(
		'vendor_name' => '',
		'vendor_poc' => '',
		'vendor_phone' => '',
		'vendor_address_1' => '',
		'vendor_address_2' => '',
		'vendor_postcode' => 0,
		'vendor_city' => ''
	);

	/**
	 * @var bool Whether inherited meta data has been loaded
	 */
	protected $inherited_meta_data_imported = false;

	/**
	 * Read post values inherited from post parent, specific to this class
	 *
	 * @param string $id
	 */
	protected function read_inherited_properties($id) {
		$vendor_id = absint(wp_get_post_parent_id($id));
		$group_id = absint(wp_get_post_parent_id($vendor_id));

		if (!$vendor_id)
			return;
		$this->set_prop('vendor_id', $vendor_id);
		$this->set_prop('vendor_term_tax_id', absint(get_the_excerpt($vendor_id)));

		if (get_post_status($vendor_id) != 'nw_activated') {
			$this->set_prop('status', 'nw_deactivated');
			$this->set_prop('deactivated_by', 'vendor');
		}

		if ($group_id) { // If vendor has a group
			$this->set_prop('group_id', $group_id);
			$this->set_prop('group_term_tax_id', absint(get_the_excerpt($group_id)));

			if (get_post_status($group_id) != 'nw_activated') {
				$this->set_prop('status', 'nw_deactivated');
				$this->set_prop('deactivated_by', 'group');
			}
		}
	}

	/**
	 * Get property from $this->data, or if $prop is an inherited meta $prop,
	 * read it from parent if not already imported
	 *
	 * @param string $prop property to change
	 * @return mixed
	 */
	protected function get_prop($prop) {
		if (isset($this->inherited_meta_data[$prop])) {
			if (empty($this->inherited_meta_data['vendor_name'])) {
				$this->read_inherited_meta();
			}
			return $this->inherited_meta_data[$prop];
		}

		// if($prop == 'freight_charge') {
		// 	echo ':)<br>';
		// 	echo (isset($this->data[$prop]))? '_'.$this->data[$prop].'_': ':(';

		// 	DFA($this->data);
		// }

		if (isset($this->data[$prop]))
			return $this->data[$prop];
		return null;
	}

	/**
	 * Read inherited meta data from vendor
	 *
	 */
	private function read_inherited_meta() {
		$vendor = new NW_Shop_Vendor($this->get_vendor_id());
		$this->inherited_meta_data['vendor_name'] = $vendor->get_name();
		$this->inherited_meta_data['vendor_poc'] = $vendor->get_poc();
		$this->inherited_meta_data['vendor_phone'] = $vendor->get_phone();
		$this->inherited_meta_data['vendor_address_1'] = $vendor->get_address_1();
		$this->inherited_meta_data['vendor_address_2'] = $vendor->get_address_2();
		$this->inherited_meta_data['vendor_postcode'] = $vendor->get_postcode();
		$this->inherited_meta_data['vendor_city'] = $vendor->get_city();
	}


	/**
	 * Get the type of store
	 *
	 * @return string
	 */
	public function get_type() {
		return 'club';
	}

	/**
	 * Get the shop id, generates a new one if not set
	 *
	 * @param string $id
	 * @return bool true if successful, false if not
	 */
	public function get_shop_id() {
		if (!$this->get_prop('shop_id')) {
			$this->set_shop_id();
		}
		return $this->get_prop('shop_id');
	}
	
	/**
	 * Get the product access, generates a new one if not set
	 *
	 * @param string $id
	 * @return bool true if successful, false if not
	 */
	public function get_product_access() {
		if (!$this->get_prop('shop_id')) {
			$this->set_shop_id();
		}
		return get_site_url().'/?klubb='.$this->get_prop('shop_id');
	}
	
	/**
	 * Sets the shop ID to a random 4-digit number
	 *
	 */
	public function set_shop_id($id = 0) {
		if ($this->get_prop('shop_id')) // Already has a shop id, return
			return true;

		$count = new WP_Query(array( // start counting based on number of clubs
			'post_type' => static::POST_TYPE,
		));

		$count = $count->found_posts;

		while (true) { // make sure it's unique
			$id = sprintf('%03d', ++$count);
			$search = new WP_Query(array(
				'post_type' => static::POST_TYPE,
				'meta_key' => '_nw_shop_id',
				'meta_value' => $id
			));
			if (!$search->found_posts)
				break;
		}
		$this->set_prop('shop_id', $id);
		$this->changes['shop_id'] = false;
		update_post_meta($this->get_id(), $this->meta_keys['shop_id'], $id);
		return true;
	}

	/**
	 * Validate that vendor customer id begins with a number between 1000 and 6000
	 * Must be 5 digits followed by a non-digit character (and anything subsequent to that)
	 *
	 * @param string $id
	 * @return true if valid
	 * @throws Exception if id is not valid
	 */
	public static function validate_shop_id($id) {
		$matches = array();
		preg_match('/^(\d{3,})(\D|$)/', $id, $matches);
		if (count($matches)) {
			$n = absint($matches[1]);
			if ($n !== 0)
				return true;
		}
		throw new Exception(__('Vendor ID must begin with a number between 9999 and 100 000', 'newwave'));
	}

	/**
	 * Get vendor name
	 *
	 * @return string
	 */
	public function get_vendor_name() {
		return $this->get_prop('vendor_name');
	}

	/**
	 * Get first name
	 *
	 * @return string
	 */
	public function get_vendor_poc() {
		return $this->get_prop('vendor_poc');
	}


	/**
	 * Get last name
	 *
	 * @return string
	 */
	public function get_vendor_phone() {
		return $this->get_prop('vendor_phone');
	}

	/**
	 * Get address line 1
	 *
	 * @return string
	 */
	public function get_vendor_address_1() {
		return $this->get_prop('vendor_address_1');
	}

	/**
	 * Get address line 2
	 *
	 * @return string
	 */
	public function get_vendor_address_2() {
		return $this->get_prop('vendor_address_2');
	}

	/**
	 * Get zip code
	 *
	 * @return int
	 */
	public function get_vendor_postcode() {
		return $this->get_prop('vendor_postcode');
	}

	/**
	 * Get city
	 *
	 * @return string
	 */
	public function get_vendor_city() {
		return $this->get_prop('vendor_city');
	}

	/**
	 * Get parent post id
	 *
	 * @return int
	 */
	public function get_parent_id() {
		return $this->get_prop('vendor_id');
	}

	/**
	 * Set parent post by id, allows 0 to remove post parent
	 *
	 * @param bool $id to set
	 * @param string $hook to unhook, to avoid infinite save loop
	 * @param string $fn_name to unhook
	 * @param int $priority priority of rehooked $fn_name
	 * @return bool true on success, false on failure
	 */
	public function set_parent_id($id, $hook = false, $fn_name = false, $priority = 10) {
		if ($this->get_parent_id() == $id)
			return;

		if (get_post_type($id) == 'nw_vendor' && $this->update_post('post_parent', $id, $hook, $fn_name, $priority)) {
			$this->set_prop('vendor_id', $id);
			return true;
		}

		return false;
	}

	/**
	 * Get the applicable terms to search for products in the taxonomy _nw_access
	 *
	 * @return int
	 */
	public function get_terms($include_campaign_term = null) {
		$terms = array(
			$this->get_prop('term_tax_id'),
			$this->get_prop('vendor_term_tax_id')
		);

		// Add group as term if $this has a group at all
		if ($this->get_prop('group_id'))
			$terms[] = $this->get_prop('group_term_tax_id');

		if (($this->has_active_campaign() && is_null($include_campaign_term)) || $include_campaign_term) {
			$term = get_option('nw_campaign_term_tax_id');
			if ($term)
				$terms[] = get_option('nw_campaign_term_tax_id');
		}

		return $terms;
	}

	/**
	 * Get the clubs vendor ID
	 *
	 * @return int
	 */
	public function get_vendor_id() {
		return $this->get_prop('vendor_id');
	}

	/**
	 * Get the clubs group post ID
	 *
	 * @return int
	 */
	public function get_group_id() {
		return $this->get_prop('group_id');
	}

	/**
	 * Create and set a new, unique registration code
	 *
	 * @return string New registration code
	 */
	public function set_new_registration_code() {
		$this->generate_and_save_registration_code();
		return $this->get_prop('registration_code');
	}

	/**
	 * Get registration code, generates one if not set
	 *
	 * @return string Unique registration code
	 *
	 */
	public function get_registration_code() {
		// Generate valid registration code if empty
		if (!$this->get_prop('registration_code'))
			$code = $this->generate_and_save_registration_code();

		return $this->get_prop('registration_code');
	}

	/**
	 * A unique, random user registration code
	 *
	 * @return string Registration code with structure A#A##A (A = letter, #= number)
	 */
	protected function generate_and_save_registration_code() {
		do {
			$a = str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ');
			$n = str_shuffle('123456789');

			$reg_code = $a[0].$n[0].$a[1].$n[1].$n[2].$a[2];

			// Make sure that we don't on the off chance generated an existing code
			$search = new WP_Query(array(
				'post_type' => static::POST_TYPE,
				'meta_key' => $this->meta_keys['registration_code'],
				'meta_value' => $reg_code
			));
		} while ($search->found_post);

		$this->set_prop('registration_code', $reg_code);
		$this->changes['registration_code'] = false;
		update_post_meta($this->get_id(), $this->meta_keys['registration_code'], $reg_code);
	}

	/**
	 * Get the number of users that have registered so far
	 *
	 * @return int No of registered users
	 */
	public function get_no_users_registered() {
		return absint($this->get_prop('users_registered'));
	}

	/**
	 * Increment the number of users registered by $n
	 *
	 * @return int No of registered users to increment by
	 */
	public function increment_users_registered($n = 1) {
		$n = $this->get_prop('users_registered') + absint($n);
		$this->set_prop('users_registered', $n);
	}

	/**
	 * Get maximum number of users allowed to register with this club
	 *
	 * @return int Maximum users
	 */
	public function get_maximum_no_users() {
		return absint($this->get_prop('maximum_no_users'));
	}

	/**
	 * Set maximum number of users allowed to register with this club
	 *
	 * @param int $users Maximum users, minimum 10 and maximum 10 000
	 */
	public function set_maximum_no_users($n) {
		$n = absint($n);
		if ($n >= 10 && $n < 10000)
			$this->set_prop('maximum_no_users', $n);
	}

	/**
	 * Get whether registration capping should occur
	 *
	 * @return bool
	 */
	public function is_capping_active() {
		return boolval($this->get_prop('capping_active'));
	}

	/**
	 * Set whether registration capping should occur
	 *
	 * @param bool $active
	 */
	public function set_capping($active) {
		$this->set_prop('capping_active', boolval($active));
	}

	/**
	 * Check whether campaign is activate for site, and $this club is enabled for it
	 *
	 * @return bool
	 */
	public function has_active_campaign() {
		if (get_option('nw_campaign_status') == 'on' &&
			strtotime(get_option('nw_campaign_start_date')) <= strtotime('today') &&
			strtotime(get_option('nw_campaign_end_date')) >= strtotime('today')) {
				return true;
		}
		return false;
	}

	/**
	 * Get whether club should join in on campaigns
	 *
	 * @return bool
	 */
	public function has_campaign_ability() {
		return $this->get_prop('campaign_ability');
	}

	/**
	 * Get whether club can be displayed on logout view
	 *
	 * @return bool
	 */
	public function has_club_onLogout() {
		return $this->get_prop('club_onLogout');
	}

	/**
	 * Get whether club is active for open shop
	 *
	 * @return bool
	 */
	public function has_open_shop_ability() {
		return $this->get_prop('open_shop_ability');
	}

	/**
	 * Set whether club should join in on campaigns
	 *
	 * @param bool $ability
	 */
	public function set_campaign_ability($ability) {
		// If $this should join campaigns, we need to do a product count for categories
		if ($ability && $this->get_prop('campaign_ability') != $ability)
			$this->update_categories();

		$this->set_prop('campaign_ability', boolval($ability));
	}
	
	/**
	 * Set whether club should join in on campaigns
	 *
	 * @param bool $active
	 */
	public function set_club_onLogout($active) {
		$this->set_prop('club_onLogout', boolval($active));
	}

	/**
	 * Set whether club has open shop ability
	 *
	 * @param bool $ability
	 */
	public function set_open_shop_ability($ability) {
            $this->set_prop('open_shop_ability', boolval($ability));
	}

	/**
	 * Get what addresses registered user of club are allowed to ship to,
	 * defaults to vendor address
	 *
	 * @return string
	 */
	public function get_allowed_shipping() {
		return $this->get_prop('allowed_shipping');
	}

	/**
	 * Set whether allowed addresses for shipping
	 *
	 * @param string $shipping Either 'club', 'club-customer', 'vendor',
	 * 'vendor-club', 'vendor-customer' or vendor-club-customer'
	 */
	public function set_allowed_shipping($shipping) {
		$options = array('club', 'club-customer', 'vendor',
			'vendor-club', 'vendor-customer', 'vendor-club-customer', 'customer');

		if (in_array($shipping, $options))
			$this->set_prop('allowed_shipping', $shipping);
	}


	/**
	 * Get sport banners image ids or urls
	 *
	 * @param string $val either 'id' or 'img'
	 * @return array of image urls
	 */
	public function get_sport_banners() {
		return $this->get_prop('sport_banners');
	}

	/**
	 * Set associated sport banners
	 *
	 * @param array $banners of sport banner post type IDs
	 */
	public function set_sport_banners($banners) {
		$save = array();

		// Validate that ids belong to sport banners
		foreach ($banners as $banner) {
			if (get_post_type($banner) == 'nw_sport_banner') {
				array_push($save, $banner);
			}
		}

		$this->set_prop('sport_banners', $save);
	}

	/**
	 * Get the url for the club logo
	 *
	 * @return string image url
	 */
	public function get_club_logo() {
		return get_the_post_thumbnail_url($this->get_id());
	}

	public function get_category_count($term_tax_id) {
		if ($this->has_active_campaign() &&
			isset($this->get_prop('categories_campaign_count')[$term_tax_id]))
			return $this->get_prop('categories_campaign_count')[$term_tax_id];

		if (isset($this->get_prop('categories_count')[$term_tax_id]))
			return $this->get_prop('categories_count')[$term_tax_id];

		return 0;
	}

	/**
	 * Update and array that keeps track of how many products each shop has in
	 * each category
	 *
	 * @param WP_Term[] $update_categories
	 */
	public function update_categories() {
		global $wpdb;

		$query = "select COUNT(DISTINCT ID) from wp_posts
		left join wp_term_relationships on (wp_posts.ID = wp_term_relationships.object_id)
		left join wp_term_relationships AS shops ON (wp_posts.ID = shops.object_id)
		where
			wp_term_relationships.term_taxonomy_id IN (%s) AND
			shops.term_taxonomy_id IN (%s) AND
			wp_posts.post_type = 'product' AND
			wp_posts.post_status = 'publish'
			limit 1
		";

		// Statically cache all product categories
		static $categories = array();
		if (empty($categories)) {
			$categories = get_terms(array(
	    	'taxonomy' => 'product_cat',
	    	'hide_empty' => false
			));
		}

		/*
			Create lists for referencing a term and getting its child terms
			e.g. [$term_tax] = '$term_tax, $child_term_tax_1, $child_term_tax_2' etc.,
			stored statically for the next club to be updated
		*/
		static $term_tax_queries = array();
		foreach ($categories as $term) {
			if (!array_key_exists($term->term_taxonomy_id, $term_tax_queries)) {
				$hierarchical_tax_ids = array($term->term_taxonomy_id);
				foreach (get_term_children($term->term_id, 'product_cat') as $child_term_id) {
					$child_term = get_term($child_term_id);
					if (!is_null($child_term) && !is_wp_error($child_term))
						$hierarchical_tax_ids[] = $child_term->term_taxonomy_id;
				}
				$term_tax_queries[$term->term_taxonomy_id] = implode(',', $hierarchical_tax_ids);
			}
		}

		$counts = array();

		// Get taxonomy terms for this shop, disregarding the campaign taxonomy
		$shop_terms = implode(',', $this->get_terms(false));

		// Count products in each category, for normal and campaign mode and update
		foreach ($term_tax_queries as $term_taxonomy_id => $cat_terms) {
			$count = $wpdb->get_var(sprintf($query, $cat_terms, $shop_terms));

			if ($count)
				$counts[$term_taxonomy_id] = $count;
		}

		$this->set_prop('categories_count', $counts);

		// If $this club participates in campaigns, count products for that scenario too
		if ($this->has_campaign_ability()) {
			$counts = array();

			// Get taxonomy terms for this shop, including the campaign taxonomy
			$shop_terms = implode(',', $this->get_terms(true));


			foreach ($term_tax_queries as $term_taxonomy_id => $cat_terms) {
				$campaign_count = $wpdb->get_var(sprintf($query, $cat_terms, $shop_terms));

				if ($campaign_count)
					$counts[$term_taxonomy_id] = $campaign_count;
			}
			$this->set_prop('categories_campaign_count', $counts);
		}
	}

	/**
	 * Get Club-specific Freight Charge
	 *
	 * @return string
	 */
	public function get_no_freight_charge() {
		return $this->get_prop('no_freight_charge');
	}

	/**
	 * Set Club-specific Freight Charge
	 *
	 * @param string (only numeric value allowed)
	 */
	public function set_no_freight_charge($status) {
		$save = $status ? 'nw_activated' : 'nw_deactivated';

		// No need to save, already has same value
		if ($save != $this->get_prop('no_freight_charge')) {
			$this->set_prop('no_freight_charge', $save);
		}

		if (empty($charge) || !is_numeric($charge) || (is_numeric($charge) && floatval($charge)<0)) {
			$charge = '';
		}

		$this->set_prop('freight_charge', $charge);
	}

	/**
	 * Get Club-specific Freight Charge
	 *
	 * @return string
	 */
	public function get_freight_charge() {
		$charge = $this->get_prop('freight_charge');
		if(empty($charge) || !is_numeric($charge)) {
			$charge = '';
		}

		return $charge;
	}

	/**
	 * Set Club-specific Freight Charge
	 *
	 * @param string (only numeric value allowed)
	 */
	public function set_freight_charge($charge) {
		if (empty($charge) || !is_numeric($charge) || (is_numeric($charge) && floatval($charge)<0)) {
			$charge = '';
		}

		$this->set_prop('freight_charge', $charge);
	}


	/**
	 * Get status for no_freight_charge
	 *
	 * @return bool true if 'nw_activated', false otherwise
	 */
	public function is_no_freight_charge() {
		if ($this->get_prop('no_freight_charge') == 'nw_activated')
			return true;
		return false;
	}

	// PLANASD-484 added getter/setter methods for custom fields added --- start
	/**
	 * Get reset_to_default_vendor
	 *
	 * @return integer
	 */
	public function get_reset_to_default_vendor() {
		return $this->get_prop('reset_to_default_vendor');
	}

	/**
	 * Set reset_to_default_vendor
	 *
	 * @param integer
	 */
	public function set_reset_to_default_vendor($reset_to_default_vendor) { 
		$this->set_prop('reset_to_default_vendor', $reset_to_default_vendor);
	}
	// PLANASD-484 added getter/setter methods for custom fields added --- END

	/**
	 * Set webshop message
	 *
	 * @return string webshop message
	 */
	public function set_nw_webshop_message($message) {
		$this->set_prop('webshop_message', $message);
		return $this->get_prop('webshop_message');
	}

	/**
	 * Get webshop messagge
	 *
	 * @return string webshop message
	 *
	 */
	public function get_nw_webshop_message() {
		return $this->get_prop('webshop_message');
	}
}