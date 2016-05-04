<?php
/*
Plugin Name: WP Add Mime Types 
Plugin URI: 
Description: The plugin additionally allows the mime types and file extensions to WordPress.
Version: 2.0.0
Author: Kimiya Kitani
Author URI: http://kitaney-wordpress.blogspot.jp/
Text Domain: wp-add-mime-types
Domain Path: /lang
*/

// Multi-language support.
function enable_language_translation(){
 load_plugin_textdomain('wp-add-mime-types', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/');
}
add_action('plugins_loaded', 'enable_language_translation');

$plugin_basename = plugin_basename ( __FILE__ );

$default_var = array(
	'wp_add_mime_types'	=>	'2.0.0',
);

// Add Setting to WordPress 'Settings' menu for Multisite.
if(is_multisite()){
	add_action('network_admin_menu', 'network_add_to_settings_menu');
	require_once( dirname( __FILE__  ) . '/includes/network-admin.php');
}
add_action('admin_menu', 'add_to_settings_menu');
require_once( dirname( __FILE__  ) . '/includes/admin.php');



// Procedure for adding the mime types and file extensions to WordPress.
function add_allow_upload_extension( $mimes ) {
	global $plugin_basename;
	if(is_multisite() && is_plugin_active_for_network($plugin_basename))
		$settings = get_site_option('wp_add_mime_types_network_array');
	else
		$settings = get_option('wp_add_mime_types_array');

	if(!isset($settings['mime_type_values']) || empty($settings['mime_type_values'])) return $mimes;
	else
		$mime_type_values = unserialize($settings['mime_type_values']);

    foreach ($mime_type_values as $line){
      // If 2 or more "=" character in the line data, it will be ignored.
      $line_value = explode("=", $line);
      if(count($line_value) != 2) continue;

      // "　" is the Japanese multi-byte space. If the character is found out, it automatically change the space. 
      $mimes[trim($line_value[0])] = trim(str_replace("　", " ", $line_value[1])); 
    }
    
    //$mimes['dot'] = 'application/word';

    return $mimes;
}

// Register the Procedure process to WordPress.
add_filter( 'upload_mimes', 'add_allow_upload_extension' );

