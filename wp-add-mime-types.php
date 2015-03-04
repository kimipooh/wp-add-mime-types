<?php
/*
Plugin Name: WP Add Mime Types 
Plugin URI: 
Description: The plugin additionally allows the mime types and file extensions to WordPress.
Version: 1.3.3
Author: Kimiya Kitani
Author URI: http://kitaney.jp/~kitani
*/

// Multi-language support.
load_plugin_textdomain('wp-add-mime-types', '/'.str_replace(ABSPATH, '', dirname(__FILE__)) . '/lang/');

$default_var = array(
	'wp_add_mime_types'	=>	'1.3.2',
);

// Add Setting to WordPress 'Settings' menu. 
add_action('admin_menu', 'add_to_settings_menu');
    
function add_to_settings_menu(){

    // add_options_page (Title, Setting Title, Permission, Special Definition, function name); 
	add_options_page(__('WP Add Mime Types Admin Settings', 'wp-add-mime-types'), __('Mime Type Settings','wp-add-mime-types'), 'manage_options', __FILE__,'admin_settings_page');

}

// Processing Setting menu for the plugin.
function admin_settings_page(){
    // Loading the stored setting data (wp_add_mime_types_array) from WordPress database.
	$settings = get_option('wp_add_mime_types_array');

	$permission = false;
    // The user who can manage the WordPress option can only access the Setting menu of this plugin.
	if(current_user_can('manage_options')) $permission = true; 
	// If the adding data is not set, the value "mime_type_values" sets "empty".
	if(!isset($settings['mime_type_values']))	$settings['mime_type_values'] = '';
	// When the adding data is saved (posted) at the setting menu, the data will update to the WordPress database after the security check
	if(isset($_POST['mime_type_values'])){
		$p_set = esc_attr(strip_tags(html_entity_decode($_POST['mime_type_values']),ENT_QUOTES));
		$mime_type_values = explode("\n", $p_set);
		foreach($mime_type_values as $m_type=>$m_value)
		    // "　" is the Japanese multi-byte space. If the character is found out, it automatically change the space. 
			$mime_type_values[$m_type] = trim(str_replace("　", " ", $m_value));
		$settings['mime_type_values'] = serialize($mime_type_values);
    }else
      $mime_type_values = unserialize($settings['mime_type_values']);
      
      
	// Update to WordPress Data.
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
foreach ($mime_type_values as $line){
   $line_value = explode("=", $line);
   if(count($line_value) != 2) continue;
   $mimes[trim($line_value[0])] = trim($line_value[1]); 
}   
    
// List view of the allowed mime types including the addition (red color) in the admin settings.
foreach($allowed_mime_values as $type=>$value){
  if(isset($mimes)){
    $add_mime_type_check = "";
     foreach($mimes as $a_type=>$a_value){
        if(!strcmp($type, $a_type)){  
              $add_mime_type_check = " style='color:red;'";
              break;
        }
     }

     echo "<tr><td$add_mime_type_check>$type</td><td$add_mime_type_check>=</td><td$add_mime_type_check>$value</td></tr>\n";
  }else
     echo "<tr><td>$type</td><td>=</td><td>$value</td></tr>\n";
}
?>
		</table>
	    </div>
     </fieldset>
	 <br/>

     <fieldset style="border:1px solid #777777; width: 750px; padding-left: 6px;">
		<legend><h3><?php _e('Add Values','wp-add-mime-types'); ?></h3></legend>
		<p><?php  _e('* About the mime type value for the file extension, please search "mime type [file extension name] using a search engine.<br/> Ex. "epub = application/epub+zip in http://ja.wikipedia.org/wiki/EPUB."','wp-add-mime-types'); ?></p>

	<?php // If the permission is not allowed, the user can only read the setting. ?>
		<textarea name="mime_type_values" cols="100" rows="10" <?php if(!$permission) echo "disabled"; ?>><?php if(isset($mimes) && is_array($mimes)) foreach ($mimes as $m_type=>$m_value) echo $m_type . "\t= " .$m_value . "\n"; ?></textarea>
     </fieldset>

     <br/>
     
     <input type="submit" value="<?php _e('Save', 'wp-add-mime-types');  ?>" />
  </form>

</div>

<?php
}
// Procedure for adding the mime types and file extensions to WordPress.
function add_allow_upload_extension( $mimes ) {
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


?>
