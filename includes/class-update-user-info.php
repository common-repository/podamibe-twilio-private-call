<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if accessed directly */
}

class POD_Twilio_Update_User_Info {
	/*
	*	@private
	*	instance of global $wpdb for accessing database
	*/
	private $_db;
	
	/**
	*	@private
	*	@array
	*	stores submited user info
	*/
	private $_postdatas;
	
	/*
	*	@var integer
	*	@private
	*	user id 
	*/
	private $_id;
	
	/**
	*	@private
	*	@array
	*	return message after update
	*/
	private $_message = array();
	
	/**
	*	A reference to an instance class POD_TWILIO_SUPPORTED_COUNTRIES.
	* 	@private
	* 	@var   POD_TWILIO_SUPPORTED_COUNTRIES
	*/
	private $_supported_countries;
	
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
	
	public function __construct( $twilio_user ){
		global $wpdb;
		$this->_db = $wpdb;
		$this->_twilio_user = $twilio_user;
		$this->_id = $this->_twilio_user->twilio_user_details["ID"];
		$this->_supported_countries = new POD_Twilio_Supported_Countries();
		
		if( isset( $_POST["pod_twilio_update_user_info"] ) ){			
			$this->_postdatas = $_POST["twilio_user"];	
			$this->_user_country_info = $this->_supported_countries->get_country_info_by_country_id( $this->_postdatas["country_id"] );
			/* concat country code to phone number */
			$this->_user_phone_number = $this->_user_country_info->country_code . $this->_postdatas["user_phone_number"];
			$this->_postdatas["user_phone_number"] = $this->_user_phone_number;
			
			/* for checking user phone number */
			$this->_create_twilio_account = new POD_Twilio_Create_Twilio_Account( $this->_postdatas, $this->_user_phone_number, $this->_user_country_info );
		}	
	}
	
	/*
	*	this method is used to update user info
	*	checks if phone number is valid using twilio lookup service
	*	updates user info if valid phone number
	*	returns success status array
	*	@param null
	*	@return array
	*/
	public function update_twilio_user(){
		if( isset( $this->_postdatas ) ){
			do_action("pod_twilio_before_user_update", $this );
			
			$check_phone = $this->_create_twilio_account->check_phone_number();	

			if( $check_phone["status"] == "error" ){
				$this->_message["status"] = $check_phone["status"];
				$this->_message["message"] = $check_phone["message"];
				
				do_action( "pod_twilio_on_invalid_phone_number_update", $this );
			}
			else{
				$this->update_user();					
			}
		}
		return $this->_message;
	}
	
	/*
	*	user user info
	*	sets create successfull message
	*	@return void
	*/
	public function update_user(){	
		$userInfo = $this->_postdatas;		
		if( $this->_db->update( POD_TWILIO_USERS, $userInfo, array( "user_id" => $this->_id ) ) ){
			$this->_message = array(
				"status" 	=> "success",
				"message" 	=> apply_filters( "pod_twilio_user_update_successful_message", __( "Updated successfully", POD_TWILIO_TEXT_DOMAIN ) )
			);
			
			do_action("pod_twilio_on_user_update_success", $this, $user_info );
		}
		else{
			$this->_message = array(
				"status" 	=> "error",
				"message" 	=> apply_filters( "pod_twilio_user_update_unsuccessful_message",__( "Oops! Error Occured !", POD_TWILIO_TEXT_DOMAIN) )
			);
			
			do_action("pod_twilio_on_user_update_fail", $this, $user_info );
		}
	}
	
}