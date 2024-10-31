<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class POD_TWilio_Contact_List_Shortcode {
	/**
	*	A reference to an instance of this class.
	* 	@private
	* 	@var   POD_TWilio_Contact_List_Shortcode
	*/
	private static $_instance = null;
	
	/**
	*	@private
	*	@array
	*	tabs to display
	**/
	private $_tabs;
	
	/**
	*	A reference to an instance class POD_Twilio_User.
	*	Current User
	* 	@private
	* 	@var   POD_Twilio_User
	*/
	private $_user;
	
	/*
	*	@var integer
	*	@private
	*	current user id 
	*/
	private $_id;
	
	/**
	*	Constructor of this class
	*	Initializes shortcode
	**/		
	private function __construct(){
		add_shortcode( "pod-twilio-contactlist", array( $this, "init_shortcode" ) );
	}
	
	/**
	* Returns an instance of this class
	* @public
	* @return   POD_TWilio_Contact_List_Shortcode
	*/
	public static function init(){
		if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	*	Callback function of the shortcode
	*	initializes the shortcode and returns shortcode content
	*	@return string
	**/
	public function init_shortcode(){		
		$this->_tabs = $this->_set_tabs();	
		do_action("pod_twilio_contactlist_shortcode_init");
		if( is_user_logged_in() ){
			$this->_id = get_current_user_id();
			$this->_user = new POD_Twilio_User( $this->_id );
			ob_start();
				echo "<div id=\"pod-twilio-contacts\">";
					if( $this->_user->has_twilio_activated ){
						$this->_show_contacts();
					}
					else{
						esc_html_e( "Please activate private call first.", POD_TWILIO_TEXT_DOMAIN );
					}
				echo "</div>";
			return ob_get_clean();
		}
	}
	
	/**
	*	show user contacts tabs and contents
	**/
	private function _show_contacts(){
		$active_tab = $this->_active_tab();
		
		echo "<ul class=\"pod-twilio-tabs\">";
		foreach( $this->_tabs as $key => $tab ){
			if( $key == $active_tab ){
				$active = "pod-twilio-active-tab";
			}
			else{
				$active = "";
			}
			echo "<li>";
			echo "<a data-tab=\"". $tab["show"] ."\" class=\"pod-twilio-show-tab-content ".$active."\">".$tab["title"]."</a>";
			echo "</li>";
		}
		
		do_action("pod_twilio_contact_list_add_tab", $active_tab );
		echo "</ul>";	
		
		$callbacks = array_filter( array_combine( array_keys( $this->_tabs ), array_column( $this->_tabs, 'tab_content' ) ) );
			
		$contents = "";
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
			
			$contents .= "<div ". $wrapper_atts ." ".$show.">";
			$contents .= call_user_func( $callback["content_callback"] );
			$contents .= "</div>";
		}
		echo $contents;
		
		do_action( "pod_twilio_contact_list_add_tab_content", $active_tab );
	}
	
	/**
	*	get tabs
	*	@return array
	**/
	private function _set_tabs(){
		$tabs = array(
			"verified" 	=> array(
				"title" 		=> __( "Verified Contacts", POD_TWILIO_TEXT_DOMAIN ),
				"show" 			=> "#verified-contacts",
				"tab_content" 	=> array(
					"content_callback" 		=> array( $this, "_load_verified_contacts" ),
					"content_wrapper_atts" 	=> array(
						"id" 	=> "verified-contacts",
						"class" => "pod-twilio-tab-content verified"
					)
				)
				
			),
			"sent" 	=> array(
				"title" 		=> __( "Contact Requests", POD_TWILIO_TEXT_DOMAIN ),	
				"show" 			=> "#pending-requests",
				"tab_content" 	=> array(
					"content_callback" 		=> array( $this, "_load_pending_contact_requests" ),
					"content_wrapper_atts" 	=> array(
						"id" 	=> "pending-requests",
						"class"	=> "pod-twilio-tab-content buy-call"
					)
				)				
			)
		);		
		
		return apply_filters( "pod_twilio_contact_list_tabs", $tabs );
	}
	
	/**
	*	show verified contact lists
	*	verified contacts tab content
	**/
	private function _load_verified_contacts(){
		if( $this->_user->has_twilio_activated ){
			$current_page = 1;
			$limit = 10;
			$offset = ( $current_page - 1 ) * $limit;
		
			$total_verified_contacts = $this->_user->user_contacts->total_verified_contacts();
			
			$total_pages = ceil( $total_verified_contacts / $limit );
			
			$contacts = $this->_user->user_contacts->get_verified_contact_list( $limit, $offset );
			
			ob_start();
			foreach( $contacts as $contact ){
				$this->_show_verified_contact_details( $contact );
			}
			
			if( $total_pages > 1 ){
			?>
				<div id="show-more-verified-contacts" class="show-more">
					<button><?php esc_html_e( "Show More", POD_TWILIO_TEXT_DOMAIN ); ?></button>
				</div>
			<?php
			}
		}
		return ob_get_clean();			
	}
	
	/**
	*	show contact request lists
	*	contact requests tab content
	**/
	private function _load_pending_contact_requests(){
		$current_page = 1;
		$limit = 10;
		$offset = ( $current_page - 1 ) * $limit;	
		$total_pending_contacts = $this->_user->user_contacts->total_pending_contacts();
		
		$total_pages = ceil( $total_pending_contacts / $limit );
		
		$contacts = $this->_user->user_contacts->get_pending_contacts_list( $limit, $offset );
		
		ob_start();
		foreach( $contacts as $contact ){
			$this->_show_pending_contact_details( $contact );			
		}
		if( $total_pages > 1 ){
		?>
			<div id="show-more-pending-contacts" class="show-more">
				<button><?php esc_html_e( "Show More", POD_TWILIO_TEXT_DOMAIN ); ?></button>
			</div>
		<?php
		}
		return ob_get_clean();
	}
	
	/*
	*	verified contact details
	*	@param $contact object
	*/
	private function _show_verified_contact_details( $contact ){
		if( $this->_id == $contact->request_sender_id ){
			$contact_user = new POD_Twilio_User( $contact->request_receiver_id );
		}
		else{
			$contact_user = new POD_Twilio_User( $contact->request_sender_id );
		}
		
		if( $this->_user->user_contacts->get_contact_status( $contact ) == "Verified" ){
		?>
			<ul>
				<li class="user-details">
				<?php 
					echo get_avatar( $contact_user->_id, 50 )."&nbsp;"; 
					echo $contact_user->get_user_name();
				?>
					<div>
						<?php echo $contact_user->user_country; ?>
					</div>
				</li>
				<li class="twilio-phone">
					<i class="fa fa-phone"></i>&nbsp;<?php echo $contact_user->twilio_number; ?>
				</li>
				<li class="remove-twilio-contact">
					<a data-user-id="<?php echo $contact_user->_id; ?>" class="pod-twilio-remove-contact"><?php esc_html_e( "Remove", POD_TWILIO_TEXT_DOMAIN ); ?></a>
				</li>
			</ul>
		<?php
		}
	}
	
	/*
	*	pending contact details
	*	@param $contact object
	*/
	private function _show_pending_contact_details( $contact ){
		if( $this->_id == $contact->request_sender_id ){
			$contact_user = new POD_Twilio_User( $contact->request_receiver_id );
		}
		else{
			$contact_user = new POD_Twilio_User( $contact->request_sender_id );
		}
		?>
		<ul>
			<li class="user-details">
				<?php echo get_avatar( $contact_user->twilio_user_details["ID"], 50 ) . "&nbsp;" ; ?>			
				<?php echo $contact_user->get_user_name(); ?>
				<div>					
					<?php echo $contact_user->user_country; ?>	
				</div>					
			</li>				
			<li class="contact-request-action">
				<?php echo $this->_user->user_contacts->action_button( $contact, $contact_user ); ?>
			</li>
		</ul>
		<?php		
	}
	
	/**
	*	get current active tab
	*	@return string - array key of the tabs array
	**/
	private function _active_tab(){
		return apply_filters( "pod_twilio_contact_list_current_tab", "verified" );
	}
}