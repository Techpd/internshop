<?php

/**
 * Integration with nShift tracking
 *
 * @package   nShift tracking plugin
 * @category Integration
 * @author   Martin Madsen
 */

require __DIR__ . '/nshift-php-client/vendor/autoload.php';

use Crakter\nShift\Entity\Connect;
use Crakter\nShift\Entity\Tracking;
use Crakter\nShift\Clients\Authorization;
use Crakter\nShift\Clients\Tracking\TrackingByOrderNumber;
use Crakter\nShift\Clients\Tracking\TrackingByBarcode;
use Crakter\nShift\Clients\Tracking\TrackingByUuid;

class WC_nShift_Tracking_Emails
{
    /**
     * Init and hook in the integration.
     */
    public function __construct()
    {
        global $woocommerce;
        add_action('woocommerce_order_status_completed', __CLASS__ . '::status_changed', 99, 2);
    }

    public static function status_changed($order_id, $order)
    {
        // If settings enable sending of notice
        $items = $order->get_items();
        if (!$template) {
            wc_get_logger()->debug('Sent to printing notice template not located. Order #' . $order_id, ["source" => "nshift_logs"]);
            return;
        }

        $mailer = WC()->mailer();
        ob_start();
        require_once(NW_PLUGIN_DIR . 'templates/sent-to-customer.php');
        $message = ob_get_clean();
        $message = $mailer->wrap_message(_x('På vei til deg', 'Email header', 'newwave'), $message);

        $customer = new WC_Customer($order->get_customer_id());
        $result = $mailer->send($customer->get_email(), sprintf(__('På vei til deg: #%s', 'newwave'), $order->get_id()), $message);
    }
}
