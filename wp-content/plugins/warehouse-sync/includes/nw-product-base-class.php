<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

	/**
	 * Base class for all the custom New Wave product types
	 *
	 */
	abstract class WC_Product_NWP_Base extends WC_Product_Variable
	{
		protected $nw_base_data = array(
			'stock_control_id' => 0,
		);


		public function __construct($product = 0)
		{
			$this->data = array_merge($this->data, $this->nw_base_data);
			parent::__construct($product);
		}

		public function get_variation_prices($include_taxes = false)
		{
			$price = $this->get_price();
			$prices = array();

			foreach ($this->get_children() as $child_id)
				$prices[$child_id] = $price;

			return array(
				'price' => $prices,
				'regular_price' => $prices,
				'sale_price' => $prices,
			);
		}


		public function is_type($type)
		{
			if (is_array($type))
				return (in_array($this->get_type(), $type) || in_array('variable', $type));

			if ($type == $this->get_type() || $type == 'variable')
				return true;
			else
				return false;
		}


		public function get_regular_price($context = 'view')
		{
			return $this->get_price();
		}

		public function get_stock_control($context = 'view')
		{
			if ($this->get_stock_control_id()) {
				return new NWP_Stock_Control(intval($this->get_stock_control_id()));
			}

			$stock_control = new NWP_Stock_Control($this->get_sku());
			$this->set_prop('stock_control_id', $stock_control->get_id());
			update_post_meta($this->get_id(), '_nw_stock_control_id', $stock_control->get_id());
			return $stock_control;
		}

		public function get_stock_control_id($context = 'view')
		{
			return $this->get_prop('stock_control_id', $context);
		}

		public function set_stock_control_id($id)
		{
			$this->set_prop('stock_control_id', $id);
		}
	}

	/**
	 * Data store class acting as a layer between product class and database
	 *
	 */
	class WC_Product_NW_Data_Store extends WC_Product_Variable_Data_Store_CPT
	{

		/**
		 * Override of helper method that reads all data from database when needed
		 *
		 * @param WC_Product
		 */
		protected function read_product_data(&$product)
		{
			parent::read_product_data($product);

			$props = array(
				'stock_control_id' => get_post_meta($product->get_id(), '_nw_stock_control_id', true),
				'brand_name' => get_post_meta($product->get_id(), '_brand_name', true)
			);

			if(get_option('_nw_shop_feature')){
				if ($product->is_type('nw_stock')) {
					$props += array(
						'color_access' => get_post_meta($product->get_id(), '_nw_color_access', true),
						'image_access' => get_post_meta($product->get_id(), '_nw_image_access', true),
						'discounts' => get_post_meta($product->get_id(), '_nw_discounts', true),
						'campaign_enabled_variations' => get_post_meta($product->get_id(), '_nw_campaign_enabled_variations', true),
					);
				} else if ($product->is_type('nw_stock_logo') || $product->is_type('nw_special')) {
					$props += array(
						'shop_id' => get_post_meta($product->get_id(), '_nw_shop', true),
						'sale_period_date' => get_post_meta($product->get_id(), '_nw_sale_period_date', true),
						'discounts' => get_post_meta($product->get_id(), '_nw_discounts', true),// PLANASD - 484 - add discounts property to base class as present for all products
					);
				}
			}

			if ($product->is_type('nw_special')){
				$props += array(
					'sale_period_date' => get_post_meta($product->get_id(), '_nw_sale_period_date', true),
				);
			}

			// NOTE when using 'set props', the data key needs to have corresponding functions names as set_{name} and get_{name}
			$product->set_props($props);
		}

		/**
		 * Calls parent function to read variation attributes if not already cached.
		 * Overrides parent function.
		 *
		 * @param WC_Product
		 */
		public function read_variation_attributes(&$product)
		{
			if (is_woocommerce()) {
				if (!$cache = wp_cache_get($product->get_id(), 'nw_variation_attributes')) {
					$cache = parent::read_variation_attributes($product);
					wp_cache_set($product->get_id(), $cache, 'nw_variation_attributes');
				}
				return $cache;
			}
			return parent::read_variation_attributes($product);
		}

		/**
		 * Override of helper method that updates all the post meta for the product
		 *
		 * @param WC_Product
		 * @param bool $force Force all props to be written even if not changed. This is used during creation.
		 */
		protected function update_post_meta(&$product, $force = false)
		{
			parent::update_post_meta($product, $force);
			update_post_meta($product->get_id(), '_nw_stock_control_id', $product->get_stock_control_id());

			if ($product->is_type('nw_special')) {
				update_post_meta($product->get_id(), '_nw_sale_period_date', $product->get_sale_period_date());
			}

			if (get_option('_nw_shop_feature')) {
				if ($product->is_type('nw_stock')) {
					update_post_meta($product->get_id(), '_nw_color_access', $product->get_color_access());
					update_post_meta($product->get_id(), '_nw_image_access', $product->get_image_access());
					// PLANASD - 484 commented and moved out as will be saved against all prodtypes
					// update_post_meta($product->get_id(), '_nw_discounts', $product->get_discounts());
					update_post_meta($product->get_id(), '_nw_campaign_enabled_variations', $product->get_campaign_enabled_variations());
				}

				// PLANASD - 484 handle save of discounts for all
				update_post_meta($product->get_id(), '_nw_discounts', $product->get_discounts());

				if ($product->is_type('nw_stock_logo')) {
					update_post_meta($product->get_id(), '_nw_shop', $product->get_shop_id());
					// update_post_meta($product->get_id(), '_nw_sale_period_date', $product->get_sale_period_date());
				}
				if ($product->is_type('nw_special')) {
					update_post_meta($product->get_id(), '_nw_shop', $product->get_shop_id());
				}
			}
		}
	}
