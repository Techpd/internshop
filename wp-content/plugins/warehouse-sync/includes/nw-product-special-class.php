<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

/**
 * New Wave Special Product Type Class
 *
 */
class WC_Product_NW_Special extends WC_Product_NW_Stock_Logo {
	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'nw_special';
	}
}