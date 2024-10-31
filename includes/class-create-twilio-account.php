<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Twilio\Rest\Client;

class POD_Twilio_Create_Twilio_Account {
	/*
	*	@var string
	*	@private
	*	Master twilio account SID
	*/
	private $_master_accountSID;
	
	/*
	*	@var string
	*	@private
	*	Master twilio account auth token
	*/		
	private $_master_authToken;
	
	/**
	*	@private
	*	@array
	*	posted user data
	*/
	private $_postdatas;	
	
	/*
	*	posted user phone number
	* 	@private
	*/
	private $_user_phone_number;
	
	/**
	*	posted user country info
	*	@private
	**/
	private $_user_country_info;
	
	/**
	*	@private
	*	@array
	*	twilio admin settings / master account settings
	*/
	private $_twilio_settings;
	
	/**
	*	A reference to an instance class Lookups_Services_Twilio.
	*	used to check valid number using twilio look up service
	* 	@private
	* 	@var   Lookups_Services_Twilio
	*/
	private $_twilio_lookup;
	
	/**
	*	A reference to an instance class Services_Twilio.
	* 	@private
	* 	@var   Services_Twilio
	*/
	private $_twilio_master_account;
	
	/**
	*	voice url associated with twilio number
	*	@private
	*	@string
	**/
	private $_voice_url;
	
	/**
	*	constructor of this class
	**/
	public function __construct( $postdatas, $user_phone_number, $user_country_info ){
		$this->_postdatas = $postdatas;
		$this->_user_phone_number = $user_phone_number;
		$this->_user_country_info = $user_country_info;
		$this->_twilio_settings = get_option( "pod_twilio_master_ac_settings" );
		$this->_master_accountSID = $this->_twilio_settings['master_twilio_account_sid'];
		$this->_master_authToken = $this->_twilio_settings['master_twilio_account_auth_token'];
		$this->_master_account = new Client($this->_master_accountSID, $this->_master_authToken);
		$this->_voice_url = apply_filters( "pod_twilio_twilio_request_url", get_page_link( get_option("pod_twilio_dial_number_page_id")) );
	}
	
	/**
	*	check user submitted phone number
	*	uses twilio lookup service to check validity of phone number
	*	@return array
	**/
	public function check_phone_number(){
		$return = array();
		try{
			$number = $this->_master_account->lookups
											->phoneNumbers( $this->_user_phone_number )
											->fetch(
												array(
														"CountryCode" => $this->_user_country_info->country_iso_code
													)
											);
			$return["status"] = "success";
			$return["message"] = apply_filters( "pod_twilio_valid_phone_number_message", _( "Valid Phone Number", POD_TWILIO_TEXT_DOMAIN ), $number );
			$return["phone_number"] = $number->phoneNumber;
			do_action( "pod_twilio_check_phone_number", $number, $this->_user_phone_number, $this->_master_account );
		}
		catch(Exception $e){
			$return["status"] = "error";
			$return["message"] = __( $e->getMessage(), POD_TWILIO_TEXT_DOMAIN );
		}
		return apply_filters( "pod_twilio_check_phone_number", $return, $this->_master_account,$this->_user_phone_number );
	}
	
	/**
	*	get twilio phone number
	*	get a random phone number form available number list in twilio
	*	@return array
	**/
	public function get_twilio_number(){
		$return = array();
		try{
			$numbers = $this->_master_account
								->availablePhoneNumbers( $this->_user_country_info->country_iso_code )
								->local
								->read();
			$count_available_phone_numbers = count( $numbers );
			$number_index = rand(0,--$count_available_phone_numbers);
			$twilio_number = $numbers[$number_index]->phoneNumber;		//get a random phone number from available phone number
			
			$return["status"] = "success";
			$return["message"] = __( "Successful", POD_TWILIO_TEXT_DOMAIN );
			$return["phone_number"] = apply_filters( "pod_twilio_get_twilio_phone_number", $twilio_number, $this->_master_account );
			do_action( "pod_twilio_get_twilio_phone_number", $numbers, $twilio_number, $this->_master_account );			
		}
		catch(Exception $e){
			$return["status"] = 'error';
			$return["message"] = __( $e->getMessage(), POD_TWILIO_TEXT_DOMAIN );
		}
		
		return apply_filters( "pod_twilio_get_twilio_number", $return, $this->_master_account );
	}
	
	/**
	*	create twilio subaccount in provided master account
	*	assign a twilio number to that account and voice url to the number	
	*	@param string $twilio_phone_number - generated twilio number by method get_twilio_number()
	*	@param $friendly_name - user name of the user
	*	@return array
	**/
	public function create_twilio_subaccount( $twilio_phone_number, $friendly_name ){
		try{					
			$subaccount = $this->_master_account->accounts->create(array('FriendlyName' => $friendly_name));	//create twilio subaccount
			
			$new_account = new Client( $subaccount->sid, $subaccount->authToken );	
			
			try{
				
				do_action("pod_twilio_set_up_twilio_phone_number", $subaccount, $twilio_phone_number);
				
				$number = $new_account->incomingPhoneNumbers->create(
					array(
						"PhoneNumber"	=> $twilio_phone_number,
						'VoiceUrl' 		=> $this->_voice_url,
						"VoiceMethod"	=> "GET"
					)
				);
				
				do_action( "pod_twilio_after_set_up_twilio_phone_number", $subaccount, $number, $this->_master_account );
				
				$return["status"] = "success";
				$return["message"] = apply_filters( "pod_twilio_account_created_message", __("Twilio Sub Account Created.", POD_TWILIO_TEXT_DOMAIN ) );
				$return["sub_account"] = $subaccount;
				$return["number"] = $number;
				
				do_action( "pod_twilio_on_create_subaccount", $subaccount, $number, $this->_master_account );
			}
			catch( Exception $e ){
				$return["status"] = "error";
				$return["message"] = $e->getMessage();
			}
		}
		catch(Exception $e){
			$return["status"] = "error";
			$return["message"] = $e->getMessage();
		}
		return apply_filters( "pod_twilio_create_twilio_subaccount", $return, $this->_master_account, $this->_voice_url );
	}
}
