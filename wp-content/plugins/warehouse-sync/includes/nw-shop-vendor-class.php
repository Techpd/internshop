<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

/**
 * New Wave Shop class, with Shop_Group as base for all shops
 *
 */
class NW_Shop_Vendor extends NW_Shop_Group {

	/**
	 * @var string Post type
	 */
	const POST_TYPE = 'nw_vendor';

	/**
	 * @var string Type of this class
	 */
	const TYPE = 'vendor';

	/**
	 * @var array Stores the shop data
	 */
	protected $data = array(
		'id' => 0,
		'status' => 'auto-draft',
		'term_tax_id' => 0,
		'deactivated_by' => 'self',
		'name' => '',
		'shop_id' => false,
		'shop_id_invoice' => '',
		'group_id' => 0,
		'poc' => '',
		'phone' => '',
		'address_1' => '',
		'address_2' => '',
		'postcode' => '',
		'city' => '',
		'club_email' => '',
		// PLANASD - 484 added the custom fields added
		'discount_nw_stock' => '',
		'discount_nw_stock_logo' => '',
		'printing_price_nw_stock_logo' => '',
		'discount_nw_special' => '',
		'reset_all_clubs' => '',
		'reset_all_products' => '',
	);

	/**
	 * @var array Holds mapping of data props to their respective meta_keys
	 */
	protected $meta_keys = array(
		'shop_id' => '_nw_shop_id',
		'shop_id_invoice' => '_nw_shop_id_invoice',
		'poc' => '_nw_poc',
		'phone' => '_nw_phone',
		'address_1' => '_nw_address_1',
		'address_2' => '_nw_address_2',
		'postcode' => '_nw_postcode',
		'city' => '_nw_city',
		'club_email' => '_nw_club_email',
		// PLANASD - 484 added the custom fields added
		'discount_nw_stock' => '_nw_discount_nw_stock',
		'discount_nw_stock_logo' => '_nw_discount_nw_stock_logo',
		'printing_price_nw_stock_logo' => '_nw_printing_price_nw_stock_logo',
		'discount_nw_special' => '_nw_discount_nw_special',
		'reset_all_clubs' => '_nw_reset_all_clubs',
		'reset_all_products' => '_nw_reset_all_products',
	);

	/**
	 * Create store object from post id
	 *
	 * @param int $id Post id of the store
	 */
	public function __construct($id = 0) {
		if (is_string($id))
			$id = absint($id);

		parent::__construct($id);

		// Create from database
		if (is_int($id)) {
			$this->read_inherited_properties($id);
		}

		$all_prod_types = wc_get_product_types();
	}

	/**
	 * Read values inherited from post parent, specific to this class
	 *
	 * @param string $id
	 */
	protected function read_inherited_properties($id) {
		$group_id = absint(wp_get_post_parent_id($id));
		if (!$group_id)
			return;

		$this->set_prop('group_id', $group_id);

		if (get_post_status($group_id) != 'nw_activated') {
			$this->set_prop('status', 'nw_deactivated');
			$this->set_prop('deactivated_by', 'group');
		}
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
		preg_match('/^\D*(\d{5})(\D|$).*/', $id, $matches);
		// preg_match('/^\d{1,6}$/', $id, $matches);
		if ($matches)
			return true;
		else
			throw new Exception(__('Vendor ID must begin with a number between 9999 and 100 000', 'newwave'));
	}

	/**
	 * Get if deactivated by self or if shop inherit property from group
	 *
	 * @return string|bool 'self' or 'group', or false if activated
	 */
	public function deactivated_by() {
		if ($this->is_activated())
			return false;

		return $this->get_prop('deactivated_by');
	}

	/**
	 * Get last name
	 *
	 * @return string
	 */
	public function get_poc() {
		return $this->get_prop('poc');
	}

	/**
	 * Set address
	 *
	 * @param string
	 */
	public function set_poc($point_of_contact) {
		$this->set_prop('poc', strval($point_of_contact));
	}

	/**
	 * Get phone number
	 *
	 * @return string
	 */
	public function get_phone() {
		return $this->get_prop('phone');
	}

	/**
	 * Set phone number
	 *
	 * @param string
	 */
	public function set_phone($number) {
		$this->set_prop('phone', strval($number));
	}

	/**
	 * Get address line 1
	 *
	 * @return string
	 */
	public function get_address_1() {
		return $this->get_prop('address_1');
	}

	/**
	 * Set address
	 *
	 * @param string
	 */
	public function set_address_1($address) {
		$this->set_prop('address_1', strval($address));
	}

	/**
	 * Get address line 2
	 *
	 * @return string
	 */
	public function get_address_2() {
		return $this->get_prop('address_2');
	}

	/**
	 * Set address
	 *
	 * @param string
	 */
	public function set_address_2($address) {
		$this->set_prop('address_2', strval($address));
	}

	/**
	 * Get zip code
	 *
	 * @return int
	 */
	public function get_postcode() {
		return $this->get_prop('postcode');
	}

	/**
	 * Set zip code
	 *
	 * @param int
	 */
	public function set_postcode($city) {
		$this->set_prop('postcode', $city);
	}

	/**
	 * Get city
	 *
	 * @return string
	 */
	public function get_city() {
		return $this->get_prop('city');
	}

	/**
	 * Set city
	 *
	 * @param string
	 */
	public function set_city($city) {
		$this->set_prop('city', strval($city));
	}

	/**
	 * Get associated group post id
	 *
	 * @return int
	 */
	public function get_group_id() {
		return $this->get_prop('group_id');
	}

	/**
	 * Get parent post id
	 *
	 * @return int
	 */
	public function get_parent_id() {
		return $this->get_group_id();
	}
	
	/* Added by Pragati - start */
	/**
	 * Get club email
	 *
	 * @return string
	 */
	public function get_club_email() {
		return $this->get_prop('club_email');
	}

	/**
	 * Set club_email
	 *
	 * @param string
	 */
	public function set_club_email($club_email) { 
		$this->set_prop('club_email', strval($club_email));
	}
	/*  Added by Pragati - END */
	
	// PLANASD-484 added getter/setter methods for custom fields added --- start
	/**
	 * Get discount_nw_stock
	 *
	 * @return integer
	 */
	public function get_discount_nw_stock() {
		return $this->get_prop('discount_nw_stock');
	}

	/**
	 * Set discount_nw_stock
	 *
	 * @param integer
	 */
	public function set_discount_nw_stock($discount_nw_stock) { 
		$this->set_prop('discount_nw_stock', $discount_nw_stock);
	}

	/**
	 * Get discount_nw_stock_logo
	 *
	 * @return integer
	 */
	public function get_discount_nw_stock_logo() {
		return $this->get_prop('discount_nw_stock_logo');
	}

	/**
	 * Set discount_nw_stock_logo
	 *
	 * @param integer
	 */
	public function set_discount_nw_stock_logo($discount_nw_stock_logo) { 
		$this->set_prop('discount_nw_stock_logo', $discount_nw_stock_logo);
	}

	/**
	 * Get printing_price_nw_stock_logo
	 *
	 * @return integer
	 */
	public function get_printing_price_nw_stock_logo() {
		return $this->get_prop('printing_price_nw_stock_logo');
	}

	/**
	 * Set printing_price_nw_stock_logo
	 *
	 * @param integer
	 */
	public function set_printing_price_nw_stock_logo($printing_price_nw_stock_logo) { 
		$this->set_prop('printing_price_nw_stock_logo', $printing_price_nw_stock_logo);
	}

	/**
	 * Get discount_nw_special
	 *
	 * @return integer
	 */
	public function get_discount_nw_special() {
		return $this->get_prop('discount_nw_special');
	}

	/**
	 * Set discount_nw_special
	 *
	 * @param integer
	 */
	public function set_discount_nw_special($discount_nw_special) { 
		$this->set_prop('discount_nw_special', $discount_nw_special);
	}

	/**
	 * Get reset_all_clubs
	 *
	 * @return integer
	 */
	public function get_reset_all_clubs() {
		return $this->get_prop('reset_all_clubs');
	}

	/**
	 * Set reset_all_clubs
	 *
	 * @param integer
	 */
	public function set_reset_all_clubs($reset_all_clubs) { 
		$this->set_prop('reset_all_clubs', $reset_all_clubs);
	}

	/**
	 * Get reset_all_products
	 *
	 * @return integer
	 */
	public function get_reset_all_products() {
		return $this->get_prop('reset_all_products');
	}

	/**
	 * Set reset_all_products
	 *
	 * @param integer
	 */
	public function set_reset_all_products($reset_all_products) { 
		$this->set_prop('reset_all_products', $reset_all_products);
	}
	// PLANASD-484 added getter/setter methods for custom fields added --- END

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

		if (($id == 0 || get_post_type($id) == 'nw_group')
			&& $this->update_post('post_parent', $id, $hook, $fn_name, $priority))
			$this->set_prop('group_id', $id);

		return false;
	}
}
