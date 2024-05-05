<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

/**
 * Stock controller, syncs availability for products with same SKU,
 * since same SKU can be used more than once
 *
 */
class NWP_Stock_Control {
	/**
	 * The SKU
	 *
	 * @var string
	 */
	protected $sku = '';

	/**
	 * Post ID reference of the object.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Holds which product attributes with the same SKU can be sold,
	 * arranged as [color][size]
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Whether changes made to $data after load.
	 *
	 * @var bool
	 */
	protected $changes = false;

	/**
	 *
	 * @param int $id SKU
	 */
	public function __construct($id = 0) {
		if (!$id)
			return;

		// If incoming param is the SKU as a string
		if (is_string($id)) {
			// Search for existing Stock Control with same SKU
			$search = new WP_Query(array(
				'post_type' => 'nw_stock_control',
				'title' => $id,
			));
			if ($search->found_posts)
				$id = $search->posts[0]->ID;

			// Create a new Stock Control object
			else {
				$id = wp_insert_post(array(
					'post_type' => 'nw_stock_control',
					'post_title' => $id
				));
				if (is_wp_error($id)) {
					NWP_Functions::log('Creating stock control failed', $id);
					return false;
				}
			}
		}

		// Initialize
		$this->id = $id;
		$this->sku = get_the_title($id);
		$data = maybe_unserialize(get_post($id)->post_content);
		if (is_array($data))
			$this->data = $data;
		else
			$this->data = array();
		return $this->sku;
	}

	/**
	 * Get the SKU
	 *
	 * @return string
	 */
	public function get_sku() {
		return $this->sku;
	}

	/**
	 * Get the SKU
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Replace array of attributes
	 *
	 * @param array $update
	 */
	public function update_data($update) {
		$this->changes = true;
		foreach ($this->data as $color_term_id => $sizes) {
			foreach ($sizes as $size_term_id => $val) {
				if (isset($update[$color_term_id][$size_term_id]))
					$this->data[$color_term_id][$size_term_id] = true;
				else
					$this->data[$color_term_id][$size_term_id] = false;
			}
		}
	}

	/**
	 * Save settings for product attributes
	 *
	 */
	public function save() {
		if (!$this->id)
			return;

		if ($this->changes) {
			wp_update_post(array(
				'ID' => $this->id,
				'post_content' => maybe_serialize($this->data),
			));
		}
	}

	/**
	 * Get the product attribute settings
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Register the color and size attribute of $product
	 *
	 * @param WC_Product_Variation $product
	 * @return bool True if successful, false if not
	 */
	public function add_product($product) {
		if (!is_a($product, 'WC_Product_Variation'))
			return false;

		$a = $product->get_attributes();
		$color = isset($a['pa_color']) ? $a['pa_color'] : false;
		$size = isset($a['pa_size']) ? $a['pa_size'] : false; //TODO must check llike this everywhere pa_color isaccessed

		if (!is_string($color) || !is_string($size))
			return false;

		if (strlen($size) <= 0 || strlen($size) <= 0)
			return false;

		if (!$color_term = get_term_by('slug', $color, 'pa_color'))
			return false;

		if (!$size_term = get_term_by('slug', $size, 'pa_size'))
			return false;

		if (!isset($this->data[$color_term->term_id]))
			$this->data[$color_term->term_id] = array();

		if (!isset($this->data[$color_term->term_id][$size_term->term_id])) {
			$this->data[$color_term->term_id][$size_term->term_id] = true;
			$this->changes = true;
		}
		return true;
	}

	/**
	 * Check whether a product with given color and size attributes is in stock
	 *
	 * @param WC_Product_Variable $product
	 * @return bool True if in stock, false if not
	 */
	public function in_stock($product) {
		if (!is_a($product, 'WC_Product_Variation'))
			return false;

		$attributes = $product->get_attributes();
		$color = isset($attributes['pa_color']) ? $attributes['pa_color'] : false;
		$size = isset($attributes['pa_size']) ? $attributes['pa_size'] : false;

		if (!is_string($color) || !is_string($size))
			return false;

		if (strlen($size) <= 0 || strlen($size) <= 0)
			return false;

		if (!$color_term = get_term_by('slug', $color, 'pa_color'))
			return false;


		if (!$size_term = get_term_by('slug', $size, 'pa_size'))
			return false;

		if (isset($this->data[$color_term->term_id][$size_term->term_id]))
			return $this->data[$color_term->term_id][$size_term->term_id] ? true : false;

		return false;
	}
}
