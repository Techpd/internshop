<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

if (!function_exists('nw_get_attribute_icons')) :

	/**
	 * Get an array of icon name, icon description, and icon URL belonging to a product.
	 *
	 * @param int $product_id The ID of the product.
	 * @return string[] An array of icon details: ['name' => string, 'description' => string, 'url' => string]
	 */

	function nw_get_attribute_icons($product_id)
	{
		if (!$product_id)
			return array();

		$product_icons = NW_Product_Property_Attribute_Icons::get_icons($product_id);

		$icons = array();
		foreach ($product_icons as $icon_slug) {
			$icon = array(
				'name' => NW_Product_Property_Attribute_Icons::get_icon_name($icon_slug),
				'description' => NW_Product_Property_Attribute_Icons::get_icon_description($icon_slug),
				'url' => NW_Product_Property_Attribute_Icons::get_icon_url($icon_slug),
			);
			array_push($icons, $icon);
		}

		return $icons;
	}

endif;

	/**
	 * Class NW_Product_Property_Attribute_Icons
	 * Adds a custom post type 'nw_attribute_icon' to be set when editing a product,
	 * and displayed on the product page, to illustrate some properties the product has.
	 */

	class NW_Product_Property_Attribute_Icons
	{

		/**
		 * @var string[] Icon names and descriptions
		 */
		static $icons = array(
			'beactive' => array(
				'name' => 'Be Active',
				'description' => 'Be Active gir optimal temperaturregulering ved fysisk aktivitet under middels kalde til kalde forhold.'
			),
			'bodymapped' => array(
				'name' => 'Bodymapped',
				'description' => 'En tettsittende passform gir deg optimal bevegelsesfrihet.'
			),
			'brilliance' => array(
				'name' => 'Brilliance',
				'description' => 'Reflekterende materialer som gir deg god synlighet under mørke forhold.'
			),
			'compression' => array(
				'name' => 'Compression',
				'description' => 'Kompresjon gir bedre blodsirkulasjon og reduserer muskelhevelse etter hard trening.'
			),
			'coolmax' => array(
				'name' => 'CoolMax',
				'description' => ''
			),
			'durable' => array(
				'name' => 'Durable',
				'description' => 'Slitesterkt materiale som gir optimal ytelse under krevende forhold.'
			),
			'keepwarm' => array(
				'name' => 'Keep Warm',
				'description' => ''
			),
			'lightweight' => array(
				'name' => 'Lightweight',
				'description' => 'Ultralett materiale som gir deg en følelse av frihet.'
			),
			'lowsupport' => array(
				'name' => 'Low Support',
				'description' => 'Gir deg god support ved lavintensiv trening.'
			),
			'mediumsupport' => array(
				'name' => 'Medium support',
				'description' => 'Gir deg optimal support under middels til lavintensive treningsøkter.'
			),
			'moisturetransport' => array(
				'name' => 'Moist transport',
				'description' => 'Transporterer fukt fra kroppen til plaggets utside og holder deg sval og tørr under intensiv trening.'
			),
			'packable' => array(
				'name' => 'Packable',
				'description' => 'Enkel å pakke sammen og oppbevare i små rom når du er på farten.'
			),
			'quickdry' => array(
				'name' => 'Quick dry',
				'description' => 'Hurtigtørkende materiale som holder fukten borte og deg sval under svette treningsøkter.'
			),
			'seamless' => array(
				'name' => 'Seamless',
				'description' => 'Sømløst materiale som eliminerer skav og gir en deilig følelse mot huden.'
			),
			'softtouch' => array(
				'name' => 'Soft touch',
				'description' => 'Kjennes deilig og behagelig for huden.'
			),
			'staycool' => array(
				'name' => 'Stay cool',
				'description' => 'Stay Cool gir suveren kjøling og bevegelsesfrihet ved fysisk aktivitet under varme forhold.'
			),
			'stretch' => array(
				'name' => 'Stretch',
				'description' => 'Elastisk materiale som gir deg optimal bevegelsesfrihet og som følger kroppens bevegelser.'
			),
			'tapedseams' => array(
				'name' => 'Taped seams',
				'description' => 'Teipede sømmer for en pen finish.'
			),
			'upf25' => array(
				'name' => 'UPF25+',
				'description' => 'Beskytter mot sol, UPF 25+.'
			),
			'upf50' => array(
				'name' => 'UPF50+',
				'description' => 'Beskytter mot sterk sol, UPF 50+.'
			),
			'ventair' => array(
				'name' => 'Ventair',
				'description' => ''
			),
			'waterproof' => array(
				'name' => 'Waterproof',
				'description' => 'Vanntett materiale som holder deg tørr i regn og ruskevær.'
			),
			'waterrepellent' => array(
				'name' => 'Water repellent',
				'description' => ''
			),
			'windproof' => array(
				'name' => 'Wind proof',
				'description' => 'Vindtett materiale som beskytter mot vær og vind.'
			),
			'windprotective' => array(
				'name' => 'Wind protective',
				'description' => 'Vindavvisende materiale som holder deg varm under kjølige forhold.'
			),
			'wool' => array(
				'name' => 'Wool',
				'description' => ''
			)
		);

		/**
		 * Initialize the class by adding hooks and filters.
		 */

		public static function init()
		{
			// Add and save custom meta boxes
			add_action('nw_properties_panel', __CLASS__ . '::render_panel', 3);
			add_action('save_post', __CLASS__ . '::save_from_post');

			// Add custom JS for image uploading and CSS for styling meta boxes
			add_action('admin_enqueue_scripts', __CLASS__ . '::enqueue_assets');

			// Enable REST API support
			add_action('rest_api_init', __CLASS__ . '::enable_REST');

			// Hide attribute icons as meta data from REST responses
			add_filter('nw_hide_product_meta_data', function ($keys) {
				array_push($keys, 'nw_attribute_icons', 'nw_attribute_icon_ids');
				return $keys;
			});
		}

		/**
		 * Get array of attribute icons as [$icon_slug] => $icon_name
		 *
		 * @return array All available icons
		 */

		public static function get_all_icons()
		{
			return static::$icons;
		}

		/**
		 * Get url for icon SVG
		 *
		 * @param string $icon_slug
		 * @return string URL
		 */

		public static function get_icon_url($icon_slug)
		{
			if (!array_key_exists($icon_slug, static::$icons))
				return '';

			return NW_Plugin::$plugin_url . 'assets/icons/' . $icon_slug . '.svg';
		}

		/**
		 * Get nice name for the icon
		 *
		 * @param string $icon_slug
		 * @return string Nice name
		 */

		public static function get_icon_name($icon_slug)
		{
			if (!array_key_exists($icon_slug, static::$icons))
				return '';

			return static::$icons[$icon_slug]['name'];
		}

		/**
		 * Get description of the icon
		 *
		 * @param string $icon_slug
		 * @return string Icon description
		 */

		public static function get_icon_description($icon_slug)
		{
			if (!array_key_exists($icon_slug, static::$icons))
				return '';

			return static::$icons[$icon_slug]['description'];
		}

		/**
		 * Add custom JS for enabling dragging of select2 <select> elements,
		 * and CSS for styling meta boxes in both product and nw_attribute_icon admin
		 */

		public static function enqueue_assets()
		{
			$screen = get_current_screen();
			if ('product' == $screen->post_type) {
				wp_enqueue_script(
					'nw_attribute_icons',
					NW_Plugin::$plugin_url . 'assets/js/nw-admin-attribute-icons.js',
					array('jquery', 'select2')
				);
				wp_enqueue_style(
					'nw_attribute_icons',
					NW_Plugin::$plugin_url . 'assets/css/nw-admin-attribute-icons.css'
				);
			}
		}

		/**
		 * Render attribute icons panel section
		 */

		public static function render_panel($post_id)
		{

			// Get already selected options
			$selected = static::get_icons($post_id);

			printf(
				'<div id="nw-attribute-icons" class="options_group"><p class="form-field"><label>%s</label>',
				__('Attribute icons', 'nw_craft')
			);
			printf('<select name="nw_attribute_icons[]" class="nw-select2" multiple>');

			// Variable bound function to print HTML for options
			$print_option = function ($icon_slug, $selected) {
				printf(
					'<option value="%s" data-icon="%s" %s>%s</option>',
					$icon_slug,
					static::get_icon_url($icon_slug),
					selected($selected),
					static::$icons[$icon_slug]['name']
				);
			};

			// Print selected icons first
			foreach ($selected as $icon_slug) {
				$print_option($icon_slug, true);
			}

			// Print the rest of the icons
			foreach (static::$icons as $icon_slug => $slug_name) {
				if (!in_array($icon_slug, static::$icons)) {
					$print_option($icon_slug, false);
				}
			}

			printf('</select>');
			printf('</p></div>');
		}

		/**
		 * Save the selected attribute icons
		 *
		 * @param int $post_id
		 */

		public static function save_from_post($post_id)
		{
			$icon_slugs = array();

			if (
				isset($_POST['nw_attribute_icons']) &&
				is_array($_POST['nw_attribute_icons'])
			) {
				$icon_slugs = $_POST['nw_attribute_icons'];
			}

			// Save icons as post_meta
			static::set_icons($icon_slugs, $post_id);
		}

		/**
		 * Enable REST API access for getting and setting material text for product
		 *
		 */

		public static function enable_REST()
		{
			register_rest_field('product', 'nw_attribute_icons', array(
				'get_callback' => __CLASS__ . '::get_icons',
				'update_callback' => __CLASS__ . '::set_icons',
			));
		}

		/**
		 * Get icon IDs for post
		 *
		 * @param int $post_id
		 * @return array
		 */

		public static function get_icons($post_id)
		{
			// If REST request, first parameter will be an array
			if (is_array($post_id))
				$post_id = $post_id['id'];

			$icon_slugs = maybe_unserialize(get_post_meta($post_id, 'nw_attribute_icons', true));
			return is_array($icon_slugs) ? $icon_slugs : array();
		}

		/**
		 * Save attribute icons as post_meta, based on icon IDs $raw_ids
		 *
		 * @param string[] $icon_slugs
		 * @param int|WP_Post $post_id
		 */

		public static function set_icons($icon_slugs, $post_id)
		{
			if (is_a($post_id, 'WC_Product'))
				$post_id = $post_id->get_id();

			$validated = array();
			foreach ($icon_slugs as $icon_slug) {
				if (array_key_exists($icon_slug, static::$icons))
					array_push($validated, $icon_slug);
			}
			update_post_meta($post_id, 'nw_attribute_icons', maybe_serialize($validated));
		}
	}

	// Initialize the class when the file is included
	NW_Product_Property_Attribute_Icons::init();