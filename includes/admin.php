<?php
	
function add_to_settings_menu(){
	$admin_permission = 'manage_options';
	// add_options_page (Title, Setting Title, Permission, Special Definition, function name); 
	add_options_page(__('WP Add Mime Types Admin Settings', 'wp-add-mime-types'), __('Mime Type Settings','wp-add-mime-types'), $admin_permission, __FILE__,'admin_settings_page');
}

// Processing Setting menu for the plugin.
function admin_settings_page(){
	global $plugin_basename;
	$mime_type_values = false;
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) 
		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );	

	$admin_permission = 'manage_options';
	// Loading the stored setting data (wp_add_mime_types_array) from WordPress database.
	if(is_multisite() && is_plugin_active_for_network($plugin_basename)){
		$settings = get_site_option('wp_add_mime_types_network_array');   	
		$past_settings = get_option('wp_add_mime_types_array');
	}else
		$settings = get_option('wp_add_mime_types_array');

	$permission = false;
	// The user who can manage the WordPress option can only access the Setting menu of this plugin.
	if(current_user_can($admin_permission)) $permission = true; 
	// If the adding data is not set, the value "mime_type_values" sets "empty".
	if(!isset($settings['mime_type_values']))	$settings['mime_type_values'] = '';
	// When the adding data is saved (posted) at the setting menu, the data will update to the WordPress database after the security check
	if(isset($_POST['mime_type_values'])){
		$p_set = esc_attr(strip_tags(html_entity_decode($_POST['mime_type_values']),ENT_QUOTES));
		$mime_type_values = explode("\n", $p_set);
		if(!empty($mime_type_values)){
			foreach($mime_type_values as $m_type=>$m_value)
			// "　" is the Japanese multi-byte space. If the character is found out, it automatically change the space. 
				$mime_type_values[$m_type] = trim(str_replace("　", " ", $m_value));
			$settings['mime_type_values'] = serialize($mime_type_values);
		}
	}else
	$mime_type_values = unserialize($settings['mime_type_values']);

	// Update to WordPress Data.
	if(is_multisite() && is_plugin_active_for_network($plugin_basename))
		get_site_option('wp_add_mime_types_network_array', $settings);
	else
		update_option('wp_add_mime_types_array', $settings);
	
?>
<div class="add_mime_media_admin_setting_page_updated"><p><strong><?php _e('Updated', 'wp-add-mime-types'); ?></strong></p></div>

<div id="add_mime_media_admin_menu">
  <h2><?php _e('WP Add Mime Types Admin Settings', 'wp-add-mime-types'); ?></h2>
  
  <form method="post" action="">
     <fieldset style="border:1px solid #777777; width: 750px; padding-left: 6px;">
		<legend><h3><?php _e('List of allowed mime types and file extensions by WordPress','wp-add-mime-types'); ?></h3></legend>
		<div style="overflow:scroll; height: 500px;">
		<table>
<?php
// Get the list of the file extensions which WordPress allows the upload.
$allowed_mime_values = get_allowed_mime_types();

// Getting the extension name in the saved data
if(!empty($mime_type_values)){
	foreach ($mime_type_values as $line){
		$line_value = explode("=", $line);
		if(count($line_value) != 2) continue;
		$mimes[trim($line_value[0])] = trim($line_value[1]); 
	}
}

// List view of the allowed mime types including the addition (red color) in the admin settings.
if(!empty($allowed_mime_values)){
	foreach($allowed_mime_values as $type=>$value){
		// Escape strings
		$type = wp_strip_all_tags($type);
		$value = wp_strip_all_tags($value);
		if(isset($mimes)){
			$add_mime_type_check = "";
			foreach($mimes as $a_type=>$a_value){
				if(!strcmp($type, $a_type)){  
					$add_mime_type_check = " style='color:red;'";
					break;
				}
			}
			echo "<tr><td$add_mime_type_check>$type</td><td$add_mime_type_check>=</td><td$add_mime_type_check>$value</td></tr>\n";
		}else{
			echo "<tr><td>$type</td><td>=</td><td>$value</td></tr>\n";
		}
	}
}
?>
		</table>
	    </div>
     </fieldset>
	 <br/>

     <fieldset style="border:1px solid #777777; width: 750px; padding-left: 6px;">
		<legend><h3><?php _e('Add Values','wp-add-mime-types'); ?></h3></legend>
		<p><?php  _e('* About the mime type value for the file extension, please search "mime type [file extension name] using a search engine.<br/>Ex. epub = application/epub+zip<br/>Reference: <a href="http://www.iana.org/assignments/media-types/media-types.xhtml" target="_blank">Media Types on the Internet Assigned Numbers Authority (IANA)</a><br/>* If the added mime type does not work, please deactivate other mime type plugins or the setting of other mime type plugins.','wp-add-mime-types'); ?>
		<br/><?php  _e('* Ignore to the right of "#" on a line. ','wp-add-mime-types'); ?></p>
		<p><span style="color:red;"><?php  if(is_multisite() && is_plugin_active_for_network($plugin_basename)) _e('* The site administrator cannot add the value for mime type because the multisite is enabled. <br/>Please contact the multisite administrator if you would like to add the value.','wp-add-mime-types'); ?></span></p>

	<?php // If the permission is not allowed, the user can only read the setting. ?>
		<textarea name="mime_type_values" cols="100" rows="10" <?php if(!$permission || (is_multisite() && is_plugin_active_for_network($plugin_basename))) echo "disabled"; ?>><?php if(isset($mimes) && is_array($mimes)) foreach ($mimes as $m_type=>$m_value) echo $m_type . "\t= " .$m_value . "\n"; ?></textarea>
     </fieldset>

<?php
	if(is_multisite() && current_user_can('manage_network_options')){
		$past_mime_type_values = unserialize($past_settings['mime_type_values']);
		if(!empty($past_mime_type_values)){

?>
     <br/>
     <fieldset style="border:1px solid #777777; width: 750px; padding-left: 6px;">
		<legend><h3><?php _e('Past Add Values before Multisite function was enabled.','wp-add-mime-types'); ?></h3></legend>
		<p><span style="color:red;"><?php  _e('* This is for  multisite network administrators and site administrators.<br/> The following values are disabled after multisite function was enabled.','wp-add-mime-types'); ?></span></p>

	<?php // If the permission is not allowed, the user can only read the setting. ?>
		<textarea name="mime_type_values" cols="100" rows="10" disabled><?php if(isset($past_mime_type_values) && is_array($past_mime_type_values)) foreach ($past_mime_type_values as $m_type=>$m_value) echo  esc_html($m_value) . "\n"; ?></textarea>
     </fieldset>

<?php
		}
	}
?>

     <br/>
     
     <input type="submit" value="<?php _e('Save', 'wp-add-mime-types');  ?>" />
  </form>

</div>

<?php
}
