<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

/**
 * New Wave Product Stock class
 *
 */
class WC_Product_NW_Stock extends WC_Product_NWP_Base {

	/**
	 * @var array Stores nw_product data.
	 */
	protected $nw_stock_data = array(
		'color_access' => array(),
		'image_access' => array(),
		'discounts' => array(),
		'campaign_enabled_variations' => array(),
		'brand_name' => ""
	);

	/**
	 * Merges nw_product data into the parent object.
	 *
	 * @param int|NW_Product_Base|WC_Product_NW_Stock|object $product Product to init.
	 */
	public function __construct($product = 0) {
		$this->data = array_merge($this->data, $this->nw_stock_data);
		parent::__construct($product);

		// Tell NW_Session that we might need to replace main image id
		do_action('newwave_stock_product_contruct', $this);
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'nw_stock';
	}

	/**
	 * Save product.
	 * Set the IDs of the shops this product should be visible for,
	 * as terms of the taxonomy _nw_access (which NW_Session filters for).
	 * Triggers parent function.
	 *
	 */
	public function save() {
		update_post_meta($this->get_id(), '_nw_type', $this->get_type());
		update_post_meta($this->get_id(), '_brand_name', $this->get_brand_name());
		if (get_option('_nw_shop_feature')) {
			$shop_ids = array_keys($this->get_color_access());
			$shop_ids = array_map('strval', $shop_ids);

			if (!empty($this->get_campaign_enabled_variations()))
				$shop_ids[] = 'campaign';

			// Set the terms $this product should be associated with
			if (!empty($shop_ids))
				wp_set_object_terms($this->get_id(), $shop_ids, '_nw_access', false);
			else
				wp_delete_object_term_relationships($this->get_id(), '_nw_access', false);


			// Update table for sorting by prices based on $discounts
			global $wpdb;
			$discounts_table = $wpdb->prefix.NWP_TABLE_DISCOUNTS;
			$wpdb->delete($discounts_table, array('product_id' => $this->get_id()), array('%d'));

			foreach ($this->get_discounts() as $shop_id => $discount) {
				$shop_term_tax_id = absint(get_the_excerpt($shop_id));
				$wpdb->query(
					$wpdb->prepare("INSERT INTO $discounts_table (product_id, shop_term_tax_id, discount, original) VALUES (%d, %d, %d, %d)",
					$this->get_id(), $shop_term_tax_id, $discount, $this->get_price()
				));
			}

			// Update prices table with campaign prices
			$campaign_term_tax_id = get_option('nw_campaign_term_tax_id');
			if ($this->is_part_of_campaign()) {
			$campaign_discount = absint(get_option('nw_campaign_discount'));
				if ($campaign_term_tax_id) {
					$campaign_price = absint(ceil($this->get_price() * (1 - ($campaign_discount/100))));
					$wpdb->query(
						$wpdb->prepare("INSERT INTO $discounts_table (product_id, shop_term_tax_id, discount, original) VALUES (%d, %d, %d, %d)",
						$this->get_id(), $campaign_term_tax_id, $campaign_price, $this->get_price()
					));
				}
			}

			// Update which variations shop be seen by which shops
			$variations_table = $wpdb->prefix.NWP_TABLE_VARIATIONS;
			$wpdb->delete($variations_table, array('product_id' => $this->get_id()), array('%d'));

			// Create lookup for variations associated with a color as: term_id => [variation_ids]
			$color_term_lookup_var_id = array();
			foreach ($this->get_children() as $var_id) {
				$var = wc_get_product($var_id);
				$attr = $var->get_attributes();

				if (isset($attr['pa_color']) && $attr['pa_color']) {
					if (!$term = get_term_by('slug', $attr['pa_color'], 'pa_color'))
						continue;

					if (!isset($color_term_lookup_var_id[$term->term_id]))
						$color_term_lookup_var_id[$term->term_id] = array();
					$color_term_lookup_var_id[$term->term_id][] = $var_id;
				}
			}

			// Based on the colors a shop should see, store all variation ids associated to those colors
			foreach ($this->get_color_access() as $shop_id => $colors) {
				$shop_term_tax_id = absint(get_the_excerpt($shop_id));
				foreach ($colors as $color => $on) { // since colors are stored as keys
					if (isset($color_term_lookup_var_id[$color])) {
						foreach ($color_term_lookup_var_id[$color] as $var_id) {
							$wpdb->query(
								$wpdb->prepare("INSERT INTO $variations_table (shop_term_tax_id, product_id, variation_id) VALUES (%d, %d, %d)",
								$shop_term_tax_id, $this->get_id(), $var_id
							));
						}
					}
				}
			}

			// Based on what variations have been selected as part of the campaign 'mode', store these in the custom table as well
			if ($campaign_term_tax_id) {
				foreach ($this->get_campaign_enabled_variations() as $var_id) {
					$wpdb->query(
						$wpdb->prepare("INSERT INTO $variations_table (shop_term_tax_id, product_id, variation_id) VALUES (%d, %d, %d)",
						$campaign_term_tax_id, $this->get_id(), $var_id
					));
				}
			}

			// Store what images should be shown for the product per shop
			// (so that if only, say, green and blue variations of a product is available,
			// images associated with these variations should be shown)

			// First, get all all images for this product and the associated colors per image
			$image_ids = array($this->get_image_id());
			$image_ids = array_merge($image_ids, $this->get_gallery_image_ids());
			$image_id_lookup_color = array();
			foreach ($image_ids as $image_id) {
				if ($saved = get_post_meta($image_id, '_nw_color', true)) {
					$image_id_lookup_color[$image_id] = NWP_Functions::unpack_list($saved);
				}
				else // no color associated with the image, so we'll show it regardless
					$image_id_lookup_color[$image_id] = array(false);
			}

			// Build the array containing shop_id => [main_image, gallery_images]
			$shop_image_ids = array();
			foreach ($this->get_color_access() as $shop_id => $shop_colors) {
				$shop_image_ids[$shop_id] = array(
					'image' => 0,
					'gallery' => array()
				);

				// Set the appropriate first image and the rest as gallery images
				$i = 0;
				foreach ($image_id_lookup_color as $image_id => $colors) {
					foreach ($colors as $color) {
						// If the shop has the color for the current image, add it,
						// or if the image does not have a color, store that one too
						if ($color === false || isset($shop_colors[$color])) {
							if ($i == 0) // set first image as main image
								$shop_image_ids[$shop_id]['image'] = $image_id;
							else // and the rest as gallery
								$shop_image_ids[$shop_id]['gallery'][] = $image_id;
							$i++;
						}
					}
				}
			}

			// Do the same for the images of the campaign activated variations
			// First, get the colors associated with the variations that are campaign activated
			$campaign_colors = array();
			foreach ($this->get_campaign_enabled_variations() as $var_id) {
				$image_id = get_post_thumbnail_id($var_id);
				if (isset($image_id_lookup_color[$image_id])) {
					$colors = $image_id_lookup_color[$image_id];
					foreach ($colors as $color) {
						if (!in_array($color, $campaign_colors))
							$campaign_colors[] = $color;
					}
				}
			}

			$shop_image_ids['campaign'] = array(
				'image' => 0,
				'gallery' => array(),
			);
			$i = 0;

			// Set the appropriate first image and the rest as gallery images
			foreach ($image_id_lookup_color as $image_id => $colors) {
				foreach ($colors as $color) {
					if ($color === false || in_array($color, $campaign_colors)) {
						if ($i == 0)
							$shop_image_ids['campaign']['image'] = $image_id;
						else
							$shop_image_ids['campaign']['gallery'][] = $image_id;
						$i++;
					}
				}
			}

			// Save the images each shop should be able to see
			$this->set_image_access($shop_image_ids);
		}

		// Trigger saving of all other attributes
		parent::save();
	}


	/**
	 * Get the IDs of the children for the product based on the current shop
	 * Overrides the parent function.
	 *
   * @param bool|string $visible_only (default: '').
	 */
	public function get_children($visible_only = '') {
		if (get_option('_nw_shop_feature') && is_woocommerce() && !is_admin() && nw_has_session()) {
			$children = wp_cache_get($this->get_id(), 'nw_variation_ids');

			// If not cached
			if ($children === false) {
				global $wpdb;

				// Let NW_Session add what term tax ids to search for
				$shop_term_taxs = implode(', ', apply_filters('newwave_stock_variations_shop_term_tax_ids', array(), $this));
				$and_where = !empty($shop_term_taxs) ? sprintf("AND shop_term_tax_id IN (%s)", $shop_term_taxs) : '';
				$table_name = $wpdb->prefix . NWP_TABLE_VARIATIONS;
				$results = $wpdb->get_results(sprintf("
					SELECT DISTINCT variation_id FROM $table_name
						WHERE product_id = {$this->get_id()} $and_where", ARRAY_A));
				$children = array();
				foreach ($results as $result) {
					$children[] = $result->variation_id;
				}
				wp_cache_set($this->get_id(), $children, 'nw_variation_ids');
			}
			return $children;
		}
		// Not front-end, don't filter children IDs based on a shop
		return parent::get_children($visible_only);
	}

	/**
	 * Get all variations. Cache enabled.
	 * Overrides parent function.
	 *
   * @return array
	 */
	public function get_available_variations( $return = 'array' ) {
		/*if (is_woocommerce() && !is_admin()) {
			$variations = wp_cache_get($this->get_id(), 'nw_variations');

			// If not already in cache
			if (false === $variations) {
				$variations = array();
				foreach ($this->get_children() as $var_id) {
					$variation = wc_get_product($var_id);
					// Get data array for the product
					$variations[] = $this->get_available_variation($variation);
				}
				wp_cache_set($this->get_id(), $variations, 'nw_variations');
			}

			return $variations;
		}
		*/
		// Not front-end, don't cache anything
		return parent::get_available_variations();
	}

	/**
	 * Get IDs of visible children only.
	 * Overrides parent function.
	 *
   * @return array
	 */
	public function get_visible_children() {
		if (is_woocommerce() && !is_admin())
			return apply_filters('woocommerce_get_children', $this->get_children(), $this, true);
		return $this->get_children();
	}

	/**
	 * Get the price of the product, letting NW_Session filter the results based on
	 * the current shop.
	 * Overrides parent function.
	 *
   * @param string $context (default: 'view')
   * @param float|int
	 */
	public function get_price($context = 'view') {
		if ( (is_woocommerce() && !is_admin() )||  ( wp_doing_ajax())) 
			return apply_filters('nw_stock_price', $this->get_prop('price', $context), $this);
		return $this->get_prop('price', $context);
	}

	/**
	 * Get the original price (no filtering)
	 * Overrides parent function.
	 *
   * @return string $context (default: 'view')
	 * @param float|int
	 */
	public function get_regular_price($context = 'view') { //for comparison
		return $this->get_prop('price', $context);
	}

	/**
	 * Get the variation prices; all variations have the same price as $this parent product
	 * Overrides parent function.
	 *
   * @param bool $include_taxes Wheter to include taxes or not. (default: false).
	 * @return array
	 */
	public function get_variation_prices($include_taxes = false) {
		$price = $this->get_price();
		$prices = array();
		$regular_price = $price;
		$regular_prices = array();

		// If on sale, get original non-shop specific price for comparison
		if (apply_filters('nw_product_stock_on_sale', false, $this)) {
			$regular_price = $this->get_regular_price();
		}

		foreach ($this->get_children() as $child_id) {
			$prices[$child_id] = $price;
			$regular_prices[$child_id] = $regular_price;
		}

		return array(
			'price' => $prices,
			'regular_price' => $regular_prices,
			'sale_price' => $prices,
		);
	}

	/**
		* Overrides original function: Returns the gallery attachment ids,
		* but applies a filter to the ids first
		*
		* @param  string $context
		* @return array
		*/
	public function get_gallery_image_ids($context = 'view') {
		return apply_filters('newwave_product_type_stock_gallery_image_ids', parent::get_gallery_image_ids(), $this);
	}

	/**
		* Overrides original function: Returns the post thumbnail id
		* but applies a filter to the ids first
		*
		* @param  string $context
		* @return array
		*/
	public function get_image_id($context = 'view') {
		return apply_filters('newwave_product_stock_image_id', $this->get_prop('image_id', $context), $this);
	}

	/**
		* Overrides original function: Returns the main url
		* but applies a filter to the ids first
		*
		* @param  string $context
		* @return array
		*/
	public function get_image($size = 'shop_thumbnail', $attr = array(), $placeholder = true) {
		if (has_post_thumbnail($this->get_id())) {
			$image = wp_get_attachment_image($this->get_image_id(), $size, false, $attr);
		}

		else if ($placeholder) {
			$image = wc_placeholder_img($size);
		}

		return str_replace(array('https://', 'http://'), '//', $image);
	}


	/**
	 * Get which product color variations which shop can access
	 *
	 * @param string $context (default: 'view')
	 * @return array
	 */
	public function get_color_access($context = 'view') {
		return $this->get_prop('color_access', $context);
	}

	/**
	 * Set which shop should be able to access which color variations
	 *
	 * @param array $shops
	 */
	public function set_color_access($shops) {
		if (!is_array($shops))
			return;
		$this->set_prop('color_access', $shops);
	}

	public function get_image_access($context = 'view') {
		return $this->get_prop('image_access', $context);
	}

	public function set_image_access($shops) {
		if (!is_array($shops))
			return;
		$this->set_prop('image_access', $shops);
	}

	/**
	 * Get discounts per stores for product
	 *
	 * @param string $context (default: 'view')
	 * @return array Returns array of stores and each discount
	 */
	public function get_discounts($context = 'view') {
		return $this->get_prop('discounts', $context);
	}


	/**
	 * Set discounts per stores for product
	 *
	 * @param  array $stores Array of store id and their discount
	 */
	public function set_discounts($shop_ids) {
		$save = array();

		// Clean out empty values
		if (is_array($shop_ids)) {
			foreach ($shop_ids as $key => $value) {
				if (!empty($value))
					$save[$key] = $value;
			}
		}
		$this->set_prop('discounts', $save);
	}

	/**
	 * Get IDs of campaign enabled variations
	 *
	 * @param string $context (default: 'view')
	 * @return int[]
	 */
	public function get_campaign_enabled_variations($context = 'view') {
		return $this->get_prop('campaign_enabled_variations', $context);
	}

	/**
	 * Store the IDs of the variations that are to be included in a campaign
	 *
	 * @param int[] $context (default: 'view')
	 */
	public function set_campaign_enabled_variations($variations) {
		$variations = is_array($variations) ? $variations : array();
		$this->set_prop('campaign_enabled_variations', $variations);
	}

	/**
	 * Whether product is activated for campaign (has any campaign enabled variations)
	 *
	 * @return bool
	 */
	public function is_part_of_campaign() {
		return (bool) count($this->get_campaign_enabled_variations());
	}

	/**
	 * Get the product brand
	 *
     * @return string $context (default: 'view')
	 * @param float|int
	 */
	public function get_brand_name($context = 'view') { //for comparison
		return $this->get_prop('brand_name', $context);
    }
        
    /**
	 * Set the product brand
	 *
	 * @param array $shops
	 */
	public function set_brand_name($brand) {
		$this->set_prop('brand_name', $brand);
	}
}
?>
