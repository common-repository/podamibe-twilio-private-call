<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class POD_Twilio_Install {
	
	/**
	 * Install Plugin
	 */
	public static function install() {
		self::create_tables();
		self::create_template_pages();
	}
	
	/*
	*	Uninstall Plugin
	**/
	public static function unistall(){
		self::delete_template_pages();
	}
	
	/*
	*	delete created pages
	*/
	private static function delete_template_pages(){
		/*
		*	delete twilio voice url page
		*/
		$twilio_dial_number_page_id = get_option( "pod_twilio_dial_number_page_id" );
		wp_delete_post( $twilio_dial_number_page_id,true );
		delete_option( "pod_twilio_dial_number_page_id" );
		
		/*
		*	delete twilio voice url callback page
		*/
		$twilio_dial_callback_page_id = get_option( "pod_twilio_dial_callback_page_id" );
		wp_delete_post( $twilio_dial_callback_page_id,true );
		delete_option( "pod_twilio_dial_callback_page_id" );
		
		/*
		*	delete paypal standard instant payment notification page
		*/
		$twilio_paypal_ipn_page_id = get_option( "pod_twilio_paypal_ipn_page_id" );
		wp_delete_post( $twilio_paypal_ipn_page_id,true );
		delete_option( "pod_twilio_paypal_ipn_page_id" );		
	}
	
	/*
	*	create necessary pages
	*/
	private static function create_template_pages(){
		/*
		*	insert twilio voice url page
		*/
		$twilio_dial_number_page = array(
			'post_title' 	=> __( 'Twilio Dial Number', POD_TWILIO_TEXT_DOMAIN ),
			'post_status' 	=> 'publish',
			'post_date' 	=> date('Y-m-d H:i:s'),
			'post_type' 	=> 'page',
		);
		$twilio_dial_number_page_id = wp_insert_post($twilio_dial_number_page);
		update_option("pod_twilio_dial_number_page_id", $twilio_dial_number_page_id);
		
		/*
		*	insert twilio voice url callback page
		*/
		$twilio_dial_callback_page = array(
			'post_title' 	=> __( 'Twilio Dial Callback', POD_TWILIO_TEXT_DOMAIN ),
			'post_status' 	=> 'publish',
			'post_date' 	=> date('Y-m-d H:i:s'),
			'post_type' 	=> 'page',
		);
		$twilio_dial_callback_page_id = wp_insert_post( $twilio_dial_callback_page );
		update_option( "pod_twilio_dial_callback_page_id", $twilio_dial_callback_page_id );
		
		/*
		*	create paypal standard instant payment notification page
		*/
		$twilio_paypal_ipn_page = array(
			'post_title' 	=> __( 'Twilio Paypal Notification', POD_TWILIO_TEXT_DOMAIN ),
			'post_status' 	=> 'publish',
			'post_date' 	=> date('Y-m-d H:i:s'),
			'post_type' 	=> 'page',
		);
		
		$twilio_paypal_ipn_page_id = wp_insert_post( $twilio_paypal_ipn_page );
		update_option( "pod_twilio_paypal_ipn_page_id", $twilio_paypal_ipn_page_id );
	}
	
	/**
	* Set up the database tables which the plugin needs to function.
	*
	* Tables:
	*		POD_TWILIO_SUPPORTED_COUNTRIES - stores supported countries by twilio
	*		POD_TWILIO_USERS -  stores users with twilio number including sub account info and personal number
	*		pod_twilio_payment_history - payment history of the user_error
	*		POD_TWILIO_CALL_DURATION - remaining call duration of the user
	* @return void
	*/
	private static function create_tables(){
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
		foreach( self::get_table_schema() as $table_schema ){
			//echo $table_schema;
			dbDelta( $table_schema );
		}
		
		add_option( 'POD_TWILIO_DB_VERSION', POD_TWILIO_DB_VERSION );
		
		$supported_country_empty = $wpdb->get_var("SELECT COUNT(*) FROM " . POD_TWILIO_SUPPORTED_COUNTRIES );
		if( ! $supported_country_empty ){
			/* insert default supported countries by twilio */
			$supported_countries = self::get_supported_countries();
			foreach( $supported_countries as $supported_country ){
				$wpdb->insert( POD_TWILIO_SUPPORTED_COUNTRIES, $supported_country );
			}
		}
	}
	
	/*
	*	Get Table Schema
	*	@return sql string
	*/
	private static function get_table_schema(){
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		
		return array(
			"CREATE TABLE IF NOT EXISTS " . POD_TWILIO_USERS . " (
				twilio_user_id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				country_id int(11) NOT NULL,
				twilio_sub_account_sid varchar(64) NOT NULL,
				twilio_sub_account_auth_token varchar(64) NOT NULL,
				user_phone_number varchar(16) NOT NULL,
				twilio_phone_number varchar(16) NOT NULL,
				twilio_phone_number_sid varchar(64) NOT NULL,						
				friendly_name varchar(32) NOT NULL,						
				PRIMARY KEY (twilio_user_id)
			) $charset_collate;",
			"CREATE TABLE IF NOT EXISTS " . POD_TWILIO_SUPPORTED_COUNTRIES . " (
				country_id int(5) NOT NULL AUTO_INCREMENT,
				country_name varchar(32) NOT NULL,
				country_code varchar(10) NOT NULL,
				country_iso_code varchar(5) NOT NULL,
				PRIMARY KEY (country_id)
			) $charset_collate;",
			"CREATE TABLE IF NOT EXISTS " . POD_TWILIO_PAYEMENT_HISTORY . " (
				payment_id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				payment_date date NOT NULL,
				amount float NOT NULL,
				payment_method varchar(32) NOT NULL,
				bought_call_time bigint(20) NOT NULL,
				PRIMARY KEY (payment_id)
			) $charset_collate;",
			"CREATE TABLE IF NOT EXISTS " . POD_TWILIO_CONTACT_LIST . " (
				contact_id int(11) NOT NULL AUTO_INCREMENT,
				request_sender_id int(11) NOT NULL,
				request_receiver_id int(11) NOT NULL,
				request_accepted boolean DEFAULT 0,
				request_rejected boolean DEFAULT 0,
				sender_caller_id_sid varchar(64) DEFAULT NULL,
				receiver_caller_id_sid varchar(64) DEFAULT NULL,
				contact_removed enum('NR','SR','RR') DEFAULT 'NR',
				PRIMARY KEY (contact_id)
			) $charset_collate;",
			"CREATE TABLE IF NOT EXISTS " . POD_TWILIO_CALL_DURATION . " (
				duration_id int(11) NOT NULL AUTO_INCREMENT,
				user_id int(11) NOT NULL,
				remaining_call_duration bigint(20) NOT NULL,
				PRIMARY KEY (duration_id)
			) $charset_collate;"
		);
	}
	
	/*
	*	Get supported countries
	*	@return array
	*/
	private static function get_supported_countries(){
		$supported_countries_array = array(
			array(
				"country_name" 		=> "United States",
				"country_code"		=> "+1",
				"country_iso_code"	=> "US"
			),					
			array(
				"country_name"		=> "Australia",
				"country_code"		=> "+61",
				"country_iso_code"	=> "AU"
			),
			array(
				"country_name"		=> "Austria",
				"country_code"		=> "+43",
				"country_iso_code"	=> "AT"
			),
			array(
				"country_name"		=> "Baharain",
				"country_code"		=> "+973",
				"country_iso_code"	=> "BH"
			),
			array(
				"country_name"		=> "Belgium",
				"country_code"		=> "+32",
				"country_iso_code"	=> "BE"
			),
			array(
				"country_name"		=> "Brazil",
				"country_code"		=> "+55",
				"country_iso_code"	=> "BR"
			),
			array(
				"country_name"		=> "Bulgaria",
				"country_code"		=> "+359",
				"country_iso_code"	=> "BG"
			),
			array(
				"country_name"		=> "Canada",
				"extension_code"	=> "+1",
				"country_iso_code"	=> "CA"
			),
			array(
				"country_name"		=> "Chilie",
				"country_code"		=> "+56",
				"country_iso_code"	=> "CL"
			),
			array(
				"country_name"		=> "Cyprus",
				"country_code"		=> "+357",
				"country_iso_code"	=> "CY"
			),
			array(
				"country_name"		=> "Czech Republic",
				"country_code"		=> "+420",
				"country_iso_code"	=> "CZ"
			),
			array(
				"country_name"		=> "Denmark",
				"country_code"		=> "+45",
				"country_iso_code"	=> "DK"
			),
			array(
				"country_name"		=> "Dominican Republic",
				"country_code"		=> "+1829",
				"country_iso_code"	=> "DO"
			),
			array(
				"country_name"		=> "El Salvador",
				"country_code"		=> "+503",
				"country_iso_code"	=> "SV"
			),
			array(
				"country_name"		=> "Estonia",
				"country_code"		=> "+372",
				"country_iso_code"	=> "EE"
			),
			array(
				"country_name"		=> "Finland",
				"country_code"		=> "+358",
				"country_iso_code"	=> "FI"
			),
			array(
				"country_name"		=> "France",
				"country_code"		=> "+33",
				"country_iso_code"	=> "FR"
			),
			array(
				"country_name"		=> "Germany",
				"country_code"		=> "+49",
				"country_iso_code"	=> "DE"
			),
			array(
				"country_name"		=> "Greece",
				"country_code"		=> "+30",
				"country_iso_code"	=> "GR"
			),
			array(
				"country_name"		=> "Hong Kong",
				"country_code"		=> "+852",
				"country_iso_code"	=> "HK"
			),
			array(
				"country_name"		=> "Hungary",
				"country_code"		=> "+36",
				"country_iso_code"	=> "HU"
			),
			array(
				"country_name"		=> "Ireland",
				"country_code"		=> "+353",
				"country_iso_code"	=> "IE"
			),
			array(
				"country_name"		=> "Israel",
				"country_code"		=> "+972",
				"country_iso_code"	=> "IL"
			),
			array(
				"country_name"		=> "Italy",
				"country_code"		=> "+39",
				"country_iso_code"	=> "IT"
			),
			array(
				"country_name"		=> "Japan",
				"country_code"		=> "+81",
				"country_iso_code"	=> "JP"
			),
			array(
				"country_name"		=> "Latvia",
				"country_code"		=> "+371",
				"country_iso_code"	=> "LV"
			),
			array(
				"country_name"		=> "Lithuania",
				"country_code"		=> "+370",
				"country_iso_code"	=> "LT"
			),
			array(
				"country_name"		=> "Luxemberg",
				"country_code"		=> "+352",
				"country_iso_code"	=> "LU"
			),
			array(
				"country_name"		=> "Malta",
				"country_code"		=> "+356",
				"country_iso_code"	=> "MT"
			),
			array(
				"country_name"		=> "Mexico",
				"country_code"		=> "+52",
				"country_iso_code"	=> "MX"
			),
			array(
				"country_name"		=> "Netherlands",
				"country_code"		=> "+31",
				"country_iso_code"	=> "NL"
			),
			array(
				"country_name"		=> "New Zealand",
				"country_code"		=> "+64",
				"country_iso_code"	=> "NZ"
			),
			array(
				"country_name"		=> "Norway",
				"country_code"		=> "+47",
				"country_iso_code"	=> "NO"
			),
			array(
				"country_name"		=> "Peru",
				"country_code"		=> "+51",
				"country_iso_code"	=> "PE"
			),
			array(
				"country_name"		=> "Poland",
				"country_code"		=> "+48",
				"country_iso_code"	=> "PL"
			),
			array(
				"country_name"		=> "Portugal",
				"country_code"		=> "+351",
				"country_iso_code"	=> "PT"
			),
			array(
				"country_name"		=> "Puerto Rico",
				"country_code"		=> "+1",
				"country_iso_code"	=> "PR"
			),
			array(
				"country_name"		=> "Romania",
				"country_code"		=> "+40",
				"country_iso_code"	=> "RO"
			),
			array(
				"country_name"		=> "Slovakia",
				"country_code"		=> "+421",
				"country_iso_code"	=> "SK"
			),
			array(
				"country_name"		=> "South Africa",
				"country_code"		=> "+27",
				"country_iso_code"	=> "ZA"
			),
			array(
				"country_name"		=> "Spain",
				"country_code"		=> "+34",
				"country_iso_code"	=> "ES"
			),
			array(
				"country_name"		=> "Sweden",
				"country_code"		=> "+46",
				"country_iso_code"	=> "SE"
			),
			array(
				"country_name"		=> "Switzerland",
				"country_code"		=> "+41",
				"country_iso_code"	=> "CH"
			),
			array(
				"country_name"		=> "United Kingdom",
				"country_code"		=> "+44",
				"country_iso_code"	=> "GB"
			)
		);
		return $supported_countries_array;
	}
}