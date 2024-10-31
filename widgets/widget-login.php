<?php
class POD_Twilio_Login_Widget extends WP_Widget {
	private $_errors = array();
	
	public function __construct() {
		$widget_ops = array( 
			'classname' => 'pod_twilio_login_widget',
			'description' => 'Twilio Login Widget',
		);
		parent::__construct( 'pod_twilio_login_widget', 'Login', $widget_ops );
		if( ! is_admin() ){			
			if( isset( $_POST["pod_twilio_login"] ) ){
				$userinfo = array_filter( $_POST["pod_twilio_user"] );
				if( empty( $userinfo ) ){
					array_push( $this->_errors, __( "Input fields are empty.", POD_TWILIO_TEXT_DOMAIN ) );
				}
				else{
					$user = wp_signon( $userinfo, false );
					if( is_wp_error( $user ) ){
						$this->_errors = $user->get_error_messages();
					}
					else{
						$settings = $this->get_settings();
						$widget_id = $_POST["widget_id"];
						$redirect = $settings[$widget_id]["redirect_page"];
						wp_redirect( get_permalink($redirect) );
						exit();
					}
				}
			}
		}
	}
	
	/**
	 * Outputs the options form on admin
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Login', POD_TWILIO_TEXT_DOMAIN );
		$pages = get_pages();
		$redirect_page = ! empty( $instance['redirect_page'] ) ? $instance['redirect_page']: 0;
?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', POD_TWILIO_TEXT_DOMAIN ); ?></label> 
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
			<label for="<?php echo esc_attr( $this->get_field_id( 'redirect_page' ) ); ?>"><?php esc_attr_e( 'After Login Redirect to:', POD_TWILIO_TEXT_DOMAIN ); ?></label> 
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'redirect_page' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'redirect_page' ) ); ?>">
				<option value="0"><?php esc_html_e( "Select", POD_TWILIO_TEXT_DOMAIN );?></option>
				<?php
					foreach( $pages as $page ){
						if( $page->ID == $redirect_page ){
							echo "<option value=\"".$page->ID."\" selected>".$page->post_title."</option>";
						}
						else{
							echo "<option value=\"".$page->ID."\">".$page->post_title."</option>";
						}
					}
				?>
			</select>
		</p>
<?php 
	}
	
	/**
	 * Outputs the content of the widget
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {	
		if( ! is_user_logged_in() ){
			echo $args['before_widget'];
			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
			}
			
			if( ! empty( $this->_errors ) ){
				$this->_display_errors();
			}
		?>
		<div class="form">
			<form class="login-form" name="pod_twilio_login" method="post">
				<div class="input-wrapper">
					<input type="text" name="pod_twilio_user[user_login]", placeholder="<?php esc_attr_e( "Username", POD_TWILIO_TEXT_DOMAIN ); ?>"/>
				</div>
				<div class="input-wrapper">
					<input type="password" name="pod_twilio_user[user_password]", placeholder="<?php esc_attr_e( "Password", POD_TWILIO_TEXT_DOMAIN ); ?>"/>
				</div>
				<input type="hidden" value="<?php echo $this->number; ?>" name="widget_id">
				<button type="submit" name="pod_twilio_login"><?php esc_html_e( "Login", POD_TWILIO_TEXT_DOMAIN ); ?></button> <p class="message"><a class='forgot_password' href="<?php echo wp_lostpassword_url(); ?>"><?php esc_html_e( "Forgot Password?", POD_TWILIO_TEXT_DOMAIN ); ?></a></p>
			</form>
		</div>
		<?php
		echo $args['after_widget'];
		}
	}
	
	/**
	*	displays login errors
	*	@param null
	**/
	private function _display_errors(){
		echo '<div class="pod_twilio_widget_login_error">';
		if( is_array( $this->_errors ) ){
			foreach( $this->_errors as $error ){
				echo $error ."<br />";
			}
		}
		else{
			echo esc_html( $this->_errors, POD_TWILIO_TEXT_DOMAIN );
		}
		echo '</div>';	
	}
}