<?php

function new_home_styles() {
    wp_enqueue_style( 'home_styles', get_stylesheet_directory_uri() . '/home-2.0-styles.css', false, '1.0.1' );
    wp_enqueue_script( 'home_slick_js', get_stylesheet_directory_uri() . '/js/slick.min.js', false, '1.0.0' );
    wp_enqueue_script( 'magnific_popup_js', get_stylesheet_directory_uri() . '/js/jquery.magnific-popup.min.js', false, '1.0.0' );
    wp_enqueue_script( 'mCustomScrollBar_js', get_stylesheet_directory_uri() . '/js/jquery.mCustomScrollbar.concat.min.js', false, '1.0.0' );
    wp_enqueue_style( 'home_slick_css', get_stylesheet_directory_uri() . '/js/slick.css', false, '1.0.0' );
    wp_enqueue_style( 'magnific_popup_css', get_stylesheet_directory_uri() . '/css/magnific-popup.css', false, '1.0.0' );
    wp_enqueue_style( 'mCustomScrollbar_css', get_stylesheet_directory_uri() . '/js/mCustomScrollbar.css', false, '1.0.0' );
	wp_enqueue_script( 'script_js', get_stylesheet_directory_uri() . '/js/script-js.js', false, '1.0.0' );
	wp_enqueue_script( 'ui-actions', get_stylesheet_directory_uri() . '/js/ui-actions.js', false, '1.0.0' );
    wp_localize_script( 'script_js', 'adminajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
}
add_action('wp_enqueue_scripts', 'new_home_styles');


add_action( 'admin_enqueue_scripts', 'load_admin_style' );
function load_admin_style() {
    // wp_register_style( 'admin_css', get_template_directory_uri() . '/admin-style.css', false, '1.0.0' );
    wp_enqueue_style( 'admin_css_fix', get_stylesheet_directory_uri() . '/admin-fix.css', false, '1.2.0' );
}


add_filter('woocommerce_breadcrumb_defaults', 'craft_breadcrumbs', 99);

function craft_breadcrumbs($a) {

    $a['home'] = '';

    return $a;

}



add_action('wp_enqueue_scripts', 'craft_polyfills');

function craft_polyfills() {

  wp_enqueue_script('craft_object_fit_polyfill', get_stylesheet_directory_uri().'/picturefill.js');

  wp_enqueue_script('script', get_stylesheet_directory_uri().'/script.js');

}



add_filter('woocommerce_output_related_products_args', 'craft_number_of_related_products', 99, 1);

function craft_number_of_related_products($args) {

    $args['posts_per_page'] = 3;

    $args['columns'] = 3;

    return $args;

}



add_action('template_redirect', 'craft_move_woocommerce_message');

function craft_move_woocommerce_message(){

    add_action('woocommerce_shortcode_before_product_cat_loop', 'wc_print_notices', 10);

    add_action('woocommerce_before_shop_loop', 'wc_print_notices', 10);

    add_action('woocommerce_before_single_product', 'wc_print_notices', 10);

  add_action('flatsome_after_header', 'wc_print_notices', 3);

}



add_action('woocommerce_before_shop_loop', 'craft_add_campaign_banner');

function craft_add_campaign_banner() {

    if (function_exists('nw_campaign_is_active') && function_exists('nw_get_campaign_banner_src')) {

        if (nw_campaign_is_active()) {

            ?><div id="craft-campaign-banner" style="background-image:url('<?php echo nw_get_campaign_banner_src(); ?>')"></div><?php

        }

    }

}



add_action('flatsome_after_header', 'craft_add_sport_banner', 2);

function craft_add_sport_banner() {

    if (function_exists('nw_get_sport_banner_src') && function_exists('nw_get_club_logo_src')) {

        if (nw_has_session() && !is_front_page()) : ?>

            <div id="craft-banner" style="background-image:url('<?php echo nw_get_sport_banner_src(); ?>');">

                <div id="craft-banner-overlay">

                    <div id="craft-banner-inside">

                        <div id="club-emblem">

                            <div id="club-logo" style="background-image:url('<?php echo nw_get_club_logo_src(); ?>');"></div>

                            <h3><?php echo nw_get_shop_name(); ?></h3>

                        </div>

                    </div>

                </div>

            </div>

        <?php endif;

    }

}



add_filter('flatsome_header_class', 'craft_header_classes');

function craft_header_classes($classes) {

    if (is_front_page() && !current_user_can('manage_woocommerce')) {

        // $classes[] = 'craft-hide-header-menu-items';

        // $classes[] = 'craft-hide-header';

    }

    return $classes;

}



add_shortcode('craft_login_form', 'craft_login_form');

function craft_login_form() {

    // printf('<div class="craft-underline"><h2>%s</h2></div>', __('Login', 'woocommerce'));
    printf('<button title="Close (Esc)" type="button" class="mfp-close custom-close-xy"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button><div class="craft-underline"><h2>%s</h2></div>', 'LOGG INN');

    wc_get_template('login-form.php', array(), get_stylesheet_directory().'/', get_stylesheet_directory().'/templates/');

}



add_shortcode('craft_register_form', 'craft_register_form');

function craft_register_form() {

    // printf('<div class="craft-underline"><h2>%s</h2></div>', __('Register', 'woocommerce'));
    printf('<button title="Close (Esc)" type="button" class="mfp-close custom-close-xy"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button><div class="craft-underline"><h2>%s</h2></div>', __('Register', 'woocommerce'));

    wc_get_template('registration-form.php', array(), get_stylesheet_directory().'/', get_stylesheet_directory().'/templates/');

}



add_action('woocommerce_review_order_before_cart_contents', 'craft_change_review_order');

function craft_change_review_order() {

    add_filter('woocommerce_cart_item_name', 'craft_review_order_cart_item_name', 99, 2);

    add_filter('woocommerce_checkout_cart_item_quantity', 'craft_review_order_cart_item_quantity', 99, 1);

}



// Add quantity indicator to the front of the cart item name

function craft_review_order_cart_item_name($name, $cart_item) {

    return sprintf('%s x %s ', $cart_item['quantity'], $name);

}



// Remove +- quantity buttons

function craft_review_order_cart_item_quantity($quantity_html) {

    return '';

}



// Remove filters, so no other templates than woocommerce/checkout/review-order.php are affected

function craft_after_change_review_order() {

    remove_filter('woocommerce_cart_item_name', 'craft_review_order_cart_item_name', 99);

    remove_filter('woocommerce_checkout_cart_item_quantity', 'craft_review_order_cart_item_quantity', 99);

}



//function filter_woocommerce_customer_meta_fields( $array ) {

//    

//    $array['shipping']['fields']['shipping_addtional_options'] = array(

//        'label'       => __( 'Leilighet, suite, osv. (valgfritt)', 'woocommerce' ),

//        'description' => '',

//    );

//    

//    return $array; 

//}; 

//

//add_filter( 'woocommerce_customer_meta_fields', 'filter_woocommerce_customer_meta_fields', 10, 1 ); 



add_filter( 'woocommerce_ship_to_different_address_checked', 'set_wc_shipping_address_to_different', 10);

function set_wc_shipping_address_to_different($value){

    $value = true;

    return $value;

}



add_action('woocommerce_checkout_order_processed', 'wc_modify_order_after_sucess', 3, 200);

function wc_modify_order_after_sucess($order_id, $posted_data, $order){

    $destination = WC()->session->get('nw_shipping_destination');

    $user_id = $order->get_user_id();

    

    if($destination == 'customer' && !$order->has_status( 'failed' )){        

        

        $address = get_user_meta( $user_id, 'user_shipping_gateadresse', true );

        $postnummer = get_user_meta( $user_id, 'user_postnummer', true );

        $user_sted = get_user_meta( $user_id, 'user_sted', true );

        

        if($address){

            update_user_meta( $user_id, 'shipping_address_1', $address );

            $order->set_shipping_address_1($address);

        }

        if($postnummer){

            update_user_meta( $user_id, 'shipping_postcode', $postnummer );

            $order->set_shipping_postcode($postnummer);

        }

        if($user_sted){

            update_user_meta( $user_id, 'shipping_city', $user_sted );

            $order->set_shipping_city($user_sted);

        }        

    }

}



add_action( 'show_user_profile', 'user_addtional_fields' );

add_action( 'edit_user_profile', 'user_addtional_fields' );

function user_addtional_fields($user) {

   ?>

    <h3><?php _e("User Additional Fields", "blank"); ?></h3>



    <table class="form-table">

        <tr>

            <th><label><?php _e("Gateadresse"); ?></label></th>

            <td>

                <input type="text" name="user_shipping_gateadresse" value="<?php echo get_user_meta($user->ID, 'user_shipping_gateadresse', TRUE); ?>" class="regular-text" />

            </td>

        </tr>

        <tr>

            <th><label><?php _e("Avdeling/gruppe"); ?></label></th>

            <td>

                <input type="text" name="shipping_addtional_options" value="<?php echo get_user_meta($user->ID, 'shipping_addtional_options', TRUE); ?>" class="regular-text" />

            </td>

        </tr>

        <tr>

            <th><label><?php _e("Postnummer"); ?></label></th>

            <td>

                <input type="text" name="user_postnummer" value="<?php echo get_user_meta($user->ID, 'user_postnummer', TRUE); ?>" class="regular-text" />

            </td>

        </tr>

        <tr>

            <th><label><?php _e("Sted"); ?></label></th>

            <td>

                <input type="text" name="user_sted" value="<?php echo get_user_meta($user->ID, 'user_sted', TRUE); ?>" class="regular-text" />

            </td>

        </tr>

    </table>

<?php

}







add_action( 'personal_options_update', 'save_extra_user_profile_fields' );

add_action( 'edit_user_profile_update', 'save_extra_user_profile_fields' );

function save_extra_user_profile_fields( $user_id ) {

    if ( !current_user_can( 'edit_user', $user_id ) ) {

        return false;

    }

    if(isset($_POST['user_shipping_gateadresse'])){

        update_user_meta( $user_id, 'user_shipping_gateadresse', $_POST['user_shipping_gateadresse'] );

    }

    if(isset($_POST['shipping_addtional_options'])){

        update_user_meta( $user_id, 'shipping_addtional_options', $_POST['shipping_addtional_options'] );

    }

    if(isset($_POST['user_postnummer'])){

        update_user_meta( $user_id, 'user_postnummer', $_POST['user_postnummer'] );

    }

    if(isset($_POST['user_sted'])){

        update_user_meta( $user_id, 'user_sted', $_POST['user_sted'] );

    }

}



function action_woocommerce_flat_rate_shipping_add_rate( $method, $rate ) { 

    $new_rate          = $rate;



    list($nw_is_free_shipping, $nw_rate) = get_nw_user_shipping_rate();

    if(!empty($nw_rate) && is_numeric($nw_rate)) {

        $new_rate['cost'] = $nw_rate;

    }



    $method->add_rate( $new_rate );

};



add_action( 'woocommerce_flat_rate_shipping_add_rate', 'action_woocommerce_flat_rate_shipping_add_rate', 10, 2 ); 



add_filter( 'woocommerce_package_rates', 'hide_shipping_when_free_is_available', 10, 2);



function hide_shipping_when_free_is_available($rates, $package) {

    list($nw_is_free_shipping, $nw_rate) = get_nw_user_shipping_rate();



    $is_free_shipping = false;



    // if(isset($_GET['wpdebug'])) {

        // echo "$nw_is_free_shipping, $nw_rate<br>";

        // DFA($rates);

        // exit;

    // }



    // proceed only if there are multiple methods available  

    if(count($rates) > 1) {



        // is this a free_shipping order? 

        foreach ( $rates as $rate_id => $rate ) {

            if ( 'free_shipping' === $rate->method_id && $nw_is_free_shipping) {

                $is_free_shipping=true;

                break;

            }

        }



        if($is_free_shipping) {

            // yes: remove all non-free_shipping methods 

            foreach ( $rates as $rate_id => $rate ) {

                if ( 'free_shipping' === $rate->method_id) {

                    continue;

                }



                unset($rates[$rate_id]);

            }

        } else {

            // no: remove free_shipping method 

            foreach ( $rates as $rate_id => $rate ) {

                if ( 'free_shipping' === $rate->method_id) {

                    unset($rates[$rate_id]);

                }

            }

        }

    }



    return $rates;

}



function get_nw_user_shipping_rate () {

    $is_free_shipping = $rate = false;



    $current_user = wp_get_current_user();

    // $active_club_id = get_user_meta($current_user->ID, '_nw_active_shop');

    // $active_club_id = $active_club[0];

    $active_club_id = WC()->session->get('nw_shop');



    if(!empty($active_club_id) && is_numeric($active_club_id)) {

        $is_club_freight_charge = get_post_meta($active_club_id, '_nw_no_freight_charge');

        $club_freight_charge = get_post_meta($active_club_id, '_nw_freight_charge');



        if(!empty($is_club_freight_charge) && is_array($is_club_freight_charge)) {

            $is_free_shipping = ($is_club_freight_charge[0]=='nw_activated')? true: false;

        }



        if(!empty($club_freight_charge) && is_array($club_freight_charge)) {

            if(isset($club_freight_charge[0]) && is_numeric($club_freight_charge[0])) {

                $rate = $club_freight_charge[0];

            }

        }

    }



    return array($is_free_shipping, $rate);

}



// Custom Field 'Shop' in Orders + Sortable + Searchable 

add_filter( 'manage_edit-shop_order_columns', 'custom_shop_order_columns', 20 );

add_action( 'manage_shop_order_posts_custom_column' , 'custom_orders_list_columns_content', 20, 2 );

add_filter("manage_edit-shop_order_sortable_columns", 'custom_orders_list_columns_sort');

add_action('pre_get_posts', 'custom_shop_column_orderby');



add_action( 'restrict_manage_posts', 'custom_orders_shop_filter_ctrl' );

add_filter( 'pre_get_posts', 'custom_orders_shop_filter_query' );





function custom_shop_order_columns($columns)

{

    $reordered_columns = array();



    foreach( $columns as $key => $column){

        $reordered_columns[$key] = $column;

        if( $key ==  'order_status' ){ //Inserting after "Status" column            

            $reordered_columns['my-shop'] = __( 'Shop');

        }

    }



    return $reordered_columns;

}



function custom_orders_list_columns_content( $column, $post_id )

{

    switch ( $column )

    {

        case 'my-shop' :

            // Get custom post meta data

            $my_shop_id = get_post_meta( $post_id, '_nw_club', true );

            if(!empty($my_shop_id)) {

                echo esc_html( get_the_title($my_shop_id) );

            } else {

                echo '<small>(<em>no value</em>)</small>';

            }



            break;

    }

}



function custom_orders_list_columns_sort($columns) {

    $columns['my-shop'] = 'my-shop';

    return $columns;    

}



function custom_shop_column_orderby( $query ) {

    if ( !is_admin() ){ return; }



    $orderby = $query->get( 'orderby');

    $order = $query->get( 'order');

    if ('my-shop' == $orderby){

        global $wpdb;    

        if(strtolower($order)!='desc') $order = 'asc';



        $result = $wpdb->get_results("SELECT P.ID 

                                        FROM ".$wpdb->prefix."posts as P 

                                        left outer join ".$wpdb->prefix."postmeta PM ON P.ID=PM.post_id and PM.meta_key='_nw_club'

                                        join ".$wpdb->prefix."posts as P2 ON PM.meta_value=P2.ID and P2.post_type='nw_club'

                                        WHERE P.post_type='shop_order' 

                                        ORDER BY P2.post_title ".$order.", P.post_date desc");



        $post_ids = array();

        foreach($result as $a) {

            $post_ids[$a->ID] = $a->ID;

        }



      $query->set('orderby','post__in');

      $query->set('post__in',$post_ids);

    }

}



function custom_orders_shop_filter_ctrl() {

    global $typenow, $wp_query, $wpdb;

    if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ) ) ) :

        $nw_shop  = '';

        $nw_shops = array();



        $nw_shop_results = $wpdb->get_results("SELECT ID, post_title

                                        FROM $wpdb->posts

                                        WHERE post_type='nw_club' and post_status='nw_activated'

                                        ORDER BY post_title");



        foreach ( $nw_shop_results as $vals ) :

            $nw_shops[ $vals->ID ] = $vals->post_title;

        endforeach;



        // Set a selected user role

        if ( ! empty( $_GET['_nw_shop'] ) ) {

            $nw_shop  = sanitize_text_field( $_GET['_nw_shop'] );

        }

        // Display drop down

        ?><select name='_nw_shop'>

            <option value=''><?php _e( 'Select a Shop', 'newwave' ); ?></option><?php

            foreach ( $nw_shops as $key => $value ) :

                ?><option <?php selected( $nw_shop, $key ); ?> value='<?php echo $key; ?>'><?php echo $value; ?></option><?php

            endforeach;

        ?></select><?php

    endif;

}



function custom_orders_shop_filter_query( $query ) {

    if ( ! $query->is_main_query() || empty($_GET['post_type']) || $_GET['post_type']!='shop_order' ) {

        return;

    }



    $ids = array();

    //EGON Restaurant 



    if(isset($_GET['s']) && $_GET['s']!='') {

        global $wpdb;

        $nw_shop_results = $wpdb->get_results("SELECT ID FROM $wpdb->posts 

                                                WHERE post_type='nw_club' and post_title like '%".$_GET['s']."%'");



        foreach($nw_shop_results as $a) {

            $ids[] = $a->ID;

        }

    }



    if (!empty( $_GET['_nw_shop'] ) ) {

        $ids[] = $_GET['_nw_shop'];

    }



    if ( count( $ids ) ) {

        $ids = array_map( 'absint', $ids );

            

        $query->set( 'meta_query', array(

            array(

                'key' => '_nw_club',

                'compare' => 'IN',

                'value' => $ids

            )

        ) );

    } else {

        $query->set( 'posts_per_page', 0 );

    }

}



function DFA($arr) {

    echo '<pre>';

    print_r($arr);

    echo '</pre>';

}

add_action( 'woocommerce_save_account_details', 'my_woocommerce_save_account_details', 1 );

function my_woocommerce_save_account_details( $user_id ){
        $shops = get_user_meta($user_id, "_nw_shops");
//        die();exit();
	if ( isset( $_POST['subscribe_newsletter'] ) ) {
		if(add_email_to_mailchimp($_POST['account_email'], $shops)){
			update_user_meta( $user_id, 'subscribe_newsletter', $_POST['subscribe_newsletter']);
		}else{
			update_user_meta( $user_id, 'subscribe_newsletter', "false" );
		}
	} else {
		add_email_to_mailchimp($_POST['account_email'], $shops, 'unsubscribed');
		update_user_meta( $user_id, 'subscribe_newsletter', "false" );
	}
}

function add_email_to_mailchimp($email = '', $shops, $status = 'subscribed'){
	if (!empty($email)) {
		$apiKey = '371b8f84d9a84d8cd1deba05c00657b5-us21';
		$listId = '710dfc02fb';
		$memberId = md5(strtolower($email));
		$dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
		$url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;
                
                
                $str_shops = [];
                $shops = explode(",",trim($shops[0], ","));
                $count_shops = count($shops);
                foreach($shops as $key=>$value) $str_shops[] = get_post($value)->post_title;
                
		$json = json_encode([ 'email_address' => $email, 'status' => $status, "tags_count" => $count_shops, "tags" => $str_shops ]);
//                echo "<pre>". $json ."</pre>";
//                exit();die;
		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                
		$result = curl_exec($ch);

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
                
		if ($httpCode == 200) {
			return true;
		}
		return false;
	}
	return false;
}

/*

add_filter( 'woocommerce_get_catalog_ordering_args', 'custom_sort_by_price_woocommerce_shop' );

 

function custom_sort_by_price_woocommerce_shop( $args ) {

    

   $orderby_value = isset( $_GET['orderby'] ) ? wc_clean( (string) wp_unslash( $_GET['orderby'] ) ) : wc_clean( get_query_var( 'orderby' ) );

   $orderby_value = is_array( $orderby_value ) ? $orderby_value : explode( '-', $orderby_value );

   $orderby       = esc_attr( $orderby_value[0] );

   $order         = ! empty( $orderby_value[1] ) ? $orderby_value[1] : '';

   $orderby = strtolower( is_array( $orderby ) ? (string) current( $orderby ) : (string) $orderby );

   $order   = strtoupper( is_array( $order ) ? (string) current( $order ) : (string) $order );

 

    

   if ( 'price' == $orderby ) {

      if ( 'DESC' === $order ) {

        add_filter( 'posts_clauses', 'custom_order_by_price_desc_post_clauses' );

     } else {

        add_filter( 'posts_clauses', 'custom_order_by_price_asc_post_clauses' );

     }

   }    

}



function custom_order_by_price_desc_post_clauses($args){

    global $wpdb, $wp_query;



    if ( isset( $wp_query->queried_object, $wp_query->queried_object->term_taxonomy_id, $wp_query->queried_object->taxonomy ) && is_a( $wp_query->queried_object, 'WP_Term' ) ) {

            $search_within_terms   = get_term_children( $wp_query->queried_object->term_taxonomy_id, $wp_query->queried_object->taxonomy );

            $search_within_terms[] = $wp_query->queried_object->term_taxonomy_id;

            $args['join'] .= " INNER JOIN (

                    SELECT post_id, max( meta_value+0 ) price

                    FROM $wpdb->postmeta

                    INNER JOIN (

                            SELECT $wpdb->term_relationships.object_id

                            FROM $wpdb->term_relationships

                            WHERE 1=1

                            AND $wpdb->term_relationships.term_taxonomy_id IN (" . implode( ',', array_map( 'absint', $search_within_terms ) ) . ")

                    ) as products_within_terms ON $wpdb->postmeta.post_id = products_within_terms.object_id

                    WHERE meta_key='_price' GROUP BY post_id ) as price_query ON $wpdb->posts.ID = price_query.post_id ";

    } else {

            $args['join'] .= " INNER JOIN ( SELECT post_id, max( meta_value+0 ) price FROM $wpdb->postmeta WHERE meta_key='_price' GROUP BY post_id ) as price_query ON $wpdb->posts.ID = price_query.post_id ";

    }



    $args['orderby'] = " price_query.price DESC, $wpdb->posts.ID DESC ";

    return $args;

}



function custom_order_by_price_asc_post_clauses($args){

    global $wpdb, $wp_query;



    if ( isset( $wp_query->queried_object, $wp_query->queried_object->term_taxonomy_id, $wp_query->queried_object->taxonomy ) && is_a( $wp_query->queried_object, 'WP_Term' ) ) {

            $search_within_terms   = get_term_children( $wp_query->queried_object->term_taxonomy_id, $wp_query->queried_object->taxonomy );

            $search_within_terms[] = $wp_query->queried_object->term_taxonomy_id;

            $args['join'] .= " INNER JOIN (

                    SELECT post_id, max( meta_value+0 ) price

                    FROM $wpdb->postmeta

                    INNER JOIN (

                            SELECT $wpdb->term_relationships.object_id

                            FROM $wpdb->term_relationships

                            WHERE 1=1

                            AND $wpdb->term_relationships.term_taxonomy_id IN (" . implode( ',', array_map( 'absint', $search_within_terms ) ) . ")

                    ) as products_within_terms ON $wpdb->postmeta.post_id = products_within_terms.object_id

                    WHERE meta_key='_price' GROUP BY post_id ) as price_query ON $wpdb->posts.ID = price_query.post_id ";

    } else {

            $args['join']    .= " INNER JOIN ( SELECT post_id, min( meta_value+0 ) price FROM $wpdb->postmeta WHERE meta_key='_price' GROUP BY post_id ) as price_query ON $wpdb->posts.ID = price_query.post_id ";

    }

    $args['orderby'] = " price_query.price ASC, $wpdb->posts.ID ASC ";

    return $args;

}



add_filter( 'woocommerce_matched_tax_rates', 'custom_woocommerce_matched_tax_rates', 10, 6 );



function custom_woocommerce_matched_tax_rates($matched_tax_rates, $country, $state, $postcode, $city, $tax_class) {

    $zero_tax_zipcodes = get_theme_mod('zero_tax_zipcodes');

    $zero_tax_zipcodes = explode(',', $zero_tax_zipcodes);

    if($zero_tax_zipcodes && in_array($postcode, $zero_tax_zipcodes)) {

        $matched_tax_rates = array();

    }

    return $matched_tax_rates;

}



add_action("customize_register", "craft_customize_register");

	

    function craft_customize_register($wp_customize) {

            $wp_customize->add_section("tax_settings", array(

    "title" => __("Tax settings", "craft"),

    "priority" => 100,

            ));





            $wp_customize->add_setting("zero_tax_zipcodes", array(

    "default" => "",

            ));



            $wp_customize->add_control(new WP_Customize_Control(

                    $wp_customize, "zero_tax_zipcodes", array(

                    'label' => __('Zero tax zipcodes', 'craft'),

                        'description' => 'comma seperated list',

                    'section' => 'tax_settings',

                    'settings' => 'zero_tax_zipcodes'

                    )

            ));



}

function change_default_sorting_name( $catalog_orderby ) {

    $catalog_orderby = str_replace("Sorter etter siste", "Sorter etter nyhetsgrad", $catalog_orderby);

    return $catalog_orderby;

}

add_filter( 'woocommerce_catalog_orderby', 'change_default_sorting_name' );

add_filter( 'woocommerce_default_catalog_orderby_options', 'change_default_sorting_name' );


*/
add_filter('woocommerce_save_account_details_required_fields', 'remove_required_fields');



function remove_required_fields( $required_fields ) {

    unset($required_fields['account_display_name']);

    return $required_fields;

}

add_action('wp_ajax_checkout_pay_by_invoice', 'checkout_pay_by_invoice'); // wp_ajax_{ACTION HERE} 
add_action('wp_ajax_nopriv_checkout_pay_by_invoice', 'checkout_pay_by_invoice');

function checkout_pay_by_invoice(){
	$user_id = get_current_user_id();
	if($user_id){
		$error = 0;
		$billing_first_name = $_POST['billing_first_name'];
		$billing_last_name 	= $_POST['billing_last_name'];
		$billing_company 	= $_POST['billing_company'];
		$billing_address_1 	= $_POST['billing_address_1'];
		$billing_address_2 	= $_POST['billing_address_2'];
		$billing_postcode 	= $_POST['billing_postcode'];
		$billing_city 		= $_POST['billing_city'];
		$billing_phone 		= $_POST['billing_phone'];
		$billing_email 		= $_POST['billing_email'];
		
		$msg = '';
		
		if(empty($billing_first_name)){
			$error = 1;
			$msg .= '<span>Betaling Fornavn er et obligatorisk felt.</span>';
		}
		
		if(empty($billing_last_name)){
			$error = 1;
			$msg .= '<span>Betaling Etternavn er et obligatorisk felt.</span>';
		}
		
		if(empty($billing_address_1)){
			$error = 1;
			$msg .= '<span>Betaling Gateadresse er et obligatorisk felt.</span>';
		}
		
		if(empty($billing_postcode)){
			$error = 1;
			$msg .= '<span>Betaling Postnummer er et obligatorisk felt.</span>';
		}
		
		if(empty($billing_city)){
			$error = 1;
			$msg .= '<span>Betaling Sted er et obligatorisk felt.</span>';
		}
		
		if(empty($billing_phone)){
			$error = 1;
			$msg .= '<span>Betaling Telefon er et obligatorisk felt.</span>';
		}
		
		if(empty($billing_email)){
			$error = 1;
			$msg .= '<span>Betaling E-postadresse er et obligatorisk felt.</span>';
		}
		
		
		if($error==1){
			$response['msg'] = $msg;
			$response['success'] = 0;
		}
		else{
			update_user_meta($user_id,'billing_first_name',$billing_first_name);
			update_user_meta($user_id,'billing_last_name',$billing_last_name);
			update_user_meta($user_id,'billing_company',$billing_company);
			update_user_meta($user_id,'billing_address_1',$billing_address_1);
			update_user_meta($user_id,'billing_address_2',$billing_address_2);
			update_user_meta($user_id,'billing_postcode',$billing_postcode);
			update_user_meta($user_id,'billing_city',$billing_city);
			update_user_meta($user_id,'billing_phone',$billing_phone);
			update_user_meta($user_id,'billing_email',$billing_email);
			
			//shipping address update
			update_user_meta($user_id,'shipping_first_name',$billing_first_name);
			update_user_meta($user_id,'shipping_last_name',$billing_last_name);
			update_user_meta($user_id,'shipping_company',$billing_company);
			update_user_meta($user_id,'shipping_address_1',$billing_address_1);
			update_user_meta($user_id,'shipping_address_2',$billing_address_2);
			update_user_meta($user_id,'shipping_postcode',$billing_postcode);
			update_user_meta($user_id,'shipping_city',$billing_city);
			update_user_meta($user_id,'shipping_phone',$billing_phone);
			update_user_meta($user_id,'shipping_email',$billing_email);
									   
			$response['success'] = 1;
		}
	}
	else{
		$response['success'] = 0;
	}

	echo json_encode($response);
	die();
}

// variations as buttons
add_filter('woocommerce_dropdown_variation_attribute_options_html', 'varitions_as_buttons', 10, 2);
function varitions_as_buttons($html, $args) {
    if ( is_single() ) {
        $custom_html = "";
        $args = wp_parse_args(apply_filters('woocommerce_dropdown_variation_attribute_options_args', $args), array(
            'options' => false,
            'attribute' => false,
            'product' => false,
            'selected' => false,
            'name' => '',
            'id' => '',
            'class' => '',
            'show_option_none' => __('Velg farge', 'woocommerce')
        ));

        $options = $args['options'];
        $product = $args['product'];
        $color_images = array();
        $attribute = $args['attribute'];
        $name = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title($attribute);
        $id = $args['id'] ? $args['id'] : sanitize_title($attribute);
        $class = $args['class'];
        $show_option_none = $args['show_option_none'] ? true : false;
        $color_var_urls = array();

        if (empty($options) && !empty($product) && !empty($attribute)) {
            $attributes = $product->get_variation_attributes();
            $options = $attributes[$attribute];
        }

        $custom_html = '<div class="' . esc_attr($class) . ' custom_var_wrap" name="' . esc_attr($name) . '" data-attribute_name="attribute_' . esc_attr(sanitize_title($attribute)) . '"' . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';

        if (!empty($options)) {
            if ($product && taxonomy_exists($attribute)) {
                // Get terms if this is a taxonomy - ordered. We need the names too.
                $terms = wc_get_product_terms($product->get_id(), $attribute, array('fields' => 'all'));
                if($attribute == 'pa_color'){
                    if(get_post_meta($product->get_id(), '_nw_show_oos_variants', true) == "1"){
                        $variation_ids = $product->get_children();
                        $available_variations = array();
                        foreach ( $variation_ids as $variation_id ) {
                            $variation = wc_get_product( $variation_id );
                            $available_variations[] = $product->get_available_variation( $variation );
                        }
                        $variations = array_values( array_filter( $available_variations ) );
                    }else{
                        $variations = $product->get_available_variations();
                    }
                    foreach ($variations as $variation) {
                        $color_images[$variation['attributes']['attribute_pa_color']] = $variation['image']['full_src'];
                        $color_var_urls[$variation['attributes']['attribute_pa_color']] = get_permalink($variation['variation_id']);
                    }
                    $pa_dropdown = '<select id="pa_color_all" style="display: none"><option value="">Velg et alternativ</option>';
                    foreach ($terms as $term) {
                        if (in_array($term->slug, $options) && array_key_exists($term->slug, $color_var_urls)) {
                            $key = $color_images[$term->slug];
                            $variation_url = isset($color_var_urls[$term->slug]) ? $color_var_urls[$term->slug] : '';
                            if($key){
                                $custom_html .= '<label><input type="radio" name="custom_var_' . esc_attr($id) . '" color="' . esc_attr($term->name) . '" value="' . esc_attr($term->slug) . '" ' . checked(sanitize_title($args['selected']), $term->slug, false) . ' data-variation_url="' . esc_attr($variation_url) .'" /><div class="dd" style="background-image: url('.$key.')" data-value="' . esc_attr($term->slug) . '"></div></label>';
                            } else {
                                $custom_html .= '<label><input type="radio" name="custom_var_' . esc_attr($id) . '" color="' . esc_attr($term->name) . '" value="' . esc_attr($term->slug) . '" ' . checked(sanitize_title($args['selected']), $term->slug, false) . ' data-variation_url="' . esc_attr($variation_url) .'" /><div class="dd" style="background-image: url('. wc_placeholder_img_src() .')" data-value="' . esc_attr($term->slug) . '"></div></label>';
                            }
                            $pa_dropdown .= '<option value="' . esc_attr($term->slug) . '" class="attached enabled">' . esc_attr($term->name) . '</option>';
                        }
                    }
                    echo $pa_dropdown .= '</select>';
                } else {
                    foreach ($terms as $term) {
                        if (in_array($term->slug, $options)) {
                            $custom_html .= '<label><input type="radio" name="custom_var_' . esc_attr($id) . '" value="' . esc_attr($term->slug) . '" ' . ($attribute != 'pa_size' ? checked(sanitize_title($args['selected']), $term->slug, false) : '') . '/><span>' . esc_html(apply_filters('woocommerce_variation_option_name', $term->name)) . '</span></label>';
                        }
                    }
                }

            } else {
                foreach ($options as $option) {
                    // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                    $selected = sanitize_title($args['selected']) === $args['selected'] ? checked($args['selected'], sanitize_title($option), false) : checked($args['selected'], $option, false);
                    $custom_html .= '<label><input type="radio" name="custom_var_' . esc_attr($id) . '" value="' . esc_attr($option) . '" ' . $selected . '/><span>' . esc_html(apply_filters('woocommerce_variation_option_name', $option)) . '</span></label>';
                }
            }
        }

        $custom_html .= '</div>';

        $html = '<select class="' . esc_attr($class) . '" name="' . esc_attr($name) . '" data-attribute_name="attribute_' . esc_attr(sanitize_title($attribute)) . '"' . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '" id=
        "'.$id.'">';

        if ($show_option_none) {
            $html .= '<option value="">' . esc_html($args['show_option_none']) . '</option>';
        }

        if (!empty($options)) {
            if ($product && taxonomy_exists($attribute)) {
                // Get terms if this is a taxonomy - ordered. We need the names too.
                $terms = wc_get_product_terms($product->get_id(), $attribute, array('fields' => 'all'));

                foreach ($terms as $term) {
                    if (in_array($term->slug, $options)) {
                        $html .= '<option value="' . esc_attr($term->slug) . '" ' . selected(sanitize_title($args['selected']), $term->slug, false) . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $term->name)) . '</option>';
                    }
                }
            } else {
                foreach ($options as $option) {
                    // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                    $selected = sanitize_title($args['selected']) === $args['selected'] ? selected($args['selected'], sanitize_title($option), false) : selected($args['selected'], $option, false);
                    $html .= '<option value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $option)) . '</option>';
                }
            }
        }

        $html .= '</select>';

        echo $custom_html;
        echo $html;
    } else {
        $args = wp_parse_args( apply_filters( 'woocommerce_dropdown_variation_attribute_options_args', $args ), array(
            'options'          => false,
            'attribute'        => false,
            'product'          => false,
            'selected'         => false,
            'name'             => '',
            'id'               => '',
            'class'            => '',
            'show_option_none' => __( 'Velg farge', 'woocommerce' ),
        ) );

        $options               = $args['options'];
        $product               = $args['product'];
        $attribute             = $args['attribute'];
        $name                  = $args['name'] ? $args['name'] : 'attribute_' . sanitize_title( $attribute );
        $id                    = $args['id'] ? $args['id'] : sanitize_title( $attribute );
        $class                 = $args['class'];
        $show_option_none      = $args['show_option_none'] ? true : false;
        $show_option_none_text = false; // We'll do our best to hide the placeholder, but we'll need to show something when resetting options.

        if ( empty( $options ) && ! empty( $product ) && ! empty( $attribute ) ) {
            $attributes = $product->get_variation_attributes();
            $options    = $attributes[ $attribute ];
        }

        $html  = '<select id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" name="' . esc_attr( $name ) . '" data-attribute_name="attribute_' . esc_attr( sanitize_title( $attribute ) ) . '" data-show_option_none="' . ( $show_option_none ? 'yes' : 'no' ) . '">';


        if ( ! empty( $options ) ) {
            if ( $product && taxonomy_exists( $attribute ) ) {
                // Get terms if this is a taxonomy - ordered. We need the names too.
                $terms = wc_get_product_terms( $product->get_id(), $attribute, array(
                    'fields' => 'all',
                ) );

                foreach ( $terms as $term ) {
                    if ( in_array( $term->slug, $options, true ) ) {
                        $html .= '<option value="' . esc_attr( $term->slug ) . '" ' . selected( sanitize_title( $args['selected'] ), $term->slug, false ) . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) ) . '</option>';
                    }
                }
            } else {
                foreach ( $options as $option ) {
                    // This handles < 2.4.0 bw compatibility where text attributes were not sanitized.
                    $selected = sanitize_title( $args['selected'] ) === $args['selected'] ? selected( $args['selected'], sanitize_title( $option ), false ) : selected( $args['selected'], $option, false );
                    $html    .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) ) . '</option>';
                }
            }
        }
        $html .= '</select>';
        return $html;
    }
}

// Disable out of stock variations
add_filter( 'woocommerce_variation_is_active', 'grey_out_variations_when_out_of_stock', 10, 2 );
function grey_out_variations_when_out_of_stock( $grey_out, $variation ) {

    if ( ! $variation->is_in_stock() )
        return false;

    return true;
} 

add_action('wp_ajax_nopriv_craft_ajax_login', 'craft_ajax_login');
function craft_ajax_login() {

    $errors = array();
    $err_flds = array();
    $redirect_link = '';
    
    // The global form-login.php template used `_wpnonce` in template versions < 3.3.0.
    $nonce_value = wc_get_var( $_REQUEST['woocommerce-login-nonce'], wc_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.

    if ( isset( $_POST['username'], $_POST['password'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-login' ) ) {

        try {
            $creds = array(
                'user_login'    => trim( wp_unslash( $_POST['username'] ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                'user_password' => $_POST['password'], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                'remember'      => isset( $_POST['rememberme'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            );

            $validation_error = new WP_Error();
            $validation_error = apply_filters( 'woocommerce_process_login_errors', $validation_error, $creds['user_login'], $creds['user_password'] );

            if ( $validation_error->get_error_code() ) {
                throw new Exception( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $validation_error->get_error_message() );
            }

            if ( empty( $creds['user_login'] ) ) {
                array_push($err_flds, 'username');
                throw new Exception( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . __( 'Username is required.', 'woocommerce' ) );
            }

            // On multisite, ensure user exists on current site, if not add them before allowing login.
            if ( is_multisite() ) {
                $user_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );

                if ( $user_data && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
                    add_user_to_blog( get_current_blog_id(), $user_data->ID, 'customer' );
                }
            }

            // Perform the login.
            $user = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), is_ssl() );

            if ( is_wp_error( $user ) ) {
                array_push($err_flds, 'username');
                array_push($err_flds, 'password');
                throw new Exception( $user->get_error_message() );
            } else {

                wp_set_current_user( $user->ID );

                do_action( 'wp_login', $user->user_login, $user );

                if ( ! empty( $_POST['redirect'] ) ) {
                    $redirect = wp_unslash( $_POST['redirect'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                } elseif ( wc_get_raw_referer() ) {
                    $redirect = wc_get_raw_referer();
                } else {
                    $redirect = wc_get_page_permalink( 'myaccount' );
                }

                $redirect_link = wp_validate_redirect( apply_filters( 'woocommerce_login_redirect', remove_query_arg( 'wc_error', $redirect ), $user ), wc_get_page_permalink( 'myaccount' ) );
            }
        } catch ( Exception $e ) {
            array_push($errors, apply_filters( 'login_errors', $e->getMessage() ));
            do_action( 'woocommerce_login_failed' );
        }
    } else 
        array_push($errors, __('Invalid Request'));

    echo json_encode(array('errors' => $errors, 'err_flds' => $err_flds, 'redirect_link' => $redirect_link, 'ses' => WC()->session->get('nw_shop'), 'ses1' => WC()->session->get('nw_error_msg'), 'user_id' => get_current_user_id()));

    wp_die();
}

add_action('wp_ajax_nopriv_craft_ajax_register', 'craft_ajax_register');
function craft_ajax_register() {

    $errors = array();
    $err_flds = array();
    $redirect_link = '';

    $nonce_value = isset( $_POST['_wpnonce'] ) ? wp_unslash( $_POST['_wpnonce'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $nonce_value = isset( $_POST['woocommerce-register-nonce'] ) ? wp_unslash( $_POST['woocommerce-register-nonce'] ) : $nonce_value; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

    if ( isset( $_POST['email'] ) && wp_verify_nonce( $nonce_value, 'woocommerce-register' ) ) {
        $username = 'no' === get_option( 'woocommerce_registration_generate_username' ) && isset( $_POST['username'] ) ? wp_unslash( $_POST['username'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $password = 'no' === get_option( 'woocommerce_registration_generate_password' ) && isset( $_POST['password'] ) ? $_POST['password'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
        $email    = wp_unslash( $_POST['email'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        try {
            $validation_error  = new WP_Error();
            $validation_error  = apply_filters( 'woocommerce_process_registration_errors', $validation_error, $username, $password, $email );

            if (!isset($_POST['nw_first_name']) || empty($_POST['nw_first_name'])) {
                array_push($err_flds, 'nw_first_name');
            }

            // Validate last name
            if (!isset($_POST['nw_last_name']) || empty($_POST['nw_last_name'])) {
                array_push($err_flds, 'nw_last_name');
            }

            // Validate norwegian phone number
            $phone_err = __('Please enter a valid phone number to register.', 'newwave');
            if (!isset($_POST['nw_phone']) || empty($_POST['nw_phone'])) {
                array_push($err_flds, 'nw_phone');
            }

            $phone = sanitize_text_field($_POST['nw_phone']);
            preg_match('/^(\+47)?((4|9)(\d{7}))/', $phone, $matches);
            if (!$matches) {
                array_push($err_flds, 'nw_phone');
            }

            // Validate the registration code
            if (!isset($_POST['nw_registration_code'])) {
                array_push($err_flds, 'nw_registration_code');
            }

            $reg_code = strtoupper(sanitize_text_field($_POST['nw_registration_code']));
            $search = new WP_Query(array(
                'post_type' => 'nw_club',
                'meta_key' => '_nw_reg_code',
                'meta_value' => $reg_code
            ));

            if (!$search->found_posts) {
               array_push($err_flds, 'nw_registration_code');
            }

            
            if ( isset( $_POST['nw_billing_gateadresse'] ) && empty( $_POST['nw_billing_gateadresse'] ) ) {
                array_push($err_flds, 'nw_billing_gateadresse');
            }
            
            if ( isset( $_POST['nw_postnummer'] ) && empty( $_POST['nw_postnummer'] ) ) {
                array_push($err_flds, 'nw_postnummer');
            }
            
            if ( isset( $_POST['nw_sted'] ) && empty( $_POST['nw_sted'] ) ) {
                array_push($err_flds, 'nw_sted');
            }

            $validation_errors = $validation_error->get_error_messages();

            if ( 1 === count( $validation_errors ) ) {
                throw new Exception( $validation_error->get_error_message() );
            } elseif ( $validation_errors ) {
                foreach ( $validation_errors as $message ) {
                    array_push($errors, '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $message);
                }
                throw new Exception();
            }

            $new_customer = wc_create_new_customer( sanitize_email( $email ), wc_clean( $username ), $password );

            if ( is_wp_error( $new_customer ) ) {
                throw new Exception( $new_customer->get_error_message() );
            }

            if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
                $success_msg = __( 'Your account was created successfully and a password has been sent to your email address.', 'woocommerce' );
            } else {
                $success_msg =  __( 'Your account was created successfully. Your login details have been sent to your email address.', 'woocommerce' );
            }

            // Only redirect after a forced login - otherwise output a success notice.
            if ( apply_filters( 'woocommerce_registration_auth_new_customer', true, $new_customer ) ) {
                wc_set_customer_auth_cookie( $new_customer );

                if ( ! empty( $_POST['redirect'] ) ) {
                    $redirect = wp_sanitize_redirect( wp_unslash( $_POST['redirect'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                } elseif ( wc_get_raw_referer() ) {
                    $redirect = wc_get_raw_referer();
                } else {
                    $redirect = wc_get_page_permalink( 'myaccount' );
                }

                $redirect_link = wp_validate_redirect( apply_filters( 'woocommerce_registration_redirect', $redirect ), wc_get_page_permalink( 'myaccount' ) ); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
            }
        } catch ( Exception $e ) {
            if ( $e->getMessage() ) {
                array_push($errors, '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $e->getMessage());
            }
        }
    }

    echo json_encode(array('errors' => $errors, 'err_flds' => $err_flds, 'redirect_link' => $redirect_link, 'success_msg' => $success_msg, 'validation_error' => $validation_errors));

    wp_die();
}

add_action( 'woocommerce_after_add_to_cart_button', 'add_content_after_addtocart_button_func' );
/*
 * Content below "Add to cart" Button.
 */
function add_content_after_addtocart_button_func() {

        // Echo content.
        echo '<div class="dagers-content">30 dagers returmulighet (gjelder ikke logo produkter) | Fri frakt på kjøp over 500kr</div>';

}

/*
 * Add text to quantity Textbox .
 */
add_action( 'woocommerce_after_add_to_cart_quantity', 'action_woocommerce_after_add_to_cart_quantity', 10, 0 ); 
 
function action_woocommerce_after_add_to_cart_quantity() {
 echo '<div class="qty_text">STK</div>'; 
}

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );

add_filter('woocommerce_reset_variations_link', '__return_empty_string');

/*
 * change text in checkout page
 */
add_filter( 'gettext', 'change_cart_totals_text', 20, 3 );
function change_cart_totals_text( $translated, $text, $domain ) {
	
    if( is_checkout() && $translated == 'Totalt' ){
        $translated = __('Frakt', 'woocommerce');
    }
    return $translated;
}

/******** For Load More Page *************/
add_action('wp_ajax_nopriv_more_products_ajax', 'more_products_ajax');
add_action('wp_ajax_more_products_ajax', 'more_products_ajax');
function more_products_ajax(){

    $postsPerPage 	= apply_filters('loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page());
    $paged 			= (isset($_POST['pageNumber'])) ? $_POST['pageNumber'] : 0;
    $category_id 	= (isset($_POST['cat'])) ? $_POST['cat'] : '';
	
	if (class_exists('NW_Session')) 
		remove_action('pre_get_posts', 'NW_Session::filter_posts', 99, 1);


    header("Content-Type: text/html");
    $tax_query = ['relation' => 'AND'];
    // if != alle
	
	if(!empty($category_id)){
		$category = array(
			'taxonomy' => 'product_cat',
			'field' => 'id',
			'terms' => $category_id,
		);
		$tax_query[] = $category;
	}

    //check if NW_Session exists
	if (class_exists('NW_Session')) {
		$_nw_access = array(
			'taxonomy' => '_nw_access',
			'field'    => 'term_taxonomy_id',
			'terms'    => NW_Session::$shop->get_terms(),
		);
		$tax_query[] = $_nw_access;
	}

    $args = array(
        'post_type' => 'product',
		'post_status' => 'publish',
        'posts_per_page' => $postsPerPage,
        'paged'    => $paged
    );
    $args['tax_query'] = $tax_query;
	//$args['tax_query'] = $tax_query;
	
	
    $query = new WP_Query($args);
	wp_reset_query();
	
	if (class_exists('NW_Session')) 
		add_action('pre_get_posts', 'NW_Session::filter_posts', 99, 1);

    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) {
            $query->the_post();           

            /**
             * Hook: woocommerce_shop_loop.
             *
             * @hooked WC_Structured_Data::generate_product_data() - 10
             */
            do_action('woocommerce_shop_loop');

            wc_get_template_part('content', 'product');
        }

        $last = $query->max_num_pages > $args['paged'] ? FALSE : TRUE;
        $post_count = $query->found_posts;
        wp_reset_query();

        echo json_encode(array('html' => ob_get_clean(), 'last' => $last, 'post_count' => $post_count, 'query' => $query->request, 'args' => $args ));
    }
    elseif ($paged == 1) {
        ob_start();
        ?>
        <p class="woocommerce-info"><?php _e('No products were found matching your selection.', 'woocommerce'); ?></p>
        <?php
        echo json_encode(array('html' => ob_get_clean(), 'post_count' => 0, 'last' => TRUE, 'query' => $query->request));
    } else {
        echo json_encode(array('html' => '', 'last' => TRUE));
    }
    die();
}

/* add_action('init', function() {

    if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
        if(!empty($_GET['del_vendor']) && !empty($_GET['post_id']))
            script_delete_vendors($_GET['post_id']);
        else if(!empty($_GET['del_club']) && !empty($_GET['post_id']))
            script_delete_club($_GET['post_id']);
    }
}); */

/* function script_delete_vendors($post_ids) {

    global $wpdb;

    $post_type = 'nw_vendor';

    $nw_posts = $wpdb->get_col("SELECT ID FROM ".$wpdb->prefix."posts AS p WHERE p.ID IN(".$post_ids.") AND p.post_type='".$post_type."'");

    echo "<pre>";
    print_r($nw_posts);
    echo "</pre>";

    if(!empty($nw_posts)) {
        foreach($nw_posts as $ind => $nw_post) { 
            $args = array(
                'post_type' => 'shop_order',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_nw_vendor',
                        'value' => $nw_post,
                        'compare' => '='
                    )
                )
            );

            $res = new WP_Query($args);
            echo "<br/><br/><br/>".$res->request;

            if($res->have_posts()) {
                while($res->have_posts()) {

                    $res->the_post();

                    $post_id = get_the_ID();

                    wp_delete_post($post_id);

                    echo "<br/> --- Deleted Order --- ".$post_id;
                }
            }

            echo "<br/>".$res->found_posts." shop_order posts deleted where _nw_vendor = ".$nw_post;

            $args = array(
                'post_type' => 'nw_club',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'vendor_id',
                        'value' => $nw_post,
                        'compare' => '='
                    )
                )
            );

            $res1 = new WP_Query($args);
            echo "<br/><br/>".$res1->request;
            if($res1->have_posts()) {
                while($res1->have_posts()) {

                    $res1->the_post();

                    $post_id = get_the_ID();

                    script_delete_club($post_id);
                }
            }

            echo "<br/>".$res1->found_posts." nw_club posts deleted where vendor_id = ".$nw_post;

            wp_delete_post($nw_post);
            echo "<br/> --- nw_vendor = ".$nw_post." deleted";
        }
    }

    exit;
} */

/* function script_delete_club($post_ids) {
    global $wpdb;

    $post_type = 'nw_club';

    $nw_posts = $wpdb->get_col("SELECT ID FROM ".$wpdb->prefix."posts AS p WHERE p.ID IN(".$post_ids.") AND p.post_type='".$post_type."'");

    echo "<pre>";
    print_r($nw_posts);
    echo "</pre>";

    if(!empty($nw_posts)) {
        require_once(ABSPATH.'wp-admin/includes/user.php' );
        foreach($nw_posts as $ind => $nw_post) { 
            $args = array(
                'post_type' => 'shop_order',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_nw_club',
                        'value' => $nw_post,
                        'compare' => '='
                    )
                )
            );

            $res = new WP_Query($args);
            echo "<br/><br/><br/>".$res->request;

            if($res->have_posts()) {
                while($res->have_posts()) {

                    $res->the_post();

                    $post_id = get_the_ID();

                    wp_delete_post($post_id);

                    echo "<br/> --- Deleted Order --- ".$post_id;
                }
            }

            echo "<br/>".$res->found_posts." shop_order posts deleted where _nw_club = ".$nw_post."<br/><br/>";

            $users = get_users(array(
                'meta_key'     => '_nw_shops',
                'meta_value'   => $nw_post,
                'meta_compare' => 'LIKE',
                'fields' => 'ids'
            ));

            $del_cnt=$updt_cnt=0;
            $tot_cnt = count($users);
            foreach($users as $ind1 => $user_id) {
                $user_pm = get_user_meta($user_id, '_nw_shops', true);
                $user_shops = explode(",", trim($user_pm, ",") );
                echo "<br/>".$user_pm." --- ".json_encode($user_shops);

                if (($key = array_search($nw_post, $user_shops)) !== false) {

                    if(count($user_shops) == 1) {
                        wp_delete_user($user_id);

                        echo "<br/> --- Deleted User --- ".$user_id;
                        $del_cnt++;
                    } else {
                        unset($user_shops[$key]);

                        $new_nw_shops = ",".implode(",", $user_shops).",";

                        update_user_meta( $user_id, '_nw_shops', $new_nw_shops );

                        echo "<br/> --- Updated User meta --- ".$user_id." = ".$new_nw_shops;
                        $updt_cnt++;
                    }
                }
            }

            echo "<br/>".$del_cnt."/".$tot_cnt." deleted and ".$updt_cnt."/".$tot_cnt." updated users where _nw_shops = ".$nw_post;

            wp_delete_post($nw_post);
            echo "<br/> --- nw_club = ".$nw_post." deleted";
        }
    }

    exit;
} */

add_filter( 'woocommerce_get_script_data', 'change_alert_text', 10, 2 );
function change_alert_text( $params, $handle ) {
    if ( $handle === 'wc-add-to-cart-variation' )
	{
        $params['i18n_make_a_selection_text'] = __( 'Obs! Du må velge farge, antall og størrelse', 'domain' );
	}

    return $params;
}

add_filter( 'woocommerce_get_availability', 'func_woocommerce_get_availability', 1, 2);
function func_woocommerce_get_availability( $availability, $_product ) {
    
    // Change Out of Stock Text
    if ( ! $_product->is_in_stock() ) {
        $availability['availability'] = __("Utsolgt", "woocommerce");
    }
    return $availability;
} 

/*** Options Page ***/
if( function_exists('acf_add_options_page') ) {

	$option_page = acf_add_options_page(array(
		'page_title' 	=> 'Theme General Settings',
		'menu_title' 	=> 'Theme Settings',
		'menu_slug' 	=> 'theme-general-settings',
		'capability' 	=> 'edit_posts',
		'redirect' 	=> false
	));

}

//add_action( 'woocommerce_before_add_to_cart_button', 'storeguide', 25 );

function storeguide(){
	global $product;
	echo '<div class="storeguide_wrap">';?>
	<a class="popup-modal" href="#store-modal"><img src="<?php echo get_stylesheet_directory_uri();?>/images/storrelsesguide.svg" class="img-responsive"/><span>Størrelsesguide</span></a>
	<?php echo '</div>';?>
<?php }

/* change price here  for PLANASD-337 */
function return_custom_price($price, $product) {
	if(!is_admin() && $product->get_type() == 'nw_stock_logo'  )
	{
		$price1 = get_post_meta(  $product->get_id(), 'nw_logo_price', true );  
		if($price1 > 0)
		{
			return $price1;
		}
	}
	return $price;
}
add_filter('woocommerce_product_get_price', 'return_custom_price', 10, 2);
/* change price here  for PLANASD-337 */

add_filter( 'woocommerce_get_price_html', 'func_add_prefix_price', 99, 2 );  
function func_add_prefix_price( $price, $product ){
	
	global $product;
	$price_suffix = 'kr';
	$nw_with_logo = '';
	
	if ( is_product() )//&& $nw_type == 'nw_stock_logo'
	{
		$price_suffix = ',-';
		if($product->is_type('nw_stock_logo'))
		{
			$nw_with_logo = ' eks. logo';
		}
		
		$price = '<strong>Din pris:</strong> '.trim($price).$price_suffix;
		
	}
	else
	{
		$price = '<strong>Din pris:</strong> '.$price;
		
		
	}
	// round is done to match the price with hook woocommerce_get_price_html price
	$num_decimal = get_option( 'woocommerce_price_num_decimals' );
	$total = (float)str_replace(",",".",get_post_meta(  $product->id, '_price',1));
	$price .= '<br> <div class="Veil-pris">Veil pris: '.round($total,$num_decimal).''.$price_suffix.'&nbsp;'.$nw_with_logo .'</div> ';
	
    return $price; 
}

add_filter( 'woocommerce_order_number', 'change_woocommerce_order_number' );
function change_woocommerce_order_number( $order_id ) 
{
    $prefix = 'INTSH';
    $new_order_id = $prefix . $order_id;
    return $new_order_id;
}


/*** Dummy Coupon Form & Checkout Title ***/
function title_coupon_start() {
	?>
	<div class="left-check-col">
		
		
		<div class="after-checkout-gift-card-form">
			<?php
            $ywgc_minimal_car_total = get_option ( 'ywgc_minimal_car_total' );
            if ( WC()->cart->total < $ywgc_minimal_car_total ){ ?>
                <p class="woocommerce-error" role="alert">
                    <?php echo _x( "In order to apply the gift card, the total amount in the cart has to be at least", 'Apply gift card', 'yith-woocommerce-gift-cards' ) . " " . $ywgc_minimal_car_total . get_woocommerce_currency_symbol(); ?>
                </p>
            <?php } ?>
			<div class="check-this ">
				<div class="check">
					<img src="<?php echo get_stylesheet_directory_uri(); ?>/images/checked.png" alt="check" />
				</div>
				<h3>Gavekort</h3>
			</div>
			<div class="ywgc_enter_code" style="display: none;"><!--style="display: none;"-->
				<input type="text" name="gift_card_code" class="input-text"
				placeholder="<?php echo esc_attr( apply_filters( 'ywgc_checkout_box_placeholder', _x( 'Gavekortkode', 'Apply gift card', 'yith-woocommerce-gift-cards' ) ) ); ?>"
				id="giftcard_code"
				value="" />
				<!-- <input type="button" class="" name="ywgc_apply_gift_card"
				value="<?php echo esc_attr( apply_filters( 'ywgc_checkout_apply_code', _x( 'Apply gift card', 'Apply gift card', 'yith-woocommerce-gift-cards' ) ) ); ?>" /> -->
				<button type="submit" class="button ywgc_apply_gift_card_button" name="ywgc_apply_gift_card" value="<?php echo get_option( 'ywgc_apply_gift_card_button_text' , esc_html__( 'Apply Gift Card', 'yith-woocommerce-gift-cards' ) ); ?>"><?php echo get_option( 'ywgc_apply_gift_card_button_text' , esc_html__( 'Apply Gift Card', 'yith-woocommerce-gift-cards' ) ); ?></button>
				
				<input type="hidden" name="is_gift_card"
				value="1" />
			</div>
    	</div>
	<?php
}
add_action('kco_wc_before_snippet', 'title_coupon_start', 20);


add_filter( 'kco_check_if_needs_payment', 'kco_change_check_if_needs_payment' );
function kco_change_check_if_needs_payment( $bool ) {
  return false;
}

add_action('admin_head', 'my_wocommerce_admin');
function my_wocommerce_admin() {
  echo '<style>
    .woocommerce_options_panel .nw-table-container.nw-discounts input[type=number] {
    width: 100%;
    float: left;
}
  </style>';
}

add_action('init', 'add_nw_order_state_meta');
function add_nw_order_state_meta() {
    if (isset($_GET['add_nw_order_state_meta'])) {
        // Get all orders which are in ASW queue
        update_order_state('processing', '_nw_order_queued', '1');
        // Get all orders which are uploaded to IBS
        update_order_state('complete', '_nw_order_placed', '1');
    }
}
function update_order_state($state, $meta_key, $meta_value) {
    $args = [
        'limit' => -1,
        'meta_key' => $meta_key,
        'meta_value' => $meta_value,
    ];
    $query = new WC_Order_Query($args);
    $orders = $query->get_orders();
    if (count($orders)) {
        foreach ($orders as $order) {
            update_post_meta($order->get_id(), '_nw_order_state', $state);
            update_post_meta($order->get_id(), '_nw_order_state_log', "updated via script"); 
        }
    }
}