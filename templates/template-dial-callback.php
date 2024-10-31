<?php
/*
*	@package Twilio Private Call
*	@callback page on call completion
*	This template is called after call completion and updates call duration remaining
*/	
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/* caller's number */
$caller_number = isset( $_REQUEST['From'] ) ? $_REQUEST['From']:"";
/* called twilio number */
$called_number = isset( $_REQUEST['To'] ) ? $_REQUEST['To']:"";
/* call status */
$call_status = isset( $_REQUEST['DialCallStatus'] ) ? $_REQUEST['DialCallStatus']:"";

if( $call_status == "completed" ){
	/* caller user information using caller twilio number */
	$caller_user = pod_twilio_get_user_by_phone_number( $caller_number );

	$called_user = pod_twilio_get_user_by_twilio_number( $called_number );
	if( $caller_user ){
		$call_duration = apply_filters( "pod_twilio_total_call_duration", isset( $_REQUEST['DialCallDuration'] ) ? $_REQUEST['DialCallDuration']:0, $call_status, $caller_user, $called_user );
			
		if( $caller_user->update_remaining_call_duration( $call_duration ) ){
			echo "updated";
		}
		else{
			echo "update error";
		}
	}
	else{
		echo "no user";
	}
}
else{
	echo $call_status;
}
