<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class POD_Twilio_User {
	
	/*
	*	@private
	*	instance of global $wpdb for accessing database
	*/
	private $_db;	
	
	/*
	*	@var integer
	*	@public
	*	user id 
	*/
	public $_id;
	
	/*
	*	@array
	*	@private
	*	user data of given id from wordpress
	*/	
	private $_userdata;
	
	/**
	*	@array
	*	@public
	*	user data and twilio user data of given user id
	**/	
	public $twilio_user_details;
	
	/**
	*	@var boolean
	*	@public
	*	check given user has twilio activated or not
	**/
	public $has_twilio_activated;
	
	/**
	*	@var string
	*	@public
	*	remaining bought call duration
	**/
	public $remaining_call_duration;
	
	/**
	*	user contacts
	* 	@public
	* 	@var   POD_Twilio_User_Contacts
	*/
	public $user_contacts;
	
	/**
	*	Constructor of this class
	**/
	public function __construct( $user_id ){
		global $wpdb;
		$this->_db = $wpdb;
		$this->_id = $user_id;
		$this->_userdata = (array)get_userdata( $user_id );		
		$this->has_twilio_activated = $this->has_twilio_activated();
		if( $this->has_twilio_activated  ){
			$this->user_contacts = new POD_Twilio_User_Contacts( $this->_id );
			$this->twilio_user_details = $this->get_user_details();		
			$this->twilio_number = $this->twilio_user_details["twilio_phone_number"];		
			$this->phone_number = $this->twilio_user_details["user_phone_number"];
			$this->account_sid = $this->twilio_user_details["twilio_sub_account_sid"];
			$this->account_auth_token = $this->twilio_user_details["twilio_sub_account_auth_token"];
			$this->user_country = $this->twilio_user_details["country_name"];			
		}
		$this->remaining_call_duration = $this->get_remaining_call_duration();
	}
	
	/**
	*	get twilio user details
	*	@param $null
	*	@return user details object
	*/	
	public function get_user_details() {
		$sql = "SELECT * FROM ". POD_TWILIO_USERS ." AS TW_USERS LEFT JOIN " . POD_TWILIO_CALL_DURATION ." AS CALL_DURATION ON TW_USERS.user_id = CALL_DURATION.user_id JOIN " . POD_TWILIO_SUPPORTED_COUNTRIES. " AS COUNTRY ON TW_USERS.country_id = COUNTRY.country_id WHERE TW_USERS.user_id=".$this->_id;
		
		$twilio_user = $this->_db->get_row( $sql, ARRAY_A );
		
		if( ! $twilio_user ){
			$twilio_user = array();
		}
		
		$twilio_user = array_merge( $this->_userdata, $twilio_user );
		
		return apply_filters( "pod_twilio_get_user_details", $twilio_user, $this->_id );
	}
	
	/**
	*	get username
	*	@public
	*	@return string
	*/	
	public function get_user_name(){
		return apply_filters( "pod_twilio_get_user_name", $this->_userdata["data"]->user_login, $this->_userdata );
	}
	
	/**
	*	check user has twilio account
	*	@public
	*	@return boolean
	**/
	public function has_twilio_activated(){
		
		$sql = "SELECT count( * ) FROM ". POD_TWILIO_USERS . " WHERE user_id=". $this->_id ;
		
		$activated = $this->_db->get_var( $sql );
		
		return apply_filters( "pod_twilio_has_twilio_activated", $activated );
		
	}
	
	/**
	*	get remaining call duration
	*	@public
	*	@return string
	**/
	public function get_remaining_call_duration(){
		if( $this->has_twilio_activated ){
			$remaining_duration = $this->twilio_user_details["remaining_call_duration"];
		}
		else{
			$query = "SELECT remaining_call_duration FROM ". POD_TWILIO_CALL_DURATION ."  WHERE user_id=".$this->_id;
			
			$remaining_duration = $this->_db->get_var( $query );
		}
		
		if( ! $remaining_duration || $remaining_duration <= 0 ){
			$remaining_duration = 0;
		}
		
		return apply_filters( "pod_twilio_get_remaining_call_duration", $remaining_duration, $this );
	}
	
	/**
	*	update call duration after call ends
	*	@public
	*	@return string
	**/
	public function update_remaining_call_duration( $call_duration ){
		$user_total_call_duration = $this->remaining_call_duration;
		$remaining_call_duration = $user_total_call_duration - $call_duration;
		
		if($remaining_call_duration < 0){
			$remaining_call_duration = 0;
		}
		
		$remaining_call_duration = apply_filters( "pod_twilio_update_remaining_call_duration", $remaining_call_duration, $this, $call_duration );
		
		do_action( "pod_twilio_update_remaining_call_duration", $this, $call_duration, $remaining_call_duration );
		
		if( $this->_db->update( 
				POD_TWILIO_CALL_DURATION, 
				array(
					"remaining_call_duration" => $remaining_call_duration 
				),
				array( 
					"user_id" => $this->_id 
				) 
			) 
		){
			
			$this->twilio_user_details["remaining_call_duration"] = $remaining_call_duration;
			$this->remaining_call_duration = $remaining_call_duration;
			
			do_action( "pod_twilio_on_update_remaining_call_duration", $this, $call_duration, $remaining_call_duration );
			
			return true;
		}
		else{
			return NULL;
		}
	}
	
	/**
	*	buy call duration
	*	@public
	*	@return string
	**/
	public function buy_call_duration( $call_duration_mins, $paid_amount, $payment_method ){
		$payment_date = date( 'Y-m-d' );
		
		$this->_db->insert( 
			POD_TWILIO_PAYEMENT_HISTORY,
			array(
				"user_id" 			=> $this->_id,
				"payment_date" 		=> $payment_date,
				"amount" 			=> $paid_amount,
				"payment_method" 	=> $payment_method,
				"bought_call_time" 	=> ( $call_duration_mins*60 )
			) 
		);
		
		$remaining_call_duration = $this->remaining_call_duration;
		
		if( ! $this->_has_call_duration_inserted() ){
			$this->_db->insert( 
				POD_TWILIO_CALL_DURATION, 
				array(
					"user_id" => $this->_id,
					"remaining_call_duration" => ( $call_duration_mins*60 ) 
				) 
			);		
		}
		else{
			$this->_db->update(
				POD_TWILIO_CALL_DURATION, 
				array(
					"remaining_call_duration" => ( ( $call_duration_mins * 60) + $remaining_call_duration )
				),
				array(
					"user_id" => $this->_id
				)
			);							
		}
		do_action( "pod_twilio_buy_call_duration", $this, $call_duration_mins, $remaining_call_duration );
	}
	
	private function _has_call_duration_inserted(){
		$sql = "SELECT COUNT(*) FROM " . POD_TWILIO_CALL_DURATION . " WHERE user_id=". $this->_id;
		$inserted = $this->_db->get_var( $sql );
		return apply_filters( "pod_twilio_has_payment_history", $inserted > 0, $this );
	}
	/**
	*	check user has payment history
	*	@public
	*	@return boolean
	**/
	public function has_payment_history(){
		$sql = "SELECT COUNT(*) FROM " . POD_TWILIO_PAYEMENT_HISTORY . " WHERE user_id=". $this->_id;
		$has_payment_history = $this->_db->get_var( $sql );
		return apply_filters( "pod_twilio_has_payment_history", $has_payment_history > 0 , $this );
	}
}