<?php
/*
Plugin Name: WP Add Mime Types 
Plugin URI: 
Description: The plugin additionally allows the mime types and file extensions to WordPress.
Version: 2.4.0
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
	'wp_add_mime_types'	=>	'2.4.0',
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
	$mime_type_values = false;
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) 
    	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

	if(is_multisite() && is_plugin_active_for_network($plugin_basename))
		$settings = get_site_option('wp_add_mime_types_network_array');
	else
		$settings = get_option('wp_add_mime_types_array');
		
	if(!isset($settings['mime_type_values']) || empty($settings['mime_type_values'])) return $mimes;
	else
		$mime_type_values = unserialize($settings['mime_type_values']);
	
	if(!empty($mime_type_values)){
		foreach ((array)$mime_type_values as $line){
			// Ignore to the right of '#' on a line.
			$line = substr($line, 0, strcspn($line, '#'));
			// Escape Strings
			$line = wp_strip_all_tags($line);
			// If 2 or more "=" character in the line data, it will be ignored.
			$line_value = explode("=", $line);
			if(count($line_value) != 2) continue;
			// "　" is the Japanese multi-byte space. If the character is found out, it automatically change the space. 
			$mimes[trim($line_value[0])] = trim(str_replace("　", " ", $line_value[1])); 
		}
	}
	
	return $mimes;
}

// Register the Procedure process to WordPress.
add_filter( 'upload_mimes', 'add_allow_upload_extension');

// Exception for WordPress 4.7.1 file contents check system using finfo_file (wp-includes/functions.php)
// In case of custom extension in this plugins' setting, the WordPress 4.7.1 file contents check system is always true.
function add_allow_upload_extension_exception( $file, $filename, $mimes ) {
	global $plugin_basename;
	$mime_type_values = false;

	$ext = $type = $proper_filename = false;
	if(isset($file['ext'])) $ext = $file['ext'];
	if(isset($file['type'])) $ext = $file['type'];
	if(isset($file['proper_filename'])) $ext = $file['proper_filename'];
	if($ext != false && $type != false) return $file;	

	// If file extension is 2 or more 
	$f_sp = explode(".", $mimes);
	$f_exp_count  = count ($f_sp);

	// Filename type is "XXX" (There is not file extension). 
	if($f_exp_count <= 1){
		return $file;
	/* Even if the file extension is "XXX.ZZZ", "XXX.YYY.ZZZ", "AAA.XXX.YYY.ZZZ" or more, it always picks up  the tail of the extensions.
	*/
	}else{
		$f_name = $f_sp[0];
		$f_ext  = $f_sp[$f_exp_count - 1];
	}
 
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) 
    	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

	if(is_multisite() && is_plugin_active_for_network($plugin_basename))
		$settings = get_site_option('wp_add_mime_types_network_array');
	else
		$settings = get_option('wp_add_mime_types_array');
		
	if(!isset($settings['mime_type_values']) || empty($settings['mime_type_values'])) return compact( 'ext', 'type', 'proper_filename' );
	else
		$mime_type_values = unserialize($settings['mime_type_values']);

	$flag = false;
	if(!empty($mime_type_values)){
		foreach ((array)$mime_type_values as $line){
			// Ignore to the right of '#' on a line.
			$line = substr($line, 0, strcspn($line, '#'));
			// Escape Strings
			$line = wp_strip_all_tags($line);
			
			$line_value = explode("=", $line);
			if(count($line_value) != 2) continue;
			// "　" is the Japanese multi-byte space. If the character is found out, it automatically change the space. 		
			if(trim($line_value[0]) === $f_ext){
				$ext = $f_ext;
				$type = trim(str_replace("　", " ", $line_value[1])); 
				$flag = true;
				break;
			}
		}
	}
	// WordPress sanitizes the filename in case of 2 or more extensions. 
	// ex. XXX.YYY.ZZZ --> XXX_.YYY.ZZZ.
	// The following function fixes the sanitized extension when a file is uploaded in the media in case of allowed extensions. 
	// ex. XXX.YYY.ZZZ -- sanitized --> XXX_.YYY.ZZZ -- fixed the plugin --> XXX.YYY.ZZZ
	// In detail, please see sanitize_file_name function in "wp-includes/formatting.php".
	if($f_exp_count > 2){
		function remove_underscore($filename, $filename_raw){
			return str_replace("_.", ".", $filename);
		}
		add_filter( 'sanitize_file_name', 'remove_underscore', 10, 2 );
	}

	if($flag)
	    return compact( 'ext', 'type', 'proper_filename' );
	else
		return $file;
}

add_filter( 'wp_check_filetype_and_ext', 'add_allow_upload_extension_exception',10,3);
