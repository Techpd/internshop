<?php
/**
 * Customer refunded order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-refunded-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */
//defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
//do_action( 'woocommerce_email_header', $email_heading, $email ); 
?>

<?php /* translators: %s: Customer first name */ ?>
<!-- <p> --><?php //printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );    ?><!-- </p> -->

<!-- <p> -->
<?php
//if ( $partial_refund ) {
/* translators: %s: Site title */
//printf( esc_html__( 'Your order on %s has been partially refunded. There are more details below for your reference:', 'woocommerce' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
//} else {
/* translators: %s: Site title */
//printf( esc_html__( 'Your order on %s has been refunded. There are more details below for your reference:', 'woocommerce' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
//}
?>
<!-- </p> -->
<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
//do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
//do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
//do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
/* if ( $additional_content ) {
  echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
  } */

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
//do_action( 'woocommerce_email_footer', $email );
?>
<!DOCTYPE html>

<html lang="en" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">
    <head>
        <title></title>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
        <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
        <!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch><o:AllowPNG/></o:OfficeDocumentSettings></xml><![endif]-->
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                padding: 0;
            }

            a[x-apple-data-detectors] {
                color: inherit !important;
                text-decoration: inherit !important;
            }

            #MessageViewBody a {
                color: inherit;
                text-decoration: none;
            }

            p {
                line-height: inherit
            }

            @media (max-width:520px) {
                .icons-inner {
                    text-align: center;
                }

                .icons-inner td {
                    margin: 0 auto;
                }

                .row-content {
                    width: 100% !important;
                }

                .column .border {
                    display: none;
                }

                table {
                    table-layout: fixed !important;
                }

                .stack .column {
                    width: 100%;
                    display: block;
                }
            }
        </style>
    </head>
    <body style="background-color: #FFFFFF; margin: 0; padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;">
        <table border="0" cellpadding="0" cellspacing="0" class="nl-container" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #FFFFFF;" width="100%">
            <tbody>
                <tr>
                    <td>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-1" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                            <tbody>
                                                <tr>
                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 5px; padding-bottom: 5px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                        <table border="0" cellpadding="0" cellspacing="0" class="image_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                            <tr>
                                                                <td style="padding-bottom:50px;padding-top:34px;width:100%;padding-right:0px;padding-left:0px;">
                                                                    <div align="center" style="line-height:10px"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/internshop-logo.png" style="display: block; height: auto; border: 0; width: 200px; max-width: 100%;" width="200"/></div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-2" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                            <tbody>
                                                <tr>
                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 5px; padding-bottom: 5px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                        <table border="0" cellpadding="0" cellspacing="0" class="heading_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                            <tr>
                                                                <td style="padding-left:10px;padding-right:10px;text-align:center;width:100%;">
                                                                    <h1 style="margin: 0; color: #424242; direction: ltr; font-family: 'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif; font-size: 14px; font-weight: 700; letter-spacing: 1px; line-height: 150%; text-align: left; margin-top: 0; margin-bottom: 0;">Ordre Refundert:&nbsp;<strong>#<?php echo $order->get_order_number(); ?></strong> </h1>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-3" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                            <tbody>
                                                <tr>
                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 0px; padding-bottom: 0px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                            <tr>
                                                                <td style="padding-left:10px;padding-right:10px;padding-top:24px;">
                                                                    <div style="color:#424242;direction:ltr;font-family:'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif;font-size:12px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                        <!--p style="margin: 0;">Hei <?php // echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();   ?>,</p-->
                                                                        <p><?php
                                                                            if ($partial_refund) {
                                                                                printf(__('Hei der. Din bestilling på %s er delvis refundert.', 'woocommerce'), get_option('blogname'));
                                                                            } else {
                                                                                printf(__('Hei der. Din bestilling på %s har blitt refundert.', 'woocommerce'), get_option('blogname'));
                                                                            }
                                                                            ?>
                                                                        </p>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-4" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                            <tbody>
                                                <tr>
                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 0px; padding-bottom: 0px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                            <tr>
                                                                <td style="padding-left:10px;padding-top:20px;">
                                                                    <div style="color:#393d47;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:12px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                        <p style="margin: 0;"><span><strong><?php echo "Ordre # " . $order->get_order_number() . " (" . date_i18n("d. F Y", $order->get_date_created()->getTimestamp()) . ")"; ?></strong></span></p>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-5" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                            <tbody>
                                                <tr>
                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="50%">
                                                        <table border="0" cellpadding="0" cellspacing="0" class="heading_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                            <tr>
                                                                <td style="text-align:center;width:100%;padding-top:40px;">
                                                                    <h1 style="margin: 0; color: #424242; direction: ltr; font-family: 'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif; font-size: 14px; font-weight: 700; letter-spacing: normal; line-height: 120%; text-align: left; margin-top: 0; margin-bottom: 0;">Produkt</h1>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="25%">
                                                        <table border="0" cellpadding="0" cellspacing="0" class="heading_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                            <tr>
                                                                <td style="text-align:center;width:100%;padding-top:40px;">
                                                                    <h1 style="margin: 0; color: #424242; direction: ltr; font-family: 'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif; font-size: 14px; font-weight: 700; letter-spacing: normal; line-height: 120%; text-align: right; margin-top: 0; margin-bottom: 0;">Antall</h1>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td class="column column-3" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="25%">
                                                        <table border="0" cellpadding="0" cellspacing="0" class="heading_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                            <tr>
                                                                <td style="text-align:center;width:100%;padding-top:40px;">
                                                                    <h1 style="margin: 0; color: #424242; direction: ltr; font-family: 'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif; font-size: 14px; font-weight: 700; letter-spacing: normal; line-height: 120%; text-align: right; margin-top: 0; margin-bottom: 0;">Pris</h1>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-6" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                            <tbody>
                                                <tr>
                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 5px; padding-bottom: 0px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                        <table border="0" cellpadding="0" cellspacing="0" class="divider_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                            <tr>
                                                                <td style="padding-bottom:14px;padding-top:14px;">
                                                                    <div align="center">
                                                                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                                            <tr>
                                                                                <td class="divider_inner" style="font-size: 1px; line-height: 1px; border-top: 2px solid #424242;"><span> </span></td>
                                                                            </tr>
                                                                        </table>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-7" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="50%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                            <tbody>
                                                <?php
                                                $order_items = $order->get_items();
                                                
                                                $itotal = 0;
                                                $itotal_tax = 0;
                                                
                                                foreach ($order_items as $item_id => $item) {

                                                    $product_variation_id = $item['variation_id'];

                                                    // Check if product has variation.
                                                    if ($product_variation_id) {
                                                        $product = wc_get_product($item['variation_id']);
                                                    } else {
                                                        $product = wc_get_product($item['product_id']);
                                                    }
                                                    $attributes = $product->get_attributes();

                                                    $itotal = $itotal + round($item->get_total() + $item->get_total_tax());
                                                    $itotal_tax += $item->get_total_tax();
                                                    ?>
                                                    <tr>

                                                        <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="50%">
                                                            <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                <tr>
                                                                    <td>
                                                                        <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                            <p style="margin: 0;">
                                                                                <?php 
                                                                                echo $item->get_name(); 
                                                                                if( $attributes ){ //show all attributes of order item
                                                                                    echo "<ul>";
                                                                                    foreach ( $attributes as $meta_key => $meta ) {
                                                                                        $display_key   = wc_attribute_label( $meta_key, $product );
                                                                                        $display_value = $product->get_attribute($meta_key);
                                                                                        echo "<li><strong>$display_key </strong> : $display_value </li>";
                                                                                    }
                                                                                    echo "</ul>";
                                                                                }
                                                                                ?> 
                                                                            </p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="25%">
                                                            <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                <tr>
                                                                    <td>
                                                                        <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:right;">
                                                                            <p style="margin: 0;"><?php echo $item->get_quantity(); ?></p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td class="column column-3" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="25%">
                                                            <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                <tr>
                                                                    <td>
                                                                        <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:right;">
                                                                            <p style="margin: 0;"><?php echo round($item->get_total() + $item->get_total_tax()); ?> Kr</p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                $coupon_retrieved = false;
                                                foreach ($order->get_items('fee') as $_ => $coupon_item) {
                                                    $coupon_retrieved = true;
                                                    $coupon = new WC_Coupon($coupon_item['name']);
                                                    $coupon_post = get_post((WC()->version < '2.7.0') ? $coupon->id : $coupon->get_id());
                                                    $discount_amount = !empty($coupon_item['total']) ? $coupon_item['total'] : 0;
                                                    $discount_amount_tax = !empty($coupon_item['total_tax']) ? $coupon_item['total_tax'] : 0;
                                                    $coupon_items['code'] = $coupon_item['name'];
                                                    $coupon_items['description'] = is_object($coupon_post) ? $coupon_post->post_excerpt : '';
                                                    $coupon_items['total'] = $discount_amount + $discount_amount_tax;
                                                    $itotal = $itotal + $coupon_items['total'];
                                                    $itotal_tax += $discount_amount_tax;
                                                    ?>
                                                    <tr>
                                                        <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="50%">
                                                            <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                <tr>
                                                                    <td>                                                    
                                                                        <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto, Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                            <p style="margin: 0;">
                                                                                <?php 
                                                                                echo $coupon_items['code'] ."<br/>".$coupon_items['description']; 
                                                                                ?> 
                                                                            </p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="25%">
                                                            <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                <tr>
                                                                    <td>
                                                                        <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:center;">
                                                                            <p style="margin: 0;"></p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td class="column column-3" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="25%">
                                                            <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                <tr>
                                                                    <td>
                                                                        <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:right;">
                                                                            <p style="margin: 0;"><?php echo "-".$coupon_items['total']; ?> Kr</p>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                if(!$coupon_retrieved){
                                                    $applied_gift_cards = get_post_meta( $order->get_id(), '_ywgc_applied_gift_cards', true );

                                                    if($applied_gift_cards){
                                                            foreach ($applied_gift_cards as $code => $amount){
                                                                $label = apply_filters( 'yith_ywgc_cart_totals_gift_card_label', esc_html( __( 'Gift card:', 'yith-woocommerce-gift-cards' ) . ' ' . $code ), $code );
                                                                $amount = isset( $applied_gift_cards[ $code ] ) ? - $amount : 0;
                                                                $itotal = $itotal + $amount;
                                                                ?>
                                                        <tr>
                                                            <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="50%">
                                                                <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                    <tr>
                                                                        <td>                                                    
                                                                            <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto, Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                                <p style="margin: 0;">
                                                                                    <?php 
                                                                                    echo $label; 
                                                                                    ?> 
                                                                                </p>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="25%">
                                                                <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                    <tr>
                                                                        <td>
                                                                            <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:center;">
                                                                                <p style="margin: 0;"></p>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td class="column column-3" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="25%">
                                                                <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                    <tr>
                                                                        <td>
                                                                            <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:right;">
                                                                                <p style="margin: 0;"><?php echo $amount; ?> Kr</p>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <?php 
                                                            }
                                                    }
                                                }
//                                                $itotal = $itotal + $order->get_shipping_total();
                                                ?>
                                                </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-8" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-1" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 5px; padding-bottom: 5px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="divider_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-top:14px;">
                                                                                    <div align="center">
                                                                                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                                                            <tr>
                                                                                                <td class="divider_inner" style="font-size: 1px; line-height: 1px; border-top: 1px solid #424242;"><span> </span></td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-2" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="66.66666666666667%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-top:20px;">
                                                                                    <div style="color:#424242;direction:ltr;font-family:'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                                        <p style="margin: 0;"><strong>Delsum</strong></p>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="33.333333333333336%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-top:20px;">
                                                                                    <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:right;">
                                                                                        <p style="margin: 0;"><?php echo ceil($itotal); ?> Kr</p>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-3" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="66.66666666666667%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-top:10px;">
                                                                                    <div style="color:#424242;direction:ltr;font-family:'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                                        <p style="margin: 0;"><strong>Frakt</strong></p>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="33.333333333333336%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-top:10px;">
                                                                                    <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:right;">
                                                                                        <p style="margin: 0;"><?php echo ($order->get_shipping_total() + $order->get_shipping_tax()); ?> Kr</p>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-4" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="66.66666666666667%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-top:10px;">
                                                                                    <div style="color:#424242;direction:ltr;font-family:'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                                        <p style="margin: 0;"><strong>Bestilling totalt</strong></p>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="33.333333333333336%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-top:10px;">
                                                                                    <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:right;">
                                                                                        <p style="margin: 0;"><strong><?php echo $order->get_total(); ?> Kr</strong></p>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-12" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                            <tbody>
                                <tr>
                                    <td>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                            <tbody>
                                                    <?php
                                                // Get the Order refunds (array of refunds)
                                                $order_refunds = $order->get_refunds();
                                                
                                                $refund_total = 0;
                                                $refund_total_tax = 0;
                                                if($order_refunds){
                                                    $order_total = $order->get_total();
                                                    // Loop through the order refunds array
                                                    foreach( $order_refunds as $refund ){
                                                        // Loop through the order refund line items
                                                        foreach( $refund->get_items() as $item_id => $item ){
                                                            $product_variation_id = $item['variation_id'];

                                                            // Check if product has variation.
                                                            if ($product_variation_id) {
                                                                $product = wc_get_product($item['variation_id']);
                                                            } else {
                                                                $product = wc_get_product($item['product_id']);
                                                            }
                                                            $attributes = $product->get_attributes();
                                                            
//                                                                                $order_total -= $item->get_subtotal();
                                                            $refund_total -= round($item->get_total() +  $item->get_total_tax());
                                                            $refund_total_tax -= $item->get_total_tax();
//                                                            echo ($item->get_total() +  $item->get_total_tax()); 
                                                        }
                                                        foreach( $refund->get_items('fee') as $item_id => $item ){
                                                            ?>
                                                            <?php 
//                                                                                $order_total -= $item->get_subtotal();
                                                            $refund_total -= round($item->get_total() +  $item->get_total_tax());
                                                            $refund_total_tax -= $item->get_total_tax();
//                                                            echo ($item->get_total() +  $item->get_total_tax()); ?></p>
                                                        <?php
                                                        }
                                                        if($refund->get_shipping_total() < 0 || $refund->get_shipping_tax() < 0){
                                                            $refund_total -= round($refund->get_shipping_total() + $refund->get_shipping_tax()) ;
                                                        }
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-14" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-1" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                            <tbody>
                                                                <tr>
                                                                    <td>
                                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="66.66666666666667%">
                                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                                            <tr>
                                                                                                <td style="padding-top:20px;">
                                                                                                    <div style="color:#424242;direction:ltr;font-family:'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                                                        <p style="margin: 0;"><strong>Refusjonssum</strong></p>
                                                                                                    </div>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                    <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="33.333333333333336%">
                                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                                            <tr>
                                                                                                <td style="padding-top:20px;">
                                                                                                    <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:right;">
                                                                                                        <p style="margin: 0;">-<?php echo $refund_total; ?> Kr</p>
                                                                                                    </div>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-2" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                            <tbody>
                                                                <tr>
                                                                    <td>
                                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="40%">
                                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                                            <tr>
                                                                                                <td style="padding-top:10px;">
                                                                                                    <div style="color:#424242;direction:ltr;font-family:'Helvetica Neue', Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                                                        <p style="margin: 0;"><strong>Totalsum</strong></p>
                                                                                                    </div>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                    <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-left: 00px; padding-right: 00px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="60%">
                                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                                            <tr>
                                                                                                <td style="padding-top:10px;">
                                                                                                    <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:13px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:right;">
                                                                                                        <p style="margin: 0;"><strong style="color:#0285ba"><s><?php echo round($order_total); ?> Kr</s> <label style="text-decoration: underline"><?php echo round($order_total - $refund_total); ?> Kr (inkludert <?php echo round($itotal_tax - $refund_total_tax) ?> kr MVA)</label></strong></p>
                                                                                                    </div>
                                                                                                </td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-15" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 0px; padding-bottom: 0px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="divider_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-bottom:30px;padding-top:10px;">
                                                                                    <div align="center">
                                                                                        <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                                                            <tr>
                                                                                                <td class="divider_inner" style="font-size: 1px; line-height: 1px; border-top: 1px solid #424242;"><span> </span></td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-16" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 0px; padding-bottom: 0px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-bottom:50px;padding-left:20px;">
                                                                                    <div style="color:#424242;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:12px;font-weight:400;letter-spacing:0px;line-height:120%;text-align:left;">
                                                                                        <p style="margin: 0;">Lurer du på noe? ta <strong><a href="mailto:internshop@newwave.no"> kontakt med oss.</a></strong></p>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-17" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="41.666666666666664%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                            <tr>
                                                                                <td style="padding-left:20px;">
                                                                                    <div style="color:#393d47;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:12px;font-weight:400;letter-spacing:1px;line-height:120%;text-align:left;">

                                                                                        <p style="margin: 0;"><strong>Fakturaadresse <br/></strong><?php echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?> <br/><?php echo $order->get_billing_address_1(); ?> <br/><?php echo $order->get_billing_address_2(); ?> <br/></p>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td class="column column-2" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="41.666666666666664%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="paragraph_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;" width="100%">
                                                                            <tr>
                                                                                <td>
                                                                                    <div style="color:#393d47;direction:ltr;font-family:Helvetica Neue, Helvetica, Roboto,  Arial, sans-serif;font-size:12px;font-weight:400;letter-spacing:1px;line-height:120%;text-align:left;">
                                                                                        <?php
                                                                                        $shipping_first_name = $order->get_shipping_first_name();
                                                                                        $shipping_last_name = $order->get_shipping_last_name();
                                                                                        $shipping_address_2 = $order->get_shipping_address_2();
                                                                                        $shipping_address_1 = $order->get_shipping_address_1();
                                                                                        $shipping_city = $order->get_shipping_city();
                                                                                        $shipping_state = $order->get_shipping_state();
                                                                                        $shipping_postcode = $order->get_shipping_postcode();
                                                                                        $shipping_country = $order->get_shipping_country();
                                                                                        ?>
                                                                                        <p style="margin: 0;"><strong>Leveringsadresse</strong> <br/><?php echo $shipping_first_name . ' ' . $shipping_last_name . ',<br/>' . $shipping_address_1 . "<br/>" . $shipping_address_2; ?></p>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td class="column column-3" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="16.666666666666668%">
                                                                        <table border="0" cellpadding="0" cellspacing="0" class="empty_block" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                                                            <tr>
                                                                                <td>
                                                                                    <div></div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-19" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack" role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 500px;" width="500">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="column column-1" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 5px; padding-bottom: 5px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;" width="100%">
                                                                        <div class="spacer_block" style="height:40px;line-height:40px;font-size:1px;"> </div>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table><!-- End -->
                        </body>
                        </html>