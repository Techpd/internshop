<?php
if (!defined('ABSPATH')) exit;
	class NWP_Register_Taxonomies
	{

		/**
		 * Init and hook into
		 */
		public static function init()
		{
			add_action('init', __CLASS__ . '::register_taxonomies');
		}

		/**
		 * Register taxonomy used for filtering products visibility for customers
		 */
		public static function register_taxonomies()
		{
			register_taxonomy(
				'_nw_access',
				'product',
				array(
					'public'             => false,
					'publicly_queryable' => false,
					'show_ui'            => false,
					'show_in_nav_menus'  => false,
					'show_in_rest'       => false,
				)
			);
			register_taxonomy_for_object_type('_nw_access', 'product');
			register_taxonomy_for_object_type('_nw_access', 'product_variation');

			register_taxonomy(
				'_nw_unprocessed',
				'shop_order',
				array(
					'public'             => false,
					'publicly_queryable' => false,
					'show_ui'            => false,
					'show_in_nav_menus'  => false,
					'show_in_rest'       => false,
				)
			);
			register_taxonomy_for_object_type('_nw_unprocessed', 'shop_order');
		}
	}