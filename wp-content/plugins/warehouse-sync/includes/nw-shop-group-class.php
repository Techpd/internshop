<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

/**
 * New Wave Shop class, also base for all shops
 *
 */
class NW_Shop_Group {

	/**
	 * @var string Post type
	 */
	const POST_TYPE = 'nw_group';

	/**
	 * @var string Type of this class
	 */
	const TYPE = 'group';

	/**
	 * Stores the shop data
	 *
	 * @var array
	 */
	protected $data = array(
		'id' => 0,
		'status' => 'auto-draft',
		'name' => '',
		'term_tax_id' => 0,
		'shop_id' => false,
	);

	/**
	 * Holds mapping of data props to their respective meta_keys
	 *
	 * @var array
	 */
	protected $meta_keys = array(
		'shop_id' => '_nw_shop_id',
	);

	/**
	 * Stores changes that are to be written to the database if $this->save() is called
	 *
	 * @var array
	 */
	protected $changes = array();

	/**
	 * Create shop object from post id, or from data array
	 *
	 * @param int|array $id Post ID of the store, or array to replace default $this->data
	 */
	function __construct($id = 0) {
		if (is_string($id))
			$id = absint($id);

		if (is_int($id)) // Read from database
			$this->read($id);


	}

	/**
	 * Read all properties from database
	 *
	 * @param int $id Post id of the store
	 */
	protected function read($id) {
		// Read non-meta values
		$this->set_prop('id', $id);
		$this->set_prop('name', get_the_title($id));
		$this->set_prop('status', get_post_status($id));
		$this->set_prop('term_tax_id', get_the_excerpt($id));

		foreach ($this->meta_keys as $prop => $meta_key) {

			$meta_data = get_post_meta($id, $meta_key, true);
			if ($meta_data) {
				$this->set_prop($prop, maybe_unserialize($meta_data));
			}
		}

		foreach ($this->meta_keys as $prop => $val) {
			$this->changes[$prop] = false;
		}
	}

	/**
	 * Get property from $this->data
	 *
	 * @param string $prop property to change
	 * @return mixed
	 */
	protected function get_prop($prop) {
	 	if (isset($this->data[$prop]))
			return $this->data[$prop];
		return null;
	}

	/**
	 * Set property if different from stored value
	 *
	 * @param string $prop property to change
	 * @param $data to set
	 */
	protected function set_prop($prop, $data) {
		if (!isset($this->data[$prop]))
			throw new Exception(sprintf("%s does not exist in data store", $prop));


		// if($prop == 'freight_charge') {
		// 	DFA($_POST);
		// 	echo '(:)<br>';
		// 	echo (isset($this->data[$prop]))? '_'.$this->data[$prop].'_': ':(';

		// 	DFA($this->data);
		// 	echo '<hr>';
		// 	var_dump($data);

		// 	if(count($_POST))
		// 		exit;
		// }

		if ($this->data[$prop] != $data) {
			$this->data[$prop] = $data;
			if (isset($this->meta_keys[$prop]))
				$this->changes[$prop] = true;
		}
	}

	/**
	 * Save all writeable properties to database,
	 * and create a term for product to associate with
	 *
	 */
	public function save() {
		if ($this->get_id()) {
			foreach ($this->meta_keys as $prop => $meta_key) {
				if ($this->changes[$prop]) {
					update_post_meta($this->get_id(), $meta_key, maybe_serialize($this->get_prop($prop)));
					$this->changes[$prop] = false;
				}
			}
		}
	}

	/**
	 * Get the type of store
	 *
	 * @return string
	 */
	public function get_type() {
		return static::TYPE;
	}

	/**
	 * Get the post ID
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->get_prop('id');
	}


	/**
	 * Get the shop ID (New Wave Customer ID)
	 *
	 * @return string
	 */
	public function get_shop_id() {
		return $this->get_prop('shop_id');
	}

	/**
	 * Set the shop ID (New Wave Customer ID)
	 *
	 * @param string $id
	 * @return bool true if successful, false if not
	 */
	public function set_shop_id($id) {
		$id = strtoupper($id);
		$search = new WP_Query(array(
			'post_type' => static::POST_TYPE,
			'meta_key' => $this->meta_keys['shop_id'],
			'meta_value' => $id
		));
		// If exists and  doesn not belong to this shop
		if ($search->found_posts && $search->posts[0]->ID != $this->get_id())
			throw new Exception(__('ID already exists, must be unique', 'newwave'));
                    self::validate_shop_id($id); // Throws exception if false
		$this->set_prop('shop_id', $id);
	}

	/**
	 * Validate that id are 4 digits followed by a non-digit character
	 * (and subsequent to that, whatever else)
	 *
	 * @param string $id to validate
	 * @return bool true if valid
	 * @throws Exception if invalid
	 */
	/* used to validate when forhandler-id is posted */
	public static function validate_shop_id($id) {
		preg_match('/^\d{1,6}$/', $id, $matches);	/* preg_match('/^\d{6}/', $id, $matches); */
		if ($matches)
				return true;
		else
			throw new Exception(__('Group ID must begin with a number between 999 and 6000', 'newwave'));
	}
	
	
	/**
	 * Get the shop ID Invoice (New Wave Customer ID Invoice)
	 *
	 * @return string
	 */
	public function get_shop_id_invoice() {
		return $this->get_prop('shop_id_invoice');
	}

	/**
	 * Set the shop ID Invoice (New Wave Customer ID Invoice)
	 *
	 * @param string $id
	 * @return bool true if successful, false if not
	 */
	public function set_shop_id_invoice($id) {
		$id = strtoupper($id);
		$search = new WP_Query(array(
			'post_type' => static::POST_TYPE,
			'meta_key' => $this->meta_keys['shop_id_invoice'],
			'meta_value' => $id
		));

		// If exists and  doesn not belong to this shop
		if ($search->found_posts && $search->posts[0]->ID != $this->get_id())
			throw new Exception(__('ID already exists, must be unique', 'newwave'));
                    self::validate_shop_id($id); // Throws exception if false
		$this->set_prop('shop_id_invoice', $id);
	}

	/**
	 * Get status for shop (post status)
	 *
	 * @return bool true if 'nw_activated', false otherwise
	 */
	public function is_activated() {
		if ($this->get_prop('status') == 'nw_activated')
			return true;
		return false;
	}

	/**
	 * Get status for shop
	 *
	 * @return string Either 'nw_activated', 'nw_deactivated', 'auto-draft' or 'AUTO DRAFT'
	 */
 	public function get_status() {
		return $this->get_prop('status');
 	}

	/**
	 * Get status for shop
	 *
	 * @return bool true if save button has never been clicked for shop
	 */
	public function is_saved() {
		if (in_array($this->get_prop('status'), array('draft', 'auto-draft', 'pending')))
			return false;

		return true;
	}

	/**
	 * Get name of shop
	 *
	 * @return string
	 */
	public function get_name() {
		if (!$this->is_saved())
			return '';

		return $this->get_prop('name');
	}

	/**
	 * Save name (post title) for shop
	 *
	 * @param string $name
	 * @param string $hook to unhook, to avoid infinite save loop
	 * @param string $fn_name to unhook
	 * @param int $priority priority of rehooked $fn_name
	 * @return bool true on success, false on failure
	 */
	public function save_name($name, $hook = false, $fn_name = false, $priority = 10) {
		// No need to save, already has same value
		if ($name == $this->get_prop('name'))
			return;

		if ($this->update_post('post_title', $name, $hook, $fn_name, $priority)) {
			$this->set_prop('name', $name);
			return true;
		}

		return false;
	}


	/**
	 * Save post status
	 *
	 * @param bool $status True for 'nw_activated', false for 'nw_deactivated'
	 * @param string $hook to unhook, to avoid infinite save loop
	 * @param string $fn_name to unhook
	 * @param int $priority priority of rehooked $fn_name
	 * @return bool true on success, false on failure
	 */
	public function save_status($status, $hook = false, $fn_name = false, $priority = 10) {
		$save = $status ? 'nw_activated' : 'nw_deactivated';

		// No need to save, already has same value
		if ($save == $this->get_prop('status'))
			return;

		if ($this->update_post('post_status', $save, $hook, $fn_name, $priority)) {
			$this->set_prop('status', $status);
			return true;
		}

		return false;
	}

	/**
	 * Get the term tax ID for $this shop
	 *
	 * @return int
	 */
	public function get_term_tax_id() {
		return $this->get_prop('term_tax_id');
	}

	/**
	 * Store the $term_tax_id in the field 'post_excerpt' for $this shop
	 *
	 * @param
	 * @return bool True on success, false on failure
	 */
	public function save_term_tax_id($term_tax_id, $hook = false, $fn_name = false, $priority = 10) {
		if ($this->update_post('post_excerpt', $term_tax_id, $hook, $fn_name, $priority))
			return true;
		return false;
	}

	/**
	 * Helper function only used on non-meta values, since wp_update_post triggers
	 * all actions hooked to 'save' to trigger
	 *
	 * @param string $key value in post to update
	 * @param mixed $data any value to store
	 * @param string $hook to unhook, to avoid infinite save loop
	 * @param string $fn_name to unhook
	 * @param int $priority priority of rehooked $fn_name
	 * @return bool true on success, false on failure
	 */
	protected function update_post($key, $data, $hook = false, $fn_name = false, $priority = 10) {
		if (is_string($hook) && is_string($fn_name)) // Unhook action
			remove_action($hook, $fn_name);

		$params = array('ID' => $this->get_id());
		if ($key != 'post_status')
			$params['post_status'] = get_post_status($this->get_id());
		$params[$key] = $data;

		$success = wp_update_post($params);

		if (is_string($hook) && is_string($fn_name)) // Re-hook the same action
			add_action($hook, $fn_name, $priority, 1);

		return boolval($success);
	}

	/**
	 * Get webshop message
	 *
	 * @return string
	 */
	public function get_webshop_message() {
		if (!$this->is_saved())
			return '';

		return $this->get_prop('webshop_message');
	}
}