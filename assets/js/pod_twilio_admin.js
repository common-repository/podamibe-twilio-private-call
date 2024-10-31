jQuery(document).ready(function(){
	/**
	*	load country info on clicking edit country button
	**/
	jQuery( ".edit_country" ).click( function() {
		jQuery( "#add_edit_country_form .add_country_error" ).html("");
		jQuery( "#add_edit_country_form" ).find( ".operation" ).val( 'edit' );
		var country_id = jQuery( this ).data( "id" );
		jQuery.ajax({
			url: ajaxurl,
			method: "post",
			dataType: "json",
			data:{ "action" : "get_country_details", "country_id" : country_id },
			beforeSend: function(){
							
			},
			success: function( countryInfo ) {		
				jQuery( "#add_edit_country_form" ).find( ".country_id" ).val( countryInfo.country_id );
				jQuery( "#add_edit_country_form" ).find( ".country_name" ).val( countryInfo.country_name );
				jQuery( "#add_edit_country_form" ).find( ".country_code" ).val( countryInfo.country_code );
				jQuery( "#add_edit_country_form" ).find( ".country_iso" ).val( countryInfo.country_iso_code );
			},
			complete: function() {
							
			}
		});
	});
	
	/**
	*	clear add edit country form on clicking add country button
	**/
	jQuery( "#add_country" ).click( function() {
		clear_add_edit_country_form();
	});
	
	/**
	*	set delete country id in delete country form on clicking delete country button
	*	when showing confirm delete popuop
	**/
	jQuery( ".delete_country" ).click( function() {
		var country_id = jQuery( this ).data( "id" );
		jQuery( "#delete_country_form .country_id" ).val( country_id );
	});
});

/**
*	clear add edit country form
**/				
function clear_add_edit_country_form() {
	jQuery( "#add_edit_country_form .add_country_error" ).html("");
	jQuery( "#add_edit_country_form" ).find( ".operation" ).val( 'add' );
	jQuery( "#add_edit_country_form" ).find( ".country_id" ).val( "" );
	jQuery( "#add_edit_country_form" ).find( ".country_name" ).val( "" );
	jQuery( "#add_edit_country_form" ).find( ".country_code" ).val( "" );
	jQuery( "#add_edit_country_form" ).find( ".country_iso" ).val( "" );
}

/**
*	submit delete country form on confirm delete
**/
function delete_country_from_submit( evt ) {
	var delete_confirm_btn = jQuery( evt.target );
	if( delete_confirm_btn.hasClass( 'yes' ) ) {
		jQuery( "#delete_country_form" ).submit();
	}
}