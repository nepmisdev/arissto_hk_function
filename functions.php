<?php

function theme_enqueue_styles() {
    wp_enqueue_style( 'avada-parent-stylesheet', get_template_directory_uri() . '/style.css' );
	wp_enqueue_script( 'script', get_template_directory_uri() . '/signature.js');
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );

add_filter( 'send_email_change_email', '__return_false' );

function avada_lang_setup() {
	$lang = get_stylesheet_directory() . '/languages';
	load_child_theme_textdomain( 'Avada', $lang );
}
add_action( 'after_setup_theme', 'avada_lang_setup' );

add_filter( 'gform_enable_field_label_visibility_settings', '__return_true' );
add_filter( 'wc_product_sku_enabled', '__return_false' );

/*Code of allow own referral*/
add_filter( 'affwp_is_customer_email_affiliate_email', '__return_false' );
add_filter( 'affwp_tracking_is_valid_affiliate', '__return_true' );

add_action( 'gform_user_registered','we_autologin_gfregistration', 10, 4 );
function we_autologin_gfregistration( $user_id, $config, $entry, $password ) {
    wp_set_auth_cookie( $user_id, false, '' );
}

add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
	if (!current_user_can('administrator') && !is_admin()) {
		show_admin_bar(false);
	}
}

function curl_get_contents($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

add_shortcode('free', 'free_experience_button');
function free_experience_button ($atts, $content = null) {
	$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	$username = basename($url);
	return '<a class="button" href="http://arissto.com/hk/free-experience-online-registration-form/onlinecafe/'.$username.'"><span>'.do_shortcode($content).'</span></a>';
}

add_shortcode('user_display_name', 'show_displayname');
function show_displayname ($atts) {
	$user = wp_get_current_user();

	return $user->display_name;
}

add_shortcode('my_orders', 'shortcode_my_orders');
function shortcode_my_orders( $atts ) {
    extract( shortcode_atts( array(
        'order_count' => -1
    ), $atts ) );

    ob_start();
    wc_get_template( 'myaccount/my-orders.php', array(
        'current_user'  => get_user_by( 'id', get_current_user_id() ),
        'order_count'   => $order_count
    ) );
    return ob_get_clean();
}

add_filter('woocommerce_payment_complete_order_status', 'autocompletePaidOrders', 10, 2);
function autocompletePaidOrders($order_status, $order_id) {
	$order = new WC_Order($order_id);
	if ($order_status == 'processing' && ($order->status == 'on-hold' || $order->status == 'pending' || $order->status == 'failed' || $order->status == 'pending payment')) {
		return 'completed';
	}
	return $order_status;
}

add_filter( 'woocommerce_return_to_shop_redirect', 'wc_empty_cart_redirect_url' );
function wc_empty_cart_redirect_url() {
	return '/hk/buy-now';
}

add_filter( 'woocommerce_login_redirect', 'affwp_wc_redirect_affiliates', 10, 2 );
function affwp_wc_redirect_affiliates( $redirect, $user ) {
    $user_id = $user->ID;
    $url = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	if ( function_exists( 'affwp_is_affiliate' ) && affwp_is_affiliate( $user_id ) ) {
		$redirect = '/hk/promote-list/';
	} else if ($url != site_url( '/checkout/', 'https' )) {
		$redirect = '/hk/my-account';
	}     
    return $redirect;
}

add_filter( 'gform_pre_render_42', 'add_readonly_script' );
function add_readonly_script($form) {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery("li.gfield.readonly input").attr("readonly","readonly");
        });
    </script>
    
    <?php
    return $form;
}

add_filter('woocommerce_currency_symbol', 'change_existing_currency_symbol', 10, 2);
function change_existing_currency_symbol( $currency_symbol, $currency ) {
	switch( $currency ) {
		case 'HKD': $currency_symbol = 'HK$'; break;
	}
	return $currency_symbol;
}

/*gravity form functions*/

add_shortcode('affiliate_form', 'show_affiliate_form');
function show_affiliate_form ($atts) {
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;
	$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	$username = basename($url);
	$user = get_user_by('login', $username );
	if (is_user_logged_in()) {
		if ( function_exists( 'affwp_is_affiliate' ) && affwp_is_affiliate( $user_id ) ) {
			$sc_form = 'You already applied as an ARISSTO Partner.';
			return $sc_form;
		} else {
			$sc_form = '[gravityform id=41 title=false description=false field_values="refemail='.$user->user_email.'&ref='.$username.'"]';
			return do_shortcode($sc_form);
		} 
	} else {
		$sc_form = '[gravityform id=41 title=false description=true]';
		return do_shortcode($sc_form);
	}
}







add_action('gform_after_submission_41', 'upd_affiliate');
function upd_affiliate($entry) {
	$name = $entry["29"];
	$code = $entry["26"];
	$email = $entry["1"];
	$phone = $entry["8"];
	$displayname = $entry["35"];
	$logged_user_id = get_current_user_id();
	$current_user = get_userdata($logged_user_id);
	$user = get_user_by('login', $current_user->user_login);

	if (get_user_meta($user->ID, 'Partner_Code', true)) {
		update_usermeta($user->ID, 'Partner_Code', $code);
	} else {
		add_user_meta($user->ID, 'Partner_Code', $code);
	}

	update_usermeta($user->ID, 'billing_phone', $phone);
	update_usermeta($user->ID, 'billing_email', $email);
	update_usermeta($user->ID, 'first_name', $name);
	update_usermeta($user->ID, 'display_name', $displayname);
}

add_shortcode('free_experience_form', 'show_free_experience_form');
function show_free_experience_form ($atts) {
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;
	$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	$username = basename($url);
	$user = get_user_by('login', $username );
	if (is_user_logged_in()) {
		$sc_form = '[gravityform id=24 title=false description=false field_values="refemail='.$user->user_email.'&ref='.$username.'"]';
		return do_shortcode($sc_form);
	} else {
		$sc_form = '[gravityform id=24 title=false description=false]';
		return do_shortcode($sc_form);
	}
}

add_action( 'gform_pre_submission_29', 'gen_Code' );
function gen_Code($form) {
	global $wpdb;
	$prefix = 'HKA';

	$code = $wpdb->get_var("SELECT value FROM 5f5Bpz_rg_lead_detail where form_id = 29 and field_number = 69 and value like 'HKA%' order by value desc limit 1");

	if(empty($code)) {
		$code = 'HKA90001';
	} else {
		$code = substr($code, -5);
		$code = (int)$code + 1;
		$code = $prefix . $code;
	}

	$_POST['input_69'] = $code;
}

add_action('gform_after_submission_29', 'insert_Code');
function insert_Code($entry) {
	global $wpdb;

	$fullname = $entry["49"];
	$hkid = $entry["51"];
	$email = $entry["1"];
	$addr = $entry["12"];
	$phone = $entry["8"];
	$referralname = $entry["40"];
	$referralcode = $entry["41"];
	$referralphone = $entry["42"];
	$agentname = $entry["18"];
	$agentcode = $entry["23"];
	$agentphone = $entry["50"];
	$bank = $entry["36"];
	$bankacc = $entry["37"];
	$acctype = $entry["38"];
	$sign = $entry["48"];
	$prd = $entry["71"];
	$payment = $entry["75"];
	$cardno = $entry["76"];
	$banktransfer = $entry["77"];
	$chequeno = $entry["78"];
	$others = $entry["79"];
	$code = $entry["69"];

	if ($cardno) {
		$payment_info = $cardno;
	} else if ($banktransfer) {
		$payment_info = $banktransfer;
	} else if ($chequeno) {
		$payment_info = $chequeno;
	} else if ($others) {
		$payment_info = $others;
	} else {
		$payment_info = '';
	}

	$sign = str_replace('.png','',$sign);
	
	$urlInsert = 'http://203.198.208.217:8081/ArisstoHK/OnlineForm?sp_name=usp_iPartner_Application_Ins&param_count=22&param1='.$code.'&param2='.$fullname.'&param3='.$hkid.'&param4='.$email.'&param5='.$addr.'&param6='.$phone.'&param7='.$referralname.'&param8='.$referralcode.'&param9='.$referralphone.'&param10='.$agentname.'&param11='.$agentcode.'&param12='.$agentphone.'&param13='.$bank.'&param14='.$bankacc.'&param15='.$acctype.'&param16='.$sign.'&param17='.$prd.'&param18='.$payment.'&param19='.$cardno.'&param20='.$banktransfer.'&param21='.$chequeno.'&param22='.$others;
	//$urlInsert = "http://203.198.208.217:8081/Arissto/db?sp_name=usp_iPartner_Application_Ins&param=".$code."|".$fullname."|".$hkid."|".$email."|".$addr."|".$phone."|".$referralname."|".$referralcode."|".$referralphone."|".$agentname."|".$agentcode."|".$agentphone."|".$bank."|".$bankacc."|".$acctype."|".$sign."|".$prd."|".$payment."|".$cardno."|".$banktransfer."|".$chequeno."|".$others."&data_type=STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR|STR&type=IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN|IN";
	$urlInsert = str_replace( ' ', '%20', $urlInsert );
	$urlInsert = str_replace( '#', '%23', $urlInsert );
	curl_get_contents($urlInsert);
	//file_get_contents( $urlInsert );

	/*echo file_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=usp_iPartner_Ins&param=".$code."|".$fullname."|".$hkid."|".$email."|".$phone."|".$referralcode."|".$bankacc."|".$bank."&data_type=STR|STR|STR|STR|STR|STR|STR|STR&type=IN|IN|IN|IN|IN|IN|IN|IN");*/

	$exists = email_exists( $email );
	$user = get_user_by( 'email', $email );
	if ($exists) {
		$user->add_role( 'ipartner' );
	} else {
		$userdata = array(
			'user_login' =>  $email,
			'user_email' =>  $email,
			'user_pass'  =>  $email,
			'role' => 'ipartner'
		);

		$user_id = wp_insert_user( $userdata );

		if (get_user_meta($user_id, 'Partner_Code', true)) {
			update_usermeta($user_id, 'Partner_Code', $code);
		} else {
			add_user_meta($user_id, 'Partner_Code', $code);
		}

	}
	
	$urlPdf = "http://203.198.208.217:8081/ArisstoHK/iPartnerApplication?application_html_template=iPartnerApplication&application_param=".$code."%7C".$fullname."%7C".$hkid."%7C".$email."%7C".$addr."%7C".$phone."%7C".$referralname."%7C".$referralcode."%7C".$referralphone."%7C".$agentname."%7C".$agentcode."%7C".$agentphone."%7C".$bank."%7C".$bankacc."&application_signature=".$sign."&application_account_type=".$acctype."&application_pdf_file=D:/iPartner/Application/".$code."_Application.pdf&order_html_template=iPartnerOrder&order_param=".$code."%7C".$code."%7C".$fullname."%7C".$phone."%7C".$email."%7C".$addr."%7C".$referralname."%7C".$referralcode."%7C".$referralphone."%7C".$agentname."%7C".$agentcode."%7C".$agentphone."&order_product_type=".$prd."&order_payment_type=".$payment."&order_payment_info=".$payment_info."&order_signature=".$sign."&order_pdf_file=D:/iPartner/Order/".$code."_Order.pdf&mail_subject=ARISSTO%20iPartner%20申請表%20和%20訂購表&mail_from=do-not-reply@arissto.com.hk&mail_from_name=ARISSTO&mail_to=".$email."&mail_template=iPartnerApplication&mail_application_name=hk%20ipartner%20email%20id";
	$urlPdf = str_replace( ' ', '%20', $urlPdf );
	$urlPdf = str_replace( '#', '%23', $urlPdf );
	curl_get_contents($urlPdf);
	//file_get_contents( $urlPdf );
}

add_shortcode('upload_form', 'show_upload_form');
function show_upload_form ($atts) {
	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;
	$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	$username = substr($url,(strpos($url, "f=")+2));
	$user = get_user_by('login', $username );
	
	$sc_form = '[gravityform id=42 title=false description=false field_values="email='.$user->user_email.'&username='.$username.'"]';
	return do_shortcode($sc_form);
}

/*woocommerce order process function*/

add_action( 'woocommerce_checkout_order_processed', 'my_status_pending',  1, 1 );
function my_status_pending($order_id) {
	global $woocommerce;
	try {
		$order = new WC_Order( $order_id );

		sendOrderDataMT($order_id);

		$items = $order->get_items();
			
		foreach ( $items as $key => $item ) {
				
			$product_name = $item['name'];
			$product_id = $item['product_id'];
			$cur_item_id = $key;
			$qtyitem = $item['qty'];

			sendOrderDataDT($order_id, $product_id, $cur_item_id, $qtyitem);			
		}

		$shipping_items = $order->get_items( 'shipping' );
			
		foreach ( $shipping_items as $key => $item ) {
				
			$method = $item['method_id'];
			$unitprice = $item['cost'];

			sendOrderDataDT2($order_id, $method, $unitprice);			
		}

		sendOrderDataDelivery($order_id);
			
	} catch (Exception $e) {
		wc_add_notice($e->getMessage(), 'error');
        return false;
    }
}

add_action( 'woocommerce_order_status_completed', 'send_data' );
function send_data($order_id) {
	//$request = file_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=USP_ONLINE_CAPSULE_SUB_REQUEST&param=".$order_id."|completed&data_type=STR|STR&type=IN|IN");
	//$request = curl_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=USP_ONLINE_CAPSULE_SUB_REQUEST&param=".$order_id."|completed&data_type=STR|STR&type=IN|IN");
	$request = curl_get_contents("http://203.198.208.217:8081/ArisstoHK/OnlineForm?sp_name=USP_ONLINE_CAPSULE_SUB_REQUEST&param_count=2&param1='.$order_id.'&param2=completed");
}

add_action( 'woocommerce_order_status_cancelled', 'delete_data' );
function delete_data($order_id) {
	//$request = file_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=USP_ONLINE_CAPSULE_SUB_REQUEST&param=".$order_id."|cancelled&data_type=STR|STR&type=IN|IN");
	//$request = curl_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=USP_ONLINE_CAPSULE_SUB_REQUEST&param=".$order_id."|completed&data_type=STR|STR&type=IN|IN");
	$request = curl_get_contents("http://203.198.208.217:8081/ArisstoHK/OnlineForm?sp_name=USP_ONLINE_CAPSULE_SUB_REQUEST&param_count=2&param1='.$order_id.'&param2=cancelled");
}

function sendOrderDataMT($order_id) {
	global $woocommerce;

	$order = new WC_Order( $order_id );

	$name = urlencode($order -> billing_first_name.' '.$order -> billing_last_name);;
	$total = $order -> order_total;

	//$request = file_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=sp_Online_TranMT&param=".$order_id."|".$name."|".$total."&data_type=STR|STR|FLOAT&type=IN|IN|IN");
	//$request = curl_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=sp_Online_TranMT&param=".$order_id."|".$name."|".$total."&data_type=STR|STR|FLOAT&type=IN|IN|IN");
	$request = curl_get_contents("http://203.198.208.217:8081/ArisstoHK/OnlineForm?sp_name=sp_Online_TranMT&param_count=3&param1='.$order_id.'&param2='.$name.'&param3='.$total");
}

function sendOrderDataDT($order_id, $product_id, $item_id, $qty) {
	global $woocommerce;
	$order = new WC_Order( $order_id );
	
	$prdcode = get_post_meta($product_id, 'c_prd_code', true);
	$priceno = get_post_meta($product_id, 'c_price_no', true);
	
	$itemtotal = wc_get_order_item_meta( $item_id, '_line_subtotal', true );
	$itemtax = wc_get_order_item_meta( $item_id, '_line_subtotal_tax', true );

	$ctotal = $itemtotal+$itemtax;

	$unitprice = $ctotal/$qty;

	//$request = file_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=sp_Online_TranDT&param=".$order_id."|".$prdcode."|".$priceno."|".$unitprice."|".$qty."|1&data_type=STR|STR|STR|FLOAT|INT|INT&type=IN|IN|IN|IN|IN|IN");
	//$request = curl_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=sp_Online_TranDT&param=".$order_id."|".$prdcode."|".$priceno."|".$unitprice."|".$qty."|1&data_type=STR|STR|STR|FLOAT|INT|INT&type=IN|IN|IN|IN|IN|IN");
	$request = curl_get_contents("http://203.198.208.217:8081/ArisstoHK/OnlineForm?sp_name=sp_Online_TranDT&param_count=6&param1='.$order_id.'&param2='.$prdcode.'&param3='.$priceno.'&param4='.$unitprice.'&param5='.$qty.'&param6=1");
}

function sendOrderDataDT2($order_id, $method, $unitprice) {
	global $woocommerce;
	$order = new WC_Order( $order_id );
	
	if (strpos($method, 'table_rate:3') !== false) {
		$prdcode = 'GNDLCHK8801';
	} else if (strpos($method, 'table_rate-2') !== false) {
		$prdcode = 'INDCOHK00002';
	}

	//$request = file_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=sp_Online_TranDT&param=".$order_id."|".$prdcode."|21|".$unitprice."|1|1&data_type=STR|STR|STR|FLOAT|INT|INT&type=IN|IN|IN|IN|IN|IN");
	//$request = curl_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=sp_Online_TranDT&param=".$order_id."|".$prdcode."|21|".$unitprice."|1|1&data_type=STR|STR|STR|FLOAT|INT|INT&type=IN|IN|IN|IN|IN|IN");
	$request = curl_get_contents("http://203.198.208.217:8081/ArisstoHK/OnlineForm?sp_name=sp_Online_TranDT&param_count=6&param1='.$order_id.'&param2='.$prdcode.'&param3=21&param4='.$unitprice.'&param5=1&param6=1");
}

function sendOrderDataDelivery($order_id) {
	global $woocommerce;
	$order = new WC_Order( $order_id );

	$name = urlencode($order -> shipping_first_name.' '.$order -> shipping_last_name);
	$contact = $order -> billing_phone;
	$addr1 = urlencode($order -> shipping_address_1);
	$addr2 = urlencode($order -> shipping_address_2);
	$city = urlencode($order ->shipping_city);
	$state = urlencode($order ->shipping_state);

	if (empty($addr2)) {
		$addr2 = $city;
		$addr3 = $state;
	} else {
		$addr2 = $addr2;
		$addr3 = $city;
	}

	//$request = file_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=sp_Online_Online_DeliverySite&param=".$order_id."|".$name."|".$contact."|".$addr1."|".$addr2."|".$addr3."|".$state."&data_type=STR|STR|STR|STR|STR|STR|STR&type=IN|IN|IN|IN|IN|IN|IN");
	//$request = curl_get_contents("http://203.198.208.217:8081/Arissto/db?sp_name=sp_Online_Online_DeliverySite&param=".$order_id."|".$name."|".$contact."|".$addr1."|".$addr2."|".$addr3."|".$state."&data_type=STR|STR|STR|STR|STR|STR|STR&type=IN|IN|IN|IN|IN|IN|IN");
	$request = curl_get_contents("http://203.198.208.217:8081/ArisstoHK/OnlineForm?sp_name=sp_Online_Online_DeliverySite&param_count=7&param1='.$order_id.'&param2='.$name.'&param3='.$contact.'&param4='.$addr1.'&param5='.$addr2.'&param6='.$addr3.'&param7='.$state");
}

add_action( 'init', 'my_custom_endpoints' );
function my_custom_endpoints() {
    add_rewrite_endpoint( 'ipartner', EP_ROOT | EP_PAGES );
}

add_filter( 'query_vars', 'my_custom_query_vars', 0 );
function my_custom_query_vars( $vars ) {
    $vars[] = 'ipartner';
    return $vars;
}
 
add_filter( 'woocommerce_account_menu_items', 'my_custom_my_account_menu_items' );
function my_custom_my_account_menu_items( $items ) {
    $logout = $items['customer-logout'];
	unset( $items['customer-logout'] );

	// Insert your custom endpoint.
	$items['ipartner'] = __( 'iPartner', 'woocommerce' );

	// Insert back the logout item.
	$items['customer-logout'] = $logout;

	return $items;
}
 
add_action( 'woocommerce_account_ipartner_endpoint', 'my_custom_endpoint_content' );
function my_custom_endpoint_content() {
	$ipartner = false;
	foreach( wp_get_current_user()->roles as $role ) {
		if ( $role == 'ipartner' || $role == 'administrator' ) {
			$ipartner = true;
		}
	}

	$logged_user_id = get_current_user_id();
	$current_user = get_userdata($logged_user_id);
	$user = get_user_by('login', $current_user->user_login);

	if ( $ipartner ) {
		echo '<h2>iPartner Link</h2>';
		echo '<table><tr><td><a href="http://arissto.com/hk/ipartner-application/?ref=' . wp_get_current_user()->user_login . '" target="_blank"><img src="http://arissto.com/hk/wp-content/uploads/ipartner-application-button.png"/></a></td></tr></table>';

		echo '<h2>Upload Payment</h2>';
		echo '<table><tr><td><a href="http://arissto.com/hk/upload-payment/?ref=' . wp_get_current_user()->user_login . '" target="_blank"><button class="gform_button button">Upload</button></a></td></tr></table>';

		echo '<h2>iPartner Application</h2>';
		echo '<table width="100%"><tr><td>' . do_shortcode('[wpdatatable id=2 var1=' . wp_get_current_user()->user_email . ']') . '</td></tr></table>';

		echo '<h2>iPartner Commission Statement</h2>';
		echo '<table width="100%"><tr><td>' . do_shortcode('[wpdatatable id=1 var1=' . wp_get_current_user()->user_email . ']') . '</td></tr></table>';
	}
}

add_action( 'gform_pre_submission_45', 'commission_create_user' );
function commission_create_user() {
	$filename = dirname( __FILE__ ) . '/arissto_hk_ipartner_list.csv';
	$file = fopen( $filename, 'r' );
	while ( ( $line = fgetcsv( $file ) ) != FALSE ) {
		$email_address = trim( $line[0] );
		if( username_exists( $email_address ) != null || username_exists( $email_address ) || email_exists( $email_address ) != null || email_exists( $email_address ) ) {
			echo $email_address . ' exist!!!<br/>';
		} else {
			$password = wp_generate_password( 12, false );
			$user_id = wp_create_user( $email_address, $password, $email_address );
			$user = new WP_User( $email_address );
			$user->set_role( 'ipartner' );
			wp_new_user_notification( $user_id, '', 'user' );
			echo $email_address . '|' . $password . '|<br/>';
		}
	}
	fclose( $file );
}

function patricks_woocommerce_catalog_orderby( $orderby ) {
	$orderby["date"] = __('Sort by date: newest to oldest', 'woocommerce');
	return $orderby;
}
add_filter( "woocommerce_catalog_orderby", "patricks_woocommerce_catalog_orderby", 20 );

// Hook in
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
    unset($fields['billing']['billing_city']);
     unset($fields['billing']['billing_postcode']);

     return $fields;
}

add_shortcode( 'add_ro_user', 'add_ro_user' );
function add_ro_user() {
	if ( is_user_logged_in() ) {
		$valid_role = false;
		foreach( wp_get_current_user()->roles as $role ) {
			if ( $role == 'administrator' ) {
				$valid_role = true;
			}
		}
		if ( $valid_role ) {
			echo do_shortcode( "[gravityform id='57' title='false' description='false']" );
		} else {
			echo 'Invalid user...';
		}
	} else {
		echo 'Please log in first...';
	}
}

add_action( 'gform_after_submission_57', 'submit_ro_user_form' );
function submit_ro_user_form( $entry ) {
	$bill_code = $entry[5];
	$user_code = $entry[3];
	$name = $entry[2];
	$email = $entry[1];

	$exists = email_exists( $email );
	if ( $exists > 0 ) {
		echo '<h4><b><font color="#FF0000">Email existed!!!</font></b></h4>';
	} else {
		$user_id = wp_create_user( $user_code, $user_code, $email );
		$user = new WP_User( $email );
		//$user->set_role( 'salesadmin' );
		wp_update_user( array( 'ID' => $user_id, 'user_nicename ' => $name ) );
		wp_update_user( array( 'ID' => $user_id, 'display_name  ' => $name ) );
		update_usermeta( $user_id, 'first_name', $name );
		update_usermeta( $user_id, 'last_name', '' );
		add_user_meta( $user_id, 'User_Code', $user_code );
		add_user_meta( $user_id, 'Bill_Code', $bill_code );
		$user = get_user_by( 'email', $email );
		$user->set_role( 'salesadmin' );
		echo 'User created successfully...';
	}
}

add_shortcode('coffee_sharing_application_personal', 'coffee_sharing_application_personal');
function coffee_sharing_application_personal ($atts) {
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$usercode = get_user_meta( $user->ID, 'User_Code', true );
		if ( isset( $usercode ) && trim( $usercode ) != "" ) {
			$bill_code = get_user_meta( $user->ID, 'Bill_Code', true );
			if ( isset( $bill_code ) && trim( $bill_code ) != "" ) {
				echo do_shortcode( "[gravityform id='55' title='false' description='false']" );
			} else {
				echo 'Bill Code not found...';
			}
		} else {
			echo 'User Code not found...';
		}
	} else {
		echo 'Please log in first...';
	}
}

add_action('gform_after_submission_55', 'submit_coffee_sharing_application_personal');
function submit_coffee_sharing_application_personal($entry) {
	global $wpdb;
	$firstname = $entry["20.3"];
	$firstname = str_replace( "'", "''", $firstname );
	$lastname = $entry["20.6"];
	$lastname = str_replace( "'", "''", $lastname );
	$fullname = $firstname . ' ' . $lastname;
	$nric = $entry["25"];
	$gender = $entry["50"];
	$race = $entry["51"];
	$email = $entry["1"];
	$phone = $entry["8"];
	$bill_addr1 = $entry["34"];
	$bill_addr1 = str_replace( "'", "''", $bill_addr1 );
	$bill_addr2 = $entry["35"];
	$bill_addr2 = str_replace( "'", "''", $bill_addr2 );
	$bill_addr3 = $entry["60"];
	$bill_addr3 = str_replace( "'", "''", $bill_addr3 );
	$bill_state = $entry["63"];//$entry["38"];
	$bill_city = $entry["62"];//$entry["53"];
	$bill_postcode = $entry["58"];//$entry["54"];
	if ($bill_postcode == 'Others') {
		$bill_postcode = $entry["58"];
	}
	$ship_addr1 = $entry["43"];
	$ship_addr1 = str_replace( "'", "''", $ship_addr1 );
	$ship_addr2 = $entry["44"];
	$ship_addr2 = str_replace( "'", "''", $ship_addr2 );
	$ship_addr3 = $entry["61"];
	$ship_addr3 = str_replace( "'", "''", $ship_addr3 );
	$ship_state = $entry["65"];//$entry["46"];
	$ship_city = $entry["64"];//$entry["55"];
	$ship_postcode = $entry["59"];//$entry["56"];
	if ($ship_postcode == 'Others') {
		$ship_postcode = $entry["59"];
	}
	if (empty($ship_addr1)) {
		$ship_addr1 = $bill_addr1;
	}
	if (empty($ship_addr2)) {
		$ship_addr2 = $bill_addr2;
	}
	if (empty($ship_addr3)) {
		$ship_addr3 = $bill_addr3;
	}
	if (empty($ship_state)) {
		$ship_state = $bill_state;
	}
	if (empty($ship_city)) {
		$ship_city = $bill_city;
	}
	if (empty($ship_postcode)) {
		$ship_postcode = $bill_postcode;
	}
	$ref_code = $entry["40"];
	$ref_name = $entry["41"];
	$ref_name = str_replace( "'", "''", $ref_name );
	$sales_name = $entry["17"];
	$sales_name = str_replace( "'", "''", $sales_name );
	$sponsor = $entry["57"];
	$member_id = $entry["66"];
	$wpdb -> query( "INSERT INTO `ct_membership` (`Membership_No`, `Membership_Type`, `Name`, `NRIC`, `Email`, `Contact`, `Gender`, `Race`, `Mailing_Addr_1`, `Mailing_Addr_2`, `Mailing_Addr_3`, `Mailing_City`, `Mailing_Postcode`, `Mailing_State`, `Delivery_Addr_1`, `Delivery_Addr_2`, `Delivery_Addr_3`, `Delivery_City`, `Delivery_Postcode`, `Delivery_State`, `USER_ID`, `Sales_Partner_Code`, `Sales_Partner_Name`, `Ref_iPartner_Code`, `Ref_iPartner_Name`) VALUES ('".$member_id."','Personal','".$fullname."','".$nric."','".$email."','".$phone."','".$gender."','".$race."','".$bill_addr1."','".$bill_addr2."','".$bill_addr3."','".$bill_city."','".$bill_postcode."','".$bill_state."','".$ship_addr1."','".$ship_addr2."','".$ship_addr3."','".$ship_city."','".$ship_postcode."','".$ship_state."','','".$sponsor."','".$sales_name."','".$ref_code."','".$ref_name."')");
}

add_shortcode( 'search_membership', 'search_membership' );
function search_membership ( $atts ) {
	//echo '<h4><b><font color="#0000FF">System maintenance in progress...</font></b></h4>';
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$usercode = get_user_meta( $user->ID, 'User_Code', true );
		if ( isset( $usercode ) && trim( $usercode ) != "" ) {
			$bill_code = get_user_meta( $user->ID, 'Bill_Code', true );
			if ( isset( $bill_code ) && trim( $bill_code ) != "" ) {
				echo '<h4><b><font color="#0000FF">Bill Code : ' . $bill_code . '</font></b></h4>';
				echo do_shortcode( "[gravityform id='58' title='false' description='false']" );
			} else {
				echo 'Bill Code not found...';
			}
		} else {
			echo 'User Code not found...';
		}
	} else {
		echo 'Please log in first...';
	}
}

add_shortcode( 'subscription_order_form', 'subscription_order_form' );
function subscription_order_form ( $atts ) {
	//echo '<h4><b><font color="#0000FF">System maintenance in progress...</font></b></h4>';
	$membership_no = $_REQUEST['membership_no'];
	$membership_no = sprintf( '%010d', $membership_no );
	$membership_no = "HAM" . $membership_no;
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$usercode = get_user_meta( $user->ID, 'User_Code', true );
		if ( isset( $usercode ) && trim( $usercode ) != "" ) {
			$bill_code = get_user_meta( $user->ID, 'Bill_Code', true );
			if ( isset( $bill_code ) && trim( $bill_code ) != "" ) {
				if ( isset( $membership_no ) && trim( $membership_no ) != "" ) {
					$cust_name = "";
					$cust_nric = "";
					$cust_mobile = "";
					$cust_email = "";
					$cust_gender = "";
					$cust_race = "";
					$cust_address_1 = "";
					$cust_address_2 = "";
					$cust_address_city = "";
					$cust_address_postcode = "";
					$cust_address_state = "";
					$cust_delivery_1 = "";
					$cust_delivery_2 = "";
					$cust_delivery_city = "";
					$cust_delivery_postcode = "";
					$cust_delivery_state = "";
					$dist_name = "";
					$dist_no = "";
					
					global $wpdb;
					$result = $wpdb->get_results( "SELECT * FROM ct_membership WHERE Membership_No = '" . $membership_no . "'" );
					foreach ( $result as $row ) {
						$cust_name = $row->Name;
						$cust_nric = $row->NRIC;
						$cust_mobile = $row->Contact;
						$cust_email = $row->Email;
						$cust_gender = substr( $row->Gender, 0, 1 );
						$cust_race = substr( $row->Race, 0, 1 );
						$cust_address_1 = $row->Mailing_Addr_1;
						$cust_address_2 = $row->Mailing_Addr_2;
						$cust_address_3 = $row->Mailing_Addr_3;
						$cust_address_city = $row->Mailing_City;
						if ( $cust_address_3 != null && $cust_address_3 != $cust_address_city ) {
							$cust_address_2 = $cust_address_2 . " " . $cust_address_3;
						}
						$cust_address_postcode = $row->Mailing_Postcode;
						$cust_address_state = strtoupper( $row->Mailing_State );
						$cust_delivery_1 = $row->Delivery_Addr_1;
						$cust_delivery_2 = $row->Delivery_Addr_2;
						$cust_delivery_3 = $row->Delivery_Addr_3;
						$cust_delivery_city = $row->Delivery_City;
						if ( $cust_delivery_3 != null && $cust_delivery_3 != $cust_delivery_city ) {
							$cust_delivery_2 = $cust_delivery_2 . " " . $cust_delivery_3;
						}
						$cust_delivery_postcode = $row->Delivery_Postcode;
						$cust_delivery_state = strtoupper( $row->Delivery_State );
						$referral = $row->Ref_iPartner_Code;
						$dist_name = $row->Sales_Partner_Name;
						$dist_no = $row->Sales_Partner_Code;
					}
					if ( empty( $dist_no ) ) {
						echo 'Please enter a valid membership number...<br/><br/><u><a href="search-membership">Search again</a></u>';
					} else {
						if ( $cust_delivery_1 == "" ) {
							$cust_delivery_1 = $cust_address_1;
						}
						if ( $cust_delivery_2 == "" ) {
							$cust_delivery_2 = $cust_address_2;
						}
						if ( $cust_delivery_city == "" ) {
							$cust_delivery_city = $cust_address_city;
						}
						if ( $cust_delivery_postcode == "" ) {
							$cust_delivery_postcode = $cust_address_postcode;
						}
						if ( $cust_delivery_state == "" ) {
							$cust_delivery_state = $cust_address_state;
						}
						echo '<h4><b><font color="#0000FF">Bill Code : ' . $bill_code . '</font></b></h4>Membership No : ' . $membership_no . '<br/>Name : ' . $cust_name . ' ( ' . $cust_nric . ' )<br/>Mobile : ' . $cust_mobile . '<br/>Email : ' . $cust_email . '<br/>Billing Address : ' . $cust_address_1 . ' ' . $cust_address_2 . ' ' . $cust_address_postcode . ' ' . $cust_address_city . ' '. $cust_address_state . '<br/>Delivery Address : ' . $cust_delivery_1 . ' ' . $cust_delivery_2 . ' ' . $cust_delivery_postcode . ' ' . $cust_delivery_city . ' ' . $cust_delivery_state . '<br/>Sales Partner Code : ' . $dist_name . ' ( ' . $dist_no . ' )';
						$cust_address_1 = str_replace( "&", "%26", $cust_address_1 );
						$cust_address_2 = str_replace( "&", "%26", $cust_address_2 );
						$cust_address_city = str_replace( "&", "%26", $cust_address_city );
						$cust_delivery_1 = str_replace( "&", "%26", $cust_delivery_1 );
						$cust_delivery_2 = str_replace( "&", "%26", $cust_delivery_2 );
						$cust_delivery_city = str_replace( "&", "%26", $cust_delivery_city );
						$subscription_order_form = '[gravityform id=56 title=false description=false field_values="m_no='.$membership_no.'&cust_name='.$cust_name.
							'&cust_nric='.$cust_nric.'&cust_mobile='.$cust_mobile.'&cust_email='.$cust_email.'&cust_gender='.$cust_gender.'&cust_race='.$cust_race.
							'&cust_address_1='.$cust_address_1.'&cust_address_2='.$cust_address_2.'&cust_address_city='.$cust_address_city.'&cust_address_postcode='.$cust_address_postcode.
							'&cust_address_state='.$cust_address_state.'&cust_delivery_1='.$cust_delivery_1.'&cust_delivery_2='.$cust_delivery_2.'&cust_delivery_city='.$cust_delivery_city.
							'&cust_delivery_postcode='.$cust_delivery_postcode.'&cust_delivery_state='.$cust_delivery_state.'&referral='.$referral.'&name='.$dist_name.'&code='.$dist_no.'"]';
						echo do_shortcode( $subscription_order_form );
					}
				} else {
					echo 'Please enter a membership number...';
				}
			} else {
				echo 'Bill Code not found!!!';
			}
		} else {
			echo 'User Code not found...';
		}
	} else {
		echo 'Please log in first...';
	}
}

add_filter( 'gform_pre_render_56', 'populate_subscription_order_form' );
function populate_subscription_order_form( $form ) {
	$product_packages_details = "<table border='1' width='100%' cellpadding='5' style='border: #AB162B 1px solid'>
		<tr bgcolor='#F5EFEF'><td width='75%'><b>Machine Monthly Rental (HKD)</b></td><td width='25%'>{rental}</td></tr>
		<tr><td><b>Machine Deposit (HKD)</b></td><td>{deposit}</td></tr>
		<tr bgcolor='#F5EFEF'><td><b>Monthly Capsules Commitment</b></td><td>{capsule}</td></tr>
		<tr><td><b>Capsules Price (HKD)</b></td><td>{price}</td></tr>
		<tr bgcolor='#F5EFEF'><td><b>Milk Capsules Price (HKD)</b></td><td>{milk}</td></tr>
		<tr bgcolor='#F5EFEF'><td><b>Free Capsules</b></td><td>{other}</td></tr>
	</table>";
	$plan = array();
	$product_packages = array();
	$machine_deposit = array();
	$capsule_commitment = array();
	$capsule_price = array();
	$milk_price = array();
	array_push( $plan, array( 'value' => 0, 'text' => 'Select Plan' ) );
	$html = str_replace( '{rental}', '', $product_packages_details );
	$html = str_replace( '{deposit}', '', $html );
	$html = str_replace( '{capsule}', '', $html );
	$html = str_replace( '{price}', '', $html );
	$html = str_replace( '{milk}', '', $html );
	$html = str_replace( '{other}', '', $html );
	array_push( $product_packages, $html );
	array_push( $machine_deposit, '' );
	array_push( $capsule_commitment, '' );
	array_push( $capsule_price, '' );
	array_push( $milk_price, '' );
	global $wpdb;
	$result = $wpdb->get_results( "SELECT * FROM ct_coffee_sharing_plan WHERE Status = 'A'" );
	$index = 1;
	foreach ( $result as $row ) {
		array_push( $plan, array( 'value' => $index, 'text' => $row->Description ) );
		$html = str_replace( '{rental}', number_format( (float)$row->Machine_Monthly_Rental, 2 ), $product_packages_details );
		$html = str_replace( '{deposit}', number_format( (float)$row->Machine_Deposit, 2 ), $html );
		$html = str_replace( '{capsule}', $row->Monthly_Capsules_Commitment, $html );
		$html = str_replace( '{price}', number_format( (float)$row->Capsules_Price, 2 ), $html );
		$html = str_replace( '{milk}', number_format( (float)$row->Milk_Capsules_Price, 2 ), $html );
		$html = str_replace( '{other}', $row->Remarks, $html );
		array_push( $product_packages, $html );
		array_push( $machine_deposit, $row->Machine_Deposit );
		array_push( $capsule_commitment, $row->Monthly_Capsules_Commitment );
		array_push( $capsule_price, $row->Capsules_Price );
		array_push( $milk_price, $row->Milk_Capsules_Price );
		$index++;
	}
	$form['fields'][1]['choices'] = $plan;
	$capsule_table = "<table border='1' width='100%' cellpadding='5' style='border: #AB162B 1px solid'><tr valign='top' style='border: #AB162B 1px solid'><td width='20%' align='center'><b>Capsule</b></td><td width='20%' align='center'><b>Price (HKD)</b></td><td width='20%' align='center'><b>Quantity</b></td><td width='20%' align='center'><b>Box</b></td><td width='20%' align='center'><b>Total (HKD)</b></td></tr>";
	$capsule_table_monthly = $capsule_table;
	$capsule_table_addon = $capsule_table;
	$capsule_flavour = array();
	$capsule_quantity = array();
	$substitution = "<option value=''>Please Select</option>";
	$result = $wpdb->get_results( "SELECT * FROM ct_coffee_sharing_capsule WHERE Status = 'A'" );
	$count = 1;
	foreach ( $result as $row ) {
		$id = $row->Name;
		$interval = $row->Quantity_Per_Unit;
		$option = "<option value='0'>-</option>";
		$x = 1;
		while( $x <= 20 ) {
			$val = $x * $interval;
			$option = $option . "<option value='" . $val . "'>" . $val . "</option>";
			$x++;
		}
		$row_bg_color = "#F5EFEF";
		if ( $count % 2 == 0 ) {
			$row_bg_color = "#FFFFFF";
		}
		$capsule_table_monthly = $capsule_table_monthly . "<tr bgcolor='" . $row_bg_color . "'><td>" . $row->Description . "</td>";
		$capsule_table_monthly = $capsule_table_monthly . "<td><span id='Price_" . $id . "'></span></td>";
		$capsule_table_monthly = $capsule_table_monthly . "<td><select id='Quantity_" . $id . "'>" . $option . "</select></td>";
		$capsule_table_monthly = $capsule_table_monthly . "<td><span id='Box_" . $id . "'></span></td>";
		$capsule_table_monthly = $capsule_table_monthly . "<td><span id='Total_" . $id . "'></span></td></tr>";
		$capsule_table_addon = $capsule_table_addon . "<tr bgcolor='" . $row_bg_color . "'><td>" . $row->Description . "</td>";
		$capsule_table_addon = $capsule_table_addon . "<td><span id='Addon_Price_" . $id . "'></span></td>";
		$capsule_table_addon = $capsule_table_addon . "<td><select id='Addon_Quantity_" . $id . "'>" . $option . "</select></td>";
		$capsule_table_addon = $capsule_table_addon . "<td><span id='Addon_Box_" . $id . "'></span></td>";
		$capsule_table_addon = $capsule_table_addon . "<td><span id='Addon_Total_" . $id . "'></span></td></tr>";
		array_push( $capsule_flavour, $id );
		array_push( $capsule_quantity, $interval );
		$count++;
		$substitution = $substitution . "<option value='" . $id . "'>" . $row->Description . "</option>";
	}
	$row_bg_color = "#F5EFEF";
	if ( $count % 2 == 0 ) {
		$row_bg_color = "#FFFFFF";
	}
	$capsule_table_monthly = $capsule_table_monthly . "<tr bgcolor='" . $row_bg_color . "' style='border: #AB162B 1px solid'><td><b>Total</b></td><td></td><td><b><span id='Total_Quantity'></span></b></td><td><b><span id='Total_Box'></span></b></td><td><b><span id='Grand_Total'></span></b></td></tr></table>";
	$capsule_table_addon = $capsule_table_addon . "<tr bgcolor='" . $row_bg_color . "' style='border: #AB162B 1px solid'><td><b>Total</b></td><td></td><td><b><span id='Addon_Total_Quantity'></span></b></td><td><b><span id='Addon_Total_Box'></span></b></td><td><b><span id='Addon_Grand_Total'></span></b></td></tr></table>";
?>
	<script type="text/javascript">
	jQuery( document ).ready( function() {
		jQuery( "li.gf_readonly input" ).attr( "readonly", "readonly" );
		jQuery( "li.gf_readonly textarea" ).attr( "readonly", "readonly" );
		jQuery( ".gform_wrapper .gfield input[type='email'], .gform_wrapper .gfield input[type='number'], .gform_wrapper .gfield input[type='password'], .gform_wrapper .gfield input[type='password'] input[type='number'], .gform_wrapper .gfield input[type='tel'], .gform_wrapper .gfield input[type='text'], .gform_wrapper .gfield input[type='url'], .gform_wrapper .gfield select, .gform_wrapper .gfield textarea, .gform_wrapper .gfield_select[multiple=multiple], .input-text, input[type='email'], input[type='text'], select, textarea" ).css( "color", "black" );
		jQuery( ".gsection_title" ).css( "color", "white" );
		jQuery( ".gsection" ).css( "background-color", "#AB162B" );
		jQuery( ".gsection" ).css( "padding", "8px" );
		jQuery( '#field_56_3' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_56_5' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_56_5' ).html( "<?php echo $capsule_table_monthly; ?>" );
		jQuery( '#field_56_16' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_56_16' ).html( "<?php echo $capsule_table_addon; ?>" );
		jQuery( '#field_56_26' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_56_38' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_56_67' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_56_75' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_56_81' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_56_83' ).removeClass( "gfield_html_formatted " );
		var html_content = <?php echo json_encode( $product_packages ); ?>;
		var machine_deposit = <?php echo json_encode( $machine_deposit ); ?>;
		var capsule_flavour = <?php echo json_encode( $capsule_flavour ); ?>;
		var milk_price = <?php echo json_encode( $milk_price ); ?>;
		var capsule_price = <?php echo json_encode( $capsule_price ); ?>;
		var capsule_commitment = <?php echo json_encode( $capsule_commitment ); ?>;
		var capsule_quantity = <?php echo json_encode( $capsule_quantity ); ?>;
		jQuery( '#input_56_2' ).change( function() {
			var selected = jQuery( '#input_56_2' ).attr( 'value' );
			jQuery( '#field_56_3' ).html( html_content[selected] );
			jQuery( '#input_56_20' ).val( machine_deposit[selected] );
			jQuery.each( capsule_flavour, function( i, val ) {
				if ( val == "Milk" ) {
					jQuery( '#Price_' + val ).html( Number( milk_price[selected] ).toFixed(2) );
				} else {
					jQuery( '#Price_' + val ).html( Number( capsule_price[selected] ).toFixed(2) );
				}
				jQuery( '#Quantity_' + val ).val( 0 );
				jQuery( '#Box_' + val ).html( "" );
				jQuery( '#Total_' + val ).html( "" );
				if ( val == "Milk" ) {
					jQuery( '#Addon_Price_' + val ).html( Number( milk_price[selected] ).toFixed(2) );
				} else {
					jQuery( '#Addon_Price_' + val ).html( Number( capsule_price[selected] ).toFixed(2) );
				}
				jQuery( '#Addon_Quantity_' + val ).val( 0 );
				jQuery( '#Addon_Box_' + val ).html( "" );
				jQuery( '#Addon_Total_' + val ).html( "" );
			} );
			jQuery( '#Total_Quantity' ).html( "" );
			jQuery( '#Total_Box' ).html( "" );
			jQuery( '#Grand_Total' ).html( "" );
			jQuery( '#input_56_11' ).val( "" );
			jQuery( '#input_56_12' ).val( "" );
			var frequency = Number( jQuery( '#input_56_10 input:radio:checked' ).val() );
			if ( isNaN( frequency ) ) {
				frequency = 1;
			}
			jQuery( '#input_56_13' ).val( capsule_commitment[selected] * frequency );
			var ndd = Number( jQuery( '#input_56_9 input:radio:checked' ).val() );
			if ( isNaN( ndd ) ) {
				ndd = 1;
			}
			jQuery( '#input_56_86' ).val( capsule_commitment[selected] * ndd );
			var todayDate = new Date();
			var commDate = new Date();
			commDate.setDate( todayDate.getDate() + ( ndd * 30 ) );
			jQuery( '#ndd' ).html( jQuery( "#input_56_27" ).val() );
			jQuery( '#input_56_14' ).val( 0 );
			jQuery( '#input_56_29' ).val( "" );
			jQuery( '#Addon_Total_Quantity' ).html( "" );
			jQuery( '#Addon_Total_Box' ).html( "" );
			jQuery( '#Addon_Grand_Total' ).html( "" );
			jQuery( '#input_56_23' ).val( "" );
			jQuery( '#Bill_Amount' ).html( "" );
			if ( jQuery( '#gform_target_page_number_56' ).val() == 2 ) {
				jQuery( '#input_56_39' ).val( "" );
			}
			jQuery( '#input_56_79' ).val( 0 );
			if ( selected == 3 ) {
				jQuery( '#field_56_30' ).show();
			} else {
				jQuery( '#field_56_30' ).hide();
			}
		} );
		if ( jQuery( '#input_56_2' ).attr( 'value' ) != 0 ) {
			jQuery( '#input_56_2' ).trigger( 'change' );
		}
		function monthlyDelivery() {
			var frequency = Number( jQuery( '#input_56_10 input:radio:checked' ).val() );
			if ( isNaN( frequency ) ) {
				frequency = 1;
			}
			var delivery = 0;
			var state = jQuery( '#input_56_65_4' ).attr( 'value' );
			if ( Number( jQuery( '#Total_Quantity' ).html() ) < 40 ) {
				delivery = 10;
			}
			jQuery( '#input_56_11' ).val( delivery.toFixed(2) );
			var total = Number( jQuery( '#Grand_Total' ).html() ) + frequency + delivery;
			jQuery( '#input_56_12' ).val( total.toFixed(2) );
			jQuery( '#input_56_29' ).val( total.toFixed(2) );
		}
		function initialDelivery() {
			var frequency = Number( jQuery( '#input_56_10 input:radio:checked' ).val() );
			if ( isNaN( frequency ) ) {
				frequency = 1;
			}
			var delivery = 0;
			if ( jQuery( '#choice_56_21_1' ).is( ':checked' ) ) {
				jQuery( '#choice_56_21_2' ).prop( "checked", false );
				jQuery( '#field_56_85' ).show();
				jQuery( '#field_56_22' ).hide();
			} else {
				jQuery( '#choice_56_21_2' ).prop( "checked", true );
				var state = jQuery( '#input_56_65_4' ).attr( 'value' );
				delivery = 20;
				if ( Number( jQuery( '#Addon_Total_Quantity' ).html() ) < 40 ) {
					delivery += 10;
				}
				jQuery( '#field_56_85' ).hide();
				jQuery( '#field_56_22' ).show();
			}
			jQuery( '#input_56_22' ).val( delivery.toFixed(2) );
			frequency = Number( jQuery( '#input_56_19' ).val() );
			var total = Number( jQuery( '#Addon_Grand_Total' ).html() ) + frequency + Number( jQuery( '#input_56_20' ).val() ) + delivery;
			jQuery( '#input_56_23' ).val( total.toFixed(2) );
			jQuery( '#Bill_Amount' ).html( "RM " + total.toFixed(2) );
			if ( jQuery( '#gform_target_page_number_56' ).val() == 2 ) {
				jQuery( '#input_56_39' ).val( total.toFixed(2) );
			}
		}
<?php
	$y = 0;
	foreach( $capsule_flavour as $value ) {
?>
		jQuery( '#Quantity_<?php echo $value; ?>' ).change( function() {
			var quantity = Number( jQuery( '#Quantity_<?php echo $value; ?>' ).attr( 'value' ) );
			var interval = Number( capsule_quantity[<?php echo $y; ?>] );
			var selected = jQuery( '#input_56_2' ).attr( 'value' );
<?php
		if ( $value == "Milk" ) {
?>
			var price = Number( milk_price[selected] );
<?php
		} else {
?>
			var price = Number( capsule_price[selected] );
<?php
		}
?>
			jQuery( '#Box_<?php echo $value; ?>' ).html( quantity / interval );
			jQuery( '#Total_<?php echo $value; ?>' ).html( ( quantity * price ).toFixed(2) );
			var totalQuantity = 0, totalBox = 0, grandTotal = 0;
			jQuery.each( capsule_flavour, function( i, val ) {
				var quan = Number( jQuery( '#Quantity_' + val ).attr( 'value' ) );
				totalQuantity += quan;
				var box = Number( jQuery( '#Box_' + val ).html() );
				totalBox += box;
				if ( val == "Milk" ) {
					grandTotal += Number( jQuery( '#Quantity_' + val ).attr( 'value' ) ) * Number( milk_price[selected] );
				} else {
					grandTotal += Number( jQuery( '#Quantity_' + val ).attr( 'value' ) ) * Number( capsule_price[selected] );
				}
			} );
			jQuery( '#Total_Quantity' ).html( totalQuantity );
			jQuery( '#Total_Box' ).html( totalBox );
			jQuery( '#Grand_Total' ).html( grandTotal.toFixed(2) );
			jQuery( '#input_56_14' ).val( totalQuantity );
			monthlyDelivery();
		} );
		jQuery( '#Addon_Quantity_<?php echo $value; ?>' ).change( function() {
			var quantity = Number( jQuery( '#Addon_Quantity_<?php echo $value; ?>' ).attr( 'value' ) );
			var interval = Number( capsule_quantity[<?php echo $y; ?>] );
			var selected = jQuery( '#input_56_2' ).attr( 'value' );
<?php
		if ( $value == "Milk" ) {
?>
			var price = Number( milk_price[selected] );
<?php
		} else {
?>
			var price = Number( capsule_price[selected] );
<?php
		}
?>
			jQuery( '#Addon_Box_<?php echo $value; ?>' ).html( quantity / interval );
			jQuery( '#Addon_Total_<?php echo $value; ?>' ).html( ( quantity * price ).toFixed(2) );
			var totalQuantity = 0, totalBox = 0, grandTotal = 0;
			jQuery.each( capsule_flavour, function( i, val ) {
				var quan = Number( jQuery( '#Addon_Quantity_' + val ).attr( 'value' ) );
				totalQuantity += quan;
				var box = Number( jQuery( '#Addon_Box_' + val ).html() );
				totalBox += box;
				if ( val == "Milk" ) {
					grandTotal += Number( jQuery( '#Addon_Quantity_' + val ).attr( 'value' ) ) * Number( milk_price[selected] );
				} else {
					grandTotal += Number( jQuery( '#Addon_Quantity_' + val ).attr( 'value' ) ) * Number( capsule_price[selected] );
				}
			} );
			jQuery( '#Addon_Total_Quantity' ).html( totalQuantity );
			jQuery( '#Addon_Total_Box' ).html( totalBox );
			jQuery( '#Addon_Grand_Total' ).html( grandTotal.toFixed(2) );
			jQuery( '#input_56_79' ).val( totalQuantity );
			initialDelivery();
		} );
<?php
		$y++;
	}
?>
		jQuery( '#input_56_10' ).change( function() {
			var frequency = Number( jQuery( '#input_56_10 input:radio:checked' ).val() );
			jQuery( '#input_56_8' ).val( frequency.toFixed(2) );
			monthlyDelivery();
			initialDelivery();
			var selected = jQuery( '#input_56_2' ).attr( 'value' );
			jQuery( '#input_56_13' ).val( capsule_commitment[selected] * frequency );
		} );
		jQuery( '#input_56_19' ).change( function() {
			initialDelivery();
		} );
		jQuery( '#input_56_9' ).change( function() {
			var ndd = Number( jQuery( '#input_56_9 input:radio:checked' ).val() );
			var selected = jQuery( '#input_56_2' ).attr( 'value' );
			jQuery( '#input_56_86' ).val( capsule_commitment[selected] * ndd );
			var todayDate = new Date();
			var commDate = new Date();
			commDate.setDate( todayDate.getDate() + ( ndd * 30 ) );
			jQuery( '#ndd' ).html( jQuery( "#input_56_27" ).val() );
		} );
		jQuery( '#choice_56_21_1' ).change( initialDelivery );
		jQuery( '#choice_56_21_2' ).change( function() {
			if ( jQuery( '#choice_56_21_2' ).is( ':checked' ) ) {
				jQuery( '#choice_56_21_1' ).prop( "checked", false );
			} else {
				jQuery( '#choice_56_21_1' ).prop( "checked", true );
			}
			initialDelivery();
		} );
		jQuery( '#input_56_85' ).change( initialDelivery );
		jQuery( '#input_56_32' ).change( function() {
			var val = jQuery( '#input_56_32' ).val( );
			if ( val.indexOf('5') == 0 ) {
				jQuery( '#choice_56_34_0' ).prop( "checked", true);
			} else {
				jQuery( '#choice_56_34_0' ).prop( "checked", false);
			}
			if ( val.indexOf('4') == 0 ) {
				jQuery( '#choice_56_34_1' ).prop( "checked", true);
			} else {
				jQuery( '#choice_56_34_1' ).prop( "checked", false);
			}
		} );
		jQuery( '#input_56_45' ).change( function() {
			var val = jQuery( '#input_56_45' ).val( );
			if ( val.indexOf('5') == 0 ) {
				jQuery( '#choice_56_47_0' ).prop( "checked", true);
			} else {
				jQuery( '#choice_56_47_0' ).prop( "checked", false);
			}
			if ( val.indexOf('4') == 0 ) {
				jQuery( '#choice_56_47_1' ).prop( "checked", true);
			} else {
				jQuery( '#choice_56_47_1' ).prop( "checked", false);
			}
		} );
		var flavour = jQuery( '#input_56_6' ).attr( 'value' );
		if ( flavour != '' ) {
			var selected_flavour = flavour.split( '|' );
			var selected_quantity = jQuery( '#input_56_7' ).attr( 'value' ).split( '|' );
			jQuery.each( selected_flavour, function( i, val ) {
				jQuery( '#Quantity_' + val ).val( selected_quantity[i] ).trigger( 'change' );
			} );
		}
		var flavour = jQuery( '#input_56_17' ).attr( 'value' );
		if ( flavour != '' ) {
			var selected_flavour = flavour.split( '|' );
			var selected_quantity = jQuery( '#input_56_18' ).attr( 'value' ).split( '|' );
			jQuery.each( selected_flavour, function( i, val ) {
				jQuery( '#Addon_Quantity_' + val ).val( selected_quantity[i] ).trigger( 'change' );
			} );
		}
		initialDelivery();
		
		if ( jQuery( '#input_56_2' ).attr( 'value' ) == 3 ) {
			jQuery( '#choice_56_30_1' ).prop( "checked", true );
			jQuery( '#choice_56_30_2' ).prop( "checked", false );
		}
		jQuery( '#choice_56_30_1' ).change( function() {
			if ( jQuery( '#choice_56_30_1' ).is( ':checked' ) ) {
				jQuery( '#choice_56_30_1' ).prop( "checked", true );
				jQuery( '#choice_56_30_2' ).prop( "checked", false );
				jQuery( '#field_56_31' ).show();
				jQuery( '#field_56_32' ).show();
				jQuery( '#field_56_33' ).show();
				jQuery( '#field_56_34' ).show();
				jQuery( '#field_56_74' ).show();
			}
		} );
		
		jQuery( '#choice_56_30_2' ).change( function() {
			if ( jQuery( '#choice_56_30_2' ).is( ':checked' ) ) {
				jQuery( '#choice_56_30_1' ).prop( "checked", false );
				jQuery( '#choice_56_30_2' ).prop( "checked", true );
				jQuery( '#field_56_31' ).hide();
				jQuery( '#field_56_32' ).hide();
				jQuery( '#field_56_33' ).hide();
				jQuery( '#field_56_34' ).hide();
				jQuery( '#field_56_74' ).hide();
			}
		} );
		jQuery( '#choice_56_30_1' ).trigger( 'change' );
		jQuery( '#choice_56_30_2' ).trigger( 'change' );
		
		jQuery( '#input_56_32' ).trigger( 'change' );
		
		jQuery( '#choice_56_40_1' ).change( function() {
			if ( jQuery( '#choice_56_40_1' ).is( ':checked' ) ) {
				//jQuery( '#input_56_39' ).prop( "readonly", "" );
				jQuery( '#choice_56_40_1' ).prop( "checked", true );
				jQuery( '#choice_56_40_2' ).prop( "checked", false );
				jQuery( '#choice_56_40_3' ).prop( "checked", false );
				jQuery( '#choice_56_40_4' ).prop( "checked", false );
				jQuery( '#choice_56_40_5' ).prop( "checked", false );
				jQuery( '#choice_56_40_6' ).prop( "checked", false );
				jQuery( '#choice_56_40_7' ).prop( "checked", false );
				jQuery( '#choice_56_40_8' ).prop( "checked", false );
				jQuery( '#choice_56_40_9' ).prop( "checked", false );
				jQuery( '#field_56_42' ).hide();
				jQuery( '#field_56_44' ).hide();
				jQuery( '#field_56_45' ).hide();
				jQuery( '#field_56_46' ).hide();
				jQuery( '#field_56_47' ).hide();
				jQuery( '#field_56_76' ).hide();
				jQuery( '#field_56_49' ).hide();
				jQuery( '#field_56_91' ).hide();
			}
		} );
		jQuery( '#choice_56_40_2' ).change( function() {
			if ( jQuery( '#choice_56_40_2' ).is( ':checked' ) ) {
				//jQuery( '#input_56_39' ).prop( "readonly", "" );
				jQuery( '#choice_56_40_1' ).prop( "checked", false );
				jQuery( '#choice_56_40_2' ).prop( "checked", true );
				jQuery( '#choice_56_40_3' ).prop( "checked", false );
				jQuery( '#choice_56_40_4' ).prop( "checked", false );
				jQuery( '#choice_56_40_5' ).prop( "checked", false );
				jQuery( '#choice_56_40_6' ).prop( "checked", false );
				jQuery( '#choice_56_40_7' ).prop( "checked", false );
				jQuery( '#choice_56_40_8' ).prop( "checked", false );
				jQuery( '#choice_56_40_9' ).prop( "checked", false );
				jQuery( '#field_56_42' ).show();
				jQuery( '#field_56_44' ).hide();
				jQuery( '#field_56_45' ).hide();
				jQuery( '#field_56_46' ).hide();
				jQuery( '#field_56_47' ).hide();
				jQuery( '#field_56_76' ).hide();
				jQuery( '#field_56_49' ).hide();
				jQuery( '#field_56_91' ).show();
			}
		} );
		jQuery( '#choice_56_40_3' ).change( function() {
			if ( jQuery( '#choice_56_40_3' ).is( ':checked' ) ) {
				//jQuery( '#input_56_39' ).prop( "readonly", "" );
				jQuery( '#choice_56_40_1' ).prop( "checked", false );
				jQuery( '#choice_56_40_2' ).prop( "checked", false );
				jQuery( '#choice_56_40_3' ).prop( "checked", true );
				jQuery( '#choice_56_40_4' ).prop( "checked", false );
				jQuery( '#choice_56_40_5' ).prop( "checked", false );
				jQuery( '#choice_56_40_6' ).prop( "checked", false );
				jQuery( '#choice_56_40_7' ).prop( "checked", false );
				jQuery( '#choice_56_40_8' ).prop( "checked", false );
				jQuery( '#choice_56_40_9' ).prop( "checked", false );
				jQuery( '#field_56_42' ).show();
				jQuery( '#field_56_44' ).hide();
				jQuery( '#field_56_45' ).hide();
				jQuery( '#field_56_46' ).hide();
				jQuery( '#field_56_47' ).hide();
				jQuery( '#field_56_76' ).hide();
				jQuery( '#field_56_49' ).hide();
				jQuery( '#field_56_91' ).show();
			}
		} );
		jQuery( '#choice_56_40_4' ).change( function() {
			if ( jQuery( '#choice_56_40_4' ).is( ':checked' ) ) {
				//jQuery( '#input_56_39' ).prop( "readonly", "" );
				jQuery( '#choice_56_40_1' ).prop( "checked", false );
				jQuery( '#choice_56_40_2' ).prop( "checked", false );
				jQuery( '#choice_56_40_3' ).prop( "checked", false );
				jQuery( '#choice_56_40_4' ).prop( "checked", true );
				jQuery( '#choice_56_40_5' ).prop( "checked", false );
				jQuery( '#choice_56_40_6' ).prop( "checked", false );
				jQuery( '#choice_56_40_7' ).prop( "checked", false );
				jQuery( '#choice_56_40_8' ).prop( "checked", false );
				jQuery( '#choice_56_40_9' ).prop( "checked", false );
				jQuery( '#field_56_42' ).show();
				jQuery( '#field_56_44' ).hide();
				jQuery( '#field_56_45' ).hide();
				jQuery( '#field_56_46' ).hide();
				jQuery( '#field_56_47' ).hide();
				jQuery( '#field_56_76' ).hide();
				jQuery( '#field_56_49' ).hide();
				jQuery( '#field_56_91' ).show();
			}
		} );
		jQuery( '#choice_56_40_5' ).change( function() {
			if ( jQuery( '#choice_56_40_5' ).is( ':checked' ) ) {
				//jQuery( '#input_56_39' ).prop( "readonly", "" );
				jQuery( '#choice_56_40_1' ).prop( "checked", false );
				jQuery( '#choice_56_40_2' ).prop( "checked", false );
				jQuery( '#choice_56_40_3' ).prop( "checked", false );
				jQuery( '#choice_56_40_4' ).prop( "checked", false );
				jQuery( '#choice_56_40_5' ).prop( "checked", true );
				jQuery( '#choice_56_40_6' ).prop( "checked", false );
				jQuery( '#choice_56_40_7' ).prop( "checked", false );
				jQuery( '#choice_56_40_8' ).prop( "checked", false );
				jQuery( '#choice_56_40_9' ).prop( "checked", false );
				jQuery( '#field_56_42' ).hide();
				jQuery( '#field_56_44' ).show();
				jQuery( '#field_56_45' ).show();
				jQuery( '#field_56_46' ).show();
				//jQuery( '#field_56_47' ).show();
				jQuery( '#field_56_47' ).hide();
				jQuery( '#field_56_76' ).hide();
				//jQuery( '#field_56_76' ).show();
				jQuery( '#field_56_49' ).show();
				jQuery( '#field_56_91' ).show();
			}
		} );
		jQuery( '#choice_56_40_6' ).change( function() {
			if ( jQuery( '#choice_56_40_6' ).is( ':checked' ) ) {
				//jQuery( '#input_56_39' ).prop( "readonly", "" );
				jQuery( '#choice_56_40_1' ).prop( "checked", false );
				jQuery( '#choice_56_40_2' ).prop( "checked", false );
				jQuery( '#choice_56_40_3' ).prop( "checked", false );
				jQuery( '#choice_56_40_4' ).prop( "checked", false );
				jQuery( '#choice_56_40_5' ).prop( "checked", false );
				jQuery( '#choice_56_40_6' ).prop( "checked", true );
				jQuery( '#choice_56_40_7' ).prop( "checked", false );
				jQuery( '#choice_56_40_8' ).prop( "checked", false );
				jQuery( '#choice_56_40_9' ).prop( "checked", false );
				jQuery( '#field_56_42' ).hide();
				jQuery( '#field_56_44' ).show();
				jQuery( '#field_56_45' ).show();
				jQuery( '#field_56_46' ).show();
				//jQuery( '#field_56_47' ).show();
				jQuery( '#field_56_47' ).hide();
				jQuery( '#field_56_76' ).hide();
				//jQuery( '#field_56_76' ).show();
				jQuery( '#field_56_49' ).show();
				jQuery( '#field_56_91' ).show();
			}
		} );
		jQuery( '#choice_56_40_7' ).change( function() {
			if ( jQuery( '#choice_56_40_7' ).is( ':checked' ) ) {
				//jQuery( '#input_56_39' ).prop( "readonly", "" );
				jQuery( '#choice_56_40_1' ).prop( "checked", false );
				jQuery( '#choice_56_40_2' ).prop( "checked", false );
				jQuery( '#choice_56_40_3' ).prop( "checked", false );
				jQuery( '#choice_56_40_4' ).prop( "checked", false );
				jQuery( '#choice_56_40_5' ).prop( "checked", false );
				jQuery( '#choice_56_40_6' ).prop( "checked", false );
				jQuery( '#choice_56_40_7' ).prop( "checked", true );
				jQuery( '#choice_56_40_8' ).prop( "checked", false );
				jQuery( '#choice_56_40_9' ).prop( "checked", false );
				jQuery( '#field_56_42' ).hide();
				jQuery( '#field_56_44' ).hide();
				jQuery( '#field_56_45' ).hide();
				jQuery( '#field_56_46' ).hide();
				jQuery( '#field_56_47' ).hide();
				jQuery( '#field_56_76' ).hide();
				jQuery( '#field_56_49' ).hide();
				jQuery( '#field_56_91' ).hide();
			}
		} );
		jQuery( '#choice_56_40_1' ).trigger( 'change' );
		jQuery( '#choice_56_40_2' ).trigger( 'change' );
		jQuery( '#choice_56_40_3' ).trigger( 'change' );
		jQuery( '#choice_56_40_4' ).trigger( 'change' );
		jQuery( '#choice_56_40_5' ).trigger( 'change' );
		jQuery( '#choice_56_40_6' ).trigger( 'change' );
		jQuery( '#choice_56_40_7' ).trigger( 'change' );
		
		jQuery( '#input_56_45' ).trigger( 'change' );
		
		jQuery( '#gform_56' ).submit( function( event ) {
			if ( jQuery( '#gform_target_page_number_56' ).val() == 2 ) {
				var monthlyCapsule = "", monthlyQuantity = "", addonCapsule = "", addonQuantity = "";
<?php
	foreach( $capsule_flavour as $value ) {
?>
				if ( Number( jQuery( '#Quantity_<?php echo $value; ?>' ).attr( 'value' ) ) > 0 ) {
					monthlyCapsule += "<?php echo $value; ?>|";
					monthlyQuantity += jQuery( '#Quantity_<?php echo $value; ?>' ).attr( 'value' ) + "|";
				}
				if ( Number( jQuery( '#Addon_Quantity_<?php echo $value; ?>' ).attr( 'value' ) ) > 0 ) {
					addonCapsule += "<?php echo $value; ?>|";
					addonQuantity += jQuery( '#Addon_Quantity_<?php echo $value; ?>' ).attr( 'value' ) + "|";
				}
<?php
	}
?>
				jQuery( '#input_56_6' ).val( monthlyCapsule );
				jQuery( '#input_56_7' ).val( monthlyQuantity );
				jQuery( '#input_56_17' ).val( addonCapsule );
				jQuery( '#input_56_18' ).val( addonQuantity );
			}
		} );
	} );
	</script>
<?php
	return $form;
}

function validate_cc_number($cc_number) {
   //return value is card type if valid.
   $false = false;
   $card_type = "";
   $card_regexes = array(
      "/^4\d{12}(\d\d\d){0,1}$/" => "visa",
      "/^5[12345]\d{14}$/"       => "mastercard",
      "/^3[47]\d{13}$/"          => "amex",
      "/^6011\d{12}$/"           => "discover",
      "/^30[012345]\d{11}$/"     => "diners",
      "/^3[68]\d{12}$/"          => "diners",
   );
 
   foreach ($card_regexes as $regex => $type) {
       if (preg_match($regex, $cc_number)) {
           $card_type = $type;
           break;
       }
   }
 
   if (!$card_type) {
       return $false;
   }
 
   //mod 10 checksum algorithm
   $revcode = strrev($cc_number);
   $checksum = 0; 
 
   for ($i = 0; $i < strlen($revcode); $i++) {
       $current_num = intval($revcode[$i]);  
       if($i & 1) {  //Odd  position
          $current_num *= 2;
       }
       //Split digits and add.
           $checksum += $current_num % 10; if
       ($current_num >  9) {
           $checksum += 1;
       }
   }
 
   if ($checksum % 10 == 0) {
       return $card_type;
   } else {
       return $false;
   }
}

add_filter('gform_validation_56', 'validate_subscription_order_form' );
function validate_subscription_order_form( $validation_result ) {
	$form = $validation_result["form"];
	$current_page = rgpost( 'gform_source_page_number_' . $form['id'] ) ? rgpost( 'gform_source_page_number_' . $form['id'] ) : 1;
	if ( rgpost( "input_2" ) == 0 ) {
		$validation_result['is_valid'] = false;
		$form['fields'][1]->failed_validation = true;
		$form['fields'][1]->validation_message = "Please select a plan.";
	} else if ( (int)rgpost( "input_13" ) > (int)rgpost( "input_14" ) ) {
		$validation_result['is_valid'] = false;
		$form['fields'][1]->failed_validation = true;
		$form['fields'][1]->validation_message = "Please select at least " . rgpost( "input_13" ) . " capsules for Standard Order.";
	} else if ( (int)rgpost( "input_79" ) < 20 ) {
		$validation_result['is_valid'] = false;
		$form['fields'][1]->failed_validation = true;
		$form['fields'][1]->validation_message = "Please select at least 20 capsules for Initial Order.";
	}
	if ( rgpost( "input_21_1" ) == "yes" && rgpost( "input_85" ) == "" ) {
		$validation_result['is_valid'] = false;
		$form['fields'][21]->failed_validation = true;
		$form['fields'][21]->validation_message = "Please select pick-up location.";
	}
	$selection = 0;
	if ( rgpost( "input_30_1" ) != "" ) {
		$selection++;
	}
	if ( rgpost( "input_30_2" ) != "" ) {
		$selection++;
	}
	if ( $selection > 1 ) {
		$validation_result['is_valid'] = false;
		$form['fields'][31]->failed_validation = true;
		$form['fields'][31]->validation_message = "Please select only one payment type!!!";
	}
	if ( $current_page == 2 ) {
		if ( rgpost( "input_27" ) != "" ) {
			$comm_date = explode( '/', rgpost( "input_27" ) );
			$day = $comm_date[0];
			$month = $comm_date[1];
			$year = $comm_date[2];
			if ( strtotime( date( "Y-m-d" ) ) >= strtotime( $year . "-" . $month . "-" . $day ) ) {
				$validation_result['is_valid'] = false;
				$form['fields'][28]->failed_validation = true;
				$form['fields'][28]->validation_message = "Invalid Date!";
			}
		}
		if ( rgpost( "input_30_1" ) != "" ) {
			if ( rgpost( "input_31" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][32]->failed_validation = true;
				$form['fields'][32]->validation_message = "CardHolder's Name is required!";
			}
			if ( rgpost( "input_32" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][33]->failed_validation = true;
				$form['fields'][33]->validation_message = "Credit Card/Debit card No. is required!";
			} else if ( !ctype_digit( str_replace( "-", "", rgpost( "input_32" ) ) ) ) {
				$validation_result['is_valid'] = false;
				$form['fields'][33]->failed_validation = true;
				$form['fields'][33]->validation_message = "Invalid Card Number .";
			} else if ( ( strpos( rgpost( "input_32" ), "4" ) === false || strpos( rgpost( "input_32" ), "4" ) <> 0 ) 
				&& ( strpos( rgpost( "input_32" ), "5" ) === false || strpos( rgpost( "input_32" ), "5" ) <> 0 ) ) {
				$validation_result['is_valid'] = false;
				$form['fields'][33]->failed_validation = true;
				$form['fields'][33]->validation_message = "Invalid Card Number ..";
			} else if ( !validate_cc_number( str_replace( "-", "", rgpost( "input_32" ) ) ) ) {
				$validation_result['is_valid'] = false;
				$form['fields'][33]->failed_validation = true;
				$form['fields'][33]->validation_message = "Invalid Card Number ...";
			}
			if ( rgpost( "input_33" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][34]->failed_validation = true;
				$form['fields'][34]->validation_message = "Card expiry date is required!";
			} else {
				$month = "";
				$year = "";
				$expiry = explode( '-', rgpost( "input_33" ) );
				if ( count( $expiry ) > 0 ) {
					$month = $expiry[0];
				}
				if ( count( $expiry ) > 1 ) {
					$year = $expiry[1];
				}
				if ( strtotime( date( "Y-m-d" ) ) >= strtotime( "20" . $year . "-" . $month . "-01" ) ) {
					$validation_result['is_valid'] = false;
					$form['fields'][34]->failed_validation = true;
					$form['fields'][34]->validation_message = "Invalid Card.";
				}
			}
			if ( rgpost( "input_34" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][35]->failed_validation = true;
				$form['fields'][35]->validation_message = "Card Type is required!";
			}
			if ( rgpost( "input_74" ) == ""  || strpos( rgpost( "input_74" ), "please" ) != ""  || strpos( rgpost( "input_74" ), "select" ) != ""  || strlen( rgpost( "input_74" ) ) > 6 ) {
				$validation_result['is_valid'] = false;
				$form['fields'][36]->failed_validation = true;
				$form['fields'][36]->validation_message = "Issuing Bank is required!";
			}
		}
		if ( !is_numeric( rgpost( "input_39" ) ) || rgpost( "input_39" ) < rgpost( "input_23" ) ) {
			$validation_result['is_valid'] = false;
			$form['fields'][42]->failed_validation = true;
			$form['fields'][42]->validation_message = "Invalid amount!";
		}
		$selection = 0;
		if ( rgpost( "input_40_1" ) != "" ) {
			$selection++;
		}
		if ( rgpost( "input_40_2" ) != "" ) {
			$selection++;
		}
		if ( rgpost( "input_40_3" ) != "" ) {
			$selection++;
		}
		if ( rgpost( "input_40_4" ) != "" ) {
			$selection++;
		}
		if ( rgpost( "input_40_5" ) != "" ) {
			$selection++;
		}
		if ( rgpost( "input_40_6" ) != "" ) {
			$selection++;
		}
		if ( $selection > 1 ) {
			$validation_result['is_valid'] = false;
			$form['fields'][43]->failed_validation = true;
			$form['fields'][43]->validation_message = "Please select only one payment type!!!";
		}
		if ( rgpost( "input_40_2" ) != "" ) {
			if ( rgpost( "input_42" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][45]->failed_validation = true;
				$form['fields'][45]->validation_message = "Reference No. is required!";
			}
			if ( rgpost( "input_91" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][54]->failed_validation = true;
				$form['fields'][54]->validation_message = "Receipt Date is required.";
			}
		}
		if ( rgpost( "input_40_3" ) != "" || rgpost( "input_40_4" ) != "" ) {
			if ( rgpost( "input_42" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][45]->failed_validation = true;
				$form['fields'][45]->validation_message = "Reference No. is required!";
			}
		}
		if ( rgpost( "input_40_5" ) != "" || rgpost( "input_40_6" ) != "" ) {
			if ( rgpost( "input_44" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][47]->failed_validation = true;
				$form['fields'][47]->validation_message = "CardHolder's Name is required.";
			}
			if ( rgpost( "input_45" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][48]->failed_validation = true;
				$form['fields'][48]->validation_message = "Credit Card/Debit card No. is required.";
			} else if ( !ctype_digit( str_replace( "-", "", rgpost( "input_45" ) ) ) ) {
				$validation_result['is_valid'] = false;
				$form['fields'][48]->failed_validation = true;
				$form['fields'][48]->validation_message = "Invalid Card Number!";
			} else if ( ( strpos( rgpost( "input_45" ), "4" ) === false || strpos( rgpost( "input_45" ), "4" ) <> 0 ) && ( strpos( rgpost( "input_45" ), "5" ) === false || strpos( rgpost( "input_45" ), "5" ) <> 0 ) ) {
				$validation_result['is_valid'] = false;
				$form['fields'][48]->failed_validation = true;
				$form['fields'][48]->validation_message = "Invalid Card Number !!";
			} else if ( !validate_cc_number( str_replace( "-", "", rgpost( "input_45" ) ) ) ) {
				$validation_result['is_valid'] = false;
				$form['fields'][48]->failed_validation = true;
				$form['fields'][48]->validation_message = "Invalid Card Number !!!";
			}
			if ( rgpost( "input_46" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][49]->failed_validation = true;
				$form['fields'][49]->validation_message = "Card expiry date is required.";
			} else {
				$month = "";
				$year = "";
				$expiry = explode( '-', rgpost( "input_46" ) );
				if ( count( $expiry ) > 0 ) {
					$month = $expiry[0];
				}
				if ( count( $expiry ) > 1 ) {
					$year = $expiry[1];
				}
				if ( strtotime( date( "Y-m-d" ) ) >= strtotime( "20" . $year . "-" . $month . "-01" ) ) {
					$validation_result['is_valid'] = false;
					$form['fields'][49]->failed_validation = true;
					$form['fields'][49]->validation_message = "Invalid Card!";
				}
			}
			if ( rgpost( "input_49" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][53]->failed_validation = true;
				$form['fields'][53]->validation_message = "Approval Code is required.";
			}
			if ( rgpost( "input_91" ) == "" ) {
				$validation_result['is_valid'] = false;
				$form['fields'][54]->failed_validation = true;
				$form['fields'][54]->validation_message = "Receipt Date is required.";
			}
		}
	}
	if ( rgpost( "input_87" ) != "" && !ctype_digit( rgpost( "input_87" ) ) ) {
		$validation_result['is_valid'] = false;
		$form['fields'][84]->failed_validation = true;
		$form['fields'][84]->validation_message = "Invalid SSO Form No.!";
	} else {
		$serial_no = rgpost( "input_87" );
		$serial_no = sprintf( '%07d', $serial_no );
		$serial_no = "SSO" . $serial_no;
		global $wpdb;
		$query = "SELECT * FROM ct_integration_subscription_order WHERE serial_no = '" . $serial_no . "' AND isDeleted = 0";
		$wpdb -> get_results( $query );
		if ( $wpdb -> num_rows > 0 ) {
			$validation_result['is_valid'] = false;
			$form['fields'][84]->failed_validation = true;
			$form['fields'][84]->validation_message = "'" . $serial_no . "' has been submitted.";
		}
	}
	$validation_result['form'] = $form;
	return $validation_result;
}

function encrypt($data) {
	$cipher = MCRYPT_RIJNDAEL_128;
	$mode = MCRYPT_MODE_CBC;
	
	//iv length should be 16 bytes
	$iv = "677262-TT-262776";
	
	$secret_key = "nep@d1771NMY";
	
	// Make sure the key length should be 16 bytes
	$key_len = strlen( $secret_key );
	if( $key_len < 16 ) {
		$addS = 16 - $key_len;
		for( $i = 0 ;$i < $addS; $i++ ) {
			$secret_key .= " ";
		}
	} else {
		$secret_key = substr( $secret_key, 0, 16 );
	}
	
	$td = mcrypt_module_open( $cipher, "", $mode, $iv );
	mcrypt_generic_init( $td, $secret_key, $iv );
	$cyper_text = mcrypt_generic( $td, $data );
	mcrypt_generic_deinit( $td );
	mcrypt_module_close( $td );
	
	return bin2hex( $cyper_text );
}

add_action( 'gform_pre_submission_56', 'pre_subscription_order_form' );
function pre_subscription_order_form( $form ) {
	$_POST['input_31'] = strtoupper( $_POST['input_31'] );
	$_POST['input_44'] = strtoupper( $_POST['input_44'] );
	$_POST['input_32'] = encrypt( $_POST['input_32'] );
	$_POST['input_45'] = encrypt( $_POST['input_45'] );
}

add_action( 'gform_after_submission_56', 'submit_subscription_order_form' );
function submit_subscription_order_form( $entry ) {
	$bill_code = $_REQUEST['bill_code'];
	
	$form_id = $entry["form_id"];
	$entry_id = $entry["id"];
	
	$serial_no = $entry[87];
	$serial_no = sprintf( '%07d', $serial_no );
	$serial_no = "SSO" . $serial_no;
	$param = $serial_no . "%7C";
	
	$plan_type = "";
	$plan_code = "";
	$deposit = "";
	$capsule_price = "";
	$milk_price = "";
	$ID = 0;
	if ( $entry[2] == 1 ) {
		$ID = 1;
	} else if ( $entry[2] == 2 ) {
		$ID = 2;
	} else if ( $entry[2] == 3 ) {
		$ID = 3;
	} else if ( $entry[2] == 4 ) {
		$ID = 4;
	} else if ( $entry[2] == 5 ) {
		$ID = 5;
	} else if ( $entry[2] == 6 ) {
		$ID = 6;
	} else if ( $entry[2] == 7 ) {
		$ID = 7;
	} else if ( $entry[2] == 8 ) {
		$ID = 8;
	} else if ( $entry[2] == 9 ) {
		$ID = 9;
	} else if ( $entry[2] == 10 ) {
		$ID = 10;
	} else if ( $entry[2] == 11 ) {
		$ID = 11;
	} else if ( $entry[2] == 12 ) {
		$ID = 12;
	} else if ( $entry[2] == 13 ) {
		$ID = 13;
	}
	$Monthly_Capsules_Commitment = 0;
	global $wpdb;
	$result = $wpdb->get_results( "SELECT * FROM ct_coffee_sharing_plan WHERE Status = 'A' and ID = " . $ID );
	foreach ( $result as $val ) {
		$plan_type = $val->Plan_Type;
		$plan_code = $val->Plan_Code;
		$deposit = $val->Machine_Deposit;
		$capsule_price = $val->Capsules_Price;
		$milk_price = $val->Milk_Capsules_Price;
		$Monthly_Capsules_Commitment = $val->Monthly_Capsules_Commitment;
		$param = $param . $val->Description . "%7C";
		$param = $param . $val->Machine_Monthly_Rental . "%7C";
		$param = $param . $deposit . "%7C";
		$param = $param . $val->Monthly_Capsules_Commitment . "%7C";
		$param = $param . $capsule_price . "%7C";
		$param = $param . $milk_price . "%7C";
	}
	
	$monthly_capsule = $entry[6];
	$monthly_quantity = $entry[7];
	$capsule_month = explode( "|", $monthly_capsule );
	$qty_month = explode( "|", $monthly_quantity );
	$c_qty_month = array_combine( $capsule_month, $qty_month );
	$amico = ( isset( $c_qty_month['Amico'] ) && !empty( $c_qty_month['Amico'] ) ) ? $c_qty_month['Amico'] : '0';
	$choco = ( isset( $c_qty_month['Choco'] ) && !empty( $c_qty_month['Choco'] ) ) ? $c_qty_month['Choco'] : '0';
	$inLove = ( isset( $c_qty_month['InLove'] ) && !empty( $c_qty_month['InLove'] ) ) ? $c_qty_month['InLove'] : '0';
	$lonely = ( isset( $c_qty_month['Lonely'] ) && !empty( $c_qty_month['Lonely'] ) ) ? $c_qty_month['Lonely'] : '0';
	$luna = ( isset( $c_qty_month['Luna'] ) && !empty( $c_qty_month['Luna'] ) ) ? $c_qty_month['Luna'] : '0';
	$moonLight = ( isset( $c_qty_month['MoonLight'] ) && !empty( $c_qty_month['MoonLight'] ) ) ? $c_qty_month['MoonLight'] : '0';
	$passion =( isset( $c_qty_month['Passion'] ) && !empty( $c_qty_month['Passion'] ) ) ? $c_qty_month['Passion'] : '0';
	$peace =( isset( $c_qty_month['Peace'] ) && !empty( $c_qty_month['Peace'] ) ) ? $c_qty_month['Peace'] : '0';
	$sunrise =( isset( $c_qty_month['Sunrise'] ) && !empty( $c_qty_month['Sunrise'] ) ) ? $c_qty_month['Sunrise'] : '0';
	$milk =( isset( $c_qty_month['Milk'] ) && !empty( $c_qty_month['Milk'] )) ? $c_qty_month['Milk'] : '0';
	$king = ( isset( $c_qty_month['King'] ) && !empty( $c_qty_month['King'] ) ) ? $c_qty_month['King'] : '0';
	$queen = ( isset( $c_qty_month['Queen'] ) && !empty( $c_qty_month['Queen'] ) ) ? $c_qty_month['Queen'] : '0';
	$earl = ( isset( $c_qty_month['Earl'] ) && !empty( $c_qty_month['Earl'] ) ) ? $c_qty_month['Earl'] : '0';
	$prince = ( isset( $c_qty_month['Prince'] ) && !empty( $c_qty_month['Prince'] ) ) ? $c_qty_month['Prince'] : '0';
	$princess = ( isset( $c_qty_month['Princess'] ) && !empty( $c_qty_month['Princess'] ) ) ? $c_qty_month['Princess'] : '0';
	$total_quantity = $entry[14];
	$capsule_interval = array();
	$result = $wpdb->get_results( "SELECT * FROM ct_coffee_sharing_capsule" );
	foreach ( $result as $val ) {
		$capsule_interval[ $val->Name ] = $val->Quantity_Per_Unit;
	}
	$monthly_box = "";
	foreach ( $capsule_month as $val ) {
		$monthly_box = $monthly_box . $capsule_interval[$val] . "%7C";
	}
	$monthly_delivery = $entry[11];
	$delivery_frequency = $entry[10];
	$commencement_date = $entry[27];
	$monthly_order_total = $entry[12];
	
	$initial_capsule = $entry[17];
	$initial_quantity = $entry[18];
	$capsule_ini = explode( "|", $initial_capsule );
	$qty_ini = explode( "|", $initial_quantity );
	$c_qty_ini = array_combine( $capsule_ini, $qty_ini );
	$amico_ini = ( isset( $c_qty_ini['Amico'] ) && !empty( $c_qty_ini['Amico'] ) ) ? $c_qty_ini['Amico'] : '0';
	$choco_ini = ( isset( $c_qty_ini['Choco'] ) && !empty( $c_qty_ini['Choco'] ) ) ? $c_qty_ini['Choco'] : '0';
	$inLove_ini = ( isset( $c_qty_ini['InLove'] ) && !empty( $c_qty_ini['InLove'] ) ) ? $c_qty_ini['InLove'] : '0';
	$lonely_ini = ( isset( $c_qty_ini['Lonely'] ) && !empty( $c_qty_ini['Lonely'] ) ) ? $c_qty_ini['Lonely'] : '0';
	$luna_ini = ( isset( $c_qty_ini['Luna'] ) && !empty( $c_qty_ini['Luna'] ) ) ? $c_qty_ini['Luna'] : '0';
	$moonLight_ini = ( isset( $c_qty_ini['MoonLight'] ) && !empty( $c_qty_ini['MoonLight'] ) ) ? $c_qty_ini['MoonLight'] : '0';
	$passion_ini = ( isset( $c_qty_ini['Passion'] ) && !empty( $c_qty_ini['Passion'] ) ) ? $c_qty_ini['Passion'] : '0';
	$peace_ini = ( isset( $c_qty_ini['Peace'] ) && !empty( $c_qty_ini['Peace'] ) ) ? $c_qty_ini['Peace'] : '0';
	$sunrise_ini = ( isset( $c_qty_ini['Sunrise'] ) && !empty( $c_qty_ini['Sunrise'] ) ) ? $c_qty_ini['Sunrise'] : '0';
	$milk_ini = ( isset( $c_qty_ini['Milk'] ) && !empty( $c_qty_ini['Milk'] ) ) ? $c_qty_ini['Milk'] : '0';
	$king_ini = ( isset( $c_qty_ini['King'] ) && !empty( $c_qty_ini['King'] ) ) ? $c_qty_ini['King'] : '0';
	$queen_ini = ( isset( $c_qty_ini['Queen'] ) && !empty( $c_qty_ini['Queen'] ) ) ? $c_qty_ini['Queen'] : '0';
	$earl_ini = ( isset( $c_qty_ini['Earl'] ) && !empty( $c_qty_ini['Earl'] ) ) ? $c_qty_ini['Earl'] : '0';
	$prince_ini = ( isset( $c_qty_ini['Prince'] ) && !empty( $c_qty_ini['Prince'] ) ) ? $c_qty_ini['Prince'] : '0';
	$princess_ini = ( isset( $c_qty_ini['Princess'] ) && !empty( $c_qty_ini['Princess'] ) ) ? $c_qty_ini['Princess'] : '0';
	$amico_ini_box = $amico_ini . "box" . $capsule_interval['Amico'];
	$choco_ini_box = $choco_ini . "box" . $capsule_interval['Choco'];
	$inLove_ini_box = $inLove_ini . "box" . $capsule_interval['InLove'];
	$lonely_ini_box = $lonely_ini . "box" . $capsule_interval['Lonely'];
	$luna_ini_box = $luna_ini . "box" . $capsule_interval['Luna'];
	$passion_ini_box = $passion_ini . "box" . $capsule_interval['Passion'];
	$sunrise_ini_box = $sunrise_ini . "box" . $capsule_interval['Sunrise'];
	$milk_ini_box = $milk_ini . "box" . $capsule_interval['Milk'];
	$king_ini_box = $king_ini . "box" . $capsule_interval['King'];
	$queen_ini_box = $queen_ini . "box" . $capsule_interval['Queen'];
	$earl_ini_box = $earl_ini . "box" . $capsule_interval['Earl'];
	$prince_ini_box = $prince_ini . "box" . $capsule_interval['Prince'];
	$princess_ini_box = $princess_ini . "box" . $capsule_interval['Princess'];
	$initial_box = "";
	foreach ( $capsule_ini as $val ) {
		$initial_box = $initial_box . $capsule_interval[$val] . "%7C";
	}
	$quantity = $entry[19];
	$initial_deposit = $entry[20];
	$delivery_mode = "Delivery";
	$initial_delivery = $entry[22];
	$tranloc = "HK1";
	if ( $initial_delivery != "0.00" ) {
		$delivery_mode = "NotFree" . $initial_delivery;
	}
	if ( $_POST['input_21_1'] == "yes" ) {
		$delivery_mode = "Pick up";
		$tranloc = $entry[85];
		$initial_delivery = "0.00";
	}
	$initial_order_total = $entry[23];
	
	$cc_name = "";
	$cc_no = "";
	$cc_expiry = "";
	$cc_type = "";
	$cc_bank = "";
	if ( $_POST['input_30_1'] == "CreditCard" ) {
		$cc_name = $entry[31];
		$cc_no = $entry[32];
		$cc_expiry = $entry[33];
		$cc_type = $entry[34];
		$cc_bank = $entry[74];
	}
	if ( $_POST['input_30_2'] == "NO_RPS" ) {
		$cc_name = "NO_RPS";
	}
	$initial_pay_total = $entry[39];
	$reference_no = "";
	$initial_cc_name = "";
	$initial_cc_no = "";
	$initial_cc_expiry = "";
	$initial_cc_type = "";
	$initial_cc_bank = "";
	$initial_cc_code = "";
	$receipt_date = "";
	$pay_type = "";
	if ( $_POST['input_40_1'] == "CS" ) {
		$pay_type = "CS";
	}
	if ( $_POST['input_40_2'] == "CQSGD" ) {
		$pay_type = "CQSGD";
		$reference_no = $entry[42];
		$receipt_date = $entry[91];
	}
	if ( $_POST['input_40_3'] == "NETS" ) {
		$pay_type = "NETS";
		$reference_no = $entry[42];
		$receipt_date = $entry[91];
	}
	if ( $_POST['input_40_4'] == "TT" ) {
		$pay_type = "TT";
		$reference_no = $entry[42];
		$receipt_date = $entry[91];
	}
	if ( $_POST['input_40_5'] == "CCMS" ) {
		$pay_type = "CCMS";
		$initial_cc_name = $entry[44];
		$initial_cc_no = $entry[45];
		$initial_cc_expiry = $entry[46];
		$initial_cc_type = $entry[47];
		$initial_cc_bank = $entry[76];
		$initial_cc_code = $entry[49];
		$receipt_date = $entry[91];
	}
	if ( $_POST['input_40_6'] == "CCVS" ) {
		$pay_type = "CCVS";
		$initial_cc_name = $entry[44];
		$initial_cc_no = $entry[45];
		$initial_cc_expiry = $entry[46];
		$initial_cc_type = $entry[47];
		$initial_cc_bank = $entry[76];
		$initial_cc_code = $entry[49];
		$receipt_date = $entry[91];
	}
	if ( $_POST['input_40_7'] == "NO_OR" ) {
		$pay_type = "NO_OR";
	}
	
	$membership_no = $entry[52];
	$cust_name = $entry[53];
	$cust_nric = $entry[54];
	$cust_mobile = $entry[56];
	$cust_email = $entry[58];
	$cust_gender = $entry[88];
	$cust_race = $entry[89];
	$cust_address_1 = $_POST['input_64_1'];
	$cust_address_1 = str_replace( '#', '%23', $cust_address_1 );
	$cust_address_2 = $_POST['input_64_2'];
	$cust_address_2 = str_replace( '#', '%23', $cust_address_2 );
	$cust_address_city = $_POST['input_64_3'];
	$cust_address_city = str_replace( '#', '%23', $cust_address_city );
	$cust_address_postcode = $_POST['input_64_5'];
	$cust_address_postcode = str_replace( '#', '%23', $cust_address_postcode );
	$cust_address_state = $_POST['input_64_4'];
	$cust_address_state = str_replace( '#', '%23', $cust_address_state );
	$cust_delivery_name = $cust_name;
	$cust_delivery_phone = $cust_mobile;
	$cust_delivery_1 = $_POST['input_65_1'];
	$cust_delivery_1 = str_replace( '#', '%23', $cust_delivery_1 );
	$cust_delivery_2 = $_POST['input_65_2'];
	$cust_delivery_2 = str_replace( '#', '%23', $cust_delivery_2 );
	$cust_delivery_city = $_POST['input_65_3'];
	$cust_delivery_city = str_replace( '#', '%23', $cust_delivery_city );
	$cust_delivery_postcode = $_POST['input_65_5'];
	$cust_delivery_postcode = str_replace( '#', '%23', $cust_delivery_postcode );
	$cust_delivery_state = $_POST['input_65_4'];
	$cust_delivery_state = str_replace( '#', '%23', $cust_delivery_state );
	$sales_code = $entry[72];
	$sales_email = $entry[78];
	$param = $param . $cust_name . "%7C";
	$param = $param . $cust_nric . "%7C";
	$param = $param . $cust_mobile . "%7C";
	$param = $param . $cust_email . "%7C";
	$param = $param . $cust_address_1 . "%20" . $cust_address_2 . "%20" . $cust_address_city . "%20" . $cust_address_postcode . "%20" . $cust_address_state . "%7C";
	$param = $param . $cust_delivery_1 . "%20" . $cust_delivery_2 . "%20" . $cust_delivery_city . "%20" . $cust_delivery_postcode . "%20" . $cust_delivery_state . "%7C";
	$param = $param . $entry[71] . "%7C";
	$param = $param . $sales_code . "%7C";
	
	$monthly_capsule = str_replace( '|', '%7C', $monthly_capsule );
	$monthly_quantity = str_replace( '|', '%7C', $monthly_quantity );
	$initial_capsule = str_replace( '|', '%7C', $initial_capsule );
	$initial_quantity = str_replace( '|', '%7C', $initial_quantity );
	
	$enc1 = "";
	if ( $initial_cc_no != "" ) {
		$enc1 = $initial_cc_no;
	}
	$enc2 = "";
	if ( $cc_no != "" ) {
		$enc2 = $cc_no;
	}
	$user = wp_get_current_user();
	$usercode = get_user_meta( $user->ID, 'User_Code', true );
	$bill_code = get_user_meta( $user->ID, 'Bill_Code', true );
	$cc_no = substr( $cc_no, strlen( $cc_no ) - 4, strlen( $cc_no ) );
	$sp_param = $entry[90] . "|" . $initial_pay_total . "-" . $initial_order_total . "|" . $quantity . "|" . $deposit . "|" . $milk_ini_box . "|" . $choco_ini_box . "|" . $inLove_ini_box . "|" . $lonely_ini_box . "|" . $passion_ini_box . "|" . $sunrise_ini_box . "|" . $luna_ini_box . "|" . $amico_ini_box . "|" . $king_ini_box . "|" . $queen_ini_box . "|" . $prince_ini_box . "|" . $princess_ini_box . "|" . $earl_ini_box . "|" . $capsule_price . "|" . $milk_price . "|" . $pay_type . "|" . $initial_cc_name . "|" . str_replace( '-', '/', $initial_cc_expiry ) . "|" . $initial_cc_code . "|" . $reference_no . "|" . $receipt_date . "|" . $delivery_mode . "|" . $cust_delivery_1 . "|" . $cust_delivery_2 . "|" . $cust_delivery_city . "|" . $cust_delivery_state . "|" . $cust_delivery_postcode . "|" . $cust_name . "|" . $cust_nric . "|" . $cust_email . "|" . $cust_mobile . "|" . $cust_gender . "|" . $cust_race . "|" . $cust_address_1 . "|" . $cust_address_2 . "|" . $cust_address_city . "|" . $cust_address_state . "|" . $cust_address_postcode . "|" . $cust_delivery_name . "|" . $cust_delivery_phone . "|" . $cust_delivery_1 . "|" . $cust_delivery_2 . "|" . $cust_delivery_city . "|" . $cust_delivery_state . "|" . $cust_delivery_postcode . "|" . $sales_code . "|" . $plan_code . "|" . $serial_no . "|" . $entry[92] . "|" . $membership_no . "||" . $commencement_date . "|" . ( $monthly_order_total - $delivery_frequency - $monthly_delivery ) . "|" . $milk . "|" . $choco . "|" . $peace . "|" . $inLove . "|" . $lonely . "|" . $moonLight . "|" . $passion . "|" . $sunrise . "|" . $luna . "|" . $amico . "|" . $king . "|" . $queen . "|" . $prince . "|" . $princess . "|" . $earl . "|" . $delivery_frequency . "|1|" . $plan_type . "|1|1|1|" . $cc_bank . "|" . $cc_name . "|" . str_replace( '-', '/', $cc_expiry ) . "|" . $cc_type . "|" . $usercode . "|" . $tranloc . "|" . $bill_code . "|HK1|SIN|" . $Monthly_Capsules_Commitment . "|no";
	$sp_param = str_replace( '&', '%26', $sp_param );
	$url = "http://203.198.208.217:8081/ArisstoHK_Test/AdminSubscriptionOrder?sp_name=sp_ONLINE_GenSubscription&sp_param=" . $sp_param . "&enc1=" . $enc1 . "&enc2=" . $enc2;
	$url = str_replace( ' ', '%20', $url );
	$url = str_replace( '#', '%23', $url );
	echo $url;
	
	global $wpdb;
	$wpdb -> query( "INSERT INTO `ct_integration_subscription_order`(`form_id`,`call_Value`,`membership_no`,`serial_no`) VALUES(" . $form_id . ",'" . $url . "','" . $membership_no . "','" . $serial_no . "')" );
	$plan_id = 0;
	$result = $wpdb->get_results("SELECT * FROM ct_coffee_sharing_plan WHERE Status = 'A' and Plan_Code = '" . $plan_code . "'");
	foreach ( $result as $val ) {
		$plan_id = $val->ID;
	}
	$wpdb -> query ( "INSERT INTO `ct_coffee_sharing_subscription`(`Plan_ID`,`Subscription_No`,`Membership_No`,`Customer_Type`,`Subs_Status`,`Subs_DateFrom`,`Amico`,`Choco`,`InLove`,
	`Lonely`,`Luna`,`MoonLight`,`Passion`,`Peace`,`Sunrise`,`Milk`,`Char_Every`,`Char_Period`,`Delivery_Fees`,`TotalSubs_Amount`,`Sales_Partner_Code`) VALUES (" . $plan_id . ",'" . $serial_no . "','" . $membership_no . "','" . $plan_type . "','A','" . $commencement_date . "'," . $amico . "," . $choco . "," . $inLove . "," . $lonely . "," . $luna . "," . $moonLight . "," . $passion . "," . $peace . "," . $sunrise . "," . $milk . "," . $delivery_frequency . ",'Month','" . $monthly_delivery . "','" . $monthly_order_total . "','" . $sales_code . "')" );
	$wpdb -> query ( "INSERT INTO `ct_subscription_payment`(`Subscription_No`,`Payment_Type`,`Status`,`Start_Date`,`Name`,`Card_No`,`Card_Expiry`,`Card_Type`,`Issuing_Bank`) VALUES ('" . $serial_no . "','CC','A','" . $commencement_date . "','" . $cc_name . "','" . $cc_no . "','" . str_replace( "-", "", $cc_expiry ) . "','" . $cc_type . "','" . $cc_bank . "')" );
	$wpdb -> query ( "INSERT INTO `ct_coffee_sharing_order`(`Plan_ID`,`Membership_No`,`Subscription_No`,`Amico`,`Choco`,`InLove`,`Lonely`,`Luna`,`MoonLight`,`Passion`,`Peace`,`Sunrise`,
	`Milk`,`Delivery_Fees`,`Total_Amount`,`Sales_Partner_Code`,`Payment_Type`,`Payment_Status`) VALUES ('" . $plan_id . "','" . $membership_no . "','" . $serial_no . "'," . $amico_ini . "," . $choco_ini . "," . $inLove_ini . "," . $lonely_ini . "," . $luna_ini . "," . $moonLight_ini . "," . $passion_ini . "," . $peace_ini . "," . $sunrise_ini . "," . $milk_ini . ",'" . $initial_delivery . "','" . $initial_order_total . "','" . $sales_code . "','" . $pay_type . "','Pending')" );
	
	$curl = curl_init();
	curl_setopt ($curl, CURLOPT_URL, $url);
	curl_exec ($curl);
	curl_close ($curl);
}

add_shortcode( 'sales_partner_subscription_order_form', 'show_sales_partner_subscription_order_form' );
function show_sales_partner_subscription_order_form ( $atts ) {
	$ref = $_REQUEST['ref'];
	if ( isset( $ref ) && trim( $ref ) != "" ) {
		$user = get_user_by( 'id', $ref );
		$fullname = $user->display_name;
		$partnercode = get_user_meta( $user->ID, 'Partner_Code', true );
		if ( isset( $partnercode ) && trim( $partnercode ) != "" ) {
			$email = $user->user_email;
			$g_form = '[gravityform id=62 title=false description=false field_values="name=' . $fullname . '&code=' . $partnercode . '&email=' . $email . '&add=&partner_delivery_zone=&env=PRD"]';
			echo '<div align="right"><b><font color="#AB162B">SP_' . $partnercode . '</font></b></div>';
			echo do_shortcode( $g_form );
		} else {
			echo '<br/><br/>Invalid referral code. Please contact your referral...<br/><br/>';
		}
		//echo '<u><a href="/hk">Back to Home</a></u><br/>';
	} else {
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$fullname = $user->display_name;
		$partnercode = get_user_meta( $user->ID, 'Partner_Code', true );
		$add = "";
		$tmp = get_user_meta( $user->ID, 'shipping_address_1', true );
		if ( isset( $tmp ) && trim( $tmp ) != "" ) {
			$add = $tmp;
		}
		$tmp = get_user_meta( $user->ID, 'shipping_address_2', true );
		if ( isset( $tmp ) && trim( $tmp ) != "" ) {
			$add = $add . ", " . $tmp;
		}
		$tmp = get_user_meta( $user->ID, 'shipping_city', true );
		if ( isset( $tmp ) && trim( $tmp ) != "" ) {
			$add = $add . ", " . $tmp;
		}
		$tmp = get_user_meta( $user->ID, 'shipping_postcode', true );
		if ( isset( $tmp ) && trim( $tmp ) != "" ) {
			$add = $add . ", " . $tmp;
		}
		$partner_delivery_zone = get_user_meta( $user->ID, 'shipping_state', true );
		if ( isset( $partner_delivery_zone ) && trim( $partner_delivery_zone ) != "" ) {
			$add = $add . ", " . $partner_delivery_zone;
			$partner_delivery_zone = 'HK';
		} else {
			$partner_delivery_zone = '';
		}
		if ( isset( $partnercode ) && trim( $partnercode ) != "" ) {
			$email = $user->user_email;
			$g_form = '[gravityform id=62 title=false description=false field_values="name=' . $fullname . '&code=' . $partnercode . '&email=' . $email . '&add=' . $add . '&partner_delivery_zone=' . $partner_delivery_zone . '&env=PRD"]';
			echo do_shortcode( $g_form );
		} else {
			echo '<br/><br/>Partner Code not found. Please contact system administrator...<br/><br/>';
		}
	} else {
		//echo '<br/><br/>Please log in first...<br/><br/><u><a href="my-account">Login</a></u><br/><br/>';
$g_form = '[gravityform id=62 title=false description=false field_values="name=HK300001&code=HK300001&email=kf_lee@nepholdings.com.my&add=&partner_delivery_zone=&env=PRD"]';
echo do_shortcode( $g_form );
	}
	echo '<u><a href="/hk">Back to Home</a></u><br/>';
	echo '<u><a href="delivery-zone">New Order</a></u>';
	}
}

add_filter( 'gform_pre_render_62', 'populate_subscription_form' );
function populate_subscription_form( $form ) {
	$delivery_zone = $_REQUEST['delivery_zone'];
	$product_packages_details = "<table border='1' width='100%' cellpadding='5' style='border: #AB162B 1px solid'>
		<tr bgcolor='#F5EFEF'><td width='50%'><b>咖啡機月費 (HKD)</b></td><td width='50%'>{rental}</td></tr>
		<tr><td><b>咖啡機可退還按金 (HKD)</b></td><td>{deposit}</td></tr>
		<tr bgcolor='#F5EFEF'><td><b>每月Capsule訂購（粒）</b></td><td>{capsule}</td></tr>
		<tr><td><b>Capsule 單價 (HKD)</b></td><td>{price}</td></tr>
		<!--<tr bgcolor='#F5EFEF'><td><b>Milk Capsules Price (HKD)</b></td><td>{milk}</td></tr>
		<tr bgcolor='#F5EFEF'><td><b>Free Capsules</b></td><td>{other}</td></tr>-->
		<tr bgcolor='#F5EFEF'><td><b>運輸費1-7盒/每月(HKD)</b></td><td>{monthly1}</td></tr>
		<tr><td><b>運輸費8盒以上/每月 (HKD)</b></td><td>{monthly2}</td></tr>
		<tr bgcolor='#F5EFEF'><td><b>首購運輸費 (HKD)</b></td><td>{initial}</td></tr>
	</table>";
	$plan = array();
	$product_packages = array();
	$machine_deposit = array();
	$capsule_commitment = array();
	$capsule_price = array();
	$milk_price = array();
	array_push( $plan, array( 'value' => 0, 'text' => '請選擇' ) );
	$html = str_replace( '{rental}', '', $product_packages_details );
	$html = str_replace( '{deposit}', '', $html );
	$html = str_replace( '{capsule}', '', $html );
	$html = str_replace( '{price}', '', $html );
	$html = str_replace( '{milk}', '', $html );
	$html = str_replace( '{other}', '', $html );
	$html = str_replace( '{monthly1}', '', $html );
	$html = str_replace( '{monthly2}', '', $html );
	$html = str_replace( '{initial}', '', $html );
	array_push( $product_packages, $html );
	array_push( $machine_deposit, '' );
	array_push( $capsule_commitment, '' );
	array_push( $capsule_price, '' );
	array_push( $milk_price, '' );
	global $wpdb;
	$result = $wpdb->get_results( "SELECT * FROM ct_coffee_sharing_plan WHERE Status = 'A'" );
	$index = 1;
	foreach ( $result as $row ) {
		array_push( $plan, array( 'value' => $index, 'text' => $row->Description ) );
		$html = str_replace( '{rental}', number_format( (float)$row->Machine_Monthly_Rental, 2 ), $product_packages_details );
		$html = str_replace( '{deposit}', number_format( (float)$row->Machine_Deposit, 2 ), $html );
		$html = str_replace( '{capsule}', $row->Monthly_Capsules_Commitment, $html );
		$html = str_replace( '{price}', number_format( (float)$row->Capsules_Price, 2 ), $html );
		$html = str_replace( '{milk}', number_format( (float)$row->Milk_Capsules_Price, 2 ), $html );
		$html = str_replace( '{other}', $row->Remarks, $html );
		if ( $delivery_zone == 'District' ) {
			$html = str_replace( '{monthly1}', '130', $html );
			$html = str_replace( '{monthly2}', '80', $html );
			$html = str_replace( '{initial}', '200', $html );
		} else {
			$html = str_replace( '{monthly1}', '50', $html );
			$html = str_replace( '{monthly2}', '0', $html );
			$html = str_replace( '{initial}', '0', $html );
		}
		array_push( $product_packages, $html );
		array_push( $machine_deposit, $row->Machine_Deposit );
		array_push( $capsule_commitment, $row->Monthly_Capsules_Commitment );
		array_push( $capsule_price, $row->Capsules_Price );
		array_push( $milk_price, $row->Milk_Capsules_Price );
		$index++;
	}
	$form['fields'][1]['choices'] = $plan;
	$capsule_table = "<table border='1' width='100%' cellpadding='5' style='border: #AB162B 1px solid'><tr valign='top' style='border: #AB162B 1px solid'><td width='20%' align='center'><b>Capsule</b></td><td width='20%' align='center'><b>價格</b></td><td width='20%' align='center'><b>數量（粒）</b></td><td width='20%' align='center'><b>盒</b></td><td width='20%' align='center'><b>總額 (HKD)</b></td></tr>";
	$capsule_table_monthly = "請依照您所定的送貨量，選擇您喜愛的Capsule 口味。<br/>" . $capsule_table;
	$capsule_table_addon = $capsule_table;
	$capsule_flavour = array();
	$capsule_quantity = array();
	$free_item = "<option style='color:black;' value=''>Please Select</option>";
	$result = $wpdb->get_results( "SELECT * FROM ct_coffee_sharing_capsule WHERE Status IN ('A','I')" );
	$count = 1;
	foreach ( $result as $row ) {
		$id = $row->Name;
		$interval = $row->Quantity_Per_Unit;
		$option = "<option style='color:black;' value='0'>-</option>";
		$x = 1;
		while( $x <= 20 ) {
			$val = $x * $interval;
			$option = $option . "<option style='color:black;' value='" . $val . "'>" . $val . "</option>";
			$x++;
		}
		$row_bg_color = "#F5EFEF";
		if ( $count % 2 == 0 ) {
			$row_bg_color = "#FFFFFF";
		}
		$status = $row->Status;
		$capsule_table_monthly = $capsule_table_monthly . "<tr bgcolor='" . $row_bg_color . "'><td>" . $row->Description . "</td>";
		$capsule_table_monthly = $capsule_table_monthly . "<td><span id='Price_" . $id . "'></span></td>";
		$capsule_table_monthly = $capsule_table_monthly . "<td><select id='Quantity_" . $id . "' style='color:black;'>" . $option . "</select></td>";
		$capsule_table_monthly = $capsule_table_monthly . "<td><span id='Box_" . $id . "'></span></td>";
		$capsule_table_monthly = $capsule_table_monthly . "<td><span id='Total_" . $id . "'></span></td></tr>";
		if ( $status == 'A' ) {
		$capsule_table_addon = $capsule_table_addon . "<tr bgcolor='" . $row_bg_color . "'><td>" . $row->Description . "</td>";
		} else {
		$capsule_table_addon = $capsule_table_addon . "<tr bgcolor='" . $row_bg_color . "'><td>" . $row->Description . "<b> <font color='#FF0000'>(OUT OF STOCK)</font> <font color='#0000FF'>(PRE-ORDER)</font></b></td>";
		}
		$capsule_table_addon = $capsule_table_addon . "<td><span id='Addon_Price_" . $id . "'></span></td>";
		//if ( $status == 'A' ) {
		$capsule_table_addon = $capsule_table_addon . "<td><select id='Addon_Quantity_" . $id . "' style='color:black;'>" . $option . "</select></td>";
		$capsule_table_addon = $capsule_table_addon . "<td><span id='Addon_Box_" . $id . "'></span></td>";
		$capsule_table_addon = $capsule_table_addon . "<td><span id='Addon_Total_" . $id . "'></span></td></tr>";
		//if ( $id != 'Milk' ) {
		if ( $row->Product_Code != '' ) {
			$free_item = $free_item . "<option style='color:black;' value='" . $row->Product_Code . "'>" . $row->Description . "</option>";
		}
		//} else {
		//$capsule_table_addon = $capsule_table_addon . "<td colspan='3'><select id='Addon_Quantity_" . $id . "' style='display:none'><option value='0'>-</option></select></select>";
		//$capsule_table_addon = $capsule_table_addon . "<span id='Addon_Box_" . $id . "'></span>";
		//$capsule_table_addon = $capsule_table_addon . "<span id='Addon_Total_" . $id . "'></span>Out of Stock</td></tr>";
		//}
		array_push( $capsule_flavour, $id );
		array_push( $capsule_quantity, $interval );
		$count++;
	}
	$row_bg_color = "#F5EFEF";
	if ( $count % 2 == 0 ) {
		$row_bg_color = "#FFFFFF";
	}
	$capsule_table_monthly = $capsule_table_monthly . "<tr bgcolor='" . $row_bg_color . "' style='border: #AB162B 1px solid'><td><b>總共</b></td><td></td><td><b><span id='Total_Quantity'></span></b></td><td><b><span id='Total_Box'></span></b></td><td><b><span id='Grand_Total'></span></b></td></tr></table>";
	$capsule_table_addon = $capsule_table_addon . "<tr bgcolor='" . $row_bg_color . "' style='border: #AB162B 1px solid'><td><b>總共</b></td><td></td><td><b><span id='Addon_Total_Quantity'></span></b></td><td><b><span id='Addon_Total_Box'></span></b></td><td><b><span id='Addon_Grand_Total'></span></b></td></tr></table>";
	//$capsule_table_addon = $capsule_table_addon . "<p></p><table id='freeitemtbl' width='100%' cellpadding='10' style='border: #AB162B 1px solid'><tr><td>Select your favorite box capsule for FREE <font color='red'>*</font></td></tr><tr><td><select id='FreeItem' style='width: 200px'>" . $free_item . "</select></td></tr></table></td></tr></table>";
	
	$msia_state = array();
	$msia_state['JHR'] = 'JOHOR';
	$msia_state['KDH'] = 'KEDAH';
	$msia_state['KTN'] = 'KELANTAN';
	$msia_state['KUL'] = 'KUALA LUMPUR';
	$msia_state['LBN'] = 'LABUAN';
	$msia_state['MLK'] = 'MELAKA';
	$msia_state['NSN'] = 'NEGERI SEMBILAN';
	$msia_state['PHG'] = 'PAHANG';
	$msia_state['PNG'] = 'PENANG';
	$msia_state['PRK'] = 'PERAK';
	$msia_state['PLS'] = 'PERLIS';
	$msia_state['PJY'] = 'PUTRAJAYA';
	$msia_state['SBH'] = 'SABAH';
	$msia_state['SRW'] = 'SARAWAK';
	$msia_state['SGR'] = 'SELANGOR';
	$msia_state['TRG'] = 'TERENGGANU';
	
	$city_list = array();
	$result = $wpdb->get_results( "SELECT State, City from ct_postcode GROUP BY City, State" );
	foreach( $result as $val ) {
		$city_list[$msia_state[$val->State]][] =  $val->City;
	}
	$postcode_list = array();
	$result = $wpdb->get_results( "SELECT City,Postcode from ct_postcode GROUP BY Postcode, City" );
	foreach( $result as $val ) {
		$postcode_list[$val->City][] =  $val->Postcode;
	}
?>
	<script type="text/javascript">
	jQuery( document ).ready( function() {
		jQuery( "li.gf_readonly input" ).attr( "readonly", "readonly" );
		jQuery( "li.gf_readonly textarea" ).attr( "readonly", "readonly" );
		jQuery( "#input_62_27" ).attr( "class", "medium" );
		jQuery( ".gform_wrapper .gfield input[type='email'], .gform_wrapper .gfield input[type='number'], .gform_wrapper .gfield input[type='password'], .gform_wrapper .gfield input[type='password'] input[type='number'], .gform_wrapper .gfield input[type='tel'], .gform_wrapper .gfield input[type='text'], .gform_wrapper .gfield input[type='url'], .gform_wrapper .gfield select, .gform_wrapper .gfield textarea, .gform_wrapper .gfield_select[multiple=multiple], .input-text, input[type='email'], input[type='text'], select, textarea" ).css( "color", "black" );
		jQuery( "option" ).css( "color", "black" );
		jQuery( ".gsection_title" ).css( "color", "white" );
		jQuery( ".gsection" ).css( "background-color", "#AB162B" );
		jQuery( ".gsection" ).css( "padding", "8px" );
		jQuery( '#field_62_3' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_62_5' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_62_5' ).html( "<?php echo $capsule_table_monthly; ?>" );
		jQuery( '#field_62_16' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_62_16' ).html( "<?php echo $capsule_table_addon; ?>" );
		jQuery( '#field_62_26' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_62_38' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_62_67' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_62_75' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_62_81' ).removeClass( "gfield_html_formatted " );
		jQuery( '#field_62_83' ).removeClass( "gfield_html_formatted " );
		jQuery( '#label_62_21_3' ).html( jQuery( '#label_62_21_3' ).html() + " ( " + jQuery( '#input_62_106' ).val() + " ) " );
		jQuery( "#label_62_89_1" ).css( "font-size", "14px" );
		jQuery( "#label_62_89_1" ).css( "color", "#AB162B" );
		jQuery( '#input_62_32' ).prop( "autocomplete", "off" );
		var html_content = <?php echo json_encode( $product_packages ); ?>;
		var machine_deposit = <?php echo json_encode( $machine_deposit ); ?>;
		var capsule_flavour = <?php echo json_encode( $capsule_flavour ); ?>;
		var milk_price = <?php echo json_encode( $milk_price ); ?>;
		var capsule_price = <?php echo json_encode( $capsule_price ); ?>;
		var capsule_commitment = <?php echo json_encode( $capsule_commitment ); ?>;
		var capsule_quantity = <?php echo json_encode( $capsule_quantity ); ?>;
		var city_list = <?php echo json_encode( $city_list ) ?>;
		var postcode_list = <?php echo json_encode( $postcode_list ) ?>;
		function nextDeliveryDay() {
			var nextDeliveryMth = Number( jQuery( '#input_62_9 input:radio:checked' ).val() );
			if ( isNaN( nextDeliveryMth ) ) {
				nextDeliveryMth = 1;
			}
			jQuery( '#input_62_19' ).val( nextDeliveryMth.toFixed(2) );
			var selected = jQuery( '#input_62_2' ).val();
			jQuery( '#input_62_86' ).val( capsule_commitment[selected] * nextDeliveryMth );
			var todayDate = new Date();
			var commDate = new Date();
			//commDate.setDate( todayDate.getDate() + ( nextDeliveryMth * 31 ) );
			commDate.setMonth( todayDate.getMonth() + nextDeliveryMth );
			var formatted = jQuery.datepicker.formatDate( 'yy-mm-dd', commDate );
			var ymd = formatted.split( '-' );
			var nextDeliveryDay = jQuery( '#input_62_87 input:radio:checked' ).val();
			if ( nextDeliveryDay == "" ) {
				nextDeliveryDay = "05";
			}
			jQuery( "#input_62_27" ).val( ymd[0] + "-" + ymd[1] + "-" + nextDeliveryDay );
			//jQuery( '#ndd' ).html( jQuery( "#input_62_27" ).val() );
			jQuery( '#ndd' ).html( nextDeliveryDay + "/" + ymd[1] + "/" + ymd[0] );
			jQuery( '#comm_dt' ).html( nextDeliveryDay + "/" + ymd[1] + "/" + ymd[0] );
			jQuery( '#input_62_109' ).val( jQuery( "#input_62_27" ).val() );
		}
		jQuery( '#input_62_2' ).change( function() {
			var selected = jQuery( '#input_62_2' ).val();
			jQuery( '#field_62_3' ).html( html_content[selected] );
			jQuery( '#input_62_20' ).val( machine_deposit[selected] );
			jQuery.each( capsule_flavour, function( i, val ) {
				if ( val == "Milk" ) {
					jQuery( '#Price_' + val ).html( Number( milk_price[selected] ).toFixed(2) );
				} else {
					jQuery( '#Price_' + val ).html( Number( capsule_price[selected] ).toFixed(2) );
				}
				jQuery( '#Quantity_' + val ).val( 0 );
				jQuery( '#Box_' + val ).html( "" );
				jQuery( '#Total_' + val ).html( "" );
				if ( val == "Milk" ) {
					jQuery( '#Addon_Price_' + val ).html( Number( milk_price[selected] ).toFixed(2) );
				} else {
					jQuery( '#Addon_Price_' + val ).html( Number( capsule_price[selected] ).toFixed(2) );
				}
				jQuery( '#Addon_Quantity_' + val ).val( 0 );
				jQuery( '#Addon_Box_' + val ).html( "" );
				jQuery( '#Addon_Total_' + val ).html( "" );
			} );
			jQuery( '#Total_Quantity' ).html( "" );
			jQuery( '#Total_Box' ).html( "" );
			jQuery( '#Grand_Total' ).html( "" );
			jQuery( '#input_62_11' ).val( "" );
			jQuery( '#input_62_12' ).val( "" );
			var frequency = Number( jQuery( '#input_62_10 input:radio:checked' ).val() );
			if ( isNaN( frequency ) ) {
				frequency = 1;
			}
			jQuery( '#input_62_13' ).val( capsule_commitment[selected] * frequency );
			nextDeliveryDay();
			jQuery( '#input_62_14' ).val( 0 );
			jQuery( '#input_62_29' ).val( "" );
			jQuery( '#total_amt' ).html( "" );
			jQuery( '#Addon_Total_Quantity' ).html( "" );
			jQuery( '#Addon_Total_Box' ).html( "" );
			jQuery( '#Addon_Grand_Total' ).html( "" );
			//jQuery( '#input_62_22' ).val( "" );
			jQuery( '#input_62_23' ).val( "" );
			jQuery( '#input_62_79' ).val( 0 );
			jQuery( '#input_62_39' ).val( "" );
			if ( selected == 3 || selected == 4 ) {
				jQuery( '#freeitemtbl' ).show();
			} else {
				jQuery( '#FreeItem' ).val( "" )
				jQuery( '#input_62_105' ).val( "" );
				jQuery( '#freeitemtbl' ).hide();
			}
		} );
		if ( jQuery( '#input_62_2' ).val() != 0 ) {
			jQuery( '#input_62_2' ).trigger( 'change' );
		}
		function monthlyDelivery() {
			var frequency = Number( jQuery( '#input_62_10 input:radio:checked' ).val() );
			if ( isNaN( frequency ) ) {
				frequency = 1;
			}
			var delivery = 0;
<?php
	if ( $_REQUEST['delivery_zone'] == "District" ) {
?>
			delivery = 80;
<?php
	}
?>
			if ( Number( jQuery( '#Total_Quantity' ).html() ) < 80 ) {
				delivery += 50;
			}
			jQuery( '#input_62_11' ).val( delivery.toFixed(2) );
			var total = Number( jQuery( '#Grand_Total' ).html() ) + frequency + delivery;
			//jQuery( '#input_62_12' ).val( total.toFixed(2) );
			jQuery( '#input_62_12' ).val( total.toFixed(2) );//+ " / every " + frequency + " month(s)" );
			jQuery( '#input_62_29' ).val( total.toFixed(2) );
			//jQuery( '#total_amt' ).html( total.toFixed(2) );//+ "/every " + frequency + " month(s)" );
			var amt_per_mth = ( total - delivery ) / frequency;//total / frequency;
			jQuery( '#total_amt' ).html( amt_per_mth.toFixed(2) );
			jQuery( '#input_62_110' ).val( amt_per_mth.toFixed(2) );
			jQuery( '#monthly_amt' ).html( "每月支付咖啡訂購 HKD" + amt_per_mth.toFixed(2) );
			
			//if ( frequency == 1 ) {
				//jQuery( '#input_62_12' ).val( total.toFixed(2) );//+ " / every month" );
				//jQuery( '#total_amt' ).html( total.toFixed(2) );//+ "/every month" );
			//}
		}
		function initialDelivery() {
			var nextDeliveryMth = Number( jQuery( '#input_62_9 input:radio:checked' ).val() );
			if ( isNaN( nextDeliveryMth ) ) {
				nextDeliveryMth = 1;
			}
			var delivery = 0;
			if ( jQuery( '#choice_62_21_1' ).is( ':checked' ) ) {
				//jQuery( '#choice_62_21_2' ).prop( "checked", false );
				jQuery( '#field_62_85' ).show();
				//jQuery( '#field_62_22' ).hide();
			} else if ( jQuery( '#choice_62_21_3' ).is( ':checked' ) ) {
				if ( jQuery( '#input_62_107' ).val() == "" ) {
					jQuery( '#choice_62_21_2' ).prop( "checked", true );
					jQuery( '#choice_62_21_3' ).prop( "checked", false );
					alert ( "请从“會員中心”页面更新運送地址。" );
				} else {
					//if ( Number( jQuery( '#Addon_Total_Quantity' ).html() ) < 80 ) {
						//delivery = 50;
					//}
<?php
	if ( $_REQUEST['delivery_zone'] == "District" ) {
?>
					delivery = 200;
<?php
	}
?>
				}
				jQuery( '#field_62_85' ).hide();
				jQuery( '#field_62_22' ).show();
			} else {
				//jQuery( '#choice_62_21_2' ).prop( "checked", true );
				//if ( Number( jQuery( '#Addon_Total_Quantity' ).html() ) < 80 ) {
				//	delivery = 50;
				//}
<?php
	if ( $_REQUEST['delivery_zone'] == "District" ) {
?>
				delivery = 200;
<?php
	}
?>
				jQuery( '#field_62_85' ).hide();
				//jQuery( '#field_62_22' ).show();
			}
			jQuery( '#input_62_22' ).val( delivery.toFixed(2) );
			var total = Number( jQuery( '#Addon_Grand_Total' ).html() ) + nextDeliveryMth + Number( jQuery( '#input_62_20' ).val() ) + delivery;
			if ( jQuery( '#choice_62_108_1' ).is( ':checked' ) ) {
				total += 1;
			}
			jQuery( '#input_62_23' ).val( total.toFixed(2) );
			jQuery( '#input_62_39' ).val( total.toFixed(2) );
			jQuery( '#initial_amt' ).html( total.toFixed(2) );
		}
<?php
	$y = 0;
	foreach( $capsule_flavour as $value ) {
?>
		jQuery( '#Quantity_<?php echo $value; ?>' ).change( function() {
			var quantity = Number( jQuery( '#Quantity_<?php echo $value; ?>' ).val() );
			var interval = Number( capsule_quantity[<?php echo $y; ?>] );
			var selected = jQuery( '#input_62_2' ).val();
<?php
		if ( $value == "Milk" ) {
?>
			var price = Number( milk_price[selected] );
<?php
		} else {
?>
			var price = Number( capsule_price[selected] );
<?php
		}
?>
			jQuery( '#Box_<?php echo $value; ?>' ).html( quantity / interval );
			jQuery( '#Total_<?php echo $value; ?>' ).html( ( quantity * price ).toFixed(2) );
			var totalQuantity = 0, totalBox = 0, grandTotal = 0;
			jQuery.each( capsule_flavour, function( i, val ) {
				var quan = Number( jQuery( '#Quantity_' + val ).val() );
				totalQuantity += quan;
				var box = Number( jQuery( '#Box_' + val ).html() );
				totalBox += box;
				if ( val == "Milk" ) {
					grandTotal += Number( jQuery( '#Quantity_' + val ).val() ) * Number( milk_price[selected] );
				} else {
					grandTotal += Number( jQuery( '#Quantity_' + val ).val() ) * Number( capsule_price[selected] );
				}
			} );
			jQuery( '#Total_Quantity' ).html( totalQuantity );
			jQuery( '#Total_Box' ).html( totalBox );
			jQuery( '#Grand_Total' ).html( grandTotal.toFixed(2) );
			jQuery( '#input_62_14' ).val( totalQuantity );
			monthlyDelivery();
		} );
		jQuery( '#Addon_Quantity_<?php echo $value; ?>' ).change( function() {
			var quantity = Number( jQuery( '#Addon_Quantity_<?php echo $value; ?>' ).val() );
			var interval = Number( capsule_quantity[<?php echo $y; ?>] );
			var selected = jQuery( '#input_62_2' ).val();
<?php
		if ( $value == "Milk" ) {
?>
			var price = Number( milk_price[selected] );
<?php
		} else {
?>
			var price = Number( capsule_price[selected] );
<?php
		}
?>
			jQuery( '#Addon_Box_<?php echo $value; ?>' ).html( quantity / interval );
			jQuery( '#Addon_Total_<?php echo $value; ?>' ).html( ( quantity * price ).toFixed(2) );
			var totalQuantity = 0, totalBox = 0, grandTotal = 0;
			jQuery.each( capsule_flavour, function( i, val ) {
				var quan = Number( jQuery( '#Addon_Quantity_' + val ).val() );
				totalQuantity += quan;
				var box = Number( jQuery( '#Addon_Box_' + val ).html() );
				totalBox += box;
				if ( val == "Milk" ) {
					grandTotal += Number( jQuery( '#Addon_Quantity_' + val ).val() ) * Number( milk_price[selected] );
				} else {
					grandTotal += Number( jQuery( '#Addon_Quantity_' + val ).val() ) * Number( capsule_price[selected] );
				}
			} );
			jQuery( '#Addon_Total_Quantity' ).html( totalQuantity );
			jQuery( '#Addon_Total_Box' ).html( totalBox );
			jQuery( '#Addon_Grand_Total' ).html( grandTotal.toFixed(2) );
			jQuery( '#input_62_79' ).val( totalQuantity );
			initialDelivery();
		} );
<?php
		$y++;
	}
?>
		jQuery( '#FreeItem' ).change( function() {
			jQuery( '#input_62_105' ).val( jQuery( '#FreeItem' ).val( ) );
		} );
		jQuery( '#input_62_10' ).change( function() {
			var frequency = Number( jQuery( '#input_62_10 input:radio:checked' ).val() );
			jQuery( '#input_62_8' ).val( frequency.toFixed(2) );
			monthlyDelivery();
			var selected = jQuery( '#input_62_2' ).val();
			jQuery( '#input_62_13' ).val( capsule_commitment[selected] * frequency );
		} );
		jQuery( '#input_62_9' ).change( function() {
			nextDeliveryDay();
			initialDelivery();
		} );
		jQuery( '#input_62_87' ).change( function() {
			nextDeliveryDay();
		} );
		//jQuery( '#input_62_10' ).trigger( 'change' );
		/*jQuery( '#choice_62_21_1' ).change( initialDelivery );
		jQuery( '#choice_62_21_2' ).change( function() {
			if ( jQuery( '#choice_62_21_2' ).is( ':checked' ) ) {
				jQuery( '#choice_62_21_1' ).prop( "checked", false );
			} else {
				jQuery( '#choice_62_21_1' ).prop( "checked", true );
			}
			initialDelivery();
		} );*/
		jQuery( '#choice_62_21_1' ).change( function() {
			if ( jQuery( '#choice_62_21_1' ).is( ':checked' ) ) {
				jQuery( '#choice_62_21_2' ).prop( "checked", false );
				jQuery( '#choice_62_21_3' ).prop( "checked", false );
			}
			initialDelivery();
		} );
		jQuery( '#choice_62_21_2' ).change( function() {
			if ( jQuery( '#choice_62_21_2' ).is( ':checked' ) ) {
				jQuery( '#choice_62_21_1' ).prop( "checked", false );
				jQuery( '#choice_62_21_3' ).prop( "checked", false );
			}
			initialDelivery();
		} );
		jQuery( '#choice_62_21_3' ).change( function() {
			if ( jQuery( '#choice_62_21_3' ).is( ':checked' ) ) {
				jQuery( '#choice_62_21_1' ).prop( "checked", false );
				if ( jQuery( '#input_62_107' ).val().trim() == "" ) {
					jQuery( '#choice_62_21_2' ).prop( "checked", true );
					jQuery( '#choice_62_21_3' ).prop( "checked", false );
					alert ( "请从“會員中心”页面更新運送地址。" );
				} else {
					jQuery( '#choice_62_21_2' ).prop( "checked", false );
				}
			}
			initialDelivery();
		} );
		jQuery( '#input_62_85' ).change( initialDelivery );
		jQuery( '#input_62_32' ).change( function() {
			var val = jQuery( '#input_62_32' ).val( );
			if ( val.indexOf('5') == 0 ) {
				jQuery( '#choice_62_34_0' ).prop( "checked", true);
			} else {
				jQuery( '#choice_62_34_0' ).prop( "checked", false);
			}
			if ( val.indexOf('4') == 0 ) {
				jQuery( '#choice_62_34_1' ).prop( "checked", true);
			} else {
				jQuery( '#choice_62_34_1' ).prop( "checked", false);
			}
		} );
		var flavour = jQuery( '#input_62_6' ).val();
		if ( flavour != '' ) {
			var selected_flavour = flavour.split( '|' );
			var selected_quantity = jQuery( '#input_62_7' ).val().split( '|' );
			jQuery.each( selected_flavour, function( i, val ) {
				jQuery( '#Quantity_' + val ).val( selected_quantity[i] ).trigger( 'change' );
			} );
		}
		var flavour = jQuery( '#input_62_17' ).val();
		if ( flavour != '' ) {
			var selected_flavour = flavour.split( '|' );
			var selected_quantity = jQuery( '#input_62_18' ).val().split( '|' );
			jQuery.each( selected_flavour, function( i, val ) {
				jQuery( '#Addon_Quantity_' + val ).val( selected_quantity[i] ).trigger( 'change' );
			} );
		}
		initialDelivery();
		jQuery( '#FreeItem' ).val( jQuery( '#input_62_105' ).val( ) );
		jQuery( '#input_62_9' ).trigger( 'change' );
		jQuery( '#input_62_32' ).trigger( 'change' );
		jQuery( '#input_62_92' ).change( function() {
			jQuery( '#input_62_93' ).empty();
			jQuery( '#input_62_93' ).append( jQuery( new Option( "请选择", "" ) ) );
			var selected = jQuery( '#input_62_92' ).val( );
			jQuery.each( city_list[selected], function( i, val ) {
				var option = new Option( val, val);
				jQuery( '#input_62_93' ).append( jQuery( option ) );
			} );
		} );
		jQuery( '#input_62_93' ).change( function() {
			var selected = jQuery( '#input_62_93' ).val( );
			jQuery( '#input_62_100' ).val( selected );
			jQuery( '#input_62_94' ).empty();
			jQuery( '#input_62_94' ).append( jQuery( new Option( "请选择", "" ) ) );
			jQuery.each( postcode_list[selected], function( i, val ) {
			var option = new Option( val, val);
				jQuery( '#input_62_94' ).append( jQuery( option ) );
			} );
		} );
		jQuery( '#input_62_94' ).change( function() {
			var selected = jQuery( '#input_62_94' ).val( );
			jQuery( '#input_62_101' ).val( selected );
		} );
		var selectedState = jQuery( '#input_62_92' ).val( );
<?php
	if ( $_REQUEST['delivery_zone'] == "District" ) {
?>
		jQuery( '#input_62_92' ).empty();
		jQuery( '#input_62_92' ).append( jQuery( new Option( "Please Select", "" ) ) );
		jQuery( '#input_62_92' ).append( jQuery( new Option( "離島", "離島" ) ) );
<?php
	}
?>
		jQuery( '#input_62_92' ).val( selectedState );
		var selectedCity = jQuery( '#input_62_100' ).val( );
		jQuery( '#input_62_92' ).trigger( 'change' );
		if ( selectedCity != "" ) {
			jQuery( '#input_62_93' ).val( selectedCity );
		}
		var selectedPostcode = jQuery( '#input_62_101' ).val( );
		jQuery( '#input_62_93' ).trigger( 'change' );
		if ( selectedPostcode != "" ) {
			jQuery( '#input_62_94' ).val( selectedPostcode );
		}
		jQuery( '#input_62_97' ).change( function() {
			jQuery( '#input_62_98' ).empty();
			jQuery( '#input_62_98' ).append( jQuery( new Option( "请选择", "" ) ) );
			var selected = jQuery( '#input_62_97' ).val( );
			jQuery.each( city_list[selected], function( i, val ) {
				var option = new Option( val, val);
				jQuery( '#input_62_98' ).append( jQuery( option ) );
			} );
		} );
		jQuery( '#input_62_98' ).change( function() {
			var selected = jQuery( '#input_62_98' ).val( );
			jQuery( '#input_62_102' ).val( selected );
			jQuery( '#input_62_99' ).empty();
			jQuery( '#input_62_99' ).append( jQuery( new Option( "请选择", "" ) ) );
			jQuery.each( postcode_list[selected], function( i, val ) {
			var option = new Option( val, val);
				jQuery( '#input_62_99' ).append( jQuery( option ) );
			} );
		} );
		jQuery( '#input_62_99' ).change( function() {
			var selected = jQuery( '#input_62_99' ).val( );
			jQuery( '#input_62_103' ).val( selected );
		} );
		var selectedDeliveryState = jQuery( '#input_62_97' ).val( );
<?php
	if ( $_REQUEST['delivery_zone'] == "District" ) {
?>
		jQuery( '#input_62_97' ).empty();
		jQuery( '#input_62_97' ).append( jQuery( new Option( "Please Select", "" ) ) );
		jQuery( '#input_62_97' ).append( jQuery( new Option( "離島", "離島" ) ) );
<?php
	}
?>
		jQuery( '#input_62_97' ).val( selectedDeliveryState );
		var selectedDeliveryCity = jQuery( '#input_62_102' ).val( );
		jQuery( '#input_62_97' ).trigger( 'change' );
		if ( selectedDeliveryCity != "" ) {
			jQuery( '#input_62_98' ).val( selectedDeliveryCity );
		}
		var selectedDeliveryPostcode = jQuery( '#input_62_103' ).val( );
		jQuery( '#input_62_98' ).trigger( 'change' );
		if ( selectedDeliveryPostcode != "" ) {
			jQuery( '#input_62_99' ).val( selectedDeliveryPostcode );
		}
		
		signatureCapture( "cardHolderSignature", "input_62_37" );
		var cardHolderCanvas = document.getElementById( "cardHolderSignature" );
		var cardHolderImg = new window.Image();
		cardHolderImg.addEventListener( "load", function () {
			cardHolderCanvas.getContext( "2d" ).drawImage( cardHolderImg, 0, 0 );
		});
		cardHolderImg.setAttribute( "src", "data:image/png;base64," + jQuery( "#input_62_37" ).val() );
		
		signatureCapture( "custSignature", "input_62_80" );
		var custCanvas = document.getElementById( "custSignature" );
		var custImg = new window.Image();
		custImg.addEventListener( "load", function () {
			custCanvas.getContext( "2d" ).drawImage( custImg, 0, 0 );
		});
		custImg.setAttribute( "src", "data:image/png;base64," + jQuery( "#input_62_80" ).val() );
		
		/*signatureCapture( "spSignature", "input_62_82" );
		var spCanvas = document.getElementById( "spSignature" );
		var spImg = new window.Image();
		spImg.addEventListener( "load", function () {
			spCanvas.getContext( "2d" ).drawImage( spImg, 0, 0 );
		});
		spImg.setAttribute( "src", "data:image/png;base64," + jQuery( "#input_62_82" ).val() );*/
		
		jQuery( '#choice_62_108_1' ).change( function() {
			var total = Number( jQuery( '#input_62_23' ).val() );
			if ( jQuery( '#choice_62_108_1' ).is( ':checked' ) ) {
				total += 1;
			} else {
				total -= 1;
			}
			jQuery( '#input_62_23' ).val( total.toFixed(2) );
			jQuery( '#input_62_39' ).val( total.toFixed(2) );
		} );
		
		jQuery( '#gform_62' ).submit( function( event ) {
			if ( jQuery( '#gform_target_page_number_62' ).val() == 2 ) {
				var monthlyCapsule = "", monthlyQuantity = "", addonCapsule = "", addonQuantity = "";
<?php
	foreach( $capsule_flavour as $value ) {
?>
				if ( Number( jQuery( '#Quantity_<?php echo $value; ?>' ).val() ) > 0 ) {
					monthlyCapsule += "<?php echo $value; ?>|";
					monthlyQuantity += jQuery( '#Quantity_<?php echo $value; ?>' ).val() + "|";
				}
				if ( Number( jQuery( '#Addon_Quantity_<?php echo $value; ?>' ).val() ) > 0 ) {
					addonCapsule += "<?php echo $value; ?>|";
					addonQuantity += jQuery( '#Addon_Quantity_<?php echo $value; ?>' ).val() + "|";
				}
<?php
	}
?>
				jQuery( '#input_62_6' ).val( monthlyCapsule );
				jQuery( '#input_62_7' ).val( monthlyQuantity );
				jQuery( '#input_62_17' ).val( addonCapsule );
				jQuery( '#input_62_18' ).val( addonQuantity );
			}
			if ( jQuery( '#input_62_37' ).val() != "" ) {
				var img =  signatureSave( "cardHolderSignature" );
				img = img.replace( "data:image/png;base64,", "" );
				jQuery( '#input_62_37' ).val( img );
			}
			if ( jQuery( '#input_62_80' ).val() != "" ) {
				var img =  signatureSave( "custSignature" );
				img = img.replace( "data:image/png;base64,", "" );
				jQuery( '#input_62_80' ).val( img );
			}
			if ( jQuery( '#input_62_82' ).val() != "" ) {
				var img =  signatureSave( "spSignature" );
				img = img.replace( "data:image/png;base64,", "" );
				jQuery( '#input_62_82' ).val( img );
			}
		} );
	} );
	</script>
<?php
	return $form;
}

function validate_welcome_offer( $user_info ) {
	$exists = email_exists( $user_info );
	if ( $exists > 0 ) {
		return false;
	}
	return true;
}

function validate_welcome_offer_cc( $cc_number, $welcome_offer_capsule ) {
	$valid = true;
	if ( $welcome_offer_capsule == "yes" ) {
		global $wpdb;
		$query = "SELECT * FROM ct_coffee_sharing_cc WHERE cc_no = '" . encrypt( $cc_number ) . "' AND Status = 'A'";
		$wpdb -> get_results( $query );
		if ( $wpdb -> num_rows > 0 ) {
			$valid = false;
		}
	}
	return $valid;
}

add_filter( 'gform_validation_message_62', function ( $message, $form ) {
	if ( gf_upgrade()->get_submissions_block() ) {
		return $message;
	}
	
	$message = "<div class='validation_error'>請更正以下錯誤";
	//$message .= '<ul>';
	//foreach ( $form['fields'] as $field ) {
	//	if ( $field->failed_validation ) {
	//		$message .= sprintf( '<li>%s - %s</li>', GFCommon::get_label( $field ), $field->validation_message );
	//	}
	//}
	//$message .= '</ul>';
	$message .= '</div>';
	
	return $message;
}, 10, 2 );

add_filter('gform_validation_62', 'validate_ipartner_subscription_form' );
function validate_ipartner_subscription_form( $validation_result ) {
	$form = $validation_result["form"];
	$current_page = rgpost( 'gform_source_page_number_' . $form['id'] ) ? rgpost( 'gform_source_page_number_' . $form['id'] ) : 1;
	if ( rgpost( "input_72" ) == "" || rgpost( "input_104" ) == "UAT" ) {
		$validation_result['is_valid'] = false;
		$form['fields'][89]->failed_validation = true;
		$form['fields'][89]->validation_message = "Partner Code not found.";
	}
	if ( rgpost( "input_2" ) == 0) {
		$validation_result['is_valid'] = false;
		$form['fields'][1]->failed_validation = true;
		$form['fields'][1]->validation_message = "請選擇一個計劃。";
	}
	if ( (int)rgpost( "input_13" ) > (int)rgpost( "input_14" ) ) {
		$validation_result['is_valid'] = false;
		$form['fields'][1]->failed_validation = true;
		$form['fields'][1]->validation_message = "請選擇至少 " . rgpost( "input_13" ) . " capsule (每月訂購)。";
	} else if ( (int)rgpost( "input_86" ) > (int)rgpost( "input_79" ) ) {
		$validation_result['is_valid'] = false;
		$form['fields'][1]->failed_validation = true;
		$form['fields'][1]->validation_message = "請選擇至少 " . rgpost( "input_86" ) . " capsule (首購)。";
	} else if ( ( rgpost( "input_2" ) == 3 || rgpost( "input_2" ) == 4 ) && rgpost( "input_105" ) == "") {
		$validation_result['is_valid'] = false;
		$form['fields'][1]->failed_validation = true;
		$form['fields'][1]->validation_message = "Please select your favorite box capsule for FREE.";
	}
	if ( rgpost( "input_21_1" ) == "yes" && rgpost( "input_85" ) == "" ) {
		$validation_result['is_valid'] = false;
		$form['fields'][22]->failed_validation = true;
		$form['fields'][22]->validation_message = "請選擇取貨地點。";
	}
	if ( rgpost( "input_21_3" ) == "partner" && trim ( rgpost( "input_107" ) ) == "" ) {
		$validation_result['is_valid'] = false;
		$form['fields'][21]->failed_validation = true;
		$form['fields'][21]->validation_message = "请从“會員中心”页面更新運送地址。";
	}
	if ( rgpost( "input_32" ) != "" ) {
		$cc_no = str_replace( "-", "", rgpost( "input_32" ) );
		if ( !ctype_digit( $cc_no ) || strlen( $cc_no ) != 16 ) {
			$validation_result['is_valid'] = false;
			$form['fields'][33]->failed_validation = true;
			$form['fields'][33]->validation_message = "卡號碼错误!";
		} else if ( ( strpos( rgpost( "input_32" ), "4" ) === false || strpos( rgpost( "input_32" ), "4" ) <> 0 ) 
			&& ( strpos( rgpost( "input_32" ), "5" ) === false || strpos( rgpost( "input_32" ), "5" ) <> 0 ) ) {
			$validation_result['is_valid'] = false;
			$form['fields'][33]->failed_validation = true;
			$form['fields'][33]->validation_message = "卡號碼错误!!";
		} else if ( !validate_cc_number( $cc_no ) ) {
			$validation_result['is_valid'] = false;
			$form['fields'][33]->failed_validation = true;
			$form['fields'][33]->validation_message = "卡號碼错误!!!";
		/*} else if ( !validate_welcome_offer_cc( rgpost( "input_32" ), rgpost( "input_108_1" ) ) ) {
			$validation_result['is_valid'] = false;
			$form['fields'][33]->failed_validation = true;
			$form['fields'][33]->validation_message = "RM1 Welcome Offer only valid for new user.";
		} else if ( !validate_cc_list( rgpost( "input_32" ) ) ) {
			$validation_result['is_valid'] = false;
			$form['fields'][33]->failed_validation = true;
			$form['fields'][33]->validation_message = "This Credit/Debit Card No. has been used more than 5 times...";
		} else {
			if ( rgpost( "input_2" ) == 1 || rgpost( "input_2" ) == 3 ) {
				$url = "https://lookup.binlist.net/" . $cc_no;
				$curl = curl_init();
				curl_setopt ( $curl, CURLOPT_URL, $url );
				curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true );
				$result = curl_exec ( $curl );
				$file = fopen( "binlist_2.txt", "a" );
				fwrite( $file, date('Y-m-d H:i:s') . " ( " . $cc_no . " ) " . $result . "\n" );
				fclose( $file );
				$json = json_decode( $result, true );
				curl_close ( $curl );
				if ( $json[type] == "debit" ) {
					$validation_result['is_valid'] = false;
					$form['fields'][33]->failed_validation = true;
					$form['fields'][33]->validation_message = "Only credit card allowed for this plan !";
				}
			}*/
		}
	}
	if ( rgpost( "input_33" ) != "" ) {
		$month = "";
		$year = "";
		$expiry = explode( '-', rgpost( "input_33" ) );
		if ( count( $expiry ) > 0 ) {
			$month = $expiry[0];
		}
		if ( count( $expiry ) > 1 ) {
			$year = $expiry[1];
		}
		if ( strtotime( date( "Y-m-d" ) ) >= strtotime( "20" . $year . "-" . $month . "-01" ) ) {
			$validation_result['is_valid'] = false;
			$form['fields'][34]->failed_validation = true;
			$form['fields'][34]->validation_message = "卡有效日期错误!";
		}
	}
	/*if ( $current_page == 2 && rgpost("input_37") == "") {
		$validation_result['is_valid'] = false;
		$form['fields'][40]->failed_validation = true;
		$form['fields'][40]->validation_message = "Please sign";
	}*/
	if ( $current_page == 2 ) {
		if ( rgpost( "input_74" ) == ""  || strpos( rgpost( "input_74" ), "请选择" ) != "" ) {
			$validation_result['is_valid'] = false;
			$form['fields'][36]->failed_validation = true;
			$form['fields'][36]->validation_message = "请选择發卡銀行!";
		}
		if ( rgpost("input_37") == "") {
			$validation_result['is_valid'] = false;
			$form['fields'][40]->failed_validation = true;
			$form['fields'][40]->validation_message = "请签名";
		}
	}
	/*if ( rgpost( "input_54" ) != "" ) {
		$nric = str_replace( "-", "", rgpost( "input_54" ) );
		if ( !ctype_digit( $nric ) || strlen( $nric ) != 12 ) {
			$validation_result['is_valid'] = false;
			$form['fields'][58]->failed_validation = true;
			$form['fields'][58]->validation_message = "Invalid NRIC!";
		}
	}
	if ( rgpost( "input_56" ) != "" ) {
		$phone = str_replace( "-", "", rgpost( "input_56" ) );
		if ( !ctype_digit( $phone ) ) {
			$validation_result['is_valid'] = false;
			$form['fields'][61]->failed_validation = true;
			$form['fields'][61]->validation_message = "Invalid Phone!";
		}
	}*/
	/*if ( strpos( rgpost( "input_58" ), "&" ) != "" ) {
		$validation_result['is_valid'] = false;
		$form['fields'][63]->failed_validation = true;
		$form['fields'][63]->validation_message = "Invalid Email!!!";
	} else if ( !validate_email_list( rgpost( "input_58" ) ) ) {
		$validation_result['is_valid'] = false;
		$form['fields'][63]->failed_validation = true;
		$form['fields'][63]->validation_message = "Invalid Email!";
	}*/
	/*if ( trim( rgpost( "input_58" ) ) != "" && trim( $form['fields'][63]->validation_message ) == "" ) {
		if ( !email_validation( 62, rgpost( "input_72" ), rgpost( "input_58" ) ) ) {
			$validation_result['is_valid'] = false;
			$form['fields'][63]->failed_validation = true;
			$form['fields'][63]->validation_message = "Invalid Email...";
		}
	}
	$delivery_add = 0;
	if ( trim( rgpost( "input_95" ) ) != "" ) {
		$delivery_add++;
	}
	if ( trim( rgpost( "input_96" ) ) != "" ) {
		$delivery_add++;
	}
	if ( trim( rgpost( "input_97" ) ) != "" ) {
		$delivery_add++;
	}
	if ( trim( rgpost( "input_98" ) ) != "" ) {
		$delivery_add++;
	}
	if ( trim( rgpost( "input_99" ) ) != "" ) {
		$delivery_add++;
	}
	if ( rgpost( "input_89_1" ) == "yes" && $delivery_add != 5 ) {
		$validation_result['is_valid'] = false;
		$form['fields'][75]->failed_validation = true;
		$form['fields'][75]->validation_message = "Please enter a complete address";
	}*/
	if ( rgpost( "input_89_1" ) == "yes" && trim( rgpost( "input_95" ) ) == "" ) {
		$validation_result['is_valid'] = false;
		$form['fields'][77]->failed_validation = true;
		$form['fields'][77]->validation_message = "請填寫送貨地址";
	}
	if ( rgpost( "input_89_1" ) == "yes" && trim( rgpost( "input_97" ) ) == "" ) {
		$validation_result['is_valid'] = false;
		$form['fields'][79]->failed_validation = true;
		$form['fields'][79]->validation_message = "請选择送貨區域 (State)";
	}
	if ( $current_page == 3 && rgpost("input_80") == "" ) {
		$validation_result['is_valid'] = false;
		$form['fields'][85]->failed_validation = true;
		$form['fields'][85]->validation_message = "請簽名";
	}
	/*if ( $current_page == 3 && rgpost("input_82") == "" ) {
		$validation_result['is_valid'] = false;
		$form['fields'][92]->failed_validation = true;
		$form['fields'][92]->validation_message = "Please sign";
	}*/
	if ( $current_page == 3 ) {
		if ( rgpost( "input_108_1" ) == "yes" && !validate_welcome_offer( trim( rgpost( "input_58" ) ) ) ) {
			$validation_result['is_valid'] = false;
			$form['fields'][104]->failed_validation = true;
			$form['fields'][104]->validation_message = "This offer only valid for new user.";
		}
	}
	$validation_result['form'] = $form;
	return $validation_result;
}

add_action( 'gform_pre_submission_62', 'pre_ipartner_subscription_form' );
function pre_ipartner_subscription_form( $form ) {
	$_POST['input_31'] = strtoupper( $_POST['input_31'] );
	$_POST['input_53'] = strtoupper( $_POST['input_53'] );
	$_POST['input_90'] = strtoupper( $_POST['input_90'] );
	$_POST['input_91'] = strtoupper( $_POST['input_91'] );
	$_POST['input_95'] = strtoupper( $_POST['input_95'] );
	$_POST['input_96'] = strtoupper( $_POST['input_96'] );
	//$_POST['input_32'] = encrypt( $_POST['input_32'] );
}

add_action( 'gform_after_submission_62', 'submit_ipartner_subscription_form' );
function submit_ipartner_subscription_form( $entry ) {
	$form_id = $entry["form_id"];
	$entry_id = $entry["id"];
	
	$serial_no = $entry[84];
	$param = $serial_no . "%7C";
	
	$img = "data:image/png;base64," . $entry[80];
	$imgData = str_replace( ' ', '+' , $img );
	$imgData = substr( $imgData, strpos( $imgData, "," ) + 1 );
	$imgData = base64_decode( $imgData );
	$filePath = dirname( dirname( dirname( __FILE__ ) ) ) . '/uploads/gravity_forms/signatures/' . $serial_no . '_cust.png';
	$file = fopen( $filePath, 'w' );
	fwrite( $file, $imgData );
	fclose( $file );
	
	$plan_type = "";
	$plan_code = "";
	$deposit = "";
	$capsule_price = "";
	$milk_price = "";
	$ID = 0;
	if ( $entry[2] == 1 ) {
		$ID = 1;
	} else if ( $entry[2] == 2 ) {
		$ID = 2;
	} else if ( $entry[2] == 3 ) {
		$ID = 3;
	} else if ( $entry[2] == 4 ) {
		$ID = 4;
	} else if ( $entry[2] == 5 ) {
		$ID = 5;
	} else if ( $entry[2] == 6 ) {
		$ID = 6;
	} else if ( $entry[2] == 7 ) {
		$ID = 7;
	} else if ( $entry[2] == 8 ) {
		$ID = 8;
	} else if ( $entry[2] == 9 ) {
		$ID = 9;
	} else if ( $entry[2] == 10 ) {
		$ID = 10;
	}
	$Monthly_Capsules_Commitment = 0;
	global $wpdb;
	$result = $wpdb->get_results( "SELECT * FROM ct_coffee_sharing_plan WHERE Status = 'A' and ID = " . $ID );
	foreach ( $result as $val ) {
		$plan_type = $val->Plan_Type;
		$plan_code = $val->Plan_Code;
		$deposit = $val->Machine_Deposit;
		$capsule_price = $val->Capsules_Price;
		$milk_price = $val->Milk_Capsules_Price;
		$Monthly_Capsules_Commitment = $val->Monthly_Capsules_Commitment;
		$param = $param . $val->Description . "%7C";
		$param = $param . $val->Machine_Monthly_Rental . "%7C";
		$param = $param . $deposit . "%7C";
		$param = $param . $Monthly_Capsules_Commitment . "%7C";
		$param = $param . $capsule_price . "%7C";
		$param = $param . $milk_price . "%7C";
	}
	$param_short = $param;
	
	$monthly_capsule = $entry[6];
	$monthly_quantity = $entry[7];
	$capsule_month = explode( "|", $monthly_capsule );
	$qty_month = explode( "|", $monthly_quantity );
	$c_qty_month = array_combine( $capsule_month, $qty_month );
	$amico = ( isset( $c_qty_month['Amico'] ) && !empty( $c_qty_month['Amico'] ) ) ? $c_qty_month['Amico'] : '0';
	$choco = ( isset( $c_qty_month['Choco'] ) && !empty( $c_qty_month['Choco'] ) ) ? $c_qty_month['Choco'] : '0';
	$inLove = ( isset( $c_qty_month['InLove'] ) && !empty( $c_qty_month['InLove'] ) ) ? $c_qty_month['InLove'] : '0';
	$lonely = ( isset( $c_qty_month['Lonely'] ) && !empty( $c_qty_month['Lonely'] ) ) ? $c_qty_month['Lonely'] : '0';
	$luna = ( isset( $c_qty_month['Luna'] ) && !empty( $c_qty_month['Luna'] ) ) ? $c_qty_month['Luna'] : '0';
	$moonLight = ( isset( $c_qty_month['MoonLight'] ) && !empty( $c_qty_month['MoonLight'] ) ) ? $c_qty_month['MoonLight'] : '0';
	$passion =( isset( $c_qty_month['Passion'] ) && !empty( $c_qty_month['Passion'] ) ) ? $c_qty_month['Passion'] : '0';
	$peace =( isset( $c_qty_month['Peace'] ) && !empty( $c_qty_month['Peace'] ) ) ? $c_qty_month['Peace'] : '0';
	$sunrise =( isset( $c_qty_month['Sunrise'] ) && !empty( $c_qty_month['Sunrise'] ) ) ? $c_qty_month['Sunrise'] : '0';
	$milk =( isset( $c_qty_month['Milk'] ) && !empty( $c_qty_month['Milk'] )) ? $c_qty_month['Milk'] : '0';
	$king = ( isset( $c_qty_month['King'] ) && !empty( $c_qty_month['King'] ) ) ? $c_qty_month['King'] : '0';
	$queen = ( isset( $c_qty_month['Queen'] ) && !empty( $c_qty_month['Queen'] ) ) ? $c_qty_month['Queen'] : '0';
	$prince = ( isset( $c_qty_month['Prince'] ) && !empty( $c_qty_month['Prince'] ) ) ? $c_qty_month['Prince'] : '0';
	$princess = ( isset( $c_qty_month['Princess'] ) && !empty( $c_qty_month['Princess'] ) ) ? $c_qty_month['Princess'] : '0';
	$earl = ( isset( $c_qty_month['Earl'] ) && !empty( $c_qty_month['Earl'] ) ) ? $c_qty_month['Earl'] : '0';
	$total_quantity = $entry[14];
	$capsule_interval = array();
	$result = $wpdb->get_results( "SELECT * FROM ct_coffee_sharing_capsule" );
	foreach ( $result as $val ) {
		$capsule_interval[ $val->Name ] = $val->Quantity_Per_Unit;
	}
	$monthly_box = "";
	foreach ( $capsule_month as $val ) {
		$monthly_box = $monthly_box . $capsule_interval[$val] . "%7C";
	}
	$monthly_delivery = $entry[11];
	$delivery_frequency = $entry[10];
	$commencement_date = $entry[109];//27];
	$monthly_order_total = $entry[12];//29];
	
	$initial_capsule = $entry[17];
	$initial_quantity = $entry[18];
	$capsule_ini = explode( "|", $initial_capsule );
	$qty_ini = explode( "|", $initial_quantity );
	$c_qty_ini = array_combine( $capsule_ini, $qty_ini );
	$amico_ini = ( isset( $c_qty_ini['Amico'] ) && !empty( $c_qty_ini['Amico'] ) ) ? $c_qty_ini['Amico'] : '0';
	$choco_ini = ( isset( $c_qty_ini['Choco'] ) && !empty( $c_qty_ini['Choco'] ) ) ? $c_qty_ini['Choco'] : '0';
	$inLove_ini = ( isset( $c_qty_ini['InLove'] ) && !empty( $c_qty_ini['InLove'] ) ) ? $c_qty_ini['InLove'] : '0';
	$lonely_ini = ( isset( $c_qty_ini['Lonely'] ) && !empty( $c_qty_ini['Lonely'] ) ) ? $c_qty_ini['Lonely'] : '0';
	$luna_ini = ( isset( $c_qty_ini['Luna'] ) && !empty( $c_qty_ini['Luna'] ) ) ? $c_qty_ini['Luna'] : '0';
	$moonLight_ini = ( isset( $c_qty_ini['MoonLight'] ) && !empty( $c_qty_ini['MoonLight'] ) ) ? $c_qty_ini['MoonLight'] : '0';
	$passion_ini = ( isset( $c_qty_ini['Passion'] ) && !empty( $c_qty_ini['Passion'] ) ) ? $c_qty_ini['Passion'] : '0';
	$peace_ini = ( isset( $c_qty_ini['Peace'] ) && !empty( $c_qty_ini['Peace'] ) ) ? $c_qty_ini['Peace'] : '0';
	$sunrise_ini = ( isset( $c_qty_ini['Sunrise'] ) && !empty( $c_qty_ini['Sunrise'] ) ) ? $c_qty_ini['Sunrise'] : '0';
	$milk_ini = ( isset( $c_qty_ini['Milk'] ) && !empty( $c_qty_ini['Milk'] ) ) ? $c_qty_ini['Milk'] : '0';
	$king_ini = ( isset( $c_qty_ini['King'] ) && !empty( $c_qty_ini['King'] ) ) ? $c_qty_ini['King'] : '0';
	$queen_ini = ( isset( $c_qty_ini['Queen'] ) && !empty( $c_qty_ini['Queen'] ) ) ? $c_qty_ini['Queen'] : '0';
	$prince_ini = ( isset( $c_qty_ini['Prince'] ) && !empty( $c_qty_ini['Prince'] ) ) ? $c_qty_ini['Prince'] : '0';
	$princess_ini = ( isset( $c_qty_ini['Princess'] ) && !empty( $c_qty_ini['Princess'] ) ) ? $c_qty_ini['Princess'] : '0';
	$earl_ini = ( isset( $c_qty_ini['Earl'] ) && !empty( $c_qty_ini['Earl'] ) ) ? $c_qty_ini['Earl'] : '0';
	$amico_ini_box = $amico_ini . "box" . $capsule_interval['Amico'];
	$choco_ini_box = $choco_ini . "box" . $capsule_interval['Choco'];
	$inLove_ini_box = $inLove_ini . "box" . $capsule_interval['InLove'];
	$lonely_ini_box = $lonely_ini . "box" . $capsule_interval['Lonely'];
	$luna_ini_box = $luna_ini . "box" . $capsule_interval['Luna'];
	$passion_ini_box = $passion_ini . "box" . $capsule_interval['Passion'];
	$sunrise_ini_box = $sunrise_ini . "box" . $capsule_interval['Sunrise'];
	$milk_ini_box = $milk_ini . "box" . $capsule_interval['Milk'];
	$king_ini_box = $king_ini . "box" . $capsule_interval['King'];
	$queen_ini_box = $queen_ini . "box" . $capsule_interval['Queen'];
	$prince_ini_box = $prince_ini . "box" . $capsule_interval['Prince'];
	$princess_ini_box = $princess_ini . "box" . $capsule_interval['Princess'];
	$earl_ini_box = $earl_ini . "box" . $capsule_interval['Earl'];
	$initial_box = "";
	foreach ( $capsule_ini as $val ) {
		$initial_box = $initial_box . $capsule_interval[$val] . "%7C";
	}
	$initial_deposit = $entry[20];
	$delivery_mode = "Delivery";
	$initial_delivery = $entry[22];
	$tranloc = "HK1";
	if ( $_POST['input_21_1'] == "yes" ) {
		$tranloc = $entry[85];
		$delivery_mode = "Pick up";
		$initial_delivery = "0.00";
	} else if ( $_POST['input_21_3'] == "partner" ) {
		$delivery_mode = "Partner" . $initial_delivery;
	} else {
		if ( $initial_delivery != "0.00" ) {
			$delivery_mode = "NotFree" . $initial_delivery;
		}
	}
	$initial_order_total = $entry[23];
	
	$monthly_order_payment = "Credit/Debit Card";
	$cc_name = $entry[31];
	$cc_name = str_replace( "'", "''", $cc_name );
	$cc_no = $entry[32];
	$cc_expiry = $entry[33];
	$cc_type = $entry[34];
	$cc_bank = $entry[74];
	
	$membership_no = $serial_no;
	$cust_name = $entry[53];
	$cust_name = str_replace( "'", "''", $cust_name );
	$cust_nric = $entry[54];
	$cust_mobile = $entry[56];
	$cust_email = $entry[58];
	$cust_email = str_replace( "'", "''", $cust_email );
	/*$last_digit = substr( $cust_nric, strlen( $cust_nric ) - 1, strlen( $cust_nric ) );
	if ( ( $last_digit % 2 ) == 0 ) {
		$cust_gender = "F";
	} else {
		$cust_gender = "M";
	}
	$cust_race = $entry[88];*/
	$cust_address_1 = $entry[90];
	$cust_address_1 = str_replace( "'", "''", $cust_address_1 );
	$cust_address_2 = $entry[91];
	$cust_address_2 = str_replace( "'", "''", $cust_address_2 );
	$cust_address_city = $entry[93];
	$cust_address_postcode = $entry[94];
	$cust_address_state = $entry[92];
	$cust_delivery_name = $cust_name;
	$cust_delivery_phone = $cust_mobile;
	$cust_delivery_1 = $entry[95];
	$cust_delivery_1 = str_replace( "'", "''", $cust_delivery_1 );
	$cust_delivery_2 = $entry[96];
	$cust_delivery_2 = str_replace( "'", "''", $cust_delivery_2 );
	$cust_delivery_city = $entry[98];
	$cust_delivery_postcode = $entry[99];
	$cust_delivery_state = $entry[97];
	if ( $cust_delivery_1 == "" ) {
		$cust_delivery_1 = $cust_address_1;
		$cust_delivery_2 = $cust_address_2;
		$cust_delivery_city = $cust_address_city;
		$cust_delivery_postcode = $cust_address_postcode;
		$cust_delivery_state = $cust_address_state;
	}
	$sales_name = str_replace( "'", "''", $entry[71] );
	$sales_code = $entry[72];
	$sales_email = $entry[78];
	$referral_code = $entry[70];
	$ims_delivery_1 = $cust_delivery_1;
	$ims_delivery_2 = $cust_delivery_2;
	$ims_delivery_city = $cust_delivery_city;
	$ims_delivery_postcode = $cust_delivery_postcode;
	$ims_delivery_state = $cust_delivery_state;
	if ( $_POST['input_21_3'] == "partner" ) {
		$user = wp_get_current_user();
		$ims_delivery_1 = get_user_meta( $user->ID, 'shipping_address_1', true );
		$ims_delivery_2 = get_user_meta( $user->ID, 'shipping_address_2', true );
		$ims_delivery_city = get_user_meta( $user->ID, 'shipping_city', true );
		$ims_delivery_postcode = get_user_meta( $user->ID, 'shipping_postcode', true );
		$ims_delivery_state = get_user_meta( $user->ID, 'shipping_state', true );
		if ( $ims_delivery_state == 'JHR') {
			$ims_delivery_state = 'Johor';
		}
	}
	$param = $param . $cust_name . "%7C";
	$param = $param . $cust_nric . "%7C";
	$param = $param . $cust_mobile . "%7C";
	$param = $param . $cust_email . "%7C";
	$param = $param . $cust_address_1 . "%20" . $cust_address_2 . "%20" . $cust_address_city . "%20" . $cust_address_postcode . "%20" . $cust_address_state . "%7C";
	$param = $param . $cust_delivery_1 . "%20" . $cust_delivery_2 . "%20" . $cust_delivery_city . "%20" . $cust_delivery_postcode . "%20" . $cust_delivery_state . "%7C";
	$param = $param . $sales_name . "%7C";
	$param = $param . $sales_code . "%7C";
	if ( $referral_code == "" ) {
		$param = $param . "%20%7C";
	} else {
		$param = $param . $referral_code . "%7C";
	}
	
	$monthly_capsule = str_replace( '|', '%7C', $monthly_capsule );
	$monthly_quantity = str_replace( '|', '%7C', $monthly_quantity );
	$initial_capsule = str_replace( '|', '%7C', $initial_capsule );
	$initial_quantity = str_replace( '|', '%7C', $initial_quantity );
	
	$enc1 = "";
	$enc2 = "";
	if ( $cc_no != "" ) {
		$enc2 = $cc_no;//encrypt( $cc_no );
	}
	$quantity = 1;
	$initial_rental = $entry[19];
	$pay_type = "FIRHK";
	$usercode = "SYSTEM";
	$billcode = "HK9";
	$company = "HK1";
	$trancode = "SIN";
	/*$is_new_user = "isNewUser";
	$exists = email_exists( $cust_email );
	if ( $exists > 0 ) {
		$is_new_user = "CapsuleSubscription";
	}*/
	$is_new_user = "CapsuleSubscription";
	$welcome_offer_capsule = "false";
	//$sp_param = $entry[105] . "|" . ( $initial_order_total . "-" . $initial_order_total ) . "|" . $initial_rental . "|" . $deposit . "|" . $milk_ini_box . "|" . $choco_ini_box . "|" . $inLove_ini_box . "|" . $lonely_ini_box . "|" . $passion_ini_box . "|" . $sunrise_ini_box . "|" . $luna_ini_box . "|" . $amico_ini_box . "|" . $king_ini_box . "|" . $queen_ini_box . "|" . $prince_ini_box . "|" . $princess_ini_box . "|" . $earl_ini_box . "|" . $capsule_price . "|" . $milk_price . "|" . $pay_type . "||initial_cc_expiry|initial_approval_code||receipt_date|" . $delivery_mode . "|" . $ims_delivery_1 . "|" . $ims_delivery_2 . "|" . $ims_delivery_city . "|" . $ims_delivery_state . "|" . $ims_delivery_postcode . "|" . $cust_name . "|" . $cust_nric . "|" . $cust_email . "|" . $cust_mobile . "|" . $cust_gender . "|" . $cust_race . "|" . $cust_address_1 . "|" . $cust_address_2 . "|" . $cust_address_city . "|" . $cust_address_state . "|" . $cust_address_postcode . "|" . $cust_delivery_name . "|" . $cust_delivery_phone . "|" . $cust_delivery_1 . "|" . $cust_delivery_2 . "|" . $cust_delivery_city . "|" . $cust_delivery_state . "|" . $cust_delivery_postcode . "|" . $sales_code . "|" . $plan_code . "|" . $serial_no . "|" . $is_new_user . "|" . $membership_no . "|" . $referral_code . "|" . $commencement_date . "|" . ( $monthly_order_total - $delivery_frequency - $monthly_delivery ) . "|" . $milk . "|" . $choco . "|" . $peace . "|" . $inLove . "|" . $lonely . "|" . $moonLight . "|" . $passion . "|" . $sunrise . "|" . $luna . "|" . $amico . "|" . $king . "|" . $queen . "|" . $prince . "|" . $princess . "|" . $earl . "|" . $delivery_frequency . "|1|" . $plan_type . "|1|" . $quantity . "|1|" . $cc_bank . "|" . $cc_name . "|" . str_replace( '-', '/', $cc_expiry ) . "|" . $cc_type . "|" . $usercode . "|" . $tranloc . "|" . $billcode . "|" . $company . "|" . $trancode . "|" . $Monthly_Capsules_Commitment . "|" . $welcome_offer_capsule;
	//$sp_param = str_replace( '&', '%26', $sp_param );
	//$param_short = str_replace( '&', '%26', $param_short );
	//$url = "http://203.198.208.217:8081/ArisstoHK_Test/AdminSubscriptionOrder?sp_name=sp_ONLINE_GenSubscription&sp_param=" . $sp_param . "&enc1=initial_cc_code&enc2=" . $enc2 . "&param=" . $param_short . "&deposit=" . $deposit . "&capsule_price=" . $capsule_price . "&milk_price=" . $milk_price . "&monthly_capsule=" . $monthly_capsule . "&monthly_quantity=" . $monthly_quantity . "&total_quantity=" . $total_quantity . "&monthly_box=" . $monthly_box . "&monthly_delivery=" . $monthly_delivery . "&delivery_frequency=" . $delivery_frequency . "&commencement_date=" . $commencement_date . "&monthly_order_total=" . $monthly_order_total . "&monthly_order_payment=" . $monthly_order_payment . "&initial_capsule=" . $initial_capsule . "&initial_quantity=" . $initial_quantity . "&initial_box=" . $initial_box . "&initial_rental=" . $initial_rental . "&initial_deposit=" . $initial_deposit . "&initial_delivery=" . $initial_delivery . "&initial_order_total=" . $initial_order_total . "&cc_name=" . $cc_name . "&cc_expiry=" . $cc_expiry . "&cc_type=" . $cc_type . "&cc_bank=" . $cc_bank . "&serial_no=" . $serial_no;
	$url = "http://203.198.208.217:8081/ArisstoHK_Test/OnlineForm?sp_name=sp_ONLINE_GenSubscription&param_count=91&param1=" . $entry[105] . "&param2=" . ( $initial_order_total . "-" . $initial_order_total ) . "&param3=" . $initial_rental . "&param4=" . $deposit . "&param5=" . $milk_ini_box . "&param6=" . $choco_ini_box . "&param7=" . $inLove_ini_box . "&param8=" . $lonely_ini_box . "&param9=" . $passion_ini_box . "&param10=" . $sunrise_ini_box . "&param11=" . $luna_ini_box . "&param12=" . $amico_ini_box . "&param13=" . $king_ini_box . "&param14=" . $queen_ini_box . "&param15=" . $prince_ini_box . "&param16=" . $princess_ini_box . "&param17=" . $earl_ini_box . "&param18=" . $capsule_price . "&param19=" . $milk_price . "&param20=" . $pay_type . "&param21=cc_name&param22=cc_expiry&param23=approval_code&param24=&param25=receipt_date&param26=" . $delivery_mode . "&param27=" . str_replace( '&', '%26', $ims_delivery_1 ) . "&param28=" . str_replace( '&', '%26', $ims_delivery_2 ) . "&param29=" . str_replace( '&', '%26', $ims_delivery_city ) . "&param30=" . str_replace( '&', '%26', $ims_delivery_state ) . "&param31=" . str_replace( '&', '%26', $ims_delivery_postcode ) . "&param32=" . str_replace( '&', '%26', $cust_name ) . "&param33=" . $cust_nric . "&param34=" . str_replace( '&', '%26', $cust_email ) . "&param35=" . str_replace( '&', '%26', $cust_mobile ) . "&param36=" . $cust_gender . "&param37=" . $cust_race . "&param38=" . str_replace( '&', '%26', $cust_address_1 ) . "&param39=" . str_replace( '&', '%26', $cust_address_2 ) . "&param40=" . str_replace( '&', '%26', $cust_address_city ) . "&param41=" . str_replace( '&', '%26', $cust_address_state ) . "&param42=" . str_replace( '&', '%26', $cust_address_postcode ) . "&param43=" . str_replace( '&', '%26', $cust_delivery_name ) . "&param44=" . str_replace( '&', '%26', $cust_delivery_phone ) . "&param45=" . str_replace( '&', '%26', $cust_delivery_1 ) . "&param46=" . str_replace( '&', '%26', $cust_delivery_2 ) . "&param47=" . str_replace( '&', '%26', $cust_delivery_city ) . "&param48=" . str_replace( '&', '%26', $cust_delivery_state ) . "&param49=" . str_replace( '&', '%26', $cust_delivery_postcode ) . "&param50=" . $sales_code . "&param51=" . $plan_code . "&param52=" . $serial_no . "&param53=" . $is_new_user . "&param54=" . $membership_no . "&param55=" . $referral_code . "&param56=" . $commencement_date . "&param57=" . ( $monthly_order_total - $delivery_frequency - $monthly_delivery ) . "&param58=" . $milk . "&param59=" . $choco . "&param60=" . $peace . "&param61=" . $inLove . "&param62=" . $lonely . "&param63=" . $moonLight . "&param64=" . $passion . "&param65=" . $sunrise . "&param66=" . $luna . "&param67=" . $amico . "&param68=" . $king . "&param69=" . $queen . "&param70=" . $prince . "&param71=" . $princess . "&param72=" . $earl . "&param73=" . $delivery_frequency . "&param74=1&param75=" . $plan_type . "&param76=1&param77=" . $quantity . "&param78=1&param79=&param80=cc_name&param81=cc_expiry&param82=cc_type&param83=" . $usercode . "&param84=" . $tranloc . "&param85=" . $billcode . "&param86=" . $company . "&param87=" . $trancode . "&param88=" . $Monthly_Capsules_Commitment . "&param89=" . $welcome_offer_capsule;
	$url = str_replace( ' ', '%20', $url );
	$file = fopen( "ct_integration_subscription_order.txt", "a" );
	fwrite( $file, $url . "\n" );
	fclose( $file );
	
	global $wpdb;
	$wpdb -> query( "INSERT INTO `ct_integration_subscription_order`(`form_id`,`call_Value`,`membership_no`,`serial_no`) VALUES(" . $form_id . ",'" . $url . "','" . $membership_no . "','" . $serial_no . "')" );
	$plan_id = 0;
	$result = $wpdb->get_results("SELECT * FROM ct_coffee_sharing_plan WHERE Status = 'A' and Plan_Code = '" . $plan_code . "'");
	foreach ( $result as $val ) {
		$plan_id = $val->ID;
	}
	
	$wpdb -> query ( "INSERT INTO `ct_coffee_sharing_subscription`(`Plan_ID`,`Subscription_No`,`Membership_No`,`Customer_Type`,`Subs_Status`,`Subs_DateFrom`,`Amico`,`Choco`,`InLove`,`Lonely`,`Luna`,`MoonLight`,`Passion`,`Peace`,`Sunrise`,`Milk`,`Char_Every`,`Char_Period`,`Delivery_Fees`,`TotalSubs_Amount`,`iPartner_Code`,`Sales_Partner_Code`,`King`,`Queen`,`Prince`,`Princess`,`Earl`,`Plan_Code`) VALUES (" . $plan_id . ",'" . $serial_no . "','" . $membership_no . "','" . $plan_type . "','A','" . $commencement_date . "'," . $amico . "," . $choco . "," . $inLove . "," . $lonely . "," . $luna . "," . $moonLight . "," . $passion . "," . $peace . "," . $sunrise . "," . $milk . "," . $delivery_frequency . ",'Month','" . $monthly_delivery . "','" . $monthly_order_total . "','" . $referral_code . "','" . $sales_code . "'," . $king . "," . $queen . "," . $prince . "," . $princess . "," . $earl . ",'" . $plan_code . "')" );
	$wpdb -> query ( "INSERT INTO `ct_subscription_payment`(`Subscription_No`,`Payment_Type`,`Status`,`Start_Date`,`Name`,`Card_No`,`Card_Expiry`,`Card_Type`,`Issuing_Bank`) VALUES ('" . $serial_no . "','CC','A','" . $commencement_date . "','" . $cc_name . "','" . $cc_no . "','" . str_replace( "-", "", $cc_expiry ) . "','" . $cc_type . "','" . $cc_bank . "')" );
	$file_code = base64_encode( hash_hmac( 'sha256', $serial_no . "-" . $sales_code, 'nep@d1771NMY', true ) );
	$file_code = str_replace( '/', '_', $file_code );
	$wpdb -> query ( "INSERT INTO `ct_coffee_sharing_order`(`Plan_ID`,`Membership_No`,`Subscription_No`,`Amico`,`Choco`,`InLove`,`Lonely`,`Luna`,`MoonLight`,`Passion`,`Peace`,`Sunrise`,`Milk`,`Delivery_Fees`,`Total_Amount`,`Sales_Partner_Code`,`Payment_Type`,`Payment_Status`,`File_Code`,`King`,`Queen`,`Prince`,`Princess`,`Earl`) VALUES ('" . $plan_id . "','" . $membership_no . "','" . $serial_no . "'," . $amico_ini . "," . $choco_ini . "," . $inLove_ini . "," . $lonely_ini . "," . $luna_ini . "," . $moonLight_ini . "," . $passion_ini . "," . $peace_ini . "," . $sunrise_ini . "," . $milk_ini . ",'" . $initial_delivery . "','" . $initial_order_total . "','" . $sales_code . "','" . $pay_type . "','Pending','" . $file_code . "'," . $king_ini . "," . $queen_ini . "," . $prince_ini . "," . $princess_ini . "," . $earl_ini . ")" );
	$wpdb -> query ( "INSERT INTO `ct_coffee_sharing_cc`(`Reference_No`,`CC_No`,`Status`) VALUES ('" . $serial_no . "','" . $cc_no . "','I')" );
	$wpdb -> query ( "INSERT INTO `ct_tran_loc`( `Membership_No`, `Reference_No`, `Name`, `Amount`, `Sales_Partner_Code`, `Status`, `Created_Date`, `Tran_Loc`, `Mode` ) VALUES ('" . $membership_no . "','" . $serial_no . "','" . $cust_name . "','" . $initial_order_total . "','" . $sales_code . "','P',CURRENT_TIMESTAMP,'" . $tranloc . "','" . $delivery_mode . "')" );

	//create user
	$cust_role = "";
	if ( $plan_code == "AP001" ) {
		$cust_role = "member";
	}
	if ( $plan_code == "AP002" ) {
		$cust_role = "vvip";
	}
	$user_id = create_new_user_func( $cust_name, "", $cust_email, $cust_role, $cust_mobile, $cust_address_1, $cust_address_2, $cust_address_city, $cust_address_state, $cust_address_postcode, $cust_delivery_1, $cust_delivery_2, $cust_delivery_city, $cust_delivery_state, $cust_delivery_postcode, $membership_no );
	//$sales_id = $wpdb->get_var("SELECT user_id FROM sAHGGr4W_usermeta WHERE meta_key='Partner_Code' and meta_value = '" . $sales_code . "'");
	$dealer = get_user_by( 'Partner_Code', $sales_code );
	$sales_id = $dealer->ID;
	$wpdb -> query ( "INSERT INTO `ct_membership`(`Membership_No`,`Membership_Type`,`Plan_Code`,`Name`,`NRIC`,`Username`,`Email`,`Contact`,`Gender`,`Race`,`Mailing_Addr_1`,`Mailing_Addr_2`,`Mailing_City`,`Mailing_Postcode`,`Mailing_State`,`Delivery_Addr_1`,`Delivery_Addr_2`,`Delivery_City`,`Delivery_Postcode`,`Delivery_State`,`user_id`,`Sales_Partner_Code`,`Sales_Partner_Name`,`Ref_iPartner_Code`,`Create_Date`) VALUES ('" . $membership_no . "','" . $plan_type . "','" . $plan_code . "','" . $cust_name . "','" . $cust_nric . "','" . $cust_email . "','" . $cust_email . "','" . $cust_mobile . "','" . $cust_gender . "','" . $cust_race . "','" . $cust_address_1 . "','" . $cust_address_2 . "','" . $cust_address_city . "','" . $cust_address_postcode . "','" . $cust_address_state . "','" . $cust_delivery_1 . "','" . $cust_delivery_2 . "','" . $cust_delivery_city . "','" . $cust_delivery_postcode . "','" . $cust_delivery_state . "','" . $sales_id . "','" . $sales_code . "','" . $sales_name . "','" . $referral_code . "',CURRENT_TIMESTAMP)");
}

function create_new_user_func( $cust_first_name, $cust_last_name, $cust_email, $role, $cust_mobile, $cust_address_1, $cust_address_2, $cust_address_city, $cust_address_state, $cust_address_postcode, $cust_delivery_1, $cust_delivery_2, $cust_delivery_city, $cust_delivery_state, $cust_delivery_postcode, $membership_number ) {
	if ( $cust_address_state == 'Johor') {
		$cust_address_state = 'JHR';
	}
	$exists = email_exists( $cust_email );
	if ( $exists > 0 ) {
		$user = get_user_by( 'email', $cust_email );
		if (get_user_meta($user->ID, 'membership_number', true)) {
		} else {
			add_user_meta($user->ID, 'membership_number', $membership_number);
		}
		$has_role = false;
		$roles = $user->roles;
		foreach ( $roles as $val ) {
			if ( $val == $role ) {
				$has_role = true;
			}
		}
		if ( !$has_role ) {
			$user->add_role( $role );
		}
		return $exists;
	} else {
		$user_id = wp_create_user( $cust_email, $cust_mobile, $cust_email );
		$user = new WP_User( $cust_email );
		$user->set_role( $role );
		//$user->add_role( $role );
		wp_update_user( array( 'ID' => $user->ID, 'user_nicename ' => $cust_first_name ) );
		wp_update_user( array( 'ID' => $user->ID, 'display_name  ' => $cust_first_name ) );
		update_usermeta( $user->ID, 'first_name', $cust_first_name );
		update_usermeta( $user->ID, 'last_name', $cust_last_name );
		add_user_meta($user->ID, 'billing_first_name', $cust_first_name);
		add_user_meta($user->ID, 'billing_last_name', $cust_last_name);
		add_user_meta($user->ID, 'billing_email', $cust_email);
		add_user_meta($user->ID, 'billing_phone', $cust_mobile);
		add_user_meta($user->ID, 'billing_country', 'MY');
		add_user_meta($user->ID, 'billing_address_1', $cust_address_1);
		add_user_meta($user->ID, 'billing_address_2', $cust_address_2);
		add_user_meta($user->ID, 'billing_city', $cust_address_city);
		add_user_meta($user->ID, 'billing_state', $cust_address_state);
		add_user_meta($user->ID, 'billing_postcode', $cust_address_postcode);
		add_user_meta($user->ID, 'shipping_first_name', $cust_first_name);
		add_user_meta($user->ID, 'shipping_last_name', $cust_last_name);
		add_user_meta($user->ID, 'shipping_country', 'MY');
		add_user_meta($user->ID, 'shipping_address_1', $cust_delivery_1);
		add_user_meta($user->ID, 'shipping_address_2', $cust_delivery_2);
		add_user_meta($user->ID, 'shipping_city', $cust_delivery_city);
		add_user_meta($user->ID, 'shipping_state', $cust_delivery_state);
		add_user_meta($user->ID, 'shipping_postcode', $cust_delivery_postcode);
		add_user_meta($user->ID, 'membership_number', $membership_number);
		return $user_id;
	}
}

function send_sub_email( $membership_no,  $serial_no ) {
	$capsule_per_box = 10;
	
	global $wpdb;
	$plan_code = "";
	$name = "";
	$email = "";
	$cc = "";
	$result = $wpdb->get_results("SELECT * FROM ct_membership WHERE Membership_No = '" . $membership_no . "'");
	foreach ( $result as $val ) {
		$plan_code = $val->Plan_Code;
		$name = $val->Name;
		$email = $val->Email;
		$cc = $val->user_id;
	}
	if ( $cc != "" ) {
		$dealer = get_userdata( $cc );
		$cc = $dealer->user_email;
	}
	$param = $name . "%7C" . $membership_no;
	
	$plan_desc = "";
	$monthly_fees = "";
	$capsule_price = "";
	$deposit = "";
	$result = $wpdb->get_results("SELECT * FROM ct_coffee_sharing_plan WHERE Plan_Code = '" . $plan_code . "'");
	foreach ( $result as $val ) {
		$plan_desc = $val->Description;
		$monthly_fees = $val->Machine_Monthly_Rental;
		$capsule_price = $val->Capsules_Price;
		$deposit = $val->Machine_Deposit;
	}
	$param = $param . "%7C" . $plan_desc;
	
	$commencement_date = "";
	$delivery_frequency = "";
	$capsules = "";
	$monthly_delivery = "";
	$monthly_order_total = "";
	$result = $wpdb->get_results("SELECT * FROM ct_coffee_sharing_subscription WHERE Subscription_No = '" . $serial_no . "'");
	foreach ( $result as $val ) {
		$commencement_date = $val->Subs_DateFrom;
		$delivery_frequency = $val->Char_Every;
		$monthly_delivery = $val->Delivery_Fees;
		$monthly_order_total = $val->TotalSubs_Amount;
		if ( $val->Choco > 0 ) {
			$quantity = $val->Choco;
			$capsules = $capsules . "<tr><td>Choco</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Amico > 0 ) {
			$quantity = $val->Amico;
			$capsules = $capsules . "<tr><td>Coffee - Amico</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->InLove > 0 ) {
			$quantity = $val->InLove;
			$capsules = $capsules . "<tr><td>Coffee - In Love</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Lonely > 0 ) {
			$quantity = $val->Lonely;
			$capsules = $capsules . "<tr><td>Coffee - Lonely</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Luna > 0 ) {
			$quantity = $val->Luna;
			$capsules = $capsules . "<tr><td>Coffee - Luna</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Passion > 0 ) {
			$quantity = $val->Passion;
			$capsules = $capsules . "<tr><td>Coffee - Passion</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Sunrise > 0 ) {
			$quantity = $val->Sunrise;
			$capsules = $capsules . "<tr><td>Coffee - Sunrise</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Milk > 0 ) {
			$quantity = $val->Milk;
			$capsules = $capsules . "<tr><td>Milk</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Earl > 0 ) {
			$quantity = $val->Earl;
			$capsules = $capsules . "<tr><td>Tea - Earl</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->King > 0 ) {
			$quantity = $val->King;
			$capsules = $capsules . "<tr><td>Tea - The King</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Prince > 0 ) {
			$quantity = $val->Prince;
			$capsules = $capsules . "<tr><td>Tea - Prince</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Princess > 0 ) {
			$quantity = $val->Princess;
			$capsules = $capsules . "<tr><td>Tea - Princess</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Queen > 0 ) {
			$quantity = $val->Queen;
			$capsules = $capsules . "<tr><td>Tea - The Queen</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
	}
	$param = $param . "%7C" . date( 'Y-m-d', strtotime($commencement_date) ) . "%7C" . $delivery_frequency . "%7C" . 
		$delivery_frequency . "%7C" . number_format( $monthly_fees, 2 ) . "%7C" . number_format( ( $delivery_frequency * $monthly_fees ), 2 ) . "%7C" . 
		$capsules . "%7C" . 
		"1" . "%7C" . number_format( $monthly_delivery, 2 ) . "%7C" . number_format( $monthly_delivery, 2 ) . "%7C" . 
		number_format( $monthly_order_total, 2 );
		
	$ini_capsules = "";
	$initial_delivery = "";
	$initial_order_total = "";
	$result = $wpdb->get_results("SELECT * FROM ct_coffee_sharing_order WHERE Subscription_No = '" . $serial_no . "'");
	foreach ( $result as $val ) {
		$initial_delivery = $val->Delivery_Fees;
		$initial_order_total = $val->Total_Amount;
		if ( $val->Choco > 0 ) {
			$quantity = $val->Choco;
			$ini_capsules = $ini_capsules . "<tr><td>Choco</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Amico > 0 ) {
			$quantity = $val->Amico;
			$ini_capsules = $ini_capsules . "<tr><td>Coffee - Amico</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->InLove > 0 ) {
			$quantity = $val->InLove;
			$ini_capsules = $ini_capsules . "<tr><td>Coffee - In Love</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Lonely > 0 ) {
			$quantity = $val->Lonely;
			$ini_capsules = $ini_capsules . "<tr><td>Coffee - Lonely</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Luna > 0 ) {
			$quantity = $val->Luna;
			$ini_capsules = $ini_capsules . "<tr><td>Coffee - Luna</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Passion > 0 ) {
			$quantity = $val->Passion;
			$ini_capsules = $ini_capsules . "<tr><td>Coffee - Passion</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Sunrise > 0 ) {
			$quantity = $val->Sunrise;
			$ini_capsules = $ini_capsules . "<tr><td>Coffee - Sunrise</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Milk > 0 ) {
			$quantity = $val->Milk;
			$ini_capsules = $ini_capsules . "<tr><td>Milk</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Earl > 0 ) {
			$quantity = $val->Earl;
			$ini_capsules = $ini_capsules . "<tr><td>Tea - Earl</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->King > 0 ) {
			$quantity = $val->King;
			$ini_capsules = $ini_capsules . "<tr><td>Tea - The King</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Prince > 0 ) {
			$quantity = $val->Prince;
			$ini_capsules = $ini_capsules . "<tr><td>Tea - Prince</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Princess > 0 ) {
			$quantity = $val->Princess;
			$ini_capsules = $ini_capsules . "<tr><td>Tea - Princess</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
		if ( $val->Queen > 0 ) {
			$quantity = $val->Queen;
			$ini_capsules = $ini_capsules . "<tr><td>Tea - The Queen</td><td align='center'>" . ( $quantity / $capsule_per_box ) . "</td><td align='right'>" . 
				number_format( ( $capsule_price * $capsule_per_box ), 2 ) . "</td><td align='right'>" . number_format( ( $quantity * $capsule_price ), 2 ) . "</td></tr>";
		}
	}
	$param = $param . "%7C" . "1" . "%7C" . number_format( $monthly_fees, 2 ) . "%7C" . number_format( $monthly_fees, 2 ) . "%7C" . 
		"1" . "%7C" . number_format( $deposit, 2 ) . "%7C" . number_format( $deposit, 2 ) . "%7C" . 
		$ini_capsules . "%7C" . 
		"1" . "%7C" . number_format( $initial_delivery, 2 ) . "%7C" . number_format( $initial_delivery, 2 ) . "%7C" . 
		number_format( $initial_order_total, 2 );
	
	$param = str_replace( '&', '%26', $param );
	//$email = "kf_lee@nepholdings.com.my";
	if ( $cc == "" ) {
		$cc = "kf_lee@nepholdings.com.my";
	}
	$url = "http://203.198.208.217:8081/ArisstoHK/Gmail?subject=ARISSTO&from=do-not-reply@arissto.com.hk&from_name=ARISSTO&mail_to=" . $email . 
		"&cc_to=" . $cc . "&template=Subscription_Order&param=" . $param . "&application_name=hk ipartner email id";
	$url = str_replace( ' ', '%20', $url );
	$curl = curl_init();
	curl_setopt ($curl, CURLOPT_URL, $url);
	curl_exec ($curl);
	curl_close ($curl);
}

add_shortcode('subscription_order_payment', 'subscription_order_payment');
function subscription_order_payment( ) {
	$ref = $_REQUEST['ref'];
	$amt = $_REQUEST['amt'];
	
	if( !empty( $ref ) && ( !empty( $amt ) && is_numeric( $amt ) ) ) {
		$amt = number_format( (float)$amt, 2, '.', '' );
		$detail =  '<table style="width: 233px; height: 169px;">
				<tr><td style="vertical-align: middle; text-decoration: underline;" colspan="3"><h2><strong>付款明細</strong></h2></td></tr>
				<tr>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">參考編號</span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">： </span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">' . $ref . '</span></td>
				</tr>
				<tr>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">總計金額</span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">：</span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">HK$ ' . $amt . '</span></td>
				</tr>
			</table>
			<p></p>
			<p style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">謝謝您的訂購，請點擊“立即付款”繼續。</p>';

		echo $detail;
		echo firstdata_payment_form( "recurring", $ref, $amt, "sale", "https://arissto.com/hk/subscription-order-payment-success/", "https://arissto.com/hk/subscription-order-payment-fail/" );
	} else {
		echo 'Missing info ...';
	}
}

add_shortcode('subscription_order_payment_result', 'subscription_order_payment_result');
function subscription_order_payment_result ( ) {
	$order_no = $_REQUEST["oid"];
	$response_code = $_REQUEST["processor_response_code"];
	$approval_code = $_REQUEST["approval_code"];
	$status = $_REQUEST["status"];
	$cardnumber = $_REQUEST["cardnumber"];
	$chargetotal = $_REQUEST["chargetotal"];
	$hosteddataid = $_REQUEST["hosteddataid"];
	$bname = $_REQUEST["bname"];
	$expyear = $_REQUEST["expyear"];
	$expmonth = $_REQUEST["expmonth"];
	$ccbrand = $_REQUEST["paymentMethod"];//ccbrand
	$result = $_REQUEST;
	
	$file = fopen( "ct_online_payment_sub.txt", "a" );
	fwrite( $file, json_encode( $result ) . "\n" );
	fclose( $file );
	
	global $wpdb;
	$wpdb->query( "INSERT INTO `ct_online_payment_sub` ( `Order_No`, `Response_Code`, `Result`, `Approval_Code` ) 
		VALUES ( '" . $order_no . "', '" . $response_code . "', '" . json_encode( $result ) . "', '" . $approval_code . "' );" );
	if ( !empty( $response_code ) && $response_code == '00' ) {
		$short_approval_code = explode( ":", $approval_code )[1];
		echo '<h2 style="text-align: left;"><strong>已收到付款</strong></h2>
			<p style="text-align: left;"><strong>謝謝，您的付款已交易成功。</strong></p>
			<p style="text-align: left;">
				參考編號: ' . $order_no . '<br/>
				金額 : HK$ ' . $chargetotal . '<br/>
				確認號碼: ' . $short_approval_code . '<br/>
				信用卡最後四個數字：' . $cardnumber;
		$wpdb->query( "INSERT INTO `ct_firstdata_token` ( `Reference_No`, `Token`, `CC_No`, `CC_Name`, `CC_Exp_Year`, `CC_Exp_Month` ) 
			VALUES ( '" . $order_no . "', '" . $hosteddataid . "', '" . $cardnumber . "', '" . $bname . "', '" . $expyear . "', '" . $expmonth . "' );" );
		send_sub_email( $order_no, $order_no );
		
		$order = $wpdb->get_results( "SELECT * FROM ct_integration_subscription_order WHERE form_id = 62 AND serial_no = '" . $order_no . "'" );
		foreach ( $order as $val ) {
			$url = $val->call_Value;
			$last4digit = substr( $cardnumber, -4 );
			$expiryDate = $expmonth . "/" . substr( $expyear, -2 );
			$wpdb->query( "UPDATE ct_coffee_sharing_order SET Payment_Status = 'Complete', Card_No = '" . $last4digit . "', Card_Expiry = '" . $expiryDate . "', 
				Modified_Date = CURRENT_TIMESTAMP WHERE Payment_Status = 'Pending' AND Subscription_No = '" . $order_no . "'" );
			$wpdb->query( "UPDATE ct_coffee_sharing_cc SET Status = 'A' WHERE Reference_No = '" . $order_no . "'" );
			$wpdb->query( "UPDATE ct_tran_loc SET Status = 'C' WHERE Reference_No = '" . $order_no . "' AND Status = 'P'" );
			$url = str_replace( 'cc_name', $bname, $url );
			$url = str_replace( 'cc_expiry', $expiryDate, $url );
			$url = str_replace( 'cc_type', $ccbrand, $url );
			$url = str_replace( 'approval_code', $short_approval_code, $url );
			$url = str_replace( 'receipt_date', date( "Y-m-d" ), $url );
			$url = $url . "&param90=" . $last4digit . "&param91=" . $hosteddataid;
			//$url = str_replace( 'param50=test', 'param50=HK300001', $url );
			//$url = str_replace( 'AOC00000031', 'test_2', $url );
			$curl = curl_init();
			curl_setopt ($curl, CURLOPT_URL, $url);
			curl_exec ($curl);
			curl_close ($curl);
			break;
		}
	}
}

function firstdata_payment_form( $store, $ref, $amt, $txn_type, $success_url, $fail_url ) {
	$url = "https://www4.ipg-online.com/connect/gateway/processing";
	if( $store == "recurring" ) {
		$storeId = "4720052824";
		$sharedSecret = "h9A5xx6umQ";
	} else if( $store == "e-comm" ) {
		$storeId = "4720052822";
		$sharedSecret = "g5Vy3kBwtx";
	} else if( $store == "test" ) {
		$url = "https://test.ipg-online.com/connect/gateway/processing";//test
		$storeId = "4700000193";
		$sharedSecret = "S2wNC7e7Gm";
	}
	$currency = "344";
	$payment_date = new DateTime( "now", new DateTimeZone( "Asia/Hong_Kong" ) );
	$txn_datetime = $payment_date->format( "Y:m:d-H:i:s" );
	$stringToHash = $storeId . $txn_datetime . $amt . $currency . $sharedSecret;
	$ascii = bin2hex( $stringToHash );
	$hashKey =  hash( "sha256", $ascii );
	
	$form = '<form method="post" action="' . $url . '">
		<input type="hidden" name="checkoutoption" value="combinedpage">
		<input type="hidden" name="txntype" value="' . $txn_type . '">
		<input type="hidden" name="timezone" value="Asia/Hong_Kong"/>
		<input type="hidden" name="txndatetime" value="' . $txn_datetime . '"/>
		<input type="hidden" name="hash_algorithm" value="SHA256"/>
		<input type="hidden" name="hash" value="' . $hashKey . '"/>
		<input type="hidden" name="storename" value="' . $storeId . '"/>
		<input type="hidden" name="mode" value="payonly"/>
		<input type="hidden" name="chargetotal" value="' . $amt . '"/>
		<input type="hidden" name="currency" value="' . $currency . '"/>
		<input type="hidden" name="oid" value="' . $ref . '"/>
		<input type="hidden" name="assignToken" value="true"/>
		<input type="hidden" name="responseSuccessURL" value="' . $success_url . '"/>
		<input type="hidden" name="responseFailURL" value="' . $fail_url . '"/>
		<input type="submit" value="立即付款">
	</form>';
	
	return $form;
}

add_shortcode('free_trial_payment', 'free_trial_payment');
function free_trial_payment( ) {
	$ref = $_REQUEST['ref'];
	
	if( !empty( $ref ) ) {
		$detail =  '<table style="width: 233px; height: 169px;">
				<tr><td style="vertical-align: middle; text-decoration: underline;" colspan="3"><h2><strong>付款明細</strong></h2></td></tr>
				<tr>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">參考編號</span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">： </span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">' . $ref . '</span></td>
				</tr>
				<tr>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">總計金額</span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">：</span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">HK$ 1</span></td>
				</tr>
			</table>
			<p></p>
			<p style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">謝謝您的訂購，請點擊“立即付款”繼續。</p>';

		echo $detail;
		echo firstdata_payment_form( "recurring", $ref, "1", "sale", "https://arissto.com/hk/free-trial-payment-success/", "https://arissto.com/hk/free-trial-payment-fail/" );
	} else {
		echo 'Missing info ...';
	}
}

add_shortcode('free_trial_payment_result', 'free_trial_payment_result');
function free_trial_payment_result ( ) {
	$order_no = $_REQUEST["oid"];
	$response_code = $_REQUEST["processor_response_code"];
	$approval_code = $_REQUEST["approval_code"];
	$status = $_REQUEST["status"];
	$cardnumber = $_REQUEST["cardnumber"];
	$chargetotal = $_REQUEST["chargetotal"];
	$hosteddataid = $_REQUEST["hosteddataid"];
	$bname = $_REQUEST["bname"];
	$expyear = $_REQUEST["expyear"];
	$expmonth = $_REQUEST["expmonth"];
	$result = $_REQUEST;
	
	global $wpdb;
	$wpdb->query( "INSERT INTO `ct_online_payment_firstdata` ( `Order_No`, `Response_Code`, `Result`, `Approval_Code` ) 
		VALUES ( '" . $order_no . "', '" . $response_code . "', '" . json_encode( $result ) . "', '" . $approval_code . "' );" );
	if ( !empty( $response_code ) && $response_code == '00' ) {
		echo '<h2 style="text-align: left;"><strong>已收到付款</strong></h2>
			<p style="text-align: left;"><strong>謝謝，您的付款已交易成功。</strong></p>
			<p style="text-align: left;">
				參考編號: ' . $order_no . '<br/>
				金額 : HK$ ' . $chargetotal . '<br/>
				確認號碼: ' . explode( ":", $approval_code )[1] . '<br/>
				信用卡最後四個數字：' . $cardnumber;
		$wpdb->query( "INSERT INTO `ct_firstdata_token` ( `Reference_No`, `Token`, `CC_No`, `CC_Name`, `CC_Exp_Year`, `CC_Exp_Month` ) 
			VALUES ( '" . $order_no . "', '" . $hosteddataid . "', '" . $cardnumber . "', '" . $bname . "', '" . $expyear . "', '" . $expmonth . "' );" );
	}
}

add_shortcode('firstdata_payment_test', 'firstdata_payment_test');
function firstdata_payment_test( ) {
	$ref = $_REQUEST['ref'];
	$amt = $_REQUEST['amt'];
	
	if( !empty( $ref ) && ( !empty( $amt ) && is_numeric( $amt ) ) ) {
		$cur_date = new DateTime( "now", new DateTimeZone( "Asia/Hong_Kong" ) );
		$txn_datetime = $cur_date->format( "Y:m:d-H:i:s" );
		$ref = $ref . "_" . $txn_datetime;
		$amt = number_format( (float)$amt, 2, '.', '' );
		
		$detail =  '<table style="width: 233px; height: 169px;">
				<tr><td style="vertical-align: middle; text-decoration: underline;" colspan="3"><h2><strong>付款明細</strong></h2></td></tr>
				<tr>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">參考編號</span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">： </span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">' . $ref . '</span></td>
				</tr>
				<tr>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">總計金額</span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">：</span></td>
					<td style="vertical-align: middle; padding: 3px;"><span style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">HK$ ' . $amt . '</span></td>
				</tr>
			</table>
			<p></p>
			<p style="color: #000000; font-size: 15px;" data-darkreader-inline-color="">謝謝您的訂購，請點擊“立即付款”繼續。</p>';

		echo $detail;
		echo firstdata_payment_form( "recurring", $ref, $amt, "sale", "https://arissto.com/hk/first-data-payment-test-result/", "https://arissto.com/hk/first-data-payment-test-result/" );
		
	} else {
		echo 'Missing info ...';
	}
}

add_shortcode( 'firstdata_payment_test_result', 'firstdata_payment_test_result' );
function firstdata_payment_test_result ( ) {
	$order_no = $_REQUEST["oid"];
	$response_code = $_REQUEST["processor_response_code"];
	$approval_code = $_REQUEST["approval_code"];
	$status = $_REQUEST["status"];
	$cardnumber = $_REQUEST["cardnumber"];
	$chargetotal = $_REQUEST["chargetotal"];
	$hosteddataid = $_REQUEST["hosteddataid"];
	$bname = $_REQUEST["bname"];
	$expyear = $_REQUEST["expyear"];
	$expmonth = $_REQUEST["expmonth"];
	$result = $_REQUEST;
	
	global $wpdb;
	$wpdb->query( "INSERT INTO `ct_online_payment_firstdata` ( `Order_No`, `Response_Code`, `Result`, `Approval_Code` ) 
		VALUES ( '" . $order_no . "', '" . $response_code . "', '" . json_encode( $result ) . "', '" . $approval_code . "' );" );
	if ( !empty( $response_code ) && $response_code == '00' ) {
		echo '<h2 style="text-align: left;"><strong>已收到付款</strong></h2>
			<p style="text-align: left;"><strong>謝謝，您的付款已交易成功。</strong></p>
			<p style="text-align: left;">
				參考編號: ' . $order_no . '<br/>
				金額 : HK$ ' . $chargetotal . '<br/>
				確認號碼: ' . explode( ":", $approval_code )[1] . '<br/>
				信用卡最後四個數字：' . $cardnumber . '</p>';
		$wpdb->query( "INSERT INTO `ct_firstdata_token` ( `Reference_No`, `Token`, `CC_No`, `CC_Name`, `CC_Exp_Year`, `CC_Exp_Month` ) 
			VALUES ( '" . $order_no . "', '" . $hosteddataid . "', '" . $cardnumber . "', '" . $bname . "', '" . $expyear . "', '" . $expmonth . "' );" );
	} else {
		echo '<h2 style="text-align: left;"><strong>無法完成交易</strong></h2>';
	}
}

//ca portal --add by sengfung
add_shortcode('show_CA_code', 'display_CA_code');
function display_CA_code ($atts) {
	global $wpdb;

	$current_user = wp_get_current_user();
	$code = get_user_meta( $current_user->ID, 'Partner_Code', true );

	return $code;
}

add_shortcode('show_user_name', 'display_user_name');
function display_user_name ($atts) {
	global $wpdb;
	
	$current_user = wp_get_current_user();
	$name = $current_user->user_login;

	return $name;
}

add_shortcode('show_referral', 'display_referral');
function display_referral ($atts) {
	global $wpdb;
	
	$current_user = wp_get_current_user();
	$email = $current_user->user_email;

	$refcode = $wpdb->get_var("SELECT Ref_Code from ct_partners where Email = '$email' limit 1;");

	return $refcode;
}

add_shortcode('show_CA_name', 'display_CA_name');
function display_CA_name ($atts) {
	global $wpdb;

	$current_user = wp_get_current_user();
	$name = $current_user->first_name. " " . $current_user->last_name;

	return $name;
}

add_shortcode('show_partner_phone', 'display_partner_phone');
function display_partner_phone ($atts) {
	global $wpdb;
	
	$current_user = wp_get_current_user();
	$phone = get_user_meta( $current_user->ID, 'author_gplus', true );

	return $phone;
}

add_shortcode('show_user_email', 'display_user_email');
function display_user_email ($atts) {
	global $wpdb;
	
	$current_user = wp_get_current_user();
	$email = $current_user->user_email;

	return $email;
}

add_shortcode('show_join_date', 'display_create_date');
function display_create_date ($atts) {
	global $wpdb;
	
	$current_user = wp_get_current_user();
	$email = $current_user->user_email;

	$refcode = $wpdb->get_var("SELECT Create_Date from ct_partners where Email = '$email' limit 1;");

	return $refcode;
}

add_shortcode('show_ipartner_portal', 'display_ipartner_portal');
function display_ipartner_portal ($atts) {
	$user = wp_get_current_user();

	if ($user->has_cap('ipartner')) {
		return '<a href="https://arissto.com/hk/ipartner-portal/" style="font-family:Century Gothic;" class="fusion-button button-flat fusion-button-pill button-medium button-default button-1" target="_self"><i class="fa fa-long-arrow-right button-icon-right"> iPARTNER PORTAL</i></a>';
	}

}


add_shortcode('login_validation', 'login_validation');
function login_validation() {
	if ( !is_user_logged_in() ) {
		wp_redirect('https://arissto.com/hk/my-account/');
		exit();
	} 
}

add_filter( 'woocommerce_login_redirect', 'wc_redirect_login_user', 10, 3 );
function wc_redirect_login_user( $redirect, $user ) {
	$redirect = $_REQUEST['redirect'];
	if ( isset( $redirect ) ) {
		return $redirect;
	}
	
	
	if ($user->has_cap('ipartner')) {
		wp_redirect('https://arissto.com/hk/my-account/ipartner/');
	} else if ($user->has_cap('affiliate')) {
		return __('/hk/ca-portal/', 'woocommerce');
	} else {
		return __('/hk', 'woocommerce');
	}
}

//add by sengfung - HK portal edit profile
add_filter('gform_validation_72', 'validate_ca_pwd' );
function validate_ca_pwd( $validation_result ) {
	$form = $validation_result["form"];
	$current_user = wp_get_current_user();
	$password = rgpost( "input_6" );
	if ( !($current_user && wp_check_password( $password, $current_user->data->user_pass, $current_user->ID )) ) {
		$validation_result['is_valid'] = false;
		$form['fields'][0]->failed_validation = true;
		$form['fields'][0]->validation_message = "Wrong Password.";
	}

	$validation_result['form'] = $form;
	return $validation_result;
}

add_action( 'gform_after_submission_72', 'update_ca_pwd' );
function update_ca_pwd($entry, $form) {
	$current_user = wp_get_current_user();
	$pwd = $entry["5"];
	wp_set_password( $pwd, $current_user->ID );
}

add_filter( 'gform_pre_render_73', 'ca_edit_profile' );
function ca_edit_profile( $form ) {
	$current_user = wp_get_current_user();
	$phone = get_user_meta( $current_user->ID, 'author_gplus', true );
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function() {
		jQuery( '#input_73_5' ).val( "<?php echo $current_user->first_name?>" );
		jQuery( '#input_73_6' ).val( "<?php echo $current_user->last_name?>" );
		jQuery( '#input_73_2' ).val( "<?php echo $current_user->user_email?>" );
		jQuery( '#input_73_3' ).val( "<?php echo $phone?>" );
	});
	</script>
	<?php
	return $form;
	
}

add_action( 'gform_after_submission_73', 'update_ca_profile');
function update_ca_profile($entry, $form) {
	global $wpdb;
	$current_user = wp_get_current_user();
	$code = get_user_meta( $current_user->ID, 'Partner_Code', true );
	
	$firstname = $entry["5"];
	$firstname = str_replace( "'", "''", $firstname );
	$lastname = $entry["6"];
	$lastname = str_replace( "'", "''", $lastname );
	$fullname = $firstname . ' ' . $lastname;
	$phone = $entry["3"];
	$phone = str_replace('(','',$phone);
	$phone = str_replace(')','',$phone);
	$phone = str_replace(' ','',$phone);
	$phone = str_replace('-','',$phone);
	$email = $entry["2"];
	
	wp_update_user( array( 'ID' => $current_user->ID, 'first_name' => $firstname ) );
	wp_update_user( array( 'ID' => $current_user->ID, 'last_name' => $lastname ) );
	//wp_update_user( array( 'ID' => $current_user->ID, 'user_email' => $email ) );
	update_user_meta( $current_user->ID, 'author_gplus', $phone );
	
	//$wpdb -> query( "UPDATE `ct_partners` SET Name='".$fullname."',Email='".$email."',Contact='".$phone."' WHERE Partner_Code='".$code."'" );
	$wpdb -> query( "UPDATE `ct_partners` SET Name='".$fullname."',Contact='".$phone."' WHERE Partner_Code='".$code."'" );
	//$wpdb -> query( "UPDATE `ct_partners` SET Ref_Email='".$email."' WHERE Ref_Code='".$code."'" );
}

//add by sf 040620 pasasword field start
add_filter( 'gform_enable_password_field', '__return_true' );
//add by sf 040620 pasasword field end

/*
add_shortcode('show_sub_online', 'display_sub_online');
function display_sub_online ($atts) {

	return '<a href="https://arissto.com/hk/subscription-order-form/?ref=' . wp_get_current_user()->id . '" target="_self"><img src="https://arissto.com/hk/wp-content/uploads/SG1-Subscription-Online-Order-Form.jpg"/></a>';
}

add_shortcode('show_CA_link', 'display_CA_application');
function display_CA_application ($atts) {

	$user = wp_get_current_user();
	$code = get_user_meta( $user->ID, "Partner_Code", true );

	return '<a href="https://arissto.com/hk/arissto-ca-application/?ref=' . wp_get_current_user()->user_login . '" target="_self"><img src="https://arissto.com/hk/wp-content/uploads/CA-Application.jpg"/></a>';
}


add_shortcode('show_workshop_link', 'display_workshop_application');
function display_workshop_application ($atts) {

	return '<a href="https://arissto.com/hk/arissto-workshop-application/?ref=' . wp_get_current_user()->user_login . '" target="_self"><img src="https://arissto.com/hk/wp-content/uploads/Workshop-Application.png"/></a>';
	
}


add_shortcode('show_training_link', 'display_training_application');
function display_training_application ($atts) {

	return '<a href=" https://arissto.com/hk/arissto-training-application/?ref=' . wp_get_current_user()->user_login . '" target="_self"><img src="http://arissto.com/hk/wp-content/uploads/training-application-1.jpg"/></a>';
}

add_shortcode('show_coff_individual_link', 'display_individual_application');
function display_individual_application ($atts) {

	return '<a href="#' . wp_get_current_user()->user_login . '" target="_self"><img src="http://arissto.com/hk/wp-content/uploads/rm1-individual.jpg"/></a>';
}

add_shortcode('show_coff_corporate_link', 'display_corporate_application');
function display_corporate_application ($atts) {

	return '<a href="#' . wp_get_current_user()->user_login . '" target="_self"><img src="http://arissto.com/hk/wp-content/uploads/rm1-corporate.jpg"/></a>';
} */

add_shortcode('show_sub_online', 'display_sub_online');
function display_sub_online ($atts) {
	return '<center><a href="https://arissto.com/hk/subscription-order-form/?ref=' . wp_get_current_user()->id . '" target="_blank"><img src="https://arissto.com/hk/wp-content/uploads/SG1-Subscription-Online-Order-Form.jpg"/></a><p style="text-align: center;">HK Subscription Form ( 市區 )</p><a href="https://arissto.com/hk/subscription-order-form/?delivery_zone=District&ref=' . wp_get_current_user()->id . '" target="_blank"><img src="https://arissto.com/hk/wp-content/uploads/SG1-Subscription-Online-Order-Form.jpg"/></a><p style="text-align: center;">HK Subscription Form ( 離島區 )</p></center>';
}

add_shortcode('20_free_trial_2020', 'display_free_trial');
function display_free_trial ($atts) {

	return '<a href="https://arissto.com/hk/20-free-trial-2020/?ref=' . wp_get_current_user()->user_login . '" target="_self"><img src="http://arissto.com/hk/wp-content/uploads/rm1-corporate.jpg"/></a>';
}



add_shortcode('my_partners', 'show_my_partners');
function show_my_partners ($atts) {
	global $wpdb;

	$current_user = wp_get_current_user();
	$email = $current_user->user_email;
	$code = get_user_meta( $current_user->ID, 'Partner_Code', true );
	
	if(is_user_logged_in() && !empty($code)) {
		//$sc_form = '[wpdatatable id=4 var1='.$email.']';
		$sc_form = '[wpdatatable id=4 var1='.$code.']';
		return do_shortcode($sc_form);
	} else {
		return 'Please login proper account'; 
	}

}


add_shortcode('affiliate_form_hk', 'affiliate_form_hk');
function affiliate_form_hk ($atts) {
	global $wpdb;
	$username = $_REQUEST['ref'];
	$user = get_user_by('login', $username );
	$code = get_user_meta( $user->ID, 'Partner_Code', true );
	

	if(!empty($code)) {
		$sc_form = '[gravityform id=68 title=false description=false ajax=false field_values="partnercode='.$code.'&refemail='.$user->user_email.'&ref='.$username.'"]';
		
		return do_shortcode($sc_form);
		
		
	} else {
		return 'There is no reference code.';
	}
}




add_filter('gform_validation_68', 'validate_ca_email_68' );
function validate_ca_email_68( $validation_result ) {
	global $wpdb;

	$form = $validation_result["form"];

	$name = rgpost("input_29");
	$name1 = preg_match("/^[a-zA-Z -]*$/",$name);
					
	$email = rgpost("input_1");
	
	$email1 = preg_match ("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $email);
	
	$email_exist = $wpdb->get_var("SELECT user_email from cv_partner WHERE user_email ='".$email."'");
	$exists = email_exists( $email );
	
	
	if(!empty($email1)){
		if (!empty($email_exist)) {
		$validation_result['is_valid'] = false;
		$form['fields'][4]->failed_validation = true;
		$form['fields'][4]->validation_message = "The email has been registered.";
		}
		else if ($exists) {
		$validation_result['is_valid'] = false;
		$form['fields'][4]->failed_validation = true;
		$form['fields'][4]->validation_message = "Invalid email.";
		}	
		
	} else {
		
		$validation_result['is_valid'] = false;
		$form['fields'][4]->failed_validation = true;
		$form['fields'][4]->validation_message = "Email cannot empty.";
		
	}
	
	

	if ($name1 === 0) {
			$validation_result['is_valid'] = false;
			$form['fields'][2]->failed_validation = true;
			$form['fields'][2]->validation_message = "Name must as in HK identity card";
		}
		
		
	
	$validation_result['form'] = $form;
	return $validation_result;
}



add_action( 'gform_after_submission_68', 'insert_CA_68');
function insert_CA_68($entry) {
	global $wpdb;

	$membership = $entry["40"];
	$sub = $entry["41"];
	if (empty($membership)) {
		$memberno = $sub;
	}
	if (empty($sub)) {
		$memberno = $membership;
	} 
	$name = $entry["29"];
	$nric = $entry["25"];
	$email = $entry["1"];
	$contact = $entry["8"];
	$addr1 = $entry["12"];
	$addr2 = $entry["32"];
	$city = $entry["33"];
	$postcode = $entry["34"];
	$bank = $entry["44"];
	$bankname = $entry["45"];
	$bankacc = $entry["46"];
	$code = $entry["36"];
	$duplicate = $entry["37"];
	//$member = $entry["42"];
	$password = $entry["51"];

	$ref_username = $entry["17"];
	$ref_email = $entry["18"];
	$ref_code = $entry["48"];
	
	
	
	if($email){
		$exists = email_exists( $email );
		$user = get_user_by( 'email', $email );
		if ($exists) {
			if (get_user_meta($user->ID, 'Partner_Code', true)) {

			} else {
				add_user_meta($user->ID, 'Partner_Code', $code);
			}
			$user->add_role( 'wpc_client' );
			$user->add_role( 'affiliate' );

			//$password = $phone;
			//wp_set_password( $password, $user->ID );

		} else {
			$userdata = array(
				'user_login' =>  $email,
				'user_email' =>  $email,
				'user_pass'  =>  $password,
				'first_name' =>  $name,
				'last_name'  =>  $name,
				'role' => 'Affiliate'
			);

			$user_id = wp_insert_user( $userdata );

			if (get_user_meta($user_id, 'Partner_Code', true)) {

			} else {
				add_user_meta($user_id, 'Partner_Code', $code);
			}
			$user = get_user_by( 'email', $email );
			$user->add_role( 'wpc_client' );
			$user->add_role( 'affiliate' );

		}
		
		
		$aff_args = array (
			'Environment'=> 'SGPRD',
			'distno' => $code, 
			'name' => $name,
			'nric' => $nric, 
			'email' => $email, 
			'contact' => $contact, 
			'referral' => $ref_code, 
			'addr1' => $addr1,
			'addr2' => $addr2,
			'city' => $city,
			'postcode' => $postcode,
			'bank' => $bank,
			'bankname' => $bankname,
			'bankacc' => $bankacc
		);

		$strparam = array();
		foreach($aff_args as $key=>$value1) {
			$strparam[] = $key."=".urlencode($value1);
		}

		$strValue = implode('&', $strparam);
	
	/*
		$request = "http://system.nepdiamond.com/nepws/htmlcall.asmx/SGCAInsert?".$strValue."";
		curl_get_contents($request);
		$wpdb->query("INSERT INTO `ct_integration` (`call_Value`) VALUES ('".$request."')");
	
*/
		
		
	} 


	$wpdb -> query( "INSERT INTO `ct_partners` (`Partner_Code`, `Name`, `NRIC`, `Email`, `Contact`, `Address_1`, `Address_2`, `city`, `Postcode`, `Bank`, `Bank_Acc_No`, `Bank_Acc_Name`, `Ref_Username`, `Ref_Email`, `Ref_Code`, `form_id`) VALUES ('".$code."','".$name."','".$nric."','".$email."','".$contact."','".$addr1."','".$addr2."','".$city."','".$postcode."','".$bank."','".$bankacc."','".$bankname."','".$ref_username."','".$ref_email."','".$ref_code."','68')");
		
		
	
}

add_shortcode('workshop_form', 'show_workshop_form');
function show_workshop_form ($atts) {
	$username = $_REQUEST['ref'];
	if (empty($username)) {
		$current_user = wp_get_current_user();
		$username = $current_user->user_login;
	}
	$username = str_replace( '%2540', '@', $username );
	$username = str_replace( '%40', '@', $username );
	$username = str_replace( '+', ' ', $username );
	$user = get_user_by('login', $username );
	$fullname = $user->first_name . ' ' . $user->last_name;
	$user_id = $user->ID;
	$code = get_user_meta( $user_id, 'Partner_Code', true );

	if ($username == 'tp://arissto.com/hk/arissto-workshop/') {
		$username = 'DIRECTHK';
		$code = 'DIRECTHK';
	} else {
		$username = $username;
		$code = $code;
	}

	$sc_form = '[gravityform id=69 title=false description=false ajax=false field_values="refemail='.$user->user_email.'&refname='.$fullname.'&partnercode='.$code.'"]';
	return do_shortcode($sc_form);
}

add_shortcode('training_form', 'show_training_form');
function show_training_form ($atts) {
	$username = $_REQUEST['ref'];
	if (empty($username)) {
		$current_user = wp_get_current_user();
		$username = $current_user->user_login;
	}
	$username = str_replace( '%2540', '@', $username );
	$username = str_replace( '%40', '@', $username );
	$username = str_replace( '+', ' ', $username );
	$user = get_user_by('login', $username );
	$fullname = $user->first_name . ' ' . $user->last_name;
	$user_id = $user->ID;
	$code = get_user_meta( $user_id, 'Partner_Code', true );

	if ($username == 'tp://arissto.com/hk/ca-training/') {
		$username = 'DIRECTHK';
		$code = 'DIRECTHK';
	} else {
		$username = $username;
		$code = $code;
	}

	$sc_form = '[gravityform id=70 title=false description=false ajax=false field_values="refemail='.$user->user_email.'&refname='.$fullname.'&partnercode='.$code.'"]';
	return do_shortcode($sc_form);
}


add_shortcode('free_20_trial', 'free_20_trial');
function free_20_trial ($atts) {
	global $wpdb;
	
	$partneremail = $_REQUEST['ref'];
	
	$result = $wpdb->get_var("SELECT Partner_Code FROM ct_partners WHERE Email = '".$partneremail."' limit 1");
	
	$ref_name =$wpdb->get_var("SELECT Name FROM ct_partners WHERE Email = '".$partneremail."' limit 1");
	
	
	
	if(!empty($partneremail) || !empty($result)) {
		$sc_form = '[gravityform id=63 title=false description=false field_values="refcode='.$result.'&refname='.$ref_name.'"]';

		return do_shortcode($sc_form);
	} else {
		$html ='<p style="margin-top: 20px; margin-bottom: 50px; text-align: center;font-size:20px;"><strong>Something is missing in this url..Please contact support.</strong></p>';
		
		echo $html;
	}
}

add_filter('gform_validation_63', 'validate_form_63' );
function validate_form_63( $validation_result ) {
	global $wpdb;
	
	$form = $validation_result["form"];
	
	$phone = rgpost("input_3");
	$email= rgpost("input_4");
	
	$duplicate_email = $wpdb->get_var("SELECT Email FROM ct_free_trial where Email='".$email."'");
	$Created_Date = $wpdb->get_var("SELECT Created_Date FROM `ct_free_trial` where Email='".$email."' ");
	
	if(!is_numeric($phone)) {
		$validation_result['is_valid'] = false;
		$form['fields'][1]->failed_validation = true;
		$form['fields'][1]->validation_message = "聯繫號碼必須是數字";
	}
	
	
	$email1 = preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $email);
    if(!empty($email1)) {
		if($duplicate_email) {
			$validation_result['is_valid'] = false;
			$form['fields'][2]->failed_validation = true;
			$form['fields'][2]->validation_message = "電子郵件已被註冊，註冊日期: ".$Created_Date."";	
		}
	} else {
		$validation_result['is_valid'] = false;
		$form['fields'][2]->failed_validation = true;
		$form['fields'][2]->validation_message = "電子郵件格式錯誤";
    }
		$validation_result['form'] = $form;
	return $validation_result;
}

add_action( 'gform_after_submission_63', 'submit_form_63');
function submit_form_63($entry) {
	global $wpdb;
	
	$name = $entry["2"];
	$phone = $entry["3"];
	$email = $entry["4"];
	$addr = $entry["9"];
	$district = $entry["14"];
	$refname =$entry["5"];
	$refcode = $entry["6"];
	$payment_code =$entry["16"];
	
	
	$wpdb->query( "INSERT INTO `ct_free_trial` (`Name`, `Phone`, `Email`, `Address`, `District`,  `Ref_Name`, `Ref_Code`, `Unique_ID`) VALUES ('".$name."','".$phone."','".$email."','".$addr."','".$district."','".$refname."','".$refcode."','".$payment_code."')");
	
}

add_shortcode('subscriptionform_log', 'subscriptionform_log');
function subscriptionform_log ($atts) {
	$url = 'http://203.198.208.217:8081/ArisstoHK_Test/SubscriptionFormLog.jsp';
	//echo file_get_contents( $url );
	$response = wp_remote_get( $url );
	if ( is_wp_error( $response ) ) {
		echo 'ERROR!!!';
	} else {
		echo $response[body];
	}
}

// Hello -GZ

add_shortcode('sw_tan_test', 'sw_tan_test');
function sw_tan_test(){
	echo "Hello World";

}