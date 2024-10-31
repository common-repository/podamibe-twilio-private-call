<?php
class POD_Twilio_User_Sign_Up_Shortcode {
	/**
	*	A reference to an instance of this class.
	* 	@private
	* 	@var   POD_Twilio_User_Sign_Up_Shortcode
	*/
	private static $_instance = null;
	
	/**
	*	@private
	*	master twilio account setting
	**/
	private $_master_ac_settings;
	
	/**
	*	@array
	*	@private
	*	signup form fields
	**/	
	private $_form_fields;
	
	/**
	*	@array
	*	@private
	*	notices on user signup
	**/
	private $_notices = array();
	
	private function __construct(){
		add_shortcode( "pod-twilio-user-signup", array( $this, "init_shortcode" ) );
		add_action( "save_post", array( $this, "save_twilio_user_signup_page_id" ) );
		if( ! is_user_logged_in() ){
			if( isset( $_POST["pod_twilio_sign_up"] ) ){
				if( pod_verify_nonce("pod_twilio_sign_up") ){
					$user_info_map = array(
						"user_login" 	=> "user_name",
						"user_pass" 	=> "user_password",
						"user_email" 	=> "user_email",
						"first_name" 	=> "first_name",
						"last_name" 	=> "last_name"
					);
					
					$posted_user_data = $_POST["pod_twilio_user_info"];
					
					$user_info = array();
				
					foreach( $user_info_map as $key => $val ){
						if( array_key_exists( $val, $posted_user_data ) ){
							$user_info[$key] = $posted_user_data[ $val ];
						}
					}
					
					$new_user = wp_insert_user( $user_info );
					
					if ( ! is_wp_error( $new_user ) ) {
						$this->_add_notice( "success", __( "Registration Successful", POD_TWILIO_TEXT_DOMAIN ) );
						
						$secure_cookie = is_ssl() ? true : false;
						wp_set_current_user( $new_user );
						wp_set_auth_cookie( $new_user, false, $secure_cookie );
						
						$redirect_page = get_option('pod_twilio_user_profile_page_id');
						if( $redirect_page ){
							wp_safe_redirect( get_permalink( $redirect_page ) );
							exit();
						}
					}
					else{
						$this->_add_notice( "error", $new_user->get_error_message() );
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
	* @return   POD_Twilio_User_Sign_Up_Shortcode
	*/
	public static function init(){
		if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	*	save twilio sign up page id
	*	callback function for 'save_post' wp action hook
	*	@param $post_id of the page
	*	@return null
	**/
	public function save_twilio_user_signup_page_id( $post_id ){
		$content_post = get_post( $post_id );
		$content = $content_post->post_content;		
		if( has_shortcode( $content, 'pod-twilio-user-signup' )){
			update_option( "pod_twilio_user_signup_page_id", $post_id );
		}
	}
	
	/**
	*	Callback function of the shortcode
	*	initializes the shortcode and returns shortcode content
	*	@return string
	**/
	public function init_shortcode(){
		$this->_form_fields = $this->_get_form_fields();
		do_action("pod_twilio_user_sign_up_shortcode_init");
		$form = "<div id=\"pod-twilio-user-signup\">";
		if( ! is_user_logged_in() ){
			if( ! empty( $this->_notices )){
				$form .= $this->_show_notices();
			}
			$form .= $this->_sign_up_form();			
		}
		$form .= "<div id=\"pod-twilio-user-signup\">";
		return $form;
	}
	
		
	/**
	*	build sign uup form
	*	@private
	*	@return string
	**/
	private function _sign_up_form() {
		$form = "<form method=\"post\" id=\"sign-up-form\" action=\"\">";
		$form .= wp_nonce_field('pod_twilio_sign_up'); 
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
					$form .= "<div " . $wrapper_attributes . ">
								<label for=\"" . $field["label_for"] . "\">" . $field["title"] . "</label>
								<input type=\"text\" name=\"" . $field["name"] . "\" value=\"" . $field["value"] . "\" " . $attributes . ">
							</div>";
					break;
                case 'email':
					$form .= "<div " . $wrapper_attributes . ">
								<label for=\"" . $field["label_for"] . "\">".$field["title"] . "</label>
								<input type=\"email\" name=\"" . $field["name"] . "\" value=\"" . $field["value"] . "\" " . $attributes . ">
							</div>";
					break;
				case 'password':
					$form .= "<div " . $wrapper_attributes . ">
								<label for=\"" . $field["label_for"] . "\">" . $field["title"] . "</label>
								<input type=\"password\" name=\"" . $field["name"] . "\" value=\"" . $field["value"] . "\" " . $attributes . ">
							</div>";
					break;
                case 'dropdown':
					$form .= "<div " . $wrapper_attributes . ">
								<label for=\"" . $field["label_for"] . "\">" . $field["title"] . "</label>
									<select name=\"" . $field["name"] . "\" " . $attributes . ">";
					foreach( $field["value"] as $key=>$value ){
						$selected = ( $key == $field["selected"] ) ? "selected":"";
						$form .= "<option value=\"" . $key . "\" " . $selected . ">" . $value . "</option>";
					}					
					$form .= "</select></div>";
					break;
				case "checkbox":
					$form .= "<div " . $wrapper_attributes . ">
								<label for=\"" . $field["label_for"] . "\">" . $field["title"] . "</label>
								<input type=\"checkbox\" name=\"" . $field["name"] . "\" value=\"" . $field["value"] . "\" " . $attributes . ">
							</div>";
					break;
				case "radio":
					$form .= "<div " . $wrapper_attributes . ">
								<label for=\"" . $field["label_for"] . "\">" . $field["title"] . "</label>
								<input type=\"radio\" name=\"" . $field["name"] . "\" value=\"" . $field["value"] . "\" " . $attributes . ">
							</div>";
					break;
				case "tel":
				case "phone":
					$form .= "<div " . $wrapper_attributes . ">
								<label for=\"" . $field["label_for"] . "\">" . $field["title"] . "</label>
								<input type=\"tel\" name=\"" . $field["name"] . "\" value=\"" . $field["value"] . "\" " . $attributes . ">";
					$form .= "</div>";
					break;
				case "label":
				case "default":
					$form .= "<div " . $wrapper_attributes . ">
								<label for=\"" . $field["label_for"] . "\">" . $field["title"] . "</label>
								<input type=\"" . $field['type'] . "\" name=\"". $field["name"] . "\" value=\"" . $field["value"] . "\" " . $attributes . ">
							</div>";
					break;					
            }
		}
		$form .= "<div id=\"pod-twilio-sign-up\"><input type=\"submit\" id=\"pod-twilio-signup-button\" name=\"pod_twilio_sign_up\" value=\"Sign Up\"></div>";
        $form .="</form>";
        return apply_filters( "pod_twilio_sign_up_form", $form, $this->_form_fields );
	}
	
	/**
	*	initializes the form fields
	*	@return array
	**/
	private function _get_form_fields(){
		$fields = array(
			"first_name" => array(
				"title"			=> apply_filters( "pod_twilio_signup_form_user_first_name_title", __( "First Name:", POD_TWILIO_TEXT_DOMAIN ) ),
				"name" 			=> "pod_twilio_user_info[first_name]",
				"value" 		=> "",
				"label_for" 	=> "",
				"type" 			=> "text",
				"wrapper_attr" 	=> array(),
				"attr" 			=> array()
			),
			"last_name" => array(
				"title" 		=> apply_filters( "pod_twilio_signup_form_user_last_name_title", __( "Last Name:", POD_TWILIO_TEXT_DOMAIN ) ),
				"name" 			=> "pod_twilio_user_info[last_name]",
				"value" 		=> "",
				"label_for" 	=> "",
				"type" 			=> "text",
				"wrapper_attr" 	=> array(),
				"attr" 			=> array()
			),
            "username" => array(
                "title" 		=> apply_filters( "pod_twilio_signup_form_username_title", __( "Username:", POD_TWILIO_TEXT_DOMAIN ) ),
				"name" 			=> "pod_twilio_user_info[user_name]",
				"value" 		=> "",
				"label_for" 	=> "",
				"type" 			=> "text",
				"wrapper_attr" 	=> array(),
				"attr" 			=> array()
            ),
            "email" => array(
				"title" 		=> apply_filters( "pod_twilio_signup_form_user_email_title", __( "Email:", POD_TWILIO_TEXT_DOMAIN ) ),
				"name" 			=> "pod_twilio_user_info[user_email]",
				"value" 		=> "",
				"label_for" 	=> "",
				"type" 			=> "text",
				"value" 		=> "",
				"wrapper_attr" 	=> array(),
				"attr" 			=> array()
            ),
			 "password" => array(
				"title" 		=> apply_filters( "pod_twilio_signup_form_user_password_title", __( "Password:", POD_TWILIO_TEXT_DOMAIN ) ),
				"name" 			=> "pod_twilio_user_info[user_password]",
				"value" 		=> "",
				"label_for" 	=> "",
				"type" 			=> "password",
				"value" 		=> "",
				"wrapper_attr" 	=> array(),
				"attr" 			=> array()
            )
        );
        return apply_filters( 'pod_twilio_signup_form_fields', $fields );
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
	*	add success or error messages
	*	@return void
	*/
	private function _add_notice( $type, $message ){
		array_push( $this->_notices, array( "type"=>$type, "message"=>$message ) );
	}
}