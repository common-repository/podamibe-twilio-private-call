<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class POD_Twilio_Admin_Supported_Countries extends POD_Twilio_Admin {	
	
	/**
	*	@var instance of POD_TWILIO_SUPPORTED_COUNTRIES 
	*	@private
	**/	
	private $_countries;
	
	/**
	*	@var array
	*	@private
	*	all supported countries
	*/
	private $_supported_countries;
	
	/* 
	*	@var integer
	*	@private
	*	paginate number
	*/
	private $_pagenum;	
	
	/**
	*	@var string
	*	@private
	*	operation (add/edit/delete)
	**/	
	private $_operation;
	
	/**
	* 	constructor of this class
	*	inititalizes total countries and limit
	*	add/edit/delete country 
	*/
	public function __construct() {
		parent::__construct();
		$this->_limit = apply_filters( "pod_twilio_admin_supported_countries_limit", 10 );
		$this->_pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;		
		$this->_countries = new POD_Twilio_Supported_Countries();
		
		if( isset( $_REQUEST["operation"] ) ){
			$this->_operation = $_REQUEST["operation"];
		}
		
		if( isset( $this->_operation) ){
			if( isset( $_POST["save_country"] ) ){
				/*
				*	add supported country
				*/
				if( $this->_operation == "add" ){					
					if( pod_verify_nonce( 'pod_twilio_add_edit_supported_country' ) ) {
						$added = $this->_countries->add_country();
						$this->_message = $added["message"];
						$this->_message_type = $added["status"];
					}
				}
			
				/*
				*	edit supported country
				*/
				if( $this->_operation == "edit" ){
					if( pod_verify_nonce( 'pod_twilio_add_edit_supported_country' ) ) {	//check nonce
						$updated = $this->_countries->edit_country();
						$this->_message = $updated["message"];
						$this->_message_type = $updated["status"];
					}
				}
			}
			
			/* 
			* delete country
			*/
			if( $this->_operation == "delete" ){
				if( pod_verify_nonce( 'pod_twilio_delete_supported_country' ) ) {			
					$deleted = $this->_countries->delete_supported_country( $_POST["country_id"] );
					$this->_message = $deleted["message"];
					$this->_message_type = $deleted["status"];
				}
			}
		
		}	
		
		$this->_supported_countries = $this->_countries->get_countries( $this->_limit,$this->_pagenum );		
	}
		
	/**
	*	shows lists of supported countries
	*	add/edit/delete supported countries form using thickbox
	*/
	public function view_supported_countries(){		
		/* show error or success messages on add edit delete*/
		$this->pod_twilio_show_message();		
?>
	<!-- show list of countries here -->
		<div class="wrap">
			<h1>
				<?php esc_html_e( "Supported Countries", POD_TWILIO_TEXT_DOMAIN ); ?>
				<a href="#TB_inline?width=550&height=225&inlineId=add-edit-supported-country" title="Add Country" id="add_country" class="thickbox page-title-action"><?php _e("Add New", POD_TWILIO_TEXT_DOMAIN ); ?></a>
			</h1>
			<p class="about-description"><h4><?php esc_html_e( "Countries supported for voice calling.", POD_TWILIO_TEXT_DOMAIN ); ?><h4></p>
				<table class="wp-list-table widefat fixed striped pages">
					<thead>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( "S.N.", POD_TWILIO_TEXT_DOMAIN) ?></th>
							<th scope="row"><?php esc_html_e( "Country", POD_TWILIO_TEXT_DOMAIN ); ?></th>
							<th scope="row"><?php esc_html_e( "Country Code", POD_TWILIO_TEXT_DOMAIN ); ?></th>
							<th scope="row"><?php esc_html_e( "ISO Code", POD_TWILIO_TEXT_DOMAIN ); ?></th>
							<th scope="row"><?php esc_html_e("Action", POD_TWILIO_TEXT_DOMAIN ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php						
						$sn = ( ( $this->_pagenum-1 ) * ( $this->_limit ) ) + 1;
						foreach ($this->_supported_countries as $supported_country){
					?>
							<tr valign="top">
								<td><?php echo $sn; ?></td>
								<td><?php echo $supported_country->country_name; ?></td>
								<td><?php echo $supported_country->country_code; ?></td>
								<td><?php echo $supported_country->country_iso_code; ?></td>
								<td><a href="#TB_inline?width=550&height=225&inlineId=add-edit-supported-country" data-id="<?php echo $supported_country->country_id; ?>" title="<?php esc_attr_e( "Edit Country", POD_TWILIO_TEXT_DOMAIN ); ?>" class="edit_country thickbox button"><?php esc_html_e("Edit", POD_TWILIO_TEXT_DOMAIN ); ?></a> <a href="#TB_inline?width=360&height=70&inlineId=delete-supported-country" title="<?php esc_attr_e( "Delete Country", POD_TWILIO_TEXT_DOMAIN ); ?>" class="delete_country thickbox button" data-id="<?php echo $supported_country->country_id; ?>"><?php esc_html_e( "Delete", POD_TWILIO_TEXT_DOMAIN ); ?></a><td>
							</tr>
					<?php
							$sn++;
						}
					?>
					</tbody>
				</table>			
			<?php 
				/* total pages = total_countries/countries per page */
				$num_of_pages = ceil( $this->_total_countries / $this->_limit );	
				
				/* show pagination links */
				pod_twilio_paginate_links( $num_of_pages, $this->_pagenum );		
			?>
		</div>
	<?php	
		/* 
		* add thickbox 
		* to add or edit country
		*/
		add_thickbox();		
	?>
		<!-- add or edit supported country form -->
		<div id="add-edit-supported-country" style="display:none;">
			<div id="loader"></div>
			<form method="post" id="add_edit_country_form" action=""> 	
				<div class="add_country_error" align="center" style="color:red;"></div>
				<?php wp_nonce_field("pod_twilio_add_edit_supported_country"); ?>
				<table class="form-table">
					<tr>
						<td><?php esc_html_e( "Country Name", POD_TWILIO_TEXT_DOMAIN ); ?></td>
						<td><input type="text" class="country_name" name="country_name"></td>
					</tr>
					<tr>
						<td><?php esc_html_e( "Country Code", POD_TWILIO_TEXT_DOMAIN ); ?></td>
						<td><input type="text" class="country_code" name="country_code"></td>
					</tr>
					<tr>
						<td><?php esc_html_e( "Country ISO Code", POD_TWILIO_TEXT_DOMAIN ); ?></td>
						<td><input type="text" class="country_iso" name="country_iso"></td>
					</tr>
				</table>
				<input type="hidden" class="operation" name="operation">
				<input type="hidden" class="country_id" name="country_id">
				<?php submit_button( esc_attr__( "Save", POD_TWILIO_TEXT_DOMAIN ), "primary", "save_country", false, array( "id" => "save_country_info" ) ); ?>
			</form>
		</div>
		<!-- end add edit supported country form -->	
		
		<!-- confirm delete country -->
		<div id="delete-supported-country" style="display:none;">
			<div><?php esc_html_e( "Are Sure You Want to delete this Country?", POD_TWILIO_TEXT_DOMAIN ); ?></div>
			<div align="center" style="margin-top:15px;">
			<div class="button yes" style="margin: 0px 50px 0px 0px;" onclick="delete_country_from_submit( event );"><?php esc_html_e( "Yes", POD_TWILIO_TEXT_DOMAIN ); ?></div><div class="button no" onclick="tb_remove();"><?php esc_html_e( "NO", POD_TWILIO_TEXT_DOMAIN ); ?></div>
			</div>
			<form method="post" id="delete_country_form" action="">
				<?php wp_nonce_field("pod_twilio_delete_supported_country"); ?>
				<input type="hidden" class="operation" name="operation" value="delete">
				<input type="hidden" class="country_id" name="country_id">
			</form>
		</div>
		<!-- end confirm delete country -->
<?php
	}
}