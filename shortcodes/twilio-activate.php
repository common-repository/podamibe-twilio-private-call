<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if accessed directly */
}

class POD_Twilio_Activate_Shortcode{
	
	/**
	*	A reference to an instance of this class.
	* 	@private
	* 	@var   POD_Twilio_Activate_Shortcode
	*/
	private static $_instance = null;
	
	/**
	*	@array
	*	@private
	*	activate twilio form fields
	**/	
	private $_form_fields;
	
	/*
	*	@var integer
	*	@private
	*	current user id 
	*/
	private $_id;
	
	/**
	*	@array
	*	@private
	*	notices on twilio activation
	**/
	private $_notices = array();
	
	/**
	*	A reference to an instance class POD_Twilio_User.
	* 	@private
	* 	@var   POD_Twilio_User
	*/
	private $_twilio_user;
	
	/**
	*	Constructor of this class
	*	Initializes shortcode for user register
	**/	
	private function __construct(){
		add_shortcode( "pod-twilio-activate", array( $this, "init_shortcode" ) );
		add_action( "save_post", array( $this, "save_activate_twilio_page_id" ) );
		if( is_user_logged_in() ){
			$this->_id = get_current_user_id();
			$this->_twilio_user = new POD_Twilio_User( $this->_id );			
			if( isset( $_POST["pod_twilio_activate_twilio"] ) ){				
				if( pod_verify_nonce( "pod_twilio_activate_twilio" ) ){
					$register_user = new POD_Twilio_User_Register( $this->_twilio_user );
					
					$register = $register_user->create_twilio_user();
				
					if( $register["status"] == "success"){					
						$this->_add_notice( "success", $register["message"] );
						$this->_twilio_user->has_twilio_activated = true;
					}
					else{
						$this->_add_notice( "error", $register["message"] );
					}
				}
				else{
					$this->_add_notice( "error", apply_filters( "pod_twilio_nonce_verification_failed_notice", __( "Oops ! Error Occured. Try again !", POD_TWILIO_TEXT_DOMAIN ) ) );
				}
			}
		}
	}
	
	/**
	* Returns an instance of this class
	* @public
	* @return   POD_Twilio_Activate_Shortcode
	*/
	public static function init(){
		if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function save_activate_twilio_page_id( $post_id ){
		$content_post = get_post( $post_id );
		$content = $content_post->post_content;		
		if( has_shortcode( $content, 'pod-twilio-activate' ) ){
			update_option( "pod_register_twilio_page_page_id", $post_id );
		}
	}
	
	/**
	*	Callback function of the shortcode
	*	@return user register form
	**/
	public function init_shortcode(){
		$this->_form_fields = $this->_get_form_fields();
		do_action("pod_twilio_activate_shortcode_init");
		$form = "<div id=\"pod-twilio-register\">";
		if( is_user_logged_in() ){
			if( ! $this->_twilio_user->has_twilio_activated ){
				if( ! empty( $this->_notices ) ){
					$form .= $this->_show_notices();
				}
				if( $this->_twilio_user->remaining_call_duration <= 0 ){
					$form .= __( "Please ", POD_TWILIO_TEXT_DOMAIN ) ."<a href=\"".get_permalink(get_option( 'pod_twilio_user_profile_page_id' ) )."\">" . __( "Buy Private Call", POD_TWILIO_TEXT_DOMAIN ) ."</a>". __( " First.", POD_TWILIO_TEXT_DOMAIN );
				}				
				else{
					$form .= $this->_create_form();
				}			
			}
			else{
				if( ! empty( $this->_notices ) ){
					$form .= $this->_show_notices();
				}
				else{
					$form .= apply_filters( "pod_twilio_already_have_twilio_account_section","<div class=\"\">".apply_filters( "pod_twilio_already_have_twilio_account_message",__( "You have already have twilio account.", POD_TWILIO_TEXT_DOMAIN )."</div>" ) );
				}
			}
			$form .= "</form>";
		}
		else{
			$form .= apply_filters( "pod_twilio_login_register_to_activate_section", "<div class=\"\">". apply_filters( "pod_twilio_login_register_to_activate_message", __( "Login or Sign Up to activate.", POD_TWILIO_TEXT_DOMAIN ) ) . "</div>" );
		}
		$form .= "</div>";
		return $form;
	}
	
	/**
	*	initializes the form fields
	*	@return array
	**/
	private function _get_form_fields(){
		$fields = array(
            "country_id" => array(
                "title" 		=> apply_filters( "pod_twilio_activation_form_country_title", __( "Country:", POD_TWILIO_TEXT_DOMAIN ) ),
				"name" 			=> "twilio_user[country_id]",
				"label_for" 	=> "",
				"type" 			=> "dropdown",
				"value" 		=> pod_format_array_for_dropdown( "country_id", "country_name", POD_Twilio_Supported_Countries::get_supported_countries("ARRAY_A") ),
				"selected" 		=> "",
				"wrapper_attr" 	=> array(),
				"attr" 			=> array()
            ),
            "mobile_number" => array(
				"title" 		=> apply_filters( "pod_twilio_activation_form_mobile_number_title", __( "Your Phone Number:", POD_TWILIO_TEXT_DOMAIN ) ),
				"name" 			=> "twilio_user[user_phone_number]",
				"label_for" 	=> "",
				"type" 			=> "text",
				"value" => "",
				"wrapper_attr" 	=> array(),
				"attr" 			=> array()
            )
        );
        return apply_filters('pod_twilio_activate_twilio_form_fields', $fields);
	}
	
	/**
	*	build activation form
	*	@private
	*	@return string
	**/
	private function _create_form() {
        $form = "<form method=\"post\" id=\"activate-form\" action=\"\">";
        $form .= wp_nonce_field('pod_twilio_activate_twilio');       
        foreach( $this->_form_fields as $field ) {
			$attribs = array_filter( $field["attr"] );
			$attributes = "";
			foreach( $attribs as $attr_key => $attr_value ){
				$attributes .= $attr_key . "=\"" . $attr_value . "\"";
			}
			
			$wrapper_attribs = array_filter( $field["wrapper_attr"] );
			$wrapper_attributes = "";
			foreach( $wrapper_attribs as $attr_key=>$attr_value ){
				$wrapper_attributes .= $attr_key . "=\"" . $attr_value . "\"";
			}
			
            switch( $field['type'] ) {
                case 'text':
					$form .= "<div ". $wrapper_attributes .">
								<label for=\"".$field["label_for"]."\">".$field["title"] . "</label>
								<input type=\"text\" name=\"". $field["name"] ."\" value=\"".$field["value"]."\" ".$attributes.">
							</div>";
					break;
                case 'email':
					$form .= "<div ". $wrapper_attributes .">
								<label for=\"".$field["label_for"]."\">".$field["title"] . "</label>
								<input type=\"email\" name=\"". $field["name"] ."\" value=\"".$field["value"]."\" ".$attributes.">
							</div>";
					break;
                case 'dropdown':
					$form .= "<div ". $wrapper_attributes .">
								<label for=\"".$field["label_for"]."\">".$field["title"] . "</label>
									<select name=\"".$field["name"]."\" ". $attributes .">";
					foreach( $field["value"] as $key=>$value ){
						$selected = ( $key == $field["selected"] ) ? "selected":"";
						$form .= "<option value=\"" . $key . "\" ". $selected . ">".$value."</option>";
					}					
					$form .= "</select></div>";
					break;
				case "checkbox":
					$form .= "<div ". $wrapper_attributes .">
								<label for=\"".$field["label_for"]."\">".$field["title"] . "</label>
								<input type=\"checkbox\" name=\"". $field["name"] ."\" value=\"".$field["value"]."\" ". $attributes .">
							</div>";
					break;
				case "radio":
					$form .= "<div ". $wrapper_attributes .">
								<label for=\"".$field["label_for"]."\">".$field["title"] . "</label>
								<input type=\"radio\" name=\"". $field["name"] ."\" value=\"".$field["value"]."\" ". $attributes .">
							</div>";
					break;
				case "tel":
				case "phone":
					$form .= "<div ". $wrapper_attributes .">
								<label for=\"".$field["label_for"]."\">".$field["title"] . "</label>
								<input type=\"tel\" name=\"". $field["name"] ."\" value=\"".$field["value"]."\" ". $attributes .">";
					$form .= "</div>";
					break;
				case "label":
				case "default":
					$form .= "<div ". $wrapper_attributes .">
								<label for=\"".$field["label_for"]."\">".$field["title"] . "</label>".$field['value']."
							</div>";
					break;					
            }			
        }
		$form .= "<div id=\"activate-twilio\"><input type=\"submit\" id=\"activate-twilio-buton\" name=\"pod_twilio_activate_twilio\" value=\"Activate\"></div>";
        $form .="</form>";
        return apply_filters( "pod_twilio_activation_form", $form, $this->_form_fields );
    }
	
	/*
	*	add success or error messages
	*	@return void
	*/
	private function _add_notice( $type, $message ){
		array_push( $this->_notices, array( "type"=>$type, "message"=>$message ) );
	}
	
	/*
	*	show success or error messages
	*	@return string
	*/
	private function _show_notices(){
		$notices = "<div class=\"pod-twilio-notices\" >";
		foreach( $this->_notices as $notice ){
			$notices .= "<div class=\"pod-twilio-show-notices ".$notice["type"]."\">".$notice["message"]."</div>";
		}
		$notices .= "</div>";
		return apply_filters( "pod_twilio_register_success_error_notices", $notices, $this->_notices );
	}
	
	/*
	*	get success / error messages array
	*	@return notices array
	*/
	private function _get_notices(){
		return $this->_notices;
	}
}