<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

/**
 * Overriding Walker class that fetches category counts from current shop,
 * instead of counts for categories for the site overall
 *
 */
class NW_Product_Cat_list_Walker extends WC_Product_Cat_List_Walker {

	/**
	 * Start the element output.
	 *
	 * @see Walker::start_el()
	 * @since 2.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $cat
	 * @param int $depth Depth of category in reference to parents.
	 * @param array $args
	 * @param integer $current_object_id
	 */
	public function start_el( &$output, $cat, $depth = 0, $args = array(), $current_object_id = 0 ) {
		$output .= '<li class="cat-item cat-item-' . $cat->term_id;

		if ($args['current_category'] == $cat->term_id) {
			$output .= ' current-cat';
		}

		if ($args['has_children'] && $args['hierarchical'] && (empty( $args['max_depth']) || $args['max_depth'] > $depth + 1)) {
			$output .= ' cat-parent';
		}

		if ( $args['current_category_ancestors'] && $args['current_category'] && in_array( $cat->term_id, $args['current_category_ancestors'] ) ) {
			$output .= ' current-cat-parent';
		}

		$output .= '"><a href="' . get_term_link( (int) $cat->term_id, $this->tree_type ) . '">' . _x( $cat->name, 'product category name', 'woocommerce' ) . '</a>';

		if ( $args['show_count'] ) {
			$output .= ' <span class="count">('.$this->get_shop_cat_count($cat).')</span>';
		}
	}

	/**
	 * Get the count of products in $category for the current $shop
	 *
	 * @param WP_Term $category
	 * @return int
	 */
	public function get_shop_cat_count($category) {
		if (nw_has_session())
			return NW_Session::$shop->get_category_count($category->term_id);
		return $category->count;
	}

	/**
	 * Traverse elements to create list from elements.
	 *
	 * @see WC_Product_Cat_List_Walker::display_element()
	 * @param object $element Data object
	 * @param array $children_elements List of elements to continue traversing.
	 * @param int $max_depth Max depth to traverse.
	 * @param int $depth Depth of current element.
	 * @param array $args
	 * @param string $output Passed by reference. Used to append additional content.
	 * @return null Null on failure with no changes to parameters.
	 */
	public function display_element($element, &$children_elements, $max_depth, $depth, $args, &$output) {
		if (!$element || (0 === $this->get_shop_cat_count($element) && !empty($args[0]['hide_empty']))) {
			return;
		}
		parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
	}
}
