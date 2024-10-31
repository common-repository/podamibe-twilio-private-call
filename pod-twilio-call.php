<?php
/**
	Plugin Name:	Podamibe Twilio Private Call
	Version:		1.0.1
	Plugin URI:		http://podamibenepal.com/wordpress-plugins/
	Author URI: 	http://podamibenepal.com
	Description:	Free Wordpress plugin for twilio private call.
	Author:			Podamibe Nepal
	Text Domain:	pod_twilio
	License: 		GPLv2 or later
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb;

/*
*	define global variables
*/

/* database prefix */
define( "POD_TWILIO_DB_PREFIX", $wpdb->prefix."pod_twilio_");

/* plugin version */
define( 'POD_TWILIO_PLUGIN_VERSION', '1.0.0' );

/* database version */
define( 'POD_TWILIO_DB_VERSION', '1.0.0' );

/* plugin directory path */
define( 'POD_TWILIO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/* plugin url */
define( 'POD_TWILIO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/* plugin icon url */
define( 'POD_TWILIO_PLUGIN_ICON', "" );

/* menu position in admin section */
define( 'POD_TWILIO_PLUGIN_MENU_POSITION', NULL );

/* text domain */
define( 'POD_TWILIO_TEXT_DOMAIN', 'pod_twilio');

/*
*	database tables
*/

/* countries table */
define( 'POD_TWILIO_SUPPORTED_COUNTRIES', POD_TWILIO_DB_PREFIX."supported_countries");

/* users with twilio account */
define( 'POD_TWILIO_USERS', POD_TWILIO_DB_PREFIX."users" );

/* remaining call duration bought */
define( 'POD_TWILIO_CALL_DURATION', POD_TWILIO_DB_PREFIX."call_duration" );

/* user contact list */
define( 'POD_TWILIO_CONTACT_LIST', POD_TWILIO_DB_PREFIX."contact_list" );

/* user payment history */
define( 'POD_TWILIO_PAYEMENT_HISTORY', POD_TWILIO_DB_PREFIX."user_payment_history" );

/* wordpress users table */
define( 'POD_USERS', $wpdb->prefix."users" );

class POD_Twilio {
	
	/**
	* A reference to an instance of this class.
	* @private
	* @var   POD_Twilio
	*/
	private static $_instance = null;
	
	/**
	*	A reference to the class POD_Twilio_Settings
	* 	@private
	* 	@var  POD_Twilio_Settings
	*/
//	private $POD_Twilio_Settings;
	
	/**
	* constructor of this class
	**/
	private function __construct() {			
		$this->_init();		
		do_action( 'pod_twilio_loaded' );
		 add_filter("plugin_row_meta", array($this, 'get_extra_meta_links'), 10, 4);       
           
	}

	/**
	 * Adds extra links to the plugin activation page
	 */
	public function get_extra_meta_links($meta, $file, $data, $status) {

			if (plugin_basename(__FILE__) == $file) {
					$meta[] = "<a href='http://shop.podamibenepal.com/forums/forum/support/' target='_blank'>" . __('Support', 'pod_twilio') . "</a>";
					$meta[] = "<a href='http://shop.podamibenepal.com/downloads/podamibe-twilio-private-call/' target='_blank'>" . __('Documentation  ', 'pod_twilio') . "</a>";
					$meta[] = "<a href='https://wordpress.org/support/plugin/podamibe-twilio-private-call/reviews#new-post' target='_blank' title='" . __('Leave a review', 'pod_twilio') . "'><i class='ml-stars'><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg></i></a>";
			}
			return $meta;
	}
	
	/**
	* Returns an instance of this class
	* @public
	* @return   POD_Twilio
	*/
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	*	initialize plugin
	*	include necessary files
	*	declare necessary hooks
	**/
	private function _init(){
		$this->_includes();		
		register_activation_hook( __FILE__, array("POD_Twilio_Install","install") );
		register_deactivation_hook( __FILE__, array("POD_Twilio_Install","unistall") );		
		$this->init_hooks();	
	}
	
	/**
	*	this function declares necessary hooks
	**/
	private function init_hooks(){		
		add_action( 'admin_menu', array( $this, "pod_twilio_admin_menu_section" ) );	
	//	add_action( 'admin_init',array ( $this, 'register_master_twilio_settings' ) );
		add_action( "admin_enqueue_scripts", array( $this, "enqueue_admin_scripts" ) );
		add_action( "wp_enqueue_scripts", array( $this, "enqueue_scripts" ) );
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		add_filter( 'template_include', array( $this, 'assign_custom_templates_to_custom_pages' ), 99 );
		add_action( "after_setup_theme", array( $this, "create_shortcodes" ) );
		$this->_ajax_hooks();	
	}

	/**
	*	@public
	*	register settings in wordpress settings api
	**/
   /*  public function register_master_twilio_settings() {
        register_setting("pod_twilio_master_ac_settings", "pod_twilio_master_ac_options");
		register_setting("pod_twilio_payment_settings", "pod_twilio_payment_options");
    } */
	
	/**
	*	@public
	*	add shortcodes and initializes the shortcodes
	**/
	public function create_shortcodes(){
		POD_Twilio_User_Sign_Up_Shortcode::init();
		POD_Twilio_Activate_Shortcode::init();
		POD_TWilio_User_Profile_Shortcode::init();
		POD_TWilio_Users_List_Shortcode::init();
		POD_TWilio_Contact_List_Shortcode::init();
	}
	
	/**
	*	@public
	*	register plugin widgets
	**/
	public function register_widgets(){
		register_widget( 'POD_Twilio_Login_Widget' );
	}
	
	/**
	*	enqueue admin scripts
	**/
	public function enqueue_admin_scripts() {
		global $pagenow;
		wp_register_script( "pod_twilio_admin", POD_TWILIO_PLUGIN_URL ."/assets/js/pod_twilio_admin.js", array( "jquery" ) );
		if( ( $pagenow == "admin.php"  && ( isset( $_GET["page"] ) && ( $_GET["page"] == "pod_twilio_supported_countries" ) ) ) ) {
			wp_enqueue_script( "pod_twilio_admin" );
		}
	}
	
	/**
	*	enqueue frontend scripts
	**/
	public function enqueue_scripts(){
		wp_register_script( "pod_twilio_frontend", POD_TWILIO_PLUGIN_URL ."/assets/js/frontend.js", array( "jquery" ) );
		wp_enqueue_script( "pod_twilio_frontend" );
		
		$localize = array( 
			"pod_twilio_master_ac_settings" 	=> get_option( "pod_twilio_master_ac_settings" ),
			"pod_twilio_payment_settings" 		=> get_option( "pod_twilio_payment_settings" ),
			"ajaxurl" 							=> admin_url( "admin-ajax.php" )
		);
		
		wp_localize_script( "pod_twilio_frontend", "pod_twilio", $localize );
		
		wp_register_style( "pod_twilio_frontend", POD_TWILIO_PLUGIN_URL ."/assets/css/frontend.css" );
		wp_register_style( "pod_twilio_font_awesome", POD_TWILIO_PLUGIN_URL ."/assets/css/font-awesome.min.css" );
		
		wp_enqueue_style( "pod_twilio_font_awesome" );
		wp_enqueue_style( "pod_twilio_frontend" );
		
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
			
	}
	
	/**
	*	@public
	*	assign templates to twilio dial page, dial callback page and paypal ipn page
	*	plugin path/templates/
	**/
	public function assign_custom_templates_to_custom_pages( $template ){
		if( is_page() ){
			switch( get_the_ID() ){
				case( get_option( "pod_twilio_dial_number_page_id" ) ):
					$template = POD_TWILIO_PLUGIN_PATH.'templates/template-dial-number.php';
					break;
				case( get_option( "pod_twilio_dial_callback_page_id" ) ):
					$template = POD_TWILIO_PLUGIN_PATH.'templates/template-dial-callback.php';
					break;
				case( get_option( "pod_twilio_paypal_ipn_page_id" ) ):
					$template = POD_TWILIO_PLUGIN_PATH.'templates/template-paypal-notification.php';
					break;
				default:
					$template = $template;
			}
		}
		return $template;
	}
	
	/*
	*	ajax hooks
	*/
	public function _ajax_hooks() {
		add_action( "wp_ajax_get_country_details", array( new POD_Twilio_Supported_Countries, "get_country_info_by_country_id" ) );
		add_action( "wp_ajax_pod_twilio_add_to_contact", "pod_twilio_add_to_contact_list" );
		add_action( "wp_ajax_pod_twilio_accept_contact_request", "pod_twilio_accept_contact_request" );
		add_action( "wp_ajax_pod_twilio_load_more_verified_contacts", "pod_twilio_load_more_verified_contacts" );
		add_action( "wp_ajax_pod_twilio_load_more_pending_contacts","pod_twilio_load_more_pending_contacts" );
		add_action( "wp_ajax_pod_twilio_remove_contact", "pod_twilio_remove_contact" );
		add_action( "wp_ajax_pod_twilio_verify_contact", "pod_twilio_verify_contact" );
		add_action( "wp_ajax_pod_twilio_check_number_verified", "pod_twilio_check_number_verification_status" );
	}
	
	/**
	*	include necessary files
	*/
	private function _includes(){
		/* if( ! class_exists( "Services_Twilio" ) ){		//php helper library 4.x
			include_once( POD_TWILIO_PLUGIN_PATH . 'includes/twilio-php-master/Services/Twilio.php' );
		} */
		
		if( ! class_exists( "Twilio\Rest\Client" ) ){		//php helper libraray 5.x
			include_once( POD_TWILIO_PLUGIN_PATH . 'includes/twilio-php-master/Twilio/autoload.php' );
		}
		
		/* classes */
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-install.php' );		
		include_once( POD_TWILIO_PLUGIN_PATH . 'functions.php' );	
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-supported-countries.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-twilio-user.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-user-logs.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-payment-history.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-user-register.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-update-user-info.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-create-twilio-account.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-contact-request-handler.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'includes/class-user-contacts.php' );
		
		/* include admin section files */
		include_once( POD_TWILIO_PLUGIN_PATH . 'admin/abstract-class-twilio-admin.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'admin/twilio-settings.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'admin/supported-countries.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'admin/twilio-users.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'admin/user-logs.php' );
		
		/* shortcodes */
		include_once( POD_TWILIO_PLUGIN_PATH . 'shortcodes/twilio-activate.php' );
		include_once( POD_TWILIO_PLUGIN_PATH . 'shortcodes/user-signup.php' );	
		include_once( POD_TWILIO_PLUGIN_PATH . 'shortcodes/user-profile.php' );		
		include_once( POD_TWILIO_PLUGIN_PATH . 'shortcodes/users-list.php' );		
		include_once( POD_TWILIO_PLUGIN_PATH . 'shortcodes/contact-list.php' );		
		
		/* widgets */
		include_once( POD_TWILIO_PLUGIN_PATH . 'widgets/widget-login.php' );		
	}
	
	/**
	*	add admin menu
	**/
	public function pod_twilio_admin_menu_section(){
		$submenus = $this->pod_twilio_submenus("pod_twilio_settings","manage_options");	
		
		$menu_page = reset( $submenus );
	
		add_menu_page( esc_html__( "Twilio Admin", POD_TWILIO_TEXT_DOMAIN ), esc_html__( "Twilio Admin", POD_TWILIO_TEXT_DOMAIN ), $menu_page['capability'], "pod_twilio_settings", $menu_page['callback'], POD_TWILIO_PLUGIN_ICON, POD_TWILIO_PLUGIN_MENU_POSITION );
				
		foreach( $submenus as $submenu ){
			add_submenu_page( $submenu['parent_slug'], $submenu['page_title'], $submenu['menu_title'], $submenu['capability'], $submenu['menu_slug'], $submenu['callback'] );
		}
	}
	
	/*
	*	set admin menu submenus
	*	@return array
	*/
	private function pod_twilio_submenus( $menu_slug, $capability ){
		$POD_Twilio_Settings = new POD_Twilio_Settings();
		$submenus = array(
				array(
					"parent_slug" 	=> $menu_slug,
					"page_title" 	=> esc_html__( "Settings", POD_TWILIO_TEXT_DOMAIN ),
					"menu_title" 	=> esc_html__( "Settings", POD_TWILIO_TEXT_DOMAIN ),
					"capability" 	=> $capability,
					"menu_slug" 	=> $menu_slug,
				//	"callback" 		=>  array( $this, "load_twilio_settings_view" )
					"callback" 		=>  array( $POD_Twilio_Settings, "load_settings_view" )
				),
				array(
					"parent_slug" 	=> $menu_slug,
					"page_title" 	=> esc_html__( "Supported Countries", POD_TWILIO_TEXT_DOMAIN ),
					"menu_title" 	=> esc_html__( "Supported Countries", POD_TWILIO_TEXT_DOMAIN ) ,
					"capability" 	=> $capability,
					"menu_slug" 	=> "pod_twilio_supported_countries",
					"callback" 		=> array( $this, "load_supported_countries_view" )
				),
				array(
					"parent_slug" 	=> $menu_slug,
					"page_title" 	=> esc_html__( "Twilio Users", POD_TWILIO_TEXT_DOMAIN ),
					"menu_title" 	=> esc_html__( "Twilio Users", POD_TWILIO_TEXT_DOMAIN ),
					"capability" 	=> $capability,
					"menu_slug" 	=> "pod_twilio_users",
					"callback" 		=> array( $this,"load_users_section" )
				)
		);
		return apply_filters( "pod_twilio_admin_menus", array_merge( array(), $submenus ), $submenus );
	}
	
	/*
	*	callback function for twilio settings submenu
	*	load twilio settings page
	**/
	/* public function load_twilio_settings_view(){
		$POD_Twilio_Settings = new POD_Twilio_Settings();
		$POD_Twilio_Settings->load_settings_view();
	} */
	
	/**
	*	call back function for supported countries submenu
	*	view supported countries
	**/
	public function load_supported_countries_view(){
		$Admin_Supported_Countries = new POD_Twilio_Admin_Supported_Countries();
		$Admin_Supported_Countries->view_supported_countries();
	}
	
	/**
	*	callback function for twilio users submenu
	*	view twilio users
	**/
	public function load_users_section(){
		$Admin_Twilio_Users = new POD_Twilio_Admin_Twilio_Users();
		$Admin_Twilio_Users->load_user_info_section();
	}
}

$TPC = POD_Twilio :: instance();