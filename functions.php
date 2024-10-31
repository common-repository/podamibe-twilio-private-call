<?php

if ( ! defined( 'ABSPATH' ) ) {
exit; // Exit if accessed directly
}

/**
*	generate paginate links
*	use wordpress function paginate_links
*	@params integer $number_of_pages
*	@param integer $current_page
**/
function pod_twilio_paginate_links( $num_of_pages, $current_page ){
	$page_links = paginate_links( array(
		'base' 		=> add_query_arg( 'pagenum', '%#%' ),
		'format' 	=> '',
		'end_size'	=> 1,
		'mid_size'	=> 2,
		'prev_text' => '&laquo;',
		'next_text' => '&raquo;',
		'total' 	=> $num_of_pages,
		'current' 	=> $current_page
	) );
	
	if ( $page_links ) {
		if(is_admin()){
			echo '<div class="tablenav">
					<div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div>
				</div>';
		}
		else{
			echo '<div class="pod-twilio-pagination">'.$page_links.'</div>';
		}
	}
}

/**
*	count total rows in a table
*	@param string $table
*	@parm $where (default: array())
*	@return array
**/
function pod_twilio_count_total_table_rows( $table, $where=array() ){
	global $wpdb;
	$sql = "SELECT COUNT(*) FROM $table";
	if( !empty( $where ) ) {
		$sql .= " WHERE ";
		$i = 0;
		foreach( $where as $key=>$value ) {
			if( $i == 0 ){
				$sql .= "{$key} = '{$value}'";
			}
			else{
				$sql .= " AND ". "{$key} = '{$value}'";
			}
			$i++;
		}
	}
	$total = $wpdb->get_var( $sql );
	return $total;
}

/**
*	format given array for select dropdown
*	@param $value i.e. the value of the dropdown
*	@param $show i.e. the value to display
*	@param array
*	@return array
**/

function pod_format_array_for_dropdown( $value, $show, $array ){
	$return_array = array();
	foreach( $array as $arr ){
		$return_array[$arr[$value]] = $arr[$show];
	}
	return $return_array;
}

/**
*	verify nonce for giiven nonce key
*	@param nonce_key
*	return bool
*/
function pod_verify_nonce( $nonce_key, $nonce_field = null ){
	if( ! $nonce_field  ){
		$wp_nonce = $_REQUEST["_wpnonce"];
	}
	else{
		$wp_nonce = $_REQUEST[$nonce_field];
	}
	return wp_verify_nonce( $wp_nonce, $nonce_key );
}

/**
*	format call duration in format hr min sec
**/
function pod_twilio_format_call_duration( $time_in_sec ){
	$mins = intval( $time_in_sec/60 );
	$hrs = intval( $mins/60 );
	$sec = $time_in_sec%60;
	$mins = $mins - $hrs*60;
	
	$time_in_hr_min_sec = $hrs . ($hrs>1 ? " hrs ":" hr ");						
	$time_in_hr_min_sec .= $mins.($mins>1 ? " mins" : " min") . " and ".$sec;			
	$time_in_hr_min_sec .= $sec>1 ? " secs": " sec";
	
	return $time_in_hr_min_sec;
}

/**
*	get twilio user by twilio number
*	@param string - twilio number
*	@return POD_Twilio_User
**/
function pod_twilio_get_user_by_twilio_number( $twilio_number ){	
	global $wpdb;
	
	if( $twilio_number ){
		$user_id = $wpdb->get_var( "SELECT user_id FROM ". POD_TWILIO_USERS . " WHERE twilio_phone_number='{$twilio_number}'" );
		if( $user_id ){
			$user = new POD_Twilio_User( $user_id );
			
			return apply_filters( "pod_twilio_get_user_by_twilio_number", new POD_Twilio_User( $user_id ), $twilio_number );
		}
		else{
			return NULL;
		}
	}
	else{
		return null;
	}
}

/**
*	get twilio user by user phone number
*	@param string - user phone number
*	@return POD_Twilio_User
**/
function pod_twilio_get_user_by_phone_number( $phone_mumber ){
	global $wpdb;
	if( $phone_mumber ){
		$user_id = $wpdb->get_var("SELECT user_id FROM ". POD_TWILIO_USERS ." WHERE user_phone_number='{$phone_mumber}'");

		if( $user_id ){
			return apply_filters( "pod_twilio_get_user_by_twilio_number", new POD_Twilio_User( $user_id ), $phone_mumber );
		}
		else{
			return NULL;
		}
	}
	else{
		return null;
	}
}

function pod_twilio_get_user_by_account_sid( $account_sid ){
	global $wpdb;
	if( $account_sid ){
		$user_id = $wpdb->get_var( "SELECT user_id FROM ". POD_TWILIO_USERS . " WHERE twilio_sub_account_sid='{$account_sid}'" );
		
		if( $user_id ){
			return apply_filters( "pod_twilio_get_user_by_account_sid", new POD_Twilio_User( $user_id ), $phone_mumber );
		}
		else{
			return NULL;
		}
		
	}
	else{
		return null;
	}
}
/**
*	add a currency sign to the amount
*	@param number - amount
*	@return void
**/
function pod_twilio_amount_with_currency( $value ){	
	$currency = get_option( "pod_twilio_payment_settings" );
	echo sprintf( "%s%0.2f", $currency["paypal_currency_symbol"],  $value );
}

/**
*	send contact request to users
*	@param integer - user id
**/
function pod_twilio_add_to_contact_list( $request_receiver_id ){
	if( defined("DOING_AJAX") && DOING_AJAX ){		//if request performed through ajax method
		$request_receiver_id = $_POST["request_user_id"];
	}

	$request_handler = new POD_TWilio_Contact_Request_Handler( $request_receiver_id );
	
	$result = $request_handler->send_contact_request();
	
	do_action( "pod_twilio_add_to_contact_list", $request_handler, $result );
	
	if( defined("DOING_AJAX") && DOING_AJAX ){
		echo json_encode( $result );
		die();
	}
	else{
		return $request;
	}
}

/**
*	send contact request to users
**/
function pod_twilio_accept_contact_request( $request_user_id, $reject = false ){
	if( defined("DOING_AJAX") && DOING_AJAX ){		//if request performed through ajax method
		$request_user_id = $_POST["request_user_id"];
		$reject = ( isset( $_POST["reject"] ) && ( $_POST["reject"] == "true" ) );
	}
	
	$request_handler = new POD_TWilio_Contact_Request_Handler( $request_user_id );
	
	$result = $request_handler->accept_contact_request( $reject );
	
	do_action( "pod_twilio_accept_contact_request", $request_handler, $result );
	
	if( defined("DOING_AJAX") && DOING_AJAX ){
		echo json_encode( $result );
		die();
	}
	else{
		return $result;
	}
}

/**
*	get more verified user contacts
*	@param $limit, $offset
*	called using wp ajax
**/
function pod_twilio_load_more_verified_contacts( $limit, $offset ) {
	if( defined("DOING_AJAX") && DOING_AJAX ){
		$limit = $_POST["limit"];
		$offset = $_POST["offset"];
	}
	
	$user = new POD_Twilio_User( get_current_user_id() );
	
	$user_contacts = $user->user_contacts->get_verified_contact_list( $limit, $offset );
	
	if( $user_contacts ){
		$result_count = count( $user_contacts );
		$result = array(
			"count" => $result_count,
			"contacts" => $user_contacts,
			"message" => __( $result_count, POD_TWILIO_TEXT_DOMAIN ) . __(" contacts found.", POD_TWILIO_TEXT_DOMAIN )
		);

		if( defined("DOING_AJAX") && DOING_AJAX ){
			$html = "";
			foreach( $user_contacts as $contact ){
				if( $user->_id == $contact->request_sender_id ){
					$contact_user = new POD_Twilio_User( $contact->request_receiver_id );
				}
				else{
					$contact_user = new POD_Twilio_User( $contact->request_sender_id );
				}
				if( $user->user_contacts->get_contact_status( $contact ) == "Verified" ){
					$html .= '<ul>
						<li class="user-details">'.
							get_avatar( $contact_user->_id, 50 ).'&nbsp;'. 
							$contact_user->get_user_name() .
							'<div>'.
								$contact_user->user_country .
							'</div>
						</li>
						<li class="twilio-phone">
							<i class="fa fa-phone"></i>&nbsp;'.$contact_user->twilio_number .
						'</li>
						<li class="remove-twilio-contact">
							<button data-user-id="'.$contact_user->_id . '">'. esc_html__( "Remove", POD_TWILIO_TEXT_DOMAIN ) . '</button>
						</li>
					</ul>';
				}				
			}
	
			$result["contact_details_html"] = $html;
		}
	}
	else{
		$result = array(
			"count" => 0,
			"contacts" => null,
			"message" => esc_html__( "No more results found.", POD_TWILIO_TEXT_DOMAIN )
		);
		
		if( defined("DOING_AJAX") && DOING_AJAX ){
			$result["contact_details_html"] = "<ul><li>" . __( "No more Results found.", POD_TWILIO_TEXT_DOMAIN ) . "</li></ul>";
		}
	}
	
	if( defined( "DOING_AJAX" ) && DOING_AJAX ){
		echo json_encode( $result );
		die();
	}
	else{
		return $result;
	}
}

/**
*	get more verification pending user contacts
*	@param $limit, $offset
*	called using wp ajax
**/
function pod_twilio_load_more_pending_contacts( $limit, $offset ) {
	if( defined( "DOING_AJAX" ) && DOING_AJAX ){
		$limit = $_POST["limit"];
		$offset = $_POST["offset"];
	}
	
	$user = new POD_Twilio_User( get_current_user_id() );
	
	$user_contacts = $user->user_contacts->get_pending_contacts_list( $limit, $offset );
	
	if( $user_contacts ){
		$result_count = count( $user_contacts );
		$result = array(
			"count" => $result_count,
			"contacts" => $user_contacts,
			"message" => __( $result_count, POD_TWILIO_TEXT_DOMAIN ) . __(" contacts found.", POD_TWILIO_TEXT_DOMAIN )
		);

		if( defined("DOING_AJAX") && DOING_AJAX ){
			$html = "";
			foreach( $user_contacts as $contact ){
				if( $user->_id == $contact->request_sender_id ){
					$contact_user = new POD_Twilio_User( $contact->request_receiver_id );
				}
				else{
					$contact_user = new POD_Twilio_User( $contact->request_sender_id );
				}
				
				$html .= '<ul>
					<li class="user-details">'.
						get_avatar( $contact_user->_id, 50 ).'&nbsp;'. 
						$contact_user->get_user_name() .
						'<div>'.
							$contact_user->user_country .
						'</div>
					</li>
					<li class="contact-request-action">'.
						$user->user_contacts->action_button( $contact, $contact_user )
					.'</li>
				</ul>';
			}
	
			$result["contact_details_html"] = $html;
		}
	}
	else{
		$result = array(
			"count" => 0,
			"contacts" => null,
			"message" => esc_html__( "No more results found.", POD_TWILIO_TEXT_DOMAIN )
		);
		
		if( defined( "DOING_AJAX" ) && DOING_AJAX ){
			$result["contact_details_html"] = "<ul><li>" . __( "No more Results found.", POD_TWILIO_TEXT_DOMAIN ) . "</li></ul>";
		}
	}
	
	if( defined( "DOING_AJAX" ) && DOING_AJAX ){
		echo json_encode( $result );
		die();
	}
	else{
		return $result;
	}
}

function pod_twilio_remove_contact( $request_user_id ){
	if( defined( "DOING_AJAX" ) && DOING_AJAX ){		//if request performed through ajax method
		$request_user_id = $_POST["request_user_id"];
	}
	
	$request_handler = new POD_TWilio_Contact_Request_Handler( $request_user_id );
	
	$result = $request_handler->remove_user_from_contact();
	
	if( defined( "DOING_AJAX" ) && DOING_AJAX ){
		echo json_encode( $result );
		die();
	}
	else{
		return $result;
	}
}

function pod_twilio_verify_contact( $contact_user_id ){
	if( defined( "DOING_AJAX" ) && DOING_AJAX ){		//if request performed through ajax method
		$contact_user_id = $_POST["contact_user_id"];
	}
	
	$request_handler = new POD_TWilio_Contact_Request_Handler( $contact_user_id );
	
	$result = $request_handler->verify_contact_request();
	
	if( defined( "DOING_AJAX" ) && DOING_AJAX ){
		echo json_encode( $result );
		die();
	}
	else{
		return $result;
	}
}


function pod_twilio_check_number_verification_status( $contact_user_id ){
	if( defined( "DOING_AJAX" ) && DOING_AJAX ){		//if request performed through ajax method
		$contact_user_id = $_POST["contact_user_id"];
	}
	
	$request_handler = new POD_TWilio_Contact_Request_Handler( $contact_user_id );
	
	$result = $request_handler->check_contact_verified();
	
	if( defined( "DOING_AJAX" ) && DOING_AJAX ){
		echo json_encode( $result );
		die();
	}
	else{
		return $result;
	}
}

/*
*	array_column function for php version < 5.5
*/
if ( ! function_exists( 'array_column' ) ) {
    function array_column( array $input, $columnKey, $indexKey = null ) {
        $array = array();
        foreach( $input as $value ) {
            if ( !array_key_exists( $columnKey, $value ) ) {
                trigger_error( "Key \"$columnKey\" does not exist in array" );
                return false;
            }
            if ( is_null( $indexKey ) ) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( !array_key_exists( $indexKey, $value ) ) {
                    trigger_error( "Key \"$indexKey\" does not exist in array" );
                    return false;
                }
                if ( ! is_scalar( $value[$indexKey] ) ) {
                    trigger_error( "Key \"$indexKey\" does not contain scalar value" );
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }
}