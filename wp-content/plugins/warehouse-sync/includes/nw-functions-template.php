<?php
// If called directly, abort
if (!defined('ABSPATH')) exit;

/**
 * Functions editing front-end content, and functions to help
 * front-end developers getting shop specific content
 */

/**
 * Remove downloads from front-end customer account menu
 *
 * @param string[] $menu
 */
function nw_remove_download_tab($menu)
{
    unset($menu['downloads']);
    return $menu;
}
add_filter('woocommerce_account_menu_items', 'nw_remove_download_tab');

/**
 * Output shipping estimates notice after order table
 *
 */
function nw_shipping_estimates()
{ ?>
    <mark class="<?php echo esc_attr(apply_filters('newwave_shipping_estimates_class', 'newwave-shipping_estimates')); ?>">
        <?php echo get_option('nw_settings_shipping_estimates'); ?>
    </mark><?php
        }
        add_action('woocommerce_order_details_after_order_table', 'nw_shipping_estimates');

        /**
         * Check whether campaign is active or not
         *
         * @return bool
         */
        function nw_campaign_is_active()
        {
            return nw_has_session() && NW_Session::$shop->has_active_campaign();
        }

        /**
         * Get current shop
         *
         * @return NW_Shop_Club
         */
        function nw_get_shop()
        {
            if (nw_has_session())
                return NW_Session::$shop;
            return new NW_Shop_Club(0);
        }

        /**
         * Get current shop id
         *
         * @return int
         */
        function nw_get_current_shop_id()
        {
            if (nw_has_session())
                return NW_Session::$shop->get_id();
            return 0;
        }

        /**
         * If session exist
         *
         * @return bool
         */
        function nw_has_session()
        {
            if (!is_null(NW_Session::$shop))
                return true;
            return false;
        }

        /**
         * Get name of current shop
         *
         * @return string
         */
        function nw_get_shop_name()
        {
            if (nw_has_session())
                return esc_html(NW_Session::$shop->get_name());
            return '';
        }

        /**
         * Get sport banner url
         *
         * @param int|bool $index Specify which sport banner, or True if random
         * @param string $size Size of the image
         * @return string
         */
        function nw_get_sport_banner_src($index = true, $size = 'nw_sport_banner')
        {
            if ('sport_banner' == $size)
                $size = 'nw_sport_banner';

            return get_the_post_thumbnail_url(nw_get_sport_banner_id($index), $size);
        }

        /**
         * Get sport banner attachment ID
         *
         * @param int|bool $index Specify which sport banner, or True if random
         * @return int
         */
        function nw_get_sport_banner_id($index = true)
        {
            $banner = 0;
            if (nw_has_session()) {
                $banners = NW_Session::$shop->get_sport_banners();

                if ($banners && is_array($banners)) {
                    if ($index === true) {
                        shuffle($banners);
                        $banner = $banners[0];
                    } else if (is_int($index) && 0 <= $index && $index < count($banner)) {
                        $banner = $banners[$index];
                    } else {
                        $banner = $banners[0];
                    }
                }
            }
            return $banner;
        }

        /**
         * Get the campaign banner url
         *
         * @param string $size Size of the image
         * @return string
         */
        function nw_get_campaign_banner_src($size = 'nw_sport_banner')
        {
            if ('sport_banner' == $size)
                $size = 'nw_sport_banner';

            if (nw_has_session())
                return wp_get_attachment_image_src(get_option('nw_campaign_banner'), 'nw_sport_banner')[0];
            return '';
        }

        /**
         * Get the club logo url for the current shop
         *
         * @return string
         */
        function nw_get_club_logo_src()
        {
            if (nw_has_session())
                return NW_Session::$shop->get_club_logo() ? NW_Session::$shop->get_club_logo() : '';
            return '';
        }

        /**
         * Get webshop message
         *
         * @return string
         */
        function nw_get_webshop_message() {
            if (nw_has_session())
                return esc_html(NW_Session::$shop->get_webshop_message());
            return '';
        }
?>