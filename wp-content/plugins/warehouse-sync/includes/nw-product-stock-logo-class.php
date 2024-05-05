<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;
	/**
	 * New Wave Product Stock class
	 *
	 */
	class WC_Product_NW_Stock_Logo extends WC_Product_NWP_Base
	{

		/**
		 * Stores nw_product data.
		 *
		 * @var array
		 */
		protected $nw_stock_logo_data = array(
			'shop_id' => 0,
			'sale_period_date' => 0,
			'discounts' => array(), // PLANASD - 484 added discounts property
			'brand_name' => ""
		);

		/**
		 * Merges nw_product data into the parent object.
		 *
		 * @param int|NW_Product_Base|NW_Product_Stock|object $product Product to init.
		 */
		public function __construct($product = 0)
		{
			$this->data = array_merge($this->data, $this->nw_stock_logo_data);
			parent::__construct($product);
		}

		/**
		 * Get internal type.
		 *
		 * @return string
		 */
		public function get_type()
		{
			return 'nw_stock_logo';
		}

		/**
		 * Save the product.
		 * Set the IDs of the shops this product should be visible for,
		 * as terms of the taxonomy _nw_access (which NW_Session filters for)
		 * Triggers the parent function.
		 *
		 */
		public function save()
		{
			if (get_option('_nw_shop_feature')) {
				wp_set_object_terms($this->get_id(), strval($this->get_shop_id()), '_nw_access', false);
				update_post_meta($this->get_id(), '_brand_name', $this->get_brand_name());

				// PLANASD - 484 - handle discounts save --- start
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
				// PLANASD - 484 - handle discounts save -- end
			}

			parent::save(); // Save all other attributes
		}

		/**
		 * Get the id of the associated shop this product belongs to
		 *
		 * @return int
		 */
		public function get_shop_id($context = 'view')
		{
			$id = $this->get_prop('shop_id', $context);
			return $id;
		}

		/**
		 * Set the id of the associated shop this product belongs to
		 *
		 * @param int
		 */
		public function set_shop_id($id)
		{
			$this->set_prop('shop_id', absint($id));
		}

		/**
		 * Get availability date //TODO redo commenting here
		 *
		 * @param  string $context
		 * @return string Returns date in format d-m-Y (e.g. 25-05-2015),
		 * or current date if not set
		 */
		public function get_sale_period_date($context = 'view')
		{
			$date = $this->get_prop('sale_period_date', 'view');

			if (!$date)
				$date = 0;

			if ($context == 'format')
				return date('d-m-Y', $date);

			return $date;
		}

		/**
		 * Save end date for product availability, takes a date as a string
		 *
		 * @param string $date
		 * @return false Returns false if date format is invalid or past current time
		 */
		public function set_sale_period_date($date, $type = '')
		{
			if ($type == 'format')
				$date = strtotime($date);

			$this->set_prop('sale_period_date', $date);
		}

		/**
		 * If product is within sale period and can be purchased
		 *
		 * @return bool Returns true if within period, false if not
		 */
		public function within_sale_period()
		{
			if (strtotime('today') <= $this->get_prop('sale_period_date'))
				return true;
			return false;
		}

		public function get_price($context = 'view')
		{

			if ((is_woocommerce() && !is_admin()) ||  (wp_doing_ajax())) {
				return apply_filters('nw_stock_price', $this->get_prop('price', $context), $this);
			}

			return $this->get_prop('price', $context);
		}

		// PLANASD - 484 -getter/setter functions for discounts --start

		/**
		 * Set discounts per stores for product
		 *
		 * @param  array $stores Array of store id and their discount
		 */
		public function set_discounts($shop_ids)
		{
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
		 * Get discounts per stores for product
		 *
		 * @param string $context (default: 'view')
		 * @return array Returns array of stores and each discount
		 */
		public function get_discounts($context = 'view') {
			return $this->get_prop('discounts', $context);
		}

		// PLANASD - 484 -getter/setter functions for discounts -- end

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