<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if accessed directly */
}

class POD_Twilio_User_Register {
	
	/*
	*	@private
	*	instance of global $wpdb for accessing database
	*/
	private $_db;
	
	/**
	*	@private
	*	@array
	*	stores submited user data when creating twilio ac
	*/
	private $_postdatas;
	
	/**
	*	@private
	*	@var POD_Twilio_User
	*	A reference to the class POD_Twilio_User
	**/
	private $_twilio_user;
	
	/*
	*	@var integer
	*	@private
	*	user id 
	*/
	private $_id;
	
	/**
	*	@private
	*	@array
	*	return message after activation
	*/
	private $_message = array();
	
	/**
	*	A reference to an instance class POD_TWILIO_SUPPORTED_COUNTRIES.
	* 	@private
	* 	@var   POD_TWILIO_SUPPORTED_COUNTRIES
	*/
	private $_supported_countries;
	
	/**
	*	A reference to an instance class POD_Twilio_Create_Twilio_Account.
	* 	@private
	* 	@var   POD_Twilio_Create_Twilio_Account
	*/
	private $_create_twilio_account;
	
	/*
	*	user phone number
	* 	@private
	*/
	private $_user_phone_number;
	
	/**
	*	user country info
	*	@private
	**/
	private $_user_country_info;
	
	/**
	*	constructor to this class
	**/
	public function __construct( $twilio_user ){
		global $wpdb;
		$this->_db = $wpdb;
		$this->_twilio_user = $twilio_user;
		$this->_id = $this->_twilio_user->twilio_user_details["ID"];
		$this->_supported_countries = new POD_Twilio_Supported_Countries();
		if( isset( $_POST["pod_twilio_activate_twilio"] ) ){			
			$this->_postdatas = $_POST["twilio_user"];	
			$this->_user_country_info = $this->_supported_countries->get_country_info_by_country_id( $this->_postdatas["country_id"] );
			/* concat country code to phone number */
			$this->_user_phone_number = $this->_user_country_info->country_code . $this->_postdatas["user_phone_number"];
			$this->_create_twilio_account = new POD_Twilio_Create_Twilio_Account( $this->_postdatas, $this->_user_phone_number, $this->_user_country_info );
		}		
	}
	
	/*
	*	this method is used to create twilio user
	*	checks if phone number is valid using twilio lookup service and registers user to twilio
	*	returns success status array
	*	@param null
	*	@return array
	*/
	public function create_twilio_user(){
		
		if( isset( $this->_postdatas ) ){
			do_action("pod_twilio_before_user_registration", $this );
		
			$check_phone = $this->_create_twilio_account->check_phone_number();		
			
			if( $check_phone["status"] == "error" ){
				$this->_message["status"] = $check_phone["status"];
				$this->_message["message"] = $check_phone["message"];
				do_action( "pod_twilio_on_invalid_phone_number_register", $this );
			}
			else{
				$this->register_user();					
			}
		}
		return $this->_message;
	}
	
	/*
	*	registers user in twilio
	*	create subaccount in twilio
	*	sets create successfull message
	*	@return void
	*/
	public function register_user(){		
		$userInfo = $this->_postdatas;		
		$userInfo["user_id"] = $this->_id;	
		$userInfo["user_phone_number"] = $this->_user_phone_number;
		$twilio_phone_number = $this->_create_twilio_account->get_twilio_number();
		if( $twilio_phone_number["status"] == "success" ){
			$twilio_number = $twilio_phone_number["phone_number"];
		
			$twilio_sub_account = $this->_create_twilio_account->create_twilio_subaccount( $twilio_number, $this->_twilio_user->get_user_name() );
			
			if( $twilio_sub_account["status"] == "success" ){
				$subaccount = $twilio_sub_account["sub_account"];
				$number = $twilio_sub_account["number"];
				$userInfo["user_id"] = get_current_user_id();				
				$userInfo["twilio_phone_number"] = $number->phoneNumber;
				$userInfo["twilio_phone_number_sid"] = $number->sid;
							
				$userInfo["twilio_sub_account_sid"] = $subaccount->sid;
				$userInfo["twilio_sub_account_auth_token"] = $subaccount->authToken;
				$userInfo["friendly_name"] = $subaccount->friendlyName;
				
				$userInfo = apply_filters( "pod_twilio_user_activation_data", $userInfo );
				
				$this->_save_user_information( $userInfo );
			}
			else{
				$this->_message = $twilio_sub_account;
			}			
		}
		else{
			$this->_message = $twilio_phone_number;
		}	
		
	}
	
	/**
	*	save user info into database
	*	@param array
	*	@return void
	**/
	private function _save_user_information( $user_info ){
		if( $this->_db->insert( POD_TWILIO_USERS, $user_info ) ){
			$this->_message = array(
				"status" 	=> "success",
				"message" 	=> apply_filters( "pod_twilio_register_user_successful_message", __( "Activated successfully", POD_TWILIO_TEXT_DOMAIN ) )
			);
			
			do_action("pod_twilio_on_user_registration_success", $this, $user_info );
		}
		else{
			$this->_message = array(
				"status" 	=> "error",
				"message" 	=> apply_filters( "pod_twilio_register_user_unsuccessful_message",__( "Oops! Error Occured !", POD_TWILIO_TEXT_DOMAIN) )
			);
			
			do_action("pod_twilio_on_user_registration_fail", $this, $user_info );
		}
	}
}