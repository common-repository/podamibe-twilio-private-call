<?php
/*
*	@package Twilio Private Call
*	@paypal ipn page after buying call duration using payal
*	This template is called by paypal for IPN after user purchases call duration
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$raw_post_data = file_get_contents('php://input');

$raw_post_array = explode('&', $raw_post_data);

$myPost = array();
$raw_post_data = file_get_contents('php://input');

$raw_post_array = explode('&', $raw_post_data);

$myPost = array();

foreach ($raw_post_array as $keyval) {
  $keyval = explode ('=', $keyval);
  if (count($keyval) == 2)
     $myPost[$keyval[0]] = urldecode($keyval[1]);
}

$req = 'cmd=_notify-validate';
if(function_exists('get_magic_quotes_gpc')) {
   $get_magic_quotes_exists = true;
} 
foreach ($myPost as $key => $value) {        
   if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) { 
        $value = urlencode(stripslashes($value)); 
   } else {
        $value = urlencode($value);
   }
   $req .= "&$key=$value";
}

$payment_settings = get_option( "pod_twilio_payment_settings" );

if( isset( $payment_settings["paypal_sanbox_mode"] ) ){
	$endpoint_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';   // sandbox paypal url
}
else{
	$endpoint_url = 'https://www.paypal.com/cgi-bin/webscr';     // paypal url
}

$ch = curl_init( $endpoint_url );
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

if( !($res = curl_exec($ch)) ) {
    curl_close($ch);
    exit;
}

if (strcmp ($res, "VERIFIED") == 0) {
	if( (isset($_POST['payment_status'])) && $_POST['payment_status']=='Completed' && isset($_GET['action']) && $_GET['action']=='ipn'){	
		$call_duration = $_REQUEST['custom'];
		$payment_amount = $_REQUEST["mc_gross"];
		$pay_member_id = isset($_REQUEST['item_number']) ? $_REQUEST['item_number'] : '';
		$pay_user = new POD_Twilio_User( $pay_member_id );		
		
		do_action( "pod_twilio_before_buy_call_duration", $pay_user, $call_duration );
		$pay_user->buy_call_duration( $call_duration, $payment_amount, "paypal" );
		
	}
}