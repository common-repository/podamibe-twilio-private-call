<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; /* Exit if accessed directly */
}

class POD_TWilio_User_Profile_Shortcode {
	/**
	*	A reference to an instance of this class.
	* 	@private
	* 	@var   POD_TWilio_User_Profile_Shortcode
	*/
	private static $_instance = null;
	
	/*
	*	@var integer
	*	@private
	*	current user id 
	*/
	private $_id;
	
	/**
	*	A reference to an instance class POD_Twilio_User.
	* 	@private
	* 	@var   POD_Twilio_User
	*/
	private $_twilio_user;
	
	/**
	*	@private
	*	@array
	*	tabs to display
	**/
	private $_tabs;
	
	/**
	*	@private
	*	@array
	*	user details to display
	**/
	
	private $_user_details;
	
	/**
	*	@private
	*	master twilio account setting
	**/
	private $_master_ac_settings;
	
	/**
	*	@private
	*	payment settings
	**/
	private $_payment_settings;
	
	/**
	*	@array
	*	@private
	*	notices on twilio activation
	**/
	private $_notices = array();
	
	/**
	*	Constructor of this class
	*	Initializes shortcode
	**/		
	private function __construct(){
		add_shortcode( "pod-twilio-userprofile", array( $this, "init_shortcode" ) );
		add_action( "save_post", array( $this, "save_twilio_user_profile_page_id" ) );
		if( is_user_logged_in() ){
			$this->_id = get_current_user_id();
			$this->_twilio_user = new POD_Twilio_User( $this->_id );
			if( $this->_twilio_user->has_twilio_activated ){
				if( isset( $_POST["pod_twilio_update_user_info"] ) ){
					if( pod_verify_nonce( "pod_twilio_update_user_details" ) ){
						$update_user = new POD_Twilio_Update_User_Info( $this->_twilio_user );
						$update = $update_user->update_twilio_user();
						if( $update["status"] == "success"){				
							$this->_add_notice( "success", $update["message"] );
						}
						else{
							$this->_add_notice( "error", $update["message"] );
						}
					}
					else{
						$this->_add_notice( "error", apply_filters( "pod_twilio_nonce_verification_failed_notice", __( "Oops ! Error Occured. Try again !", POD_TWILIO_TEXT_DOMAIN ) ) );
					}
				}
			}			
		}
	}
	
	/**
	* Returns an instance of this class
	* @public
	* @return   POD_TWilio_User_Profile_Shortcode
	*/
	public static function init(){
		if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	*	save twilio profile page id
	*	callback function for 'save_post' wp action hook
	*	@param $post_id of the page
	*	@return null
	**/
	public function save_twilio_user_profile_page_id( $post_id ){
		$content_post = get_post( $post_id );
		$content = $content_post->post_content;		
		if( has_shortcode( $content, 'pod-twilio-userprofile' )){
			update_option( "pod_twilio_user_profile_page_id", $post_id );
		}
	}
	
	/**
	*	Callback function of the shortcode
	*	initializes the shortcode and returns shortcode content
	*	@return string
	**/
	public function init_shortcode(){
		$this->_tabs = $this->_set_tabs();
		$this->_master_ac_settings = get_option( "pod_twilio_master_ac_settings" );
		$this->_payment_settings = get_option( "pod_twilio_payment_settings" );
		
		do_action("pod_twilio_user_profile_shortcode_init");
		
		if( is_user_logged_in() ){
			if( $this->_twilio_user->has_twilio_activated ){
				$this->_user_details = $this->_get_user_details();
				$user_details = $this->_load_content();
				return $user_details;
			}
			else{
				
				$message = "<div class=\"pod-twilio-notices\" >";
				
				$message .= "<div class=\"pod-twilio-show-notices error\">";
				
				if( $this->_twilio_user->remaining_call_duration <= 0 ){
					$message .= apply_filters( "pod_twilio_buy_call_duration_message", __( "Please buy call duration.", POD_TWILIO_TEXT_DOMAIN )  );
					$message .= $this->_load_buy_call_form();
				}
				else{
					$message .= apply_filters( "pod_twilio_activate_twilio_message", __( "Please  ", POD_TWILIO_TEXT_DOMAIN )."<a href=\"".get_permalink(get_option( "pod_register_twilio_page_page_id"))."\">" . __( "activate", POD_TWILIO_TEXT_DOMAIN )."</a>" .__( " private call.", POD_TWILIO_TEXT_DOMAIN ) );
				}
				
				$message .= "</div></div>";
				
				return $message;
			}
		}
		
	}
	
	/**
	*	load shortcode content
	*	@return string
	**/
	private function _load_content(){
		$active_tab = $this->_active_tab();
		$view = "<div id=\"pod-twilio-user-details\">";
		$view .= "<ul class=\"pod-twilio-tabs\">";		
		foreach( $this->_tabs as $key => $tab ){
			if( $key == $active_tab ){
				$active = "pod-twilio-active-tab";
			}
			else{
				$active = "";
			}
			$view .= "<li>";
			$view .= "<a data-tab=\"". $tab["show"] ."\" class=\"pod-twilio-show-tab-content ".$active."\">".$tab["title"]."</a>";
			$view.= "</li>"; 
		}
		do_action("pod_twilio_user_details_add_tab", $active_tab );
		$view .= "</ul>";
		
		/* show payment success message */
		if( ( isset( $_REQUEST["payment_status"] ) &&  $_REQUEST["payment_status"] == "Completed" ) && ( isset( $_REQUEST["item_name"] ) &&  $_REQUEST["item_name"] == __( "Buy Call Duration", POD_TWILIO_TEXT_DOMAIN ) ) ){
			$view .="<p class=\"call-duration\">" . __( "Thank You for your Payment", POD_TWILIO_TEXT_DOMAIN )."</p>";
		}
		
		/* remaining  call duration */
		$view .="<p class=\"call-duration\">" . __( "You have ", POD_TWILIO_TEXT_DOMAIN ) . pod_twilio_format_call_duration( $this->_twilio_user->remaining_call_duration ). __( " call duration left.", POD_TWILIO_TEXT_DOMAIN )."</p>";
		
		/* get tab content form tabs array and store in array with respective keys as key*/
		$callbacks = array_filter( array_combine( array_keys( $this->_tabs ), array_column( $this->_tabs, 'tab_content' ) ) );
		
		/* show tab contents */
		foreach( $callbacks as $key => $callback ){
			if( $key == $active_tab ){
				$active = "pod-twilio-active-tab";
				$show = "style=\"display:block;\"";
			}
			else{
				$active = "";
				$show = "style=\"display: none;\"";
			}
			
			$wrapper_atts = "";
			foreach( $callback["content_wrapper_atts"] as $attribute => $value ){
				$wrapper_atts .= $attribute." = \"".$value."\" ";
			}
			
			$view .= "<div ". $wrapper_atts ." ".$show.">";
			$view .= call_user_func( $callback["content_callback"] );
			$view .= "</div>";
		}
		do_action( "pod_twilio_user_details_add_tab_content", $active_tab );
		$view .= "</div>";
		return $view;
	}
	
	/**
	*	get tabs
	*	@return array
	**/
	private function _set_tabs(){
		$tabs = array(
			"user_details" => array(
				"title" 		=> __( "Twilio Account Details", POD_TWILIO_TEXT_DOMAIN ),
				"show" 			=> "#twilio-user-details",
				"tab_content" 	=> array(
					"content_callback" 		=> array( $this, "_load_user_details" ),
					"content_wrapper_atts" 	=> array(
						"id" 	=> "twilio-user-details",
						"class"	=> "pod-twilio-tab-content user-details"
					)
				)
				
			),
			"buy_call_duration" => array(
				"title" 		=> __( "Buy Call Duration", POD_TWILIO_TEXT_DOMAIN ),	
				"show" 			=> "#twilio-buy-call",
				"tab_content" 	=> array(
					"content_callback" 		=> array( $this, "_load_buy_call_form" ),
					"content_wrapper_atts" 	=> array(
						"id" 	=> "twilio-buy-call",
						"class"	=> "pod-twilio-tab-content buy-call"
					)
				)				
			)
		);		
		return apply_filters( "pod_twilio_user_profile_tabs", $tabs );
	}
	
	/**
	*	view user details
	**/
	private function _load_user_details(){
		ob_start();
?>
		<div id="user-details">
		<?php 
			if(! empty( $this->_notices )){
				$this->_show_notices();
			}
		?>
			<div class="title">
				<span><h5>
					<?php _e('My Details', POD_TWILIO_TEXT_DOMAIN); ?>				
				</h5></span>
				<span><a class="logout_btn" href="<?php echo wp_logout_url(); ?>"><?php _e( 'Logout', POD_TWILIO_TEXT_DOMAIN ); ?></a></span>
			</div>
			<hr />
			 <form method="post" id="edit-form" action="">
			<?php 
				echo wp_nonce_field('pod_twilio_update_user_details');       
				/* show user related details */
				foreach( $this->_user_details as $user_details ){
					$wrapper_atts="";
					foreach( $user_details["wrapper_atts"] as $attr => $value ){
						$wrapper_atts .= $attr . "=\"". $value ."\"";
					}
					
					echo "<ul ". $wrapper_atts . ">".
							"<li>".$user_details["label"] . "</li>" . 
							"<li>";
								switch( $user_details["input_type"] ){
									case( "label" ):
										echo $user_details["value"];
										break;
									case( "text" ):
										echo "<input type=\"text\" value=\"" . $user_details["value"] . "\" name=\"" . $user_details["name"] . "\">";
										break;
									case( "dropdown" ):
										echo "<select name=\"" . $user_details["name"] . "\">";
											foreach( $user_details["value"] as $value => $label ){
												if( $value ==  $user_details["selected"]  ){
													echo "<option value=\"" . $value . "\" selected>" . $label. "</option>";
												}
												else{
													echo "<option value=\"" . $value . "\">" . $label. "</option>";
												}
											}
										echo "</select>";
										break;
									default:
										echo "<input type=\"" . $user_details["input_type"] . "\" value=\"" . $user_details["value"] . "\">";
										break;
								}
				
					echo "</li>" . "
						</ul>";
				}
				
				do_action( "pod_twilio_after_user_details", $this->_twilio_user );
			?>
			<input type="submit" id="update-twilio-info-button" name="pod_twilio_update_user_info" value="<?php esc_attr_e( "Update", POD_TWILIO_TEXT_DOMAIN ); ?>">
			</form>
		</div>
<?php
		return ob_get_clean();
	}
	
	/*
	*	buy call duration tab content
	*	loads buy call duration form
	*/
	private function _load_buy_call_form(){
		if( isset( $this->_payment_settings["paypal_sanbox_mode"] ) ){
			$paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';   // testing paypal url
		}
		else{
			$paypal_url = 'https://www.paypal.com/cgi-bin/webscr';     // paypal url
		}
		ob_start();
?>		<div class="title">
			<h5>
				<?php esc_html_e('Buy Call Time', POD_TWILIO_TEXT_DOMAIN); ?>
			</h5>
		</div>
		<hr />
		<form action="<?php echo $paypal_url; ?>" method="post" name="buy_call_duration" onclick="return check_validations();">
			<ul class="price-per-min">
				<li><label><?php esc_html_e( "Price Per Minute:", POD_TWILIO_TEXT_DOMAIN ); ?> </label></li>
				<li><?php pod_twilio_amount_with_currency( $this->_master_ac_settings["call_cost_per_minute"] ); ?></li>
			</ul>
			<ul class="call-duration">
				<li><label><?php esc_html_e( "Call Duration:", POD_TWILIO_TEXT_DOMAIN ); ?> </label></li>
				<li><input class="call-duration" id="call-duration" name="call_duration" type="text" placeholder="<?php esc_attr_e( "Call Duration (in minutes)", POD_TWILIO_TEXT_DOMAIN ); ?>"></li>
			</ul>
			<ul class="total-price">
				<li><label class=""><?php esc_html_e( "Total Price:", POD_TWILIO_TEXT_DOMAIN ); ?> </label></li>
				<li><span class="show-total-price" id="show-total-price">
				<?php pod_twilio_amount_with_currency(0); ?></span></li>
			</ul>	
			<input type="hidden" name="cmd" value="_xclick"/>
			<input type="hidden" name="business" value="<?php echo $this->_payment_settings["paypal_email_address"]; ?>" />
			<input type="hidden" name="currency_code" value="<?php echo $this->_payment_settings["paypal_currency_code"]; ?>" />
			<input type="hidden" name="item_number" value="<?php echo $this->_id; ?>" />
			<input type="hidden" name="item_name" value="Buy Call Duration" />
			<input type="hidden" name="amount" value="0" />
			<input type="hidden" name="custom" value="0" />
			<input type="hidden" name="return" value="<?php the_permalink(); ?>">
			<input type="hidden" name="cancel_return" value="<?php the_permalink(); ?>">
			<input type="hidden" name="notify_url" value="<?php echo get_page_link( get_option( "pod_twilio_paypal_ipn_page_id" ) ) ."?action=ipn"; ?>">
			<input type="submit" value="buy">
		</form>
<?php
		return ob_get_clean();
	}
	
	
	/**
	*	load user details sections
	*	@return array
	**/
	private function _get_user_details(){
		$twilio_user_info = $this->_twilio_user->get_user_details();
		$country_code = $twilio_user_info["country_code"];
		$phone_number = $twilio_user_info["user_phone_number"];

		$twilio_user_info["user_phone_number"] = preg_replace( "/^\\".$country_code."/", "", $phone_number );
		
		$sections = array(
			"username" => array(
				"label" => __( "Username: ", POD_TWILIO_TEXT_DOMAIN ),
				"input_type" => "label",
				"value" => $this->_twilio_user->get_user_name(),
				"wrapper_atts" => array(
					"class" => "username"
				)
			),
			"country" => array(
				"label" => __( "Country: ", POD_TWILIO_TEXT_DOMAIN ),
				"input_type" => "dropdown",
				"name" => "twilio_user[country_id]",
				"value" => pod_format_array_for_dropdown( "country_id", "country_name", POD_Twilio_Supported_Countries::get_supported_countries("ARRAY_A") ),
				"selected" => $twilio_user_info["country_id"],
				"wrapper_atts" => array(
					"class" => "country"
				)
			),
			"phone_number" => array(
				"label" => __( "Phone Number: ", POD_TWILIO_TEXT_DOMAIN ),
				"value" => $twilio_user_info["user_phone_number"],
				"name" => "twilio_user[user_phone_number]",
				"input_type" => "text",
				"wrapper_atts" => array(
					"class" => "phone_number"
				)
			),
			"twilio_number" => array(
				"label" => __( "Twilio Number: ", POD_TWILIO_TEXT_DOMAIN ),
				"input_type" => "label",
				"value" => $twilio_user_info["twilio_phone_number"],
				"wrapper_atts" => array(
					"class" => "twilio_number"
				)
			)
		);
		return apply_filters( "pod_twilio_user_details", $sections );
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
		echo apply_filters( "pod_twilio_register_success_error_notices", $notices, $this->_notices );
	}
	
	/**
	*	get current active tab
	*	@return string - array key of the tabs array
	**/
	private function _active_tab(){
		return apply_filters( "pod_twilio_user_details_current_tab", "user_details" );
	}
}