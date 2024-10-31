<?php

global $wpdb;

$verification_status = isset( $_REQUEST['VerificationStatus'] ) ? $_REQUEST['VerificationStatus']:"";

if( $verification_status == "success" ){
	$verified_number = isset( $_REQUEST['Called'] ) ? $_REQUEST['Called']:"";
	
	$verified_ac_sid = isset( $_REQUEST['AccountSid'] ) ? $_REQUEST['AccountSid']:"";
	
	$verified_caller_id_sid = isset( $_REQUEST['OutgoingCallerIdSid'] ) ? $_REQUEST['OutgoingCallerIdSid']:"";
	
	$verified_user = pod_twilio_get_user_by_twilio_number( $verified_number );
	
	$verified_to_user = pod_twilio_get_user_by_account_sid( $verified_ac_sid );
	
	if( $verified_user && $verified_to_user ){
		$contact_request = new POD_TWilio_Contact_Request_Handler( $verified_to_user->_id, $verified_user->_id );
		if( $contact->contact_request->contact_exists ){
			$update_array = array();
			$contact = $contact_request->contact;
			if( $verified_user->_id == $contact->request_receiver_id && $verified_to_user->_id == $contact->request_sender_id ){
				$update_array = array(
					"receiver_caller_id_sid" => $verified_caller_id_sid
				);
			}
			else if( $verified_user->_id == $contact->request_sender_id && $verified_to_user->_id == $contact->request_receiver_id ){
				$update_array = array(
					"sender_caller_id_sid" => $verified_caller_id_sid
				);
			}
			else{
				die( __( "Error Occured ! ", POD_TWILIO_TEXT_DOMAIN ) );
			}
			
			$wpdb->update( POD_TWILIO_CONTACT_LIST, $update_array, array( "contact_id" => $contact->contact_id ) );
		}
	}
}
else{
	die( __( "Verification Error Occured !", POD_TWILIO_TEXT_DOMAIN ) );
}