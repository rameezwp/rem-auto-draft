<?php
/*
Plugin Name: Auto Draft Properties
Description: Addon for Real Estate Manager Pro
Plugin URI: https://webcodingplace.com
Author: WebCodingPlace
Author URI: https://webcodingplace.com
Version: 1.0
License: GPL2
*/

add_filter( 'rem_admin_settings_fields', 'rem_custom_admin_settings_fields', 20, 1 );

function rem_custom_admin_settings_fields($fieldsData){
	foreach ($fieldsData as $key => $panel) {
		if (isset($panel['panel_name']) && $panel['panel_name'] == 'advanced_settings') {
			$new_fields = array(
				array(
	                'type' => 'number',
	                'name' => 'auto_draft_time',
	                'title' => __( 'Property will be set from Published to Draft Automatically', 'real-estate-manager' ),
	                'help' => __( 'Set time in days. If left blank, property will stay published.', 'real-estate-manager' ),
				),
			);
			$fieldsData[$key]['fields'] = array_merge($panel['fields'], $new_fields);
		}
		if (isset($panel['panel_name']) && $panel['panel_name'] == 'email_messages') {
			$new_fields = array(
				array(
	                'type' => 'textarea',
	                'name' => 'draft_message',
	                'title' => __( 'Notify Message to Agent when property is set to draft', 'real-estate-manager' ),
	                'help' => __( 'This message will be sent to agent when property is set from published to draft', 'real-estate-manager' ),
				),
			);
			$fieldsData[$key]['fields'] = array_merge($panel['fields'], $new_fields);
		}
	}
	return $fieldsData;
}

add_action( 'save_post', "rem_auto_draft_activate" );

function rem_auto_draft_activate($post_id) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
    return;
	
	$post_type = get_post_type($post_id);

    if ( "rem_property" != $post_type ) return;

    if (rem_get_option('auto_draft_time', '') != '' ) {
    	$time_in_seconds = 86400 * intval(rem_get_option('auto_draft_time'));
    	wp_clear_scheduled_hook( 'rem_post_status_check', array($post_id) );
		wp_schedule_single_event( time() + $time_in_seconds, "rem_post_status_check", array($post_id) );
    }
}

add_action('rem_post_status_check', 'rem_check_for_old_props', 10, 1);

function rem_check_for_old_props($post_id) {

	wp_update_post(array( 'ID'    =>  $post_id, 'post_status'   =>  'draft' ));

	$agent_id = get_post_field( 'post_author', $post_id );
	$agent_info = get_userdata($agent_id);
	$email = $agent_info->user_email;
	$to = $email;
	$subject = 'Property Expired';
	$headers = array('Content-Type: text/html; charset=UTF-8');
	$message = rem_get_option('draft_message');

	$message = str_replace("%property_id%", $post_id, $message);
	$message = str_replace("%property_url%", get_permalink( $post_id ), $message);
	$message = str_replace("%property_title%", get_the_title( $post_id ), $message);

	wp_mail( $to, $subject, $message, $headers );			
}


?>