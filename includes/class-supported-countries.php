<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class POD_Twilio_Supported_Countries {
	
	/*
	*	@private
	*	instance of global $wpdb for accessing database
	*/
	private $_db;
	
	/**
	*	constructor of this class
	**/
	public function __construct(){
		global $wpdb;
		$this->_db = $wpdb;
	}
	
	/**
	*	get supported countries
	*	@param $limit
	*	@param $pagenum
	*	@return $object
	*/
	public function get_countries( $limit, $pagenum ) {
		$offset = ( $pagenum - 1 ) * $limit;
		$supported_countries = $this->_db->get_results( "SELECT * FROM ".POD_TWILIO_SUPPORTED_COUNTRIES." ORDER BY country_name ASC LIMIT $offset,$limit" );
		return $supported_countries;
	}
	
	/**
	*	get all supported countries
	*	@parm $where (default: array())
	*	@return array
	**/
	public static function get_supported_countries( $extractionType = "OBJECT", $where=array() ){
		global $wpdb;
		$table = POD_TWILIO_SUPPORTED_COUNTRIES;
		$sql = "SELECT * FROM $table";
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
		$sql .= " ORDER BY country_name ASC";
		$supported_countries = $wpdb->get_results( $sql, $extractionType  );
		return $supported_countries;
	}
	
	/**
	*	add supported country
	*	@param null
	*	insert country into supported countries table
	*	sets error message on error and success message on success
	*	@return null
	*/
	public function add_country(){	
		$country_name = ucwords( isset( $_POST["country_name"] ) ? $_POST["country_name"] : "" );	
		$country_code = isset( $_POST['country_code'] ) ? $_POST['country_code'] : "" ;
		$iso_code = strtoupper( isset( $_POST['country_iso'] ) ? $_POST['country_iso'] : "" );
		
		if( $country_name == "" || $country_code == "" || $iso_code == "" ){
			$success["status"] = "error";
			$success["message"] = __( "OOps! Error Editing Country.", POD_TWILIO_TEXT_DOMAIN );
			
			return $success;
		}
		else{
			$args = array(
					"country_name" => $country_name,
					"country_code" => $country_code,
					"country_iso_code" => $iso_code
			);
			$country_exists = $this->check_country_exists( $args );		//check if already exists return error
			if( $country_exists["status"] == "error" ){
				$success["status"] = "error";
				$success["message"] = $country_exists["message"];						
			}
			else{
				if( $this->_db->insert( POD_TWILIO_SUPPORTED_COUNTRIES, $args ) ){		//insert the country
					$success["status"] = "success";
					$success["message"] = __( "Country Added Successfully.", POD_TWILIO_TEXT_DOMAIN );						
				}
				else{
					$success["status"] = "error";
					$success["message"] = __( "OOps! Error Adding Country.", POD_TWILIO_TEXT_DOMAIN );							
				}
			}
		}				
		return $success;
	}
	
	/*
	*	add supported country
	*	@param null
	*	edit supported country
	*	sets error message on error and success message on success
	*	@return array
	*/
	public function edit_country(){
		$country_name = ucwords( isset($_POST["country_name"] ) ? $_POST["country_name"] : "" );	
		$country_code = isset( $_POST['country_code'] ) ? $_POST['country_code'] : "" ;
		$iso_code = strtoupper( isset( $_POST['country_iso'] ) ? $_POST['country_iso'] : "" );
		
		if( $country_name == "" || $country_code == "" || $iso_code == "" ){
			$success["status"] = "error";
			$success["message"] = __( "OOps! Error Editing Country.", POD_TWILIO_TEXT_DOMAIN );			
		}
		else{				
			$args = array(
				"country_name" 		=> $country_name,
				"country_code" 		=> $country_code,
				"country_iso_code" 	=> $iso_code
			);
				
			$where = array( "country_id" => $_POST["country_id"] );
					
			if( $this->_db->update( POD_TWILIO_SUPPORTED_COUNTRIES, $args, $where ) ){		//update the country
				$success["status"] = "updated";
				$success["message"] = __( "Country Updated Successfully.", POD_TWILIO_TEXT_DOMAIN );
				
			}
			else{
				$success["status"] = "error";
				$success["message"] = __( "OOps! Error Editing Country.", POD_TWILIO_TEXT_DOMAIN );					
			}
		}
		
		return $success;
	}
	
	/**
	*	delete selected country
	*	@param $country_id(default:null)
	*	@return string(if request performed through ajax) else set message
	**/
	public function delete_supported_country( $country_id = NULL ){
		if( defined( "DOING_AJAX" ) && DOING_AJAX ){		//if request performed through ajax method
			$country_id = $_POST["country_id"];
		}
		
		if( $country_id ) {
			if( $this->_db->delete( POD_TWILIO_SUPPORTED_COUNTRIES,array( 'id' => $country_id ) ) ){
				$return["status"] = "success";
				$return["message"] = __( "Country deleted successfully.", POD_TWILIO_TEXT_DOMAIN );
			}
			else{
				$return["status"] = "error";
				$return["message"] = __( "Oops! Error deleting country.", POD_TWILIO_TEXT_DOMAIN );
			}
		
			if( defined("DOING_AJAX") && DOING_AJAX ){		//if request performed through ajax method return json string
				echo json_encode($return);
				die();
			}
			else{
				return $return;				
			}
		}
	}
	
	/**
	* get country info
	* @param $country_id
	* @return json string if request made through ajax else country info object
	**/
	public function get_country_info_by_country_id( $country_id = NULL ){
		if( defined( "DOING_AJAX" ) && DOING_AJAX ){
			$country_id = $_POST["country_id"];
		}
			
		$country_info = $this->_db->get_row( "SELECT * FROM ".POD_TWILIO_SUPPORTED_COUNTRIES." WHERE country_id=".$country_id );
		
		if( defined( "DOING_AJAX" ) && DOING_AJAX ){
			echo json_encode( $country_info );
			die();
		}
		else {
			return $country_info;
		}
	}
	
	/**
	* 	check if country exists
	*	@param array country_info
	*	@return json string if request made through ajax else return status array()
	**/
	public function check_country_exists( $country_info = array() ){
		if( defined( "DOING_AJAX" ) && DOING_AJAX ){
			$country_info = $_POST;
			unset( $country_info["action"] );
		}
		
		$sql = "SELECT COUNT(*) FROM ".POD_TWILIO_SUPPORTED_COUNTRIES;
			if( ! empty( $country_info ) ) {
			$sql .= " WHERE ";
			$i = 0;
			foreach( $country_info as $key => $value ) {
				if( trim( $value ) == "" ){
					$return["status"] = "error";
					$return["message"] = __( "Error Occured. Please Check if you entered empty value.", POD_TWILIO_TEXT_DOMAIN );
						
					if( defined( "DOING_AJAX" ) && DOING_AJAX ){
						echo json_encode( $return );
						die();
					}
					else{
						return $return;
					}
					break;
				}
				else{
					if( $i == 0 ){
						$sql .= "{$key} LIKE '{$value}'";
					}
					else{
						$sql .= " OR ". "{$key} LIKE '{$value}'";
					}						
				}
				$i++;
			}
				
			$country_exists = $this->_db->get_var( $sql );
			if( $country_exists > 0 ){
				$return["status"] = "error";
				$return["message"] = __( "Error Occured. Please check if country exits and try again.", POD_TWILIO_TEXT_DOMAIN );
			}
			else{
				$return["status"] = "success";
				$return["message"] = __( "Country does not exist.", POD_TWILIO_TEXT_DOMAIN );
			}
		}
		else{
			$return["status"] = "error";
			$return["message"] = __( "Empty country info", POD_TWILIO_TEXT_DOMAIN );
		}
			
		if( defined( "DOING_AJAX" ) && DOING_AJAX ){
			echo json_encode( $return );
			die();
		}
		else{
			return $return;
		}
	}
}